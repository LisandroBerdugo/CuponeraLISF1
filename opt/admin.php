<?php
// Include database connection
require_once 'conexion.php';

// Initialize variables for error/success messages
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $nivel = trim($_POST['nivel'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    // Basic validation
    if (empty($nivel)) $errors[] = "El nivel es obligatorio.";
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellido)) $errors[] = "El apellido es obligatorio.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";

    // If no validation errors, proceed with database insertion
    if (empty($errors)) {
        try {
            // Hash the password
            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Insert into admincuentas table
            $stmt = $conn->prepare("
                INSERT INTO admincuentas (nivel, nombre, apellido, correo, telefono, usuario, contraseña)
                VALUES (:nivel, :nombre, :apellido, :correo, :telefono, :usuario, :contrasena)
            ");
            $stmt->execute([
                ':nivel' => $nivel,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':telefono' => $telefono ?: null, // Allow NULL for optional field
                ':usuario' => $usuario,
                ':contrasena' => $contrasenaHash
            ]);

            $success = "¡Registro exitoso! La cuenta de administrador ha sido creada.";

        } catch (PDOException $e) {
            $errors[] = "Error al registrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Administrador</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .error { color: red; }
        .success { color: green; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 8px; margin-bottom: 10px; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <h2>Registro de Administrador</h2>

    <!-- Display success or error messages -->
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <ul class="error">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Registration form -->
    <form method="POST" action="">
        <div class="form-group">
            <label for="nivel">Nivel *</label>
            <select id="nivel" name="nivel" required>
                <option value="">Seleccione...</option>
                <option value="administrador">Administrador</option>
                <option value="gerente">Gerente</option>
                <option value="ventas">Ventas</option>
            </select>
        </div>
        <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="apellido">Apellido *</label>
            <input type="text" id="apellido" name="apellido" value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico *</label>
            <input type="email" id="correo" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="usuario">Usuario *</label>
            <input type="text" id="usuario" name="usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="contrasena">Contraseña *</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <button type="submit">Registrarse</button>
        <button type="button" onclick="window.location.href='../index.html'">Volver</button>
    </form>
</body>
</html>
