<?php
// login.php - Plain text password version (FOR TESTING ONLY)
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT u.*, r.role_name 
        FROM users u 
        LEFT JOIN user_roles r ON u.role_id = r.id 
        WHERE u.username = ? AND u.is_active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // COMPARE PLAIN TEXT PASSWORD (NO HASHING)
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['role_id'] = $user['role_id'];
        
        // Update last login
        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);
        
        logAudit($user['id'], 'LOGIN', 'users', $user['id'], 'User logged in');
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
             background: #9EBD13;
             background: linear-gradient(90deg, rgba(158, 189, 19, 1) 0%, rgba(0, 133, 82, 1) 100%);      height: 100vh;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            animation: slideUp 0.6s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .icon {
            font-size: 50px;
            color: #e84e06;
            margin-bottom: 10px;
        }
        .login-header h2 {
            color: #333;
            font-weight: 700;
            font-size: 24px;
        }
        .login-header p {
            color: #888;
            font-size: 14px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e8ecf1;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #f16d0f 0%, #c68331 100%);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            color: white;
            transition: transform 0.3s;
        }
        .btn-login:hover {
            transform: scale(1.02);
            color: white;
        }
        .role-hint {
            font-size: 11px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
        }
        .role-hint span {
            display: inline-block;
            margin: 3px 5px;
            padding: 2px 10px;
            border-radius: 12px;
            background: #f0f0f0;
            font-size: 5px;
        }
        .role-hint .superadmin { background: #dc3545; color: white; }
        .role-hint .admin { background: #0d6efd; color: white; }
        .role-hint .editor { background: #198754; color: white; }
        .role-hint .enumerator { background: #ffc107; color: #333; }
        .barangay-seal {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 5px;
            display: block;
        }
        .version {
            font-size: 11px;
            color: #bbb;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="barangay-seal">
                    <img src="assets/images/Logo.png" alt="Landmark Icon" width="100" height="100" style="border-radius: 50%;">
                </div>
                <h2>Registry of Barangay Inhabitants</h2>
                <p>System Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="role-hint text-center">
                <strong>
                    Registry of Barangay Nayong Kanluran Inhabitants System 
                    Powered by: Lavender Fields Research and Development
                    Developed by: NICOR TECH</strong><br>
            </div>
            
            <div class="text-center version">
                <small>RBIS v1.0.0 &copy; <?= date('Y') ?></small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>