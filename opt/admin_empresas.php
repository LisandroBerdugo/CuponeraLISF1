<?php
declare(strict_types=1);


error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

// ===============================
// 1) ESTADÍSTICAS
// ===============================
$totalEmpresas     = 0;
$empresasAprobadas = 0;
$empresasPendientes = 0;

try {
    $stmt = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estado = 'aprobada'  THEN 1 ELSE 0 END) AS aprobadas,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes
        FROM empresas
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalEmpresas      = (int)$row['total'];
        $empresasAprobadas  = (int)$row['aprobadas'];
        $empresasPendientes = (int)$row['pendientes'];
    }
} catch (PDOException $e) {
    die('Error al contar empresas: ' . $e->getMessage());
}

// ===============================
// 2) LISTADO DE EMPRESAS (APROBADAS Y RECHAZADAS)
// ===============================
$empresas = [];
try {
    $stmt = $conn->query("
        SELECT empresaId, nombreEmpresa, NIT, estado, comision, fechaCreado, aprobado_en
        FROM empresas
        ORDER BY fechaCreado DESC
        LIMIT 20
    ");
    $empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error al obtener empresas: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title> Empresas </title>
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
        .stat-sub {
            font-size:12px;
            color:#9ca3af;
            margin-top:2px;
        }

        .toolbar {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:16px;
            gap:10px;
            flex-wrap:wrap;
        }
        .btn-primary {
            background:#6f42c1;
            color:#fff;
            border-radius:999px;
            padding:8px 18px;
            border:none;
            font-size:14px;
            cursor:pointer;
            box-shadow:0 8px 20px rgba(111,66,193,0.25);
        }
        .btn-primary:hover {
            background:#5a32a2;
        }
        .search-input {
            padding:7px 10px;
            border-radius:999px;
            border:1px solid #e5e7eb;
            font-size:14px;
            min-width:220px;
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
        .badge-aprobada {
            background:rgba(16,185,129,0.08);
            color:#059669;
        }
        .badge-pendiente {
            background:rgba(245,158,11,0.08);
            color:#b45309;
        }
        .badge-rechazada {
            background:rgba(239,68,68,0.08);
            color:#b91c1c;
        }

        .actions {
            display:flex;
            gap:6px;
        }
        .btn-xs {
            font-size:12px;
            padding:4px 9px;
            border-radius:999px;
            border:none;
            cursor:pointer;
        }
        .btn-outline {
            background:#fff;
            border:1px solid #e5e7eb;
            color:#4b5563;
        }
        .btn-outline:hover {
            background:#f3f4f6;
        }

        @media (max-width: 640px) {
            .page-container {
                padding:20px 12px 40px;
            }
            h1 {
                font-size:22px;
            }
        }
    </style>
</head>
<body>
<div class="page-container">
    <h1>Comercios / Empresas</h1>
    <p class="subtitle">Panel de gestión de comercios registrados en Tickets LIS UDB.</p>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-label">Empresas totales</div>
            <div class="stat-value"><?php echo $totalEmpresas; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Empresas aprobadas</div>
            <div class="stat-value"><?php echo $empresasAprobadas; ?></div>
            <div class="stat-sub">Con acceso para publicar cupones</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pendientes de revisión</div>
            <div class="stat-value"><?php echo $empresasPendientes; ?></div>
            <div class="stat-sub">Puedes gestionarlas en “Empresas pendientes”</div>
        </div>
    </div>

    <div class="toolbar">
        <button class="btn-primary" onclick="location.href='registro_empresas.php'">
            + Registrar empresa
        </button>
        
        
    </div>

    <div class="card-table">
        <h2 style="margin:0 0 10px;font-size:18px;">Listado de empresas</h2>

        <?php if (empty($empresas)): ?>
            <p style="color:#6b7280;font-size:14px;">No hay empresas registradas actualmente.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>NIT</th>
                        <th>Estado</th>
                        <th>Comisión</th>
                        <th>Fecha creado</th>
                        <th>Aprobado en</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empresas as $e): ?>
                        <tr>
                            <td><?php echo (int)$e['empresaId']; ?></td>
                            <td><?php echo htmlspecialchars($e['nombreEmpresa']); ?></td>
                            <td><?php echo htmlspecialchars($e['NIT']); ?></td>
                            <td>
                                <?php
                                $estado = $e['estado'];
                                $clase  = 'badge-pendiente';
                                if ($estado === 'aprobada')  $clase = 'badge-aprobada';
                                if ($estado === 'rechazada') $clase = 'badge-rechazada';
                                ?>
                                <span class="badge <?php echo $clase; ?>">
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                echo $e['comision'] !== null
                                    ? number_format((float)$e['comision'], 2) . '%'
                                    : '-';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($e['fechaCreado']); ?></td>
                            <td><?php echo htmlspecialchars($e['aprobado_en'] ?? '—'); ?></td>
                            <td>
                                
                                    
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
