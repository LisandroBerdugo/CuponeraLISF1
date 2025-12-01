<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

//  POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_empresas_pendientes.php');
    exit;
}

//  CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    die('CSRF inválido');
}

// Datos recibidos
$id       = (int)($_POST['id'] ?? 0);
$comision = isset($_POST['comision']) ? (float)$_POST['comision'] : -1.0;

if ($id <= 0 || $comision < 0) {
    http_response_code(400);
    die('Datos inválidos');
}

try {

    /* =====================================================
     * 1. OBTENER NOMBRE DE LA EMPRESA Y USUARIO ASOCIADO
     * ===================================================== */
    $sqlInfo = "SELECT nombreEmpresa, usuario
                FROM empresas
                WHERE empresaId = :id
                LIMIT 1";

    $stmtInfo = $conn->prepare($sqlInfo);
    $stmtInfo->execute([':id' => $id]);
    $empresa = $stmtInfo->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        die("No se encontró la empresa.");
    }

    $nombreEmpresa  = $empresa['nombreEmpresa'];
    $usuarioEmpresa = $empresa['usuario'];


    /* =====================================================
     * 2. ACTUALIZAR ESTADO EMPRESA A APROBADA
     * ===================================================== */
    $sql = "UPDATE empresas
            SET estado       = 'aprobada',
                comision     = :comision,
                aprobado_por = :admin,
                aprobado_en  = NOW()
            WHERE empresaId = :id
              AND estado    = 'pendiente'";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':comision' => $comision,
        ':admin'    => (int)($_SESSION['admin_id'] ?? 0),
        ':id'       => $id,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error al aprobar la empresa: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empresa aprobada</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    // Datos enviados desde PHP
    const empresa = <?php echo json_encode($nombreEmpresa, JSON_UNESCAPED_UNICODE); ?>;
    const usuario = <?php echo json_encode($usuarioEmpresa, JSON_UNESCAPED_UNICODE); ?>;

    Swal.fire({
        icon: 'success',
        title: '¡Empresa aprobada!',
        html: `
            <b>Empresa:</b> ${empresa}<br>
            <b>Usuario:</b> ${usuario}<br><br>
            Ha sido aprobada correctamente.
        `,
        confirmButtonText: 'Continuar'
    }).then(() => {
        window.location.href = 'admin_empresas_pendientes.php';
    });
</script>
</body>
</html>
