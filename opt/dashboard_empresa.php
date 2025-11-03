<?php
// opt/panel_cliente.php
require_once __DIR__ . '/_guard_empresa.php';

// Flags de sesión login actual
$isLogged = isset($_SESSION['empresa_id']) && (($_SESSION['tipo_usuario'] ?? '') === 'empresa');
// Datos principales (empresa)
$empresaId      = $_SESSION['empresa_id']     ?? null;
$nombreEmpresa  = $_SESSION['nombreEmpresa']  ?? '';   // ej. se seteó en login_empresa.php
$nit            = $_SESSION['NIT']            ?? '';
$correoEmpresa  = $_SESSION['correo']         ?? '';
$estadoEmpresa  = $_SESSION['estado']         ?? '';   // 'activo' | 'pendiente' | 'desactivo'
$fechaCreado    = $_SESSION['fechaCreado']    ?? null; // opcional si lo guardas
$correo = $_SESSION['correo'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Empresa</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
  integrity="sha512-M9rN6GlAwzSqt7nYZKYbghJ6zN93jANXReOYXyIhZxYxIRh7Ytu9Q1dlAf3y+z/NxJ5pSGpVny7iM5vF5MUuw=="
  crossorigin="anonymous"
  referrerpolicy="no-referrer"
/>

  <style>
    .panel-wrap { max-width: 980px; margin: 40px auto; padding: 0 16px; }
    .card { background:#fff; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.06); padding:24px; }
    .grid { display:grid; gap:16px; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); }
    .kpi { background:#f9fafb; border:1px solid #eef2f7; border-radius:12px; padding:16px; }
    .kpi h4 { margin:0 0 6px; font-size:14px; color:#6b7280; }
    .kpi div { font-size:22px; font-weight:800; }
    .actions a { text-decoration:none; color:#374151; margin-right:12px; }
    .actions a:hover { text-decoration:underline; }

    /* Asegurar que el saludo se vea */
    .card h2 { color:#111827; font-weight:800; margin:0 0 8px 0; line-height:1.2; }

    /* Footer */
    .footer { background:#f9fafb; color:#333; padding:40px 20px 20px; margin-top:40px; border-top:1px solid #e5e7eb; }
    .footer-container { display:flex; flex-wrap:wrap; justify-content:space-between; max-width:1100px; margin:0 auto; }
    .footer-logo { flex:1 1 200px; text-align:center; }
    .footer-logo img { width:80px; border-radius:50%; margin-bottom:10px; }
    .footer-info { flex:1 1 300px; font-size:14px; line-height:1.6; }
    .footer-social { flex:1 1 200px; text-align:center; }
    .footer-social ul { list-style:none; padding:0; display:flex; justify-content:center; gap:15px; }
    .footer-social ul li a { font-size:20px; color:#333; transition:color .3s; }
    .footer-social ul li a:hover { color:#4f46e5; }
    .footer-bottom { text-align:center; margin-top:20px; font-size:13px; color:#666; border-top:1px solid #ddd; padding-top:15px; }
  </style>
</head>
<body>

  <!-- Header -->
  <section id="header">
    <a href="../opt/index.php"><img src="../img/logo.jpg" class="logo" alt="Logo"></a>
    <div>
      <ul id="navbar">
        <li><a href="../opt/index.php">Inicio</a></li>
        <li><a href="../opt/shop.html">Tienda</a></li>
        <li><a href="../opt/contact.html">Contacto</a></li>
        <li><a href="../opt/cart.html"><i class="fa fa-shopping-cart"></i></a></li>
        

        <?php if ($isLogged): ?>
          <!-- Mostrar Dashboard si hay sesión -->
          <li><a class="active" href="panel_cliente.php"><i class="fa fa-user"></i> Dashboard</a></li>
        <?php else: ?>
          <!-- Si no hay sesión, icono de login -->
          <li><a href="login_empresa.php"><i class="fa fa-user"></i></a></li>
        <?php endif; ?>
      </ul>
    </div>
  </section>

  <div class="panel-wrap">
    <div class="card">
      <h2>Hola, <?= htmlspecialchars($nombreEmpresa ?: 'EmpresaID') ?></h2>
      <?php if ($correo): ?>
        <p>Correo: <?= htmlspecialchars($correo) ?></p>
      <?php endif; ?>

      <div class="actions" style="margin:12px 0 20px;">
        <a href="mis_cupones_emp.php">Mis cupones</a>
        <a href="mis_propuestas.php">Proponer cupones</a>
        <a href="editar_perfil.php">Editar perfil</a>
         <a href="../opt/login.html">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Cerrar sesión</span>
</a>
      </div>

      <div class="grid">
        <div class="kpi"><h4>Cupones disponibles</h4><div>—</div></div>
        <div class="kpi"><h4>Ventas realizadas</h4><div>—</div></div>
        <div class="kpi"><h4>Ingreso total</h4><div>$ —</div></div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-logo">
        <img src="../img/logo.jpg" alt="Tickets LIS UDB">
        <h3>Tickets LIS UDB</h3>
      </div>
      <div class="footer-info">
        <p>Dirección: Colonial Escalón, 1ra calle poniente #513</p>
        <p>San Salvador, El Salvador</p>
        <p>Teléfono: <a href="tel:+50361612222">503 6161 2222</a></p>
        <p>Whatsapp: <a href="https://wa.me/5036162223" target="_blank" rel="noopener">503 6162 2223</a></p>
      </div>
      <div class="footer-social">
        <h4>Síguenos</h4>
        <ul>
          <li><a href="#"><i class="fa-brands fa-facebook"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
          <li><a href="#"><i class="fa-brands fa-whatsapp"></i></a></li>
        </ul>
      </div>

    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 TICKETS LIS UDB SV - Todos los derechos reservados</p>
    </div>
  </footer>

</body>
</html>
