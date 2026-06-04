<?php
// admin_settings.php
require 'config.php';
requireAdmin();

$active_page = 'settings';
$error = '';
$success = '';

// Fetch current admin information from the database
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_info = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($current_password)) {
        $error = 'Name, Email, and Current Password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Check if the email is taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = 'Email is already in use by another user.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $db_pass = $stmt->fetch()['password'];

            if (!password_verify($current_password, $db_pass)) {
                $error = 'Incorrect current password.';
            } else {
                // If user wants to update password
                if (!empty($new_password)) {
                    if ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match.';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } else {
                        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $hashed, $_SESSION['user_id']]);
                        
                        $_SESSION['name'] = $name; // Update active session name
                        logAdminAction($pdo, "Updated admin credentials & password", $_SESSION['name']);
                        $success = 'Credentials and password updated successfully!';
                    }
                } else {
                    // Update name and email only
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $_SESSION['user_id']]);
                    
                    $_SESSION['name'] = $name; // Update active session name
                    logAdminAction($pdo, "Updated admin credentials (name & email)", $_SESSION['name']);
                    $success = 'Credentials updated successfully!';
                }
                
                // Refresh admin info in form
                $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $admin_info = $stmt->fetch();
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
    <title>Admin Settings - Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-nav {
            background: linear-gradient(90deg, #1f2937, #111827) !important;
            border-bottom: 2px solid var(--danger);
        }
        .admin-nav .nav-brand, .admin-nav .nav-links a {
            color: white;
        }
        .settings-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 2.5rem;
            max-width: 800px;
            margin: 1.5rem auto;
        }
        .section-divider {
            height: 1px;
            background: var(--border-color);
            margin: 2rem 0;
        }
    </style>
</head>
<body class="dashboard-layout">
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="dash-main">
        <?php include 'admin_header.php'; ?>

        <div class="dash-content">
            <div id="settings" class="tab-pane active">
                <h2 class="dash-title">System Settings</h2>
                
                <div class="settings-card">
                    <h3 style="font-size:1.4rem; font-weight:700; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-main);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--danger);"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        Admin Profile Settings
                    </h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" style="margin-bottom: 2rem;">
                            <span>✅</span> <?= $success ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-error" style="margin-bottom: 2rem;">
                            <span>⚠️</span> <?= $error ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="admin_settings.php" method="POST" class="modern-form">
                        <!-- Profile Details -->
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>Administrator Name</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($admin_info['name']) ?>" placeholder="e.g. System Admin">
                            </div>
                            <div class="form-group">
                                <label>Admin Email ID (Username)</label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($admin_info['email']) ?>" placeholder="admin@college.edu">
                            </div>
                        </div>
                        
                        <div class="section-divider"></div>
                        
                        <!-- Change Password Section -->
                        <h4 style="font-size:1.1rem; font-weight:600; margin-bottom:1rem; color:var(--text-main);">Update Password (Optional)</h4>
                        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem;">Leave new password fields blank if you only want to change your Name or Email.</p>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Min. 6 characters">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" placeholder="Confirm new password">
                            </div>
                        </div>
                        
                        <div class="section-divider"></div>
                        
                        <!-- Confirm Identity Section -->
                        <div class="form-group" style="max-width: 400px;">
                            <label style="color: var(--danger); font-weight: 600;">Confirm Current Password *</label>
                            <input type="password" name="current_password" required placeholder="Enter current admin password to verify">
                        </div>
                        
                        <button type="submit" class="btn btn-danger" style="margin-top:1rem; padding: 0.75rem 2rem;">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // No-op table filter for header search bar consistency on settings page
        function filterDashboardTable() {
            // Settings page doesn't have lists to filter
        }
    </script>
</body>
</html>
