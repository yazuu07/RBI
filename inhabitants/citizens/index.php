<?php
require_once '../../config.php';
requireLogin();

$pdo = getDB();

// Get filter options for dropdowns
$sexes = $pdo->query("SELECT DISTINCT sex FROM individual_records WHERE sex IS NOT NULL")->fetchAll();
$civil_statuses = $pdo->query("SELECT DISTINCT civil_status FROM individual_records WHERE civil_status IS NOT NULL")->fetchAll();
$educations = $pdo->query("SELECT DISTINCT highest_education FROM individual_records WHERE highest_education IS NOT NULL")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Citizens - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user text-primary"></i> Barangay Citizens
                        <span class="badge bg-primary ms-2" id="totalCount">0</span>
                    </h1>
                    <?php if (hasPermission('inhabitants', 'add')): ?>
                        <a href="create.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add New Citizen
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Filter Bar -->
                <div class="search-filter-bar">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select id="sexFilter" class="form-select">
                                <option value="">All Sex</option>
                                <?php foreach ($sexes as $sex): ?>
                                    <option value="<?= $sex['sex'] ?>"><?= $sex['sex'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="civilFilter" class="form-select">
                                <option value="">All Civil Status</option>
                                <?php foreach ($civil_statuses as $status): ?>
                                    <option value="<?= $status['civil_status'] ?>"><?= $status['civil_status'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="educationFilter" class="form-select">
                                <option value="">All Education</option>
                                <?php foreach ($educations as $edu): ?>
                                    <option value="<?= $edu['highest_education'] ?>"><?= $edu['highest_education'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="card">
                    <div class="card-body">
                        <table id="citizensTable" class="table table-hover table-striped" style="width:100%">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 50px;">Photo</th>
                                    <th>Name</th>
                                    <th style="width: 60px;">Age</th>
                                    <th style="width: 80px;">Sex</th>
                                    <th style="width: 120px;">Civil Status</th>
                                    <th style="width: 120px;">Education</th>
                                    <th style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this citizen record?</p>
                    <p class="text-danger"><small><i class="fas fa-info-circle"></i> This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete.php">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        var table;
        var deleteModal;
        var deleteName = document.getElementById('deleteName');
        var deleteId = document.getElementById('deleteId');
        
        $(document).ready(function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            // Initialize DataTable with server-side processing
            table = $('#citizensTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../../api/citizens.php',
                    type: 'GET',
                    data: function(d) {
                        d.sex = $('#sexFilter').val();
                        d.civil_status = $('#civilFilter').val();
                        d.education = $('#educationFilter').val();
                    },
                    dataSrc: function(json) {
                        // Update total count
                        $('#totalCount').text(json.recordsTotal);
                        return json.data;
                    }
                },
                columns: [
                    { 
                        data: 0, 
                        orderable: false, 
                        searchable: false,
                        render: function(data) {
                            return data || '<img src="../../assets/images/default-avatar.png" class="profile-preview-sm" alt="Default">';
                        }
                    },
                    { data: 1 },
                    { data: 2 },
                    { 
                        data: 3, 
                        orderable: false, 
                        searchable: false 
                    },
                    { data: 4 },
                    { data: 5 },
                    { 
                        data: 6, 
                        orderable: false, 
                        searchable: false 
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No citizens found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ citizens',
                    infoEmpty: 'Showing 0 to 0 of 0 citizens',
                    infoFiltered: '(filtered from _MAX_ total citizens)',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries'
                },
                // Performance optimizations
                deferLoading: 0,
                stateSave: true,
                stateDuration: 7200,
                scrollCollapse: true,
                scrollX: true,
                // Custom rendering for better performance
                createdRow: function(row, data, dataIndex) {
                    $(row).addClass('fade-in');
                }
            });
            
            // Debounce search for better performance
            $('.dataTables_filter input').off('keyup').on('keyup', function() {
                var search = $(this).val();
                if (search.length >= 2 || search.length === 0) {
                    table.search(search).draw();
                }
            });
            
            // Handle Enter key for search
            $('.dataTables_filter input').on('keypress', function(e) {
                if (e.which === 13) {
                    table.search($(this).val()).draw();
                }
            });
        });
        
        // Apply filters
        function applyFilters() {
            table.ajax.reload();
        }
        
        // Delete record with preview
        function deleteRecord(id, name) {
            deleteId.value = id;
            deleteName.textContent = 'Record: ' + name;
            deleteModal.show();
        }
        
        // Refresh table (for auto-refresh feature)
        function refreshTable() {
            table.ajax.reload(null, false);
        }
        
        // Auto-refresh every 60 seconds (optional)
        // setInterval(refreshTable, 60000);
    </script>
</body>
</html>