<?php
// Include database connection
require_once 'conexion.php';

// Initialize variables for error/success messages
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $nacimiento = $_POST['nacimiento'] ?? '';
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $puntoRef = trim($_POST['puntoRef'] ?? '');
    $adminId = 1; // Default adminId (modify as needed)

    // Basic validation
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellido)) $errors[] = "El apellido es obligatorio.";
    if (empty($nacimiento)) $errors[] = "La fecha de nacimiento es obligatoria.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";
    if (empty($calle)) $errors[] = "La calle es obligatoria.";
    if (empty($ciudad)) $errors[] = "La ciudad es obligatoria.";

    // If no validation errors, proceed with database insertion
    if (empty($errors)) {
        try {
            // Start transaction (assuming PDO connection)
            $conn->beginTransaction();

            // Insert into contactos table
            $stmt = $conn->prepare("
                INSERT INTO contactos (telefono, calle, ciudad, puntoRef)
                VALUES (:telefono, :calle, :ciudad, :puntoRef)
            ");
            $stmt->execute([
                ':telefono' => $telefono ?: null, // Allow NULL for optional field
                ':calle' => $calle,
                ':ciudad' => $ciudad,
                ':puntoRef' => $puntoRef ?: null // Allow NULL for optional field
            ]);
            $contactoId = $conn->lastInsertId(); // Get the inserted contactoId

            // Hash the password
            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

            // Insert into clientes table
            $stmt = $conn->prepare("
                INSERT INTO clientes (adminId, contactoId, estado, nombre, apellido, nacimiento, correo, usuario, contraseña)
                VALUES (:adminId, :contactoId, 'pendiente', :nombre, :apellido, :nacimiento, :correo, :usuario, :contrasena)
            ");
            $stmt->execute([
                ':adminId' => $adminId,
                ':contactoId' => $contactoId,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':nacimiento' => $nacimiento,
                ':correo' => $correo,
                ':usuario' => $usuario,
                ':contrasena' => $contrasenaHash
            ]);

            // Commit transaction
            $conn->commit();
            $success = "¡Registro exitoso! Tu cuenta está pendiente de aprobación.";

        } catch (PDOException $e) {
            // Rollback transaction on error
            $conn->rollBack();
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
    <title>Registro de Cliente</title>
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
    <h2>Registro de Cliente</h2>

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
            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="apellido">Apellido *</label>
            <input type="text" id="apellido" name="apellido" value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="nacimiento">Fecha de Nacimiento *</label>
            <input type="date" id="nacimiento" name="nacimiento" value="<?php echo isset($_POST['nacimiento']) ? htmlspecialchars($_POST['nacimiento']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="correo">Correo Electrónico *</label>
            <input type="email" id="correo" name="correo" value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="usuario">Usuario *</label>
            <input type="text" id="usuario" name="usuario" value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="contrasena">Contraseña *</label>
            <input type="password" id="contrasena" name="contrasena" required>
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="calle">Calle *</label>
            <input type="text" id="calle" name="calle" value="<?php echo isset($_POST['calle']) ? htmlspecialchars($_POST['calle']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="ciudad">Ciudad *</label>
            <input type="text" id="ciudad" name="ciudad" value="<?php echo isset($_POST['ciudad']) ? htmlspecialchars($_POST['ciudad']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="puntoRef">Punto de Referencia</label>
            <input type="text" id="puntoRef" name="puntoRef" value="<?php echo isset($_POST['puntoRef']) ? htmlspecialchars($_POST['puntoRef']) : ''; ?>">
        </div>
        <button type="submit">Registrarse</button>
        <button type="button" onclick="window.location.href='../index.html'">Volver</button>
    </form>
</body>
</html>