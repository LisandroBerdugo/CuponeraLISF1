<?php
require_once 'conexion.php';
$errors = [];
$success = '';

function isAdult($dateStr, $years = 18) {
    $dob = DateTime::createFromFormat('Y-m-d', $dateStr);
    if (!$dob) return false;
    $today = new DateTime('today');
    return $dob <= (clone $today)->modify("-{$years} years");
}

function validDUI($dui) {
    // Formato exacto: ########-#
    return preg_match('/^\d{8}-\d$/', $dui) === 1;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $nacimiento = $_POST['nacimiento'] ?? '';
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';
    $telefono = trim($_POST['telefono'] ?? '');
    $calle = trim($_POST['calle'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $dui = trim($_POST['dui'] ?? '');
    $adminId = 1;

    if (empty($nombre)) $errors[] = "El nombre es obligatorio.";
    if (empty($apellido)) $errors[] = "El apellido es obligatorio.";
    if (empty($nacimiento)) $errors[] = "La fecha de nacimiento es obligatoria.";
    if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) $errors[] = "El correo no es válido.";
    if (empty($usuario)) $errors[] = "El usuario es obligatorio.";
    if (empty($contrasena)) $errors[] = "La contraseña es obligatoria.";
    if (empty($calle)) $errors[] = "La calle es obligatoria.";
    if (empty($ciudad)) $errors[] = "La ciudad es obligatoria.";
    if (empty($dui)) $errors[] = "El DUI es obligatorio.";
    if (!empty($nacimiento) && !isAdult($nacimiento, 18)) {
    $errors[] = "Debes ser mayor de 18 años.";}
    if (empty($errors)) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM clientes WHERE correo = :c OR usuario = :u OR dui = :d");
    $stmt->execute([':c' => $correo, ':u' => $usuario, ':d' => $dui]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Correo, usuario o DUI ya existen.";
    }
}


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
                ':puntoRef' => null,
            ]);
            $contactoId = $conn->lastInsertId();

            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO clientes
      (adminId, contactoId, estado, nombre, apellido, dui, nacimiento, correo, usuario, contraseña)
    VALUES
      (:adminId, :contactoId, 'pendiente', :nombre, :apellido, :dui, :nacimiento, :correo, :usuario, :contrasena)");
            $stmt->execute([
            ':adminId'    => $adminId,
            ':contactoId' => $contactoId,
            ':nombre'     => $nombre,
            ':apellido'   => $apellido,
            ':dui'        => $dui,
            ':nacimiento' => $nacimiento,
            ':correo'     => $correo,
            ':usuario'    => $usuario,
            ':contrasena' => $contrasenaHash,
]);

            $conn->commit();
            $success = "¡Registro exitoso! Tu cuenta está pendiente de aprobación.";
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
    <title>Registro de Cliente</title>
    <link rel="stylesheet" href="../css/login-choice.css">
</head>
<body class="registro-page">

    <div class="registro-container">
        <div class="registro-header">
            <h2>Registro de Cliente</h2>
            <p>Completa los siguientes campos para crear tu cuenta</p>
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

        <form method="POST" action="" class="registro-form">
            <div class="form-grid">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre" required value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" placeholder="Ingresa tu apellido" required value="<?php echo htmlspecialchars($_POST['apellido'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="nacimiento">Fecha de Nacimiento *</label>
                    <input type="date" id="nacimiento" name="nacimiento" required value="<?php echo htmlspecialchars($_POST['nacimiento'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="correo">Correo Electrónico *</label>
                    <input type="email" id="correo" name="correo" placeholder="example@mail.com" required value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="usuario">Usuario *</label>
                    <input type="text" id="usuario" name="usuario" placeholder="Nombre de usuario" required value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="contrasena">Contraseña *</label>
                    <input type="password" id="contrasena" name="contrasena" placeholder="********" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" placeholder="Ej: +503 7000-0000" value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="calle">Calle *</label>
                    <input type="text" id="calle" name="calle" placeholder="Ej: Calle Los Pinos" required value="<?php echo htmlspecialchars($_POST['calle'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="ciudad">Ciudad *</label>
                    <input type="text" id="ciudad" name="ciudad" placeholder="Ej: San Salvador" required value="<?php echo htmlspecialchars($_POST['ciudad'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="dui">DUI *</label>
                    <input type="text" id="dui" name="dui" required placeholder="00000000-0" inputmode="numeric"
                    pattern="\d{8}-\d" maxlength="10" value="<?php echo htmlspecialchars($_POST['dui'] ?? ''); 
                    ?>"oninput="let v = this.value.replace(/\D/g,'').slice(0,9); this.value = v.length > 8 ? v.slice(0,8) + '-' + v.slice(8) : v;">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Registrarse</button>
                <button type="button" class="btn-secondary" onclick="location.href='registry.html'">Volver</button>
            </div>
        </form>
    </div>

</body>
</html>
                    