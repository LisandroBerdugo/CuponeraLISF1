<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['cliente_id'])) {
    header("Location: login_cliente.php");
    exit;
}
