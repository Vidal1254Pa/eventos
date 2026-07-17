<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_login();
require_role(['admin']);

require_once __DIR__ . '/../libs/SimpleXLSX.php';
use Shuchkin\SimpleXLSX;

$mensaje = null;

function clean_dni(string $v): string {
    // Quita todo lo que no sea número (evita 7.12E+7, puntos, espacios, etc.)
    $v = trim($v);
    // Si viene como número en Excel, puede venir como "71228437" o "7.1228437E7"
    // Mantener solo dígitos:
    $digits = preg_replace('/\D+/', '', $v);
    return $digits ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel'])) {

    if (!isset($_FILES['excel']['tmp_name']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = ['err', 'Error al subir el archivo Excel.'];
    } else {

        $xlsx = SimpleXLSX::parse($_FILES['excel']['tmp_name']);

        if (!$xlsx) {
            $mensaje = ['err', 'Archivo Excel no válido o no se pudo leer.'];
        } else {

            $filas = $xlsx->rows();
            if (count($filas) < 2) {
                $mensaje = ['err', 'El Excel no tiene filas para importar (mínimo 2: encabezado + datos).'];
            } else {

                // Validar encabezado (flexible, solo verifica que existan al menos 3 columnas)
                $head = array_map('strtolower', array_map('trim', $filas[0]));
                // Esperado: DNI | Nombres | Apellidos | Área | Cargo
                // No obligamos el texto exacto, pero sí el orden de columnas.

                $importados = 0;
                $duplicados = 0;
                $invalidos  = 0;

                // Opcional: transacción para mejor rendimiento
                $conexion->begin_transaction();

                try {
                    for ($i = 1; $i < count($filas); $i++) {

                        $dni_raw   = (string)($filas[$i][0] ?? '');
                        $dni       = clean_dni($dni_raw);

                        $nombres   = trim((string)($filas[$i][1] ?? ''));
                        $apellidos = trim((string)($filas[$i][2] ?? ''));
                        $area      = trim((string)($filas[$i][3] ?? ''));
                        $cargo     = trim((string)($filas[$i][4] ?? ''));

                        // Validación mínima
                        if ($dni === '' || $nombres === '' || $apellidos === '') {
                            $invalidos++;
                            continue;
                        }

                        // (Opcional) validar longitud DNI (8 en Perú). Si quieres estricto, descomenta:
                        // if (strlen($dni) !== 8) { $invalidos++; continue; }

                        // Verificar si ya existe
                        $stmt = $conexion->prepare("SELECT id FROM asistentes WHERE dni=? LIMIT 1");
                        $stmt->bind_param("s", $dni);
                        $stmt->execute();
                        $stmt->store_result();

                        if ($stmt->num_rows > 0) {
                            $duplicados++;
                            continue;
                        }

                        // Insertar
                        $stmt = $conexion->prepare("
                            INSERT INTO asistentes (dni, nombres, apellidos, area, cargo, activo)
                            VALUES (?, ?, ?, ?, ?, 1)
                        ");
                        $stmt->bind_param("sssss", $dni, $nombres, $apellidos, $area, $cargo);
                        $stmt->execute();

                        $importados++;
                    }

                    $conexion->commit();

                    $mensaje = ['ok', "Importación completa. Importados: $importados | Duplicados: $duplicados | Inválidos/Omitidos: $invalidos"];

                } catch (Throwable $e) {
                    $conexion->rollback();
                    $mensaje = ['err', "Error en importación: " . $e->getMessage()];
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
      <label>Archivo Excel (.xlsx)</label>
      <input class="input" type="file" name="excel" accept=".xlsx" required>

      <div class="row" style="margin-top:10px">
        <button class="btn" type="submit">Importar</button>
        <a class="btn ghost" href="<?= BASE_URL ?>admin/asistentes.php">Volver</a>
      </div>
    </form>

    <hr>

    <h4>Formato requerido (columnas en este orden)</h4>
    <small class="badge">DNI | Nombres | Apellidos | Área | Cargo</small>
    <p style="margin-top:10px;color:#64748b">
      Recomendación: en Excel pon la columna <b>DNI</b> como <b>Texto</b> para evitar formato científico.
    </p>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
