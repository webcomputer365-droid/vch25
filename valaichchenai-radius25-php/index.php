<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
$query = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$categories = place_categories();
if (!in_array($category, $categories, true)) {
    $category = '';
}

$conditions = ['is_active = 1'];
$params = [];
if ($query !== '') {
    $conditions[] = '(name LIKE :name_query OR description LIKE :description_query OR address LIKE :address_query)';
    $likeQuery = '%' . $query . '%';
    $params['name_query'] = $likeQuery;
    $params['description_query'] = $likeQuery;
    $params['address_query'] = $likeQuery;
}
if ($category !== '') {
    $conditions[] = 'category = :category';
    $params['category'] = $category;
}
$statement = $pdo->prepare('SELECT * FROM places WHERE ' . implode(' AND ', $conditions) . ' ORDER BY name');
$statement->execute($params);
$places = array_values(array_filter(
    $statement->fetchAll(),
    static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
));
$totalPlaces = count(array_filter(
    $pdo->query('SELECT latitude, longitude FROM places WHERE is_active = 1')->fetchAll(),
    static fn(array $place): bool => distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM
));
$activeNav = 'explore';
$pageTitle = 'Discover Valaichchenai in a day';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-inner">
        <div class="hero-copy">
            <span class="eyebrow">Radius25 · a day on the East Coast</span>
            <h1>Meet the<br>coastline.</h1>
            <p>Calm beaches, lagoon rides, quiet viewpoints and rural heritage — bring the best of Valaichchenai together in one thoughtful day.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="#places">Explore places <span aria-hidden="true">→</span></a>
                <a class="button button-outline" href="<?= e(url('planner.php')) ?>">Plan my day</a>
            </div>
            <div class="hero-note">Within 25 km of Valaichchenai town centre.</div>
        </div>
    </div>
</section>
<section class="intro-strip" aria-label="At a glance">
    <div class="container">
        <div class="intro-item"><span class="intro-dot" aria-hidden="true">01</span><span><strong><?= $totalPlaces ?> nearby places</strong>Beach, lagoon, heritage</span></div>
        <div class="intro-item"><span class="intro-dot" aria-hidden="true">02</span><span><strong>Plan at your pace</strong>Build a realistic day route</span></div>
        <div class="intro-item"><span class="intro-dot" aria-hidden="true">03</span><span><strong>Travel respectfully</strong>Local tips for every stop</span></div>
    </div>
</section>
<section class="section" id="places">
    <div class="container">
        <div class="section-heading">
            <div><span class="eyebrow">The places that make a day</span><h2>Start with a place.</h2></div>
            <p>Explore local landmarks and build a day around what interests you most.</p>
        </div>
        <form class="filter-form" method="get" action="<?= e(url('')) ?>" role="search">
            <label class="search-field">
                <span class="hide">Search places</span>
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="      Search by place or keyword">
            </label>
            <label><span class="hide">Filter by category</span>
                <select name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $option): ?>
                        <option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="button button-primary" type="submit">Find places</button>
            <?php if ($query !== '' || $category !== ''): ?><a class="button button-soft" href="<?= e(url('')) ?>">Clear</a><?php endif; ?>
        </form>
        <?php if ($places): ?>
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
        <?php else: ?>
            <div class="empty-state"><strong>No places match that search.</strong><br>Try another word or clear the category filter.</div>
        <?php endif; ?>
    </div>
</section>
<section class="section-tight">
    <div class="container">
        <div class="planner-callout">
            <div><span class="eyebrow">Make the most of your day</span><h2>Good days have a good route.</h2><p>Choose your stops, set a pace and get a practical visit plan with estimated timings and opening-hour notes.</p></div>
            <a class="button button-primary" href="<?= e(url('planner.php')) ?>">Build my day <span aria-hidden="true">→</span></a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>