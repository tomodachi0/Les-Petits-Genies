<?php
session_start();
require_once '../includes/db_connect.php';

// Authentication check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Initialize variables
$error = '';
$success = '';
$job = [
    'id' => '',
    'job_name' => '',
    'image_path' => '',
    'audio_path' => ''
];

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connect to database
$pdo = getDbConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Get current file path for deletion
        $stmt = $pdo->prepare("SELECT image_path FROM jobs WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $job = $stmt->fetch();
        
        if ($job) {
            // Delete image file from server
            if (!empty($job['image_path']) && file_exists("../" . $job['image_path'])) {
                unlink("../" . $job['image_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Job deleted successfully";
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Error deleting job";
    }
}

// Handle edit action - load job data
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $job = $row;
        } else {
            $error = "Job not found";
        }
    } catch (PDOException $e) {
        error_log("Edit load error: " . $e->getMessage());
        $error = "Error loading job data";
    }
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        // Get form data
        $id = isset($_POST['id']) ? filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) : null;
        $job_name = trim(filter_input(INPUT_POST, 'job_name', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($job_name)) {
            $error = "Job name is required";
        } elseif (strlen($job_name) > 100) {
            $error = "Job name must be less than 100 characters";
        } else {
            try {
                // Get file paths from form
                $image_path = trim(filter_input(INPUT_POST, 'image_path', FILTER_SANITIZE_STRING));
                $audio_path = trim(filter_input(INPUT_POST, 'audio_path', FILTER_SANITIZE_STRING));
                
                // Validate paths
                if (empty($image_path)) {
                    $error = "Image path is required";
                } elseif (!preg_match('/^images\/jobs\/[\w-]+\.(jpg|jpeg|png|gif)$/i', $image_path)) {
                    $error = "Invalid image path format. Must be in format: images/jobs/filename.jpg";
                } elseif (!file_exists("../" . $image_path)) {
                    $error = "Image file does not exist in the specified path";
                }
                
                if (empty($audio_path)) {
                    $error = "Audio path is required";
                } elseif (!preg_match('/^audio\/jobs\/[\w-]+\.(mp3|wav|ogg)$/i', $audio_path)) {
                    $error = "Invalid audio path format. Must be in format: audio/jobs/filename.mp3";
                } elseif (!file_exists("../" . $audio_path)) {
                    $error = "Audio file does not exist in the specified path";
                }
                
                // If no errors, save to database
                if (empty($error)) {
                    if ($id) {
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE jobs SET job_name = :job_name, 
                                             image_path = :image_path, audio_path = :audio_path
                                             WHERE id = :id");
                        $stmt->bindParam(':job_name', $job_name);
                        $stmt->bindParam(':image_path', $image_path);
                        $stmt->bindParam(':audio_path', $audio_path);
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        $success = "Job updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO jobs (job_name, image_path, audio_path)
                                             VALUES (:job_name, :image_path, :audio_path)");
                        $stmt->bindParam(':job_name', $job_name);
                        $stmt->bindParam(':image_path', $image_path);
                        $stmt->bindParam(':audio_path', $audio_path);
                        $stmt->execute();
                        
                        $success = "Job added successfully";
                    }
                    
                    // Reset form after successful submission
                    $job = [
                        'id' => '',
                        'title' => '',
                        'category' => '',
                        'description' => '',
                        'responsibilities' => '',
                        'work_environment' => '',
                        'education_required' => '',
                        'skills_required' => '',
                        'salary_range' => '',
                        'image_path' => ''
                    ];
                }
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
                $error = "Error saving job data";
            }
        }
    }
}

// Get all jobs for listing
try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Count total records
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    $totalJobs = $stmt->fetchColumn();
    $totalPages = ceil($totalJobs / $perPage);
    
    // Get paginated records
    $stmt = $pdo->prepare("SELECT * FROM jobs ORDER BY job_name LIMIT :offset, :perPage");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $jobs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Listing error: " . $e->getMessage());
    $error = "Error fetching jobs";
    $jobs = [];
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - Kids Learning Zone</title>
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
                    <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="flags_manage.php"><i class="fas fa-flag"></i> Manage Flags</a></li>
                    <li><a href="animals_manage.php"><i class="fas fa-paw"></i> Manage Animals</a></li>
                    <li class="active"><a href="jobs_manage.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                    <li><a href="stories_manage.php"><i class="fas fa-book"></i> Manage Stories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-briefcase"></i> Manage Jobs</h1>
                <div class="admin-user">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Job' : 'Add New Job'; ?></h2>
                </div>
                
                <div class="panel-body">
                    <form method="post" action="jobs_manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($job['id']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="job_name">Job Name:</label>
                                    <input type="text" id="job_name" name="job_name" maxlength="100"
                                           value="<?php echo htmlspecialchars($job['job_name']); ?>" required>
                                    <small>Enter the name of the job (max 100 characters)</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image_path">Image Path:</label>
                                    <input type="text" id="image_path" name="image_path" 
                                           value="<?php echo htmlspecialchars($job['image_path']); ?>" 
                                           placeholder="images/jobs/example.jpg" <?php echo empty($job['id']) ? 'required' : ''; ?>>
                                    <small>Enter the path to the image file (relative to website root)</small>
                                    <?php if (!empty($job['image_path'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo '../' . htmlspecialchars($job['image_path']); ?>" width="100" height="auto">
                                            <p>Current: <?php echo htmlspecialchars($job['image_path']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="audio_path">Audio Path:</label>
                                    <input type="text" id="audio_path" name="audio_path" 
                                           value="<?php echo htmlspecialchars($job['audio_path']); ?>" 
                                           placeholder="audio/jobs/example.mp3" <?php echo empty($job['id']) ? 'required' : ''; ?>>
                                    <small>Enter the path to the audio file (relative to website root)</small>
                                    <?php if (!empty($job['audio_path'])): ?>
                                        <div class="mt-2">
                                            <audio controls>
                                                <source src="<?php echo '../' . htmlspecialchars($job['audio_path']); ?>" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                            <p>Current: <?php echo htmlspecialchars($job['audio_path']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Update Job' : 'Add Job'; ?>
                            </button>
                            <a href="jobs_manage.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-panel mt-4">
                <div class="panel-header">
                    <h2>All Jobs</h2>
                </div>
                
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Job Name</th>
                                <th>Audio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No jobs found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($jobs as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td>
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($item['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['job_name']); ?>" 
                                                     width="50" height="50">
                                            <?php else: ?>
                                                No image
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['job_name']); ?></td>
                                        <td>
                                            <?php if (!empty($item['audio_path'])): ?>
                                                <audio controls>
                                                    <source src="<?php echo '../' . htmlspecialchars($item['audio_path']); ?>" type="audio/mpeg">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            <?php else: ?>
                                                No audio
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="jobs_manage.php?action=edit&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="jobs_manage.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this job?');">
                                                   <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-sm">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="btn btn-sm <?php echo $i == $page ? 'btn-primary' : ''; ?>">
                                   <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-sm">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 