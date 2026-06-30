<?php
session_start();

// Static admin credentials config
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

// Process login request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = ADMIN_USER;
        header("Location: admin.php");
        exit();
    } else {
        $error = 'Invalid admin credentials';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Seat&Screen</title>
    <link href="login_register.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0f1119;
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            margin-top: 10vh;
        }
        .card h2 {
            color: #ff3366;
            margin-bottom: 5px;
        }
        .card h4 {
            color: #a0a0a0;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #a0a0a0;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #ff3366;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="images/logo.png" alt="Seat&Screen Logo" class="logo-img" style="height: 50px;">
            </div>
        </div>
        
        <div class="card">
            <h2>Admin Portal</h2>
            <h4>Seat&Screen Management</h4>
            
            <?php if (!empty($error)): ?>
                <div class="error" style="display: block; background-color: rgba(255, 71, 87, 0.1); border: 1px solid #ff4757; color: #ff4757; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Admin username" style="color: black;" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Admin password" style="color: black;" required>
                </div>

                <button type="submit" class="btn" style="background-color: #ff3366; color: white; border: none; font-weight: bold; cursor: pointer; transition: background 0.3s;">
                    <i class="fas fa-lock"></i> Authorize & Sign In
                </button>
            </form>
            
            <div class="links" style="text-align: center;">
                <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Main Website</a>
            </div>
        </div>
    </div>
</body>
</html>
