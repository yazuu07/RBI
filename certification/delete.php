<?php
require_once '../config.php';
requireLogin();
requirePermission('certification', 'delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        header('Location: index.php');
        exit();
    }
    
    $pdo = getDB();
    
    // Get record details for audit
    $stmt = $pdo->prepare("SELECT * FROM certificates WHERE id = ?");
    $stmt->execute([$id]);
    $certificate = $stmt->fetch();
    
    if (!$certificate) {
        header('Location: index.php');
        exit();
    }
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM certificates WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAudit($_SESSION['user_id'], 'DELETE', 'certificates', $id, 
            "Deleted certificate: " . $certificate['certificate_type'] . ' - ' . $certificate['certificate_number']);
    }
    
    header('Location: index.php?success=deleted');
    exit();
}

header('Location: index.php');
exit();
?>