<?php
// config.php
session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'rbis_db');

// Application Configuration
define('APP_NAME', 'Registry of Barangay Inhabitants System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/rbis');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Cache Configuration
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TIME', 3600); // 1 hour

// Backup Configuration
define('BACKUP_DIR', __DIR__ . '/backups/');

// Create necessary directories
$directories = [UPLOAD_DIR, CACHE_DIR, BACKUP_DIR, __DIR__ . '/logs/'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Database Connection
function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch(PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function hasRole($role_name) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role_name;
}

function hasPermission($module, $action = 'view') {
    $user_role = $_SESSION['role'] ?? 'enumerator';
    
    $permissions = [
        'superadmin' => [
            'dashboard' => ['view'],
            'inhabitants' => ['view', 'add', 'edit', 'delete'],
            'demographic' => ['view'],
            'certification' => ['view', 'add', 'edit', 'delete'],
            'extras' => ['view', 'add', 'edit', 'delete'],
            'reports' => ['view', 'generate'],
            'system' => ['view', 'manage', 'sql_execute']
        ],
        'admin' => [
            'dashboard' => ['view'],
            'inhabitants' => ['view', 'add', 'edit', 'delete'],
            'demographic' => ['view'],
            'certification' => ['view', 'add', 'edit', 'delete'],
            'extras' => ['view', 'add', 'edit', 'delete'],
            'reports' => ['view', 'generate'],
            'system' => ['view_users']
        ],
        'editor' => [
            'dashboard' => ['view'],
            'inhabitants' => ['view', 'add', 'edit', 'delete'],
            'demographic' => ['view', 'add', 'edit', 'delete'],
            'certification' => ['view', 'add', 'edit', 'delete'],
            'extras' => ['view', 'add', 'edit', 'delete'],
            'reports' => ['view', 'generate']
        ],
        'enumerator' => [
            'dashboard' => ['view'],
            'inhabitants' => ['view', 'add', 'edit'],
            'demographic' => ['view'],
            'certification' => ['view', 'add']
        ]
    ];
    
    return isset($permissions[$user_role][$module]) && 
           in_array($action, $permissions[$user_role][$module]);
}

function requirePermission($module, $action = 'view') {
    if (!hasPermission($module, $action)) {
        header('Location: dashboard.php?error=unauthorized');
        exit();
    }
}

// ============================================
// AUDIT FUNCTIONS
// ============================================

function logAudit($user_id, $action, $table_name, $record_id = null, $details = null) {
    $pdo = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_trails (user_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $table_name, $record_id, $details, $ip]);
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

function uploadFile($file, $target_dir = null) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $target_dir = $target_dir ?? UPLOAD_DIR;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return null;
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return null;
    }
    
    $filename = uniqid() . '.' . $ext;
    $filepath = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return null;
}

function deleteFile($filename, $target_dir = null) {
    $target_dir = $target_dir ?? UPLOAD_DIR;
    $filepath = $target_dir . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

function calculateAge($dob) {
    if (empty($dob)) return null;
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    return $birthDate->diff($today)->y;
}

function getUserName($user_id) {
    if (!$user_id) return 'System';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    return $user ? $user['full_name'] : 'Unknown';
}

function getRoleName($role_id) {
    if (!$role_id) return 'Unknown';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT role_name FROM user_roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $role = $stmt->fetch();
    return $role ? $role['role_name'] : 'Unknown';
}

// ============================================
// CACHE FUNCTIONS
// ============================================

function getCache($key) {
    $file = CACHE_DIR . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file)) < CACHE_TIME) {
        return unserialize(file_get_contents($file));
    }
    return null;
}

function setCache($key, $data) {
    $file = CACHE_DIR . md5($key) . '.cache';
    file_put_contents($file, serialize($data));
}

function clearCache($key = null) {
    if ($key) {
        $file = CACHE_DIR . md5($key) . '.cache';
        if (file_exists($file)) unlink($file);
    } else {
        $files = glob(CACHE_DIR . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// ============================================
// DASHBOARD STATISTICS (Cached)
// ============================================

function getDashboardStats() {
    $cache_key = 'dashboard_stats';
    $stats = getCache($cache_key);
    
    if (!$stats) {
        $pdo = getDB();
        
        // Get birthday celebrants today
        $birthday_stmt = $pdo->query("
            SELECT CONCAT(last_name, ', ', first_name) as full_name 
            FROM individual_records 
            WHERE DATE_FORMAT(date_of_birth, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
            LIMIT 5
        ");
        $birthday_celebrants = $birthday_stmt->fetchAll();
        
        // Use the view for main stats
        $view_stmt = $pdo->query("SELECT * FROM demographic_stats");
        $view_stats = $view_stmt->fetch();
        
        $stats = [
            'total_households' => $view_stats['total_households'] ?? 0,
            'total_population' => $view_stats['total_population'] ?? 0,
            'total_male' => $view_stats['total_male'] ?? 0,
            'total_female' => $view_stats['total_female'] ?? 0,
            'birthday_today' => $view_stats['birthday_today'] ?? 0,
            'birthday_celebrants' => $birthday_celebrants
        ];
        
        setCache($cache_key, $stats);
    }
    
    return $stats;
}
?>