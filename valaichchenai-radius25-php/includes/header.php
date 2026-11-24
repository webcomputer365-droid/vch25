<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Discover Valaichchenai and the East Coast in a day';
$activeNav = $activeNav ?? '';
$flash = take_flash();
$user = current_user();

// Stale session guard
if ($user && !empty($user['id'])) {
    try {
        $probe = db()->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
        $probe->execute(['id' => (int) $user['id']]);
        if (!$probe->fetch()) {
            $_SESSION = [];
            session_regenerate_id(true);
            $user = null;
            $flash = ['type' => 'error', 'message' => 'Your session has expired. Please sign in again.'];
        }
    } catch (Throwable $e) {
        // DB unreachable — leave the session alone.
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · Radius25</title>
    <meta name="description" content="Radius25 — a coastal day-trip guide and 25 km itinerary planner for Valaichchenai and the East Coast of Sri Lanka.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <?php if (!empty($includeLeaflet)): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="Radius25 home">
        <span class="brand-mark" aria-hidden="true">R25</span>
        <span><strong>Radius25</strong><small>VALAICHCHENAI · EAST COAST</small></span>
    </a>
    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="main-nav" data-nav-toggle>Menu</button>
    <nav class="main-nav" id="main-nav" data-nav>
        <?php if ($user && is_admin()): ?>
            <a class="<?= $activeNav === 'explore' ? 'is-active' : '' ?>" href="<?= e(url('')) ?>">Explore</a>
            <a class="<?= $activeNav === 'about' ? 'is-active' : '' ?>" href="<?= e(url('about.php')) ?>">About</a>
            <a class="<?= $activeNav === 'admin' ? 'is-active' : '' ?>" href="<?= e(url('admin/index.php')) ?>">Admin</a>
            <a class="<?= $activeNav === 'visitors' ? 'is-active' : '' ?>" href="<?= e(url('admin/visitors.php')) ?>">Visitor accounts</a>
            <a class="<?= $activeNav === 'map' ? 'is-active' : '' ?>" href="<?= e(url('map.php')) ?>">Map</a>
            <a class="<?= $activeNav === 'planner' ? 'is-active' : '' ?>" href="<?= e(url('planner.php')) ?>">Day planner</a>
            <a class="<?= $activeNav === 'plans' ? 'is-active' : '' ?>" href="<?= e(url('my-plans.php')) ?>">My plans</a>
            <form class="nav-inline-form" method="post" action="<?= e(url('logout.php')) ?>">
                <?= csrf_field() ?><button class="nav-signin" type="submit">Sign out</button>
            </form>
        <?php else: ?>
            <a class="<?= $activeNav === 'explore' ? 'is-active' : '' ?>" href="<?= e(url('')) ?>">Explore</a>
            <a class="<?= $activeNav === 'about' ? 'is-active' : '' ?>" href="<?= e(url('about.php')) ?>">About</a>
            <a class="<?= $activeNav === 'map' ? 'is-active' : '' ?>" href="<?= e(url('map.php')) ?>">Map</a>
            <a class="<?= $activeNav === 'planner' ? 'is-active' : '' ?>" href="<?= e(url('planner.php')) ?>">Day planner</a>
            <?php if ($user): ?>
                <a class="<?= $activeNav === 'plans' ? 'is-active' : '' ?>" href="<?= e(url('my-plans.php')) ?>">My plans</a>
                <span class="nav-user">Hello, <?= e($user['name']) ?></span>
                <form class="nav-inline-form" method="post" action="<?= e(url('logout.php')) ?>">
                    <?= csrf_field() ?><button class="nav-signin" type="submit">Sign out</button>
                </form>
            <?php else: ?>
                <a class="nav-signin" href="<?= e(url('login.php')) ?>">Sign in</a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>
</header>
<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
<?php endif; ?>
<main id="main">