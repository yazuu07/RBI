<?php
require_once '../../config.php';
requireLogin();

$pdo = getDB();

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Simple pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build the query with search
if (!empty($search)) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM household_records WHERE last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ?");
    $search_param = "%$search%";
    $count_stmt->execute([$search_param, $search_param, $search_param]);
    $total = $count_stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT * FROM household_records WHERE last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ? ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute([$search_param, $search_param, $search_param]);
    $households = $stmt->fetchAll();
} else {
    $total = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
    $stmt = $pdo->query("SELECT * FROM household_records ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $households = $stmt->fetchAll();
}

$total_pages = ceil($total / $limit);
$user_role = $_SESSION['role'] ?? 'enumerator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Households - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .profile-preview-sm {
            width: 35px;
            height: 35px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        .table td {
            vertical-align: middle;
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
        .search-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .refresh-btn {
            border-radius: 10px;
            padding: 8px 20px;
        }
        .search-result {
            padding: 8px 15px;
            background: #e9ecef;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .search-result .highlight {
            color: #0d6efd;
            font-weight: 600;
        }
        .table th {
            white-space: nowrap;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table td {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-home text-success"></i> Barangay Households
                        <span class="badge bg-success ms-2"><?= number_format($total) ?></span>
                        <?php if (!empty($search)): ?>
                            <span class="search-result">
                                <i class="fas fa-search"></i> 
                                Showing results for: <span class="highlight">"<?= htmlspecialchars($search) ?>"</span>
                                <a href="?" class="text-danger ms-2" title="Clear search">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <div class="btn-toolbar">
                        <button class="btn btn-secondary me-2 refresh-btn" onclick="window.location.reload()" title="Refresh page">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <?php if (hasPermission('inhabitants', 'add')): ?>
                            <a href="create.php" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add New Household
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number"><?= number_format($total) ?></div>
                                    <div class="stat-label">Total Households</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-home"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-primary text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number">
                                        <?= $pdo->query("SELECT COUNT(*) FROM household_records WHERE sex = 'Male'")->fetchColumn() ?>
                                    </div>
                                    <div class="stat-label">Male Heads</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-mars"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card bg-danger text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="stat-number">
                                        <?= $pdo->query("SELECT COUNT(*) FROM household_records WHERE sex = 'Female'")->fetchColumn() ?>
                                    </div>
                                    <div class="stat-label">Female Heads</div>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-venus"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-bar">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search by last name, first name, or middle name..." value="<?= htmlspecialchars($search) ?>">
                                <?php if (!empty($search)): ?>
                                    <a href="?" class="btn btn-outline-danger" title="Clear search">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="window.location.href='?'" title="Reset all filters">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Households Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (count($households) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 45px;">Photo</th>
                                            <th>Name</th>
                                            <th style="width: 50px;">Age</th>
                                            <th style="width: 70px;">Sex</th>
                                            <th style="width: 90px;">Civil Status</th>
                                            <th style="width: 90px;">Household Type</th>
                                            <th style="width: 80px;">Position</th>
                                            <th style="width: 90px;">Citizenship</th>
                                            <th style="width: 90px;">Occupation</th>
                                            <th style="width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($households as $household): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($household['profile_picture']): ?>
                                                        <img src="../../uploads/<?= $household['profile_picture'] ?>" class="profile-preview-sm" alt="Profile">
                                                    <?php else: ?>
                                                        <img src="../../assets/images/default-avatar.png" class="profile-preview-sm" alt="Default">
                                                    <?php endif; ?>
                                                </td>
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
                                                <td><?= $household['household_type'] ?: 'N/A' ?></td>
                                                <td><?= $household['position_in_household'] ?: 'N/A' ?></td>
                                                <td><?= $household['citizenship'] ?: 'N/A' ?></td>
                                                <td><?= $household['occupation'] ?: 'N/A' ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="view.php?id=<?= $household['id'] ?>" class="btn btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (hasPermission('inhabitants', 'edit')): ?>
                                                            <a href="edit.php?id=<?= $household['id'] ?>" class="btn btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (hasPermission('inhabitants', 'delete')): ?>
                                                            <button onclick="deleteRecord(<?= $household['id'] ?>)" class="btn btn-danger" title="Delete">
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

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav>
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page-1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page+1 ?><?= !empty($search) ? '&search='.urlencode($search) : '' ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                            
                            <div class="text-muted small">
                                Showing <?= count($households) ?> of <?= number_format($total) ?> households
                            </div>
                            
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted"></i>
                                <p class="mt-2">
                                    <?php if (!empty($search)): ?>
                                        No households found matching "<strong><?= htmlspecialchars($search) ?></strong>"
                                    <?php else: ?>
                                        No households found
                                    <?php endif; ?>
                                </p>
                                <a href="create.php" class="btn btn-primary btn-sm">Add New Household</a>
                            </div>
                        <?php endif; ?>
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
                    <p>Are you sure you want to delete this household record?</p>
                    <p class="text-danger"><small>This action cannot be undone!</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" action="delete.php">
                        <input type="hidden" name="id" id="deleteId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        
        function deleteRecord(id) {
            document.getElementById('deleteId').value = id;
            deleteModal.show();
        }
    </script>
</body>
</html>