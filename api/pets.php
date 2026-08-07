<?php
// api/pets.php - Updated to handle duplicates properly
require_once '../config.php';
requireLogin();

header('Content-Type: application/json');

$pdo = getDB();

// DataTables parameters
$draw = isset($_GET['draw']) ? intval($_GET['draw']) : 0;
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;
$length = isset($_GET['length']) ? intval($_GET['length']) : 10;
$search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

$order_column = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

$columns = ['p.id', 'p.pet_name', 'p.pet_type', 'p.breed', 'p.age', 'p.status', 'owner_name'];
$order_by = $columns[$order_column] ?? 'p.created_at';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

// Build query with DISTINCT to prevent duplicates
$sql = "SELECT DISTINCT p.*, 
        CONCAT(h.last_name, ', ', h.first_name, 
               IF(h.middle_name IS NOT NULL, CONCAT(' ', SUBSTRING(h.middle_name, 1, 1), '.'), ''),
               IF(h.ext_name IS NOT NULL, CONCAT(' (', h.ext_name, ')'), '')) as owner_name
        FROM pets p 
        LEFT JOIN household_records h ON p.owner_id = h.id 
        WHERE 1=1";
$count_sql = "SELECT COUNT(DISTINCT p.id) FROM pets p WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.pet_name LIKE ? OR p.pet_type LIKE ? OR p.breed LIKE ? OR h.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_sql .= " AND (p.pet_name LIKE ? OR p.pet_type LIKE ? OR p.breed LIKE ? OR h.last_name LIKE ?)";
}

if (isset($_GET['pet_type']) && !empty($_GET['pet_type'])) {
    $sql .= " AND p.pet_type = ?";
    $count_sql .= " AND p.pet_type = ?";
    $params[] = $_GET['pet_type'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND p.status = ?";
    $count_sql .= " AND p.status = ?";
    $params[] = $_GET['status'];
}

// Get total count
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$filtered_count = $total_records;

// Apply ordering and pagination
$sql .= " ORDER BY $order_by $order_dir LIMIT $length OFFSET $start";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$data = [];
foreach ($records as $record) {
    $photo = $record['pet_photo'] 
        ? '<img src="../uploads/' . $record['pet_photo'] . '" class="pet-photo-sm" alt="Pet">'
        : '<div class="pet-photo-placeholder"><i class="fas fa-paw"></i></div>';
    
    $status_badge = '';
    switch($record['status']) {
        case 'Active': $status_badge = '<span class="badge bg-success">Active</span>'; break;
        case 'Inactive': $status_badge = '<span class="badge bg-warning text-dark">Inactive</span>'; break;
        case 'Deceased': $status_badge = '<span class="badge bg-danger">Deceased</span>'; break;
        default: $status_badge = '<span class="badge bg-secondary">' . $record['status'] . '</span>';
    }
    
    $type_badge = '<span class="pet-type-badge ' . strtolower($record['pet_type']) . '">' . ($record['pet_type'] ?? 'N/A') . '</span>';
    
    $actions = '
        <div class="btn-group btn-group-sm">
            <button onclick="viewPet(' . $record['id'] . ')" class="btn btn-info" title="View">
                <i class="fas fa-eye"></i>
            </button>';
    
    if (hasPermission('extras', 'edit')) {
        $actions .= '
            <button onclick="editPet(' . $record['id'] . ')" class="btn btn-warning" title="Edit">
                <i class="fas fa-edit"></i>
            </button>';
    }
    
    if (hasPermission('extras', 'delete')) {
        $actions .= '
            <button onclick="deletePet(' . $record['id'] . ', \'' . addslashes($record['pet_name']) . '\')" class="btn btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
            </button>';
    }
    $actions .= '</div>';
    
    $data[] = [
        $photo,
        $record['pet_name'] ?: 'N/A',
        $type_badge,
        $record['breed'] ?: 'N/A',
        $record['age'] ?: 'N/A',
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