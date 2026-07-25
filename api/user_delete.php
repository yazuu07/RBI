<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        header('Location: users.php');
        exit();
    }
    
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        header('Location: users.php?error=cannot_delete_self');
        exit();
    }
    
    $pdo = getDB();
    
    // Get user details for audit
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: users.php');
        exit();
    }
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAudit($_SESSION['user_id'], 'DELETE', 'users', $id, "Deleted user: " . $user['username']);
    }
    
    header('Location: users.php?success=deleted');
    exit();
}

header('Location: users.php');
exit();
?>