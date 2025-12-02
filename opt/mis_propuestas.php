<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard_empresa.php';
require_once __DIR__ . '/conexion.php';

$empresaId = $_SESSION['empresa_id'] ?? null;
if (!$empresaId) {
    http_response_code(403);
    die('Empresa no autenticada');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreCupon    = trim($_POST['nombreCupon'] ?? '');
    $precioRegular  = (float)($_POST['precioRegular'] ?? 0);
    $precioPromo    = (float)($_POST['precioPromocion'] ?? 0);
    $fechaInicio    = $_POST['FechaInicio']       ?? '';
    $fechaFin       = $_POST['FechaFin']          ?? '';
    $fechaLimite    = $_POST['FechaLimiteCanje']  ?? '';
    $cantidadRaw    = trim($_POST['maximo']       ?? '');
    $descripcion    = trim($_POST['descripcion']  ?? '');
    $estado         = $_POST['estado']            ?? 'activo'; 

    // Validaciones básicas
    if ($nombreCupon === '')          $errores[] = 'El título de la oferta es obligatorio.';
    if ($precioRegular <= 0)          $errores[] = 'El precio regular debe ser mayor que 0.';
    if ($precioPromo <= 0)            $errores[] = 'El precio de oferta debe ser mayor que 0.';
    if ($precioPromo >= $precioRegular)
        $errores[] = 'El precio de oferta debe ser menor al precio regular.';
    if ($fechaInicio === '' || $fechaFin === '' || $fechaLimite === '')
        $errores[] = 'Todas las fechas son obligatorias.';
    if ($descripcion === '')          $errores[] = 'La descripción es obligatoria.';

    // Cantidad opcional (NULL = ilimitado)
    $maximo = null;
    if ($cantidadRaw !== '') {
        $maximo = (int)$cantidadRaw;
        if ($maximo <= 0) {
            $errores[] = 'La cantidad de cupones debe ser mayor que 0 si se indica.';
        }
    }

    // Ajusta estos valores al ENUM que dejes en DB
    $estado = $_POST['estado'] ?? 'activo';
if (!in_array($estado, ['activo', 'Desactivo'], true)) {
    $estado = 'activo';
}

    if (!$errores) {
        $sql = "INSERT INTO cupones
                (empresaId, estado, FechaInicio, FechaFin, FechaLimiteCanje,
                 nombreCupon, descripcion, totalDisp, maximo,
                 precioRegular, precioPromocion)
                VALUES
                (:empresaId, :estado, :FechaInicio, :FechaFin, :FechaLimiteCanje,
                 :nombreCupon, :descripcion, :totalDisp, :maximo,
                 :precioRegular, :precioPromocion)";

        // totalDisp inicial = maximo (si hay limite) o 0 si es ilimitado
        $totalDisp = $maximo ?? 0;

      $stmt = $conn->prepare($sql);
      $stmt->execute([
    ':empresaId'        => $empresaId,
    ':estado'           => $estado,
    ':FechaInicio'      => $fechaInicio,
    ':FechaFin'         => $fechaFin,
    ':FechaLimiteCanje' => $fechaLimite,
    ':nombreCupon'      => $nombreCupon,
    ':descripcion'      => $descripcion,
    ':totalDisp'        => $totalDisp,
    ':maximo'           => $maximo,
    ':precioRegular'    => $precioRegular,
    ':precioPromocion'  => $precioPromo,
]);

        header('Location: dashboard_empresa.php?cupon_creado=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Proponer cupón</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <div class="panel-wrap">
    <div class="card">
      <h2>Proponer nuevo cupón</h2>

      <?php if ($errores): ?>
        <ul style="color:red;">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post">
        <label>Título de la oferta</label><br>
        <input type="text" name="nombreCupon" required><br><br>

        <label>Precio regular</label><br>
        <input type="number" step="0.01" name="precioRegular" required><br><br>

        <label>Precio de oferta</label><br>
        <input type="number" step="0.01" name="precioPromocion" required><br><br>

        <label>Fecha de inicio de oferta</label><br>
        <input type="date" name="FechaInicio" required><br><br>

        <label>Fecha de fin de la oferta</label><br>
        <input type="date" name="FechaFin" required><br><br>

        <label>Fecha límite para canjear cupón</label><br>
        <input type="date" name="FechaLimiteCanje" required><br><br>

        <label>Cantidad de cupones (dejar vacío = sin límite)</label><br>
        <input type="number" name="maximo" min="1"><br><br>

        <label>Descripción de la oferta</label><br>
        <textarea name="descripcion" rows="4" required></textarea><br><br>

        <label>Estado de la oferta</label><br>
        <select name="estado">
          <option value="activo">Disponible</option>
          <option value="Desactivo">No disponible</option>
          <!-- o activo / Desactivo si mantienes el ENUM original -->
        </select><br><br>

        <button type="submit">Guardar cupón</button>
        <a href="dashboard_empresa.php">Cancelar</a>
      </form>
    </div>
  </div>
</body>
</html>