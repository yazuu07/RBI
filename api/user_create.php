<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

header('Content-Type: application/json');

$pdo = getDB();

$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validate
if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit();
}
if (empty($password) || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
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

// Check if username exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Username already exists']);
    exit();
}

// Check if email exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$sql = "INSERT INTO users (username, password, full_name, email, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$result = $stmt->execute([$username, $hashed_password, $full_name, $email, $role_id, $is_active]);

if ($result) {
    $id = $pdo->lastInsertId();
    logAudit($_SESSION['user_id'], 'CREATE', 'users', $id, "Created user: $username");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create user']);
}
?>