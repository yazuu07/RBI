<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

$total = $pdo->query("SELECT COUNT(*) FROM audit_trails")->fetchColumn();
$today = $pdo->query("SELECT COUNT(*) FROM audit_trails WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$week = $pdo->query("SELECT COUNT(*) FROM audit_trails WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())")->fetchColumn();
$modules = $pdo->query("SELECT COUNT(DISTINCT table_name) FROM audit_trails")->fetchColumn();

echo json_encode([
    'total' => (int)$total,
    'today' => (int)$today,
    'week' => (int)$week,
    'modules' => (int)$modules
]);
?>