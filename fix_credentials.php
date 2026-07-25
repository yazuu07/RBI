<?php
// fix_credentials.php
require_once 'config.php';

$pdo = getDB();

// The hash for 'admin123'
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Update all users
$stmt = $pdo->prepare("UPDATE users SET password = ?");
$stmt->execute([$hash]);

// Check what users exist
$users = $pdo->query("SELECT id, username, full_name, role_id, is_active FROM users")->fetchAll();

echo "<h3>✅ All users updated with password: <strong>admin123</strong></h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role ID</th><th>Active</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td><strong>" . $user['username'] . "</strong></td>";
    echo "<td>" . $user['full_name'] . "</td>";
    echo "<td>" . $user['role_id'] . "</td>";
    echo "<td>" . ($user['is_active'] ? '✅' : '❌') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<div style='padding: 15px; background: #d4edda; border-radius: 5px;'>";
echo "<strong>Login Credentials:</strong><br>";
echo "Username: <strong>superadmin</strong> | Password: <strong>admin123</strong><br>";
echo "Username: <strong>admin</strong> | Password: <strong>admin123</strong><br>";
echo "Username: <strong>editor</strong> | Password: <strong>admin123</strong><br>";
echo "Username: <strong>enumerator</strong> | Password: <strong>admin123</strong><br>";
echo "</div>";

echo '<br><a href="login.php" class="btn btn-primary">Go to Login</a>';
?>