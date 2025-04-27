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
$animal = [
    'id' => '',
    'animal_name' => '',
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
        $stmt = $pdo->prepare("SELECT image_path FROM animals WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $animal = $stmt->fetch();
        
        if ($animal) {
            // Delete image file from server
            if (!empty($animal['image_path']) && file_exists("../" . $animal['image_path'])) {
                unlink("../" . $animal['image_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM animals WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Animal deleted successfully";
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Error deleting animal";
    }
}

// Handle edit action - load animal data
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM animals WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $animal = $row;
        } else {
            $error = "Animal not found";
        }
    } catch (PDOException $e) {
        error_log("Edit load error: " . $e->getMessage());
        $error = "Error loading animal data";
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
        $animal_name = trim(filter_input(INPUT_POST, 'animal_name', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($animal_name)) {
            $error = "Animal name is required";
        } elseif (strlen($animal_name) > 100) {
            $error = "Animal name must be less than 100 characters";
        } else {
            try {
                // Get file paths from form
                $image_path = trim(filter_input(INPUT_POST, 'image_path', FILTER_SANITIZE_STRING));
                $audio_path = trim(filter_input(INPUT_POST, 'audio_path', FILTER_SANITIZE_STRING));
                
                // Validate paths
                if (empty($image_path)) {
                    $error = "Image path is required";
                } elseif (!preg_match('/^images\/animals\/[\w-]+\.(jpg|jpeg|png|gif)$/i', $image_path)) {
                    $error = "Invalid image path format. Must be in format: images/animals/filename.jpg";
                } elseif (!file_exists("../" . $image_path)) {
                    $error = "Image file does not exist in the specified path";
                }
                
                if (empty($audio_path)) {
                    $error = "Audio path is required";
                } elseif (!preg_match('/^audio\/animals\/[\w-]+\.(mp3|wav|ogg)$/i', $audio_path)) {
                    $error = "Invalid audio path format. Must be in format: audio/animals/filename.mp3";
                } elseif (!file_exists("../" . $audio_path)) {
                    $error = "Audio file does not exist in the specified path";
                }
                
                // If no errors, save to database
                if (empty($error)) {
                    if ($id) {
                        // Update existing record
                        $stmt = $pdo->prepare("UPDATE animals SET animal_name = :animal_name, 
                                             image_path = :image_path, audio_path = :audio_path
                                             WHERE id = :id");
                        $stmt->bindParam(':animal_name', $animal_name);
                        $stmt->bindParam(':image_path', $image_path);
                        $stmt->bindParam(':audio_path', $audio_path);
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        $success = "Animal updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO animals (animal_name, image_path, audio_path)
                                             VALUES (:animal_name, :image_path, :audio_path)");
                        $stmt->bindParam(':animal_name', $animal_name);
                        $stmt->bindParam(':image_path', $image_path);
                        $stmt->bindParam(':audio_path', $audio_path);
                        $stmt->execute();
                        
                        $success = "Animal added successfully";
                    }
                    
                    // Reset form after successful submission
                    $animal = [
                        'id' => '',
                        'animal_name' => '',
                        'image_path' => '',
                        'audio_path' => ''
                    ];
                }
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
                $error = "Error saving animal data";
            }
        }
    }
}

// Get all animals for listing
try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Count total records
    $stmt = $pdo->query("SELECT COUNT(*) FROM animals");
    $totalAnimals = $stmt->fetchColumn();
    $totalPages = ceil($totalAnimals / $perPage);
    
    // Get paginated records
    $stmt = $pdo->prepare("SELECT * FROM animals ORDER BY animal_name LIMIT :offset, :perPage");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $animals = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Listing error: " . $e->getMessage());
    $error = "Error fetching animals";
    $animals = [];
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Animals - Kids Learning Zone</title>
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
                    <li class="active"><a href="animals_manage.php"><i class="fas fa-paw"></i> Manage Animals</a></li>
                    <li><a href="jobs_manage.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                    <li><a href="stories_manage.php"><i class="fas fa-book"></i> Manage Stories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-paw"></i> Manage Animals</h1>
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
                    <h2><?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Animal' : 'Add New Animal'; ?></h2>
                </div>
                
                <div class="panel-body">
                    <form method="post" action="animals_manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($animal['id']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="animal_name">Animal Name:</label>
                                    <input type="text" id="animal_name" name="animal_name" maxlength="100"
                                           value="<?php echo htmlspecialchars($animal['animal_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image_path">Image Path:</label>
                                    <input type="text" id="image_path" name="image_path" 
                                           value="<?php echo htmlspecialchars($animal['image_path']); ?>" 
                                           placeholder="images/animals/example.jpg" <?php echo empty($animal['id']) ? 'required' : ''; ?>>
                                    <small>Enter the path to the image file (relative to website root)</small>
                                    <?php if (!empty($animal['image_path'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo '../' . htmlspecialchars($animal['image_path']); ?>" width="100" height="auto">
                                            <p>Current: <?php echo htmlspecialchars($animal['image_path']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="audio_path">Audio Path:</label>
                                    <input type="text" id="audio_path" name="audio_path" 
                                           value="<?php echo htmlspecialchars($animal['audio_path']); ?>" 
                                           placeholder="audio/animals/example.mp3" <?php echo empty($animal['id']) ? 'required' : ''; ?>>
                                    <small>Enter the path to the audio file (relative to website root)</small>
                                    <?php if (!empty($animal['audio_path'])): ?>
                                        <div class="mt-2">
                                            <audio controls>
                                                <source src="<?php echo '../' . htmlspecialchars($animal['audio_path']); ?>" type="audio/mpeg">
                                                Your browser does not support the audio element.
                                            </audio>
                                            <p>Current: <?php echo htmlspecialchars($animal['audio_path']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Update Animal' : 'Add Animal'; ?>
                            </button>
                            <a href="animals_manage.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-panel mt-4">
                <div class="panel-header">
                    <h2>All Animals</h2>
                </div>
                
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Audio</th>
                                <th>Animal Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($animals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No animals found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($animals as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td>
                                            <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($item['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['animal_name']); ?>" 
                                                     width="50" height="50">
                                            <?php else: ?>
                                                No image
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($item['audio_path'])): ?>
                                                <audio controls style="width: 150px;">
                                                    <source src="<?php echo '../' . htmlspecialchars($item['audio_path']); ?>" type="audio/mpeg">
                                                    Your browser does not support the audio element.
                                                </audio>
                                            <?php else: ?>
                                                No audio
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['animal_name']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="animals_manage.php?action=edit&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="animals_manage.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this animal?');">
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