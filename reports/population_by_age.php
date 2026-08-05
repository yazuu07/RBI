<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Age distribution
$age_distribution = $pdo->query("
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
        COUNT(*) as count,
        GROUP_CONCAT(CONCAT(last_name, ', ', first_name) SEPARATOR '; ') as members
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

$total_population = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE age IS NOT NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population By Age - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px double #1a3c6e;
            margin-bottom: 20px;
        }
        .chart-container {
            height: 400px;
            margin: 20px 0;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
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
                        <i class="fas fa-calendar-alt text-info"></i> Population By Age
                    </h1>
                    <div class="btn-toolbar no-print">
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <div class="report-header">
                    <h1>Republic of the Philippines</h1>
                    <h3>Barangay Nayong Kanluran, Quezon City</h3>
                    <h4>POPULATION BY AGE DISTRIBUTION</h4>
                    <p>Generated on: <?= date('F d, Y h:i A') ?></p>
                    <p>Total Population: <?= number_format($total_population) ?></p>
                </div>

                <!-- Chart -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="ageChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table id="ageTable" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>Age Group</th>
                                <th>Count</th>
                                <th>Percentage</th>
                                <th>Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($age_distribution as $group): ?>
                                <?php $percentage = $total_population > 0 ? round(($group['count'] / $total_population) * 100, 1) : 0; ?>
                                <tr>
                                    <td><strong><?= $group['age_group'] ?></strong></td>
                                    <td><?= number_format($group['count']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                <div class="progress-bar bg-<?= 
                                                    $group['age_group'] == '0-17' ? 'info' : 
                                                    ($group['age_group'] == '18-25' ? 'primary' : 
                                                    ($group['age_group'] == '26-35' ? 'success' : 
                                                    ($group['age_group'] == '36-45' ? 'warning' : 
                                                    ($group['age_group'] == '46-55' ? 'orange' : 
                                                    ($group['age_group'] == '56-65' ? 'danger' : 'secondary'))))) 
                                                ?>" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <span><?= $percentage ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= substr($group['members'] ?? '', 0, 100) ?>...</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-footer mt-4 text-center text-muted">
                    <hr>
                    <small>This is a computer-generated report. For official use only.</small>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#ageTable').DataTable({
                pageLength: 25,
                order: [[0, 'asc']]
            });
        });

        // Chart
        const ctx = document.getElementById('ageChart').getContext('2d');
        const ageData = <?= json_encode($age_distribution) ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ageData.map(d => d.age_group),
                datasets: [{
                    label: 'Population',
                    data: ageData.map(d => d.count),
                    backgroundColor: ['#0dcaf0', '#4e73df', '#1cc88a', '#f6c23e', '#fd7e14', '#e74a3b', '#6c757d'],
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
                                let total = <?= $total_population ?>;
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
    </script>
</body>
</html>