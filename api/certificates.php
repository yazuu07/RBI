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
    'c.id',
    'c.certificate_number',
    'c.certificate_type',
    'c.status',
    'c.issued_date',
    'c.created_at'
];

$order_by = $columns[$order_column] ?? 'c.created_at';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

// Build query with join to get resident name
$sql = "SELECT c.*, 
        CONCAT(i.last_name, ', ', i.first_name) as full_name,
        i.first_name, i.last_name, i.middle_name, i.ext_name
        FROM certificates c 
        LEFT JOIN individual_records i ON c.resident_id = i.id 
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM certificates c WHERE 1=1";
$params = [];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (i.last_name LIKE ? OR i.first_name LIKE ? OR c.certificate_type LIKE ? OR c.certificate_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_sql .= " AND (i.last_name LIKE ? OR i.first_name LIKE ? OR c.certificate_type LIKE ? OR c.certificate_number LIKE ?)";
}

// Apply additional filters
if (isset($_GET['certificate_type']) && !empty($_GET['certificate_type'])) {
    $sql .= " AND c.certificate_type = ?";
    $count_sql .= " AND c.certificate_type = ?";
    $params[] = $_GET['certificate_type'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $sql .= " AND c.status = ?";
    $count_sql .= " AND c.status = ?";
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
    // Full name
    $full_name = htmlspecialchars($record['last_name']) . ', ' . htmlspecialchars($record['first_name']);
    if ($record['middle_name']) {
        $full_name .= ' ' . htmlspecialchars($record['middle_name'][0]) . '.';
    }
    if ($record['ext_name']) {
        $full_name .= ' ' . htmlspecialchars($record['ext_name']);
    }
    
    // Certificate types badges
    $types = explode(',', $record['certificate_type']);
    $type_badges = '';
    foreach ($types as $type) {
        $type = trim($type);
        $badge_color = 'primary';
        if (strpos(strtoupper($type), 'RESIDENCY') !== false) $badge_color = 'info';
        elseif (strpos(strtoupper($type), 'INDIGENCY') !== false) $badge_color = 'warning';
        elseif (strpos(strtoupper($type), 'ID') !== false) $badge_color = 'success';
        elseif (strpos(strtoupper($type), 'CLEARANCE') !== false) $badge_color = 'danger';
        $type_badges .= '<span class="badge bg-' . $badge_color . ' me-1">' . htmlspecialchars($type) . '</span>';
    }
    
    // Status badge
    $status_badge = '';
    switch($record['status']) {
        case 'Pending':
            $status_badge = '<span class="badge bg-warning text-dark">Pending</span>';
            break;
        case 'Issued':
            $status_badge = '<span class="badge bg-success">Issued</span>';
            break;
        case 'Expired':
            $status_badge = '<span class="badge bg-danger">Expired</span>';
            break;
        case 'Cancelled':
            $status_badge = '<span class="badge bg-secondary">Cancelled</span>';
            break;
        default:
            $status_badge = '<span class="badge bg-secondary">' . $record['status'] . '</span>';
    }
    
    // Actions
    $actions = '
        <div class="btn-group btn-group-sm">
            <button onclick="viewCertificate(' . $record['id'] . ')" class="btn btn-info" title="View">
                <i class="fas fa-eye"></i>
            </button>';
    
    if (hasPermission('certification', 'edit')) {
        $actions .= '
            <button onclick="editCertificate(' . $record['id'] . ')" class="btn btn-warning" title="Edit">
                <i class="fas fa-edit"></i>
            </button>';
    }
    
    if (hasPermission('certification', 'delete')) {
        $actions .= '
            <button onclick="deleteCertificate(' . $record['id'] . ', \'' . 
                addslashes($full_name) . '\')" class="btn btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
            </button>';
    }
    
    $actions .= '
            <button onclick="printCertificate(' . $record['id'] . ')" class="btn btn-secondary" title="Print">
                <i class="fas fa-print"></i>
            </button>
        </div>';
    
    $data[] = [
        $full_name,
        $type_badges,
        $record['certificate_number'] ?: 'N/A',
        $status_badge,
        $record['issued_date'] ? date('M d, Y', strtotime($record['issued_date'])) : 'N/A',
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