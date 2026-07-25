<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM user_roles WHERE id = ?");
$stmt->execute([$id]);
$role = $stmt->fetch();

if (!$role) {
    echo json_encode(['error' => 'Role not found']);
    exit();
}

echo json_encode($role);
?>