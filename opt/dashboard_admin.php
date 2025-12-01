<?php
// opt/dashboard_admin.php
declare(strict_types=1);

require_once __DIR__ . '/_guard_admin.php';

/* ============================
   CONEXIÓN PDO SEGURA
============================ */
$paths = [
    __DIR__ . '/conexion.php',
    __DIR__ . '/../conexion.php',
];
foreach ($paths as $p) {
    if (is_file($p)) {
        require_once $p;
        break;
    }
}

if (!isset($pdo)) {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;dbname=cuponera;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ============================
   CSRF TOKEN
============================ */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ============================
   LOGOUT
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    if (hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        session_unset();
        session_destroy();
        header('Location: ../opt/index.php');
        exit;
    }
}

/* ============================
   DATOS DEL ADMIN LOGUEADO
============================ */
$adminNombre = $_SESSION['admin_nombre'] ?? 'Administrador';
$adminCorreo = $_SESSION['admin_correo'] ?? '';

/* ============================
   KPI DEL SISTEMA
============================ */
$kpiAdmins     = (int)$pdo->query("SELECT COUNT(*) FROM admincuentas")->fetchColumn();
$kpiClientes   = (int)$pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$kpiComercios  = (int)$pdo->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
$kpiCupones    = (int)$pdo->query("SELECT COUNT(*) FROM cupones")->fetchColumn();
$kpiOrdenes    = (int)$pdo->query("SELECT COUNT(*) FROM transacciones")->fetchColumn();
$kpiVentas     = (float)$pdo->query("SELECT COALESCE(SUM(TotalPagar),0) FROM transacciones")->fetchColumn();

/* ============================
   REPORTE POR EMPRESA
============================ */
$sql = "
    SELECT 
        e.empresaId,
        e.nombreEmpresa AS empresa,
        e.comision,

        -- cupones vendidos
        COALESCE(SUM(t.cantidad), 0) AS total_cupones_vendidos,

        -- ventas totales
        COALESCE(SUM(t.TotalPagar), 0) AS total_ventas,

        -- ganancia para empresa
        COALESCE(SUM(t.TotalPagar), 0) * (e.comision / 100) AS total_ganancia_empresa

    FROM empresas e
    LEFT JOIN cupones c 
        ON c.empresaId = e.empresaId
    LEFT JOIN transacciones t 
        ON t.cuponId = c.cuponId

    -- ⬇⬇ SOLO EMPRESAS APROBADAS
    WHERE e.estado = 'aprobada'

    GROUP BY 
        e.empresaId,
        e.nombreEmpresa,
        e.comision
    ORDER BY 
        total_ventas DESC
";

$reportesEmpresas = $pdo->query($sql)->fetchAll();
$totalGananciasGlobal = 0;

foreach ($reportesEmpresas as $row) {
    $totalGananciasGlobal += (float)$row['total_ganancia_empresa'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Administrador</title>

    <!-- CSS -->
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- ===============================
        HEADER
================================ -->
<header class="admin-header">
    <div class="admin-header-inner">

        <a href="dashboard_admin.php" class="admin-logo-wrap">
            <img src="../img/logo.jpg" class="admin-logo">
        </a>

        <nav class="admin-nav">
            <a class="admin-nav-link active" href="dashboard_admin.php">
                <i class="fa-solid fa-gauge"></i> Dashboard
            </a>

            <a class="admin-nav-link" href="admin_empresas_pendientes.php">
                <i class="fa-solid fa-hourglass-half"></i> Empresas pendientes
            </a>

            <a class="admin-nav-link" href="admin.php" style="background:black;color:white;border-radius:8px;">
                <i class="fa-solid fa-user-plus"></i> Registrar Admin
            </a>

            <form method="post" class="logout-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="logout">
                <button class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Salir</button>
            </form>
        </nav>
    </div>
</header>

<!-- ===============================
        CONTENIDO PRINCIPAL
================================ -->
<div class="admin-container">

    <!-- Tarjeta de bienvenida -->
    <div class="dashboard-card">
        <h2 class="dashboard-title">Hola, <?= htmlspecialchars($adminNombre) ?></h2>

        <?php if ($adminCorreo): ?>
            <p class="dashboard-sub">Correo: <?= htmlspecialchars($adminCorreo) ?></p>
        <?php endif; ?>

        <p class="dashboard-sub">Desde este panel puedes gestionar administradores, clientes, comercios, cupones y transacciones.</p>

        <!-- KPI CARDS -->
        <div class="kpi-grid">

            <div class="kpi-box">
                <i class="fa-solid fa-user-shield kpi-icon"></i>
                <div class="kpi-label">Administradores</div>
                <div class="kpi-value"><?= $kpiAdmins ?></div>
            </div>

            <div class="kpi-box">
                <i class="fa-solid fa-users kpi-icon"></i>
                <div class="kpi-label">Clientes</div>
                <div class="kpi-value"><?= $kpiClientes ?></div>
            </div>

            <div class="kpi-box">
                <i class="fa-solid fa-building kpi-icon"></i>
                <div class="kpi-label">Empresas</div>
                <div class="kpi-value"><?= $kpiComercios ?></div>
            </div>

            <div class="kpi-box">
                <i class="fa-solid fa-ticket kpi-icon"></i>
                <div class="kpi-label">Cupones</div>
                <div class="kpi-value"><?= $kpiCupones ?></div>
            </div>

            <div class="kpi-box">
                <i class="fa-solid fa-money-check-dollar kpi-icon"></i>
                <div class="kpi-label">Transacciones</div>
                <div class="kpi-value"><?= $kpiOrdenes ?></div>
            </div>


        </div>
    </div>

    <!-- ============================
        REPORTES POR EMPRESA
    ============================= -->
    <div class="dashboard-card">
        <h3 class="section-title">Reportes por empresa</h3>
        <p class="section-sub">Ventas, cupones vendidos y ganancias según la comisión asignada.</p>

        <div class="total-global-box">
            Ventas Totales: $<?= number_format($totalGananciasGlobal, 2) ?>
        </div>

        <div style="overflow-x:auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Empresa</th>
                        <th>Cupones vendidos</th>
                        <th>Total ventas</th>
                        <th>Comisión</th>
                        <th>Ganancia empresa</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($reportesEmpresas): $i = 1; ?>
                    <?php foreach ($reportesEmpresas as $row): ?>
                        <?php
                        $cupones = (int)$row['total_cupones_vendidos'];
                        $ventas  = (float)$row['total_ventas'];
                        $comision = (float)$row['comision'];
                        $ganancia = (float)$row['total_ganancia_empresa'];

                        
                        $percent = min(100, $ventas * 5);
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['empresa']) ?></td>
                            <td><?= number_format($cupones) ?></td>

                            <td>
                                $<?= number_format($ventas, 2) ?>
                                <div class="progress-bar">
                                    <div class="progress-fill <?= $ventas == 0 ? 'zero' : '' ?>" 
                                         style="width: <?= $percent ?>%;">
                                    </div>
                                </div>
                            </td>

                            <td><?= number_format($comision, 2) ?>%</td>
                            <td>$<?= number_format($ganancia, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr><td colspan="6">No hay datos disponibles.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer style="background:#111;color:#fff;padding:12px;margin-top:40px;text-align:center;border-radius:12px;">
        <p>&copy; 2025 TICKETS LIS UDB - Todos los derechos reservados</p>
    </footer>

</div>

</body>
</html>
