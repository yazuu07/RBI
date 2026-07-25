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

$order_column = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
$order_dir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';

$columns = ['a.created_at', 'u.full_name', 'a.action', 'a.table_name', 'a.details', 'a.record_id', 'a.ip_address'];
$order_by = $columns[$order_column] ?? 'a.created_at';
$order_dir = strtoupper($order_dir) === 'ASC' ? 'ASC' : 'DESC';

// Build query
$sql = "SELECT a.*, u.full_name, u.username 
        FROM audit_trails a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE 1=1";
$count_sql = "SELECT COUNT(*) FROM audit_trails a WHERE 1=1";
$params = [];

// Search
if (!empty($search)) {
    $sql .= " AND (a.action LIKE ? OR a.table_name LIKE ? OR a.details LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $count_sql .= " AND (a.action LIKE ? OR a.table_name LIKE ? OR a.details LIKE ? OR u.full_name LIKE ?)";
}

// Filters
if (isset($_GET['action']) && !empty($_GET['action'])) {
    $sql .= " AND a.action = ?";
    $count_sql .= " AND a.action = ?";
    $params[] = $_GET['action'];
}
if (isset($_GET['module']) && !empty($_GET['module'])) {
    $sql .= " AND a.table_name = ?";
    $count_sql .= " AND a.table_name = ?";
    $params[] = $_GET['module'];
}
if (isset($_GET['user']) && !empty($_GET['user'])) {
    $sql .= " AND a.user_id = ?";
    $count_sql .= " AND a.user_id = ?";
    $params[] = $_GET['user'];
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $sql .= " AND DATE(a.created_at) >= ?";
    $count_sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $sql .= " AND DATE(a.created_at) <= ?";
    $count_sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $_GET['date_to'];
}

// Get total
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$filtered_count = $total_records;

// Order and paginate
$sql .= " ORDER BY $order_by $order_dir LIMIT $length OFFSET $start";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$audits = $stmt->fetchAll();

// Format data
$data = [];
foreach ($audits as $audit) {
    $action_badge = '<span class="badge bg-' . 
        ($audit['action'] == 'CREATE' ? 'success' : 
        ($audit['action'] == 'UPDATE' ? 'warning' : 
        ($audit['action'] == 'DELETE' ? 'danger' : 
        ($audit['action'] == 'LOGIN' ? 'info' : 
        ($audit['action'] == 'LOGOUT' ? 'secondary' : 
        ($audit['action'] == 'SQL_EXECUTE' ? 'danger' : 'dark')))))) . '">' . 
        $audit['action'] . '</span>';
    
    $data[] = [
        date('M d, Y', strtotime($audit['created_at'])) . '<br><small class="text-muted">' . date('h:i:s A', strtotime($audit['created_at'])) . '</small>',
        $audit['full_name'] ? '<strong>' . htmlspecialchars($audit['full_name']) . '</strong><br><small class="text-muted">@' . htmlspecialchars($audit['username']) . '</small>' : '<span class="text-muted">System</span>',
        $action_badge,
        '<span class="badge bg-dark">' . ucfirst(str_replace('_', ' ', $audit['table_name'])) . '</span>',
        htmlspecialchars($audit['details'] ?? ''),
        $audit['record_id'] ? '<span class="badge bg-secondary">#' . $audit['record_id'] . '</span>' : '-',
        $audit['ip_address'] ?? '-'
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_count,
    'data' => $data
]);
?>