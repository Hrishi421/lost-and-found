<?php
// reset-password.php
require 'config.php';

$error = '';
$success = false;
$token = sanitize($_GET['token'] ?? $_POST['token'] ?? '');
$is_token_valid = false;
$user = null;

if (!empty($token)) {
    // Look up the user by token
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token IS NOT NULL");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Check expiry (expiry should be greater than now)
        $expiry_time = strtotime($user['reset_token_expiry']);
        if ($expiry_time > time()) {
            $is_token_valid = true;
        } else {
            $error = 'The password recovery link has expired. Password recovery tokens are only valid for 1 hour.';
        }
    } else {
        $error = 'Invalid password recovery token.';
    }
} else {
    $error = 'No password recovery token was provided.';
}

// Handle Form Submission
if ($is_token_valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill out both password fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the new password securely
        $new_hash = password_hash($password, PASSWORD_BCRYPT);

        // Update the user password and invalidate the token
        $update_stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        $update_stmt->execute([$new_hash, $user['id']]);

        // Redirect to login page with success status
        header("Location: login.php?reset=success");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-left">
        <div class="auth-graphic-text">
            <h2>Security Guard</h2>
            <p style="color:var(--text-muted); font-size:1.1rem;">Securely update and save your new credentials.</p>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-container">
            <h2 class="auth-title">Reset Password</h2>
            
            <?php if (!$is_token_valid): ?>
                <p class="auth-subtitle">We couldn't verify your password reset request.</p>
                
                <div class="alert alert-error" style="margin-bottom: 2rem;">
                    <span>⚠️</span> <?= htmlspecialchars($error) ?>
                </div>

                <div class="auth-footer" style="text-align: left; margin-top: 1rem;">
                    <a href="forgot-password.php" class="btn btn-primary btn-block" style="text-decoration:none; text-align:center; display:block;">Request New Reset Link</a>
                    <br>
                    <a href="login.php" style="color:var(--text-muted); display:inline-block; margin-top: 1rem;">← Return to Login</a>
                </div>
            <?php else: ?>
                <p class="auth-subtitle">Hello <strong><?= htmlspecialchars($user['name']) ?></strong>, please choose a strong new password below.</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span>⚠️</span> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="reset-password.php" method="POST">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
                    <div class="form-floating" style="margin-bottom: 1.5rem;">
                        <input type="password" id="password" name="password" required placeholder="New Password">
                        <label for="password">New Password</label>
                        <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                    </div>

                    <div class="form-floating" style="margin-bottom: 1.5rem;">
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm Password">
                        <label for="confirm_password">Confirm New Password</label>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">👁️</span>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Update Password</button>
                </form>

                <div class="auth-footer">
                    Remembered your password? <a href="login.php" style="font-weight:600;">Log in</a>
                </div>
            <?php endif; ?>
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
