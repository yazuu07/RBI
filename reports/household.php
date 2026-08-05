<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get all household records with member count
$households = $pdo->query("
    SELECT h.*, 
           (SELECT COUNT(*) FROM individual_records WHERE last_name = h.last_name AND first_name != h.first_name) as member_count
    FROM household_records h
    ORDER BY h.last_name, h.first_name
")->fetchAll();

$total_households = count($households);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Household Report - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .report-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px double #1a3c6e;
            margin-bottom: 20px;
        }
        .report-header h1 {
            font-size: 24px;
            color: #1a3c6e;
            font-weight: bold;
        }
        .report-header p {
            color: #666;
            font-size: 14px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .report-header { border-bottom: 2px solid #333; }
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
                        <i class="fas fa-home text-success"></i> Household Report
                    </h1>
                    <div class="btn-toolbar no-print">
                        <button class="btn btn-success me-2" onclick="exportReport('pdf')">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-primary me-2" onclick="exportReport('excel')">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Report Header -->
                <div class="report-header">
                    <h1>Republic of the Philippines</h1>
                    <h3>Barangay Nayong Kanluran, Quezon City</h3>
                    <h4>HOUSEHOLD REPORT</h4>
                    <p>Generated on: <?= date('F d, Y h:i A') ?></p>
                    <p>Total Households: <?= number_format($total_households) ?></p>
                </div>

                <!-- Household Table -->
                <div class="table-responsive">
                    <table id="householdTable" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Head of Household</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Civil Status</th>
                                <th>Citizenship</th>
                                <th>Occupation</th>
                                <th>Members</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($households as $household): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($household['last_name']) ?></strong>,
                                        <?= htmlspecialchars($household['first_name']) ?>
                                        <?php if ($household['middle_name']): ?>
                                            <?= htmlspecialchars($household['middle_name'][0]) ?>.
                                        <?php endif; ?>
                                        <?php if ($household['ext_name']): ?>
                                            <small class="text-muted"><?= htmlspecialchars($household['ext_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $household['age'] ?? 'N/A' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $household['sex'] == 'Male' ? 'primary' : ($household['sex'] == 'Female' ? 'danger' : 'secondary') ?>">
                                            <?= $household['sex'] ?>
                                        </span>
                                    </td>
                                    <td><?= $household['civil_status'] ?></td>
                                    <td><?= $household['citizenship'] ?: 'N/A' ?></td>
                                    <td><?= $household['occupation'] ?: 'N/A' ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $household['member_count'] ?? 0 ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
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
            $('#householdTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ households'
                },
                order: [[1, 'asc']]
            });
        });

        function exportReport(type) {
            alert('Export to ' + type.toUpperCase() + ' functionality coming soon!');
        }
    </script>
</body>
</html>