<?php
// Start session
session_start();

// Include database connection
require_once 'conexion.php';

// Initialize variables
$errors = [];
$usuario = '';

// Generate or validate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Error de validación CSRF.";
    } else {
        // Collect and sanitize inputs
        $usuario = trim($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        // Basic validation
        if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
        if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";

        // Proceed if no validation errors
        if (empty($errors)) {
            try {
                // Query clientes table
                $stmt = $conn->prepare("
                    SELECT clienteId, nombre, apellido, correo, usuario, contraseña, estado
                    FROM clientes
                    WHERE usuario = :usuario
                ");
                $stmt->execute([':usuario' => $usuario]);
                $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify user and password
                if ($cliente && password_verify($contrasena, $cliente['contraseña'])) {
                    // Check if account is active
                    if ($cliente['estado'] === 'activo') {
                        // Store user data in session
                        $_SESSION['cliente_id'] = $cliente['clienteId'];
                        $_SESSION['nombre'] = $cliente['nombre'];
                        $_SESSION['apellido'] = $cliente['apellido'];
                        $_SESSION['correo'] = $cliente['correo'];
                        $_SESSION['tipo_usuario'] = 'cliente';

                        // Regenerate session ID for security
                        session_regenerate_id(true);

                        // Redirect to dashboard
                        header('Location: dashboard_cliente.php');
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

    // Regenerate CSRF token after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Cliente</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
        .error { color: red; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Iniciar Sesión - Cliente</h2>

    <!-- Display errors -->
    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Login form -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="form-group">
            <label for="usuario">Usuario *</label>
            <input type="text" id="usuario" name="usuario" value="<?php echo htmlspecialchars($usuario); ?>" required>
        </div>
        <div class="form-group">
            <label for="contrasena">Contraseña *</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <button type="submit">Iniciar Sesión</button>
        <button type="button" onclick="window.location.href='../index.html'">Volver</button>
        <p>¿No tienes cuenta? <a href="registro_cliente.php">Regístrate aquí</a></p>
        <p><a href="recuperar_contrasena.php">¿Olvidaste tu contraseña?</a></p>
    </form>
</body>
</html>