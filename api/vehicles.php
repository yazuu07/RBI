<?php
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
    'v.id',
    'v.plate_number',
    'v.vehicle_type',
    'v.status',
    'v.brand',
    'v.color',
    'v.year_model',
    'v.created_at'
];

$order_by = $columns[$order_column] ?? 'v.created_at';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

// Build query with join to get owner info
$sql = "SELECT v.*, 
        CONCAT(h.last_name, ', ', h.first_name, 
               IF(h.middle_name IS NOT NULL, CONCAT(' ', SUBSTRING(h.middle_name, 1, 1), '.'), ''),
               IF(h.ext_name IS NOT NULL, CONCAT(' ', h.ext_name), '')) as owner_name
        FROM vehicles v 
        LEFT JOIN household_records h ON v.owner_id = h.id 
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM vehicles v WHERE 1=1";
$params = [];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR v.brand LIKE ? OR v.color LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_sql .= " AND (v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR v.brand LIKE ? OR v.color LIKE ?)";
}

// Apply additional filters
if (isset($_GET['vehicle_type']) && !empty($_GET['vehicle_type'])) {
    $sql .= " AND v.vehicle_type = ?";
    $count_sql .= " AND v.vehicle_type = ?";
    $params[] = $_GET['vehicle_type'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND v.status = ?";
    $count_sql .= " AND v.status = ?";
    $params[] = $_GET['status'];
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
    // Vehicle photo
    $photo = $record['vehicle_photo'] 
        ? '<img src="../uploads/' . $record['vehicle_photo'] . '" class="vehicle-photo-sm" alt="Vehicle">'
        : '<div class="vehicle-photo-placeholder"><i class="fas fa-car"></i></div>';
    
    // Status badge
    $status_badge = '';
    switch($record['status']) {
        case 'Active':
            $status_badge = '<span class="badge bg-success">Active</span>';
            break;
        case 'Inactive':
            $status_badge = '<span class="badge bg-danger">Inactive</span>';
            break;
        case 'Expired':
            $status_badge = '<span class="badge bg-warning text-dark">Expired</span>';
            break;
        default:
            $status_badge = '<span class="badge bg-secondary">' . $record['status'] . '</span>';
    }
    
    // Actions
    $actions = '
        <div class="btn-group btn-group-sm">
            <button onclick="viewVehicle(' . $record['id'] . ')" class="btn btn-info" title="View">
                <i class="fas fa-eye"></i>
            </button>';
    
    if (hasPermission('extras', 'edit')) {
        $actions .= '
            <button onclick="editVehicle(' . $record['id'] . ')" class="btn btn-warning" title="Edit">
                <i class="fas fa-edit"></i>
            </button>';
    }
    
    if (hasPermission('extras', 'delete')) {
        $actions .= '
            <button onclick="deleteVehicle(' . $record['id'] . ', \'' . 
                addslashes($record['plate_number']) . '\')" class="btn btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
            </button>';
    }
    
    $actions .= '</div>';
    
    $data[] = [
        $photo,
        $record['plate_number'] ?: 'N/A',
        $record['vehicle_type'] ?: 'N/A',
        $status_badge,
        $record['owner_name'] ?: 'Unassigned',
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_count,
    'data' => $data
]);
?>