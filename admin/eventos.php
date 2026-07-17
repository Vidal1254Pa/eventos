<?php
require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lugar = trim($_POST['lugar'] ?? '');
        $fecha = preg_replace('/[^0-9\-]/', '', $_POST['fecha'] ?? '');
        $hora_inicio = preg_replace('/[^0-9:]/', '', $_POST['hora_inicio'] ?? '');
        $hora_fin = preg_replace('/[^0-9:]/', '', $_POST['hora_fin'] ?? '');
        $activo = isset($_POST['activo']);

        if ($titulo && $fecha && $hora_inicio) {
            $uid = (int) ($_SESSION['user']['id'] ?? 0);
            $stmt = $conexion->prepare('
                INSERT INTO eventos(titulo, descripcion, lugar, fecha, hora_inicio, hora_fin, activo, creado_por)
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->bind_param('ssssssii', $titulo, $descripcion, $lugar, $fecha, $hora_inicio, $hora_fin, $activo, $uid);
            $stmt->execute();
            flash_set('ok', 'Evento creado.');
        } else {
            flash_set('err', 'Complete titulo, fecha y hora de inicio.');
        }

        redirect('admin/eventos.php');
    }

    if ($accion === 'editar') {
        $id = (int) ($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $lugar = trim($_POST['lugar'] ?? '');
        $fecha = preg_replace('/[^0-9\-]/', '', $_POST['fecha'] ?? '');
        $hora_inicio = preg_replace('/[^0-9:]/', '', $_POST['hora_inicio'] ?? '');
        $hora_fin = preg_replace('/[^0-9:]/', '', $_POST['hora_fin'] ?? '');
        $activo = isset($_POST['activo']);

        if ($id > 0 && $titulo && $fecha && $hora_inicio) {
            $stmt = $conexion->prepare('UPDATE eventos SET titulo = ?, descripcion = ?, lugar = ?, fecha = ?, hora_inicio = ?, hora_fin = ?, activo = ? WHERE id = ?');
            $stmt->bind_param('ssssssii', $titulo, $descripcion, $lugar, $fecha, $hora_inicio, $hora_fin, $activo, $id);
            $stmt->execute();
            flash_set('ok', 'Evento actualizado.');
        } else {
            flash_set('err', 'Datos invalidos.');
        }

        redirect('admin/eventos.php');
    }

    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conexion->prepare('DELETE FROM eventos WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            flash_set('ok', 'Evento eliminado con sus asistencias asociadas.');
        }

        redirect('admin/eventos.php');
    }
}

$edit_id = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($edit_id > 0) {
    $stmt = $conexion->prepare('SELECT * FROM eventos WHERE id = ?');
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}

$lista = $conexion->query("
  SELECT e.*,
  (SELECT COUNT(*) FROM asistencias a WHERE a.evento_id = e.id) AS asistencias_count
  FROM eventos e
  ORDER BY e.fecha DESC, e.hora_inicio DESC
");

require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid">
  <div class="card" style="grid-column: span 5;">
    <h2><?= $edit ? 'Editar evento' : 'Crear evento' ?></h2>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="accion" value="<?= $edit ? 'editar' : 'crear' ?>">
      <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>

      <div class="col-12"><label>Titulo</label><input class="input" name="titulo" required value="<?= h($edit['titulo'] ?? '') ?>"></div>
      <div class="col-12"><label>Descripcion (opcional)</label><input class="input" name="descripcion" value="<?= h($edit['descripcion'] ?? '') ?>"></div>
      <div class="col-12"><label>Lugar (opcional)</label><input class="input" name="lugar" value="<?= h($edit['lugar'] ?? '') ?>"></div>

      <div class="col-6"><label>Fecha</label><input class="input" type="date" name="fecha" required value="<?= h($edit['fecha'] ?? date('Y-m-d')) ?>"></div>
      <div class="col-3"><label>Inicio</label><input class="input" type="time" name="hora_inicio" required value="<?= h(substr((string) ($edit['hora_inicio'] ?? '08:00:00'), 0, 5)) ?>"></div>
      <div class="col-3"><label>Fin</label><input class="input" type="time" name="hora_fin" value="<?= h(substr((string) ($edit['hora_fin'] ?? ''), 0, 5)) ?>"></div>

      <div class="col-12">
        <div class="row" style="align-items:center">
          <input type="checkbox" name="activo" <?= !$edit || db_bool($edit['activo'] ?? true) ? 'checked' : '' ?>>
          <small class="badge">Activo</small>
        </div>
      </div>

      <div class="col-12 row">
        <button class="btn" type="submit"><?= $edit ? 'Actualizar' : 'Guardar' ?></button>
        <?php if ($edit): ?><a class="btn ghost" href="<?= BASE_URL ?>admin/eventos.php">Cancelar</a><?php endif; ?>
      </div>
      <div class="col-12">
        <small class="badge">Cada evento tiene su propio conteo de asistentes.</small>
      </div>
    </form>
  </div>

  <div class="card" style="grid-column: span 7;">
    <h2>Eventos</h2>
    <table class="table">
      <thead><tr><th>Fecha</th><th>Hora</th><th>Titulo</th><th>Lugar</th><th>Act.</th><th>Asist.</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php while ($e = $lista->fetch_assoc()): ?>
          <tr>
            <td><?= h($e['fecha']) ?></td>
            <td><?= h(substr((string) $e['hora_inicio'], 0, 5)) ?><?= $e['hora_fin'] ? ' - ' . h(substr((string) $e['hora_fin'], 0, 5)) : '' ?></td>
            <td><?= h($e['titulo']) ?></td>
            <td><?= h($e['lugar'] ?? '') ?></td>
            <td><?= db_bool($e['activo']) ? 'Si' : 'No' ?></td>
            <td><span class="badge"><?= (int) $e['asistencias_count'] ?></span></td>
            <td class="row">
              <a class="btn ghost" href="<?= BASE_URL ?>admin/eventos.php?edit=<?= (int) $e['id'] ?>">Editar</a>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $e['id'] ?>">
                <button class="btn danger" data-confirm="Eliminar evento y asistencias asociadas?">Eliminar</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
