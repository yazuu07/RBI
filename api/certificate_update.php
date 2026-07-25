<?php
require_once '../config.php';
requireLogin();
requirePermission('certification', 'edit');

header('Content-Type: application/json');

$pdo = getDB();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$resident_id = isset($_POST['resident_id']) ? (int)$_POST['resident_id'] : 0;
$certificate_type = isset($_POST['certificate_type']) ? trim($_POST['certificate_type']) : '';
$certificate_number = isset($_POST['certificate_number']) ? trim($_POST['certificate_number']) : '';
$purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : '';
$status = isset($_POST['status']) ? trim($_POST['status']) : 'Pending';
$issued_date = isset($_POST['issued_date']) ? $_POST['issued_date'] : date('Y-m-d');
$expiry_date = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid certificate ID']);
    exit();
}

if ($resident_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a resident']);
    exit();
}

if (empty($certificate_type)) {
    echo json_encode(['success' => false, 'message' => 'Certificate type is required']);
    exit();
}

// Check if certificate number exists for other records
if (!empty($certificate_number)) {
    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE certificate_number = ? AND id != ?");
    $stmt->execute([$certificate_number, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Certificate number already exists']);
        exit();
    }
}

// Update certificate
$sql = "UPDATE certificates SET 
    resident_id = ?, certificate_type = ?, certificate_number = ?,
    purpose = ?, status = ?, issued_date = ?, expiry_date = ?
    WHERE id = ?";

$stmt = $pdo->prepare($sql);
$result = $stmt->execute([
    $resident_id, $certificate_type, $certificate_number,
    $purpose, $status, $issued_date, $expiry_date, $id
]);

if ($result) {
    logAudit($_SESSION['user_id'], 'UPDATE', 'certificates', $id, 
        "Updated certificate: $certificate_type for resident ID $resident_id");
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update certificate']);
}
?>