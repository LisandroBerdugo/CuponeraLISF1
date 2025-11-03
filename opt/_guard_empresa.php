<?php
// opt/_guard_empresa.php
if (session_status() === PHP_SESSION_NONE) session_start();

// Verifica que sea una sesión de EMPRESA válida
if (
    !isset($_SESSION['empresa_id']) ||
    ($_SESSION['tipo_usuario'] ?? '') !== 'empresa'
) {
}
