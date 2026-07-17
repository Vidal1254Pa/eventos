<?php
require_once __DIR__ . '/config.php';
require_login();

$hoy = date('Y-m-d');

$evento_id = (int)($_GET['evento_id'] ?? 0);

if ($evento_id === 0) {
    $stmt = $conexion->prepare("SELECT id FROM eventos WHERE activo=1 AND fecha=? LIMIT 1");
    $stmt->bind_param("s", $hoy);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $evento_id = (int)$row['id'];
}

$evtInfo = null;
if ($evento_id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM eventos WHERE id=?");
    $stmt->bind_param("i",$evento_id);
    $stmt->execute();
    $evtInfo = $stmt->get_result()->fetch_assoc();
}

/* ===== DATOS ===== */
$q_total = $conexion->query("
  SELECT cargo, COUNT(*) total 
  FROM asistentes 
  WHERE activo=1 
  GROUP BY cargo
");

$tipos = ['ACTIVO'=>0,'VITALICIO'=>0,'TRANSEUNTE'=>0];
$total = 0;

while ($row = $q_total->fetch_assoc()) {
    $cargo = strtoupper(trim($row['cargo']));
    if(isset($tipos[$cargo])) $tipos[$cargo] = $row['total'];
    $total += $row['total'];
}

$deudaResumen = ['SI'=>0,'NO'=>0];

$q_deuda = $conexion->query("
  SELECT deuda, COUNT(*) total
  FROM asistentes WHERE activo=1 GROUP BY deuda
");

while ($row = $q_deuda->fetch_assoc()) {
    $estado = strtoupper(trim($row['deuda']));
    if(isset($deudaResumen[$estado])) $deudaResumen[$estado]=$row['total'];
}

$presentes = 0;
$asistenciaTipos = ['ACTIVO'=>0,'VITALICIO'=>0,'TRANSEUNTE'=>0];

if ($evento_id > 0) {
    $q = $conexion->prepare("
      SELECT s.cargo, COUNT(*) total
      FROM asistencias a
      JOIN asistentes s ON s.id=a.asistente_id
      WHERE a.evento_id=? AND s.activo=1
      GROUP BY s.cargo
    ");
    $q->bind_param("i",$evento_id);
    $q->execute();
    $res = $q->get_result();

    while($r=$res->fetch_assoc()){
        $cargo=strtoupper(trim($r['cargo']));
        if(isset($asistenciaTipos[$cargo])) $asistenciaTipos[$cargo]=$r['total'];
        $presentes += $r['total'];
    }
}

$porc = $total>0 ? round(($presentes/$total)*100,2):0;

$evtList = $conexion->query("
SELECT e.id,e.titulo,e.fecha,e.hora_inicio,
(SELECT COUNT(*) FROM asistencias a WHERE a.evento_id=e.id) asist_count
FROM eventos e WHERE activo=1 ORDER BY fecha DESC
");

$ult=null;
if($evento_id>0){
$q=$conexion->prepare("
SELECT a.hora,a.estado,s.dni,s.cargo,
CONCAT(s.nombres,' ',s.apellidos) asistente
FROM asistencias a
JOIN asistentes s ON s.id=a.asistente_id
WHERE a.evento_id=? ORDER BY a.hora DESC LIMIT 10
");
$q->bind_param("i",$evento_id);
$q->execute();
$ult=$q->get_result();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="grid">

<!-- EVENTO -->
<div class="card col-12">
<h2>Evento</h2>

<form method="get" class="row">
<select name="evento_id" onchange="this.form.submit()">
<option value="0">-- Evento --</option>
<?php while($e=$evtList->fetch_assoc()): ?>
<option value="<?= $e['id'] ?>" <?= $e['id']==$evento_id?'selected':'' ?>>
<?= $e['fecha'].' '.$e['titulo'].' ('.$e['asist_count'].')' ?>
</option>
<?php endwhile; ?>
</select>

<a class="btn" href="registro/asistencia.php?evento_id=<?= $evento_id ?>">Registrar</a>
</form>

<?php if($evtInfo): ?>
<div class="badge"><?= $evtInfo['titulo'] ?></div>
<?php endif; ?>

</div>

<!-- KPI -->
<div class="card col-4">
<h2>Total</h2>
<div class="value"><?= $total ?></div>
<div class="badge">ACTIVOS: <?= $tipos['ACTIVO'] ?></div>
<div class="badge">VITALICIO: <?= $tipos['VITALICIO'] ?></div>
<div class="badge">TRANSEÚNTE: <?= $tipos['TRANSEUNTE'] ?></div>
</div>

<div class="card col-4">
<h2>Asistencia</h2>
<div class="value"><?= $presentes ?></div>
<div class="badge">ACTIVOS: <?= $asistenciaTipos['ACTIVO'] ?></div>
<div class="badge">VITALICIO: <?= $asistenciaTipos['VITALICIO'] ?></div>
<div class="badge">TRANSEÚNTE: <?= $asistenciaTipos['TRANSEUNTE'] ?></div>
</div>

<div class="card col-4">
<h2>Porcentaje</h2>
<div class="value"><?= $porc ?>%</div>
<div class="badge">DEUDA: <?= $deudaResumen['SI'] ?></div>
<div class="badge">AL DÍA: <?= $deudaResumen['NO'] ?></div>
</div>

<!-- TABLA -->
<div class="card col-12">
<h2>Últimos registros</h2>

<table class="table">
<tr>
<th>Hora</th><th>DNI</th><th>Nombre</th><th>Cargo</th><th>Estado</th>
</tr>

<?php if($ult): while($r=$ult->fetch_assoc()): ?>
<tr>
<td><?= substr($r['hora'],0,5) ?></td>
<td><?= $r['dni'] ?></td>
<td><?= $r['asistente'] ?></td>
<td><?= $r['cargo'] ?></td>
<td><?= $r['estado'] ?></td>
</tr>
<?php endwhile; endif; ?>

</table>

</div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>