<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

header('Content-Type: application/json');

$pdo = getDB();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
$password = isset($_POST['password']) ? $_POST['password'] : '';

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

// Validate
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit();
}
if (empty($full_name)) {
    echo json_encode(['success' => false, 'message' => 'Full name is required']);
    exit();
}
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit();
}
if ($role_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Role is required']);
    exit();
}

// Check if username exists for other users
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit();
}

// Check if email exists for other users
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

// Build update query
$sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role_id = ?, is_active = ?";
$params = [$username, $full_name, $email, $role_id, $is_active];

if (!empty($password)) {
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        exit();
    }
    $sql .= ", password = ?";
    $params[] = password_hash($password, PASSWORD_DEFAULT);
}

$sql .= " WHERE id = ?";
$params[] = $id;

$stmt = $pdo->prepare($sql);
$result = $stmt->execute($params);

if ($result) {
    logAudit($_SESSION['user_id'], 'UPDATE', 'users', $id, "Updated user: $username");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update user']);
}
?>