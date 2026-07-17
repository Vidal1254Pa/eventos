<?php
require_once __DIR__ . '/../config.php';
require_login();

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;

$desde = preg_replace('/[^0-9\-]/','', $_GET['desde'] ?? date('Y-m-01'));
$hasta = preg_replace('/[^0-9\-]/','', $_GET['hasta'] ?? date('Y-m-d'));
$evento_id = (int)($_GET['evento_id'] ?? 0);

$sql = "SELECT a.fecha,a.hora,a.estado,a.obs,
  s.dni, s.nombres, s.apellidos, s.area, s.cargo,
  e.titulo AS evento, e.fecha AS fecha_evento, e.hora_inicio,
  u.nombre AS registrado_por
  FROM asistencias a
  INNER JOIN asistentes s ON s.id=a.asistente_id
  INNER JOIN usuarios u ON u.id=a.registrado_por
  INNER JOIN eventos e ON e.id=a.evento_id
  WHERE a.fecha BETWEEN ? AND ?";

if ($evento_id > 0) $sql .= " AND a.evento_id=? ";
$sql .= " ORDER BY a.fecha DESC, a.hora DESC";

if ($evento_id > 0) {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi",$desde,$hasta,$evento_id);
} else {
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss",$desde,$hasta);
}
$stmt->execute();
$res = $stmt->get_result();

$rows = '';
while($r = $res->fetch_assoc()){
  $rows .= '<tr>'.
    '<td>'.h($r['evento']).'</td>'.
    '<td>'.h($r['fecha_evento']).'</td>'.
    '<td>'.h(substr($r['hora_inicio'],0,5)).'</td>'.
    '<td>'.h($r['fecha']).'</td>'.
    '<td>'.h(substr($r['hora'],0,5)).'</td>'.
    '<td>'.h($r['dni']).'</td>'.
    '<td>'.h($r['nombres'].' '.$r['apellidos']).'</td>'.
    '<td>'.h($r['area'] ?? '').'</td>'.
    '<td>'.h($r['cargo'] ?? '').'</td>'.
    '<td>'.h($r['estado']).'</td>'.
    '<td>'.h($r['registrado_por']).'</td>'.
    '<td>'.h($r['obs'] ?? '').'</td>'.
  '</tr>';
}

$html = '<html><head><meta charset="utf-8">
<style>
body{font-family:DejaVu Sans, sans-serif; font-size:10px}
h2{margin:0 0 8px 0}
small{color:#555}
table{width:100%; border-collapse:collapse}
th,td{border:1px solid #ddd; padding:6px; vertical-align:top}
th{background:#f3f4f6}
</style></head><body>'.
'<h2>Reporte de Asistencia</h2>'.
'<small>Rango: '.h($desde).' a '.h($hasta).($evento_id>0 ? ' · Evento filtrado' : '').' · Generado: '.date('Y-m-d H:i').'</small>'.
'<table><thead><tr>'.
'<th>Evento</th><th>Fecha evt.</th><th>Hora evt.</th><th>Fecha marcado</th><th>Hora marcado</th><th>DNI</th><th>Nombres</th><th>Área</th><th>Cargo</th><th>Estado</th><th>Registrado por</th><th>Obs.</th>'.
'</tr></thead><tbody>'.$rows.'</tbody></table>'.
'</body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = "reporte_asistencia_{$desde}_{$hasta}".($evento_id>0 ? "_evento{$evento_id}" : "").".pdf";
$dompdf->stream($filename, ["Attachment" => true]);
exit;
