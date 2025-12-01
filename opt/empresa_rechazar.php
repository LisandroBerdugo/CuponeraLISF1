<?php
declare(strict_types=1);

require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_empresas_pendientes.php');
    exit;
}

// Validar CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    die('CSRF inválido');
}

$id     = (int)($_POST['id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');

if ($id <= 0) {
    http_response_code(400);
    die('ID inválido');
}

try {
    /* =====================================================
     * 1. OBTENER NOMBRE DE LA EMPRESA Y USUARIO ASOCIADO (PARA POPUP)
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
     * 2. ACTUALIZAR ESTADO A RECHAZADA
     * ===================================================== */
    $sql = "UPDATE empresas
            SET estado         = 'rechazada',
                motivo_rechazo = :motivo,
                rechazado_por  = :admin,
                rechazado_en   = NOW()
            WHERE empresaId = :id
              AND estado    = 'pendiente'";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':motivo' => $motivo !== '' ? $motivo : null,
        ':admin'  => (int)($_SESSION['admin_id'] ?? 0),
        ':id'     => $id,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error al rechazar la empresa: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Empresa rechazada</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
    // Datos desde PHP
    const empresa = <?php echo json_encode($nombreEmpresa,  JSON_UNESCAPED_UNICODE); ?>;
    const usuario = <?php echo json_encode($usuarioEmpresa, JSON_UNESCAPED_UNICODE); ?>;
    const motivo  = <?php echo json_encode($motivo,         JSON_UNESCAPED_UNICODE); ?>;

    let htmlTexto = `
        <b>Empresa:</b> ${empresa}<br>
        <b>Usuario:</b> ${usuario}<br>
    `;

    if (motivo) {
        htmlTexto += `<b>Motivo:</b> ${motivo}<br><br>`;
    } else {
        htmlTexto += `<br>`;
    }
    htmlTexto += `La empresa ha sido rechazada.`;

    Swal.fire({
        icon: 'info',
        title: 'Empresa rechazada',
        html: htmlTexto,
        confirmButtonText: 'Continuar'
    }).then(() => {
        window.location.href = 'admin_empresas_pendientes.php';
    });
</script>
</body>
</html>
