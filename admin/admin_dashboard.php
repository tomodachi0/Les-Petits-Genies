<?php
session_start();
require_once '../includes/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Get statistics from database
try {
    $pdo = getDbConnection();
    
    // Count records in each table
    $tables = ['flags', 'animals', 'jobs', 'stories'];
    $counts = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $counts[$table] = $stmt->fetchColumn();
    }
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "An error occurred while retrieving statistics";
}

// Include custom admin header
$pageTitle = "Admin Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kids Learning Zone</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-page">
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-logo">
                <h2><i class="fas fa-graduation-cap"></i> Admin Panel</h2>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li class="active"><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="flags_manage.php"><i class="fas fa-flag"></i> Manage Flags</a></li>
                    <li><a href="animals_manage.php"><i class="fas fa-paw"></i> Manage Animals</a></li>
                    <li><a href="jobs_manage.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                    <li><a href="stories_manage.php"><i class="fas fa-book"></i> Manage Stories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-flag"></i></div>
                    <div class="stat-info">
                        <h3>Flags</h3>
                        <p class="stat-number"><?php echo isset($counts['flags']) ? $counts['flags'] : 0; ?></p>
                    </div>
                    <a href="flags_manage.php" class="stat-link">Manage</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-paw"></i></div>
                    <div class="stat-info">
                        <h3>Animals</h3>
                        <p class="stat-number"><?php echo isset($counts['animals']) ? $counts['animals'] : 0; ?></p>
                    </div>
                    <a href="animals_manage.php" class="stat-link">Manage</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="stat-info">
                        <h3>Jobs</h3>
                        <p class="stat-number"><?php echo isset($counts['jobs']) ? $counts['jobs'] : 0; ?></p>
                    </div>
                    <a href="jobs_manage.php" class="stat-link">Manage</a>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-info">
                        <h3>Stories</h3>
                        <p class="stat-number"><?php echo isset($counts['stories']) ? $counts['stories'] : 0; ?></p>
                    </div>
                    <a href="stories_manage.php" class="stat-link">Manage</a>
                </div>
            </div>
            
            <div class="dashboard-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="flags_manage.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Flag</a>
                    <a href="animals_manage.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Animal</a>
                    <a href="jobs_manage.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Job</a>
                    <a href="stories_manage.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Story</a>
                </div>
            </div>
            
            <div class="dashboard-footer">
                <a href="../public/index.php" class="btn btn-secondary"><i class="fas fa-globe"></i> View Website</a>
            </div>
        </div>
    </div>
</body>
</html> 