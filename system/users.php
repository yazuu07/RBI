<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'view');

$pdo = getDB();
$user_role = $_SESSION['role'] ?? 'enumerator';
$is_superadmin = ($user_role == 'superadmin');
$is_admin = ($user_role == 'admin');

// Get all users
$users = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    LEFT JOIN user_roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC
")->fetchAll();

// Get roles for dropdown
$roles = $pdo->query("SELECT * FROM user_roles ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - RBIS</title>
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
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
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
                        <i class="fas fa-users-cog text-primary"></i> Users Management
                        <span class="badge bg-primary ms-2"><?= count($users) ?></span>
                    </h1>
                    <?php if ($is_superadmin): ?>
                        <button class="btn btn-success" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New User
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= count($users) ?></div>
                                    <div class="stat-label">Total Users</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number">
                                        <?= count(array_filter($users, fn($u) => $u['is_active'] == 1)) ?>
                                    </div>
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
                                    <div class="stat-number">
                                        <?= count(array_filter($users, fn($u) => $u['is_active'] == 0)) ?>
                                    </div>
                                    <div class="stat-label">Inactive</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-pause-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number">
                                        <?= count(array_filter($users, fn($u) => $u['role_name'] == 'admin')) ?>
                                    </div>
                                    <div class="stat-label">Admins</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-user-shield"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usersTable" class="table table-hover table-striped" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width: 50px;">Avatar</th>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <?php if ($is_superadmin): ?>
                                            <th style="width: 150px;">Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($user['full_name'], 0, 2)) ?>
                                                </div>
                                            </td>
                                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $user['role_name'] == 'superadmin' ? 'danger' : 
                                                    ($user['role_name'] == 'admin' ? 'primary' : 
                                                    ($user['role_name'] == 'editor' ? 'success' : 'warning')) 
                                                ?>">
                                                    <?= strtoupper($user['role_name']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td><?= $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never' ?></td>
                                            <?php if ($is_superadmin): ?>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button onclick="editUser(<?= $user['id'] ?>)" class="btn btn-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <button onclick="deleteUser(<?= $user['id'] ?>, '<?= addslashes($user['username']) ?>')" class="btn btn-danger" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-user-plus text-success"></i> Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" name="id" id="userId">
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="fullName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role_id" id="roleId" class="form-select" required>
                                <option value="">Select Role...</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?= $role['id'] ?>"><?= ucfirst($role['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="passwordField">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 characters">
                            <small class="text-muted">Leave blank to keep current password when editing</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="isActive" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">
                        <i class="fas fa-save"></i> Save User
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
                    <p>Are you sure you want to delete this user?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="user_delete.php">
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
        var userModal;
        var isEdit = false;
        
        $(document).ready(function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            userModal = new bootstrap.Modal(document.getElementById('userModal'));
            
            $('#usersTable').DataTable({
                pageLength: 25,
                order: [[1, 'asc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ users'
                }
            });
        });
        
        function openCreateModal() {
            isEdit = false;
            $('#modalTitle').html('<i class="fas fa-user-plus text-success"></i> Add New User');
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#password').prop('required', true);
            $('#passwordField').show();
            userModal.show();
        }
        
        function editUser(id) {
            isEdit = true;
            $('#modalTitle').html('<i class="fas fa-user-edit text-warning"></i> Edit User');
            
            $.ajax({
                url: '../api/user_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    $('#userId').val(data.id);
                    $('#username').val(data.username);
                    $('#fullName').val(data.full_name);
                    $('#email').val(data.email);
                    $('#roleId').val(data.role_id);
                    $('#isActive').val(data.is_active);
                    $('#password').prop('required', false);
                    $('#passwordField').hide();
                    userModal.show();
                }
            });
        }
        
        function saveUser() {
            var form = $('#userForm');
            var data = form.serialize();
            var url = isEdit ? '../api/user_update.php' : '../api/user_create.php';
            
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        userModal.hide();
                        location.reload();
                    } else {
                        showAlert('danger', response.message || 'Failed to save user');
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred. Please try again.');
                }
            });
        }
        
        function deleteUser(id, username) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = 'User: ' + username;
            deleteModal.show();
        }
        
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