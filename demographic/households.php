<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();
$user_role = $_SESSION['role'] ?? 'enumerator';

// ============================================
// GET ALL HOUSEHOLD DEMOGRAPHIC DATA
// ============================================

// 1. Total households
$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();

// 2. Household Units (Dwelling Types)
$dwelling_stats = $pdo->query("
    SELECT dwelling_type, COUNT(*) as count 
    FROM household_records 
    WHERE dwelling_type IS NOT NULL AND dwelling_type != ''
    GROUP BY dwelling_type
    ORDER BY count DESC
")->fetchAll();

// 3. Household Types
$type_stats = $pdo->query("
    SELECT household_type, COUNT(*) as count 
    FROM household_records 
    WHERE household_type IS NOT NULL AND household_type != ''
    GROUP BY household_type
    ORDER BY count DESC
")->fetchAll();

// 4. Tenure Status
$tenure_stats = $pdo->query("
    SELECT tenure_status, COUNT(*) as count 
    FROM household_records 
    WHERE tenure_status IS NOT NULL AND tenure_status != ''
    GROUP BY tenure_status
    ORDER BY count DESC
")->fetchAll();

// 5. Gender distribution (Household)
$gender_stats = $pdo->query("
    SELECT sex, COUNT(*) as count 
    FROM household_records 
    GROUP BY sex
")->fetchAll();

// 6. Civil Status distribution (Household)
$civil_stats = $pdo->query("
    SELECT civil_status, COUNT(*) as count 
    FROM household_records 
    GROUP BY civil_status
")->fetchAll();

// 7. Position distribution
$position_stats = $pdo->query("
    SELECT position_in_household, COUNT(*) as count 
    FROM household_records 
    WHERE position_in_household IS NOT NULL AND position_in_household != ''
    GROUP BY position_in_household
    ORDER BY count DESC
    LIMIT 10
")->fetchAll();

// 8. Monthly Income ranges

// 9. Top 5 household names
$name_stats = $pdo->query("
    SELECT household_name, COUNT(*) as count 
    FROM household_records 
    WHERE household_name IS NOT NULL AND household_name != ''
    GROUP BY household_name
    ORDER BY count DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Households Demographic - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .chart-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .chart-container-sm {
            height: 250px;
        }
        .refresh-btn {
            border-radius: 10px;
            padding: 8px 20px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-item .label {
            font-weight: 500;
        }
        .stat-item .badge-count {
            font-size: 0.8rem;
            padding: 3px 10px;
        }
        .progress {
            height: 20px;
            border-radius: 10px;
        }
        .progress-bar {
            border-radius: 10px;
            transition: width 1s ease;
            font-size: 0.75rem;
            line-height: 20px;
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
                        <i class="fas fa-home text-success"></i> Households Demographic
                        <span class="badge bg-success ms-2"><?= number_format($total_households) ?> Total</span>
                    </h1>
                    <button class="btn btn-secondary refresh-btn" onclick="window.location.reload()" title="Refresh page">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
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
                </div>

                <!-- Row 1: Household Units & Types -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-building text-primary"></i> Household Units (Dwelling Types)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="dwellingChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_dwelling = array_sum(array_column($dwelling_stats, 'count'));
                                    foreach ($dwelling_stats as $stat): 
                                        $percent = $total_dwelling > 0 ? round(($stat['count'] / $total_dwelling) * 100) : 0;
                                    ?>
                                        <div class="stat-item">
                                            <span class="label"><?= htmlspecialchars($stat['dwelling_type']) ?></span>
                                            <div class="d-flex align-items-center" style="flex: 1; margin: 0 10px;">
                                                <div class="progress w-100">
                                                    <div class="progress-bar bg-primary" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $stat['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-users text-info"></i> Household Types</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="typeChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_type = array_sum(array_column($type_stats, 'count'));
                                    foreach ($type_stats as $stat): 
                                        $percent = $total_type > 0 ? round(($stat['count'] / $total_type) * 100) : 0;
                                    ?>
                                        <div class="stat-item">
                                            <span class="label"><?= htmlspecialchars($stat['household_type']) ?></span>
                                            <div class="d-flex align-items-center" style="flex: 1; margin: 0 10px;">
                                                <div class="progress w-100">
                                                    <div class="progress-bar bg-info" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $stat['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Tenure Status & Income Ranges -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-handshake text-warning"></i> Tenure Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="tenureChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_tenure = array_sum(array_column($tenure_stats, 'count'));
                                    foreach ($tenure_stats as $stat): 
                                        $percent = $total_tenure > 0 ? round(($stat['count'] / $total_tenure) * 100) : 0;
                                    ?>
                                        <div class="stat-item">
                                            <span class="label"><?= htmlspecialchars($stat['tenure_status']) ?></span>
                                            <div class="d-flex align-items-center" style="flex: 1; margin: 0 10px;">
                                                <div class="progress w-100">
                                                    <div class="progress-bar bg-warning" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $stat['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ============================================
        // COLOR PALETTE
        // ============================================
        const colors = {
            blue: '#4e73df',
            green: '#1cc88a',
            red: '#e74a3b',
            yellow: '#f6c23e',
            purple: '#6f42c1',
            orange: '#fd7e14',
            teal: '#20c997',
            pink: '#e83e8c',
            indigo: '#6610f2',
            cyan: '#0dcaf0',
            dark: '#343a40'
        };

        const colorPalette = [
            colors.blue, colors.green, colors.red, colors.yellow, 
            colors.purple, colors.orange, colors.teal, colors.pink,
            colors.indigo, colors.cyan
        ];

        // ============================================
        // DATA FROM PHP
        // ============================================
        const dwellingData = <?= json_encode($dwelling_stats) ?>;
        const typeData = <?= json_encode($type_stats) ?>;
        const tenureData = <?= json_encode($tenure_stats) ?>;

        // ============================================
        // 1. DWELLING TYPE CHART
        // ============================================
        const dwellingCtx = document.getElementById('dwellingChart').getContext('2d');
        new Chart(dwellingCtx, {
            type: 'bar',
            data: {
                labels: dwellingData.map(d => d.dwelling_type),
                datasets: [{
                    label: 'Households',
                    data: dwellingData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, dwellingData.length),
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // ============================================
        // 2. HOUSEHOLD TYPE CHART
        // ============================================
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        new Chart(typeCtx, {
            type: 'pie',
            data: {
                labels: typeData.map(d => d.household_type),
                datasets: [{
                    data: typeData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, typeData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // ============================================
        // 3. TENURE STATUS CHART
        // ============================================
        const tenureCtx = document.getElementById('tenureChart').getContext('2d');
        new Chart(tenureCtx, {
            type: 'doughnut',
            data: {
                labels: tenureData.map(d => d.tenure_status),
                datasets: [{
                    data: tenureData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, tenureData.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>