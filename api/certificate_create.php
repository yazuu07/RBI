<?php
require_once '../config.php';
requireLogin();
requirePermission('certification', 'add');

header('Content-Type: application/json');

$pdo = getDB();

$resident_id = isset($_POST['resident_id']) ? (int)$_POST['resident_id'] : 0;
$certificate_type = isset($_POST['certificate_type']) ? trim($_POST['certificate_type']) : '';
$certificate_number = isset($_POST['certificate_number']) ? trim($_POST['certificate_number']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Pending';
$issued_date = isset($_POST['issued_date']) ? $_POST['issued_date'] : date('Y-m-d');
$expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

// Validate
if ($resident_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a resident']);
    exit();
}

if (empty($certificate_type)) {
    echo json_encode(['success' => false, 'message' => 'Certificate type is required']);
    exit();
}

// Generate certificate number if not provided
if (empty($certificate_number)) {
    $prefix = 'BRGY';
    $year = date('Y');
    $stmt = $pdo->query("SELECT COUNT(*) FROM certificates WHERE YEAR(created_at) = $year");
    $count = $stmt->fetchColumn() + 1;
    $certificate_number = $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// Check if certificate number already exists
if (!empty($certificate_number)) {
    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE certificate_number = ?");
    $stmt->execute([$certificate_number]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Certificate number already exists']);
        exit();
    }
}

// Insert certificate
$sql = "INSERT INTO certificates (
    resident_id, certificate_type, certificate_number, purpose, 
    status, issued_date, expiry_date, issued_by
) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $resident_id, $certificate_type, $certificate_number, $purpose,
    $status, $issued_date, $expiry_date, $_SESSION['user_id']
]);

if ($result) {
    $id = $pdo->lastInsertId();
    logAudit($_SESSION['user_id'], 'CREATE', 'certificates', $id, 
        "Issued certificate: $certificate_type for resident ID $resident_id");
    
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save certificate']);
}
?>