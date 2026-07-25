<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'view');

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

// Remove password from response
unset($user['password']);

echo json_encode($user);
?>