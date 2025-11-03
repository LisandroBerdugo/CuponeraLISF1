<?php
// opt/dashboard_admin.php
declare(strict_types=1);

// 1) Protege la ruta 
require_once __DIR__ . '/_guard_admin.php';

// 2) Asegura conexión PDO en $pdo
$conexionIncluida = false;
$paths = [
    __DIR__ . '/conexion.php',   
    __DIR__ . '/../conexion.php', 
];
foreach ($paths as $p) {
    if (is_file($p)) {
        require_once $p;
        $conexionIncluida = true;
        break;
    }
}


//    crea un PDO local apuntando a la BD
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=cuponera;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        die('No se pudo crear conexión PDO: ' . $e->getMessage());
    }
}

// ---------- Flags/vars de sesión ----------
$adminNombre = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
$adminCorreo = $_SESSION['correo'] ?? '';
$csrf_token  = $_SESSION['csrf_token'] ?? '';

// ---------- Cerrar sesión (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die('Token CSRF inválido.');
    }
    session_unset();
    session_destroy();
    header('Location: login_admin.php');
    exit;
}

// ---------- Helpers ----------
function fetchCount(PDO $pdo, string $sql): int {
    try {
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function fetchCountTry(PDO $pdo, array $queries): int {
    foreach ($queries as $q) {
        try {
            $stmt = $pdo->query($q);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // intenta el siguiente
        }
    }
    return 0;
}

function fetchRows(PDO $pdo, string $sql, array $params = [], int $limit = 8): array {
    $sqlFinal = preg_match('/\blimit\s+\d+\b/i', $sql) ? $sql : ($sql . ' LIMIT ' . (int)$limit);
    try {
        $stmt = $pdo->prepare($sqlFinal);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

// ---------- KPIs (con tolerancia a nombres de tabla) ----------
$kpiAdmins    = fetchCountTry($pdo, [
    "SELECT COUNT(*) FROM admincuentas",
    "SELECT COUNT(*) FROM admins",
]);

$kpiClientes  = fetchCountTry($pdo, [
    "SELECT COUNT(*) FROM clientes",
    "SELECT COUNT(*) FROM users",
]);

// comercios vs empresas
$kpiComercios = fetchCountTry($pdo, [
    "SELECT COUNT(*) FROM comercios",
    "SELECT COUNT(*) FROM empresas",
]);

$kpiCupones   = fetchCountTry($pdo, [
    "SELECT COUNT(*) FROM cupones",
]);

$kpiOrdenes    = fetchCountTry($pdo, [
    "SELECT COUNT(*) FROM transacciones"
]);

$kpiVentas = 0.0;
try {
  $kpiVentas = (float)$pdo->query("SELECT COALESCE(SUM(TotalPagar),0) FROM transacciones")->fetchColumn();
} catch (Throwable $e) { $kpiVentas = 0.0; }


// ---------- Recientes ----------
$ultimosCupones = fetchRows(
    $pdo,
    "SELECT id, 
            COALESCE(titulo, nombre, CONCAT('Cupón ', id)) AS titulo,
            COALESCE(estado, '—') AS estado,
            COALESCE(actualizado_en, creado_en, FechaCreado, NOW()) AS fecha
     FROM cupones
     ORDER BY COALESCE(actualizado_en, creado_en, FechaCreado, NOW()) DESC",
    [],
    8
);

// Órdenes/compras recientes si existe la tabla
$ultimasOrdenes = [];
try {
   $ultimasOrdenes = fetchRows(
    $pdo,
   
    "SELECT 
        transaccionId AS id,
        clienteld    AS cliente_id,   
        TotalPagar   AS total,
        estado,
        FechaCreado  AS fecha
     FROM transacciones
     ORDER BY FechaCreado DESC",
    [],
    8
);

} catch (Throwable $e) {
    // intenta con "compras"
    $ultimasOrdenes = fetchRows(
        $pdo,
        "SELECT id, 
                COALESCE(cliente_id, clienteld, cliente, 0) AS cliente_id,
                COALESCE(total, monto, 0) AS total,
                COALESCE(estado, '—') AS estado,
                COALESCE(creado_en, FechaCreado, NOW()) AS fecha
         FROM compras
         ORDER BY COALESCE(creado_en, FechaCreado, NOW()) DESC",
        [],
        8
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel del Administrador</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    .panel-wrap { max-width: 1100px; margin: 40px auto; padding: 0 16px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); padding:24px; }
    .grid { display:grid; gap:16px; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); }
    .kpi { background:#f9fafb; border:1px solid #eef2f7; border-radius:12px; padding:16px; }
    .kpi h4 { margin:0 0 6px; font-size:14px; color:#6b7280; }
    .kpi div { font-size:24px; font-weight:800; color:#111827; }
    .section-title { margin:20px 0 10px; font-weight:800; color:#111827; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; }
    th, td { padding:12px 14px; border-bottom:1px solid #eef2f7; font-size:14px; }
    th { text-align:left; background:#f9fafb; color:#374151; font-weight:700; }
    tr:last-child td { border-bottom:none; }
    .actions a { text-decoration:none; color:#374151; margin-right:12px; }
    .actions a:hover { text-decoration:underline; }
    .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #e5e7eb; background:#f9fafb; }
    .logout-btn { display:inline-flex; align-items:center; gap:8px; border:none; background:#ef4444; color:#fff; padding:8px 12px; border-radius:8px; cursor:pointer; }
    .logout-btn:hover { filter:brightness(.95); }
    #header { display:flex; align-items:center; justify-content:space-between; padding:10px 20px; background:#fff; border-bottom:1px solid #e5e7eb; }
    #navbar { display:flex; gap:16px; list-style:none; }
    #navbar li a { text-decoration:none; color:#111827; font-weight:600; padding:6px 10px; border-radius:8px; }
    #navbar li a.active, #navbar li a:hover { background:#f3f4f6; }
    .logo { height:46px; width:auto; border-radius:8px; }
    .footer { background:#f9fafb; color:#333; padding:40px 20px 20px; margin-top:40px; border-top:1px solid #e5e7eb; }
    .footer-container { display:flex; flex-wrap:wrap; justify-content:space-between; max-width:1100px; margin:0 auto; }
    .footer-logo { flex:1 1 200px; text-align:center; }
    .footer-logo img { width:80px; border-radius:50%; margin-bottom:10px; }
    .footer-info { flex:1 1 300px; font-size:14px; line-height:1.6; }
    .footer-social { flex:1 1 200px; text-align:center; }
    .footer-social ul { list-style:none; padding:0; display:flex; justify-content:center; gap:15px; }
    .footer-social ul li a { font-size:20px; color:#333; transition:color .3s; }
    .footer-social ul li a:hover { color:#4f46e5; }
    .footer-bottom { text-align:center; margin-top:20px; font-size:13px; color:#666; border-top:1px solid #ddd; padding-top:15px; }
  </style>
</head>
<body>

  <!-- Header -->
<section id="header">
  <a href="../opt/index.php"><img src="../img/logo.jpg" class="logo" alt="Logo"></a>
  <div>
    <ul id="navbar">
      <!-- Público -->
      <li><a href="../opt/index.php">Inicio</a></li>
      <li><a href="../opt/shop.html">Tienda</a></li>
      <li><a href="../opt/contact.html">Contacto</a></li>

      <!-- Admin (empujado a la derecha con .push) -->
      <li class="push"><a class="active" href="dashboard_admin.php"><i class="fa-solid fa-gauge"></i> Admin</a></li>
      <li><a href="admin_comercios.php"><i class="fa-solid fa-store"></i> Comercios</a></li>
      <li><a href="admin_clientes.php"><i class="fa-solid fa-users"></i> Clientes</a></li>
      <li><a href="admin_empresas_pendientes.php"><i class="fa-solid fa-hourglass-half"></i> Empresas pendientes</a></li>
      <li class="btn-accent"><a href="admin.php"><i class="fa fa-user-plus"></i> Registrar Admin</a></li>
    </ul>
  </div>
</section>


  <!-- Cerrar sesión -->
  <li>
    <form method="post" action="dashboard_admin.php" style="margin:0;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="action" value="logout">
      <button type="submit" class="logout-btn" title="Cerrar sesión">
        <i class="fa-solid fa-right-from-bracket"></i><span>Salir</span>
      </button>
    </form>
  </li>
</ul>

    </div>
  </section>

  <div class="panel-wrap">
    <div class="card">
      <h2 style="color:#111827; font-weight:800; margin:0 0 8px;">Hola, <?= htmlspecialchars($adminNombre ?: 'Administrador') ?></h2>
      <?php if ($adminCorreo): ?>
        <p>Correo: <?= htmlspecialchars($adminCorreo) ?></p>
      <?php endif; ?>

      <div class="grid" style="margin-top:16px;">
        <div class="kpi"><h4>Administradores</h4><div><?= number_format($kpiAdmins) ?></div></div>
        <div class="kpi"><h4>Clientes</h4><div><?= number_format($kpiClientes) ?></div></div>
        <div class="kpi"><h4>Comercios</h4><div><?= number_format($kpiComercios) ?></div></div>
        <div class="kpi"><h4>Cupones</h4><div><?= number_format($kpiCupones) ?></div></div>
        <div class="kpi"><h4>Órdenes</h4><div><?= number_format($kpiOrdenes) ?></div></div>
        <div class="kpi"><h4>Ventas</h4><div>$<?= number_format($kpiVentas, 2) ?></div></div>

      </div>

      <div class="actions" style="margin:16px 0 8px;">
        <a href="crear_cupon.php"><i class="fa-solid fa-plus"></i> Crear cupón</a>
        <a href="admin_cupones.php"><i class="fa-solid fa-table"></i> Ver cupones</a>
        <a href="admin_ordenes.php"><i class="fa-solid fa-list-check"></i> Ver órdenes</a>
      </div>
    </div>

    <div class="grid" style="margin-top:16px;">
      <div class="card">
        <h3 class="section-title">Últimos cupones</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Título</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$ultimosCupones): ?>
            <tr><td colspan="5">Sin registros.</td></tr>
          <?php else: ?>
            <?php foreach ($ultimosCupones as $c): ?>
              <tr>
                <td>#<?= htmlspecialchars((string)$c['id']) ?></td>
                <td><?= htmlspecialchars((string)$c['titulo']) ?></td>
                <td><span class="badge"><?= htmlspecialchars((string)$c['estado']) ?></span></td>
                <td><?= htmlspecialchars((string)$c['fecha']) ?></td>
                <td>
                  <a href="editar_cupon.php?id=<?= urlencode((string)$c['id']) ?>">Editar</a>
                  <a href="ver_cupon.php?id=<?= urlencode((string)$c['id']) ?>">Ver</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="card">
        <h3 class="section-title">Órdenes recientes</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Estado</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$ultimasOrdenes): ?>
            <tr><td colspan="5">Sin registros.</td></tr>
          <?php else: ?>
            <?php foreach ($ultimasOrdenes as $o): ?>
              <tr>
                <td>#<?= htmlspecialchars((string)$o['id']) ?></td>
                <td><?= htmlspecialchars((string)$o['cliente_id']) ?></td>
                <td>$<?= number_format((float)$o['total'], 2) ?></td>
                <td><span class="badge"><?= htmlspecialchars((string)$o['estado']) ?></span></td>
                <td><?= htmlspecialchars((string)$o['fecha']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-logo">
        <img src="../img/logo.jpg" alt="Tickets LIS UDB">
        <h3>Tickets LIS UDB</h3>
      </div>
      <div class="footer-info">
        <p>Dirección: Colonial Escalón, 1ra calle poniente #513</p>
        <p>San Salvador, El Salvador</p>
        <p>Teléfono: <a href="tel:+50361612222">503 6161 2222</a></p>
        <p>Whatsapp: <a href="https://wa.me/5036162223" target="_blank" rel="noopener">503 6162 2223</a></p>
      </div>
      <div class="footer-social">
        <h4>Síguenos</h4>
        <ul>
          <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-whatsapp"></i></a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 TICKETS LIS UDB SV - Todos los derechos reservados</p>
    </div>
  </footer>

</body>
</html>
