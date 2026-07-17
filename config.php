<?php
declare(strict_types=1);

// 🔹 CONFIGURACIÓN DE SESIÓN (ANTES)
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');

// 🔹 INICIAR SESIÓN
session_start();

// --- CONFIG DB ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'asistencia_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// App
define('APP_NAME', 'Control de Asistencia');
define('APP_TZ', 'America/Lima');

date_default_timezone_set(APP_TZ);

// Base URL
define('BASE_URL', '/eventos/');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/includes/helpers.php';
