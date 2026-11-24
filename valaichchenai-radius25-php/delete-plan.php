<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
if (!$user) {
    set_flash('error', 'Please sign in to delete a plan.');
    redirect('login.php?next=' . rawurlencode('my-plans.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$planId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
if ($planId <= 0) {
    set_flash('error', 'Invalid plan reference.');
    redirect(is_admin() ? 'admin/index.php' : 'my-plans.php');
}

$pdo = db();

// Inspect the plan first so we can decide the right response.
$lookup = $pdo->prepare('SELECT id, user_id, title FROM itineraries WHERE id = :id LIMIT 1');
$lookup->execute(['id' => $planId]);
$plan = $lookup->fetch();

if (!$plan) {
    set_flash('error', 'That plan was already removed.');
    redirect(is_admin() ? 'admin/visitors.php' : 'my-plans.php');
}

// Owner can delete their own plan; admin can delete any plan.
$isOwner = (int) $plan['user_id'] === (int) $user['id'];
if (!is_admin() && !$isOwner) {
    http_response_code(403);
    exit('You do not have permission to delete that plan.');
}

// Delete exactly ONE row, scoped by id (and user_id for defence-in-depth).
// This never touches the visitor account and never cascades to other plans.
if (is_admin() && !$isOwner) {
    $delete = $pdo->prepare('DELETE FROM itineraries WHERE id = :id LIMIT 1');
    $delete->execute(['id' => $planId]);
} else {
    $delete = $pdo->prepare('DELETE FROM itineraries WHERE id = :id AND user_id = :user_id LIMIT 1');
    $delete->execute(['id' => $planId, 'user_id' => (int) $user['id']]);
}

set_flash('success', $delete->rowCount() ? 'The saved plan was deleted.' : 'That plan was already removed.');

// Return the admin to the same visitor’s plans page if they came from there.
$back = $_POST['back'] ?? '';
if (is_admin() && $back === 'visitor-plans' && !empty($_POST['user_id'])) {
    redirect('admin/visitor-plans.php?id=' . (int) $_POST['user_id']);
}
redirect(is_admin() ? 'admin/visitors.php' : 'my-plans.php');