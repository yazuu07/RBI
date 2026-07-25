<?php
require_once 'config.php';
requireLogin();

$pdo = getDB();

// Get statistics
$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
$total_individuals = $pdo->query("SELECT COUNT(*) FROM individual_records")->fetchColumn();
$total_male = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Male'")->fetchColumn();
$total_female = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Female'")->fetchColumn();

// Get birthday celebrants today
$birthday_stmt = $pdo->query("
    SELECT CONCAT(last_name, ', ', first_name) as full_name, 
           first_name, last_name, middle_name, ext_name,
           age, profile_picture
    FROM individual_records 
    WHERE DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
    LIMIT 10
");
$birthday_celebrants = $birthday_stmt->fetchAll();
$birthday_today = count($birthday_celebrants);

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
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        .birthday-section {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 25px;
            border-left: 5px solid #fdcb6e;
        }
        .birthday-section .birthday-icon {
            font-size: 2rem;
            color: #fdcb6e;
        }
        .birthday-section .birthday-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #856404;
        }
        .birthday-section .birthday-count {
            background: #fdcb6e;
            color: #2d3436;
            padding: 2px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        .birthday-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fdcb6e;
        }
        .birthday-name {
            font-weight: 600;
            color: #2d3436;
        }
        .rbi-info-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border: 1px solid #e9ecef;
            margin-top: 20px;
        }
        .rbi-info-section h5 {
            color: #2d3436;
            font-weight: 700;
        }
        .rbi-info-section .rbi-icon {
            font-size: 2.5rem;
            color: #6c5ce7;
            margin-right: 15px;
        }
        .rbi-info-section p {
            color: #636e72;
            line-height: 1.8;
            text-align: justify;
        }
        .refresh-btn {
            border-radius: 10px;
            padding: 8px 20px;
        }
        .welcome-banner {
            background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);
            color: white;
            border-radius: 15px;
            padding: 20px 25px;
            margin-bottom: 25px;
        }
        .welcome-banner h4 {
            font-weight: 700;
        }
        .welcome-banner .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .quick-action-btn {
            border-radius: 12px;
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
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
                        <button class="btn btn-secondary me-2 refresh-btn" onclick="window.location.reload()" title="Refresh page">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
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

                <!-- Welcome Banner -->
                <div class="welcome-banner">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4>
                                <i class="fas fa-hand-wave"></i> Welcome back, <strong><?= $_SESSION['full_name'] ?></strong>!
                            </h4>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-calendar-alt"></i> Today is <?= date('l, F d, Y') ?>
                            </p>
                        </div>
                        <div>
                            <span class="role-badge">
                                <i class="fas fa-user-shield"></i> <?= ucfirst($user_role) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($total_households) ?></div>
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
                                    <div class="stat-number"><?= number_format($total_individuals) ?></div>
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
                                    <div class="stat-number"><?= number_format($total_male) ?></div>
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
                                    <div class="stat-number"><?= number_format($total_female) ?></div>
                                    <div class="stat-label">Total Female</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Birthday Celebrants Section -->
                <div class="birthday-section">
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center">
                                <div class="birthday-icon me-3">
                                    <i class="fas fa-birthday-cake"></i>
                                </div>
                                <div>
                                    <div class="birthday-title">
                                        <span class="birthday-count me-2"><?= $birthday_today ?></span>
                                        Citizen(s) celebrating birthday today!
                                    </div>
                                </div>
                            </div>
                            <?php if ($birthday_today > 0): ?>
                                <div class="mt-3">
                                    <strong><i class="fas fa-gift"></i> Today's Birthday Celebrants</strong>
                                    <div class="d-flex flex-wrap gap-3 mt-2">
                                        <?php foreach ($birthday_celebrants as $celebrant): ?>
                                            <div class="d-flex align-items-center bg-white px-3 py-2 rounded-3 shadow-sm">
                                                <?php if ($celebrant['profile_picture']): ?>
                                                    <img src="uploads/<?= $celebrant['profile_picture'] ?>" class="birthday-avatar me-2" alt="Profile">
                                                <?php else: ?>
                                                    <div class="birthday-avatar me-2 d-flex align-items-center justify-content-center bg-secondary text-white" style="font-size: 14px;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="birthday-name"><?= $celebrant['full_name'] ?></span>
                                                <?php if ($celebrant['age']): ?>
                                                    <span class="badge bg-warning text-dark ms-2"><?= $celebrant['age'] ?> yrs</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <p class="mt-2 mb-0 text-muted">
                                    <i class="fas fa-sleep"></i> No birthdays today. Check back tomorrow!
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="inhabitants/citizens/index.php" class="btn btn-outline-primary quick-action-btn">
                                <i class="fas fa-user"></i> View Citizens
                            </a>
                            <a href="inhabitants/households/index.php" class="btn btn-outline-success quick-action-btn">
                                <i class="fas fa-home"></i> View Households
                            </a>
                            <a href="demographic/populations.php" class="btn btn-outline-info quick-action-btn">
                                <i class="fas fa-chart-bar"></i> View Demographics
                            </a>
                            <a href="certification/index.php" class="btn btn-outline-warning quick-action-btn">
                                <i class="fas fa-certificate"></i> Certificates
                            </a>
                            <a href="reports/index.php" class="btn btn-outline-secondary quick-action-btn">
                                <i class="fas fa-file-alt"></i> Reports
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Recent Citizens & Activities -->
                <div class="row">
                    <!-- Recent Citizens -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-user-plus text-primary"></i> Recent Citizens</h5>
                                <a href="inhabitants/citizens/index.php" class="btn btn-sm btn-link">View All</a>
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
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-clock text-warning"></i> Recent Activities</h5>
                                <a href="system/audit.php" class="btn btn-sm btn-link">View All</a>
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

                <!-- What Is RBI Section -->
                <div class="rbi-info-section">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex align-items-center">
                                <div class="rbi-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h5>What Is Registry Of Barangay Inhabitants (RBI)</h5>
                            </div>
                            <p class="mt-3">
                                The <strong>Registry of Barangay Inhabitants (RBI)</strong> is a local government record system used in the Philippines to document and manage information about all residents living within a barangay.
                            </p>
                            <p>
                                It serves as an official database that includes details such as a person's name, address, age, civil status, occupation, and household membership. The RBI is important because it helps barangay officials identify legitimate residents and determine who is eligible for various government programs and services, such as financial assistance, healthcare, and community projects.
                            </p>
                            <p class="mb-0">
                                It also supports planning, policy-making, and maintaining accurate population records. Managed by the barangay office, the RBI ensures that local governance is organized and that resources are properly distributed to the community.
                            </p>
                            <div class="mt-3">
                                <span class="badge bg-primary me-1"><i class="fas fa-check-circle"></i> Accurate Records</span>
                                <span class="badge bg-success me-1"><i class="fas fa-hand-holding-heart"></i> Community Service</span>
                                <span class="badge bg-info me-1"><i class="fas fa-chart-line"></i> Planning & Policy</span>
                                <span class="badge bg-warning text-dark"><i class="fas fa-shield-alt"></i> Legitimate Residents</span>
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