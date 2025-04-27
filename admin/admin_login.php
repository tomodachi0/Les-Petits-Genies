<?php
session_start();
require_once '../includes/db_connect.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } else {
            try {
                $pdo = getDbConnection();
                $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                
                if ($user = $stmt->fetch()) {
                    // Verify password
                    if (password_verify($password, $user['password'])) {
                        // Set session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        // Redirect to dashboard
                        header("Location: admin_dashboard.php");
                        exit;
                    } else {
                        $error = "Invalid username or password";
                    }
                } else {
                    $error = "Invalid username or password";
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "An error occurred during login";
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Kids Learning Zone</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-login">
    <div class="login-container">
        <div class="login-form">
            <h1><i class="fas fa-user-shield"></i> Admin Login</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            
            <div class="back-to-site">
                <a href="../public/index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html> 