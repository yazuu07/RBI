<?php
require_once '../config.php';
requireLogin();
requirePermission('extras', 'delete');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id <= 0) {
        header('Location: vehicles.php');
        exit();
    }
    
    $pdo = getDB();
    
    // Get record details for audit
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch();
    
    if (!$vehicle) {
        header('Location: vehicles.php');
        exit();
    }
    
    // Delete photo if exists
    if ($vehicle['vehicle_photo']) {
        deleteFile($vehicle['vehicle_photo']);
    }
    
    // Delete record
    $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
    if ($stmt->execute([$id])) {
        logAudit($_SESSION['user_id'], 'DELETE', 'vehicles', $id, 
            "Deleted vehicle: " . $vehicle['plate_number']);
    }
    
    header('Location: vehicles.php?success=deleted');
    exit();
}

header('Location: vehicles.php');
exit();
?>