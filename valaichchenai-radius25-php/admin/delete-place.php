<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}
verify_csrf();
$delete = db()->prepare('DELETE FROM places WHERE id = :id');
$delete->execute(['id' => (int) ($_POST['id'] ?? 0)]);
set_flash('success', $delete->rowCount() ? 'The place was deleted.' : 'That place was already removed.');
redirect('admin/index.php');
