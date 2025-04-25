<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Learning Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <h1>Kids Learning</h1>
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="math.php">Math Games</a></li>
                <li><a href="flags.php">World Flags</a></li>
                <li><a href="animals.php">Animals</a></li>
                <li><a href="stories.php">Stories</a></li>
                <li><a href="jobs.php">Jobs</a></li>
                <?php if(isset($_SESSION['admin'])): ?>
                    <li><a href="admin/index.php">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main> 