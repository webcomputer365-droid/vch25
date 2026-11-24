<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
$pdo = db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

$stmt = $pdo->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = :id AND role = "visitor" LIMIT 1');
$stmt->execute(['id' => $id]);
$visitor = $stmt->fetch();
if (!$visitor) {
    set_flash('error', 'That visitor account could not be found.');
    redirect('admin/visitors.php');
}

$error = '';
$values = [
    'name' => (string) $visitor['name'],
    'email' => (string) $visitor['email'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values['name'] = trim((string) ($_POST['name'] ?? ''));
    $values['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 120) {
        $error = 'Enter a name between 2 and 120 characters.';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($values['email']) > 190) {
        $error = 'Enter a valid email address.';
    } else {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $check->execute(['email' => $values['email'], 'id' => $id]);
        if ($check->fetch()) {
            $error = 'Another account already uses that email address.';
        } else {
            $update = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id AND role = "visitor"');
            $update->execute(['name' => $values['name'], 'email' => $values['email'], 'id' => $id]);
            set_flash('success', 'Visitor account updated.');
            redirect('admin/visitors.php');
        }
    }
}

$activeNav = 'visitors';
$pageTitle = 'Edit visitor';
require __DIR__ . '/../includes/header.php';
?>
<section class="page-hero">
    <div class="container">
        <span class="eyebrow">Administrator workspace</span>
        <h1>Edit visitor account.</h1>
        <p>Update the name or email for this visitor. Their saved plans remain unchanged.</p>
    </div>
</section>
<section class="container section-tight">
    <div class="panel" style="max-width:640px">
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('admin/visitor-form.php?id=' . $id)) ?>">
            <?= csrf_field() ?>
            <div class="form-group"><label for="name">Name</label><input class="field" id="name" name="name" type="text" required minlength="2" maxlength="120" value="<?= e($values['name']) ?>"></div>
            <div class="form-group"><label for="email">Email address</label><input class="field" id="email" name="email" type="email" required maxlength="190" value="<?= e($values['email']) ?>"></div>
            <div class="hero-actions">
                <button class="button button-primary" type="submit">Save changes</button>
                <a class="button button-soft" href="<?= e(url('admin/visitors.php')) ?>">Cancel</a>
            </div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>