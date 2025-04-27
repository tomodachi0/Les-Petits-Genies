<?php
ob_start();
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
$story = [
    'id' => '',
    'title' => '',
    'description' => '',
    'youtube_link' => '',
    'thumbnail_path' => ''
];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connect to database
$pdo = getDbConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $pdo->prepare("SELECT thumbnail_path FROM stories WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $story = $stmt->fetch();
        
        if ($story) {
            if (!empty($story['thumbnail_path']) && file_exists("../" . $story['thumbnail_path'])) {
                unlink("../" . $story['thumbnail_path']);
            }
            
            // Delete from database
            $stmt = $pdo->prepare("DELETE FROM stories WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                $success = "Story deleted successfully";
                header("Location: stories_manage.php"); 
                exit;
            } else {
                $error = "Failed to delete story";
            }
        }
    } catch (PDOException $e) {
        error_log("Delete error: " . $e->getMessage());
        $error = "Error deleting story";
    }
}

// Handle edit action 
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
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        $id = isset($_POST['id']) ? filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) : null;
        $title = trim(filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
        $youtube_link = trim(filter_input(INPUT_POST, 'youtube_link', FILTER_SANITIZE_URL));
       
        if (empty($title)) {
            $error = "Story title is required";
        } elseif (strlen($title) > 255) {
            $error = "Title must be less than 255 characters";
        } elseif (empty($description)) {
            $error = "Story description is required";
        } elseif (empty($youtube_link)) {
            $error = "YouTube link is required";
        } elseif (!filter_var($youtube_link, FILTER_VALIDATE_URL)) {
            $error = "Invalid YouTube link format";
        } else {
            try {
              
                $thumbnail_path = trim(filter_input(INPUT_POST, 'thumbnail_path', FILTER_SANITIZE_STRING));
                
                if (empty($thumbnail_path)) {
                    $error = "Thumbnail path is required";
                } elseif (!preg_match('/^images\/stories\/[\w-]+\.(jpg|jpeg|png|gif)$/i', $thumbnail_path)) {
                    $error = "Invalid thumbnail path format. Must be in format: images/stories/filename.jpg";
                } elseif (!file_exists("../" . $thumbnail_path)) {
                    $error = "Thumbnail file does not exist in the specified path";
                }
                
                if (empty($error)) {
                    if ($id) {
                        $stmt = $pdo->prepare("UPDATE stories SET title = :title, description = :description, 
                                             youtube_link = :youtube_link, thumbnail_path = :thumbnail_path
                                             WHERE id = :id");
                        $stmt->bindParam(':title', $title);
                        $stmt->bindParam(':description', $description);
                        $stmt->bindParam(':youtube_link', $youtube_link);
                        $stmt->bindParam(':thumbnail_path', $thumbnail_path);
                        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        header("Location: stories_manage.php?updated=1");
                        exit;
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO stories (title, description, youtube_link, thumbnail_path)
                                             VALUES (:title, :description, :youtube_link, :thumbnail_path)");
                        $stmt->bindParam(':title', $title);
                        $stmt->bindParam(':description', $description);
                        $stmt->bindParam(':youtube_link', $youtube_link);
                        $stmt->bindParam(':thumbnail_path', $thumbnail_path);
                        $stmt->execute();
                        header("Location: stories_manage.php?success=1");
                        exit;
                    }
                 
                }
            } catch (PDOException $e) {
                error_log("Save error: " . $e->getMessage());
                $error = "Error saving story data: " . $e->getMessage();
            }
        }
    }
}


try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 10;
    $offset = ($page - 1) * $perPage;
    
  
    $stmt = $pdo->query("SELECT COUNT(*) FROM stories");
    $totalStories = $stmt->fetchColumn();
    $totalPages = ceil($totalStories / $perPage);
    
 
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
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Story added successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">Story updated successfully.</div>
            <?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Story deleted successfully.</div>
            <?php endif; ?>
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
                                    <label for="description">Story Description:</label>
                                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($story['description']); ?></textarea>
                                </div>
                                
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="youtube_link">YouTube Link:</label>
                                    <input type="url" id="youtube_link" name="youtube_link" maxlength="255"
                                           value="<?php echo htmlspecialchars($story['youtube_link']); ?>" required
                                           placeholder="https://www.youtube.com/embed/...">
                                    <small>Enter the embedded YouTube video URL</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="thumbnail_path">Thumbnail Path:</label>
                                    <input type="text" id="thumbnail_path" name="thumbnail_path" 
                                           value="<?php echo htmlspecialchars($story['thumbnail_path']); ?>" 
                                           placeholder="images/stories/example.jpg" <?php echo empty($story['id']) ? 'required' : ''; ?>>
                                    <small>Enter the path to the thumbnail file (relative to website root)</small>
                                    <?php if (!empty($story['thumbnail_path'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo '../' . htmlspecialchars($story['thumbnail_path']); ?>" width="100" height="auto">
                                            <p>Current: <?php echo htmlspecialchars($story['thumbnail_path']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
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
                                <th>Thumbnail</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>YouTube Link</th>
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
                                            <?php if (!empty($item['thumbnail_path'])): ?>
                                                <img src="<?php echo '../' . htmlspecialchars($item['thumbnail_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                                     width="50" height="50">
                                            <?php else: ?>
                                                No thumbnail
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo substr(htmlspecialchars($item['description']), 0, 100) . '...'; ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($item['youtube_link']); ?>" target="_blank">
                                                View Video
                                            </a>
                                        </td>
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
