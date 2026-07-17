<?php
require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $rol = $_POST['rol'] ?? 'viewer';
        $pass = $_POST['password'] ?? '';
        $rolesValidos = ['admin', 'registro', 'viewer'];

        if (!in_array($rol, $rolesValidos, true)) {
            $rol = 'viewer';
        }

        if ($nombre && $usuario && strlen($pass) >= 8) {
            $hash = password_hash($pass, PASSWORD_BCRYPT);

            try {
                $stmt = $conexion->prepare('INSERT INTO usuarios(nombre, usuario, pass_hash, rol, activo) VALUES(?, ?, ?, ?, TRUE)');
                $stmt->bind_param('ssss', $nombre, $usuario, $hash, $rol);
                $stmt->execute();
                flash_set('ok', 'Usuario creado.');
            } catch (Throwable $e) {
                flash_set('err', db_is_unique_violation($e) ? 'El usuario ya existe.' : 'No se pudo crear el usuario.');
            }
        } else {
            flash_set('err', 'Complete todos los campos y use una clave de al menos 8 caracteres.');
        }

        redirect('admin/usuarios.php');
    }

    if ($accion === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $actual = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conexion->prepare('UPDATE usuarios SET activo = CASE WHEN activo THEN FALSE ELSE TRUE END WHERE id = ? AND id <> ?');
            $stmt->bind_param('ii', $id, $actual);
            $stmt->execute();
            flash_set('ok', 'Estado actualizado.');
        }

        redirect('admin/usuarios.php');
    }

    if ($accion === 'reset') {
        $id = (int) ($_POST['id'] ?? 0);
        $new = $_POST['newpass'] ?? '';

        if ($id > 0 && strlen($new) >= 8) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $conexion->prepare('UPDATE usuarios SET pass_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            flash_set('ok', 'Contrasena actualizada.');
        } else {
            flash_set('err', 'Ingrese una nueva contrasena de al menos 8 caracteres.');
        }

        redirect('admin/usuarios.php');
    }
}

$lista = $conexion->query('SELECT id, nombre, usuario, rol, activo, creado_en FROM usuarios ORDER BY id DESC');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid">
  <div class="card" style="grid-column: span 5;">
    <h2>Crear usuario</h2>
    <form method="post" class="form">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="accion" value="crear">
      <div class="col-12"><label>Nombre</label><input class="input" name="nombre" required></div>
      <div class="col-12"><label>Usuario</label><input class="input" name="usuario" required></div>
      <div class="col-6"><label>Rol</label>
        <select name="rol">
          <option value="admin">admin</option>
          <option value="registro" selected>registro</option>
          <option value="viewer">viewer</option>
        </select>
      </div>
      <div class="col-6"><label>Contrasena</label><input class="input" type="password" name="password" minlength="8" required></div>
      <div class="col-12"><button class="btn" type="submit">Guardar</button></div>
    </form>
  </div>

  <div class="card" style="grid-column: span 7;">
    <h2>Usuarios</h2>
    <table class="table">
      <thead><tr><th>ID</th><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Activo</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php while ($u = $lista->fetch_assoc()): ?>
        <tr>
          <td><?= (int) $u['id'] ?></td>
          <td><?= h($u['nombre']) ?></td>
          <td><?= h($u['usuario']) ?></td>
          <td><span class="badge"><?= h($u['rol']) ?></span></td>
          <td><?= db_bool($u['activo']) ? 'Si' : 'No' ?></td>
          <td class="row">
            <form method="post">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="accion" value="toggle">
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <button class="btn ghost" <?= (int) $u['id'] === (int) ($_SESSION['user']['id'] ?? 0) ? 'disabled' : '' ?> data-confirm="Cambiar estado de este usuario?">Activar/Desactivar</button>
            </form>

            <form method="post" class="row">
              <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
              <input type="hidden" name="accion" value="reset">
              <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
              <input class="input" style="max-width:160px" name="newpass" placeholder="Nueva clave" minlength="8">
              <button class="btn" type="submit">Reset</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
