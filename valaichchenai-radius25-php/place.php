<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$place = place_by_id(db(), $id);
if (!$place || distance_from_home_km($place) > MAX_PLACE_RADIUS_KM) {
    http_response_code(404);
    $pageTitle = 'Place not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><strong>We could not find that place.</strong><br><a class="text-link" href="' . e(url('')) . '">Return to explore</a></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$activeNav = 'explore';
$pageTitle = $place['name'];
$lat = (float) $place['latitude'];
$lng = (float) $place['longitude'];
$distanceFromHome = distance_from_home_km($place);
$mapEmbed = 'https://www.openstreetmap.org/export/embed.html?bbox=' .
    rawurlencode(($lng - 0.008) . ',' . ($lat - 0.006) . ',' . ($lng + 0.008) . ',' . ($lat + 0.006)) .
    '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow"><?= e($place['category']) ?> · Valaichchenai </span>
        <h1><?= e($place['name']) ?></h1>
        <p><?= e($place['address']) ?> · About <?= e(number_format($distanceFromHome, 1)) ?> km from <?= e(HOME_LABEL) ?></p>
    </div>
</section>
<section class="container place-detail">
    <div>
        <img class="detail-image" src="<?= e(image_url($place)) ?>" alt="<?= e($place['name']) ?>">
        <div class="detail-section">
            <h2>About this place</h2><p><?= nl2br(e($place['description'])) ?></p>
        </div>
        <?php if (!empty($place['travel_tips'])): ?>
            <div class="detail-section"><h2>A few local tips</h2><p><?= nl2br(e($place['travel_tips'])) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($place['dress_code'])): ?>
            <div class="detail-section"><h2>Respectful visits</h2><p><?= nl2br(e($place['dress_code'])) ?></p></div>
        <?php endif; ?>
        <?php if (!empty($place['facilities'])): ?>
            <div class="detail-section"><h2>Facilities</h2><p><?= nl2br(e($place['facilities'])) ?></p></div>
        <?php endif; ?>
    </div>
    <aside class="detail-side">
        <div class="detail-stats">
            <div class="stat-box"><small>Opening hours</small><strong><?= $place['opening_time'] && $place['closing_time'] ? e(date('g:i A', strtotime($place['opening_time'])) . ' – ' . date('g:i A', strtotime($place['closing_time']))) : 'Check locally' ?></strong></div>
            <div class="stat-box"><small>Time to allow</small><strong><?= e(format_duration((int) $place['visit_duration'])) ?></strong></div>
            <div class="stat-box"><small>Entry fee</small><strong><?= e(format_fee($place['entry_fee'])) ?></strong></div>
            <div class="stat-box"><small>Best time</small><strong><?= e($place['best_visit_time'] ?: 'Check locally') ?></strong></div>
        </div>
        <iframe class="map-frame" title="Map showing <?= e($place['name']) ?>" loading="lazy" src="<?= e($mapEmbed) ?>"></iframe>
        <div class="hero-actions spacer-top">
            <a class="button button-primary" href="<?= e(url('planner.php?place=' . (int) $place['id'])) ?>">Add to my day</a>
            <a class="button button-soft" href="<?= e(map_url($lat, $lng)) ?>" target="_blank" rel="noopener noreferrer">Open map</a>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
