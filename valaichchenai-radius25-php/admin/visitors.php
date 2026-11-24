<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();

$users = $pdo->query(
    'SELECT u.id, u.name, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM itineraries i WHERE i.user_id = u.id) AS plan_count
     FROM users u
     WHERE u.role = "visitor"
     ORDER BY u.created_at DESC'
)->fetchAll();

$activeNav = 'visitors';
$pageTitle = 'Visitor accounts';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Administrator workspace</span>
        <h1>Visitor accounts.</h1>
        <p>Review registered visitors, their email addresses, and how many day plans each has saved.</p>
    </div>
</section>
<section class="section-tight">
    <div class="container">
        <div class="admin-head">
            <div>
                <h2 style="font:600 30px 'Playfair Display',serif;margin:0">Registered visitors</h2>
                <p class="text-muted"><?= count($users) ?> visitor account<?= count($users) === 1 ? '' : 's' ?> on this site.</p>
            </div>
            <a class="button button-soft" href="<?= e(url('admin/index.php')) ?>">Back to overview</a>
        </div>
        <?php if (!$users): ?>
            <div class="empty-state">No visitor accounts yet.<br><a class="text-link" href="<?= e(url('register.php')) ?>">Register a visitor</a> to start collecting plans.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gmail / Email</th>
                            <th>Joined</th>
                            <th>Saved plans</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php $planCount = (int) $user['plan_count']; ?>
                        <tr>
                            <td><strong><?= e($user['name']) ?></strong></td>
                            <td><a class="text-link" href="mailto:<?= e($user['email']) ?>"><?= e($user['email']) ?></a></td>
                            <td><?= e(date('M j, Y', strtotime($user['created_at']))) ?></td>
                            <td>
                                <?php if ($planCount === 0): ?>
                                    <span class="badge" style="background:#f3e9d9;color:#7a6132">0 plans</span>
                                <?php else: ?>
                                    <span class="badge"><?= $planCount ?> plan<?= $planCount === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-soft button-small" href="<?= e(url('admin/visitor-plans.php?id=' . (int) $user['id'])) ?>">View plans</a>
                                    <a class="button button-soft button-small" href="<?= e(url('admin/visitor-form.php?id=' . (int) $user['id'])) ?>">Edit</a>
                                    <form method="post" action="<?= e(url('admin/delete-visitor.php')) ?>" data-confirm="Delete visitor <?= e($user['name']) ?>? All their saved plans will also be removed.">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <button class="button button-danger button-small" type="submit">Delete</button>
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