<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get voters (18+ years old)
$voters = $pdo->query("
    SELECT * FROM individual_records 
    WHERE age >= 18 AND age IS NOT NULL
    ORDER BY last_name, first_name
")->fetchAll();

$total_voters = count($voters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voters List - RBIS</title>
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
                        <i class="fas fa-vote-yea text-primary"></i> Voters List
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

                <div class="report-header">
                    <h1>Republic of the Philippines</h1>
                    <h3>Barangay Nayong Kanluran, Quezon City</h3>
                    <h4>VOTERS LIST</h4>
                    <p>Generated on: <?= date('F d, Y h:i A') ?></p>
                    <p>Total Eligible Voters (18+): <?= number_format($total_voters) ?></p>
                </div>

                <div class="table-responsive">
                    <table id="votersTable" class="table table-hover table-striped" style="width:100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Sex</th>
                                <th>Civil Status</th>
                                <th>Education</th>
                                <th>Place of Birth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($voters as $voter): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($voter['last_name']) ?></strong>,
                                        <?= htmlspecialchars($voter['first_name']) ?>
                                        <?php if ($voter['middle_name']): ?>
                                            <?= htmlspecialchars($voter['middle_name'][0]) ?>.
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $voter['age'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $voter['sex'] == 'Male' ? 'primary' : 'danger' ?>">
                                            <?= $voter['sex'] ?>
                                        </span>
                                    </td>
                                    <td><?= $voter['civil_status'] ?></td>
                                    <td><?= $voter['highest_education'] ?: 'N/A' ?></td>
                                    <td><?= $voter['place_of_birth'] ?: 'N/A' ?></td>
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
            $('#votersTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[1, 'asc']]
            });
        });

        function exportReport(type) {
            alert('Export to ' + type.toUpperCase() + ' functionality coming soon!');
        }
    </script>
</body>
</html>