<?php
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT p.*, 
           CONCAT(h.last_name, ', ', h.first_name) as owner_name
    FROM pets p 
    LEFT JOIN household_records h ON p.owner_id = h.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pet = $stmt->fetch();

if (!$pet) {
    echo json_encode(['error' => 'Pet not found']);
    exit();
}

echo json_encode($pet);
?>