<?php
// forgot-password.php
require 'config.php';

// Self-healing database migration for password reset token columns
try {
    $pdo->query("SELECT reset_token, reset_token_expiry FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expiry DATETIME DEFAULT NULL");
    } catch (PDOException $ex) {
        // Silently capture if somehow already modified or table issues
    }
}

$error = '';
$success = false;
$email = '';
$token = '';
$reset_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your campus email address.';
    } else {
        // Query the user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate a secure 64-character token
            $token = bin2hex(random_bytes(32));
            // Expiry set to 1 hour from now
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store in database
            $update_stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
            $update_stmt->execute([$token, $expiry, $user['id']]);

            // Construct absolute reset URL
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $uri = $_SERVER['REQUEST_URI'];
            $dir = dirname($uri);
            $dir = ($dir === '\\' || $dir === '/') ? '' : $dir;
            $reset_url = $protocol . $host . $dir . '/reset-password.php?token=' . $token;

            $success = true;
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .simulator-card {
            animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-left">
        <div class="auth-graphic-text">
            <h2>Recover Password</h2>
            <p style="color:var(--text-muted); font-size:1.1rem;">Get back into your account securely.</p>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-container">
            <h2 class="auth-title">Forgot Password</h2>
            <p class="auth-subtitle">Enter your email and we'll simulate sending you a secure reset link.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399;">
                    <span>✓</span> Success! Recovery token generated. See the simulated email box below.
                </div>

                <div class="simulator-card" style="background: rgba(94, 106, 210, 0.08); border: 1px dashed var(--primary-color); border-radius: var(--radius-sm); padding: 1.5rem; margin-top: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <span style="font-size: 1.5rem;">✉️</span>
                        <h4 style="margin: 0; color: #fff; font-size: 1.1rem; font-weight: 600;">SMTP Email Simulator (Local Development)</h4>
                    </div>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                        Since your local environment does not have SMTP configured, we generated the cryptographically secure reset token locally. Select the option below to reset your credentials.
                    </p>
                    <div style="background: var(--bg-app); border: 1px solid var(--border-color); border-radius: 6px; padding: 1.25rem; font-size: 0.9rem; text-align: left;">
                        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 0.75rem; color: var(--text-muted); font-size: 0.85rem;">
                            <strong>To:</strong> <span style="color: #fff; font-family: monospace;"><?= htmlspecialchars($email) ?></span><br>
                            <strong>Subject:</strong> <span style="color: #fff;">Reset your College Lost and Found Password</span>
                        </div>
                        <div style="line-height: 1.6; color: var(--text-main); font-size: 0.85rem;">
                            Hello <?= htmlspecialchars($user['name'] ?? 'User') ?>,<br><br>
                            We received a request to reset your password. Click the button below to set a new password. This recovery token is active for 1 hour.
                            <div style="text-align: center; margin: 1.5rem 0;">
                                <a href="<?= htmlspecialchars($reset_url) ?>" class="btn btn-primary" style="display: inline-block; padding: 0.75rem 1.5rem; font-size: 0.9rem; text-decoration: none; border-radius: 4px; font-weight: 600;">Reset My Password</a>
                            </div>
                            Or copy and paste this URL into your browser:<br>
                            <textarea readonly style="width: 100%; background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); color: var(--text-muted); font-family: monospace; font-size: 0.75rem; padding: 0.5rem; border-radius: 4px; resize: none; margin-top: 0.5rem; outline: none; border-radius: 4px;" rows="2"><?= htmlspecialchars($reset_url) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="auth-footer" style="margin-top: 1.5rem;">
                    <a href="login.php" style="color:var(--text-main); font-weight:600; text-decoration:none;">← Return to Login</a>
                </div>
            <?php else: ?>
                <form action="forgot-password.php" method="POST">
                    <div class="form-floating" style="margin-bottom: 1.5rem;">
                        <input type="email" id="email" name="email" required placeholder="name@college.edu" value="<?= htmlspecialchars($email) ?>">
                        <label for="email">Campus Email</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Send Reset Link</button>
                </form>

                <div class="auth-footer">
                    Remember your password? <a href="login.php" style="font-weight:600;">Log in</a>
                    <br><br>
                    <a href="index.php" style="color:var(--text-muted);">← Back to Platform</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
