<?php
require_once '../config.php';
requireLogin();
requirePermission('extras', 'edit');

header('Content-Type: application/json');

$pdo = getDB();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
$plate_number = isset($_POST['plate_number']) ? trim($_POST['plate_number']) : '';
$vehicle_type = isset($_POST['vehicle_type']) ? trim($_POST['vehicle_type']) : '';
$brand = isset($_POST['brand']) ? trim($_POST['brand']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '';
$year_model = isset($_POST['year_model']) ? (int)$_POST['year_model'] : null;
$registration_date = isset($_POST['registration_date']) ? $_POST['registration_date'] : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid vehicle ID']);
    exit();
}

if (empty($plate_number)) {
    echo json_encode(['success' => false, 'message' => 'Plate number is required']);
    exit();
}

if (empty($vehicle_type)) {
    echo json_encode(['success' => false, 'message' => 'Vehicle type is required']);
    exit();
}

// Check if plate number exists for other vehicles
$stmt = $pdo->prepare("SELECT id FROM vehicles WHERE plate_number = ? AND id != ?");
$stmt->execute([$plate_number, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Plate number already exists']);
    exit();
}

// Get current vehicle to check existing photo
$stmt = $pdo->prepare("SELECT vehicle_photo FROM vehicles WHERE id = ?");
$stmt->execute([$id]);
$current = $stmt->fetch();

$vehicle_photo = $current['vehicle_photo'];

// Handle photo upload
if (isset($_FILES['vehicle_photo']) && $_FILES['vehicle_photo']['error'] === UPLOAD_ERR_OK) {
    // Delete old photo
    if ($current['vehicle_photo']) {
        deleteFile($current['vehicle_photo']);
    }
    
    $new_photo = uploadFile($_FILES['vehicle_photo']);
    if ($new_photo) {
        $vehicle_photo = $new_photo;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).']);
        exit();
    }
}

// Update vehicle
$sql = "UPDATE vehicles SET 
    owner_id = ?, plate_number = ?, vehicle_type = ?, brand = ?, color = ?,
    year_model = ?, registration_date = ?, status = ?, vehicle_photo = ?
    WHERE id = ?";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $owner_id, $plate_number, $vehicle_type, $brand, $color,
    $year_model, $registration_date, $status, $vehicle_photo, $id
]);

if ($result) {
    logAudit($_SESSION['user_id'], 'UPDATE', 'vehicles', $id, 
        "Updated vehicle: $plate_number ($vehicle_type)");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update vehicle']);
}
?>