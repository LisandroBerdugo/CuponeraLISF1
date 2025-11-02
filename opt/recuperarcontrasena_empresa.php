<?php
session_start();
require_once __DIR__ . '/conexion.php';

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');

    if ($correo === '') {
        $error = "Por favor, ingresa tu correo electrónico.";
    } else {
        try {
            // Buscar empresa por correo
            $stmt = $conn->prepare("SELECT empresaId, nombreEmpresa FROM empresas WHERE correo = :correo");
            $stmt->execute([':correo' => $correo]);
            $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($empresa) {
                // Crear token
                $token  = bin2hex(random_bytes(50));
                $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // Guardar token
                $ins = $conn->prepare("
                    INSERT INTO tokens_recuperacion_empresas (empresaId, token, expira)
                    VALUES (:empresaId, :token, :expira)
                ");
                $ins->execute([
                    ':empresaId' => $empresa['empresaId'],
                    ':token'     => $token,
                    ':expira'    => $expira
                ]);

                // Construir enlace absoluto robusto
                $base = sprintf(
                    '%s://%s%s',
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
                    $_SERVER['HTTP_HOST'],
                    rtrim(dirname($_SERVER['PHP_SELF']), '/\\')
                );
                $enlace = $base . '/restablecercontrasena_empresa.php?token=' . urlencode($token);

               
                // mail($correo, "Recuperación de contraseña", "Haz clic en: $enlace");

                $mensaje = "Se ha enviado un enlace de recuperación a tu correo";
            } else {
                $error = "No existe ninguna empresa asociada a ese correo.";
            }
        } catch (PDOException $e) {
            $error = "Error en la base de datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - Empresa</title>
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">
<div class="registro-container">
    <div class="registro-header">
        <h2>Recuperar Contraseña - Empresa</h2>
        <p>Ingresa el correo registrado de tu empresa para recibir el enlace</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert success"><?php echo $mensaje; ?></div>
    <?php elseif ($error): ?>
        <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="correo">Correo electrónico *</label>
            <input type="email" id="correo" name="correo" required placeholder="empresa@dominio.com">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-submit">Enviar enlace</button>
            <button type="button" class="btn-secondary" onclick="location.href='login_empresa.php'">Volver</button>
        </div>
    </form>
</div>
</body>
</html>
