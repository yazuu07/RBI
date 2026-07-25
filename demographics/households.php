<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();
$user_role = $_SESSION['role'] ?? 'enumerator';

// Get household-specific statistics
$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();

// Get household by civil status
$civil_stmt = $pdo->query("
    SELECT civil_status, COUNT(*) as count 
    FROM household_records 
    GROUP BY civil_status
");
$civil_stats = $civil_stmt->fetchAll();

// Get household by citizenship
$citizenship_stmt = $pdo->query("
    SELECT citizenship, COUNT(*) as count 
    FROM household_records 
    WHERE citizenship IS NOT NULL AND citizenship != ''
    GROUP BY citizenship
    ORDER BY count DESC
");
$citizenship_stats = $citizenship_stmt->fetchAll();

// Get household by occupation
$occupation_stmt = $pdo->query("
    SELECT occupation, COUNT(*) as count 
    FROM household_records 
    WHERE occupation IS NOT NULL AND occupation != ''
    GROUP BY occupation
    ORDER BY count DESC
    LIMIT 15
");
$occupation_stats = $occupation_stmt->fetchAll();

// Get household by sex
$sex_stmt = $pdo->query("
    SELECT sex, COUNT(*) as count 
    FROM household_records 
    GROUP BY sex
");
$sex_stats = $sex_stmt->fetchAll();

// Get households with pets
$pets_stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN pets IS NULL OR pets = '' THEN 'No Pets'
            ELSE 'Has Pets'
        END as pet_status,
        COUNT(*) as count
    FROM household_records 
    GROUP BY pet_status
");
$pets_stats = $pets_stmt->fetchAll();

// Get households with disability
$disability_stmt = $pdo->query("
    SELECT 
        CASE 
            WHEN disability IS NULL OR disability = '' THEN 'No Disability'
            ELSE 'Has Disability'
        END as disability_status,
        COUNT(*) as count
    FROM household_records 
    GROUP BY disability_status
");
$disability_stats = $disability_stmt->fetchAll();

// Get household age distribution
$age_stmt = $pdo->query("
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
    FROM household_records
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
");
$age_stats = $age_stmt->fetchAll();
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
                    </h1>
                    <span class="badge bg-success">Total: <?= number_format($total_households) ?> Households</span>
                </div>

                <!-- Charts Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-venus-mars text-primary"></i> Gender Distribution (Household)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="genderChart"></canvas>
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-calendar-alt text-warning"></i> Age Distribution (Household)</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="ageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-passport text-primary"></i> Citizenship Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="citizenshipChart"></canvas>
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
                                <h5 class="mb-0"><i class="fas fa-briefcase text-warning"></i> Top Occupations</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="occupationChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card chart-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-paw text-primary"></i> Pets & Disability Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="chart-container-sm">
                                            <canvas id="petsChart"></canvas>
                                        </div>
                                        <p class="text-center text-muted small">Pets Status</p>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="chart-container-sm">
                                            <canvas id="disabilityChart"></canvas>
                                        </div>
                                        <p class="text-center text-muted small">Disability Status</p>
                                    </div>
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
        // Colors
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
            cyan: '#0dcaf0'
        };

        const colorPalette = [
            colors.blue, colors.green, colors.red, colors.yellow, 
            colors.purple, colors.orange, colors.teal, colors.pink,
            colors.indigo, colors.cyan
        ];

        // 1. Gender Chart
        const genderData = <?= json_encode($sex_stats) ?>;
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

        // 2. Civil Status Chart
        const civilData = <?= json_encode($civil_stats) ?>;
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

        // 3. Age Chart
        const ageData = <?= json_encode($age_stats) ?>;
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ageData.map(d => d.age_group),
                datasets: [{
                    label: 'Household Members',
                    data: ageData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, ageData.length),
                    borderColor: '#fff',
                    borderWidth: 1
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

        // 4. Citizenship Chart
        const citizenshipData = <?= json_encode($citizenship_stats) ?>;
        const citizenshipCtx = document.getElementById('citizenshipChart').getContext('2d');
        new Chart(citizenshipCtx, {
            type: 'doughnut',
            data: {
                labels: citizenshipData.map(d => d.citizenship),
                datasets: [{
                    data: citizenshipData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, citizenshipData.length),
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

        // 5. Occupation Chart (Horizontal Bar)
        const occupationData = <?= json_encode($occupation_stats) ?>;
        const occupationCtx = document.getElementById('occupationChart').getContext('2d');
        new Chart(occupationCtx, {
            type: 'horizontalBar',
            data: {
                labels: occupationData.map(d => d.occupation),
                datasets: [{
                    label: 'Count',
                    data: occupationData.map(d => d.count),
                    backgroundColor: colorPalette.slice(0, occupationData.length),
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // 6. Pets Chart
        const petsData = <?= json_encode($pets_stats) ?>;
        const petsCtx = document.getElementById('petsChart').getContext('2d');
        new Chart(petsCtx, {
            type: 'doughnut',
            data: {
                labels: petsData.map(d => d.pet_status),
                datasets: [{
                    data: petsData.map(d => d.count),
                    backgroundColor: ['#1cc88a', '#e74a3b'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    },
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

        // 7. Disability Chart
        const disabilityData = <?= json_encode($disability_stats) ?>;
        const disabilityCtx = document.getElementById('disabilityChart').getContext('2d');
        new Chart(disabilityCtx, {
            type: 'doughnut',
            data: {
                labels: disabilityData.map(d => d.disability_status),
                datasets: [{
                    data: disabilityData.map(d => d.count),
                    backgroundColor: ['#4e73df', '#f6c23e'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: { boxWidth: 10, font: { size: 10 } }
                    },
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