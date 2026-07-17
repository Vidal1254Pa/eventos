<?php
require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin', 'registro']);

$ahora = date('H:i:s');
$evento_id_sel = isset($_GET['evento_id']) ? (int) $_GET['evento_id'] : 0;

if ($evento_id_sel === 0) {
    $hoy = date('Y-m-d');
    $stmt = $conexion->prepare('SELECT id FROM eventos WHERE activo = TRUE AND fecha = ? ORDER BY hora_inicio ASC LIMIT 1');
    $stmt->bind_param('s', $hoy);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $evento_id_sel = (int) $row['id'];
    }
}

$evtInfo = null;
if ($evento_id_sel > 0) {
    $stmtI = $conexion->prepare('SELECT * FROM eventos WHERE id = ?');
    $stmtI->bind_param('i', $evento_id_sel);
    $stmtI->execute();
    $evtInfo = $stmtI->get_result()->fetch_assoc();
}

$toast = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $evento_id = (int) ($_POST['evento_id'] ?? 0);
    $codigo = trim($_POST['codigo'] ?? '');
    $estado = $_POST['estado'] ?? 'PRESENTE';
    $obs = trim($_POST['obs'] ?? '');

    if ($evento_id <= 0) {
        $toast = ['type' => 'err', 'msg' => 'Seleccione un evento'];
    } elseif ($codigo === '') {
        $toast = ['type' => 'err', 'msg' => 'Escanee DNI'];
    } else {
        $stmtA = $conexion->prepare("
            SELECT id, CONCAT(nombres, ' ', apellidos) AS nombre, dni, deuda
            FROM asistentes
            WHERE dni = ? AND activo = TRUE
            LIMIT 1
        ");
        $stmtA->bind_param('s', $codigo);
        $stmtA->execute();
        $asistente = $stmtA->get_result()->fetch_assoc();

        if (!$asistente) {
            $toast = ['type' => 'err', 'msg' => 'DNI ' . $codigo . ' no encontrado'];
        } elseif (strtoupper((string) ($asistente['deuda'] ?? 'NO')) === 'SI') {
            $toast = ['type' => 'err', 'msg' => $asistente['nombre'] . ' (DNI ' . $asistente['dni'] . ') tiene deuda'];
        } else {
            try {
                $fecha_actual = date('Y-m-d');
                $uid = (int) ($_SESSION['user']['id'] ?? 0);

                $stmt = $conexion->prepare('
                    INSERT INTO asistencias(evento_id, asistente_id, fecha, hora, estado, registrado_por, obs)
                    VALUES(?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->bind_param(
                    'iisssis',
                    $evento_id,
                    $asistente['id'],
                    $fecha_actual,
                    $ahora,
                    $estado,
                    $uid,
                    $obs
                );
                $stmt->execute();

                $toast = ['type' => 'ok', 'msg' => $asistente['nombre'] . ' (DNI ' . $asistente['dni'] . ') registrado'];
            } catch (Throwable $e) {
                if (db_is_unique_violation($e)) {
                    $toast = ['type' => 'err', 'msg' => $asistente['nombre'] . ' (DNI ' . $asistente['dni'] . ') ya registrado'];
                } else {
                    $toast = ['type' => 'err', 'msg' => 'Error al registrar la asistencia.'];
                }
            }
        }
    }

    $_SESSION['toast'] = $toast;
    redirect('registro/asistencia.php?evento_id=' . $evento_id);
}

if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    unset($_SESSION['toast']);
}

$evtList = $conexion->query('SELECT id, titulo, fecha FROM eventos WHERE activo = TRUE ORDER BY fecha DESC');

$hoyRes = null;
if ($evento_id_sel > 0) {
    $q = $conexion->prepare("
        SELECT a.hora, a.estado,
        s.dni, CONCAT(s.nombres, ' ', s.apellidos) AS asistente
        FROM asistencias a
        INNER JOIN asistentes s ON s.id = a.asistente_id
        WHERE a.evento_id = ?
        ORDER BY a.hora DESC
    ");
    $q->bind_param('i', $evento_id_sel);
    $q->execute();
    $hoyRes = $q->get_result();
}

require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($toast): ?>
  <div id="toast" class="toast <?= h($toast['type']) ?>">
    <?= h($toast['msg']) ?>
  </div>
<?php endif; ?>

<div class="grid">
  <div class="card col-5">
    <h2>Asistencia</h2>

    <form method="get" class="row">
      <div style="flex:1">
        <label>Evento</label>
        <select name="evento_id" onchange="this.form.submit()">
          <option value="0">-- Seleccionar evento --</option>
          <?php while ($e = $evtList->fetch_assoc()): ?>
            <option value="<?= (int) $e['id'] ?>" <?= (int) $e['id'] === $evento_id_sel ? 'selected' : '' ?>>
              <?= h($e['fecha'] . ' ' . $e['titulo']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    </form>

    <?php if ($evento_id_sel > 0): ?>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="evento_id" value="<?= $evento_id_sel ?>">

      <div class="col-12">
        <label>Escanear DNI</label>
        <input class="input" name="codigo" autofocus placeholder="Escanear...">
      </div>

      <div class="col-6">
        <label>Estado</label>
        <select name="estado">
          <option>PRESENTE</option>
          <option>TARDE</option>
        </select>
      </div>

      <div class="col-6">
        <label>Hora</label>
        <input class="input" value="<?= h(substr($ahora, 0, 5)) ?>" disabled>
      </div>

      <div class="col-12">
        <button class="btn">Registrar</button>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <div class="card col-7">
    <h2>Registros</h2>

    <table class="table">
      <thead>
        <tr>
          <th>Hora</th>
          <th>DNI</th>
          <th>Nombre</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($hoyRes): while ($r = $hoyRes->fetch_assoc()): ?>
        <tr>
          <td><?= h(substr((string) $r['hora'], 0, 5)) ?></td>
          <td><?= h($r['dni']) ?></td>
          <td><?= h($r['asistente']) ?></td>
          <td><?= h($r['estado']) ?></td>
        </tr>
        <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
window.addEventListener("load", () => {
  const t = document.getElementById("toast");
  if (t) {
    t.style.top = "20px";
    t.style.left = "50%";
    t.style.transform = "translateX(-50%)";

    setTimeout(() => {
      t.classList.add("show");
    }, 50);

    setTimeout(() => {
      t.remove();
    }, 3000);
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
