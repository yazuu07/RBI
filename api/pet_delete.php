<?php
require_once '../config.php';
requireLogin();
requirePermission('extras', 'delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        header('Location: pets.php');
        exit();
    }
    
    $pdo = getDB();
    
    // Get record details for audit
    $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->execute([$id]);
    $pet = $stmt->fetch();
    
    if (!$pet) {
        header('Location: pets.php');
        exit();
    }
    
    // Delete photo if exists
    if ($pet['pet_photo']) {
        deleteFile($pet['pet_photo']);
    }
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAudit($_SESSION['user_id'], 'DELETE', 'pets', $id, 
            "Deleted pet: " . $pet['pet_name']);
    }
    
    header('Location: pets.php?success=deleted');
    exit();
}

header('Location: pets.php');
exit();
?>