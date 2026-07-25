<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

$pdo = getDB();
$success = false;
$error = '';

// Handle Add/Edit Role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
    $role_name = isset($_POST['role_name']) ? trim($_POST['role_name']) : '';
    $role_description = isset($_POST['role_description']) ? trim($_POST['role_description']) : '';
    
    if ($action === 'add') {
        if (empty($role_name)) {
            $error = 'Role name is required';
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_roles (role_name, role_description) VALUES (?, ?)");
            if ($stmt->execute([$role_name, $role_description])) {
                $success = "Role added successfully!";
                logAudit($_SESSION['user_id'], 'CREATE', 'user_roles', $pdo->lastInsertId(), "Created role: $role_name");
            } else {
                $error = "Failed to add role";
            }
        }
    } elseif ($action === 'edit') {
        if (empty($role_name)) {
            $error = 'Role name is required';
        } else {
            $stmt = $pdo->prepare("UPDATE user_roles SET role_name = ?, role_description = ? WHERE id = ?");
            if ($stmt->execute([$role_name, $role_description, $role_id])) {
                $success = "Role updated successfully!";
                logAudit($_SESSION['user_id'], 'UPDATE', 'user_roles', $role_id, "Updated role: $role_name");
            } else {
                $error = "Failed to update role";
            }
        }
    } elseif ($action === 'delete') {
        $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
        
        // Check if role is in use
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $check->execute([$role_id]);
        if ($check->fetchColumn() > 0) {
            $error = "Cannot delete role. It is currently assigned to users.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM user_roles WHERE id = ?");
            if ($stmt->execute([$role_id])) {
                $success = "Role deleted successfully!";
                logAudit($_SESSION['user_id'], 'DELETE', 'user_roles', $role_id, "Deleted role");
            } else {
                $error = "Failed to delete role";
            }
        }
    }
}

// Get all roles with user count
$roles = $pdo->query("
    SELECT r.*, COUNT(u.id) as user_count 
    FROM user_roles r 
    LEFT JOIN users u ON r.id = u.role_id 
    GROUP BY r.id 
    ORDER BY r.id
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Group - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-tag text-primary"></i> Users Group / Roles
                    </h1>
                    <button class="btn btn-success" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Role
                    </button>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="rolesTable" class="table table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Role Name</th>
                                        <th>Description</th>
                                        <th>Users</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><?= $role['id'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $role['role_name'] == 'superadmin' ? 'danger' : 
                                                    ($role['role_name'] == 'admin' ? 'primary' : 
                                                    ($role['role_name'] == 'editor' ? 'success' : 'warning')) 
                                                ?>">
                                                    <?= strtoupper($role['role_name']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($role['role_description']) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= $role['user_count'] ?></span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button onclick="editRole(<?= $role['id'] ?>)" class="btn btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($role['role_name'] != 'superadmin'): ?>
                                                        <button onclick="deleteRole(<?= $role['id'] ?>, '<?= addslashes($role['role_name']) ?>')" class="btn btn-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
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

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-user-tag text-primary"></i> Add New Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="roleForm">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="role_id" id="roleId" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="role_name" id="roleName" class="form-control" required>
                            <small class="text-muted">Use lowercase without spaces (e.g., superadmin, admin)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="role_description" id="roleDescription" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveRole()">
                        <i class="fas fa-save"></i> Save Role
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this role?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                    <div id="deletePreview" class="alert alert-secondary">
                        <strong id="deleteName"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="role_id" id="deleteId">
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
        var roleModal;
        var deleteModal;
        
        $(document).ready(function() {
            roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            
            $('#rolesTable').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ roles'
                }
            });
        });
        
        function openAddModal() {
            $('#modalTitle').html('<i class="fas fa-user-tag text-primary"></i> Add New Role');
            $('#formAction').val('add');
            $('#roleId').val('');
            $('#roleName').val('');
            $('#roleDescription').val('');
            roleModal.show();
        }
        
        function editRole(id) {
            $.ajax({
                url: '../api/role_details.php?id=' + id,
                type: 'GET',
                success: function(data) {
                    $('#modalTitle').html('<i class="fas fa-edit text-warning"></i> Edit Role');
                    $('#formAction').val('edit');
                    $('#roleId').val(data.id);
                    $('#roleName').val(data.role_name);
                    $('#roleDescription').val(data.role_description);
                    roleModal.show();
                }
            });
        }
        
        function saveRole() {
            var form = $('#roleForm');
            var data = form.serialize();
            
            $.ajax({
                url: 'groups.php',
                type: 'POST',
                data: data,
                success: function() {
                    location.reload();
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        }
        
        function deleteRole(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteName').textContent = 'Role: ' + name;
            deleteModal.show();
        }
    </script>
</body>
</html>