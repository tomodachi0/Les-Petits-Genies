<?php
// Start session on every page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Learning Zone</title>
    <link rel="stylesheet" href="/educational-website/css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">
            <h1><i class="fas fa-graduation-cap"></i> Kids Learning Zone</h1>
        </div>
        <div class="admin-login-header" style="position:absolute;top:18px;right:30px;z-index:1000;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="/educational-website/admin/admin_login.php" class="admin-login-link" style="font-size: 0.98em; color: #666; background: #f3f3f3; padding: 5px 14px; border-radius: 18px; text-decoration: none; border: 1px solid #ccc; transition: background 0.2s;">
                    <i class="fas fa-lock"></i> Admin Login
                </a>
            <?php endif; ?>
        </div>
        <nav>
            <ul class="main-nav">
                <li><a href="/educational-website/public/index.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="/educational-website/public/math.php"><i class="fas fa-calculator"></i> Math Fun</a></li>
                <li><a href="/educational-website/public/flags.php"><i class="fas fa-flag"></i> World Flags</a></li>
                <li><a href="/educational-website/public/animals.php"><i class="fas fa-paw"></i> Animal Kingdom</a></li>
                <li><a href="/educational-website/public/jobs.php"><i class="fas fa-briefcase"></i> Jobs</a></li>
                <li><a href="/educational-website/public/stories.php"><i class="fas fa-book"></i> Stories</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="/educational-website/admin/admin_dashboard.php"><i class="fas fa-cog"></i> Admin</a></li>
                <li><a href="/educational-website/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>
    <main class="container"> 