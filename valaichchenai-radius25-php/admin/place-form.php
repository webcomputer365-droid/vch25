<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$place = $id ? place_by_id($pdo, $id, true) : null;
if ($id && !$place) {
    set_flash('error', 'That place could not be found.');
    redirect('admin/index.php');
}
$categories = place_categories();
$values = $place ?: [
    'name' => '', 'description' => '', 'category' => 'Heritage', 'address' => 'Batticaloa, Sri Lanka',
    'latitude' => '', 'longitude' => '', 'opening_time' => '', 'closing_time' => '', 'visit_duration' => 60,
    'entry_fee' => '', 'image_url' => '', 'travel_tips' => '', 'dress_code' => '', 'facilities' => '',
    'best_visit_time' => '', 'distance_from_home' => '', 'is_active' => 1,
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach ($values as $key => $value) {
        if (array_key_exists($key, $_POST)) {
            $values[$key] = trim((string) $_POST[$key]);
        }
    }
    $values['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $latitude = filter_var($values['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($values['longitude'], FILTER_VALIDATE_FLOAT);
    $duration = filter_var($values['visit_duration'], FILTER_VALIDATE_INT);
    $fee = $values['entry_fee'] === '' ? null : filter_var($values['entry_fee'], FILTER_VALIDATE_FLOAT);
    $distance = $latitude !== false && $longitude !== false
        ? round(haversine_km(HOME_LAT, HOME_LNG, (float) $latitude, (float) $longitude), 1)
        : null;
    $image = $values['image_url'] === '' ? null : filter_var($values['image_url'], FILTER_VALIDATE_URL);
    $imageScheme = $image ? strtolower((string) parse_url($image, PHP_URL_SCHEME)) : '';

    if (mb_strlen((string) $values['name']) < 2 || mb_strlen((string) $values['name']) > 180) {
        $error = 'Enter a place name between 2 and 180 characters.';
    } elseif (trim((string) $values['description']) === '') {
        $error = 'Add a short place description.';
    } elseif (!in_array($values['category'], $categories, true)) {
        $error = 'Choose one of the available categories.';
    } elseif ($latitude === false || $latitude < -90 || $latitude > 90 || $longitude === false || $longitude < -180 || $longitude > 180) {
        $error = 'Enter valid map coordinates.';
    } elseif (haversine_km(HOME_LAT, HOME_LNG, (float) $latitude, (float) $longitude) > MAX_PLACE_RADIUS_KM) {
        $error = 'Places added to the local guide must be within 25 km of the configured home location.';
    } elseif ($duration === false || $duration < 10 || $duration > 1440) {
        $error = 'Visit duration must be between 10 and 1,440 minutes.';
    } elseif ($values['entry_fee'] !== '' && $fee === false) {
        $error = 'Enter a valid entry fee or leave it blank.';
    } elseif ($values['image_url'] !== '' && (!$image || !in_array($imageScheme, ['http', 'https'], true))) {
        $error = 'Image URL must be a valid HTTP or HTTPS address.';
    } else {
        $data = [
            'name' => $values['name'],
            'description' => $values['description'],
            'category' => $values['category'],
            'address' => $values['address'] ?: 'Batticaloa, Sri Lanka',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'opening_time' => $values['opening_time'] !== '' ? $values['opening_time'] : null,
            'closing_time' => $values['closing_time'] !== '' ? $values['closing_time'] : null,
            'visit_duration' => $duration,
            'entry_fee' => $fee,
            'image_url' => $image,
            'travel_tips' => $values['travel_tips'] ?: null,
            'dress_code' => $values['dress_code'] ?: null,
            'facilities' => $values['facilities'] ?: null,
            'best_visit_time' => $values['best_visit_time'] ?: null,
            'distance_from_home' => $distance,
            'is_active' => (int) $values['is_active'],
        ];
        if ($id) {
            $data['id'] = $id;
            $sql = 'UPDATE places SET name=:name, description=:description, category=:category, address=:address, latitude=:latitude, longitude=:longitude, opening_time=:opening_time, closing_time=:closing_time, visit_duration=:visit_duration, entry_fee=:entry_fee, image_url=:image_url, travel_tips=:travel_tips, dress_code=:dress_code, facilities=:facilities, best_visit_time=:best_visit_time, distance_from_home=:distance_from_home, is_active=:is_active WHERE id=:id';
        } else {
            $sql = 'INSERT INTO places (name, description, category, address, latitude, longitude, opening_time, closing_time, visit_duration, entry_fee, image_url, travel_tips, dress_code, facilities, best_visit_time, distance_from_home, is_active) VALUES (:name, :description, :category, :address, :latitude, :longitude, :opening_time, :closing_time, :visit_duration, :entry_fee, :image_url, :travel_tips, :dress_code, :facilities, :best_visit_time, :distance_from_home, :is_active)';
        }
        $save = $pdo->prepare($sql);
        $save->execute($data);
        set_flash('success', $id ? 'Place details updated.' : 'Place added to DayTrail.');
        redirect('admin/index.php');
    }
}

$activeNav = 'admin';
$pageTitle = $id ? 'Edit place' : 'Add a place';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Administrator workspace</span><h1><?= $id ? 'Edit place details.' : 'Add a place.' ?></h1><p>Keep information specific, useful and current for visitors planning a day in Valaichchenai.</p></div></section>
<section class="container section-tight"><div class="panel">
    <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('admin/place-form.php' . ($id ? '?id=' . $id : ''))) ?>">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group"><label for="name">Place name</label><input class="field" id="name" name="name" maxlength="180" required value="<?= e($values['name']) ?>"></div>
            <div class="form-group"><label for="category">Category</label><select class="field" id="category" name="category"><?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= $values['category'] === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label for="description">Description</label><textarea class="field" id="description" name="description" required><?= e($values['description']) ?></textarea></div>
        <div class="form-group"><label for="address">Address</label><input class="field" id="address" name="address" maxlength="255" value="<?= e($values['address']) ?>"></div>
        <div class="form-row">
            <div class="form-group"><label for="latitude">Latitude</label><input class="field" id="latitude" name="latitude" type="number" step="any" min="-90" max="90" required value="<?= e($values['latitude']) ?>"></div>
            <div class="form-group"><label for="longitude">Longitude</label><input class="field" id="longitude" name="longitude" type="number" step="any" min="-180" max="180" required value="<?= e($values['longitude']) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="opening-time">Opening time</label><input class="field" id="opening-time" name="opening_time" type="time" value="<?= e($values['opening_time'] ? substr((string) $values['opening_time'], 0, 5) : '') ?>"></div>
            <div class="form-group"><label for="closing-time">Closing time</label><input class="field" id="closing-time" name="closing_time" type="time" value="<?= e($values['closing_time'] ? substr((string) $values['closing_time'], 0, 5) : '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="visit-duration">Suggested visit duration (minutes)</label><input class="field" id="visit-duration" name="visit_duration" type="number" min="10" max="1440" required value="<?= e($values['visit_duration']) ?>"></div>
            <div class="form-group"><label for="entry-fee">Entry fee (LKR)</label><input class="field" id="entry-fee" name="entry_fee" type="number" min="0" step="0.01" value="<?= e($values['entry_fee']) ?>"><span class="form-help">Leave blank if not confirmed.</span></div>
        </div>
        <div class="form-group"><label for="image-url">Image URL</label><input class="field" id="image-url" name="image_url" type="url" maxlength="1000" value="<?= e($values['image_url']) ?>"><span class="form-help">Use a public image you have permission to display. Leave blank for the local illustration.</span></div>
        <div class="form-group"><label for="travel-tips">Travel tips</label><textarea class="field" id="travel-tips" name="travel_tips"><?= e($values['travel_tips']) ?></textarea></div>
        <div class="form-group"><label for="dress-code">Cultural etiquette and dress code</label><textarea class="field" id="dress-code" name="dress_code"><?= e($values['dress_code']) ?></textarea></div>
        <div class="form-group"><label for="facilities">Facilities</label><textarea class="field" id="facilities" name="facilities"><?= e($values['facilities']) ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label for="best-visit-time">Best visiting time</label><input class="field" id="best-visit-time" name="best_visit_time" maxlength="120" value="<?= e($values['best_visit_time']) ?>"></div>
            <div class="form-group"><label>Estimated distance from <?= e(HOME_LABEL) ?></label><input class="field" type="text" value="<?= is_numeric($values['latitude']) && is_numeric($values['longitude']) ? e(number_format(haversine_km(HOME_LAT, HOME_LNG, (float) $values['latitude'], (float) $values['longitude']), 1) . ' km') : 'Enter coordinates to calculate' ?>" readonly><span class="form-help">Calculated automatically from the coordinates and configured home location.</span></div>
        </div>
        <div class="form-group"><label class="check-option"><input type="checkbox" name="is_active" value="1" <?= (int) $values['is_active'] === 1 ? 'checked' : '' ?>><span><strong>Show this place in visitor listings</strong></span></label></div>
        <div class="hero-actions"><button class="button button-primary" type="submit"><?= $id ? 'Save changes' : 'Add place' ?></button><a class="button button-soft" href="<?= e(url('admin/index.php')) ?>">Cancel</a></div>
    </form>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
