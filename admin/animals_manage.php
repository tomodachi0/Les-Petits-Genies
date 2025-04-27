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
$animal = [
    'id' => '',
    'name' => '',
    'species' => '',
    'habitat' => '',
    'diet' => '',
    'lifespan' => '',
    'conservation_status' => '',
    'description' => '',
    'fun_fact' => '',
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
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $species = trim(filter_input(INPUT_POST, 'species', FILTER_SANITIZE_STRING));
        $habitat = trim(filter_input(INPUT_POST, 'habitat', FILTER_SANITIZE_STRING));
        $diet = trim(filter_input(INPUT_POST, 'diet', FILTER_SANITIZE_STRING));
        $lifespan = trim(filter_input(INPUT_POST, 'lifespan', FILTER_SANITIZE_STRING));
        $conservation_status = trim(filter_input(INPUT_POST, 'conservation_status', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $fun_fact = trim(filter_input(INPUT_POST, 'fun_fact', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($name)) {
            $error = "Animal name is required";
        } elseif (empty($species)) {
            $error = "Species is required";
        } elseif (empty($description)) {
            $error = "Description is required";
        } else {
            try {
                // Handle file upload
                $image_path = isset($animal['image_path']) ? $animal['image_path'] : '';
                
                if (!empty($_FILES['image']['name'])) {
                    $image_dir = "../images/animals/";
                    if (!is_dir($image_dir)) {
                        mkdir($image_dir, 0755, true);
                    }
                    
                    $image_filename = time() . '_' . basename($_FILES['image']['name']);
                    $image_path = "images/animals/" . $image_filename;
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
                        $stmt = $pdo->prepare("UPDATE animals SET name = :name, species = :species, 
                                             habitat = :habitat, diet = :diet, lifespan = :lifespan,
                                             conservation_status = :conservation_status, 
                                             description = :description, fun_fact = :fun_fact,
                                             image_path = :image_path, updated_at = NOW()
                                             WHERE id = :id");
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->bindParam(':species', $species, PDO::PARAM_STR);
                        $stmt->bindParam(':habitat', $habitat, PDO::PARAM_STR);
                        $stmt->bindParam(':diet', $diet, PDO::PARAM_STR);
                        $stmt->bindParam(':lifespan', $lifespan, PDO::PARAM_STR);
                        $stmt->bindParam(':conservation_status', $conservation_status, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':fun_fact', $fun_fact, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Animal updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO animals (name, species, habitat, diet, lifespan,
                                             conservation_status, description, fun_fact, image_path, 
                                             created_at, updated_at) 
                                             VALUES (:name, :species, :habitat, :diet, :lifespan,
                                             :conservation_status, :description, :fun_fact, :image_path, 
                                             NOW(), NOW())");
                        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                        $stmt->bindParam(':species', $species, PDO::PARAM_STR);
                        $stmt->bindParam(':habitat', $habitat, PDO::PARAM_STR);
                        $stmt->bindParam(':diet', $diet, PDO::PARAM_STR);
                        $stmt->bindParam(':lifespan', $lifespan, PDO::PARAM_STR);
                        $stmt->bindParam(':conservation_status', $conservation_status, PDO::PARAM_STR);
                        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
                        $stmt->bindParam(':fun_fact', $fun_fact, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Animal added successfully";
                    }
                    
                    // Reset form after successful submission
                    $animal = [
                        'id' => '',
                        'name' => '',
                        'species' => '',
                        'habitat' => '',
                        'diet' => '',
                        'lifespan' => '',
                        'conservation_status' => '',
                        'description' => '',
                        'fun_fact' => '',
                        'image_path' => ''
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
    $stmt = $pdo->prepare("SELECT * FROM animals ORDER BY name LIMIT :offset, :perPage");
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
                                    <label for="name">Animal Name:</label>
                                    <input type="text" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($animal['name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="species">Species:</label>
                                    <input type="text" id="species" name="species" 
                                           value="<?php echo htmlspecialchars($animal['species']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="habitat">Habitat:</label>
                                    <input type="text" id="habitat" name="habitat" 
                                           value="<?php echo htmlspecialchars($animal['habitat']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="diet">Diet:</label>
                                    <input type="text" id="diet" name="diet" 
                                           value="<?php echo htmlspecialchars($animal['diet']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lifespan">Lifespan:</label>
                                    <input type="text" id="lifespan" name="lifespan" 
                                           value="<?php echo htmlspecialchars($animal['lifespan']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="conservation_status">Conservation Status:</label>
                                    <select id="conservation_status" name="conservation_status" required>
                                        <option value="">-- Select Status --</option>
                                        <option value="Extinct" <?php echo ($animal['conservation_status'] == 'Extinct') ? 'selected' : ''; ?>>Extinct</option>
                                        <option value="Extinct in the Wild" <?php echo ($animal['conservation_status'] == 'Extinct in the Wild') ? 'selected' : ''; ?>>Extinct in the Wild</option>
                                        <option value="Critically Endangered" <?php echo ($animal['conservation_status'] == 'Critically Endangered') ? 'selected' : ''; ?>>Critically Endangered</option>
                                        <option value="Endangered" <?php echo ($animal['conservation_status'] == 'Endangered') ? 'selected' : ''; ?>>Endangered</option>
                                        <option value="Vulnerable" <?php echo ($animal['conservation_status'] == 'Vulnerable') ? 'selected' : ''; ?>>Vulnerable</option>
                                        <option value="Near Threatened" <?php echo ($animal['conservation_status'] == 'Near Threatened') ? 'selected' : ''; ?>>Near Threatened</option>
                                        <option value="Least Concern" <?php echo ($animal['conservation_status'] == 'Least Concern') ? 'selected' : ''; ?>>Least Concern</option>
                                        <option value="Data Deficient" <?php echo ($animal['conservation_status'] == 'Data Deficient') ? 'selected' : ''; ?>>Data Deficient</option>
                                        <option value="Not Evaluated" <?php echo ($animal['conservation_status'] == 'Not Evaluated') ? 'selected' : ''; ?>>Not Evaluated</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image">Animal Image:</label>
                                    <?php if (!empty($animal['image_path'])): ?>
                                        <div class="current-file">
                                            <img src="<?php echo '../' . htmlspecialchars($animal['image_path']); ?>" width="100" height="auto">
                                            <span>Current: <?php echo htmlspecialchars($animal['image_path']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" <?php echo empty($animal['id']) ? 'required' : ''; ?>>
                                    <small>Upload JPG, PNG or GIF (max 2MB). A clear image of the animal.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($animal['description']); ?></textarea>
                            <small>Provide detailed information about the animal suitable for children's education.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="fun_fact">Fun Fact:</label>
                            <textarea id="fun_fact" name="fun_fact" rows="3"><?php echo htmlspecialchars($animal['fun_fact']); ?></textarea>
                            <small>An interesting fact about this animal that children would find fascinating.</small>
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
                                <th>Name</th>
                                <th>Species</th>
                                <th>Habitat</th>
                                <th>Conservation Status</th>
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
                                                     width="50" height="auto" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['species']); ?></td>
                                        <td><?php echo htmlspecialchars($item['habitat']); ?></td>
                                        <td><?php echo htmlspecialchars($item['conservation_status']); ?></td>
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