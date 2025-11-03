<?php
require_once 'conexion.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombreEmpresa = trim($_POST['nombreEmpresa'] ?? '');
    $NIT = trim($_POST['NIT'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $puntoRef = trim($_POST['puntoRef'] ?? '');
    $adminId = 1;

    if (empty($nombreEmpresa)) $errors[] = "El nombre de la empresa es obligatorio.";
    if (empty($NIT)) $errors[] = "El NIT es obligatorio.";
     if (!empty($NIT) && !preg_match('/^\d{4}-\d{6}-\d{3}-\d$/', $NIT)) {
         $errors[] = "El NIT debe tener el formato 0000-000000-000-0.";}
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($password)) $errors[] = "La contraseña es obligatoria.";
    if (empty($calle)) $errors[] = "La calle es obligatoria.";
    if (empty($ciudad)) $errors[] = "La ciudad es obligatoria.";

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("
                INSERT INTO contactos (telefono, calle, ciudad, puntoRef)
                VALUES (:telefono, :calle, :ciudad, :puntoRef)
            ");
            $stmt->execute([
                ':telefono' => $telefono ?: null,
                ':calle' => $calle,
                ':ciudad' => $ciudad,
                ':puntoRef' => $puntoRef ?: null
            ]);
            $contactoId = $conn->lastInsertId();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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

            $conn->commit();
            $success = "¡Registro de empresa exitoso! Tu cuenta está pendiente de aprobación.";

        } catch (PDOException $e) {
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
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">

<div class="registro-container">
    <div class="registro-header">
        <h2>Registro de Empresa</h2>
        <p>Completa los siguientes campos para registrar tu empresa</p>
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
                <label for="nombreEmpresa">Nombre de la Empresa *</label>
                <input type="text" id="nombreEmpresa" name="nombreEmpresa" placeholder="Ej: Mi Empresa S.A. de C.V." 
                       value="<?php echo isset($_POST['nombreEmpresa']) ? htmlspecialchars($_POST['nombreEmpresa']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="NIT">NIT *</label>
                <input type="text" id="NIT" name="NIT" required placeholder="Ej: 0000-000000-000-0"
                       pattern="\d{4}-\d{6}-\d{3}-\d" maxlength="17"
                       value="<?php echo isset($_POST['NIT']) ? htmlspecialchars($_POST['NIT']) : ''; ?>" required
                       oninput="let v = this.value.replace(/\D/g,'').slice(0,14);
                                   if (v.length > 12) this.value = v.slice(0,4)+'-'+v.slice(4,10)+'-'+v.slice(10,13)+'-'+v.slice(13);
                                else if (v.length > 9) this.value = v.slice(0,4)+'-'+v.slice(4,10)+'-'+v.slice(10);
                                else if (v.length > 4) this.value = v.slice(0,4)+'-'+v.slice(4);
                                else this.value = v;">
            </div>

            <div class="form-group">
                <label for="correo">Correo Electrónico *</label>
                <input type="email" id="correo" name="correo" placeholder="empresa@correo.com"
                       value="<?php echo isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="usuario">Usuario *</label>
                <input type="text" id="usuario" name="usuario" placeholder="Nombre de usuario"
                       value="<?php echo isset($_POST['usuario']) ? htmlspecialchars($_POST['usuario']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" placeholder="********" required>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="text" id="telefono" name="telefono" placeholder="Ej: +503 7000-0000"
                       value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="calle">Calle *</label>
                <input type="text" id="calle" name="calle" placeholder="Ej: Calle Los Pinos"
                       value="<?php echo isset($_POST['calle']) ? htmlspecialchars($_POST['calle']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="ciudad">Ciudad *</label>
                <input type="text" id="ciudad" name="ciudad" placeholder="Ej: San Salvador"
                       value="<?php echo isset($_POST['ciudad']) ? htmlspecialchars($_POST['ciudad']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="puntoRef">Punto de Referencia</label>
                <input type="text" id="puntoRef" name="puntoRef" placeholder="Cerca de..."
                       value="<?php echo isset($_POST['puntoRef']) ? htmlspecialchars($_POST['puntoRef']) : ''; ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Registrar Empresa</button>
            <button type="button" class="btn-secondary" onclick="location.href='registry.html'">Volver</button>
        </div>
    </form>
</div>

</body>
</html>
