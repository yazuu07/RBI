<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();
$user_role = $_SESSION['role'] ?? 'enumerator';

// ============================================
// GET ALL DATA DIRECTLY FROM DATABASE (NO AJAX)
// ============================================

// 1. Total statistics
$total_individuals = $pdo->query("SELECT COUNT(*) FROM individual_records")->fetchColumn();
$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
$total_male = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Male'")->fetchColumn();
$total_female = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE sex = 'Female'")->fetchColumn();

// 2. Gender distribution (Individual)
$gender_stats = $pdo->query("
    SELECT sex, COUNT(*) as count 
    FROM individual_records 
    GROUP BY sex
")->fetchAll();

// 3. Civil status distribution
$civil_stats = $pdo->query("
    SELECT civil_status, COUNT(*) as count 
    FROM individual_records 
    GROUP BY civil_status
")->fetchAll();

// 4. Education distribution
$education_stats = $pdo->query("
    SELECT highest_education, COUNT(*) as count 
    FROM individual_records 
    WHERE highest_education IS NOT NULL
    GROUP BY highest_education
")->fetchAll();

// 5. Age distribution
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
    ORDER BY 
        CASE 
            WHEN age_group = '0-17' THEN 1
            WHEN age_group = '18-25' THEN 2
            WHEN age_group = '26-35' THEN 3
            WHEN age_group = '36-45' THEN 4
            WHEN age_group = '46-55' THEN 5
            WHEN age_group = '56-65' THEN 6
            ELSE 7
        END
")->fetchAll();

// 6. Citizenship distribution
$citizenship_stats = $pdo->query("
    SELECT citizenship, COUNT(*) as count 
    FROM household_records 
    WHERE citizenship IS NOT NULL AND citizenship != ''
    GROUP BY citizenship
    ORDER BY count DESC
    LIMIT 10
")->fetchAll();

// 7. Birthday today
$birthday_stmt = $pdo->query("
    SELECT CONCAT(last_name, ', ', first_name) as full_name 
    FROM individual_records 
    WHERE DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
    LIMIT 5
");
$birthday_celebrants = $birthday_stmt->fetchAll();
$birthday_today = count($birthday_celebrants);

// 8. Monthly registrations (last 12 months)
$monthly_stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM individual_records 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stats = $monthly_stmt->fetchAll();

// 9. Age ranges for display
$age_ranges = [];
foreach ($age_stats as $age) {
    $age_ranges[$age['age_group']] = $age['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population Demographic - RBIS</title>
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
        .refresh-btn {
            border-radius: 10px;
            padding: 8px 20px;
        }
        .birthday-badge {
            background: #fff3cd;
            color: #856404;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        .birthday-badge i {
            color: #ffc107;
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
                        <i class="fas fa-users text-primary"></i> Population Demographic
                    </h1>
                    <button class="btn btn-secondary refresh-btn" onclick="window.location.reload()" title="Refresh page">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>

                <!-- Birthday Celebrants -->
                <?php if ($birthday_today > 0): ?>
                    <div class="alert alert-warning birthday-badge mb-3">
                        <i class="fas fa-birthday-cake"></i> 
                        <strong><?= $birthday_today ?></strong> citizen(s) celebrating birthday today!
                        <?php foreach ($birthday_celebrants as $celebrant): ?>
                            <span class="badge bg-warning text-dark ms-1"><?= $celebrant['full_name'] ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Cards -->
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
                </div>

                <!-- Charts Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-venus-mars text-primary"></i> Gender Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="genderChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_gender = array_sum(array_column($gender_stats, 'count'));
                                    foreach ($gender_stats as $gender): 
                                        $percent = $total_gender > 0 ? round(($gender['count'] / $total_gender) * 100) : 0;
                                        $color = $gender['sex'] == 'Male' ? 'primary' : ($gender['sex'] == 'Female' ? 'danger' : 'secondary');
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= $gender['sex'] ?: 'Unknown' ?></span>
                                            <div class="w-75">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $gender['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-ring text-info"></i> Civil Status Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="civilChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_civil = array_sum(array_column($civil_stats, 'count'));
                                    foreach ($civil_stats as $civil): 
                                        $percent = $total_civil > 0 ? round(($civil['count'] / $total_civil) * 100) : 0;
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= $civil['civil_status'] ?></span>
                                            <div class="w-75">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-info" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $civil['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt text-warning"></i> Age Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="ageChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_age = array_sum($age_ranges);
                                    foreach ($age_ranges as $range => $count): 
                                        $percent = $total_age > 0 ? round(($count / $total_age) * 100) : 0;
                                        $colors = ['info', 'primary', 'success', 'warning', 'orange', 'danger', 'secondary'];
                                        $idx = array_search($range, array_keys($age_ranges));
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= $range ?></span>
                                            <div class="w-75">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-<?= $colors[$idx] ?? 'secondary' ?>" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $count ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-graduation-cap text-success"></i> Education Level</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="educationChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_edu = array_sum(array_column($education_stats, 'count'));
                                    foreach ($education_stats as $edu): 
                                        $percent = $total_edu > 0 ? round(($edu['count'] / $total_edu) * 100) : 0;
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= $edu['highest_education'] ?: 'Unknown' ?></span>
                                            <div class="w-75">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $edu['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 3 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-passport text-primary"></i> Citizenship Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="citizenshipChart"></canvas>
                                </div>
                                <div class="mt-3">
                                    <?php 
                                    $total_cit = array_sum(array_column($citizenship_stats, 'count'));
                                    foreach ($citizenship_stats as $cit): 
                                        $percent = $total_cit > 0 ? round(($cit['count'] / $total_cit) * 100) : 0;
                                    ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span><?= $cit['citizenship'] ?></span>
                                            <div class="w-75">
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-primary" style="width: <?= $percent ?>%">
                                                        <?= $percent ?>%
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge bg-secondary"><?= $cit['count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line text-danger"></i> Monthly Registrations</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="monthlyChart"></canvas>
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
        const genderData = <?= json_encode($gender_stats) ?>;
        const civilData = <?= json_encode($civil_stats) ?>;
        const educationData = <?= json_encode($education_stats) ?>;
        const ageData = <?= json_encode($age_stats) ?>;
        const citizenshipData = <?= json_encode($citizenship_stats) ?>;
        const monthlyData = <?= json_encode($monthly_stats) ?>;

        // ============================================
        // 1. GENDER CHART
        // ============================================
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: genderData.map(d => d.sex || 'Unknown'),
                datasets: [{
                    data: genderData.map(d => d.count),
                    backgroundColor: ['#4e73df', '#e74a3b', '#6c757d'],
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
        // 2. CIVIL STATUS CHART
        // ============================================
        const civilCtx = document.getElementById('civilChart').getContext('2d');
        new Chart(civilCtx, {
            type: 'pie',
            data: {
                labels: civilData.map(d => d.civil_status),
                datasets: [{
                    data: civilData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, civilData.length),
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
        // 3. AGE CHART
        // ============================================
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        const ageLabels = ageData.map(d => d.age_group);
        const ageValues = ageData.map(d => d.count);
        const ageColors = ['#0dcaf0', '#4e73df', '#1cc88a', '#f6c23e', '#fd7e14', '#e74a3b', '#6c757d'];

        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageLabels,
                datasets: [{
                    label: 'Population',
                    data: ageValues,
                    backgroundColor: ageColors.slice(0, ageLabels.length),
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let total = <?= $total_individuals ?>;
                                let percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return context.parsed.y + ' people (' + percentage + '%)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // ============================================
        // 4. EDUCATION CHART
        // ============================================
        const eduCtx = document.getElementById('educationChart').getContext('2d');
        const eduLabels = educationData.map(d => d.highest_education || 'Unknown');
        const eduValues = educationData.map(d => d.count);

        new Chart(eduCtx, {
            type: 'bar',
            data: {
                labels: eduLabels,
                datasets: [{
                    label: 'Count',
                    data: eduValues,
                    backgroundColor: ['#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#6f42c1', '#20c997'],
                    borderColor: '#fff',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // ============================================
        // 5. CITIZENSHIP CHART
        // ============================================
        const citCtx = document.getElementById('citizenshipChart').getContext('2d');
        const citLabels = citizenshipData.map(d => d.citizenship);
        const citValues = citizenshipData.map(d => d.count);

        new Chart(citCtx, {
            type: 'doughnut',
            data: {
                labels: citLabels,
                datasets: [{
                    data: citValues,
                    backgroundColor: colorPalette.slice(0, citLabels.length),
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
        // 6. MONTHLY REGISTRATIONS CHART
        // ============================================
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthLabels = monthlyData.map(d => d.month);
        const monthValues = monthlyData.map(d => d.count);

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'New Registrations',
                    data: monthValues,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>