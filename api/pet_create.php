<?php
require_once '../config.php';
requireLogin();
requirePermission('extras', 'add');

header('Content-Type: application/json');

$pdo = getDB();

$owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
$pet_name = isset($_POST['pet_name']) ? trim($_POST['pet_name']) : '';
$pet_type = isset($_POST['pet_type']) ? trim($_POST['pet_type']) : '';
$breed = isset($_POST['breed']) ? trim($_POST['breed']) : '';
$color = isset($_POST['color']) ? trim($_POST['color']) : '';
$age = isset($_POST['age']) ? (int)$_POST['age'] : null;
$gender = isset($_POST['gender']) ? trim($_POST['gender']) : 'Male';
$weight = isset($_POST['weight']) ? (float)$_POST['weight'] : null;
$microchip_number = isset($_POST['microchip_number']) ? trim($_POST['microchip_number']) : '';
$vaccination_status = isset($_POST['vaccination_status']) ? trim($_POST['vaccination_status']) : 'None';
$registration_date = isset($_POST['registration_date']) ? $_POST['registration_date'] : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Active';

// Validate
if (empty($pet_name)) {
    echo json_encode(['success' => false, 'message' => 'Pet name is required']);
    exit();
}

if (empty($pet_type)) {
    echo json_encode(['success' => false, 'message' => 'Pet type is required']);
    exit();
}

if ($owner_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select an owner']);
    exit();
}

// Handle photo upload
$pet_photo = null;
if (isset($_FILES['pet_photo']) && $_FILES['pet_photo']['error'] === UPLOAD_ERR_OK) {
    $pet_photo = uploadFile($_FILES['pet_photo']);
    if (!$pet_photo) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, PNG, GIF allowed (max 2MB).']);
        exit();
    }
}

// Insert pet
$sql = "INSERT INTO pets (
    owner_id, pet_name, pet_type, breed, color, age, gender, weight,
    microchip_number, vaccination_status, registration_date, status, pet_photo, created_by
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $owner_id, $pet_name, $pet_type, $breed, $color, $age, $gender, $weight,
    $microchip_number, $vaccination_status, $registration_date, $status, $pet_photo, $_SESSION['user_id']
]);

if ($result) {
    $id = $pdo->lastInsertId();
    logAudit($_SESSION['user_id'], 'CREATE', 'pets', $id, 
        "Added pet: $pet_name ($pet_type)");
    
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save pet']);
}
?>