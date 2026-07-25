<?php
require_once '../../config.php';
requireLogin();
requirePermission('inhabitants', 'delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        header('Location: index.php');
        exit();
    }
    
    $pdo = getDB();
    
    // Get record details for audit
    $stmt = $pdo->prepare("SELECT * FROM individual_records WHERE id = ?");
    $stmt->execute([$id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        header('Location: index.php');
        exit();
    }
    
    // Delete profile picture if exists
    if ($record['profile_picture']) {
        deleteFile($record['profile_picture']);
    }
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM individual_records WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAudit($_SESSION['user_id'], 'DELETE', 'individual_records', $id, 
            "Deleted citizen: " . $record['first_name'] . ' ' . $record['last_name']);
        
        // Clear cache
        clearCache('dashboard_stats');
    }
    
    header('Location: index.php?success=deleted');
    exit();
}

header('Location: index.php');
exit();
?>