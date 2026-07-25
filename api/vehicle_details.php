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
    SELECT v.*, 
           CONCAT(h.last_name, ', ', h.first_name, 
                  IF(h.middle_name IS NOT NULL, CONCAT(' ', SUBSTRING(h.middle_name, 1, 1), '.'), ''),
                  IF(h.ext_name IS NOT NULL, CONCAT(' ', h.ext_name), '')) as owner_name
    FROM vehicles v 
    LEFT JOIN household_records h ON v.owner_id = h.id 
    WHERE v.id = ?
");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    echo json_encode(['error' => 'Vehicle not found']);
    exit();
}

echo json_encode($vehicle);
?>