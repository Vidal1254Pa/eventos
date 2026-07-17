<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_login();

// Evitar que cualquier salida dañe el xlsx
ini_set('zlib.output_compression', '0');
while (ob_get_level() > 0) { ob_end_clean(); }

require_once __DIR__ . '/../libs/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

$desde = preg_replace('/[^0-9\-]/','', $_GET['desde'] ?? date('Y-m-01'));
$hasta = preg_replace('/[^0-9\-]/','', $_GET['hasta'] ?? date('Y-m-d'));
$evento_id = (int)($_GET['evento_id'] ?? 0);

$firstSheet = true;
$xlsx = null;

// Si evento_id = 0 => crea una hoja por evento (en el rango)
if ($evento_id === 0) {

    $stmtE = $conexion->prepare("
        SELECT id, titulo, fecha, hora_inicio
        FROM eventos
        WHERE fecha BETWEEN ? AND ?
        ORDER BY fecha DESC, hora_inicio DESC
    ");
    $stmtE->bind_param("ss", $desde, $hasta);
    $stmtE->execute();
    $eventos = $stmtE->get_result();

    while ($e = $eventos->fetch_assoc()) {
        $eid = (int)$e['id'];

        // Nombre hoja máx 31 caracteres (Excel)
        $nombreHoja = substr($e['fecha'] . " " . substr($e['hora_inicio'],0,5) . " - " . $e['titulo'], 0, 31);

        $data = [
            ['Evento','Fecha evento','Hora evento','DNI','Nombres','Mesa','Área','Cargo','Estado','Hora marcado','Registrado por','Obs.']
        ];

        $stmt = $conexion->prepare("
            SELECT
              e.titulo AS evento,
              e.fecha AS fecha_evento,
              e.hora_inicio,
              s.dni,
              CONCAT(s.nombres,' ',s.apellidos) AS nombres,
              s.mesa,
              s.area,
              s.cargo,
              a.estado,
              a.hora,
              u.nombre AS registrado_por,
              a.obs
            FROM asistencias a
            INNER JOIN asistentes s ON s.id = a.asistente_id
            INNER JOIN usuarios u ON u.id = a.registrado_por
            INNER JOIN eventos e ON e.id = a.evento_id
            WHERE a.evento_id = ?
            ORDER BY a.hora DESC
        ");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($r = $res->fetch_assoc()) {
            $data[] = [
                $r['evento'],
                $r['fecha_evento'],
                substr($r['hora_inicio'],0,5),
                $r['dni'],
                $r['nombres'],
                $r['mesa'] ?? '',
                $r['area'] ?? '',
                $r['cargo'] ?? '',
                $r['estado'],
                substr($r['hora'],0,5),
                $r['registrado_por'],
                $r['obs'] ?? ''
            ];
        }

        if ($firstSheet) {
            $xlsx = SimpleXLSXGen::fromArray($data, $nombreHoja);
            $firstSheet = false;
        } else {
            $xlsx->addSheet($data, $nombreHoja);
        }
    }

} else {
    // Solo 1 evento => 1 hoja

    $stmtEvt = $conexion->prepare("SELECT titulo, fecha, hora_inicio FROM eventos WHERE id=? LIMIT 1");
    $stmtEvt->bind_param("i", $evento_id);
    $stmtEvt->execute();
    $evt = $stmtEvt->get_result()->fetch_assoc();

    $nombreHoja = $evt
        ? substr($evt['fecha']." ".substr($evt['hora_inicio'],0,5)." - ".$evt['titulo'], 0, 31)
        : 'Reporte';

    $data = [
        ['Evento','Fecha evento','Hora evento','DNI','Nombres','Mesa','Área','Cargo','Estado','Hora marcado','Registrado por','Obs.']
    ];

    $stmt = $conexion->prepare("
        SELECT
          e.titulo AS evento,
          e.fecha AS fecha_evento,
          e.hora_inicio,
          s.dni,
          CONCAT(s.nombres,' ',s.apellidos) AS nombres,
          s.mesa,
          s.area,
          s.cargo,
          a.estado,
          a.hora,
          u.nombre AS registrado_por,
          a.obs
        FROM asistencias a
        INNER JOIN asistentes s ON s.id = a.asistente_id
        INNER JOIN usuarios u ON u.id = a.registrado_por
        INNER JOIN eventos e ON e.id = a.evento_id
        WHERE a.evento_id = ?
        ORDER BY a.hora DESC
    ");
    $stmt->bind_param("i", $evento_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $data[] = [
            $r['evento'],
            $r['fecha_evento'],
            substr($r['hora_inicio'],0,5),
            $r['dni'],
            $r['nombres'],
            $r['mesa'] ?? '',
            $r['area'] ?? '',
            $r['cargo'] ?? '',
            $r['estado'],
            substr($r['hora'],0,5),
            $r['registrado_por'],
            $r['obs'] ?? ''
        ];
    }

    $xlsx = SimpleXLSXGen::fromArray($data, $nombreHoja);
}

if (!$xlsx) {
    // Si no hay eventos en el rango
    $xlsx = SimpleXLSXGen::fromArray([['Sin datos para exportar']], 'Reporte');
}

$xlsx->downloadAs("reporte_asistencia_{$desde}_{$hasta}.xlsx");
exit;

