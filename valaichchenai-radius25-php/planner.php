<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/planner.php';
$pdo = db();
$activeNav = 'planner';
$pageTitle = 'Build your one-day plan';
$places = array_values(array_filter(
    $pdo->query('SELECT * FROM places WHERE is_active = 1 ORDER BY name')->fetchAll(),
    static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
));
$plan = null;
$planTitle = '';
$planDate = date('Y-m-d');
$startTime = '08:00';
$pace = 'balanced';
$savedPlanId = null;
$formError = '';
$selectedIds = [];

if (!isset($_GET['id']) && isset($_GET['place'])) {
    $initialPlace = place_by_id($pdo, filter_input(INPUT_GET, 'place', FILTER_VALIDATE_INT) ?: 0);
    if ($initialPlace) {
        $selectedIds[] = (int) $initialPlace['id'];
    }
}

if (isset($_GET['id']) && current_user()) {
    $savedStatement = $pdo->prepare('SELECT * FROM itineraries WHERE id = :id AND user_id = :user_id LIMIT 1');
    $savedStatement->execute(['id' => (int) $_GET['id'], 'user_id' => (int) current_user()['id']]);
    $saved = $savedStatement->fetch();
    if ($saved) {
        $storedItems = json_decode((string) $saved['route_order'], true);
        $storedWarnings = json_decode((string) $saved['warnings'], true);
        $plan = [
            'items' => is_array($storedItems) ? $storedItems : [],
            'warnings' => is_array($storedWarnings) ? $storedWarnings : [],
            'total_distance' => (float) $saved['total_distance'],
            'total_travel_time' => (int) $saved['total_travel_time'],
            'total_visit_time' => 0,
            'total_duration' => (int) $saved['total_travel_time'],
            'start_minutes' => ((int) substr((string) $saved['start_time'], 0, 2) * 60) + (int) substr((string) $saved['start_time'], 3, 2),
            'end_minutes' => is_array($storedItems) && $storedItems
                ? (int) end($storedItems)['visit_end_minutes']
                : ((int) substr((string) $saved['expected_end_time'], 0, 2) * 60) + (int) substr((string) $saved['expected_end_time'], 3, 2),
            'pace' => $saved['pace'],
            'pace_label' => ucfirst($saved['pace']),
        ];
        $planTitle = (string) $saved['title'];
        $planDate = (string) $saved['visit_date'];
        $startTime = substr((string) $saved['start_time'], 0, 5);
        $pace = (string) $saved['pace'];
        $savedPlanId = (int) $saved['id'];
        foreach ($plan['items'] as $item) {
            if (isset($item['place']['id'])) {
                $selectedIds[] = (int) $item['place']['id'];
            }
        }
    } else {
        set_flash('error', 'That saved plan could not be found.');
        redirect('my-plans.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // ---- 1. Guests must sign in first ----------------------------------
    if (!current_user()) {
        set_flash('error', 'Please sign in or create an account to build and save your day plan.');
        $postedPlaceIds = array_map('intval', (array) ($_POST['places'] ?? []));
        $returnQuery = http_build_query([
            'next' => 'planner.php',
            'resume' => 1,
            'visit_date' => (string) ($_POST['visit_date'] ?? date('Y-m-d')),
            'start_time' => (string) ($_POST['start_time'] ?? '08:00'),
            'pace' => (string) ($_POST['pace'] ?? 'balanced'),
            'title' => trim((string) ($_POST['title'] ?? '')),
            'places' => array_values(array_filter($postedPlaceIds, static fn(int $v): bool => $v > 0)),
        ], '', '&', PHP_QUERY_RFC3986);
        redirect('login.php?' . $returnQuery);
    }

    // ---- 2. Verify the session user still exists in DB -----------------
    $liveStmt = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
    $liveStmt->execute(['id' => (int) current_user()['id']]);
    $liveId = $liveStmt->fetchColumn();
    if ($liveId === false) {
        $_SESSION = [];
        session_regenerate_id(true);
        set_flash('error', 'Your session has expired. Please sign in again.');
        redirect('login.php?next=' . rawurlencode('planner.php'));
    }
    $liveId = (int) $liveId;

    $ids = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['places'] ?? [])),
        static fn(int $id): bool => $id > 0
    )));
    $selectedIds = array_slice($ids, 0, 8);
    $startTime = (string) ($_POST['start_time'] ?? '08:00');
    $pace = (string) ($_POST['pace'] ?? 'balanced');
    $planDate = (string) ($_POST['visit_date'] ?? date('Y-m-d'));
    $planTitle = trim((string) ($_POST['title'] ?? ''));

    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $startTime)) {
        $formError = 'Choose a valid start time.';
    } elseif (!in_array($pace, ['relaxed', 'balanced', 'quick'], true)) {
        $formError = 'Choose one of the available travel paces.';
    } elseif (!$selectedIds) {
        $formError = 'Choose at least one place to build your route.';
    } elseif (count($ids) > 8) {
        $formError = 'Choose up to eight places for one day.';
    } elseif (($parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $planDate)) === false ||
        $parsedDate->format('Y-m-d') !== $planDate ||
        (DateTimeImmutable::getLastErrors() !== false &&
            (DateTimeImmutable::getLastErrors()['warning_count'] > 0 || DateTimeImmutable::getLastErrors()['error_count'] > 0))) {
        $formError = 'Choose a valid visit date.';
    } else {
        $placeQuery = $pdo->prepare('SELECT * FROM places WHERE is_active = 1 AND id IN (' . implode(',', array_fill(0, count($selectedIds), '?')) . ')');
        $placeQuery->execute($selectedIds);
        $selectedPlaces = array_values(array_filter(
            $placeQuery->fetchAll(),
            static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
        ));
        if (count($selectedPlaces) !== count($selectedIds)) {
            $formError = 'One or more selected places are no longer available within the local 25 km planning area.';
        } else {
            $plan = build_day_plan($selectedPlaces, $startTime, $pace);

            if  (current_user() && $liveId > 0) {
                $planTitle = $planTitle !== '' ? mb_substr($planTitle, 0, 160) : 'Valaichchenai day plan';
                $saveValues = [
                    'user_id' => $liveId,
                    'title' => $planTitle,
                    'visit_date' => $planDate,
                    'start_time' => $startTime,
                    'pace' => $pace,
                    'route_order' => json_encode($plan['items'], JSON_THROW_ON_ERROR),
                    'total_distance' => $plan['total_distance'],
                    'total_travel_time' => $plan['total_travel_time'],
                    'expected_end_time' => sprintf('%02d:%02d', intdiv($plan['end_minutes'] % 1440, 60), $plan['end_minutes'] % 60),
                    'warnings' => json_encode($plan['warnings'], JSON_THROW_ON_ERROR),
                ];
                $requestedPlanId = filter_var($_POST['itinerary_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
                $ownedPlan = null;
                if ($requestedPlanId > 0) {
                    $ownerCheck = $pdo->prepare('SELECT id FROM itineraries WHERE id = :id AND user_id = :user_id');
                    $ownerCheck->execute(['id' => $requestedPlanId, 'user_id' => $liveId]);
                    $ownedPlan = $ownerCheck->fetch();
                }
                if ($ownedPlan) {
                    $saveValues['id'] = $requestedPlanId;
                    $update = $pdo->prepare(
                        'UPDATE itineraries SET title=:title, visit_date=:visit_date, start_time=:start_time, pace=:pace, route_order=:route_order, total_distance=:total_distance, total_travel_time=:total_travel_time, expected_end_time=:expected_end_time, warnings=:warnings WHERE id=:id AND user_id=:user_id'
                    );
                    $update->execute($saveValues);
                    $savedPlanId = $requestedPlanId;
                } else {
                    $insert = $pdo->prepare(
                        'INSERT INTO itineraries (user_id, title, visit_date, start_time, pace, route_order, total_distance, total_travel_time, expected_end_time, warnings)
                         VALUES (:user_id, :title, :visit_date, :start_time, :pace, :route_order, :total_distance, :total_travel_time, :expected_end_time, :warnings)'
                    );
                    $insert->execute($saveValues);
                    $savedPlanId = (int) $pdo->lastInsertId();
                }
                set_flash('success', 'Your day plan has been saved to your account.');
                redirect('planner.php?id=' . $savedPlanId);
            }
        }
    }
}

// ---- 3. Resume from login round-trip (only when signed in) ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['resume']) && current_user()) {
    $planDate = (string) ($_GET['visit_date'] ?? $planDate);
    $startTime = (string) ($_GET['start_time'] ?? $startTime);
    $paceInput = (string) ($_GET['pace'] ?? $pace);
    if (in_array($paceInput, ['relaxed', 'balanced', 'quick'], true)) {
        $pace = $paceInput;
    }
    $planTitle = trim((string) ($_GET['title'] ?? ''));
    $resumePlaces = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_GET['places'] ?? [])),
        static fn(int $id): bool => $id > 0
    )));
    if ($resumePlaces) {
        $selectedIds = array_slice($resumePlaces, 0, 8);
        $placeQuery = $pdo->prepare('SELECT * FROM places WHERE is_active = 1 AND id IN (' . implode(',', array_fill(0, count($selectedIds), '?')) . ')');
        $placeQuery->execute($selectedIds);
        $resumeRows = array_values(array_filter(
            $placeQuery->fetchAll(),
            static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
        ));
        if (count($resumeRows) === count($selectedIds)) {
            $plan = build_day_plan($resumeRows, $startTime, $pace);
        }
    }
}

$selectedSet = array_fill_keys($selectedIds, true);

$mapPlaces = [];
if ($plan && !empty($plan['items'])) {
    foreach ($plan['items'] as $item) {
        $p = $item['place'];
        $mapPlaces[] = [
            'id' => (int) $p['id'],
            'name' => (string) $p['name'],
            'category' => (string) $p['category'],
            'latitude' => (float) $p['latitude'],
            'longitude' => (float) $p['longitude'],
        ];
    }
} else {
    foreach ($places as $p) {
        if (isset($selectedSet[(int) $p['id']])) {
            $mapPlaces[] = [
                'id' => (int) $p['id'],
                'name' => (string) $p['name'],
                'category' => (string) $p['category'],
                'latitude' => (float) $p['latitude'],
                'longitude' => (float) $p['longitude'],
            ];
        }
    }
}
$home = ['lat' => HOME_LAT, 'lng' => HOME_LNG, 'label' => HOME_LABEL];
$includeLeaflet = true;
$user = current_user();

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container"><span class="eyebrow">Your day, your pace</span><h1><?= $savedPlanId ? e($planTitle) : 'Shape a day on the East Coast.' ?></h1><p>Choose a few places and we’ll suggest a practical order, estimated travel times and any opening-hour conflicts to check.</p></div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="panel" style="padding:18px">
            <div class="section-heading" style="margin-bottom:12px">
                <div>
                    <span class="eyebrow">Your route on the map</span>
                    <h2 style="font-size:22px;margin:4px 0 0">Selected stops &amp; travel path</h2>
                </div>
                
            </div>
            <div class="map-frame leaflet-map" id="planner-map" role="application" aria-label="Map of selected itinerary stops"
                 data-places="<?= e(json_encode($mapPlaces, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?: '[]') ?>"
                 data-home="<?= e(json_encode($home, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?: '{}') ?>"
                 style="height:360px"></div>
        </div>
    </div>
</section>

<section class="container planner-layout">
    <form class="panel" method="post" action="<?= e(url('planner.php')) ?>">
        <?= csrf_field() ?>
        <?php if ($savedPlanId): ?><input type="hidden" name="itinerary_id" value="<?= $savedPlanId ?>"><?php endif; ?>
        <h2>Build your route</h2>
        <p class="panel-lead">Start near <?= e(HOME_LABEL) ?>. Choose up to eight stops.</p>
        <?php if ($formError !== ''): ?><div class="flash flash-error" role="alert"><?= e($formError) ?></div><?php endif; ?>
        <div class="form-row">
            <div class="form-group"><label for="visit-date">Visit date</label><input class="field" id="visit-date" name="visit_date" type="date" value="<?= e($planDate) ?>" required></div>
            <div class="form-group"><label for="start-time">Start time</label><input class="field" id="start-time" name="start_time" type="time" value="<?= e($startTime) ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="pace">Travel pace</label><select class="field" id="pace" name="pace"><option value="relaxed" <?= $pace === 'relaxed' ? 'selected' : '' ?>>Relaxed — longer visits</option><option value="balanced" <?= $pace === 'balanced' ? 'selected' : '' ?>>Balanced — just right</option><option value="quick" <?= $pace === 'quick' ? 'selected' : '' ?>>Quick — shorter visits</option></select></div>
            <div class="form-group"><label for="plan-title">Plan name <span class="form-help">(optional)</span></label><input class="field" id="plan-title" name="title" type="text" maxlength="160" value="<?= e($planTitle) ?>" placeholder="A day along the coast"></div>
        </div>
        <div class="section-heading"><div><h2 style="font-size:22px">Choose your stops</h2></div><button class="button button-soft button-small" type="button" data-select-all>Select all shown</button></div>
        <div class="checklist">
            <?php foreach ($places as $place): ?>
                <?php $placeId = (int) $place['id']; ?>
                <label class="check-option">
                    <input data-place-select type="checkbox" name="places[]" value="<?= $placeId ?>" <?= isset($selectedSet[$placeId]) ? 'checked' : '' ?>>
                    <span><strong><?= e($place['name']) ?></strong><small><?= e($place['category']) ?> · <?= e(format_duration((int) $place['visit_duration'])) ?></small></span>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if ($user): ?>
            <div class="form-group"><label class="check-option"><input type="checkbox" name="save_plan" value="1" <?= $savedPlanId ? 'checked' : '' ?>><span><strong>Save this plan to my account</strong><small>Find it again from My plans.</small></span></label></div>
        <?php else: ?>
            <div class="warning-box">You’ll be asked to sign in (or create a free account) before your day plan is created. <a class="text-link" href="<?= e(url('login.php?next=planner.php')) ?>">Sign in now</a></div>
        <?php endif; ?>
        <button class="button button-primary" type="submit"><?= $savedPlanId ? 'Update plan preview' : 'Create my day plan' ?> <span aria-hidden="true">→</span></button>
    </form>
    <aside class="panel plan-summary">
        <h2><?= $plan ? 'Your day, mapped out.' : 'A good day starts here.' ?></h2>
        <p class="panel-lead"><?= $plan ? e($planDate) . ' · ' . e($plan['pace_label']) . ' pace' : 'Your route summary will appear here once you choose places.' ?></p>
        <?php if ($plan): ?>
            <div class="summary-metrics">
                <div class="summary-metric"><strong><?= e(number_format((float) $plan['total_distance'], 1)) ?> km</strong><small>EST. ROAD DISTANCE</small></div>
                <div class="summary-metric"><strong><?= e(format_duration((int) $plan['total_travel_time'])) ?></strong><small>ON THE ROAD</small></div>
                <div class="summary-metric"><strong><?= e(clock_label((int) $plan['end_minutes'])) ?></strong><small>EXPECTED FINISH</small></div>
            </div>
            <?php foreach ($plan['warnings'] as $warning): ?><div class="warning-box"><?= e($warning) ?></div><?php endforeach; ?>
            <div class="timeline">
                <?php foreach ($plan['items'] as $index => $item): ?>
                    <?php $planPlace = $item['place']; ?>
                    <article class="timeline-item">
                        <span class="timeline-time"><?= e(clock_label((int) $item['arrival_minutes'])) ?> · Stop <?= $index + 1 ?></span>
                        <h3><a href="<?= e(url('place.php?id=' . (int) $planPlace['id'])) ?>"><?= e($planPlace['name']) ?></a></h3>
                        <p><?= e(format_duration((int) $item['travel_minutes'])) ?> drive · <?= e(number_format((float) $item['distance_km'], 1)) ?> km · Visit <?= e(clock_label((int) $item['visit_start_minutes'])) ?>–<?= e(clock_label((int) $item['visit_end_minutes'])) ?></p>
                        <?php foreach ($item['warnings'] as $warning): ?><p class="text-muted"><?= e($warning) ?></p><?php endforeach; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <a class="button button-soft" target="_blank" rel="noopener noreferrer" href="<?= e(maps_directions_url(array_map(static fn(array $item): array => $item['place'], $plan['items']))) ?>">Open route in Google Maps</a>
            <?php if ($savedPlanId): ?><a class="button button-soft" href="<?= e(url('my-plans.php')) ?>">View my saved plans</a><?php endif; ?>
        <?php else: ?>
            <div class="plan-empty"><strong>Your itinerary takes shape here.</strong><br>Choose a date, a travel pace and the places you’d like to see.</div>
            <div class="detail-section"><h2>How timing works</h2><p>Visit durations and drive times are estimates. When a place’s listed opening hours are available, the plan flags likely conflicts so you can verify them before setting out.</p></div>
        <?php endif; ?>
    </aside>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= e(url('assets/js/planner-map.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>