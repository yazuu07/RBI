<?php
require_once '../config.php';
requireLogin();

$pdo = getDB();

// Get filter options
$pet_types = $pdo->query("SELECT DISTINCT pet_type FROM pets WHERE pet_type IS NOT NULL")->fetchAll();
$statuses = $pdo->query("SELECT DISTINCT status FROM pets")->fetchAll();

$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pets - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .pet-photo-sm {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        .pet-photo-placeholder {
            width: 50px;
            height: 50px;
            background: #f0f0f0;
            border-radius: 50%;
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
        .pet-type-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
        }
        .pet-type-badge.dog { background: #cce5ff; color: #004085; }
        .pet-type-badge.cat { background: #d4edda; color: #155724; }
        .pet-type-badge.bird { background: #fff3cd; color: #856404; }
        .pet-type-badge.fish { background: #d6d8db; color: #1b1e21; }
        .pet-type-badge.other { background: #e8daef; color: #6c3483; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-paw text-primary"></i> Pets
                        <span class="badge bg-primary ms-2" id="totalCount">0</span>
                    </h1>
                    <?php if (hasPermission('extras', 'add')): ?>
                        <button class="btn btn-success" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New Pet
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="totalPets">0</div>
                                    <div class="stat-label">Total Pets</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-paw"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number" id="activePets">0</div>
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
                                    <div class="stat-number" id="inactivePets">0</div>
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
                                    <div class="stat-number" id="deceasedPets">0</div>
                                    <div class="stat-label">Deceased</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-heart-broken"></i>
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
                                <option value="">All Pet Types</option>
                                <?php foreach ($pet_types as $type): ?>
                                    <option value="<?= $type['pet_type'] ?>"><?= $type['pet_type'] ?></option>
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
                            <table id="petsTable" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 60px;">PHOTO</th>
                                        <th>PET NAME</th>
                                        <th>TYPE</th>
                                        <th>BREED</th>
                                        <th>AGE</th>
                                        <th>STATUS</th>
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
    <div class="modal fade" id="petModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-paw text-primary"></i> Add New Pet
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="petForm" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="petId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Owner (Household) <span class="text-danger">*</span></label>
                                <select name="owner_id" id="ownerId" class="form-select" required>
                                    <option value="">Select Owner...</option>
                                    <!-- Loaded via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pet Name <span class="text-danger">*</span></label>
                                <input type="text" name="pet_name" id="petName" class="form-control" required placeholder="e.g., Max, Luna">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Pet Type <span class="text-danger">*</span></label>
                                <select name="pet_type" id="petType" class="form-select" required>
                                    <option value="">Select Type...</option>
                                    <option value="Dog">Dog</option>
                                    <option value="Cat">Cat</option>
                                    <option value="Bird">Bird</option>
                                    <option value="Fish">Fish</option>
                                    <option value="Rabbit">Rabbit</option>
                                    <option value="Hamster">Hamster</option>
                                    <option value="Reptile">Reptile</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Breed</label>
                                <input type="text" name="breed" id="petBreed" class="form-control" placeholder="e.g., Golden Retriever, Persian">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Color</label>
                                <input type="text" name="color" id="petColor" class="form-control" placeholder="e.g., Brown, White">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Age (years)</label>
                                <input type="number" name="age" id="petAge" class="form-control" placeholder="e.g., 2" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="petGender" class="form-select">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Weight (kg)</label>
                                <input type="number" name="weight" id="petWeight" class="form-control" placeholder="e.g., 5.5" step="0.1" min="0">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Microchip Number</label>
                                <input type="text" name="microchip_number" id="microchipNumber" class="form-control" placeholder="e.g., 123456789">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Vaccination Status</label>
                                <select name="vaccination_status" id="vaccinationStatus" class="form-select">
                                    <option value="Up to Date">Up to Date</option>
                                    <option value="Partial">Partial</option>
                                    <option value="None">None</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Registration Date</label>
                                <input type="date" name="registration_date" id="registrationDate" class="form-control">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="petStatus" class="form-select">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                    <option value="Deceased">Deceased</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Pet Photo</label>
                                <input type="file" name="pet_photo" id="petPhoto" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                                <div id="photoPreview" class="mt-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePet()">
                        <i class="fas fa-save"></i> Save Pet
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
                        <i class="fas fa-paw text-primary"></i> Pet Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewContent">
                    <!-- Loaded via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printPet()">
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
                    <p>Are you sure you want to delete this pet record?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="pet_delete.php">
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
        var petModal;
        var isEdit = false;
        
        $(document).ready(function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
            petModal = new bootstrap.Modal(document.getElementById('petModal'));
            
            // Load owners for dropdown
            loadOwners();
            
            // Initialize DataTable
            table = $('#petsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '../api/pets.php',
                    type: 'GET',
                    data: function(d) {
                        d.pet_type = $('#typeFilter').val();
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
                    { data: 5 },
                    { data: 6 },
                    { data: 7, orderable: false, searchable: false }
                ],
                order: [[1, 'asc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
                    emptyTable: 'No pets found',
                    info: 'Showing _START_ to _END_ of _TOTAL_ pets',
                    infoEmpty: 'Showing 0 to 0 of 0 pets',
                    infoFiltered: '(filtered from _MAX_ total pets)',
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
                            var name = item[1].replace(/<[^>]*>/g, '').trim();
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
                url: '../api/pet_stats.php',
                type: 'GET',
                success: function(stats) {
                    $('#totalPets').text(stats.total || 0);
                    $('#activePets').text(stats.active || 0);
                    $('#inactivePets').text(stats.inactive || 0);
                    $('#deceasedPets').text(stats.deceased || 0);
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
            $('#modalTitle').html('<i class="fas fa-paw text-primary"></i> Add New Pet');
            $('#petForm')[0].reset();
            $('#petId').val('');
            $('#photoPreview').html('');
            $('#petStatus').val('Active');
            $('#petGender').val('Male');
            $('#vaccinationStatus').val('Up to Date');
            petModal.show();
        }
        
        // Edit pet
        function editPet(id) {
            isEdit = true;
            $('#modalTitle').html('<i class="fas fa-edit text-warning"></i> Edit Pet Details');
            
            $.ajax({
                url: '../api/pet_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    $('#petId').val(data.id);
                    $('#ownerId').val(data.owner_id);
                    $('#petName').val(data.pet_name);
                    $('#petType').val(data.pet_type);
                    $('#petBreed').val(data.breed);
                    $('#petColor').val(data.color);
                    $('#petAge').val(data.age);
                    $('#petGender').val(data.gender);
                    $('#petWeight').val(data.weight);
                    $('#microchipNumber').val(data.microchip_number);
                    $('#vaccinationStatus').val(data.vaccination_status);
                    $('#registrationDate').val(data.registration_date);
                    $('#petStatus').val(data.status);
                    
                    if (data.pet_photo) {
                        $('#photoPreview').html('<img src="../uploads/' + data.pet_photo + '" class="pet-photo-sm" alt="Pet">');
                    } else {
                        $('#photoPreview').html('');
                    }
                    
                    petModal.show();
                }
            });
        }
        
        // Save pet
        function savePet() {
            var formData = new FormData($('#petForm')[0]);
            var url = isEdit ? '../api/pet_update.php' : '../api/pet_create.php';
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        petModal.hide();
                        table.ajax.reload();
                        showAlert('success', 'Pet saved successfully!');
                    } else {
                        showAlert('danger', response.message || 'Failed to save pet');
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred. Please try again.');
                }
            });
        }
        
        // View pet
        function viewPet(id) {
            $.ajax({
                url: '../api/pet_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    var statusBadge = '';
                    switch(data.status) {
                        case 'Active': statusBadge = 'success'; break;
                        case 'Inactive': statusBadge = 'warning'; break;
                        case 'Deceased': statusBadge = 'danger'; break;
                        default: statusBadge = 'secondary';
                    }
                    
                    var html = `
                        <div class="pet-details">
                            <div class="row">
                                <div class="col-md-12 text-center mb-3">
                                    ${data.pet_photo ? 
                                        '<img src="../uploads/' + data.pet_photo + '" class="img-fluid" style="max-height: 200px; border-radius: 10px;" alt="Pet">' : 
                                        '<div class="alert alert-secondary"><i class="fas fa-paw fa-3x"></i><br>No pet photo available</div>'
                                    }
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">PET NAME:</div>
                                    <div class="info-value"><strong>${data.pet_name || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">OWNER:</div>
                                    <div class="info-value"><strong>${data.owner_name || 'Unassigned'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">PET TYPE:</div>
                                    <div class="info-value"><strong>${data.pet_type || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">BREED:</div>
                                    <div class="info-value"><strong>${data.breed || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">COLOR:</div>
                                    <div class="info-value"><strong>${data.color || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">AGE:</div>
                                    <div class="info-value"><strong>${data.age || 'N/A'} years</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">GENDER:</div>
                                    <div class="info-value"><strong>${data.gender || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">WEIGHT:</div>
                                    <div class="info-value"><strong>${data.weight || 'N/A'} kg</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">MICROCHIP:</div>
                                    <div class="info-value"><strong>${data.microchip_number || 'N/A'}</strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">VACCINATION:</div>
                                    <div class="info-value"><strong>${data.vaccination_status || 'N/A'}</strong></div>
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
        
        // Delete pet
        function deletePet(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = 'Pet: ' + name;
            deleteModal.show();
        }
        
        // Print pet
        function printPet() {
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