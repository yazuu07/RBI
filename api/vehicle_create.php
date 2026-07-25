<?php
require_once '../config.php';
requireLogin();
requirePermission('extras', 'add');

header('Content-Type: application/json');

$pdo = getDB();

$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
$plate_number = isset($_POST['plate_number']) ? trim($_POST['plate_number']) : '';
$vehicle_type = isset($_POST['vehicle_type']) ? trim($_POST['vehicle_type']) : '';
$brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '';
$year_model = isset($_POST['year_model']) ? (int)$_POST['year_model'] : null;
$registration_date = isset($_POST['registration_date']) ? $_POST['registration_date'] : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

// Validate
if (empty($plate_number)) {
    echo json_encode(['success' => false, 'message' => 'Plate number is required']);
    exit();
}

if (empty($vehicle_type)) {
    echo json_encode(['success' => false, 'message' => 'Vehicle type is required']);
    exit();
}

// Check if plate number already exists
$stmt = $pdo->prepare("SELECT id FROM vehicles WHERE plate_number = ?");
$stmt->execute([$plate_number]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Plate number already exists']);
    exit();
}

// Handle photo upload
$vehicle_photo = null;
if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
    $vehicle_photo = uploadFile($_FILES['vehicle_photo']);
    if (!$vehicle_photo) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).']);
        exit();
    }
}

// Insert vehicle
$sql = "INSERT INTO vehicles (
    owner_id, plate_number, vehicle_type, brand, color, 
    year_model, registration_date, status, vehicle_photo, created_by
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $owner_id, $plate_number, $vehicle_type, $brand, $color,
    $year_model, $registration_date, $status, $vehicle_photo, $_SESSION['user_id']
]);

if ($result) {
    $id = $pdo->lastInsertId();
    logAudit($_SESSION['user_id'], 'CREATE', 'vehicles', $id, 
        "Added vehicle: $plate_number ($vehicle_type)");
    
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save vehicle']);
}
?>