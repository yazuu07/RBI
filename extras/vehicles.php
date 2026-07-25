<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get filter options
$vehicle_types = $pdo->query("SELECT DISTINCT vehicle_type FROM vehicles WHERE vehicle_type IS NOT NULL")->fetchAll();
$statuses = $pdo->query("SELECT DISTINCT status FROM vehicles")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .vehicle-photo-sm {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
        }
        .vehicle-photo-placeholder {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 24px;
            border: 2px dashed #ddd;
        }
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-car text-primary"></i> Vehicles
                        <span class="badge bg-primary ms-2" id="totalCount">0</span>
                    </h1>
                    <?php if (hasPermission('extras', 'add')): ?>
                        <button class="btn btn-success" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New Vehicle
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="totalVehicles">0</div>
                                    <div class="stat-label">Total Vehicles</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-car"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="activeVehicles">0</div>
                                    <div class="stat-label">Active</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="inactiveVehicles">0</div>
                                    <div class="stat-label">Inactive</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-danger text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="expiredVehicles">0</div>
                                    <div class="stat-label">Expired</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="search-filter-bar">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select id="typeFilter" class="form-select">
                                <option value="">All Vehicle Types</option>
                                <?php foreach ($vehicle_types as $type): ?>
                                    <option value="<?= $type['vehicle_type'] ?>"><?= $type['vehicle_type'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status['status'] ?>"><?= $status['status'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="vehiclesTable" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 60px;">VEHICLE PHOTO</th>
                                        <th>PLATE NUMBER</th>
                                        <th>VEHICLE TYPE</th>
                                        <th>VEHICLE STATUS</th>
                                        <th>OWNER</th>
                                        <th style="width: 150px;">ACTIONS</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-car text-primary"></i> Add New Vehicle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="vehicleForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="vehicleId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner (Household) <span class="text-danger">*</span></label>
                                <select name="owner_id" id="ownerId" class="form-select" required>
                                    <option value="">Select Owner...</option>
                                    <!-- Loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Plate Number <span class="text-danger">*</span></label>
                                <input type="text" name="plate_number" id="plateNumber" class="form-control" required placeholder="e.g., ABC-1234">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                                <select name="vehicle_type" id="vehicleType" class="form-select" required>
                                    <option value="">Select Type...</option>
                                    <option value="Tricycle">Tricycle</option>
                                    <option value="Motorcycle">Motorcycle</option>
                                    <option value="Car">Car</option>
                                    <option value="SUV">SUV</option>
                                    <option value="Van">Van</option>
                                    <option value="Truck">Truck</option>
                                    <option value="Bus">Bus</option>
                                    <option value="Tractor">Tractor</option>
                                    <option value="Bicycle">Bicycle</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vehicle Brand</label>
                                <input type="text" name="brand" id="vehicleBrand" class="form-control" placeholder="e.g., Toyota, Kawasaki">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vehicle Color</label>
                                <input type="text" name="color" id="vehicleColor" class="form-control" placeholder="e.g., Silver, Red">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Year Model</label>
                                <input type="number" name="year_model" id="yearModel" class="form-control" placeholder="e.g., 2020" min="1900" max="<?= date('Y') ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Registration Date</label>
                                <input type="date" name="registration_date" id="registrationDate" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="vehicleStatus" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Expired">Expired</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Vehicle Photo</label>
                                <input type="file" name="vehicle_photo" id="vehiclePhoto" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                                <div id="photoPreview" class="mt-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveVehicle()">
                        <i class="fas fa-save"></i> Save Vehicle
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-car text-primary"></i> Vehicle Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewContent">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printVehicle()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
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
                    <p>Are you sure you want to delete this vehicle?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="vehicle_delete.php">
                        <input type="hidden" name="id" id="deleteId">
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
        var viewModal;
        var vehicleModal;
        var isEdit = false;
        
        $(document).ready(function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            vehicleModal = new bootstrap.Modal(document.getElementById('vehicleModal'));
            
            // Load owners for dropdown
            loadOwners();
            
            // Initialize DataTable
            table = $('#vehiclesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/vehicles.php',
                    type: 'GET',
                    data: function(d) {
                        d.vehicle_type = $('#typeFilter').val();
                        d.status = $('#statusFilter').val();
                    },
                    dataSrc: function(json) {
                        updateStats(json);
                        return json.data;
                    }
                },
                columns: [
                    { data: 0, orderable: false, searchable: false },
                    { data: 1 },
                    { data: 2 },
                    { data: 3 },
                    { data: 4 },
                    { data: 5, orderable: false, searchable: false }
                ],
                order: [[1, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No vehicles found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ vehicles',
                    infoEmpty: 'Showing 0 to 0 of 0 vehicles',
                    infoFiltered: '(filtered from _MAX_ total vehicles)',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries'
                },
                deferLoading: 0,
                stateSave: true,
                stateDuration: 7200,
                scrollCollapse: true,
                scrollX: true
            });
            
            // Debounce search
            $('.dataTables_filter input').off('keyup').on('keyup', function() {
                var search = $(this).val();
                if (search.length >= 2 || search.length === 0) {
                    table.search(search).draw();
                }
            });
        });
        
        // Load owners from household records
        function loadOwners() {
            $.ajax({
                url: '../api/households.php?draw=1&length=1000',
                type: 'GET',
                success: function(response) {
                    if (response.data) {
                        var select = $('#ownerId');
                        select.empty();
                        select.append('<option value="">Select Owner...</option>');
                        response.data.forEach(function(item) {
                            // Extract name from the HTML data
                            var name = item[1].replace(/<[^>]*>/g, '').trim();
                            // Extract ID from actions
                            var match = item[7].match(/deleteRecord\((\d+)\)/);
                            if (match) {
                                var id = match[1];
                                select.append('<option value="' + id + '">' + name + '</option>');
                            }
                        });
                    }
                }
            });
        }
        
        // Update statistics
        function updateStats(json) {
            $.ajax({
                url: '../api/vehicle_stats.php',
                type: 'GET',
                success: function(stats) {
                    $('#totalVehicles').text(stats.total || 0);
                    $('#activeVehicles').text(stats.active || 0);
                    $('#inactiveVehicles').text(stats.inactive || 0);
                    $('#expiredVehicles').text(stats.expired || 0);
                    $('#totalCount').text(stats.total || 0);
                }
            });
        }
        
        // Apply filters
        function applyFilters() {
            table.ajax.reload();
        }
        
        // Open create modal
        function openCreateModal() {
            isEdit = false;
            $('#modalTitle').html('<i class="fas fa-car text-primary"></i> Add New Vehicle');
            $('#vehicleForm')[0].reset();
            $('#vehicleId').val('');
            $('#photoPreview').html('');
            $('#vehicleStatus').val('Active');
            vehicleModal.show();
        }
        
        // Edit vehicle
        function editVehicle(id) {
            isEdit = true;
            $('#modalTitle').html('<i class="fas fa-edit text-warning"></i> Edit Vehicle Details');
            
            $.ajax({
                url: '../api/vehicle_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    $('#vehicleId').val(data.id);
                    $('#ownerId').val(data.owner_id);
                    $('#plateNumber').val(data.plate_number);
                    $('#vehicleType').val(data.vehicle_type);
                    $('#vehicleBrand').val(data.brand);
                    $('#vehicleColor').val(data.color);
                    $('#yearModel').val(data.year_model);
                    $('#registrationDate').val(data.registration_date);
                    $('#vehicleStatus').val(data.status);
                    
                    if (data.vehicle_photo) {
                        $('#photoPreview').html('<img src="../uploads/' + data.vehicle_photo + '" class="vehicle-photo-sm" alt="Vehicle">');
                    } else {
                        $('#photoPreview').html('');
                    }
                    
                    vehicleModal.show();
                }
            });
        }
        
        // Save vehicle
        function saveVehicle() {
            var formData = new FormData($('#vehicleForm')[0]);
            var url = isEdit ? '../api/vehicle_update.php' : '../api/vehicle_create.php';
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        vehicleModal.hide();
                        table.ajax.reload();
                        showAlert('success', 'Vehicle saved successfully!');
                    } else {
                        showAlert('danger', response.message || 'Failed to save vehicle');
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred. Please try again.');
                }
            });
        }
        
        // View vehicle
        function viewVehicle(id) {
            $.ajax({
                url: '../api/vehicle_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    var statusBadge = '';
                    switch(data.status) {
                        case 'Active': statusBadge = 'success'; break;
                        case 'Inactive': statusBadge = 'danger'; break;
                        case 'Expired': statusBadge = 'warning'; break;
                        default: statusBadge = 'secondary';
                    }
                    
                    var html = `
                        <div class="vehicle-details">
                            <div class="row">
                                <div class="col-md-12 text-center mb-3">
                                    ${data.vehicle_photo ? 
                                        '<img src="../uploads/' + data.vehicle_photo + '" class="img-fluid" style="max-height: 200px; border-radius: 10px;" alt="Vehicle">' : 
                                        '<div class="alert alert-secondary">No vehicle photo available</div>'
                                    }
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">OWNER:</div>
                                    <div class="info-value"><strong>${data.owner_name || 'Unassigned'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">PLATE NUMBER:</div>
                                    <div class="info-value"><strong>${data.plate_number || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">VEHICLE TYPE:</div>
                                    <div class="info-value"><strong>${data.vehicle_type || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">VEHICLE COLOR:</div>
                                    <div class="info-value"><strong>${data.color || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">VEHICLE BRAND:</div>
                                    <div class="info-value"><strong>${data.brand || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">YEAR MODEL:</div>
                                    <div class="info-value"><strong>${data.year_model || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">REGISTRATION DATE:</div>
                                    <div class="info-value"><strong>${data.registration_date || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">STATUS:</div>
                                    <div class="info-value"><span class="badge bg-${statusBadge}">${data.status}</span></div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#viewContent').html(html);
                    viewModal.show();
                }
            });
        }
        
        // Delete vehicle
        function deleteVehicle(id, plate) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = 'Vehicle: ' + plate;
            deleteModal.show();
        }
        
        // Print vehicle
        function printVehicle() {
            window.print();
        }
        
        // Show alert
        function showAlert(type, message) {
            var alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999; min-width: 300px;">
                    <i class="fas fa-${type == 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('body').append(alertHtml);
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        }
    </script>
</body>
</html>