<?php
// Include database connection
require_once 'conexion.php';

// Initialize variables for error/success messages
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form inputs
    $nombreEmpresa = trim($_POST['nombreEmpresa'] ?? '');
    $NIT = trim($_POST['NIT'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $puntoRef = trim($_POST['puntoRef'] ?? '');
    $adminId = 1; // Default adminId (modify as needed)

    // Basic validation
    if (empty($nombreEmpresa)) $errors[] = "El nombre de la empresa es obligatorio.";
    if (empty($NIT)) $errors[] = "El NIT es obligatorio.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($password)) $errors[] = "La contraseña es obligatoria.";
    if (empty($calle)) $errors[] = "La calle es obligatoria.";
    if (empty($ciudad)) $errors[] = "La ciudad es obligatoria.";

    // If no validation errors, proceed with database insertion
    if (empty($errors)) {
        try {
            // Start transaction
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
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insert into empresas table
            $stmt = $conn->prepare("
                INSERT INTO empresas (adminId, contactoId, nombreEmpresa, NIT, correo, usuario, password, estado)
                VALUES (:adminId, :contactoId, :nombreEmpresa, :NIT, :correo, :usuario, :password, 'pendiente')
            ");
            $stmt->execute([
                ':adminId' => $adminId,
                ':contactoId' => $contactoId,
                ':nombreEmpresa' => $nombreEmpresa,
                ':NIT' => $NIT,
                ':correo' => $correo,
                ':usuario' => $usuario,
                ':password' => $passwordHash
            ]);

            // Commit transaction
            $conn->commit();
            $success = "¡Registro de empresa exitoso! Tu cuenta está pendiente de aprobación.";

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
    <title>Registro de Empresa</title>
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
    <h2>Registro de Empresa</h2>

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
            <label for="nombreEmpresa">Nombre de la Empresa *</label>
            <input type="text" id="nombreEmpresa" name="nombreEmpresa" value="<?php echo isset($_POST['nombreEmpresa']) ? htmlspecialchars($_POST['nombreEmpresa']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label for="NIT">NIT *</label>
            <input type="text" id="NIT" name="NIT" value="<?php echo isset($_POST['NIT']) ? htmlspecialchars($_POST['NIT']) : ''; ?>" required>
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
            <label for="password">Contraseña *</label>
            <input type="password" id="password" name="password" required>
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
        <button type="submit">Registrar Empresa</button>
        <button type="button" onclick="window.location.href='../index.html'">Volver</button>
    </form>
</body>
</html>