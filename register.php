<?php
// register.php
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
            if ($stmt->execute([$name, $email, $hashed])) {
                $success = 'Registration successful! You can now <a href="login.php">login</a>.';
            } else {
                $error = 'An error occurred during registration.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-left">
        <div class="auth-graphic-text">
            <h2>Join the Platform</h2>
            <p style="color:var(--text-muted); font-size:1.1rem;">Start reporting and claiming items today.</p>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-container">
            <h2 class="auth-title">Create Account</h2>
            <p class="auth-subtitle">Join the College Lost and Found community.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?= $error ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?= $success ?>
                </div>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <div class="form-floating">
                        <input type="text" id="name" name="name" required placeholder="John Doe">
                        <label for="name">Full Name</label>
                    </div>
                    <div class="form-floating">
                        <input type="email" id="email" name="email" required placeholder="name@college.edu">
                        <label for="email">Campus Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" id="password" name="password" required placeholder="Password">
                        <label for="password">Password</label>
                        <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                </form>
            <?php endif; ?>
        
        <div class="auth-footer">
            Already have an account? <a href="login.php" style="font-weight:600;">Log in</a>
            <br><br>
            <a href="index.php" style="color:var(--text-muted);">← Back to Platform</a>
        </div>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }
    </script>
</body>
</html>
