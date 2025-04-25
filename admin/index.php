<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle content deletion
if (isset($_POST['delete']) && isset($_POST['id']) && isset($_POST['section'])) {
    $id = (int)$_POST['id'];
    $section = $_POST['section'];
    
    switch($section) {
        case 'math':
            $sql = "DELETE FROM math_exercises WHERE id = ?";
            break;
        case 'countries':
            $sql = "DELETE FROM countries WHERE id = ?";
            break;
        case 'animals':
            $sql = "DELETE FROM animals WHERE id = ?";
            break;
        case 'stories':
            $sql = "DELETE FROM stories WHERE id = ?";
            break;
        case 'jobs':
            $sql = "DELETE FROM jobs WHERE id = ?";
            break;
    }
    
    if (isset($sql)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }
}

// Fetch content from database based on section
function fetchContent($conn, $section) {
    switch($section) {
        case 'math':
            return $conn->query("SELECT * FROM math_exercises ORDER BY created_at DESC");
        case 'countries':
            return $conn->query("SELECT * FROM countries ORDER BY name");
        case 'animals':
            return $conn->query("SELECT * FROM animals ORDER BY animal_name");
        case 'stories':
            return $conn->query("SELECT * FROM stories ORDER BY title");
        case 'jobs':
            return $conn->query("SELECT * FROM jobs ORDER BY job_name");
        default:
            return null;
    }
}

$section = isset($_GET['section']) ? $_GET['section'] : 'math';
$content = fetchContent($conn, $section);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .nav {
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .nav a {
            display: inline-block;
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            border-radius: 3px;
        }
        .nav a:hover {
            background-color: #f0f0f0;
        }
        .nav a.active {
            background-color: #007bff;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #c82333;
        }
        .add-form {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="nav">
            <a href="?section=math" <?php echo $section === 'math' ? 'class="active"' : ''; ?>>Math Exercises</a>
            <a href="?section=countries" <?php echo $section === 'countries' ? 'class="active"' : ''; ?>>Countries</a>
            <a href="?section=animals" <?php echo $section === 'animals' ? 'class="active"' : ''; ?>>Animals</a>
            <a href="?section=stories" <?php echo $section === 'stories' ? 'class="active"' : ''; ?>>Stories</a>
            <a href="?section=jobs" <?php echo $section === 'jobs' ? 'class="active"' : ''; ?>>Jobs</a>
            <a href="logout.php" style="float: right;">Logout</a>
        </div>
        
        <?php include "sections/$section.php"; ?>
    </div>
</body>
</html> 