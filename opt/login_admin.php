<?php
session_start();
require_once 'conexion.php';

$errors = [];
$usuario = '';

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Error de validación CSRF token.";
    } else {
        $usuario    = trim($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        if ($usuario === '')    $errors[] = "El usuario es obligatorio.";
        if ($contrasena === '') $errors[] = "La contraseña es obligatoria.";

        if (empty($errors)) {
            try {
                
                $stmt = $conn->prepare("
                    SELECT adminId, nombre, apellido, correo, usuario, contraseña
                    FROM admincuentas
                    WHERE usuario = :usuario
                    LIMIT 1
                ");
                $stmt->execute([':usuario' => $usuario]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($contrasena, $admin['contraseña'])) {
                    // Sesión admin
                    $_SESSION['admin_id']     = $admin['adminId'];
                    $_SESSION['nombre']       = $admin['nombre'];
                    $_SESSION['apellido']     = $admin['apellido'];
                    $_SESSION['correo']       = $admin['correo'];
                    $_SESSION['tipo_usuario'] = 'admin';

                    session_regenerate_id(true);
                    header('Location: ../opt/dashboard_admin.php');
                    exit;
                } else {
                    $errors[] = "Usuario o contraseña incorrectos.";
                }
            } catch (PDOException $e) {
                $errors[] = "Error al iniciar sesión: " . $e->getMessage();
            }
        }
    }

    // rotamos token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Iniciar Sesión - Administrador</title>
  <link rel="stylesheet" href="../css/login-choice.css" />
</head>
<body class="registro-page">

<div class="registro-container">
  <div class="registro-header">
    <h2>Inicio de Sesión - Administrador</h2>
    <p>Ingresa tus credenciales para administrar el sistema</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />

    <div class="form-grid">
      <div class="form-group">
        <label for="usuario">Usuario *</label>
        <input type="text" id="usuario" name="usuario" placeholder="Tu usuario de administrador"
               value="<?php echo htmlspecialchars($usuario); ?>" required />
      </div>
      <div class="form-group">
        <label for="contrasena">Contraseña *</label>
        <input type="password" id="contrasena" name="contrasena" placeholder="********" required />
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-submit">Iniciar Sesión</button>
      <button type="button" class="btn-secondary" onclick="location.href='login.html'">Volver</button>
    </div>

    <div style="text-align:center;margin-top:20px;font-size:14px;">
      <p><a href="./recuperarcontrasena_admin.php" style="color:#6f42c1;text-decoration:none;">¿Olvidaste tu contraseña?</a></p>
    </div>
  </form>
</div>
</body>
</html>
