<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard_admin.php';

/* Cargar conexión */
$conexionIncluida = false;
foreach ([__DIR__.'/conexion.php', __DIR__.'/../conexion.php'] as $p) {
    if (is_file($p)) { require_once $p; $conexionIncluida = true; break; }
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=cuponera;charset=utf8mb4',
        'root','',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}

/* Consultar empresas pendientes */
$stmt = $pdo->prepare("
  SELECT empresaId, nombreEmpresa, NIT, correo, usuario, estado, fechaCreado
  FROM empresas
  WHERE estado = 'pendiente'
  ORDER BY fechaCreado DESC
");
$stmt->execute();
$pendientes = $stmt->fetchAll() ?: [];

$csrf = $_SESSION['csrf_token'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Empresas pendientes - Admin</title>

  <link rel="stylesheet" href="../css/admin.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

<!-- HEADER PRINCIPAL -->
<header class="admin-header">
  <div class="admin-header-inner">

    <a href="dashboard_admin.php" class="admin-logo-wrap">
      <img src="../img/logo.jpg" class="admin-logo">
      
    </a>

    <nav class="admin-nav">
      <a href="dashboard_admin.php" class="admin-nav-link"> <i class="fa-solid fa-gauge"></i> Dashboard </a>
     
      <a href="admin_empresas_pendientes.php" class="admin-nav-link active"><i class="fa-solid fa-hourglass-half"></i> Empresas pendientes</a>
      <a href="admin.php" class="admin-nav-link" style="background:black;color:white;border-radius:8px;">
        <i class="fa-solid fa-user-plus"></i> Registrar Admin
        
      </a>
         <button type="submit" class="logout-btn" title="Cerrar sesión">
          <i class="fa-solid fa-right-from-bracket"></i><span>Salir</span>
        </button>
    </nav>

  </div>
</header>

<!-- CONTENIDO PRINCIPAL -->
<div class="admin-container">


    <div class="pill-tabs">
        <a href="admin_empresas_pendientes.php" class="pill-tab pill-tab--active">
            <i class="fa-solid fa-hourglass-half"></i> Empresas pendientes
        </a>
       
    </div>

    <!-- Card principal -->
    <div class="card">
        <h2 class="page-title">Empresas pendientes de aprobación</h2>
        <p class="page-desc">Revisa y aprueba las solicitudes enviadas por los comercios para operar dentro del sistema Tickets LIS UDB.</p>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>NIT</th>
                    <th>Correo</th>
                    <th>Usuario</th>
                    <th>Creado</th>
                    <th>Aprobar (comisión %)</th>
                    <th>Rechazar</th>
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
                    <td><?= htmlspecialchars($e['fechaCreado']) ?></td>

                    <td>
                        <form action="empresa_aprobar.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int)$e['empresaId'] ?>">
                            <input type="number" class="input-number" name="comision" step="0.01" min="0" max="100" required>
                            <button class="btn btn-approve"><i class="fa fa-check"></i> Aprobar</button>
                        </form>
                    </td>

                    <td>
                        <form action="empresa_rechazar.php" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <input type="hidden" name="id" value="<?= (int)$e['empresaId'] ?>">
                            <input type="text" class="input-text" name="motivo" placeholder="Motivo (opcional)">
                            <button class="btn btn-reject"><i class="fa fa-xmark"></i> Rechazar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>

        </table>
    </div>
     <footer style="background:#111;color:#fff;padding:10px 10px;margin-top:50px;text-align:center;">
    <p>&copy; 2025 TICKETS LIS UDB - Todos los derechos reservados</p>
  </footer>

</div>

</body>
</html>
