<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();

$visitorId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
if ($visitorId <= 0) {
    set_flash('error', 'Invalid visitor reference.');
    redirect('admin/visitors.php');
}

$pdo = db();

// Only allow deleting real visitor accounts — never touch admins.
$check = $pdo->prepare('SELECT id, name, email FROM users WHERE id = :id AND role = "visitor" LIMIT 1');
$check->execute(['id' => $visitorId]);
$visitor = $check->fetch();
if (!$visitor) {
    set_flash('error', 'That visitor account was already removed.');
    redirect('admin/visitors.php');
}

// FK `fk_itineraries_user ... ON DELETE CASCADE` removes this visitor's plans.
// Other visitors' rows are unaffected because they have a different user_id.
$delete = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "visitor"');
$delete->execute(['id' => $visitorId]);

set_flash(
    'success',
    $delete->rowCount()
        ? 'Visitor account “' . $visitor['name'] . '” and all of its saved plans were deleted.'
        : 'That visitor account was already removed.'
);
redirect('admin/visitors.php');