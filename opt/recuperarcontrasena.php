<?php
session_start();
require_once 'conexion.php';

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);

    if (!empty($correo)) {
        try {
            // Verificar si el correo existe en la base de datos
            $stmt = $conn->prepare("SELECT clienteId, nombre FROM clientes WHERE correo = :correo");
            $stmt->execute([':correo' => $correo]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                // Crear token aleatorio
                $token = bin2hex(random_bytes(50));
                $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // Guardar token en tabla (puedes crear una tabla 'tokens_recuperacion')
                $conn->prepare("
                    INSERT INTO tokens_recuperacion (clienteId, token, expira)
                    VALUES (:clienteId, :token, :expira)
                ")->execute([
                    ':clienteId' => $cliente['clienteId'],
                    ':token' => $token,
                    ':expira' => $expira
                ]);

                // Generar enlace
                $enlace = "http://localhost/proyecto/recuperar/restablecer_contrasena.php?token=$token";

                // Enviar correo 
                // mail($correo, "Recuperación de contraseña", "Haz clic aquí: $enlace");

                $mensaje = "Se ha enviado un enlace de recuperación a tu correo electrónico.";
            } else {
                $error = "No existe ninguna cuenta asociada a ese correo.";
            }
        } catch (PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }
    } else {
        $error = "Por favor, ingresa tu correo electrónico.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">
<div class="registro-container">
    <div class="registro-header">
        <h2>Recuperar Contraseña</h2>
        <p>Ingresa tu correo electrónico para recuperar tu cuenta</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php elseif ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="correo">Correo electrónico *</label>
            <input type="email" id="correo" name="correo" required placeholder="tu@correo.com">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Enviar enlace</button>
            <button type="button" class="btn-secondary" onclick="location.href='login_cliente.php'">Volver</button>
        </div>
    </form>
</div>
</body>
</html>
