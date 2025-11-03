<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

$ok = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $err = 'CSRF inválido';
    } else {
        $nombre   = trim($_POST['nombre'] ?? '');
        $apellido = trim($_POST['apellido'] ?? '');
        $usuario  = trim($_POST['usuario'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $pass     = $_POST['password'] ?? '';

        if (!$nombre || !$apellido || !$usuario || !$correo || strlen($pass) < 6) {
            $err = 'Completa todos los campos (mín. 6 caracteres de contraseña)';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $sql = "INSERT INTO admincuentas (usuario, password_hash, nombre, apellido, correo, creado_en)
                        VALUES (:u,:h,:n,:a,:c,NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':u'=>$usuario, ':h'=>$hash, ':n'=>$nombre, ':a'=>$apellido, ':c'=>$correo
                ]);
                $ok = 'Administrador creado';
            } catch (Throwable $e) {
                $err = 'Error: ' . $e->getMessage();
            }
        }
    }
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"><title>Crear administrador</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .wrap { max-width:680px; margin: 30px auto; padding: 16px;}
    .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); padding:24px;}
    input { width:100%; padding:10px; border:1px solid #e5e7eb; border-radius:10px; margin:8px 0;}
    .btn { background:#4f46e5; color:#fff; border:none; padding:10px 14px; border-radius:10px; cursor:pointer;}
    .msg-ok{color:#10b981;} .msg-err{color:#ef4444;}
  </style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h2>Registrar administrador</h2>
    <?php if($ok): ?><p class="msg-ok"><?= htmlspecialchars($ok) ?></p><?php endif; ?>
    <?php if($err): ?><p class="msg-err"><?= htmlspecialchars($err) ?></p><?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="text" name="nombre" placeholder="Nombre" required>
      <input type="text" name="apellido" placeholder="Apellido" required>
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="email" name="correo" placeholder="Correo" required>
      <input type="password" name="password" placeholder="Contraseña (mín. 6)" required>
      <button class="btn" type="submit">Crear</button>
    </form>
  </div>
</div>
</body>
</html>
