<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim(APP_BASE_URL, '/');
    $path = ltrim($path, '/');
    return $base . ($path === '' ? '/' : '/' . $path);
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_admin(): void
{
    if (!is_admin()) {
        set_flash('error', 'Please sign in with an administrator account to continue.');
        redirect('login.php');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $known = (string) ($_SESSION['csrf_token'] ?? '');
    if ($known === '' || !hash_equals($known, $submitted)) {
        http_response_code(403);
        exit('This form has expired. Please return and try again.');
    }
}

function place_categories(): array
{
    return ['Heritage', 'Religious', 'Nature', 'Cultural', 'Museum', 'Recreational', 'Restaurant', 'Public Place', 'Recreation', 'Beach', 'Viewpoint', 'Landmark', 'Local Attraction'];
}

function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 2 * $earthRadius * atan2(sqrt($a), sqrt(1 - $a));
}

function distance_from_home_km(array $place): float
{
    return haversine_km(HOME_LAT, HOME_LNG, (float) $place['latitude'], (float) $place['longitude']);
}

function place_by_id(PDO $pdo, int $id, bool $includeInactive = false): ?array
{
    $sql = 'SELECT * FROM places WHERE id = :id' . ($includeInactive ? '' : ' AND is_active = 1');
    $statement = $pdo->prepare($sql);
    $statement->execute(['id' => $id]);
    $place = $statement->fetch();
    return $place ?: null;
}

function format_duration(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    if ($hours === 0) {
        return $remaining . ' min';
    }
    return $hours . ' hr' . ($hours > 1 ? 's' : '') . ($remaining ? ' ' . $remaining . ' min' : '');
}

function format_fee(mixed $fee): string
{
    return $fee === null || $fee === '' ? 'Check locally' : 'LKR ' . number_format((float) $fee, 0);
}

function map_url(float $lat, float $lng): string
{
    return 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat) .
        '&mlon=' . rawurlencode((string) $lng) . '#map=16/' .
        rawurlencode((string) $lat) . '/' . rawurlencode((string) $lng);
}

function maps_directions_url(array $coordinates): string
{
    if (!$coordinates) {
        return 'https://www.google.com/maps/dir/' . rawurlencode(HOME_LAT . ',' . HOME_LNG);
    }
    $points = array_merge([HOME_LAT . ',' . HOME_LNG], array_map(
        static fn(array $point): string => $point['latitude'] . ',' . $point['longitude'],
        $coordinates
    ));
    return 'https://www.google.com/maps/dir/' . implode('/', array_map('rawurlencode', $points));
}

function image_url(array $place): string
{
    $candidate = trim((string) ($place['image_url'] ?? ''));
    if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_URL) &&
        in_array(strtolower((string) parse_url($candidate, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        return $candidate;
    }
    return url('assets/images/radius25-hero.jpg');
}

function safe_next(string $fallback = 'index.php'): string
{
    $next = (string) ($_POST['next'] ?? $_GET['next'] ?? '');
    if ($next === '') {
        return $fallback;
    }
    if (str_contains($next, '://') || str_starts_with($next, '//') || str_contains($next, '..')) {
        return $fallback;
    }
    if (preg_match('/^[A-Za-z0-9_\-]+\.php(\?[^\s]*)?$/', $next) !== 1) {
        return $fallback;
    }
    return $next;
}