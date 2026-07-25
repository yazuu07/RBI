<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

$total = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$issued = $pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'Issued'")->fetchColumn();
$pending = $pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'Pending'")->fetchColumn();
$expired = $pdo->query("SELECT COUNT(*) FROM certificates WHERE status = 'Expired'")->fetchColumn();

echo json_encode([
    'total' => (int)$total,
    'issued' => (int)$issued,
    'pending' => (int)$pending,
    'expired' => (int)$expired
]);
?>