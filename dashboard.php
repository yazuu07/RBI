<?php
require_once 'config.php';
requireLogin();

$stats = getDashboardStats();
$pdo = getDB();

// Get recent activities
$stmt = $pdo->query("
    SELECT a.*, u.full_name 
    FROM audit_trails a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$recent_activities = $stmt->fetchAll();

// Get recent citizens
$citizens_stmt = $pdo->query("
    SELECT * FROM individual_records 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_citizens = $citizens_stmt->fetchAll();

// Get user role for sidebar
$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-pie text-primary"></i> Dashboard
                    </h1>
                    <div class="btn-toolbar">
                        <?php if (hasPermission('inhabitants', 'add')): ?>
                            <a href="inhabitants/citizens/create.php" class="btn btn-sm btn-primary me-2">
                                <i class="fas fa-user-plus"></i> Add Citizen
                            </a>
                            <a href="inhabitants/households/create.php" class="btn btn-sm btn-success">
                                <i class="fas fa-home-plus"></i> Add Household
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-hand-wave"></i> Welcome back, <strong><?= $_SESSION['full_name'] ?></strong>!
                    <small class="text-muted">(<?= ucfirst($user_role) ?>)</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($stats['total_households'] ?? 0) ?></div>
                                    <div class="stat-label">Total Households</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($stats['total_population'] ?? 0) ?></div>
                                    <div class="stat-label">Total Population</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($stats['total_male'] ?? 0) ?></div>
                                    <div class="stat-label">Total Male</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-mars"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($stats['total_female'] ?? 0) ?></div>
                                    <div class="stat-label">Total Female</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Birthday Celebrants -->
                <?php if (!empty($stats['birthday_celebrants'])): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-birthday-cake"></i> <?= $stats['birthday_today'] ?> Citizen(s) celebrating birthday today!</h5>
                        <div class="mt-2">
                            <?php foreach ($stats['birthday_celebrants'] as $celebrant): ?>
                                <span class="badge bg-warning text-dark p-2 me-1">
                                    <i class="fas fa-gift"></i> <?= $celebrant['full_name'] ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Recent Citizens -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-user-plus text-primary"></i> Recent Citizens</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_citizens) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($recent_citizens as $citizen): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div>
                                                    <?php if ($citizen['profile_picture']): ?>
                                                        <img src="uploads/<?= $citizen['profile_picture'] ?>" class="profile-preview-sm me-2" alt="Profile">
                                                    <?php else: ?>
                                                        <img src="assets/images/default-avatar.png" class="profile-preview-sm me-2" alt="Default">
                                                    <?php endif; ?>
                                                    <strong><?= $citizen['last_name'] ?></strong>, <?= $citizen['first_name'] ?>
                                                </div>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($citizen['created_at'])) ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No citizens recorded yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-clock text-warning"></i> Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_activities) > 0): ?>
                                    <div class="list-group">
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <span class="badge bg-<?= 
                                                            $activity['action'] == 'CREATE' ? 'success' : 
                                                            ($activity['action'] == 'UPDATE' ? 'warning' : 
                                                            ($activity['action'] == 'DELETE' ? 'danger' : 'secondary')) 
                                                        ?>">
                                                            <?= $activity['action'] ?>
                                                        </span>
                                                        <?= $activity['full_name'] ?? 'System' ?>
                                                        <small class="text-muted d-block"><?= $activity['details'] ?? '' ?></small>
                                                    </div>
                                                    <small class="text-muted"><?= date('h:i A', strtotime($activity['created_at'])) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No recent activities.</p>
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