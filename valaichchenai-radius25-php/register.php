<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) {
    redirect('my-plans.php');
}
$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $error = 'Enter a name between 2 and 120 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 10) {
        $error = 'Choose a password with at least 10 characters.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'The password confirmation did not match.';
    } else {
        $existing = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $existing->execute(['email' => $email]);
        if ($existing->fetch()) {
            $error = 'An account already uses that email address. Try signing in instead.';
        } else {
            $insert = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, "visitor")');
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            set_flash('success', 'Your account is ready.');
            redirect(safe_next('my-plans.php'));
        }
    }
}
$pageTitle = 'Create an account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
    <div class="auth-panel">
        <span class="eyebrow">Your trips, in one place</span><h1>Create account.</h1><p>Save your Valaichchenai day plans and return to them whenever you need.</p>
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('register.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e(safe_next('my-plans.php')) ?>">
            <div class="form-group"><label for="name">Your name</label><input class="field" id="name" name="name" type="text" autocomplete="name" minlength="2" maxlength="120" required value="<?= e($name) ?>"></div>
            <div class="form-group"><label for="email">Email address</label><input class="field" id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>"></div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-box">
                    <input class="field password-input" id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
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
                <span class="form-help">Use at least 10 characters.</span>
            </div>

            <div class="form-group">
                <label for="password-confirmation">Confirm password</label>
                <div class="password-box">
                    <input class="field password-input" id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>
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

            <button class="button button-primary" type="submit">Create account</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= e(url('login.php?next=' . rawurlencode(safe_next('my-plans.php')))) ?>">Sign in</a></p>
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