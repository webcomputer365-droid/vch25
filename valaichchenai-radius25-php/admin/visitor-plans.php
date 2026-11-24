<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id AND role = "visitor" LIMIT 1');
$stmt->execute(['id' => $id]);
$visitor = $stmt->fetch();
if (!$visitor) {
    set_flash('error', 'That visitor account could not be found.');
    redirect('admin/visitors.php');
}

$plansStmt = $pdo->prepare(
    'SELECT id, title, visit_date, start_time, pace, route_order,
            total_distance, total_travel_time, expected_end_time, created_at
     FROM itineraries
     WHERE user_id = :user_id
     ORDER BY visit_date DESC, created_at DESC'
);
$plansStmt->execute(['user_id' => $id]);
$plans = $plansStmt->fetchAll();

// Decode category info for each plan.
$enrichedPlans = [];
foreach ($plans as $plan) {
    $items = json_decode((string) $plan['route_order'], true);
    $items = is_array($items) ? $items : [];

    $placesInPlan = [];
    $categoryCounts = [];
    foreach ($items as $item) {
        $place = $item['place'] ?? null;
        if (!is_array($place)) {
            continue;
        }
        $name = (string) ($place['name'] ?? '');
        $category = (string) ($place['category'] ?? 'Uncategorised');
        $placesInPlan[] = ['name' => $name, 'category' => $category];
        $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
    }

    $enrichedPlans[] = [
        'plan' => $plan,
        'places' => $placesInPlan,
        'category_counts' => $categoryCounts,
    ];
}

$activeNav = 'visitors';
$pageTitle = 'Plans for ' . $visitor['name'];
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Administrator workspace</span>
        <h1>Plans for <?= e($visitor['name']) ?>.</h1>
        <p>
            <a class="text-link" href="mailto:<?= e($visitor['email']) ?>"><?= e($visitor['email']) ?></a>
            · <?= count($enrichedPlans) ?> saved plan<?= count($enrichedPlans) === 1 ? '' : 's' ?>.
        </p>
    </div>
</section>
<section class="section-tight">
    <div class="container">
        <div class="admin-head">
            <div>
                <h2 style="font:600 30px 'Playfair Display',serif;margin:0">Saved itineraries</h2>
                <p class="text-muted">Delete a single plan here; the visitor account stays intact.</p>
            </div>
            <a class="button button-soft" href="<?= e(url('admin/visitors.php')) ?>">Back to visitors</a>
        </div>

        <?php if (!$enrichedPlans): ?>
            <div class="empty-state">
                This visitor has not saved any plans yet.<br>
                They will appear here when the visitor creates a plan on the
                <a class="text-link" href="<?= e(url('planner.php')) ?>">day planner</a>.
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Visit date</th>
                            <th>Start</th>
                            <th>Pace</th>
                            <th>Categories covered</th>
                            <th>Places</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enrichedPlans as $row): ?>
                        <?php
                            $plan = $row['plan'];
                            $categories = $row['category_counts'];
                            ksort($categories);
                        ?>
                        <tr>
                            <td><strong><?= e($plan['title']) ?></strong></td>
                            <td><?= e(date('M j, Y', strtotime($plan['visit_date']))) ?></td>
                            <td><?= e(date('g:i A', strtotime($plan['start_time']))) ?></td>
                            <td><?= e(ucfirst($plan['pace'])) ?></td>
                            <td>
                                <?php if (!$categories): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <div class="category-tags">
                                        <?php foreach ($categories as $catName => $count): ?>
                                            <span class="badge">
                                                <?= e($catName) ?><?= $count > 1 ? ' ×' . $count : '' ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$row['places']): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <ul class="plan-places">
                                        <?php foreach ($row['places'] as $p): ?>
                                            <li>
                                                <strong><?= e($p['name']) ?></strong>
                                                <small class="text-muted"> · <?= e($p['category']) ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-soft button-small" href="<?= e(url('planner.php?id=' . (int) $plan['id'])) ?>">Open</a>
                                    <form method="post" action="<?= e(url('delete-plan.php')) ?>" data-confirm="Delete this plan only? The visitor account and other plans are not affected.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $visitor['id'] ?>">
                                        <input type="hidden" name="back" value="visitor-plans">
                                        <button class="button button-danger button-small" type="submit">Delete plan</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>