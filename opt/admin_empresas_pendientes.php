<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard_admin.php';

/* Conexión */
$loaded = false;
foreach ([__DIR__ . '/conexion.php', __DIR__ . '/../conexion.php'] as $p) {
  if (is_file($p)) { require_once $p; $loaded = true; break; }
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
  try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=cuponera;charset=utf8mb4','root','',[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
  } catch (Throwable $e) { die('No se pudo crear conexión PDO: ' . $e->getMessage()); }
}

/* Traer empresas en estado pendientes*/
try {
  $stmt = $pdo->prepare("
    SELECT empresaId, nombreEmpresa, NIT, correo, usuario, estado, fechaCreado
    FROM empresas
    WHERE estado = 'pendiente'
    ORDER BY fechaCreado DESC
  ");
  $stmt->execute();
  $pendientes = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
  die('Error al consultar empresas: ' . $e->getMessage());
}

$csrf = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Empresas pendientes</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .wrap { max-width: 1100px; margin: 30px auto; padding: 0 16px;}
    .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); padding:24px; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; }
    th, td { padding:12px 14px; border-bottom:1px solid #eef2f7; font-size:14px; }
    th { background:#f9fafb; text-align:left; color:#374151; font-weight:700; }
    .actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .btn { border:none; padding:8px 10px; border-radius:8px; cursor:pointer; }
    .btn-approve { background:#10b981; color:#fff; }
    .btn-reject { background:#ef4444; color:#fff; }
    input[type="number"], input[type="text"] { padding:6px 8px; border:1px solid #e5e7eb; border-radius:6px; }
    .logo { height:46px; width:auto; border-radius:8px; }
    #header { display:flex; align-items:center; justify-content:space-between; padding:10px 20px; background:#fff; border-bottom:1px solid #e5e7eb; }
    #navbar { display:flex; gap:16px; list-style:none; }
    #navbar li a { text-decoration:none; color:#111827; font-weight:600; padding:6px 10px; border-radius:8px; }
    #navbar li a.active, #navbar li a:hover { background:#f3f4f6; }
  </style>
</head>
<body>
<section id="header">
  <a href="index.php"><img src="../img/logo.jpg" class="logo" alt="Logo"></a>
  <ul id="navbar">
    <li><a href="dashboard_admin.php"><i class="fa-solid fa-gauge"></i> Admin</a></li>
    <li><a class="active" href="admin_empresas_pendientes.php"><i class="fa-solid fa-hourglass-half"></i> Empresas pendientes</a></li>
    <li><a href="admin_reportes.php"><i class="fa-solid fa-chart-line"></i> Reportes</a></li>
  </ul>
</section>

<div class="wrap">
  <div class="card">
    <h2>Empresas pendientes de aprobación</h2>
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Empresa</th><th>NIT</th><th>Correo</th><th>Usuario</th><th>Creado</th>
          <th>Aprobar (comisión %)</th><th>Rechazar</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$pendientes): ?>
        <tr><td colspan="8">No hay solicitudes pendientes.</td></tr>
      <?php else: foreach ($pendientes as $e): ?>
        <tr>
          <td>#<?= (int)$e['empresaId'] ?></td>
          <td><?= htmlspecialchars($e['nombreEmpresa']) ?></td>
          <td><?= htmlspecialchars($e['NIT']) ?></td>
          <td><?= htmlspecialchars($e['correo']) ?></td>
          <td><?= htmlspecialchars($e['usuario']) ?></td>
          <td><?= htmlspecialchars((string)$e['fechaCreado']) ?></td>
          <td>
            <form method="post" action="empresa_aprobar.php" class="actions">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$e['empresaId'] ?>">
              <input type="number" name="comision" step="0.01" min="0" max="100" placeholder="%" required>
              <button class="btn btn-approve" type="submit"><i class="fa fa-check"></i> Aprobar</button>
            </form>
          </td>
          <td>
            <form method="post" action="empresa_rechazar.php" class="actions">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$e['empresaId'] ?>">
              <input type="text" name="motivo" placeholder="Motivo (opcional)" style="width:160px;">
              <button class="btn btn-reject" type="submit"><i class="fa fa-xmark"></i> Rechazar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
