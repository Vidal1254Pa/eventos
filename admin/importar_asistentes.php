<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin']);

require_once __DIR__ . '/../libs/SimpleXLSX.php';

use Shuchkin\SimpleXLSX;

$mensaje = null;

function clean_dni(string $value): string
{
    $digits = preg_replace('/\D+/', '', trim($value));
    return $digits ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {
    csrf_check();

    if (!isset($_FILES['excel']['tmp_name']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = ['err', 'Error al subir el archivo Excel.'];
    } elseif (strtolower((string) pathinfo($_FILES['excel']['name'] ?? '', PATHINFO_EXTENSION)) !== 'xlsx') {
        $mensaje = ['err', 'Solo se permiten archivos .xlsx.'];
    } elseif ((int) ($_FILES['excel']['size'] ?? 0) > 5 * 1024 * 1024) {
        $mensaje = ['err', 'El archivo supera el limite de 5 MB.'];
    } else {
        $xlsx = SimpleXLSX::parse($_FILES['excel']['tmp_name']);

        if (!$xlsx) {
            $mensaje = ['err', 'Archivo Excel invalido o no se pudo leer.'];
        } else {
            $filas = $xlsx->rows();
            if (count($filas) < 2) {
                $mensaje = ['err', 'El Excel no tiene filas para importar.'];
            } else {
                $importados = 0;
                $duplicados = 0;
                $invalidos = 0;
                $conexion->begin_transaction();

                try {
                    for ($i = 1; $i < count($filas); $i++) {
                        $dni = clean_dni((string) ($filas[$i][0] ?? ''));
                        $nombres = trim((string) ($filas[$i][1] ?? ''));
                        $apellidos = trim((string) ($filas[$i][2] ?? ''));
                        $area = trim((string) ($filas[$i][3] ?? ''));
                        $cargo = trim((string) ($filas[$i][4] ?? ''));

                        if ($dni === '' || $nombres === '' || $apellidos === '') {
                            $invalidos++;
                            continue;
                        }

                        $stmt = $conexion->prepare('SELECT id FROM asistentes WHERE dni = ? LIMIT 1');
                        $stmt->bind_param('s', $dni);
                        $stmt->execute();
                        $stmt->store_result();

                        if ($stmt->num_rows > 0) {
                            $duplicados++;
                            continue;
                        }

                        $stmt = $conexion->prepare('
                            INSERT INTO asistentes (dni, nombres, apellidos, area, cargo, activo)
                            VALUES (?, ?, ?, ?, ?, TRUE)
                        ');
                        $stmt->bind_param('sssss', $dni, $nombres, $apellidos, $area, $cargo);
                        $stmt->execute();

                        $importados++;
                    }

                    $conexion->commit();
                    $mensaje = ['ok', "Importacion completa. Importados: $importados | Duplicados: $duplicados | Invalidos/Omitidos: $invalidos"];
                } catch (Throwable $e) {
                    $conexion->rollback();
                    $mensaje = ['err', 'Error en importacion: ' . $e->getMessage()];
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid">
  <div class="card" style="grid-column: span 6;">
    <h2>Importar asistentes desde Excel</h2>

    <?php if ($mensaje): ?>
      <div class="alert <?= $mensaje[0] === 'ok' ? 'ok' : 'err' ?>">
        <?= h($mensaje[1]) ?>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <label>Archivo Excel (.xlsx)</label>
      <input class="input" type="file" name="excel" accept=".xlsx" required>

      <div class="row" style="margin-top:10px">
        <button class="btn" type="submit">Importar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>admin/asistentes.php">Volver</a>
      </div>
    </form>

    <hr>

    <h4>Formato requerido</h4>
    <small class="badge">DNI | Nombres | Apellidos | Area | Cargo</small>
    <p style="margin-top:10px;color:#64748b">
      Recomendacion: en Excel pon la columna <b>DNI</b> como <b>Texto</b> para evitar formato cientifico.
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
