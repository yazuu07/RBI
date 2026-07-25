<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'sql_execute');

$pdo = getDB();

// Get filter options
$actions = $pdo->query("SELECT DISTINCT action FROM audit_trails ORDER BY action")->fetchAll();
$modules = $pdo->query("SELECT DISTINCT table_name FROM audit_trails ORDER BY table_name")->fetchAll();
$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trails - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
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
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
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
                        <i class="fas fa-clipboard-list text-warning"></i> Audit Trails
                    </h1>
                    <button class="btn btn-secondary" onclick="window.location.reload()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="totalLogs">0</div>
                                    <div class="stat-label">Total Activities</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="todayLogs">0</div>
                                    <div class="stat-label">Today</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="weekLogs">0</div>
                                    <div class="stat-label">This Week</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-week"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="modulesCount">0</div>
                                    <div class="stat-label">Modules</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-cubes"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Action</label>
                            <select name="action" id="actionFilter" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?= $action['action'] ?>"><?= $action['action'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Module</label>
                            <select name="module" id="moduleFilter" class="form-select">
                                <option value="">All Modules</option>
                                <?php foreach ($modules as $module): ?>
                                    <option value="<?= $module['table_name'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $module['table_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">User</label>
                            <select name="user" id="userFilter" class="form-select">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" id="dateFrom" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" id="dateTo" class="form-control">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Audit Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="auditTable" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 150px;">Date/Time</th>
                                        <th style="width: 150px;">User</th>
                                        <th style="width: 100px;">Action</th>
                                        <th style="width: 130px;">Module</th>
                                        <th>Details</th>
                                        <th style="width: 80px;">Record ID</th>
                                        <th style="width: 120px;">IP Address</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        var table;
        
        $(document).ready(function() {
            // Initialize DataTable
            table = $('#auditTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/audit_trails.php',
                    type: 'GET',
                    data: function(d) {
                        d.action = $('#actionFilter').val();
                        d.module = $('#moduleFilter').val();
                        d.user = $('#userFilter').val();
                        d.date_from = $('#dateFrom').val();
                        d.date_to = $('#dateTo').val();
                    },
                    dataSrc: function(json) {
                        updateStats(json);
                        return json.data;
                    }
                },
                columns: [
                    { data: 0 },
                    { data: 1 },
                    { data: 2 },
                    { data: 3 },
                    { data: 4 },
                    { data: 5 },
                    { data: 6 }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    processing: '<div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No audit records found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ records',
                    infoEmpty: 'Showing 0 to 0 of 0 records',
                    infoFiltered: '(filtered from _MAX_ total records)',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries'
                },
                deferLoading: 0,
                stateSave: true,
                stateDuration: 7200,
                scrollCollapse: true,
                scrollX: true
            });
        });
        
        function updateStats(json) {
            // Get stats from API
            $.ajax({
                url: '../api/audit_stats.php',
                type: 'GET',
                success: function(stats) {
                    $('#totalLogs').text(stats.total || 0);
                    $('#todayLogs').text(stats.today || 0);
                    $('#weekLogs').text(stats.week || 0);
                    $('#modulesCount').text(stats.modules || 0);
                }
            });
        }
        
        function applyFilters() {
            table.ajax.reload();
        }
    </script>
</body>
</html>