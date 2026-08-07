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

// Decode sectors
$sector_list = !empty($record['sectors']) ? explode(',', $record['sectors']) : [];
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
        .profile-large {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ddd;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .detail-card {
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            margin-bottom: 15px;
        }
        .detail-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .detail-card .card-header i {
            margin-right: 8px;
            width: 20px;
        }
        .detail-card .card-body {
            padding: 12px 15px;
        }
        .info-row {
            display: flex;
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 0.9rem;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            width: 140px;
            flex-shrink: 0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .sector-badge {
            padding: 3px 10px;
            border-radius: 15px;
            background: #e7f1ff;
            color: #0d6efd;
            font-size: 0.75rem;
            display: inline-block;
            margin: 2px;
        }
        .sector-badge.other {
            background: #fff3cd;
            color: #856404;
        }
        .profile-card {
            text-align: center;
            padding: 20px 15px;
        }
        .profile-card .name {
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 10px;
        }
        .profile-card .sub-info {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .profile-card .badge-role {
            padding: 4px 15px;
            border-radius: 20px;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-citizen {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        @media (max-width: 768px) {
            .two-col-grid {
                grid-template-columns: 1fr;
            }
            .info-row {
                flex-direction: column;
                padding: 8px 0;
            }
            .info-label {
                width: 100%;
                margin-bottom: 2px;
            }
            .profile-large {
                width: 130px;
                height: 130px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <!-- Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h4">
                        <i class="fas fa-user text-info"></i> Citizen Details
                        <span class="badge bg-secondary ms-2">#<?= $record['id'] ?></span>
                    </h1>
                    <div>
                        <?php if (hasPermission('inhabitants', 'edit')): ?>
                            <a href="edit.php?id=<?= $record['id'] ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- LEFT COLUMN - Profile & Quick Info -->
                    <div class="col-md-3">
                        <div class="card detail-card">
                            <div class="profile-card">
                                <?php if ($record['profile_picture']): ?>
                                    <img src="../../uploads/<?= $record['profile_picture'] ?>" class="profile-large" alt="Profile">
                                <?php else: ?>
                                    <img src="../../assets/images/default-avatar.png" class="profile-large" alt="Default">
                                <?php endif; ?>
                                
                                <div class="name">
                                    <?= htmlspecialchars($record['first_name']) ?>
                                    <?= htmlspecialchars($record['last_name']) ?>
                                </div>
                                
                                <?php if ($record['middle_name']): ?>
                                    <div class="sub-info"><?= htmlspecialchars($record['middle_name']) ?></div>
                                <?php endif; ?>
                                
                                <div class="mt-2">
                                    <span class="badge badge-citizen"><i class="fas fa-user"></i> Citizen</span>
                                    <?php if ($record['position_in_household']): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($record['position_in_household']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <hr class="my-2">
                                
                                <div class="row g-1">
                                    <div class="col-6">
                                        <div class="border rounded p-1">
                                            <small class="text-muted d-block">Age</small>
                                            <strong><?= $record['age'] ?? 'N/A' ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="border rounded p-1">
                                            <small class="text-muted d-block">Sex</small>
                                            <span class="badge bg-<?= $record['sex'] == 'Male' ? 'primary' : ($record['sex'] == 'Female' ? 'danger' : 'secondary') ?>">
                                                <?= $record['sex'] ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-2">
                                
                                <div class="text-start small">
                                    <div><span class="text-muted">Created:</span> <?= date('M d, Y', strtotime($record['created_at'])) ?></div>
                                    <div><span class="text-muted">By:</span> <?= $creator ?></div>
                                    <?php if ($record['updated_at'] && $record['updated_at'] != $record['created_at']): ?>
                                        <div><span class="text-muted">Updated:</span> <?= date('M d, Y', strtotime($record['updated_at'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN - All Details -->
                    <div class="col-md-9">
                        <div class="two-col-grid">
                            <!-- Personal Information -->
                            <div class="card detail-card">
                                <div class="card-header"><i class="fas fa-user text-primary"></i> Personal</div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-value">
                                            <strong><?= htmlspecialchars($record['last_name']) ?></strong>,
                                            <?= htmlspecialchars($record['first_name']) ?>
                                            <?php if ($record['middle_name']): ?>
                                                <?= htmlspecialchars($record['middle_name'][0]) ?>.
                                            <?php endif; ?>
                                            <?php if ($record['ext_name']): ?>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($record['ext_name']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Birthdate</span>
                                        <span class="info-value"><?= $record['date_of_birth'] ? date('F d, Y', strtotime($record['date_of_birth'])) : 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Birth Place</span>
                                        <span class="info-value"><?= htmlspecialchars($record['place_of_birth']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Civil Status</span>
                                        <span class="info-value"><span class="badge bg-info"><?= $record['civil_status'] ?></span></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Education</span>
                                        <span class="info-value"><?= $record['highest_education'] ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Educ. Status</span>
                                        <span class="info-value"><?= htmlspecialchars($record['educational_status']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Profession</span>
                                        <span class="info-value"><?= htmlspecialchars($record['profession']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">PhilSys</span>
                                        <span class="info-value"><?= htmlspecialchars($record['philsys_number']) ?: 'N/A' ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Details -->
                            <div class="card detail-card">
                                <div class="card-header"><i class="fas fa-address-card text-success"></i> Contact</div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Mobile</span>
                                        <span class="info-value"><?= htmlspecialchars($record['mobile_number']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Email</span>
                                        <span class="info-value"><?= htmlspecialchars($record['email']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Telephone</span>
                                        <span class="info-value"><?= htmlspecialchars($record['telephone_number']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Region</span>
                                        <span class="info-value"><?= htmlspecialchars($record['region']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Province</span>
                                        <span class="info-value"><?= htmlspecialchars($record['province']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">City/Municipality</span>
                                        <span class="info-value"><?= htmlspecialchars($record['city_municipality']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Barangay</span>
                                        <span class="info-value"><?= htmlspecialchars($record['barangay_address']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">House/Block/Lot</span>
                                        <span class="info-value"><?= htmlspecialchars($record['house_address']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Street</span>
                                        <span class="info-value"><?= htmlspecialchars($record['street']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Subdivision</span>
                                        <span class="info-value"><?= htmlspecialchars($record['subdivision']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Zip Code</span>
                                        <span class="info-value"><?= htmlspecialchars($record['zip_code']) ?: 'N/A' ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Identity Information -->
                            <div class="card detail-card">
                                <div class="card-header"><i class="fas fa-id-card text-info"></i> Identity</div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Blood Type</span>
                                        <span class="info-value"><?= htmlspecialchars($record['blood_type']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Weight</span>
                                        <span class="info-value"><?= $record['weight'] ? $record['weight'] . ' kg' : 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Height</span>
                                        <span class="info-value"><?= htmlspecialchars($record['height']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Citizenship</span>
                                        <span class="info-value"><?= htmlspecialchars($record['citizenship']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Ethnicity</span>
                                        <span class="info-value"><?= htmlspecialchars($record['ethnicity']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Registered Voter</span>
                                        <span class="info-value">
                                            <?= $record['registered_voter'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Voter (non-resident)</span>
                                        <span class="info-value">
                                            <?= $record['voter_not_resident'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Has Pet</span>
                                        <span class="info-value">
                                            <?= $record['has_pet'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Position</span>
                                        <span class="info-value"><?= htmlspecialchars($record['position_in_household']) ?: 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Mother's Maiden</span>
                                        <span class="info-value"><?= htmlspecialchars($record['mother_maiden_name']) ?: 'N/A' ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Sectoral Information -->
                            <div class="card detail-card">
                                <div class="card-header"><i class="fas fa-building text-warning"></i> Sectoral</div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="info-label">Sectors</span>
                                        <span class="info-value">
                                            <?php if (!empty($sector_list) || !empty($record['sector_other'])): ?>
                                                <?php foreach ($sector_list as $sector): ?>
                                                    <?php if (trim($sector)): ?>
                                                        <span class="sector-badge"><?= htmlspecialchars(trim($sector)) ?></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if ($record['sector_other']): ?>
                                                    <span class="sector-badge other"><?= htmlspecialchars($record['sector_other']) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">None selected</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Trail - Full Width -->
                        <div class="card detail-card">
                            <div class="card-header"><i class="fas fa-history text-secondary"></i> Activity Log</div>
                            <div class="card-body">
                                <?php if (count($audit_trails) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
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