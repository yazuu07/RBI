<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get sector distribution based on occupation
$sector_data = $pdo->query("
    SELECT 
        CASE 
            WHEN occupation LIKE '%Teacher%' OR occupation LIKE '%Educator%' OR occupation LIKE '%Professor%' THEN 'Education'
            WHEN occupation LIKE '%Nurse%' OR occupation LIKE '%Doctor%' OR occupation LIKE '%Medical%' OR occupation LIKE '%Health%' THEN 'Healthcare'
            WHEN occupation LIKE '%Engineer%' OR occupation LIKE '%Architect%' OR occupation LIKE '%Technical%' THEN 'Engineering & Technical'
            WHEN occupation LIKE '%Farmer%' OR occupation LIKE '%Fisher%' OR occupation LIKE '%Agricultural%' THEN 'Agriculture'
            WHEN occupation LIKE '%Driver%' OR occupation LIKE '%Pilot%' OR occupation LIKE '%Transport%' THEN 'Transportation'
            WHEN occupation LIKE '%Business%' OR occupation LIKE '%Entrepreneur%' OR occupation LIKE '%Merchant%' THEN 'Business & Trade'
            WHEN occupation LIKE '%Government%' OR occupation LIKE '%Public%' OR occupation LIKE '%Civil%' THEN 'Government Service'
            WHEN occupation LIKE '%Student%' THEN 'Student'
            WHEN occupation LIKE '%Retired%' OR occupation LIKE '%Pension%' THEN 'Retired'
            WHEN occupation IS NULL OR occupation = '' THEN 'Unemployed/Not Specified'
            ELSE 'Other'
        END as sector,
        COUNT(*) as count,
        GROUP_CONCAT(CONCAT(last_name, ', ', first_name) SEPARATOR '; ') as members
    FROM household_records
    GROUP BY sector
    ORDER BY count DESC
")->fetchAll();

$total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population By Sector - RBIS</title>
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
                        <i class="fas fa-building text-warning"></i> Population By Sector
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
                    <h4>POPULATION BY SECTOR</h4>
                    <p>Generated on: <?= date('F d, Y h:i A') ?></p>
                    <p>Total Households: <?= number_format($total_households) ?></p>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="chart-container" style="height: 350px;">
                                    <canvas id="sectorPieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="chart-container" style="height: 350px;">
                                    <canvas id="sectorBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table id="sectorTable" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Sector</th>
                                <th>Count</th>
                                <th>Percentage</th>
                                <th>Household Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($sector_data as $sector): ?>
                                <?php $percentage = $total_households > 0 ? round(($sector['count'] / $total_households) * 100, 1) : 0; ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= $sector['sector'] ?></strong></td>
                                    <td><?= number_format($sector['count']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <span><?= $percentage ?>%</span>
                                        </div>
                                    </td>
                                    <td><small><?= substr($sector['members'] ?? '', 0, 80) ?>...</small></td>
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
            $('#sectorTable').DataTable({
                pageLength: 25,
                order: [[2, 'desc']]
            });
        });

        // Colors
        const colors = [
            '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#6f42c1',
            '#fd7e14', '#20c997', '#e83e8c', '#6610f2', '#0dcaf0',
            '#6c757d', '#343a40'
        ];

        const sectorData = <?= json_encode($sector_data) ?>;
        const labels = sectorData.map(d => d.sector);
        const values = sectorData.map(d => d.count);

        // Pie Chart
        const pieCtx = document.getElementById('sectorPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { font: { size: 11 } }
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

        // Bar Chart
        const barCtx = document.getElementById('sectorBarChart').getContext('2d');
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Households',
                    data: values,
                    backgroundColor: colors.slice(0, labels.length).map(c => c + 'CC'),
                    borderColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</body>
</html>