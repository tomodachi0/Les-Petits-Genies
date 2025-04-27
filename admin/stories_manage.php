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
$story = [
    'id' => '',
    'title' => '',
    'author' => '',
    'content' => '',
    'moral' => '',
    'age_group' => '',
    'category' => '',
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
        $stmt = $pdo->prepare("SELECT image_path FROM stories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $story = $stmt->fetch();
        
        if ($story) {
            // Delete image file from server
            if (!empty($story['image_path']) && file_exists("../" . $story['image_path'])) {
                unlink("../" . $story['image_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM stories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Story deleted successfully";
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Error deleting story";
    }
}

// Handle edit action - load story data
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM stories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($row = $stmt->fetch()) {
            $story = $row;
        } else {
            $error = "Story not found";
        }
    } catch (PDOException $e) {
        error_log("Edit load error: " . $e->getMessage());
        $error = "Error loading story data";
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
        $author = trim(filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING));
        $content = trim(filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING));
        $moral = trim(filter_input(INPUT_POST, 'moral', FILTER_SANITIZE_STRING));
        $age_group = trim(filter_input(INPUT_POST, 'age_group', FILTER_SANITIZE_STRING));
        $category = trim(filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING));
        
        // Validate input
        if (empty($title)) {
            $error = "Story title is required";
        } elseif (empty($content)) {
            $error = "Story content is required";
        } elseif (empty($category)) {
            $error = "Story category is required";
        } else {
            try {
                // Handle file upload
                $image_path = isset($story['image_path']) ? $story['image_path'] : '';
                
                if (!empty($_FILES['image']['name'])) {
                    $image_dir = "../images/stories/";
                    if (!is_dir($image_dir)) {
                        mkdir($image_dir, 0755, true);
                    }
                    
                    $image_filename = time() . '_' . basename($_FILES['image']['name']);
                    $image_path = "images/stories/" . $image_filename;
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
                        $stmt = $pdo->prepare("UPDATE stories SET title = :title, author = :author, 
                                             content = :content, moral = :moral, age_group = :age_group, 
                                             category = :category, image_path = :image_path,
                                             updated_at = NOW()
                                             WHERE id = :id");
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
                        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                        $stmt->bindParam(':moral', $moral, PDO::PARAM_STR);
                        $stmt->bindParam(':age_group', $age_group, PDO::PARAM_STR);
                        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Story updated successfully";
                    } else {
                        // Insert new record
                        $stmt = $pdo->prepare("INSERT INTO stories (title, author, content, moral, 
                                             age_group, category, image_path, created_at, updated_at) 
                                             VALUES (:title, :author, :content, :moral, 
                                             :age_group, :category, :image_path, NOW(), NOW())");
                        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                        $stmt->bindParam(':author', $author, PDO::PARAM_STR);
                        $stmt->bindParam(':content', $content, PDO::PARAM_STR);
                        $stmt->bindParam(':moral', $moral, PDO::PARAM_STR);
                        $stmt->bindParam(':age_group', $age_group, PDO::PARAM_STR);
                        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
                        $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $success = "Story added successfully";
                    }
                    
                    // Reset form after successful submission
                    $story = [
                        'id' => '',
                        'title' => '',
                        'author' => '',
                        'content' => '',
                        'moral' => '',
                        'age_group' => '',
                        'category' => '',
                        'image_path' => ''
                    ];
                }
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
                $error = "Error saving story data";
            }
        }
    }
}

// Get all stories for listing
try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
    // Count total records
    $stmt = $pdo->query("SELECT COUNT(*) FROM stories");
    $totalStories = $stmt->fetchColumn();
    $totalPages = ceil($totalStories / $perPage);
    
    // Get paginated records
    $stmt = $pdo->prepare("SELECT * FROM stories ORDER BY title LIMIT :offset, :perPage");
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':perPage', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    
    $stories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Listing error: " . $e->getMessage());
    $error = "Error fetching stories";
    $stories = [];
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stories - Kids Learning Zone</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include TinyMCE for rich text editing -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 300,
            plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        });
    </script>
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
                    <li><a href="jobs_manage.php"><i class="fas fa-briefcase"></i> Manage Jobs</a></li>
                    <li class="active"><a href="stories_manage.php"><i class="fas fa-book"></i> Manage Stories</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><i class="fas fa-book"></i> Manage Stories</h1>
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
                    <h2><?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Edit Story' : 'Add New Story'; ?></h2>
                </div>
                
                <div class="panel-body">
                    <form method="post" action="stories_manage.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($story['id']); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title">Story Title:</label>
                                    <input type="text" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($story['title']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="author">Author:</label>
                                    <input type="text" id="author" name="author" 
                                           value="<?php echo htmlspecialchars($story['author']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category">Category:</label>
                                    <select id="category" name="category" required>
                                        <option value="">-- Select Category --</option>
                                        <option value="Fable" <?php echo ($story['category'] == 'Fable') ? 'selected' : ''; ?>>Fable</option>
                                        <option value="Fairy Tale" <?php echo ($story['category'] == 'Fairy Tale') ? 'selected' : ''; ?>>Fairy Tale</option>
                                        <option value="Folktale" <?php echo ($story['category'] == 'Folktale') ? 'selected' : ''; ?>>Folktale</option>
                                        <option value="Myth" <?php echo ($story['category'] == 'Myth') ? 'selected' : ''; ?>>Myth</option>
                                        <option value="Legend" <?php echo ($story['category'] == 'Legend') ? 'selected' : ''; ?>>Legend</option>
                                        <option value="Adventure" <?php echo ($story['category'] == 'Adventure') ? 'selected' : ''; ?>>Adventure</option>
                                        <option value="Fantasy" <?php echo ($story['category'] == 'Fantasy') ? 'selected' : ''; ?>>Fantasy</option>
                                        <option value="Historical" <?php echo ($story['category'] == 'Historical') ? 'selected' : ''; ?>>Historical</option>
                                        <option value="Educational" <?php echo ($story['category'] == 'Educational') ? 'selected' : ''; ?>>Educational</option>
                                        <option value="Other" <?php echo ($story['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="age_group">Age Group:</label>
                                    <select id="age_group" name="age_group" required>
                                        <option value="">-- Select Age Group --</option>
                                        <option value="3-5 years" <?php echo ($story['age_group'] == '3-5 years') ? 'selected' : ''; ?>>3-5 years</option>
                                        <option value="6-8 years" <?php echo ($story['age_group'] == '6-8 years') ? 'selected' : ''; ?>>6-8 years</option>
                                        <option value="9-12 years" <?php echo ($story['age_group'] == '9-12 years') ? 'selected' : ''; ?>>9-12 years</option>
                                        <option value="All ages" <?php echo ($story['age_group'] == 'All ages') ? 'selected' : ''; ?>>All ages</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="image">Story Image:</label>
                                    <?php if (!empty($story['image_path'])): ?>
                                        <div class="current-file">
                                            <img src="<?php echo '../' . htmlspecialchars($story['image_path']); ?>" width="100" height="auto">
                                            <span>Current: <?php echo htmlspecialchars($story['image_path']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="image" name="image" <?php echo empty($story['id']) ? 'required' : ''; ?>>
                                    <small>Upload JPG, PNG or GIF (max 2MB). Image representing this story.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Story Content:</label>
                            <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($story['content']); ?></textarea>
                            <small>The full text of the story. Use paragraphs to make it easy to read.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="moral">Moral or Lesson (if any):</label>
                            <textarea id="moral" name="moral" rows="3"><?php echo htmlspecialchars($story['moral']); ?></textarea>
                            <small>The moral or lesson of the story, if applicable.</small>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET['action']) && $_GET['action'] == 'edit' ? 'Update Story' : 'Add Story'; ?>
                            </button>
                            <a href="stories_manage.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="admin-panel mt-4">
                <div class="panel-header">
                    <h2>All Stories</h2>
                </div>
                
                <div class="panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Age Group</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stories)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No stories found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stories as $item): ?>
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
                                        <td><?php echo htmlspecialchars($item['author']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td><?php echo htmlspecialchars($item['age_group']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="stories_manage.php?action=edit&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                                <a href="stories_manage.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this story?');">
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