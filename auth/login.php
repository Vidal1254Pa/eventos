<?php
require_once __DIR__ . '/../config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';
    $throttle = auth_throttle_status($user);

    if (($throttle['blocked_until'] ?? 0) > time()) {
        $waitMinutes = (int) ceil((($throttle['blocked_until'] ?? 0) - time()) / 60);
        flash_set('err', 'Demasiados intentos. Intente nuevamente en ' . max(1, $waitMinutes) . ' minuto(s).');
        redirect('auth/login.php');
    }

    $stmt = $conexion->prepare('SELECT id, nombre, usuario, pass_hash, rol, activo FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && db_bool($res['activo']) && password_verify($pass, $res['pass_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $res['id'],
            'nombre' => $res['nombre'],
            'usuario' => $res['usuario'],
            'rol' => $res['rol'],
        ];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        auth_clear_failures($user);
        flash_set('ok', 'Bienvenido, ' . $res['nombre']);
        redirect('dashboard.php');
    }

    auth_register_failure($user);
    flash_set('err', 'Usuario o contrasena incorrectos.');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid">
  <div class="card col-6">
    <h2>Ingresar</h2>

    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <div class="col-12">
        <label>Usuario</label>
        <input class="input" name="usuario" required>
      </div>

      <div class="col-12">
        <label>Contrasena</label>
        <input class="input" type="password" name="password" required>
      </div>

      <div class="col-12 row">
        <button class="btn" type="submit">Entrar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>">Inicio</a>
      </div>

      <div class="col-12">
        <small class="badge">Cambie la clave inicial del administrador despues del primer acceso.</small>
      </div>
    </form>
  </div>

  <div class="card col-6">
    <h2>Que incluye</h2>
    <ul>
      <li>Usuarios del sistema (Admin / Registro / Viewer)</li>
      <li>Registro de asistentes (DNI, nombres, area, cargo)</li>
      <li>Registro de asistencia por dia (evita duplicados)</li>
      <li>Panel con cantidad y porcentaje de asistencia del dia</li>
      <li>Reportes por rango de fechas</li>
      <li>Exportar a Excel (XLSX) y PDF</li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
