<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

$total = $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Active'")->fetchColumn();
$inactive = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Inactive'")->fetchColumn();
$deceased = $pdo->query("SELECT COUNT(*) FROM pets WHERE status = 'Deceased'")->fetchColumn();

// Get pet type counts
$type_counts = $pdo->query("
    SELECT pet_type, COUNT(*) as count 
    FROM pets 
    WHERE pet_type IS NOT NULL 
    GROUP BY pet_type 
    ORDER BY count DESC
")->fetchAll();

echo json_encode([
    'total' => (int)$total,
    'active' => (int)$active,
    'inactive' => (int)$inactive,
    'deceased' => (int)$deceased,
    'types' => $type_counts
]);
?>