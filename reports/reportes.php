<?php
require_once __DIR__ . '/../config.php';
require_login();

$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$evento_id = (int)($_GET['evento_id'] ?? 0);

$desde = preg_replace('/[^0-9\-]/','',$desde);
$hasta = preg_replace('/[^0-9\-]/','',$hasta);

$evtList = $conexion->query("SELECT id, titulo, fecha, hora_inicio FROM eventos ORDER BY fecha DESC, hora_inicio DESC");

$sql = "SELECT a.fecha,a.hora,a.estado,a.obs,
  s.dni, s.nombres, s.apellidos,s.mesa, s.area, s.cargo,
  e.titulo AS evento, e.fecha AS fecha_evento, e.hora_inicio,
  u.nombre AS registrado_por
  FROM asistencias a
  INNER JOIN asistentes s ON s.id=a.asistente_id
  INNER JOIN usuarios u ON u.id=a.registrado_por
  INNER JOIN eventos e ON e.id=a.evento_id
  WHERE a.fecha BETWEEN ? AND ?";

if ($evento_id > 0) $sql .= " AND a.evento_id = ? ";
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

// stats rango
if ($evento_id > 0) {
    $stmt2 = $conexion->prepare("SELECT COUNT(*) AS t FROM asistencias WHERE fecha BETWEEN ? AND ? AND evento_id=?");
    $stmt2->bind_param("ssi",$desde,$hasta,$evento_id);
} else {
    $stmt2 = $conexion->prepare("SELECT COUNT(*) AS t FROM asistencias WHERE fecha BETWEEN ? AND ?");
    $stmt2->bind_param("ss",$desde,$hasta);
}
$stmt2->execute();
$totalReg = (int)$stmt2->get_result()->fetch_assoc()['t'];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid">
  <div class="card" style="grid-column: span 12;">
    <h2>Reportes</h2>
    <form method="get" class="row" style="margin-bottom:12px">
      <div>
        <label>Desde</label>
        <input class="input" type="date" name="desde" value="<?= h($desde) ?>">
      </div>
      <div>
        <label>Hasta</label>
        <input class="input" type="date" name="hasta" value="<?= h($hasta) ?>">
      </div>
      <div style="min-width:320px">
        <label>Evento (opcional)</label>
        <select name="evento_id">
          <option value="0">Todos</option>
          <?php while($e = $evtList->fetch_assoc()): ?>
            <option value="<?= (int)$e['id'] ?>" <?= (int)$e['id']===$evento_id ? 'selected' : '' ?>>
              <?= h($e['fecha'].' '.substr($e['hora_inicio'],0,5).' · '.$e['titulo']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div style="align-self:end" class="row">
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>reports/reportes.php">Limpiar</a>
      </div>

      <div style="margin-left:auto;align-self:end" class="row">
        <a class="btn" href="<?= BASE_URL ?>exports/excel.php?desde=<?= h($desde) ?>&hasta=<?= h($hasta) ?>&evento_id=<?= (int)$evento_id ?>">Descargar Excel</a>
       
      </div>
      
    </form>
    <div class="row" style="margin-bottom:10px">
      <span class="badge">Registros: <?= $totalReg ?></span>
      <span class="badge">Rango: <?= h($desde) ?> a <?= h($hasta) ?></span>
      <?php if($evento_id>0): ?><span class="badge">Filtrado por evento</span><?php endif; ?>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Evento</th><th>Fecha evt.</th><th>Hora evt.</th>
          <th>Fecha</th><th>Hora</th><th>DNI</th><th>Nombres</th><th>Área</th><th>Cargo</th><th>Estado</th><th>Registrado por</th><th>Obs.</th>
        </tr>
      </thead>
      <tbody>
        <?php while($r = $res->fetch_assoc()): ?>
          <tr>
            <td><?= h($r['evento']) ?></td>
            <td><?= h($r['fecha_evento']) ?></td>
            <td><?= h(substr($r['hora_inicio'],0,5)) ?></td>

            <td><?= h($r['fecha']) ?></td>
            <td><?= h(substr($r['hora'],0,5)) ?></td>
            <td><?= h($r['dni']) ?></td>
            <td><?= h($r['nombres'].' '.$r['apellidos']) ?></td>
            <td><?= h($r['area'] ?? '') ?></td>
            <td><?= h($r['cargo'] ?? '') ?></td>
            <td><span class="badge"><?= h($r['estado']) ?></span></td>
            <td><?= h($r['registrado_por']) ?></td>
            <td><?= h($r['obs'] ?? '') ?></td>
          </tr>
        <?php endwhile; ?>
        <?php if($totalReg===0): ?>
          <tr><td colspan="12">Sin resultados para el filtro seleccionado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>