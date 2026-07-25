<?php
// api/citizens.php - Add this at the very top for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Then your existing code...
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

// Order columns
$order_column = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

$columns = [
    'id',
    'last_name',
    'first_name',
    'age',
    'sex',
    'civil_status',
    'highest_education',
    'profile_picture',
    'created_at'
];

$order_by = $columns[$order_column] ?? 'created_at';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

// Build base query
$sql = "SELECT * FROM individual_records WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM individual_records WHERE 1=1";
$params = [];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_sql .= " AND (last_name LIKE ? OR first_name LIKE ? OR middle_name LIKE ?)";
}

// Apply additional filters
if (isset($_GET['sex']) && !empty($_GET['sex'])) {
    $sql .= " AND sex = ?";
    $count_sql .= " AND sex = ?";
    $params[] = $_GET['sex'];
}

if (isset($_GET['civil_status']) && !empty($_GET['civil_status'])) {
    $sql .= " AND civil_status = ?";
    $count_sql .= " AND civil_status = ?";
    $params[] = $_GET['civil_status'];
}

if (isset($_GET['education']) && !empty($_GET['education'])) {
    $sql .= " AND highest_education = ?";
    $count_sql .= " AND highest_education = ?";
    $params[] = $_GET['education'];
}

// Get total count
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Get filtered count
$filtered_count = $total_records;

// Apply ordering and pagination
$sql .= " ORDER BY $order_by $order_dir LIMIT $length OFFSET $start";

// Execute query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

// Prepare data
$data = [];
foreach ($records as $record) {
    // Profile picture
    $profile_pic = $record['profile_picture'] 
        ? '<img src="../uploads/' . $record['profile_picture'] . '" class="profile-preview-sm" alt="Profile">'
        : '<img src="../assets/images/default-avatar.png" class="profile-preview-sm" alt="Default">';
    
    // Sex badge
    $sex_badge = '<span class="badge bg-' . 
        ($record['sex'] == 'Male' ? 'primary' : 
        ($record['sex'] == 'Female' ? 'danger' : 'secondary')) . '">' . 
        $record['sex'] . '</span>';
    
    // Full name
    $full_name = '<strong>' . htmlspecialchars($record['last_name']) . '</strong>, ' . 
                 htmlspecialchars($record['first_name']);
    if ($record['middle_name']) {
        $full_name .= ' ' . htmlspecialchars($record['middle_name'][0]) . '.';
    }
    if ($record['ext_name']) {
        $full_name .= ' <small class="text-muted">' . htmlspecialchars($record['ext_name']) . '</small>';
    }
    
    // Actions
    $actions = '
        <div class="btn-group btn-group-sm table-actions">
            <a href="view.php?id=' . $record['id'] . '" class="btn btn-info" title="View">
                <i class="fas fa-eye"></i>
            </a>';
    
    if (hasPermission('inhabitants', 'edit')) {
        $actions .= '
            <a href="edit.php?id=' . $record['id'] . '" class="btn btn-warning" title="Edit">
                <i class="fas fa-edit"></i>
            </a>';
    }
    
    if (hasPermission('inhabitants', 'delete')) {
        $actions .= '
            <button onclick="deleteRecord(' . $record['id'] . ', \'' . 
                addslashes($record['last_name'] . ', ' . $record['first_name']) . '\')" 
                class="btn btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
            </button>';
    }
    
    $actions .= '</div>';
    
    $data[] = [
        $profile_pic,
        $full_name,
        $record['age'] ?? 'N/A',
        $sex_badge,
        $record['civil_status'],
        $record['highest_education'] ?? 'N/A',
        $actions
    ];
}

// Return JSON response
echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_count,
    'data' => $data
]);
?>