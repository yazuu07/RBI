<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get filter options
$cert_types = $pdo->query("SELECT DISTINCT certificate_type FROM certificates WHERE certificate_type IS NOT NULL")->fetchAll();
$statuses = $pdo->query("SELECT DISTINCT status FROM certificates")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificates - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .certificate-badge {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            display: inline-block;
            margin: 2px;
        }
        .certificate-badge.residency {
            background: #cce5ff;
            color: #004085;
        }
        .certificate-badge.indigency {
            background: #fff3cd;
            color: #856404;
        }
        .certificate-badge.id {
            background: #d4edda;
            color: #155724;
        }
        .certificate-badge.clearance {
            background: #f8d7da;
            color: #721c24;
        }
        .certificate-badge.barangay {
            background: #e8daef;
            color: #6c3483;
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
                        <i class="fas fa-certificate text-warning"></i> Generate Barangay Certificate
                        <span class="badge bg-warning ms-2" id="totalCount">0</span>
                    </h1>
                    <?php if (hasPermission('certification', 'add')): ?>
                        <button class="btn btn-success" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Issue New Certificate
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4" id="statsRow">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="totalCertificates">0</div>
                                    <div class="stat-label">Total Certificates</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="issuedCount">0</div>
                                    <div class="stat-label">Issued</div>
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
                                    <div class="stat-number" id="pendingCount">0</div>
                                    <div class="stat-label">Pending</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-danger text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="expiredCount">0</div>
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
                        <div class="col-md-3">
                            <select id="typeFilter" class="form-select">
                                <option value="">All Certificate Types</option>
                                <option value="ID">ID</option>
                                <option value="Residency">Residency</option>
                                <option value="Indigency">Indigency</option>
                                <option value="Clearance">Clearance</option>
                                <option value="Barangay">Barangay</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= $status['status'] ?>"><?= $status['status'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="dateFromFilter" class="form-control" placeholder="Date From">
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
                        <div class="table-responsive">
                            <table id="certificatesTable" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>FULL NAME</th>
                                        <th>CERTIFICATE</th>
                                        <th>CERT #</th>
                                        <th>STATUS</th>
                                        <th>ISSUED DATE</th>
                                        <th style="width: 180px;">ACTIONS</th>
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
    <div class="modal fade" id="certificateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-certificate text-warning"></i> Issue New Certificate
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="certificateForm">
                        <input type="hidden" name="id" id="certId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Resident <span class="text-danger">*</span></label>
                                <select name="resident_id" id="residentId" class="form-select" required>
                                    <option value="">Select Resident...</option>
                                    <!-- Will be loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Certificate Type <span class="text-danger">*</span></label>
                                <select name="certificate_type" id="certificateType" class="form-select" required>
                                    <option value="">Select Type...</option>
                                    <option value="ID">ID</option>
                                    <option value="Residency">Residency</option>
                                    <option value="Indigency">Indigency</option>
                                    <option value="Clearance">Clearance</option>
                                    <option value="Barangay">Barangay</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Certificate Number</label>
                                <input type="text" name="certificate_number" id="certNumber" class="form-control" placeholder="Auto-generated if left blank">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="certStatus" class="form-select">
                                    <option value="Pending">Pending</option>
                                    <option value="Issued" selected>Issued</option>
                                    <option value="Expired">Expired</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Purpose</label>
                                <textarea name="purpose" id="certPurpose" class="form-control" rows="3" placeholder="Purpose of certificate..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Issued Date</label>
                                <input type="date" name="issued_date" id="certIssuedDate" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" id="certExpiryDate" class="form-control">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCertificate()">
                        <i class="fas fa-save"></i> Save Certificate
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
                        <i class="fas fa-certificate text-warning"></i> Certificate Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewContent">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printCertificateView()">
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
                    <p>Are you sure you want to delete this certificate?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete.php">
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
        var certModal;
        var isEdit = false;
        
        $(document).ready(function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            certModal = new bootstrap.Modal(document.getElementById('certificateModal'));
            
            // Load residents for dropdown
            loadResidents();
            
            // Initialize DataTable
            table = $('#certificatesTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/certificates.php',
                    type: 'GET',
                    data: function(d) {
                        d.certificate_type = $('#typeFilter').val();
                        d.status = $('#statusFilter').val();
                        d.date_from = $('#dateFromFilter').val();
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
                    { data: 5 }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: '<div class="spinner-border text-warning" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No certificates found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ certificates',
                    infoEmpty: 'Showing 0 to 0 of 0 certificates',
                    infoFiltered: '(filtered from _MAX_ total certificates)',
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
        
        // Load residents for dropdown
        function loadResidents() {
            $.ajax({
                url: '../api/citizens.php?draw=1&length=1000',
                type: 'GET',
                success: function(response) {
                    if (response.data) {
                        var select = $('#residentId');
                        select.empty();
                        select.append('<option value="">Select Resident...</option>');
                        response.data.forEach(function(item) {
                            // Extract name from the HTML data
                            var name = item[1].replace(/<[^>]*>/g, '').trim();
                            // We need the ID from somewhere - using the action button data
                            // In a real implementation, we'd modify the API to include IDs
                            // For now, let's use a workaround
                            var match = item[6].match(/deleteRecord\((\d+)\)/);
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
            // We need to get stats from a separate API call
            $.ajax({
                url: '../api/certificate_stats.php',
                type: 'GET',
                success: function(stats) {
                    $('#totalCertificates').text(stats.total || 0);
                    $('#issuedCount').text(stats.issued || 0);
                    $('#pendingCount').text(stats.pending || 0);
                    $('#expiredCount').text(stats.expired || 0);
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
            $('#modalTitle').html('<i class="fas fa-certificate text-warning"></i> Issue New Certificate');
            $('#certificateForm')[0].reset();
            $('#certId').val('');
            $('#certNumber').val('');
            $('#certStatus').val('Issued');
            $('#certIssuedDate').val(new Date().toISOString().split('T')[0]);
            
            // Set expiry date to 1 year from now
            var date = new Date();
            date.setFullYear(date.getFullYear() + 1);
            $('#certExpiryDate').val(date.toISOString().split('T')[0]);
            
            certModal.show();
        }
        
        // Edit certificate
        function editCertificate(id) {
            isEdit = true;
            $('#modalTitle').html('<i class="fas fa-edit text-warning"></i> Edit Certificate');
            
            $.ajax({
                url: '../api/certificate_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    $('#certId').val(data.id);
                    $('#residentId').val(data.resident_id);
                    $('#certificateType').val(data.certificate_type);
                    $('#certNumber').val(data.certificate_number);
                    $('#certStatus').val(data.status);
                    $('#certPurpose').val(data.purpose);
                    $('#certIssuedDate').val(data.issued_date);
                    $('#certExpiryDate').val(data.expiry_date);
                    certModal.show();
                }
            });
        }
        
        // Save certificate
        function saveCertificate() {
            var form = $('#certificateForm');
            var data = form.serialize();
            
            var url = isEdit ? '../api/certificate_update.php' : '../api/certificate_create.php';
            
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        certModal.hide();
                        table.ajax.reload();
                        showAlert('success', 'Certificate saved successfully!');
                    } else {
                        showAlert('danger', response.message || 'Failed to save certificate');
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred. Please try again.');
                }
            });
        }
        
        // View certificate
        function viewCertificate(id) {
            $.ajax({
                url: '../api/certificate_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    var html = `
                        <div class="certificate-view">
                            <div class="text-center mb-4">
                                <h4>Republic of the Philippines</h4>
                                <h5>Barangay Certificate</h5>
                                <hr>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Certificate No:</strong> ${data.certificate_number}</p>
                                    <p><strong>Resident:</strong> ${data.resident_name}</p>
                                    <p><strong>Certificate Type:</strong> ${data.certificate_type}</p>
                                    <p><strong>Status:</strong> <span class="badge bg-${data.status == 'Issued' ? 'success' : data.status == 'Pending' ? 'warning' : 'danger'}">${data.status}</span></p>
                                    <p><strong>Purpose:</strong> ${data.purpose || 'N/A'}</p>
                                    <p><strong>Issued Date:</strong> ${data.issued_date}</p>
                                    <p><strong>Expiry Date:</strong> ${data.expiry_date || 'N/A'}</p>
                                    <p><strong>Issued By:</strong> ${data.issued_by_name}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#viewContent').html(html);
                    viewModal.show();
                }
            });
        }
        
        // Delete certificate
        function deleteCertificate(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = 'Certificate for: ' + name;
            deleteModal.show();
        }
        
        // Print certificate
        function printCertificate(id) {
            window.open('../api/certificate_print.php?id=' + id, '_blank');
        }
        
        function printCertificateView() {
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