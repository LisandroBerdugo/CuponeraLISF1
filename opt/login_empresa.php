<?php
session_start();
require_once 'conexion.php';

$errors = [];
$usuario = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Error de validación CSRF.";
    } else {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
        if (empty($password)) $errors[] = "La contraseña es obligatoria.";

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("
                    SELECT empresaId, nombreEmpresa, NIT, correo, usuario, password, estado
                    FROM empresas
                    WHERE usuario = :usuario
                ");
                $stmt->execute([':usuario' => $usuario]);
                $empresa = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($empresa && password_verify($password, $empresa['password'])) {
                    if ($empresa['estado'] === 'activo') {
                        $_SESSION['empresa_id'] = $empresa['empresaId'];
                        $_SESSION['nombreEmpresa'] = $empresa['nombreEmpresa'];
                        $_SESSION['NIT'] = $empresa['NIT'];
                        $_SESSION['correo'] = $empresa['correo'];
                        $_SESSION['tipo_usuario'] = 'empresa';

                        session_regenerate_id(true);
                        header('Location: dashboard_empresa.php');
                        exit;
                    } else {
                        $errors[] = "Tu cuenta no está activa. Contacta al administrador.";
                    }
                } else {
                    $errors[] = "Usuario o contraseña incorrectos.";
                }
            } catch (PDOException $e) {
                $errors[] = "Error al iniciar sesión: " . $e->getMessage();
            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Empresa</title>
    <link rel="stylesheet" href="../css/login-choice.css"> <!-- mismo CSS que los demás -->
</head>
<body class="registro-page">

<div class="registro-container">
    <div class="registro-header">
        <h2>Inicio de Sesión - Empresa</h2>
        <p>Accede al panel de tu empresa con tus credenciales</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div class="form-grid">
            <div class="form-group">
                <label for="usuario">Usuario *</label>
                <input type="text" id="usuario" name="usuario" placeholder="Usuario de empresa"
                       value="<?php echo htmlspecialchars($usuario); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" placeholder="********" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Iniciar Sesión</button>
            <button type="button" class="btn-secondary" onclick="window.location.href='../index.html'">Volver</button>
        </div>

        <div style="text-align:center; margin-top:20px; font-size:14px;">
            <p>¿No tienes cuenta? <a href="registro_empresas.php" style="color:#007bff; text-decoration:none;">Regístrate aquí</a></p>
            <p><a href="recuperar_contrasena.php" style="color:#6f42c1; text-decoration:none;">¿Olvidaste tu contraseña?</a></p>
        </div>
    </form>
</div>

</body>
</html>
