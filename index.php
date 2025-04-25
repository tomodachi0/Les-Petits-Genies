<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kids Educational Website</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Kids Learning</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="math.php">Math</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="countries.php">Countries</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="animals.php">Animals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stories.php">Stories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="colors.php">Colors & Shapes</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 text-center">
                <h1>Welcome to Kids Learning!</h1>
                <p class="lead">Fun and interactive educational content for children</p>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Math Games</h5>
                        <p class="card-text">Practice addition, subtraction, and multiplication with fun exercises!</p>
                        <a href="math.php" class="btn btn-primary">Play Now</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">World Flags</h5>
                        <p class="card-text">Learn about countries and their flags from around the world!</p>
                        <a href="flags.php" class="btn btn-primary">Explore</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Animals</h5>
                        <p class="card-text">Discover amazing animals and learn their names and sounds!</p>
                        <a href="animals.php" class="btn btn-primary">Meet Animals</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Stories</h5>
                        <p class="card-text">Watch fun and educational stories from YouTube Kids!</p>
                        <a href="stories.php" class="btn btn-primary">Watch Stories</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Jobs</h5>
                        <p class="card-text">Learn about different jobs and professions!</p>
                        <a href="jobs.php" class="btn btn-primary">Explore Jobs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 