<?php
require_once '../../config.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$pdo = getDB();

// Get record details
$stmt = $pdo->prepare("SELECT * FROM individual_records WHERE id = ?");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: index.php');
    exit();
}

// Log view action
logAudit($_SESSION['user_id'], 'VIEW', 'individual_records', $id, 
    "Viewed citizen: " . $record['first_name'] . ' ' . $record['last_name']);

$creator = getUserName($record['created_by']);

// Get audit trail for this record
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name 
    FROM audit_trails a 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.table_name = 'individual_records' AND a.record_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$stmt->execute([$id]);
$audit_trails = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Citizen - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 1.1rem;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .info-value:last-child {
            border-bottom: none;
        }
        .profile-large {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #ddd;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .detail-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .badge-citizen {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user text-info"></i> Citizen Details
                    </h1>
                    <div>
                        <?php if (hasPermission('inhabitants', 'edit')): ?>
                            <a href="edit.php?id=<?= $record['id'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Profile Card -->
                    <div class="col-md-4">
                        <div class="card detail-card">
                            <div class="card-body text-center">
                                <?php if ($record['profile_picture']): ?>
                                    <img src="../../uploads/<?= $record['profile_picture'] ?>" class="profile-large mb-3" alt="Profile">
                                <?php else: ?>
                                    <img src="../../assets/images/default-avatar.png" class="profile-large mb-3" alt="Default">
                                <?php endif; ?>
                                
                                <h3 class="mb-0">
                                    <?= htmlspecialchars($record['first_name']) ?>
                                    <?= htmlspecialchars($record['last_name']) ?>
                                </h3>
                                <?php if ($record['middle_name']): ?>
                                    <p class="text-muted">Middle: <?= htmlspecialchars($record['middle_name']) ?></p>
                                <?php endif; ?>
                                <?php if ($record['ext_name']): ?>
                                    <span class="badge bg-secondary">EXT: <?= htmlspecialchars($record['ext_name']) ?></span>
                                <?php endif; ?>
                                
                                <hr>
                                
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <small class="text-muted">Age</small>
                                            <h5><?= $record['age'] ?? 'N/A' ?></h5>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-2">
                                            <small class="text-muted">Sex</small>
                                            <h5>
                                                <span class="badge bg-<?= 
                                                    $record['sex'] == 'Male' ? 'primary' : 
                                                    ($record['sex'] == 'Female' ? 'danger' : 'secondary') 
                                                ?>">
                                                    <?= $record['sex'] ?>
                                                </span>
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <span class="badge badge-citizen">
                                        <i class="fas fa-user"></i> Barangay Citizen
                                    </span>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">Record ID: #<?= $record['id'] ?></small><br>
                                    <small class="text-muted">Created by: <?= $creator ?></small><br>
                                    <small class="text-muted">Created: <?= date('M d, Y h:i A', strtotime($record['created_at'])) ?></small>
                                    <?php if ($record['updated_at'] && $record['updated_at'] != $record['created_at']): ?>
                                        <br><small class="text-muted">Updated: <?= date('M d, Y h:i A', strtotime($record['updated_at'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Information Cards -->
                    <div class="col-md-8">
                        <div class="card detail-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-info-circle text-primary"></i> Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="info-label">Full Name</div>
                                        <div class="info-value">
                                            <strong><?= htmlspecialchars($record['last_name']) ?></strong>,
                                            <?= htmlspecialchars($record['first_name']) ?>
                                            <?php if ($record['middle_name']): ?>
                                                <?= htmlspecialchars($record['middle_name'][0]) ?>.
                                            <?php endif; ?>
                                            <?php if ($record['ext_name']): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($record['ext_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Place of Birth</div>
                                        <div class="info-value"><?= htmlspecialchars($record['place_of_birth']) ?: 'N/A' ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Date of Birth</div>
                                        <div class="info-value"><?= $record['date_of_birth'] ? date('F d, Y', strtotime($record['date_of_birth'])) : 'N/A' ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Age</div>
                                        <div class="info-value"><?= $record['age'] ?? 'N/A' ?> years old</div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Sex</div>
                                        <div class="info-value">
                                            <span class="badge bg-<?= 
                                                $record['sex'] == 'Male' ? 'primary' : 
                                                ($record['sex'] == 'Female' ? 'danger' : 'secondary') 
                                            ?>">
                                                <?= $record['sex'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-label">Civil Status</div>
                                        <div class="info-value">
                                            <span class="badge bg-info"><?= $record['civil_status'] ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="info-label">Highest Educational Attainment</div>
                                        <div class="info-value">
                                            <span class="badge bg-success"><?= $record['highest_education'] ?: 'N/A' ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Trail -->
                        <div class="card detail-card mt-3">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-history text-warning"></i> Recent Activity Log</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($audit_trails) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date/Time</th>
                                                    <th>User</th>
                                                    <th>Action</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($audit_trails as $trail): ?>
                                                    <tr>
                                                        <td><?= date('M d, Y h:i A', strtotime($trail['created_at'])) ?></td>
                                                        <td><?= $trail['full_name'] ?? 'System' ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= 
                                                                $trail['action'] == 'CREATE' ? 'success' : 
                                                                ($trail['action'] == 'UPDATE' ? 'warning' : 
                                                                ($trail['action'] == 'DELETE' ? 'danger' : 'secondary')) 
                                                            ?>">
                                                                <?= $trail['action'] ?>
                                                            </span>
                                                        </td>
                                                        <td><?= $trail['details'] ?? '' ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No activity logs found for this record</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>