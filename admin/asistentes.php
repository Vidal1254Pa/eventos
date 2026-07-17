<?php
require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin']);

/* =========================
ACCIONES
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

csrf_check();
$accion = $_POST['accion'] ?? '';

/* CREAR */

if ($accion === 'crear') {

$dni = trim($_POST['dni']);
$nombres = trim($_POST['nombres']);
$apellidos = trim($_POST['apellidos']);
$mesa = trim($_POST['mesa']);
$area = trim($_POST['area']);
$cargo = trim($_POST['cargo']);
$deuda = $_POST['deuda'] ?? 'NO';

$stmt = $conexion->prepare("INSERT INTO asistentes(dni,nombres,apellidos,mesa,area,cargo,deuda,activo) VALUES(?,?,?,?,?,?,?,TRUE)");
$stmt->bind_param("sssssss",$dni,$nombres,$apellidos,$mesa,$area,$cargo,$deuda);
$stmt->execute();

redirect('admin/asistentes.php');
}

/* EDITAR */

if ($accion === 'editar') {

$id = (int)$_POST['id'];

$dni = trim($_POST['dni']);
$nombres = trim($_POST['nombres']);
$apellidos = trim($_POST['apellidos']);
$mesa = trim($_POST['mesa']);
$area = trim($_POST['area']);
$cargo = trim($_POST['cargo']);

$stmt = $conexion->prepare("UPDATE asistentes SET dni=?,nombres=?,apellidos=?,mesa=?,area=?,cargo=? WHERE id=?");
$stmt->bind_param("ssssssi",$dni,$nombres,$apellidos,$mesa,$area,$cargo,$id);
$stmt->execute();

redirect('admin/asistentes.php');
}

/* CAMBIAR DEUDA */

if ($accion === 'toggle_deuda') {

$id = (int)$_POST['id'];
$estado = $_POST['estado'];

$stmt = $conexion->prepare("UPDATE asistentes SET deuda=? WHERE id=?");
$stmt->bind_param("si",$estado,$id);
$stmt->execute();

redirect('admin/asistentes.php');
}

/* ELIMINAR */

if ($accion === 'eliminar') {

$id = (int)$_POST['id'];

$stmt = $conexion->prepare("DELETE FROM asistentes WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();

redirect('admin/asistentes.php');
}

/* MARCAR TODOS AL DIA */

if ($accion === 'reset_deuda') {

$conexion->query("UPDATE asistentes SET deuda='NO'");
redirect('admin/asistentes.php');

}

}

/* =========================
BUSCAR
========================= */

$buscar = trim($_GET['q'] ?? '');

if ($buscar != '') {

$like="%$buscar%";

$stmt=$conexion->prepare("SELECT * FROM asistentes 
WHERE dni LIKE ? OR nombres LIKE ? OR apellidos LIKE ? 
ORDER BY apellidos ASC, nombres ASC");

$stmt->bind_param("sss",$like,$like,$like);
$stmt->execute();
$lista=$stmt->get_result();

}else{

$lista=$conexion->query("SELECT * FROM asistentes ORDER BY apellidos ASC, nombres ASC");

}

/* =========================
EDITAR
========================= */

$edit_id = (int)($_GET['edit'] ?? 0);
$edit = null;

if($edit_id>0){

$stmt=$conexion->prepare("SELECT * FROM asistentes WHERE id=?");
$stmt->bind_param("i",$edit_id);
$stmt->execute();
$edit=$stmt->get_result()->fetch_assoc();

}

/* =========================
CONTADORES
========================= */

$total = $conexion->query("SELECT COUNT(*) total FROM asistentes")->fetch_assoc()['total'];
$deben = $conexion->query("SELECT COUNT(*) total FROM asistentes WHERE deuda='SI'")->fetch_assoc()['total'];
$aldia = $conexion->query("SELECT COUNT(*) total FROM asistentes WHERE deuda='NO' OR deuda IS NULL")->fetch_assoc()['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>

.deuda-si{background:#ffe5e5;}
.deuda-no{background:#e9ffe9;}

.resumen{
display:flex;
gap:20px;
margin-bottom:15px;
}

.box{
padding:10px 20px;
border-radius:6px;
font-weight:bold;
}

.total{background:#e3f2fd;}
.aldia{background:#e8f5e9;}
.deben{background:#ffebee;}

.btn-deuda{
padding:6px 12px;
border:none;
border-radius:5px;
font-weight:bold;
cursor:pointer;
}

</style>

<div class="resumen">

<div class="box total">👥 Total socios: <?= $total ?></div>
<div class="box aldia">🟢 Al día: <?= $aldia ?></div>
<div class="box deben">🔴 Deudores: <?= $deben ?></div>

</div>

<div class="card">

<h2><?= $edit ? 'Editar asistente' : 'Registrar asistente' ?></h2>

<form method="post" class="form">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="accion" value="<?= $edit?'editar':'crear' ?>">

<?php if($edit): ?>
<input type="hidden" name="id" value="<?= $edit['id'] ?>">
<?php endif; ?>

<div class="col-4">
<label>DNI</label>
<input class="input" name="dni" required value="<?= h($edit['dni'] ?? '') ?>">
</div>

<div class="col-4">
<label>Nombres</label>
<input class="input" name="nombres" required value="<?= h($edit['nombres'] ?? '') ?>">
</div>

<div class="col-4">
<label>Apellidos</label>
<input class="input" name="apellidos" required value="<?= h($edit['apellidos'] ?? '') ?>">
</div>

<div class="col-3">
<label>Mesa</label>
<input class="input" name="mesa" value="<?= h($edit['mesa'] ?? '') ?>">
</div>

<div class="col-3">
<label>Área</label>
<input class="input" name="area" value="<?= h($edit['area'] ?? '') ?>">
</div>

<div class="col-3">
<label>Situación</label>
<input class="input" name="cargo" value="<?= h($edit['cargo'] ?? '') ?>">
</div>

<div class="col-3">
<label>Deuda</label>

<select class="input" name="deuda">
<option value="NO">NO</option>
<option value="SI">SI</option>
</select>

</div>

<div class="col-12">

<button class="btn"><?= $edit?'Actualizar':'Guardar' ?></button>

<?php if($edit): ?>
<a class="btn ghost" href="<?= BASE_URL ?>admin/asistentes.php">Cancelar</a>
<?php endif; ?>

</div>

</form>

</div>

<br>

<div class="card">

<h2>Lista de asistentes</h2>

<form method="post" style="margin-bottom:10px">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="accion" value="reset_deuda">

<button
class="btn"
style="background:#2563eb;color:white;font-weight:bold"
onclick="return confirm('¿Marcar a todos como AL DÍA?')">

✅ Marcar todos como AL DÍA

</button>

</form>

<form method="get" style="margin-bottom:15px">

<input
class="input"
style="max-width:250px"
name="q"
value="<?= h($buscar) ?>"
placeholder="Buscar por DNI o nombre">

<button class="btn">Buscar</button>

<a class="btn ghost" href="<?= BASE_URL ?>admin/asistentes.php">
Limpiar
</a>

</form>

<table class="table">

<thead>

<tr>
<th>DNI</th>
<th>Nombre</th>
<th>Condición</th>
<th>Situación</th>
<th>Deuda</th>
<th>Acciones</th>
</tr>

</thead>

<tbody>

<?php while($s=$lista->fetch_assoc()): ?>

<tr class="<?= (($s['deuda'] ?? 'NO')=='SI')?'deuda-si':'deuda-no' ?>">

<td><?= h($s['dni']) ?></td>

<td><?= h($s['apellidos'].' '.$s['nombres']) ?></td>

<td><?= h($s['area']) ?></td>

<td><?= h($s['cargo']) ?></td>

<td>

<form method="post">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="accion" value="toggle_deuda">
<input type="hidden" name="id" value="<?= $s['id'] ?>">

<?php if(($s['deuda'] ?? 'NO')=='SI'): ?>

<input type="hidden" name="estado" value="NO">

<button class="btn-deuda" style="background:#dc2626;color:white">
🔴 DEBE
</button>

<?php else: ?>

<input type="hidden" name="estado" value="SI">

<button class="btn-deuda" style="background:#16a34a;color:white">
🟢 AL DÍA
</button>

<?php endif; ?>

</form>

</td>

<td>

<a class="btn ghost" href="<?= BASE_URL ?>admin/asistentes.php?edit=<?= $s['id'] ?>">
✏️ Editar
</a>

<form method="post" style="display:inline">

<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<input type="hidden" name="accion" value="eliminar">
<input type="hidden" name="id" value="<?= $s['id'] ?>">

<button class="btn danger">
🗑 Eliminar
</button>

</form>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
