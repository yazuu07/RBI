<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get summary statistics for reports
$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
$total_individuals = $pdo->query("SELECT COUNT(*) FROM individual_records")->fetchColumn();
$total_male = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Male'")->fetchColumn();
$total_female = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Female'")->fetchColumn();

// Get age distribution for quick stats
$age_stats = $pdo->query("
    SELECT 
        CASE 
            WHEN age <= 17 THEN '0-17'
            WHEN age <= 25 THEN '18-25'
            WHEN age <= 35 THEN '26-35'
            WHEN age <= 45 THEN '36-45'
            WHEN age <= 55 THEN '46-55'
            WHEN age <= 65 THEN '56-65'
            ELSE '65+'
        END as age_group,
        COUNT(*) as count
    FROM individual_records
    WHERE age IS NOT NULL
    GROUP BY age_group
")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .report-card {
            border-radius: 15px;
            padding: 25px;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            height: 100%;
            text-decoration: none;
            display: block;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .report-card .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .report-card .title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .report-card .description {
            font-size: 0.85rem;
            color: #666;
        }
        .report-card .badge-count {
            position: absolute;
            top: 15px;
            right: 15px;
        }
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-file-alt text-primary"></i> Reports
                    </h1>
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.location.reload()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
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
                        <div class="stat-card bg-success text-white">
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
                        <div class="stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($total_male) ?></div>
                                    <div class="stat-label">Male</div>
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
                                    <div class="stat-label">Female</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Cards -->
                <div class="row g-4">
                    <!-- Household Report -->
                    <div class="col-md-4">
                        <a href="household.php" class="report-card bg-white border">
                            <div class="position-relative">
                                <span class="badge bg-success badge-count"><?= number_format($total_households) ?></span>
                                <div class="icon text-success">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="title">Household Report</div>
                                <div class="description">Complete list of all registered households with member details</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><i class="fas fa-print"></i> Printable</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-file-pdf"></i> PDF</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-file-excel"></i> Excel</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Voters List -->
                    <div class="col-md-4">
                        <a href="voters_list.php" class="report-card bg-white border">
                            <div class="position-relative">
                                <span class="badge bg-primary badge-count"><?= number_format($total_individuals) ?></span>
                                <div class="icon text-primary">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                                <div class="title">Voters List</div>
                                <div class="description">List of eligible voters (18+ years old) with their information</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><i class="fas fa-print"></i> Printable</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-file-pdf"></i> PDF</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-file-excel"></i> Excel</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Population By Age -->
                    <div class="col-md-4">
                        <a href="population_by_age.php" class="report-card bg-white border">
                            <div class="position-relative">
                                <span class="badge bg-info badge-count"><?= count($age_stats) ?> Groups</span>
                                <div class="icon text-info">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <div class="title">Population By Age</div>
                                <div class="description">Age distribution analysis with charts and detailed breakdown</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><i class="fas fa-chart-bar"></i> Chart</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-print"></i> Printable</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Population By Sector -->
                    <div class="col-md-4">
                        <a href="population_by_sector.php" class="report-card bg-white border">
                            <div class="position-relative">
                                <span class="badge bg-warning badge-count">Sectors</span>
                                <div class="icon text-warning">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="title">Population By Sector</div>
                                <div class="description">Population distribution by business/employment sectors</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><i class="fas fa-chart-pie"></i> Chart</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-print"></i> Printable</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Population By Street -->
                    <div class="col-md-4">
                        <a href="population_by_street.php" class="report-card bg-white border">
                            <div class="position-relative">
                                <span class="badge bg-danger badge-count">Streets</span>
                                <div class="icon text-danger">
                                    <i class="fas fa-road"></i>
                                </div>
                                <div class="title">Population By Street</div>
                                <div class="description">Population distribution by street/address location</div>
                                <div class="mt-2">
                                    <span class="badge bg-light text-dark"><i class="fas fa-map-marker-alt"></i> Map</span>
                                    <span class="badge bg-light text-dark"><i class="fas fa-print"></i> Printable</span>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Quick Stats -->
                    <div class="col-md-4">
                        <div class="report-card bg-light border">
                            <div class="position-relative">
                                <div class="icon text-secondary">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="title">Quick Statistics</div>
                                <div class="description">At-a-glance demographic summary</div>
                                <div class="mt-3">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="bg-white p-2 rounded text-center border">
                                                <small class="text-muted">Male</small>
                                                <div class="fw-bold"><?= number_format($total_male) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="bg-white p-2 rounded text-center border">
                                                <small class="text-muted">Female</small>
                                                <div class="fw-bold"><?= number_format($total_female) ?></div>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div class="bg-white p-2 rounded text-center border">
                                                <small class="text-muted">Households</small>
                                                <div class="fw-bold"><?= number_format($total_households) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reports Activity -->
                <div class="card mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-clock text-warning"></i> Recent Report Activity</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>User</th>
                                        <th>Report Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT a.*, u.full_name 
                                        FROM audit_trails a 
                                        LEFT JOIN users u ON a.user_id = u.id 
                                        WHERE a.table_name LIKE '%report%' OR a.action = 'GENERATE_REPORT'
                                        ORDER BY a.created_at DESC 
                                        LIMIT 10
                                    ");
                                    $activities = $stmt->fetchAll();
                                    ?>
                                    <?php if (count($activities) > 0): ?>
                                        <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td><?= date('M d, Y h:i A', strtotime($activity['created_at'])) ?></td>
                                                <td><?= $activity['full_name'] ?? 'System' ?></td>
                                                <td><?= ucfirst(str_replace('_', ' ', $activity['table_name'])) ?></td>
                                                <td><span class="badge bg-info"><?= $activity['action'] ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No report activity yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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