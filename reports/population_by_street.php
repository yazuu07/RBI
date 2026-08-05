<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get street distribution based on place_of_birth (used as address proxy)
$street_data = $pdo->query("
    SELECT 
        CASE 
            WHEN place_of_birth LIKE '%Street%' OR place_of_birth LIKE '%St.%' 
                OR place_of_birth LIKE '%Avenue%' OR place_of_birth LIKE '%Ave.%'
                OR place_of_birth LIKE '%Road%' OR place_of_birth LIKE '%Rd.%'
                OR place_of_birth LIKE '%Drive%' OR place_of_birth LIKE '%Dr.%'
                OR place_of_birth LIKE '%Boulevard%' OR place_of_birth LIKE '%Blvd.%'
                OR place_of_birth LIKE '%Lane%' OR place_of_birth LIKE '%Ln.%'
            THEN 
                TRIM(SUBSTRING_INDEX(
                    SUBSTRING_INDEX(place_of_birth, ',', 1), 
                    ' ', -2
                ))
            ELSE 
                CASE 
                    WHEN place_of_birth IS NOT NULL AND place_of_birth != '' 
                    THEN 'Various Locations'
                    ELSE 'Unknown'
                END
        END as street,
        COUNT(*) as count,
        GROUP_CONCAT(CONCAT(last_name, ', ', first_name) SEPARATOR '; ') as residents
    FROM individual_records
    WHERE place_of_birth IS NOT NULL
    GROUP BY street
    ORDER BY count DESC
    LIMIT 20
")->fetchAll();

$total_with_address = $pdo->query("SELECT COUNT(*) FROM individual_records WHERE place_of_birth IS NOT NULL")->fetchColumn();
$total_individuals = $pdo->query("SELECT COUNT(*) FROM individual_records")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Population By Street - RBIS</title>
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
                        <i class="fas fa-road text-danger"></i> Population By Street
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
                    <h4>POPULATION BY STREET</h4>
                    <p>Generated on: <?= date('F d, Y h:i A') ?></p>
                    <p>Total Population: <?= number_format($total_individuals) ?></p>
                    <p>With Address: <?= number_format($total_with_address) ?></p>
                </div>

                <!-- Chart -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="streetChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="table-responsive">
                    <table id="streetTable" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Street / Location</th>
                                <th>Population</th>
                                <th>Percentage</th>
                                <th>Residents</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($street_data as $street): ?>
                                <?php $percentage = $total_with_address > 0 ? round(($street['count'] / $total_with_address) * 100, 1) : 0; ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><strong><?= htmlspecialchars($street['street']) ?></strong></td>
                                    <td><?= number_format($street['count']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                                <div class="progress-bar bg-danger" style="width: <?= $percentage ?>%"></div>
                                            </div>
                                            <span><?= $percentage ?>%</span>
                                        </div>
                                    </td>
                                    <td><small><?= substr($street['residents'] ?? '', 0, 80) ?>...</small></td>
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
            $('#streetTable').DataTable({
                pageLength: 25,
                order: [[2, 'desc']]
            });
        });

        // Chart
        const streetData = <?= json_encode($street_data) ?>;
        const colors = [
            '#e74a3b', '#f6c23e', '#4e73df', '#1cc88a', '#6f42c1',
            '#fd7e14', '#20c997', '#e83e8c', '#6610f2', '#0dcaf0',
            '#6c757d', '#343a40', '#d63384', '#fd7e14', '#20c997'
        ];

        const ctx = document.getElementById('streetChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: streetData.map(d => d.street),
                datasets: [{
                    label: 'Population',
                    data: streetData.map(d => d.count),
                    backgroundColor: colors.slice(0, streetData.length).map(c => c + 'CC'),
                    borderColor: colors.slice(0, streetData.length),
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
                                let total = <?= $total_with_address ?>;
                                let percentage = total > 0 ? ((context.parsed.y / total) * 100).toFixed(1) : 0;
                                return context.parsed.y + ' residents (' + percentage + '%)';
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