<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
$pdo = db();
$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount > 0) {
        $error = 'Administrator setup is already complete.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $error = 'Enter a name between 2 and 120 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 12) {
        $error = 'Choose an administrator password with at least 12 characters.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'The password confirmation did not match.';
    } else {
        try {
            $insert = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, "admin")');
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            set_flash('success', 'Administrator account created. Please sign in.');
            redirect('login.php');
        } catch (PDOException $exception) {
            $error = 'That email address is already registered.';
        }
    }
}
$pageTitle = 'Administrator setup';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
    <div class="auth-panel">
        <span class="eyebrow">First-time setup</span><h1>Administrator access.</h1>
        <?php if ($adminCount > 0): ?>
            <p>An administrator account is already set up. This page is now closed.</p>
            <a class="button button-primary" href="<?= e(url('login.php')) ?>">Go to sign in</a>
        <?php else: ?>
            <p>Create the first administrator account to manage places. This one-time form closes after setup.</p>
            <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="<?= e(url('setup-admin.php')) ?>">
                <?= csrf_field() ?>
                <div class="form-group"><label for="name">Administrator name</label><input class="field" id="name" name="name" type="text" required minlength="2" maxlength="120" value="<?= e($name) ?>"></div>
                <div class="form-group"><label for="email">Email address</label><input class="field" id="email" name="email" type="email" required value="<?= e($email) ?>"></div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-box">
                        <input class="field password-input" id="password" name="password" type="password" required minlength="12" autocomplete="new-password">
                        <button type="button" class="password-eye" id="password-eye" aria-label="Show password" aria-pressed="false" aria-controls="password" tabindex="0">
                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                    <span class="form-help">Use at least 12 characters.</span>
                </div>

                <div class="form-group">
                    <label for="password-confirmation">Confirm password</label>
                    <div class="password-box">
                        <input class="field password-input" id="password-confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password">
                        <button type="button" class="password-eye" id="password-confirmation-eye" aria-label="Show password confirmation" aria-pressed="false" aria-controls="password-confirmation" tabindex="0">
                            <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="eye-closed" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:none">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button class="button button-primary" type="submit">Create administrator</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<script>
(function () {
    function bindToggle(inputId, buttonId) {
        var input = document.getElementById(inputId);
        var eye   = document.getElementById(buttonId);
        if (!input || !eye) return;

        var eyeOpen   = eye.querySelector('.eye-open');
        var eyeClosed = eye.querySelector('.eye-closed');

        function setVisible(visible) {
            input.type = visible ? 'text' : 'password';
            eye.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            eye.setAttribute('aria-pressed', visible ? 'true' : 'false');
            eyeOpen.style.display   = visible ? 'none' : '';
            eyeClosed.style.display = visible ? '' : 'none';
        }

        // Default: hidden
        setVisible(false);

        eye.addEventListener('click', function () {
            var isHidden = input.type === 'password';
            setVisible(isHidden); // hidden -> show, shown -> hide
            input.focus({ preventScroll: true });
        });
    }

    bindToggle('password', 'password-eye');
    bindToggle('password-confirmation', 'password-confirmation-eye');
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>