<?php
// opt/_guard_admin.php

// Asegura sesión activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
 * Si necesitas la conexión en otras páginas, puedes incluirla aquí:
 * require_once __DIR__ . '/conexion.php';
 * Pero NO bloqueamos si $pdo no existe, para que el guard sea ligero.
 */

// --- Validación de sesión de ADMIN ---
// Minimalista: solo valida que exista admin_id.
// (Si además usas un flag de tipo, descomenta la línea de $requiereRol)
$requiereRol = false; // pon true si quieres forzar tipo_usuario='admin'

$hayAdminId = isset($_SESSION['admin_id']);
$rolEsAdmin = (($_SESSION['tipo_usuario'] ?? '') === 'admin');

if (!$hayAdminId || ($requiereRol && !$rolEsAdmin)) {
    header('Location: login_admin.php');
    exit;
}

// --- CSRF para formularios del panel ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
