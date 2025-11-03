<?php
session_start();
require_once 'conexion.php';

$errors = [];
$ok     = '';
$ident  = ''; // correo o usuario

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $errors[] = "Error de validación CSRF.";
  } else {
    $ident = trim($_POST['ident'] ?? '');

    if ($ident === '') {
      $errors[] = "Ingresa tu correo o tu usuario.";
    } else {
      try {
       
        $stmt = $conn->prepare("
          SELECT adminId, correo, usuario
          FROM admincuentas
          WHERE correo = :ident OR usuario = :ident
          LIMIT 1
        ");
        $stmt->execute([':ident' => $ident]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mensaje genérico. Si existe, generamos token.
        if ($admin) {
          $token     = bin2hex(random_bytes(32)); // 64 chars
          $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

          // upsert en password_resets
          $stmt = $conn->prepare("
            INSERT INTO password_resets (tipo, user_id, token, expira_en)
            VALUES ('admin', :uid, :tok, :exp)
            ON DUPLICATE KEY UPDATE token = VALUES(token), expira_en = VALUES(expira_en)
          ");
          $stmt->execute([':uid'=>$admin['adminId'], ':tok'=>$token, ':exp'=>$expiresAt]);

          // Enlace de reseteo (en local lo mostramos)
          $resetLink = sprintf(
            '%s://%s%s/reset_admin.php?uid=%d&token=%s',
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            rtrim(dirname($_SERVER['PHP_SELF']), '/\\'),
            (int)$admin['adminId'],
            $token
          );

          $ok = "Si existe una cuenta con esos datos, te enviaremos un enlace para restablecer la contraseña.";
         
        
        } else {
          $ok = "Si existe una cuenta con esos datos, te enviaremos un enlace para restablecer la contraseña.";
        }
      } catch (Throwable $e) {
        $errors[] = "Ocurrió un error: " . $e->getMessage();
      }
    }
  }
  // rotar CSRF
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recuperar contraseña · Admin</title>
  <link rel="stylesheet" href="../css/login-choice.css" />
</head>
<body class="registro-page">

<div class="registro-container">
  <div class="registro-header">
    <h2>¿Olvidaste tu contraseña? (Admin)</h2>
    <p>Escribe tu <strong>correo</strong> o <strong>usuario</strong> para enviarte un enlace temporal.</p>
  </div>

  <?php if ($ok): ?>
    <div class="alert success"><?php echo $ok; ?></div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert error">
      <ul><?php foreach ($errors as $er) echo '<li>'.htmlspecialchars($er).'</li>'; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" />
    <div class="form-grid" style="grid-template-columns: 1fr;">
      <div class="form-group">
        <label for="ident">Correo o Usuario *</label>
        <input type="text" id="ident" name="ident" placeholder="admin@dominio.com o admin"
               value="<?php echo htmlspecialchars($ident); ?>" required />
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-submit">Enviar enlace</button>
      <button type="button" class="btn-secondary" onclick="location.href='login_admin.php'">Volver</button>
    </div>
  </form>
</div>
</body>
</html>
