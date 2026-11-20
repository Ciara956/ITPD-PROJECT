<?php
session_start();
include 'connection.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
$success = isset($_GET['msg']) ? $_GET['msg'] : ""; // To show success message from register page

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
   
    // Fetch user by username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
   
    // Verify password using PHP's secure hashing check
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
       
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body.login-page { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; margin: 0; font-family: Arial, sans-serif; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-box h2 { text-align: center; color: #007bff; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-login:hover { background: #0056b3; }
        .error-msg { color: red; text-align: center; margin-bottom: 15px; }
        .success-msg { color: green; text-align: center; margin-bottom: 15px; }
        .register-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .register-link a { color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <h2>HMS Login</h2>
       
        <?php if($error): ?>
            <p class="error-msg"><?php echo $error; ?></p>
        <?php endif; ?>
       
        <?php if($success): ?>
            <p class="success-msg"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
       
        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Enter username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
       
        <!-- NEW: Link to Registration Page -->
        <div class="register-link">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </div>
</body>
</html>