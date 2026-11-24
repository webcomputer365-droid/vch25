<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
if (!current_user()) {
    set_flash('error', 'Sign in to view your saved plans.');
    redirect('login.php?next=my-plans.php');
}
$statement = db()->prepare('SELECT id, title, visit_date, start_time, created_at FROM itineraries WHERE user_id = :user_id ORDER BY visit_date DESC, created_at DESC');
$statement->execute(['user_id' => (int) current_user()['id']]);
$plans = $statement->fetchAll();
$activeNav = 'plans';
$pageTitle = 'My saved plans';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">Your saved journeys</span><h1>My day plans.</h1><p>Return to an itinerary, review its route and timings, or remove a plan you no longer need.</p></div></section>
<section class="section-tight"><div class="container">
    <div class="admin-head">
        <div>
            <h2 style="font:600 30px 'Playfair Display',serif;margin:0">Saved itineraries</h2>
            <p class="text-muted"></p>
        </div>
        <a class="button button-primary" href="<?= e(url('planner.php')) ?>">Create a new plan</a>
    </div>
    <?php if (!$plans): ?>
        <div class="empty-state">You have no saved plans yet.<br><a class="text-link" href="<?= e(url('planner.php')) ?>">Build your first Valaichchenai day</a></div>
    <?php else: ?>
        <div class="table-wrap"><table class="data-table"><thead><tr><th>Plan</th><th>Visit date</th><th>Start</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($plans as $plan): ?>
            <tr>
                <td><strong><?= e($plan['title']) ?></strong></td>
                <td><?= e(date('M j, Y', strtotime($plan['visit_date']))) ?></td>
                <td><?= e(date('g:i A', strtotime($plan['start_time']))) ?></td>
                <td><div class="table-actions">
                    <a class="button button-soft button-small" href="<?= e(url('planner.php?id=' . (int) $plan['id'])) ?>">Open</a>
                    <form method="post" action="<?= e(url('delete-plan.php')) ?>" data-confirm="Delete this saved itinerary? This cannot be undone.">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $plan['id'] ?>">
                        <button class="button button-danger button-small" type="submit">Delete</button>
                    </form>
                </div></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>