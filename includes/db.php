<?php
// includes/db.php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conexion->set_charset('utf8mb4');
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error de conexión a BD: " . htmlspecialchars($e->getMessage());
    exit;
}
