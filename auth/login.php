<?php
require_once __DIR__ . '/../config.php';
if (is_logged_in()) redirect('dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $conexion->prepare("SELECT id,nombre,usuario,pass_hash,rol,activo FROM usuarios WHERE usuario=? LIMIT 1");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res && (int)$res['activo'] === 1 && password_verify($pass, $res['pass_hash'])) {
        $_SESSION['user'] = [
            'id' => (int)$res['id'],
            'nombre' => $res['nombre'],
            'usuario' => $res['usuario'],
            'rol' => $res['rol']
        ];
        flash_set('ok', 'Bienvenido, ' . $res['nombre']);
        redirect('dashboard.php');
    } else {
        flash_set('err', 'Usuario o contraseña incorrectos.');
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid">

  <!-- LOGIN -->
  <div class="card col-6">
    <h2>Ingresar</h2>

    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

      <div class="col-12">
        <label>Usuario</label>
        <input class="input" name="usuario" required />
      </div>

      <div class="col-12">
        <label>Contraseña</label>
        <input class="input" type="password" name="password" required />
      </div>

      <div class="col-12 row">
        <button class="btn" type="submit">Entrar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>">Inicio</a>
      </div>

      <div class="col-12">
        <small class="badge">Usuario demo: admin · Clave: admin123</small>
      </div>
    </form>
  </div>

  <!-- INFO -->
  <div class="card col-6">
    <h2>¿Qué incluye?</h2>
    <ul>
      <li>Usuarios del sistema (Admin / Registro / Viewer)</li>
      <li>Registro de asistentes (DNI, nombres, área, cargo)</li>
      <li>Registro de asistencia por día (evita duplicados)</li>
      <li>Panel con cantidad y porcentaje de asistencia del día</li>
      <li>Reportes por rango de fechas</li>
      <li>Exportar a Excel (XLSX) y PDF</li>
    </ul>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>