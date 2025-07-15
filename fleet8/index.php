<?php
require_once 'config.php';
session_start();

// Check if user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticateUser($username, $password)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Fuel Management - Login</title>
    <meta name="description" content="Secure login to Fleet Fuel Management System for water utility transport operations">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.8rem;
        }

        .demo-credentials {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }

        .demo-credentials h4 {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .demo-credentials p {
            margin: 0.25rem 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🚗 Fleet Manager</h1>
            <p>Water Utility Transport Operations</p>
        </div>
        
        <div class="demo-credentials">
            <h4>Demo Login Credentials:</h4>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> Admin123#</p>
            <p><em>Use these credentials for initial login</em></p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Sign In</button>
        </form>
        
        <div class="footer">
            <p>Fleet Fuel Management System v2.0</p>
        </div>
    </div>
</body>
</html>