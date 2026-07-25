<?php
require_once '../../config.php';
requireLogin();
requirePermission('system', 'sql_execute');

$pdo = getDB();
$result = null;
$error = null;
$affected_rows = 0;
$query_time = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
    $sql_query = trim($_POST['sql_query']);
    
    if (!empty($sql_query)) {
        // Log the query
        $query_start = microtime(true);
        
        try {
            // Determine query type
            $query_type = strtoupper(strtok($sql_query, ' '));
            $is_destructive = in_array($query_type, ['DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE']);
            
            // If destructive, require confirmation
            if ($is_destructive && !isset($_POST['confirm'])) {
                $error = "⚠️ This is a destructive query. Please confirm by checking the confirmation box below.";
            } else {
                $stmt = $pdo->prepare($sql_query);
                $stmt->execute();
                
                if ($query_type === 'SELECT') {
                    $result = $stmt->fetchAll();
                    $affected_rows = count($result);
                } else {
                    $affected_rows = $stmt->rowCount();
                }
                
                $query_time = round((microtime(true) - $query_start) * 1000, 2);
                
                // Log successful query
                logAudit($_SESSION['user_id'], 'SQL_EXECUTE', 'database', null, 
                    "Executed: " . substr($sql_query, 0, 100) . "... (Affected: $affected_rows)");
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
        }
    }
}

// Get query history
$history = $pdo->query("
    SELECT * FROM sql_query_log 
    WHERE user_id = " . $_SESSION['user_id'] . "
    ORDER BY executed_at DESC 
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL Executor - RBIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/custom.css">
    <style>
        .sql-editor {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            background: #1e1e2e;
            color: #d4d4d4;
            border-radius: 10px;
            padding: 15px;
            min-height: 200px;
            border: none;
            width: 100%;
            resize: vertical;
        }
        .sql-editor:focus {
            outline: 2px solid #667eea;
        }
        .sql-result {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            max-height: 400px;
            overflow: auto;
        }
        .sql-result table {
            font-size: 12px;
        }
        .sql-result table th {
            background: #667eea;
            color: white;
            position: sticky;
            top: 0;
        }
        .stat-card {
            border-radius: 15px;
            padding: 15px;
            transition: transform 0.3s;
            cursor: default;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
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
                        <i class="fas fa-database text-danger"></i> SQL Query Executor
                        <span class="badge bg-danger ms-2">SuperAdmin Only</span>
                    </h1>
                    <a href="backup.php" class="btn btn-primary">
                        <i class="fas fa-database"></i> Backup Database
                    </a>
                </div>

                <!-- Warning -->
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> This tool allows execution of raw SQL queries. 
                    Incorrect queries can damage or delete data. Use with extreme caution!
                </div>

                <!-- SQL Editor -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-code text-primary"></i> SQL Query Editor</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <textarea name="sql_query" class="sql-editor" placeholder="Enter your SQL query here..."><?= isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : 'SELECT * FROM users LIMIT 10;' ?></textarea>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" name="confirm" id="confirmCheck" class="form-check-input">
                                        <label for="confirmCheck" class="form-check-label text-danger">
                                            <i class="fas fa-exclamation-circle"></i> I confirm this query is safe to execute
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-play"></i> Execute Query
                                    </button>
                                    <button type="reset" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($result !== null): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle text-success"></i> Query Results
                                <span class="badge bg-success ms-2"><?= $affected_rows ?> rows</span>
                                <span class="badge bg-info ms-2"><?= $query_time ?> ms</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($result) > 0): ?>
                                <div class="sql-result">
                                    <table class="table table-bordered table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <?php foreach (array_keys((array)$result[0]) as $col): ?>
                                                    <th><?= htmlspecialchars($col) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($result as $row): ?>
                                                <tr>
                                                    <?php foreach ((array)$row as $value): ?>
                                                        <td><?= htmlspecialchars($value ?? 'NULL') ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Query executed successfully. No results to display.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Query History -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-history text-warning"></i> Query History (Last 50)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($history) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Query</th>
                                            <th>Type</th>
                                            <th>Affected Rows</th>
                                            <th>Time (ms)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $log): ?>
                                            <tr>
                                                <td><?= date('M d, Y h:i A', strtotime($log['executed_at'])) ?></td>
                                                <td><code><?= htmlspecialchars(substr($log['query'], 0, 80)) ?>...</code></td>
                                                <td><span class="badge bg-<?= 
                                                    $log['query_type'] == 'SELECT' ? 'success' : 
                                                    ($log['query_type'] == 'INSERT' ? 'primary' : 
                                                    ($log['query_type'] == 'UPDATE' ? 'warning' : 
                                                    ($log['query_type'] == 'DELETE' ? 'danger' : 'secondary'))) 
                                                ?>"><?= $log['query_type'] ?></span></td>
                                                <td><?= $log['affected_rows'] ?? 'N/A' ?></td>
                                                <td><?= $log['query_time'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No query history yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>