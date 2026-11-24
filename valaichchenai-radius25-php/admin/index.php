<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();
$places = $pdo->query('SELECT id, name, category, latitude, longitude, distance_from_home, is_active, updated_at FROM places ORDER BY name')->fetchAll();
$activeCount = count(array_filter($places, static fn(array $place): bool => (int) $place['is_active'] === 1 && distance_from_home_km($place) <= MAX_PLACE_RADIUS_KM));
$userCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'visitor'")->fetchColumn();
$planCount = (int) $pdo->query('SELECT COUNT(*) FROM itineraries')->fetchColumn();
$activeNav = 'admin';
$pageTitle = 'Place management';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Administrator workspace</span><h1>Keep local details current.</h1><p>Manage the place information visitors use to explore Valaichchenai and build their one-day plans.</p></div></section>
<section class="section-tight"><div class="container">
    <div class="admin-head">
        <div>
            <h2 style="font:600 30px 'Playfair Display',serif;margin:0">Overview</h2>
            <p class="text-muted">Signed in as <?= e(current_user()['name']) ?>.</p>
        </div>
        <div class="hero-actions">
            <a class="button button-soft" href="<?= e(url('admin/visitors.php')) ?>">Visitor accounts</a>
            <a class="button button-primary" href="<?= e(url('admin/place-form.php')) ?>">Add a place <span aria-hidden="true">+</span></a>
        </div>
    </div>
    <div class="admin-stats">
        <div class="admin-stat"><strong><?= $activeCount ?></strong><small>Active places</small></div>
        <div class="admin-stat"><strong><?= $userCount ?></strong><small>Visitor accounts</small></div>
        <div class="admin-stat"><strong><?= $planCount ?></strong><small>Saved day plans</small></div>
    </div>
    <div class="section-heading"><div><span class="eyebrow">Your place directory</span><h2 style="font-size:30px">Manage places</h2></div><p>Check details regularly. Opening times, fees and visitor guidance may change.</p></div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>Place</th><th>Category</th><th>Distance from origin</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($places as $place): ?>
        <tr>
            <td><strong><?= e($place['name']) ?></strong></td>
            <td><?= e($place['category']) ?></td>
            <td><?= $place['distance_from_home'] !== null ? e(number_format((float) $place['distance_from_home'], 1)) . ' km' : 'Not set' ?></td>
            <td><span class="badge"><?= (int) $place['is_active'] === 1 ? 'Active' : 'Hidden' ?></span></td>
            <td><div class="table-actions">
                <a class="button button-soft button-small" href="<?= e(url('admin/place-form.php?id=' . (int) $place['id'])) ?>">Edit</a>
                <form method="post" action="<?= e(url('admin/delete-place.php')) ?>" data-confirm="Permanently delete <?= e($place['name']) ?>? Existing saved itinerary snapshots will remain, but this place cannot be restored from the app.">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $place['id'] ?>">
                    <button class="button button-danger button-small" type="submit">Delete</button>
                </form>
            </div></td>
        </tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>