<?php
// includes/helpers.php
declare(strict_types=1);

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): void {
    header("Location: " . BASE_URL . ltrim($path,'/'));
    exit;
}

function flash_set(string $key, string $msg): void { $_SESSION['flash'][$key] = $msg; }
function flash_get(string $key): ?string {
    if (!isset($_SESSION['flash'][$key])) return null;
    $m = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $m;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(400);
        echo "CSRF inválido.";
        exit;
    }
}

function require_role(array $roles): void {
    $rol = $_SESSION['user']['rol'] ?? '';
    if (!in_array($rol, $roles, true)) {
        http_response_code(403);
        echo "No autorizado.";
        exit;
    }
}
