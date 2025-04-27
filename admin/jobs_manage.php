<?php
session_start();
require_once '../includes/db_connect.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Initialize variables
$error = '';
$success = '';
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
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $responsibilities = trim(filter_input(INPUT_POST, 'responsibilities', FILTER_SANITIZE_STRING));
        $work_environment = trim(filter_input(INPUT_POST, 'work_environment', FILTER_SANITIZE_STRING));
        $education_required = trim(filter_input(INPUT_POST, 'education_required', FILTER_SANITIZE_STRING));
        $skills_required = trim(filter_input(INPUT_POST, 'skills_required', FILTER_SANITIZE_STRING));
        $salary_range = trim(filter_input(INPUT_POST, 'salary_range', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($title)) {
            $error = "Job title is required";
        } elseif (empty($category)) {
            $error = "Category is required";
        } elseif (empty($description)) {
            $error = "Description is required";
        } else {
            try {
                // Handle file upload
                $image_path = isset($job['image_path']) ? $job['image_path'] : '';
                
                if (!empty($_FILES['image']['name'])) {
                    $image_dir = "../images/jobs/";
                    if (!is_dir($image_dir)) {
                        mkdir($image_dir, 0755, true);
                    }
                    
                    $image_filename = time() . '_' . basename($_FILES['image']['name']);
                    $image_path = "images/jobs/" . $image_filename;
                    $image_target = $image_dir . $image_filename;
                    
                    // Check file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!in_array($_FILES['image']['type'], $allowed_types)) {
                        $error = "Only JPG, PNG, and GIF images are allowed";
                    } elseif ($_FILES['image']['size'] > 2097152) { // 2MB limit
                        $error = "Image size should be less than 2MB";
                    } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $image_target)) {
                        $error = "Failed to upload image";
                    }
                }
                
                // If no errors, save to database
                if (empty($error)) {
                    if ($id) {
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE jobs SET title = :title, category = :category, 
                                             description = :description, responsibilities = :responsibilities, 
                                             work_environment = :work_environment, education_required = :education_required, 
                                             skills_required = :skills_required, salary_range = :salary_range, 
                                             image_path = :image_path, updated_at = NOW()
                                             WHERE id = :id");
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':responsibilities', $responsibilities, PDO::PARAM_STR);
                        $stmt->bindParam(':work_environment', $work_environment, PDO::PARAM_STR);
                        $stmt->bindParam(':education_required', $education_required, PDO::PARAM_STR);
                        $stmt->bindParam(':skills_required', $skills_required, PDO::PARAM_STR);
                        $stmt->bindParam(':salary_range', $salary_range, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Job updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO jobs (title, category, description, responsibilities, 
                                             work_environment, education_required, skills_required, salary_range, 
                                             image_path, created_at, updated_at) 
                                             VALUES (:title, :category, :description, :responsibilities, 
                                             :work_environment, :education_required, :skills_required, :salary_range, 
                                             :image_path, NOW(), NOW())");
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':responsibilities', $responsibilities, PDO::PARAM_STR);
                        $stmt->bindParam(':work_environment', $work_environment, PDO::PARAM_STR);
                        $stmt->bindParam(':education_required', $education_required, PDO::PARAM_STR);
                        $stmt->bindParam(':skills_required', $skills_required, PDO::PARAM_STR);
                        $stmt->bindParam(':salary_range', $salary_range, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
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
    $stmt = $pdo->prepare("SELECT * FROM jobs ORDER BY title LIMIT :offset, :perPage");
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
                                    <label for="title">Job Title:</label>
                                    <input type="text" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($job['title']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category">Category:</label>
                                    <select id="category" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="Healthcare" <?php echo ($job['category'] == 'Healthcare') ? 'selected' : ''; ?>>Healthcare</option>
                                        <option value="Education" <?php echo ($job['category'] == 'Education') ? 'selected' : ''; ?>>Education</option>
                                        <option value="Science" <?php echo ($job['category'] == 'Science') ? 'selected' : ''; ?>>Science</option>
                                        <option value="Technology" <?php echo ($job['category'] == 'Technology') ? 'selected' : ''; ?>>Technology</option>
                                        <option value="Arts" <?php echo ($job['category'] == 'Arts') ? 'selected' : ''; ?>>Arts</option>
                                        <option value="Environment" <?php echo ($job['category'] == 'Environment') ? 'selected' : ''; ?>>Environment</option>
                                        <option value="Business" <?php echo ($job['category'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                                        <option value="Service" <?php echo ($job['category'] == 'Service') ? 'selected' : ''; ?>>Service</option>
                                        <option value="Trades" <?php echo ($job['category'] == 'Trades') ? 'selected' : ''; ?>>Trades</option>
                                        <option value="Other" <?php echo ($job['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="education_required">Education Required:</label>
                                    <input type="text" id="education_required" name="education_required" 
                                           value="<?php echo htmlspecialchars($job['education_required']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="salary_range">Salary Range:</label>
                                    <input type="text" id="salary_range" name="salary_range" 
                                           value="<?php echo htmlspecialchars($job['salary_range']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image">Job Image:</label>
                                    <?php if (!empty($job['image_path'])): ?>
                                        <div class="current-file">
                                            <img src="<?php echo '../' . htmlspecialchars($job['image_path']); ?>" width="100" height="auto">
                                            <span>Current: <?php echo htmlspecialchars($job['image_path']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" <?php echo empty($job['id']) ? 'required' : ''; ?>>
                                    <small>Upload JPG, PNG or GIF (max 2MB). An image representing this occupation.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="skills_required">Skills Required:</label>
                                    <textarea id="skills_required" name="skills_required" rows="3"><?php echo htmlspecialchars($job['skills_required']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                            <small>A simple explanation about what this job involves, suitable for children to understand.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="responsibilities">Responsibilities:</label>
                            <textarea id="responsibilities" name="responsibilities" rows="4"><?php echo htmlspecialchars($job['responsibilities']); ?></textarea>
                            <small>Key duties and responsibilities of this job written in simple terms.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="work_environment">Work Environment:</label>
                            <textarea id="work_environment" name="work_environment" rows="4"><?php echo htmlspecialchars($job['work_environment']); ?></textarea>
                            <small>Describe where this job typically takes place and what the work setting is like.</small>
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
                                <th>Title</th>
                                <th>Category</th>
                                <th>Education Required</th>
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
                                                     width="50" height="auto" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td><?php echo htmlspecialchars($item['education_required']); ?></td>
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