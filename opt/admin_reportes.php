<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

// Normaliza la variable: usa $pdo siempre
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}
if (!($pdo instanceof PDO)) {
    http_response_code(500);
    die('Error de conexión: no se obtuvo una instancia PDO.');
}

/**
 * Helper seguro para SELECT
 */
function fetchAllSafe(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$desde = $_GET['desde'] ?? null;   // formato YYYY-MM-DD
$hasta = $_GET['hasta'] ?? null;   // formato YYYY-MM-DD
$empresaId = isset($_GET['empresa_id']) ? (int)$_GET['empresa_id'] : null;

$where = [];
$params = [];

if ($desde) {
    $where[] = 'r.fecha >= :desde';
    $params[':desde'] = $desde . ' 00:00:00';
}
if ($hasta) {
    $where[] = 'r.fecha <= :hasta';
    $params[':hasta'] = $hasta . ' 23:59:59';
}
if ($empresaId) {
    $where[] = 'r.empresa_id = :empresa_id';
    $params[':empresa_id'] = $empresaId;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';


$sql = "
SELECT
  r.id,
  r.fecha,
  e.nombre     AS empresa,
  c.nombre     AS cliente,
  r.total      AS monto,
  r.estado
FROM reportes r
LEFT JOIN empresas e ON e.id = r.empresa_id
LEFT JOIN clientes c ON c.id = r.cliente_id
{$whereSql}
ORDER BY r.fecha DESC
LIMIT 500
";

try {
    $rows = fetchAllSafe($pdo, $sql, $params);
} catch (Throwable $e) {
    http_response_code(500);
    die('Error al consultar reportes: ' . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reportes</title>
  <link rel="stylesheet" href="../css/admin-reportes.css">
  <style>
  
    body { font-family: system-ui, Arial, sans-serif; margin: 20px; }
    h1 { margin-bottom: 12px; }
    form.filtros { display: flex; gap: 12px; align-items: end; margin-bottom: 16px; flex-wrap: wrap; }
    form.filtros label { display: block; font-weight: 600; font-size: 14px; }
    form.filtros input, form.filtros select { padding: 6px 8px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #e5e7eb; padding: 8px 10px; font-size: 14px; }
    th { background: #f3f4f6; text-align: left; }
    tr:nth-child(even) { background: #fafafa; }
    .monto { text-align: right; font-variant-numeric: tabular-nums; }
    .estado { font-weight: 600; }
  </style>
</head>
<body>
  <h1>Reportes</h1>

  <!-- Filtros  -->
  <form class="filtros" method="get">
    <div>
      <label for="desde">Desde</label>
      <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($desde ?? '') ?>">
    </div>
    <div>
      <label for="hasta">Hasta</label>
      <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($hasta ?? '') ?>">
    </div>
    <div>
      <label for="empresa_id">Empresa</label>
      <select id="empresa_id" name="empresa_id">
        <option value="">Todas</option>
        <?php
        // carga rápida de empresas 
        try {
            $empresas = fetchAllSafe($pdo, "SELECT id, nombre FROM empresas ORDER BY nombre");
            foreach ($empresas as $emp) {
                $sel = ($empresaId && $empresaId === (int)$emp['id']) ? 'selected' : '';
                echo '<option value="'.(int)$emp['id'].'" '.$sel.'>'.htmlspecialchars($emp['nombre']).'</option>';
            }
        } catch (Throwable $e) { }
        ?>
      </select>
    </div>
    <div>
      <button type="submit">Filtrar</button>
    </div>
  </form>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Fecha</th>
        <th>Empresa</th>
        <th>Cliente</th>
        <th>Monto</th>
        <th>Estado</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6">Sin resultados.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['fecha']) ?></td>
            <td><?= htmlspecialchars($r['empresa'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['cliente'] ?? '-') ?></td>
            <td class="monto">$ <?= number_format((float)$r['monto'], 2) ?></td>
            <td class="estado"><?= htmlspecialchars($r['estado']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</body>
</html>
