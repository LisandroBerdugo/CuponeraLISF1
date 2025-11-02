<?php
require_once 'conexion.php';
session_start();

$token = $_GET['token'] ?? '';
$error = '';
$mensaje = '';

if (!$token) {
    die("Token inválido o inexistente.");
}

// Verificar token
$stmt = $conn->prepare("
    SELECT tr.clienteId, tr.expira, c.usuario 
    FROM tokens_recuperacion tr 
    INNER JOIN clientes c ON tr.clienteId = c.clienteId 
    WHERE tr.token = :token
");
$stmt->execute([':token' => $token]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die("Token inválido.");
}

// Verificar expiración
if (strtotime($datos['expira']) < time()) {
    die("El enlace ha expirado. Solicita uno nuevo.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva = $_POST['nueva'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (empty($nueva) || empty($confirmar)) {
        $error = "Debes completar ambos campos.";
    } elseif ($nueva !== $confirmar) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Actualizar contraseña
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $conn->prepare("UPDATE clientes SET contraseña = :pass WHERE clienteId = :id")
             ->execute([':pass' => $hash, ':id' => $datos['clienteId']]);

        // Eliminar token
        $conn->prepare("DELETE FROM tokens_recuperacion WHERE token = :token")->execute([':token' => $token]);

        $mensaje = "Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">
<div class="registro-container">
    <div class="registro-header">
        <h2>Restablecer Contraseña</h2>
        <p>Ingresa tu nueva contraseña para el usuario <b><?php echo htmlspecialchars($datos['usuario']); ?></b></p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?php echo htmlspecialchars($mensaje); ?></div>
        <div style="text-align:center; margin-top:10px;">
            <a href="login_cliente.php" class="btn-submit">Iniciar Sesión</a>
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
