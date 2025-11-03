<?php
declare(strict_types=1);
require_once __DIR__ . '/_guard_admin.php';
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_empresas_pendientes.php'); exit;
}
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(400); die('CSRF inválido');
}
$id = (int)($_POST['id'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');

if ($id <= 0) { http_response_code(400); die('ID inválido'); }

$sql = "UPDATE empresas
        SET estado='rechazada', motivo_rechazo=:motivo, rechazado_por=:admin, rechazado_en=NOW()
        WHERE id=:id AND estado='pendiente'";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':motivo' => $motivo ?: null,
    ':admin'  => (int)($_SESSION['admin_id'] ?? 0),
    ':id'     => $id
]);

header('Location: admin_empresas_pendientes.php');
