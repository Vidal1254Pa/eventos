<?php
declare(strict_types=1);

load_env_file(__DIR__ . '/.env');

$appDebug = env_bool('APP_DEBUG', false);

define('DB_DRIVER', env_value('DB_DRIVER', 'pgsql'));
define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_NAME', env_value('DB_NAME', 'asistencia_db'));
define('DB_USER', env_value('DB_USER', 'postgres'));
define('DB_PASS', env_value('DB_PASS', 'postgres'));
define('DB_SSLMODE', env_value('DB_SSLMODE', 'prefer'));

define('APP_ENV', env_value('APP_ENV', 'production'));
define('APP_DEBUG', $appDebug);
define('APP_NAME', env_value('APP_NAME', 'Control de Asistencia'));
define('APP_TZ', env_value('APP_TZ', 'America/Lima'));
define('BASE_URL', normalize_base_url(env_value('BASE_URL', '/eventos/')));

date_default_timezone_set(APP_TZ);
configure_php_security(APP_DEBUG);
start_secure_session();
send_security_headers();
enforce_session_fingerprint();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/helpers.php';

function load_env_file(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env_value(string $key, string $default = ''): string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
}

function env_bool(string $key, bool $default = false): bool
{
    $value = strtolower(env_value($key, $default ? 'true' : 'false'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function normalize_base_url(string $baseUrl): string
{
    $path = trim($baseUrl);
    if ($path === '') {
        return '/';
    }

    if (!str_starts_with($path, '/')) {
        $path = '/' . $path;
    }

    return rtrim($path, '/') . '/';
}

function configure_php_security(bool $debug): void
{
    ini_set('default_charset', 'UTF-8');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('expose_php', '0');
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    error_reporting($debug ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);

    header_remove('X-Powered-By');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('eventos_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => BASE_URL,
        'domain' => '',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function enforce_session_fingerprint(): void
{
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    if (!isset($_SESSION['__fingerprint'])) {
        $_SESSION['__fingerprint'] = $fingerprint;
        $_SESSION['__regenerated_at'] = time();
        session_regenerate_id(true);
        return;
    }

    if (!hash_equals((string) $_SESSION['__fingerprint'], $fingerprint)) {
        $_SESSION = [];
        session_destroy();
        start_secure_session();
        $_SESSION['__fingerprint'] = $fingerprint;
        $_SESSION['__regenerated_at'] = time();
        return;
    }

    if ((time() - (int) ($_SESSION['__regenerated_at'] ?? 0)) > 900) {
        session_regenerate_id(true);
        $_SESSION['__regenerated_at'] = time();
    }
}

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; font-src 'self' data:; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? null) === '443');
}
