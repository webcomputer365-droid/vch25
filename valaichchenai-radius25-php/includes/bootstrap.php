<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';

try {
    db();
} catch (PDOException $exception) {
    http_response_code(503);
    $home = e(url(''));
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Finish setup · Radius25</title>';
    echo '<body style="font:16px/1.6 system-ui,sans-serif;background:#f1f8fb;color:#0f2c3d;padding:8vw;max-width:780px;margin:auto">';
    echo '<p style="color:#0b6e99;font-weight:700;letter-spacing:.1em;text-transform:uppercase">Radius25</p>';
    echo '<h1>One quick setup step first</h1>';
    echo '<p>Start MySQL in XAMPP, create the <strong>' . e(DB_NAME) . '</strong> database in phpMyAdmin, then import <code>database/valaichchenai_radius25.sql</code>.</p>';
    echo '<p>Check the database settings in <code>config/config.php</code> if your local setup uses different credentials.</p>';
    echo '<p><a href="' . $home . '">Try again</a></p>';
    echo '</body></html>';
    exit;
}