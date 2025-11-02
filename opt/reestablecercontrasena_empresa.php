<?php
session_start();
require_once __DIR__ . '/conexion.php';

$token = $_GET['token'] ?? '';
$error = '';
$mensaje = '';

if (!$token) {
    die("Token inválido o inexistente.");
}

// Buscar token y empresa
$sel = $conn->prepare("
    SELECT tre.empresaId, tre.expira, e.usuario
    FROM tokens_recuperacion_empresas tre
    INNER JOIN empresas e ON e.empresaId = tre.empresaId
    WHERE tre.token = :token
");
$sel->execute([':token' => $token]);
$data = $sel->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Token inválido.");
}
if (strtotime($data['expira']) < time()) {
    die("El enlace ha expirado. Solicita uno nuevo.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva     = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nueva === '' || $confirmar === '') {
        $error = "Debes completar ambos campos.";
    } elseif ($nueva !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE empresas SET password = :p WHERE empresaId = :id");
            $up->execute([':p' => $hash, ':id' => $data['empresaId']]);

            // eliminar token para que no se reutilice
            $del = $conn->prepare("DELETE FROM tokens_recuperacion_empresas WHERE token = :t");
            $del->execute([':t' => $token]);

            $mensaje = "Tu contraseña ha sido restablecida correctamente.";
        } catch (PDOException $e) {
            $error = "Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña - Empresa</title>
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">
<div class="registro-container">
    <div class="registro-header">
        <h2>Restablecer Contraseña - Empresa</h2>
        <p>Usuario: <b><?php echo htmlspecialchars($data['usuario']); ?></b></p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?php echo htmlspecialchars($mensaje); ?></div>
        <div style="text-align:center; margin-top:10px;">
            <a class="btn-submit" href="login_empresa.php">Iniciar sesión</a>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="nueva">Nueva contraseña *</label>
                <input type="password" id="nueva" name="nueva" required>
            </div>
            <div class="form-group">
                <label for="confirmar">Confirmar contraseña *</label>
                <input type="password" id="confirmar" name="confirmar" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-submit">Guardar contraseña</button>
            </div>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
