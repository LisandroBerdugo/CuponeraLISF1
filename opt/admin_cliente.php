<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

// Contadores básicos
$totalClientes   = 0;
$clientesActivos = 0;

try {
    $stmt = $conn->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) AS activos
        FROM clientes
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalClientes   = (int)$row['total'];
        $clientesActivos = (int)$row['activos'];
    }
} catch (PDOException $e) {
    
    die('Error al contar clientes: ' . $e->getMessage());
}

// Listar últimos clientes
$clientes = [];
try {
    $stmt = $conn->query("
        SELECT clienteId, nombre, apellido, correo, dui, estado, FechaCreado
        FROM clientes
        ORDER BY FechaCreado DESC
        LIMIT 20
    ");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error al obtener clientes: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 
    <link rel="stylesheet" href="../css/admin-dashboard.css">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:#f9fafb;
            margin:0;
            padding:0;
        }
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }
        h1 {
            font-size: 26px;
            margin-bottom: 6px;
            color:#111827;
        }
        .subtitle {
            color:#6b7280;
            font-size:14px;
            margin-bottom:20px;
        }
        .stats {
            display:grid;
            grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
            gap:16px;
            margin-bottom:24px;
        }
        .stat-card {
            background:#fff;
            border-radius:16px;
            padding:14px 16px;
            box-shadow:0 10px 25px rgba(0,0,0,0.03);
        }
        .stat-label {
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:#9ca3af;
            margin-bottom:4px;
        }
        .stat-value {
            font-size:22px;
            font-weight:700;
        }
        .card-table {
            background:#fff;
            border-radius:16px;
            padding:16px 18px;
            box-shadow:0 12px 30px rgba(0,0,0,0.03);
            overflow-x:auto;
        }
        table {
            width:100%;
            border-collapse:collapse;
            font-size:14px;
        }
        th, td {
            padding:8px 6px;
            border-bottom:1px solid #f3f4f6;
            text-align:left;
            white-space:nowrap;
        }
        th {
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:#9ca3af;
        }
        tbody tr:hover {
            background:#f9fafb;
        }
        .badge {
            border-radius:999px;
            padding:3px 9px;
            font-size:11px;
            font-weight:600;
        }
        .badge-activo {
            background:rgba(16,185,129,0.08);
            color:#059669;
        }
        .badge-pendiente {
            background:rgba(245,158,11,0.08);
            color:#b45309;
        }
        .badge-desactivo {
            background:rgba(239,68,68,0.08);
            color:#b91c1c;
        }
    </style>
</head>
<body>
<div class="page-container">
    <h1>Clientes</h1>
    <p class="subtitle">Panel de gestión de clientes de Tickets LIS UDB.</p>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Clientes totales</div>
            <div class="stat-value"><?php echo $totalClientes; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Clientes activos</div>
            <div class="stat-value"><?php echo $clientesActivos; ?></div>
        </div>
    </div>

    <div class="card-table">
        <h2 style="margin:0 0 10px;font-size:18px;">Listado de clientes</h2>

        <?php if (empty($clientes)): ?>
            <p style="color:#6b7280;font-size:14px;">No hay clientes registrados.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>DUI</th>
                        <th>Estado</th>
                        <th>Fecha creado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td><?php echo (int)$c['clienteId']; ?></td>
                            <td><?php echo htmlspecialchars($c['nombre'].' '.$c['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($c['correo']); ?></td>
                            <td><?php echo htmlspecialchars($c['dui']); ?></td>
                            <td>
                                <?php
                                $estado = $c['estado'];
                                $clase  = 'badge-pendiente';
                                if ($estado === 'activo')    $clase = 'badge-activo';
                                if ($estado === 'desactivo') $clase = 'badge-desactivo';
                                ?>
                                <span class="badge <?php echo $clase; ?>">
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($c['FechaCreado']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
