<?php
// includes/auth_guard.php
declare(strict_types=1);

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
}
