<?php
include 'connection.php'; 

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];
    $full_name = trim($_POST['full_name']); // New field for doctor's name
    
    // 1. Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->fetch()) {
        $error = "Username already exists. Please choose another.";
    } else {
        // 2. Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            // 3. Insert into users table (For Login)
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role]);
            
            // 4. IF ROLE IS DOCTOR, ALSO INSERT INTO DOCTORS TABLE (For Dropdowns)
            if ($role === 'doctor') {
                $stmtDoc = $pdo->prepare("INSERT INTO doctors (name, specialty, phone) VALUES (?, 'General', 'N/A')");
                $stmtDoc->execute([$full_name]);
            }
            
            header("Location: index.php?msg=Account created successfully! Please login.");
            exit();
        } catch (PDOException $e) {
            $error = "Error creating account: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMS Create Account</title>
    <style>
        body.login-page { display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; margin: 0; font-family: Arial, sans-serif; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-box h2 { text-align: center; color: #28a745; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-register { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-register:hover { background: #218838; }
        .error-msg { color: red; text-align: center; margin-bottom: 15px; }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
        .login-link a { color: #007bff; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <h2>Create Account</h2>
        
        <?php if($error): ?>
            <p class="error-msg"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <!-- NEW: Full Name Field -->
            <div class="form-group">
                <label>Full Name (Required for Doctors)</label>
                <input type="text" name="full_name" required placeholder="e.g., Dr. Jane Doe">
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Choose a password">
            </div>
            
            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="doctor">Doctor</option>
                    <option value="nurse">Nurse</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <button type="submit" class="btn-register">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
</body>
</html>