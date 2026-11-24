<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) {
    redirect('my-plans.php');
}
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $statement = db()->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();
    if ($user && password_verify($password, (string) $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
        redirect(safe_next('my-plans.php'));
    }
    $error = 'The email or password did not match. Please try again.';
}
$pageTitle = 'Sign in';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap">
    <div class="auth-panel">
        <span class="eyebrow">Welcome back</span><h1>Sign in.</h1><p>Pick up where your Valaichchenai day plan left off.</p>
        <?php if ($error): ?><div class="flash flash-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('login.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e(safe_next('my-plans.php')) ?>">
            <div class="form-group"><label for="email">Email address</label><input class="field" id="email" name="email" type="email" autocomplete="email" required value="<?= e($email) ?>"></div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-box">
                    <input class="field password-input" id="password" name="password" type="password" autocomplete="current-password" required>
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
            </div>
            <button class="button button-primary" type="submit">Sign in</button>
        </form>
        <p class="auth-switch">New to DayTrail? <a href="<?= e(url('register.php?next=' . rawurlencode(safe_next('my-plans.php')))) ?>">Create an account</a></p>
    </div>
</section>


<script>
(function () {
    var input = document.getElementById('password');
    var eye   = document.getElementById('password-eye');
    if (!input || !eye) return;

    var eyeOpen   = eye.querySelector('.eye-open');
    var eyeClosed = eye.querySelector('.eye-closed');

    function setVisible(visible) {
        // visible = true  -> password shown as text
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
        setVisible(isHidden); // if hidden -> show; if shown -> hide
        input.focus({ preventScroll: true });
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>