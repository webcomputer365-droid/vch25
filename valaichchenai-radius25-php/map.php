<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$allPlaces = db()->query('SELECT * FROM places WHERE is_active = 1 ORDER BY name')->fetchAll();
$places = array_values(array_filter(
    $allPlaces,
    static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
));
$activeNav = 'map';
$pageTitle = 'Valaichchenai places map';
$includeLeaflet = true;
$mapData = array_map(static function (array $place): array {
    return [
        'id' => (int) $place['id'],
        'name' => $place['name'],
        'category' => $place['category'],
        'latitude' => (float) $place['latitude'],
        'longitude' => (float) $place['longitude'],
        'url' => url('place.php?id=' . (int) $place['id']),
    ];
}, $places);
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
    <div class="container"><span class="eyebrow">Get your bearings</span><h1>Places around Valaichchenai.</h1><p>Browse the nearby stops on the map, then open a place for more information and visitor tips.</p></div>
</section>
<section class="section-tight">
    <div class="container">
        <div class="map-frame leaflet-map" id="daytrail-map" role="application" aria-label="Interactive map of places in Valaichchenai" data-places="<?= e(json_encode($mapData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) ?: '[]') ?>"></div>
        <p class="form-help">Map data © OpenStreetMap contributors. Locations and distances are approximate.</p>
    </div>
</section>
<section class="section-tight">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">All nearby places</span><h2 style="font-size:30px">Browse the guide</h2></div>
            <p><?= count($places) ?> active place<?= count($places) === 1 ? '' : 's' ?> within the local planning area.</p>
        </div>
        <?php if (!$places): ?>
            <div class="empty-state">No places are currently available.</div>
        <?php else: ?>
            <div class="place-grid">
                <?php foreach ($places as $place): ?>
                    <article class="place-card">
                        <a href="<?= e(url('place.php?id=' . (int) $place['id'])) ?>" aria-label="View <?= e($place['name']) ?>">
                            <div class="place-image">
                                <img src="<?= e(image_url($place)) ?>" alt="<?= e($place['name']) ?>" loading="lazy">
                                <span class="category-pill"><?= e($place['category']) ?></span>
                            </div>
                            <div class="place-card-body">
                                <h3><?= e($place['name']) ?></h3>
                                <p><?= e(mb_strimwidth((string) $place['description'], 0, 120, '…', 'UTF-8')) ?></p>
                                <div class="place-meta">
                                    <span><?= e(number_format(distance_from_home_km($place), 1)) ?> km away</span>
                                    <span><?= e(format_duration((int) $place['visit_duration'])) ?></span>
                                    <span class="card-arrow">Explore &#8599;</span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?= e(url('assets/js/map.js')) ?>" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>