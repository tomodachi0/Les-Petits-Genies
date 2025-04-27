<?php

session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Les Petits Génies</title>
    <link rel="stylesheet" href="/educational-website/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo-container">
            <img src="/educational-website/images/LesPetitsGénies(1).png" alt="Les Petits Génies" class="logo">
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
                <li><a href="/educational-website/public/index.php"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="/educational-website/public/math.php"><i class="fas fa-calculator"></i> Mathématiques</a></li>
                <li><a href="/educational-website/public/flags.php"><i class="fas fa-flag"></i> Drapeaux</a></li>
                <li><a href="/educational-website/public/animals.php"><i class="fas fa-paw"></i> Animaux</a></li>
                <li><a href="/educational-website/public/jobs.php"><i class="fas fa-briefcase"></i> Métiers</a></li>
                <li><a href="/educational-website/public/stories.php"><i class="fas fa-book"></i> Histoires</a></li>
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
