<?php
require_once 'conexion.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nivel = trim($_POST['nivel'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($nivel)) $errors[] = "El nivel es obligatorio.";
    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellido)) $errors[] = "El apellido es obligatorio.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";

    if (empty($errors)) {
        try {
            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO admincuentas (nivel, nombre, apellido, correo, telefono, usuario, contraseña)
                VALUES (:nivel, :nombre, :apellido, :correo, :telefono, :usuario, :contrasena)
            ");
            $stmt->execute([
                ':nivel' => $nivel,
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':telefono' => $telefono ?: null,
                ':usuario' => $usuario,
                ':contrasena' => $contrasenaHash
            ]);

            $success = "✅ ¡Registro exitoso! La cuenta de administrador ha sido creada.";

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
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">

<div class="registro-container">
    <div class="registro-header">
        <h2>Registro de Administrador</h2>
        <p>Completa los siguientes datos para crear una cuenta de administrador</p>
    </div>

    <?php if ($success): ?>
        <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

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
        <div class="form-grid">
            <div class="form-group">
                <label for="nivel">Nivel *</label>
                <select id="nivel" name="nivel" required>
                    <option value="">Seleccione...</option>
                    <option value="administrador" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="gerente" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'gerente') ? 'selected' : ''; ?>>Gerente</option>
                    <option value="ventas" <?php echo (isset($_POST['nivel']) && $_POST['nivel'] == 'ventas') ? 'selected' : ''; ?>>Ventas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Carlos"
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido *</label>
                <input type="text" id="apellido" name="apellido" placeholder="Ej: Ramírez"
                       value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="correo">Correo Electrónico *</label>
                <input type="email" id="correo" name="correo" placeholder="admin@empresa.com"
                       value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" placeholder="Ej: +503 7000-0000"
                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="usuario">Usuario *</label>
                <input type="text" id="usuario" name="usuario" placeholder="Nombre de usuario"
                       value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña *</label>
                <input type="password" id="contrasena" name="contrasena" placeholder="********" required>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Registrar Administrador</button>
            <button type="button" class="btn-secondary" onclick="window.location.href='../index.html'">Volver</button>
        </div>
    </form>
</div>

</body>
</html>
