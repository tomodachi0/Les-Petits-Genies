<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session and clear any existing session data
session_start();


error_log("Initial session state: " . print_r($_SESSION, true));

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    error_log("User already logged in, redirecting to dashboard");
    header("Location: admin_dashboard.php");
    exit();
}

require_once '../includes/db_connect.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
 
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
                
                error_log("Database connection established");
                
                $stmt = $pdo->prepare("SELECT id, username, password FROM admin_users WHERE username = :username LIMIT 1");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                
              
                error_log("Attempting login for username: " . $username);
                
                $stmt->execute();
                
                if ($user = $stmt->fetch()) {
                    error_log("User found in database");
                    error_log("Stored password: " . $user['password']);
                    error_log("Provided password: " . $password);
                    
                    // Verify password
                    if ($password === $user['password']) {
                        error_log("Password match successful");
                      
                        $_SESSION = array();
                        
                        // Set new session variables
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        
                        // Regenerate session ID for security
                        session_regenerate_id(true);
                        
                        error_log("Login successful, session data: " . print_r($_SESSION, true));
                        
                        // Redirect to dashboard
                        header("Location: admin_dashboard.php");
                        exit();
                    } else {
                        $error = "Invalid username or password";
                    }
                } else {
                    $error = "Invalid username or password";
                }
            } catch (PDOException $e) {
                $errorMessage = "Login error: " . $e->getMessage();
                error_log($errorMessage);
                $error = "Database error: " . $e->getMessage();  // Show actual error during development
            }
        }
    }
}


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
