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
    SELECT c.*, 
           CONCAT(i.last_name, ', ', i.first_name, 
                  IF(i.middle_name IS NOT NULL, CONCAT(' ', SUBSTRING(i.middle_name, 1, 1), '.'), ''),
                  IF(i.ext_name IS NOT NULL, CONCAT(' ', i.ext_name), '')) as resident_name,
           i.sex, i.date_of_birth, i.place_of_birth, i.civil_status,
           u.full_name as issued_by_name
    FROM certificates c 
    LEFT JOIN individual_records i ON c.resident_id = i.id 
    LEFT JOIN users u ON c.issued_by = u.id 
    WHERE c.id = ?
");
$stmt->execute([$id]);
$certificate = $stmt->fetch();

if (!$certificate) {
    echo json_encode(['error' => 'Certificate not found']);
    exit();
}

echo json_encode($certificate);
?>