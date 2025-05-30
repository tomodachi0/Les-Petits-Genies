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
$flag = [
    'id' => '',
    'country_name' => '',
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
        // Get current file paths for deletion
        $stmt = $pdo->prepare("SELECT image_path, audio_path FROM flags WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $flag = $stmt->fetch();
        
        if ($flag) {
            // Delete files from server
            if (file_exists("../" . $flag['image_path'])) {
                unlink("../" . $flag['image_path']);
            }
            
            if (file_exists("../" . $flag['audio_path'])) {
                unlink("../" . $flag['audio_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM flags WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Flag deleted successfully";
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Error deleting flag";
    }
}

// Handle edit action - load flag data
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM flags WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $flag = $row;
        } else {
            $error = "Flag not found";
        }
    } catch (PDOException $e) {
        error_log("Edit load error: " . $e->getMessage());
        $error = "Error loading flag data";
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
        $country_name = trim(filter_input(INPUT_POST, 'country_name', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($country_name)) {
            $error = "Country name is required";
        } else {
            try {
                // Get file paths from form
                $image_path = trim(filter_input(INPUT_POST, 'image_path', FILTER_SANITIZE_STRING));
                $audio_path = trim(filter_input(INPUT_POST, 'audio_path', FILTER_SANITIZE_STRING));
                
                // Validate paths
                if (empty($image_path)) {
                    $error = "Image path is required";
                } elseif (!preg_match('/^images\/flags\/[\w-]+\.(jpg|jpeg|png|gif)$/i', $image_path)) {
                    $error = "Invalid image path format. Must be in format: images/flags/filename.jpg";
                } elseif (!file_exists("../" . $image_path)) {
                    $error = "Image file does not exist in the specified path";
                }
                
                if (empty($audio_path)) {
                    $error = "Audio path is required";
                } elseif (!preg_match('/^audio\/flags\/[\w-]+\.(mp3|wav|ogg)$/i', $audio_path)) {
                    $error = "Invalid audio path format. Must be in format: audio/flags/filename.mp3";
                } elseif (!file_exists("../" . $audio_path)) {
                    $error = "Audio file does not exist in the specified path";
                }
                
                // If no errors, save to database
                if (empty($error)) {
                    if ($id) {
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE flags SET country_name = :country_name, 
                                         image_path = :image_path, audio_path = :audio_path 
                                         WHERE id = :id");
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->bindParam(':audio_path', $audio_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Flag updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO flags (country_name, image_path, audio_path) 
                                         VALUES (:country_name, :image_path, :audio_path)");
                        $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->bindParam(':audio_path', $audio_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Flag added successfully";
                    }
                    
                    // Reset form after successful submission
                    $flag = [
                        'id' => '',
                        'country_name' => '',
                        'image_path' => '',
                        'audio_path' => ''
                    ];
                }
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
                $error = "Error saving flag data";
            }
        }
    }
}

// Get all flags for listing
try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Count total records
    $stmt = $pdo->query("SELECT COUNT(*) FROM flags");
    $totalFlags = $stmt->fetchColumn();
    $totalPages = ceil($totalFlags / $perPage);
    
    // Get paginated records
    $stmt = $pdo->prepare("SELECT * FROM flags ORDER BY country_name LIMIT :offset, :perPage");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $flags = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Listing error: " . $e->getMessage());
    $error = "Error fetching flags";
    $flags = [];
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flags - Kids Learning Zone</title>
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
                    <li class="active"><a href="flags_manage.php"><i class="fas fa-flag"></i> Manage Flags</a></li>
                    <li><a href="animals_manage.php"><i class="fas fa-paw"></i> Manage Animals</a></li>
                    <li><a href="jobs_manage.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                    <li><a href="stories_manage.php"><i class="fas fa-book"></i> Manage Stories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-flag"></i> Manage Flags</h1>
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
                    <h2><?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Flag' : 'Add New Flag'; ?></h2>
                </div>
                
                <div class="panel-body">
                    <form method="post" action="flags_manage.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($flag['id']); ?>">
                        
                        <div class="form-group">
                            <label for="country_name">Country Name:</label>
                            <input type="text" id="country_name" name="country_name" 
                                   value="<?php echo htmlspecialchars($flag['country_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_path">Image Path:</label>
                            <input type="text" id="image_path" name="image_path" 
                                   value="<?php echo htmlspecialchars($flag['image_path']); ?>" 
                                   placeholder="images/flags/example.jpg" <?php echo empty($flag['id']) ? 'required' : ''; ?>>
                            <small>Enter the path to the image file (relative to website root)</small>
                            <?php if (!empty($flag['image_path'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo '../' . htmlspecialchars($flag['image_path']); ?>" width="100" height="auto">
                                    <p>Current: <?php echo htmlspecialchars($flag['image_path']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="audio_path">Audio Path:</label>
                            <input type="text" id="audio_path" name="audio_path" 
                                   value="<?php echo htmlspecialchars($flag['audio_path']); ?>" 
                                   placeholder="audio/flags/example.mp3" <?php echo empty($flag['id']) ? 'required' : ''; ?>>
                            <small>Enter the path to the audio file (relative to website root)</small>
                            <?php if (!empty($flag['audio_path'])): ?>
                                <div class="mt-2">
                                    <audio controls>
                                        <source src="<?php echo '../' . htmlspecialchars($flag['audio_path']); ?>" type="audio/mpeg">
                                        Your browser does not support the audio element.
                                    </audio>
                                    <p>Current: <?php echo htmlspecialchars($flag['audio_path']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Update Flag' : 'Add Flag'; ?>
                            </button>
                            <a href="flags_manage.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-panel mt-4">
                <div class="panel-header">
                    <h2>All Flags</h2>
                </div>
                
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Flag</th>
                                <th>Country</th>
                                <th>Audio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($flags)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No flags found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($flags as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td>
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($item['image_path']); ?>" 
                                                     width="50" height="auto" alt="<?php echo htmlspecialchars($item['country_name']); ?>">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['country_name']); ?></td>
                                        <td>
                                            <?php if (!empty($item['audio_path'])): ?>
                                                <audio controls>
                                                    <source src="<?php echo '../' . htmlspecialchars($item['audio_path']); ?>" type="audio/mpeg">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            <?php else: ?>
                                                <span class="text-muted">No audio</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="flags_manage.php?action=edit&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="flags_manage.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this flag?');">
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
