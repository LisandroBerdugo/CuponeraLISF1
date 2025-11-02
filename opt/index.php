<?php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Sesión: marcar como logueado si viene del login de cliente
$isLogged = isset($_SESSION['cliente_id']) && (($_SESSION['tipo_usuario'] ?? '') === 'cliente');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Ticket LIS UDB</title>

  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/home.css" />

 
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body>

  <!-- Header -->
  <section id="header">
    <a href="../opt/index.php">
      <img src="../img/logo.jpg" class="logo" alt="Logo">
    </a>
    <div>
      <ul id="navbar">
        <li><a class="active" href="../opt/index.php">Inicio</a></li>
        <li><a href="../opt/shop.html">Tienda</a></li>
        <li><a href="../opt/contact.html">Contacto</a></li>
        <li>
          <a href="../opt/cart.html" title="Carrito">
            <i class="fa fa-shopping-cart"></i>
            <span id="cart-plus-sign"></span>
          </a>
        </li>

        <?php if ($isLogged): ?>
          <!-- Si hay sesión, muestra Dashboard -->
          <li>
            <a href="../opt/dashboard_cliente.php">
              <i class="fa fa-user"></i> Dashboard
            </a>
          </li>
        <?php else: ?>
          <!-- Sin sesión, muestra icono de login -->
          <li>
            <a href="../opt/login_cliente.php" title="Iniciar sesión">
              <i class="fa fa-user"></i>
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </section>

    <!-- Contenido principal -->
  <div id="content">
    <section id="hero"
    style="background-image:url('../img/inicio.jpg'); background-size:cover; background-position:center;">
      <h4>TICKETS LIS UDB</h4>
      <h2>Las mejores ofertas</h2>
      <h1>de las mejores empresas de la región</h1>
      <p id="phero">Ahorra hasta el 50% al comprar con nosotros</p>
      <button onclick="window.location.href='../opt/shop.html'">Ver ofertas</button>
    </section>
  </div>

  <!-- Footer mínimo -->
  <footer style="background:#111; color:#fff; padding:20px 10px; margin-top:50px; text-align:center;">
    <p>&copy; 2025 TICKETS LIS UDB - Todos los derechos reservados</p>
    <div style="margin-top:10px;">
      <a href="#" style="color:#fff; margin:0 10px;"><i class="fab fa-facebook"></i></a>
      <a href="#" style="color:#fff; margin:0 10px;"><i class="fab fa-twitter"></i></a>
      <a href="#" style="color:#fff; margin:0 10px;"><i class="fab fa-instagram"></i></a>
    </div>
  </footer>

  <!-- Actualizar contador del carrito  -->
  <script>
    (function updateCartBadge() {
      try {
        const key = 'cart';               
        const raw = localStorage.getItem(key);
        const items = raw ? JSON.parse(raw) : [];
        const totalQty = Array.isArray(items)
          ? items.reduce((s, it) => s + (Number(it.qty) || 0), 0)
          : 0;
        const badge = document.getElementById('cart-plus-sign');
        if (badge) badge.textContent = totalQty > 0 ? `(${totalQty})` : '';
      } catch (e) {
      
      }
    })();
  </script>
</body>
</html>
