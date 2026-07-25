<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

$total = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Active'")->fetchColumn();
$inactive = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Inactive'")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Expired'")->fetchColumn();

echo json_encode([
    'total' => (int)$total,
    'active' => (int)$active,
    'inactive' => (int)$inactive,
    'expired' => (int)$expired
]);
?>