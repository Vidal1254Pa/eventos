<?php
declare(strict_types=1);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function flash_set(string $key, string $msg): void
{
    $_SESSION['flash'][$key] = $msg;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_check(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        echo 'CSRF invalido.';
        exit;
    }
}

function require_role(array $roles): void
{
    $role = $_SESSION['user']['rol'] ?? '';
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        echo 'No autorizado.';
        exit;
    }
}

function db_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return (int) $value === 1;
    }

    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['1', 't', 'true', 'y', 'yes', 'on'], true);
}

function client_ip(): string
{
    return trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function auth_throttle_status(string $user): array
{
    $path = auth_throttle_file($user);
    if (!is_file($path)) {
        return ['attempts' => 0, 'blocked_until' => 0];
    }

    $raw = file_get_contents($path);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    if (!is_array($data)) {
        @unlink($path);
        return ['attempts' => 0, 'blocked_until' => 0];
    }

    $now = time();
    $lastAttempt = (int) ($data['last_attempt'] ?? 0);
    if ($lastAttempt > 0 && ($now - $lastAttempt) > 900) {
        @unlink($path);
        return ['attempts' => 0, 'blocked_until' => 0];
    }

    return [
        'attempts' => (int) ($data['attempts'] ?? 0),
        'blocked_until' => (int) ($data['blocked_until'] ?? 0),
    ];
}

function auth_register_failure(string $user): void
{
    $state = auth_throttle_status($user);
    $attempts = $state['attempts'] + 1;
    $blockedUntil = $attempts >= 5 ? time() + 900 : 0;

    file_put_contents(
        auth_throttle_file($user),
        json_encode([
            'attempts' => $attempts,
            'blocked_until' => $blockedUntil,
            'last_attempt' => time(),
        ], JSON_THROW_ON_ERROR),
        LOCK_EX
    );
}

function auth_clear_failures(string $user): void
{
    $path = auth_throttle_file($user);
    if (is_file($path)) {
        @unlink($path);
    }
}

function auth_throttle_file(string $user): string
{
    $key = hash('sha256', strtolower(trim($user)) . '|' . client_ip());
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eventos_login_' . $key . '.json';
}
