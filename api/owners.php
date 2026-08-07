<?php
// api/owners.php - Searchable owners endpoint
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT id, last_name, first_name, middle_name, ext_name 
        FROM household_records 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (last_name LIKE ? OR first_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY last_name, first_name LIMIT 50"; // Limit to 50 results for performance

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$owners = $stmt->fetchAll();

echo json_encode([
    'data' => $owners,
    'total' => count($owners),
    'search' => $search
]);
?>