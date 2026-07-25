<?php
require_once '../config.php';
requireLogin();
requirePermission('system', 'manage');

$pdo = getDB();
$success = false;
$error = '';

// Get current settings (you can store these in a settings table or config file)
// For now, we'll use a simple settings array stored in a JSON file or database
$settings = [
    'barangay_name' => isset($_SESSION['barangay_name']) ? $_SESSION['barangay_name'] : 'Santo Cristo',
    'barangay_captain' => isset($_SESSION['barangay_captain']) ? $_SESSION['barangay_captain'] : 'Hon. Daniel Berroya',
    'barangay_address' => isset($_SESSION['barangay_address']) ? $_SESSION['barangay_address'] : 'San Antonio Hall, Quezon City',
    'barangay_contact' => isset($_SESSION['barangay_contact']) ? $_SESSION['barangay_contact'] : '123-4567',
    'barangay_email' => isset($_SESSION['barangay_email']) ? $_SESSION['barangay_email'] : 'barangay@example.com',
    'system_name' => isset($_SESSION['system_name']) ? $_SESSION['system_name'] : 'Registry of Barangay Inhabitants System',
    'system_version' => isset($_SESSION['system_version']) ? $_SESSION['system_version'] : '1.0.0',
    'maintenance_mode' => isset($_SESSION['maintenance_mode']) ? $_SESSION['maintenance_mode'] : 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update settings in session (or database)
    $_SESSION['barangay_name'] = $_POST['barangay_name'] ?? 'Santo Cristo';
    $_SESSION['barangay_captain'] = $_POST['barangay_captain'] ?? 'Hon. Daniel Berroya';
    $_SESSION['barangay_address'] = $_POST['barangay_address'] ?? 'San Antonio Hall, Quezon City';
    $_SESSION['barangay_contact'] = $_POST['barangay_contact'] ?? '123-4567';
    $_SESSION['barangay_email'] = $_POST['barangay_email'] ?? 'barangay@example.com';
    $_SESSION['system_name'] = $_POST['system_name'] ?? 'Registry of Barangay Inhabitants System';
    $_SESSION['system_version'] = $_POST['system_version'] ?? '1.0.0';
    $_SESSION['maintenance_mode'] = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    $success = "Settings updated successfully!";
    logAudit($_SESSION['user_id'], 'UPDATE', 'system_settings', null, "System settings updated");
    
    // Refresh settings
    $settings = [
        'barangay_name' => $_SESSION['barangay_name'],
        'barangay_captain' => $_SESSION['barangay_captain'],
        'barangay_address' => $_SESSION['barangay_address'],
        'barangay_contact' => $_SESSION['barangay_contact'],
        'barangay_email' => $_SESSION['barangay_email'],
        'system_name' => $_SESSION['system_name'],
        'system_version' => $_SESSION['system_version'],
        'maintenance_mode' => $_SESSION['maintenance_mode']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        .settings-section {
            margin-bottom: 30px;
        }
        .settings-section h5 {
            border-bottom: 2px solid #e8ecf1;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .setting-card {
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-sliders-h text-primary"></i> System Settings
                    </h1>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="setting-card">
                    <form method="POST">
                        <!-- Barangay Information -->
                        <div class="settings-section">
                            <h5><i class="fas fa-landmark text-primary"></i> Barangay Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Barangay Name</label>
                                    <input type="text" name="barangay_name" class="form-control" value="<?= htmlspecialchars($settings['barangay_name']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Barangay Captain</label>
                                    <input type="text" name="barangay_captain" class="form-control" value="<?= htmlspecialchars($settings['barangay_captain']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="barangay_address" class="form-control" value="<?= htmlspecialchars($settings['barangay_address']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" name="barangay_contact" class="form-control" value="<?= htmlspecialchars($settings['barangay_contact']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="barangay_email" class="form-control" value="<?= htmlspecialchars($settings['barangay_email']) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="settings-section">
                            <h5><i class="fas fa-cogs text-info"></i> System Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">System Name</label>
                                    <input type="text" name="system_name" class="form-control" value="<?= htmlspecialchars($settings['system_name']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">System Version</label>
                                    <input type="text" name="system_version" class="form-control" value="<?= htmlspecialchars($settings['system_version']) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="settings-section">
                            <h5><i class="fas fa-status text-warning"></i> System Status</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="form-check form-switch">
                                        <input type="checkbox" name="maintenance_mode" id="maintenanceMode" class="form-check-input" <?= $settings['maintenance_mode'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="maintenanceMode">
                                            <i class="fas fa-<?= $settings['maintenance_mode'] ? 'exclamation-triangle text-danger' : 'check-circle text-success' ?>"></i>
                                            <?= $settings['maintenance_mode'] ? 'Maintenance Mode Enabled' : 'System Active' ?>
                                        </label>
                                    </div>
                                    <small class="text-muted">When enabled, only administrators can access the system</small>
                                </div>
                            </div>
                        </div>

                        <!-- System Stats -->
                        <div class="settings-section">
                            <h5><i class="fas fa-chart-line text-success"></i> System Statistics</h5>
                            <div class="row">
                                <?php
                                $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                $total_citizens = $pdo->query("SELECT COUNT(*) FROM individual_records")->fetchColumn();
                                $total_households = $pdo->query("SELECT COUNT(*) FROM household_records")->fetchColumn();
                                $total_certificates = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
                                $total_vehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
                                ?>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <h4><?= number_format($total_users) ?></h4>
                                        <small class="text-muted">Users</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <h4><?= number_format($total_citizens) ?></h4>
                                        <small class="text-muted">Citizens</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <h4><?= number_format($total_households) ?></h4>
                                        <small class="text-muted">Households</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 text-center">
                                        <h4><?= number_format($total_certificates) ?></h4>
                                        <small class="text-muted">Certificates</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>