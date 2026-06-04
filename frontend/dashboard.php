<?php
// dashboard.php
require '../backend/config.php';
requireLogin();

if ($_SESSION['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Ensure uploads directory exists
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission for new item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_item'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $location = sanitize($_POST['location']);
    $date_lost_found = sanitize($_POST['date']);
    $type = sanitize($_POST['type']);
    
    $image_path = null;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] == UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $filename = $_FILES['image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $newname = uniqid() . '.' . $filetype;
                $destination = $upload_dir . $newname;
                if (!is_writable($upload_dir)) {
                    $error_msg = "Upload directory is not writable. Please check permissions.";
                } else if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_path = $destination;
                } else {
                    $error_msg = "Failed to upload image. Directory permission error.";
                }
            } else {
                $error_msg = "Invalid file type. Only JPG, PNG, WEBP allowed.";
            }
        } else {
            switch ($_FILES['image']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_msg = "The uploaded file exceeds the upload_max_filesize directive in php.ini (" . ini_get('upload_max_filesize') . ").";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_msg = "The uploaded file was only partially uploaded.";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_msg = "Missing a temporary folder in PHP configuration.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_msg = "Failed to write file to disk.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_msg = "A PHP extension stopped the file upload.";
                    break;
                default:
                    $error_msg = "Unknown upload error (code: " . $_FILES['image']['error'] . ").";
                    break;
            }
        }
    }
    
    if (empty($error_msg)) {
        $stmt = $pdo->prepare("INSERT INTO items (user_id, title, description, category, location, date_lost_found, type, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $title, $description, $category, $location, $date_lost_found, $type, $image_path])) {
            $success_msg = "Item reported successfully and is pending approval.";
        } else {
            $error_msg = "Database error.";
        }
    }
}

// Handle form submission for password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $is_google_user = (isset($_SESSION['login_method']) && $_SESSION['login_method'] === 'google');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$is_google_user && empty($current_password)) {
        $error_msg = 'Current password is required.';
    } elseif (empty($new_password) || empty($confirm_password)) {
        $error_msg = 'New password fields are required.';
    } elseif (strlen($new_password) < 6) {
        $error_msg = 'New password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error_msg = 'New passwords do not match.';
    } else {
        // Fetch current password hash from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Check if we need to verify current password (only for standard login sessions)
        $verified = false;
        if ($is_google_user) {
            $verified = true; // Google logged-in users bypass current password verification
        } else {
            if ($user && password_verify($current_password, $user['password'])) {
                $verified = true;
            }
        }

        if ($verified) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update_stmt->execute([$hashed_password, $user_id])) {
                if ($is_google_user) {
                    $success_msg = 'Password set successfully! You can now also log in using your campus email and this password.';
                } else {
                    $success_msg = 'Password changed successfully!';
                }
            } else {
                $error_msg = 'Failed to update password in database.';
            }
        } else {
            $error_msg = 'Incorrect current password.';
        }
    }
}

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved FROM items WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

// Fetch current user info
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

// Fetch My Posts
$stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$my_posts = $stmt->fetchAll();

// Fetch Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Count unread notifications
$stmt = $pdo->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_notifs = $stmt->fetch()['unread'] ?? 0;

// Determine active tab from query params
$active_tab = $_GET['tab'] ?? 'overview';
$contact_id = $_GET['contact_id'] ?? null;
$item_id_param = $_GET['item_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <!-- Synchronous Theme Loader to prevent flashes -->
    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'system';
            const root = document.documentElement;
            root.setAttribute('data-theme-preference', saved);
            if (saved === 'system') {
                const dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                root.setAttribute('data-theme', dark ? 'dark' : 'light');
            } else {
                root.setAttribute('data-theme', saved);
            }
        })();
    </script>
    <script src="theme-engine.js" defer></script>
</head>
<body class="dashboard-layout">
    <!-- Left Sidebar -->
    <aside class="dash-sidebar">
        <a href="index.php" class="logo-container" style="margin-bottom: 2rem;">
            <div class="logo-icon-wrapper">
                <svg class="logo-svg-back" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="6" /></svg>
                <svg class="logo-svg-front" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" /><line x1="21" y1="21" x2="16.65" y2="16.65" /><path d="M11 8v6M8 11h6" /></svg>
            </div>
            <span class="logo-text" style="font-size: 1.25rem;">College L&F<span class="logo-dot"></span></span>
        </a>
        
        <div class="dash-nav">
            <div class="dash-nav-item <?= $active_tab === 'overview' ? 'active' : '' ?>" onclick="switchTab('overview')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Dashboard
            </div>
            <div class="dash-nav-item <?= $active_tab === 'report' ? 'active' : '' ?>" onclick="switchTab('report')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Report Item
            </div>
            <div class="dash-nav-item <?= $active_tab === 'posts' ? 'active' : '' ?>" onclick="switchTab('posts')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path></svg>
                My Items
            </div>
            <div class="dash-nav-item <?= $active_tab === 'messages' ? 'active' : '' ?>" onclick="switchTab('messages')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Messages
                <span class="badge badge-found" style="margin-left:auto;">New</span>
            </div>
            <div class="dash-nav-item <?= $active_tab === 'notifications' ? 'active' : '' ?>" onclick="switchTab('notifications')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                Notifications
                <?php if ($unread_notifs > 0): ?>
                    <span class="badge badge-pending" id="notif-badge" style="margin-left:auto;"><?= $unread_notifs ?></span>
                <?php endif; ?>
            </div>
            <div class="dash-nav-item <?= $active_tab === 'security' ? 'active' : '' ?>" onclick="switchTab('security')">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Security
            </div>
        </div>
        
        <div class="dash-user">
            <div class="avatar" style="width: 40px; height: 40px; font-size: 1.2rem;">
                <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
            </div>
            <div style="flex:1; overflow:hidden;">
                <div style="font-weight:600; font-size:0.9rem; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= htmlspecialchars($_SESSION['name']) ?></div>
                <div style="font-size:0.75rem; color:var(--text-muted);">Student Profile</div>
            </div>
            <a href="logout.php" style="color:var(--text-muted); cursor:pointer;" title="Logout">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="dash-main">
        <!-- Top Section -->
        <header class="dash-header">
            <div class="dash-header-search">
                <input type="text" placeholder="Search across dashboard...">
            </div>
            <div class="dash-header-actions" style="display: flex; align-items: center; gap: 0.75rem;">
                <!-- Modern Theme Switcher -->
                <div class="theme-switcher" id="themeSwitcher">
                    <button type="button" class="theme-btn" data-theme-opt="light" title="Light Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    </button>
                    <button type="button" class="theme-btn" data-theme-opt="dark" title="Dark Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <button type="button" class="theme-btn" data-theme-opt="system" title="System Theme">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    </button>
                    <div class="theme-slider"></div>
                </div>
                <a href="index.php" class="btn btn-outline" style="font-size:0.85rem; padding: 0.4rem 1rem;">Back to Platform</a>
            </div>
        </header>

        <div class="dash-content">
            <?php if($success_msg): ?><div class="alert alert-success"><span>✅</span> <?= $success_msg ?></div><?php endif; ?>
            <?php if($error_msg): ?><div class="alert alert-error"><span>⚠️</span> <?= $error_msg ?></div><?php endif; ?>

            <!-- OVERVIEW TAB -->
            <div id="overview" class="tab-pane <?= $active_tab === 'overview' ? 'active' : '' ?>">
                <h2 class="dash-title">Dashboard Overview</h2>
                
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="analytics-value"><?= $stats['total'] ?? 0 ?></div>
                        <div class="analytics-label">Total Reports</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--warning);"><?= $stats['pending'] ?? 0 ?></div>
                        <div class="analytics-label">Pending Claims</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--success);"><?= $stats['resolved'] ?? 0 ?></div>
                        <div class="analytics-label">Items Resolved</div>
                    </div>
                </div>
            </div>

            <!-- REPORT TAB -->
            <div id="report" class="tab-pane <?= $active_tab === 'report' ? 'active' : '' ?>">
                <h2 class="dash-title">Report an Item</h2>
                
                <form action="dashboard.php?tab=report" method="POST" enctype="multipart/form-data" class="modern-form" style="max-width:800px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" required>
                                <option value="lost">I Lost Something</option>
                                <option value="found">I Found Something</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="Electronics">Electronics</option>
                                <option value="Wallets & ID">Wallets & ID</option>
                                <option value="Keys">Keys</option>
                                <option value="Books">Books</option>
                                <option value="Clothing">Clothing</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" required placeholder="e.g. Blue iPhone 13">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" required placeholder="Provide clear details like color, brand, distinct marks..."></textarea>
                    </div>
                    
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" required placeholder="e.g. Library 2nd Floor">
                        </div>
                        <div class="form-group">
                            <label>Date Lost/Found</label>
                            <input type="date" name="date" required max="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Upload Image</label>
                        <input type="file" name="image" id="imageInput" accept="image/*" style="padding:0.75rem;">
                        <div class="image-preview" id="imagePreviewContainer">
                            <span id="previewText">Image Preview</span>
                            <img id="imagePreview" src="">
                        </div>
                    </div>
                    
                    <button type="submit" name="submit_item" class="btn btn-primary" style="margin-top:1rem;">Submit Report</button>
                </form>
            </div>

            <!-- MY POSTS TAB -->
            <div id="posts" class="tab-pane <?= $active_tab === 'posts' ? 'active' : '' ?>">
                <h2 class="dash-title">My Items</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($my_posts)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 3rem; color: var(--text-muted);">No posts yet. Report an item to get started.</td></tr>
                            <?php else: ?>
                                <?php foreach($my_posts as $post): ?>
                                <tr>
                                    <td>
                                        <strong style="font-size: 1.05rem; display: block; margin-bottom: 0.25rem;"><?= htmlspecialchars($post['title']) ?></strong>
                                        <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($post['category']) ?></div>
                                    </td>
                                    <td><span class="badge badge-<?= $post['type'] ?>"><?= ucfirst($post['type']) ?></span></td>
                                    <td style="color: var(--text-muted);"><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                                    <td>
                                        <?php
                                            $status_color = 'primary';
                                            if($post['status'] == 'resolved') $status_color = 'success';
                                            if($post['status'] == 'rejected') $status_color = 'danger';
                                            if($post['status'] == 'pending') $status_color = 'warning';
                                        ?>
                                        <span class="badge" style="background-color: var(--<?= $status_color ?>-light); color: var(--<?= $status_color ?>);"><?= ucfirst($post['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons" style="justify-content: flex-end;">
                                            <?php if($post['status'] !== 'resolved' && $post['status'] !== 'rejected'): ?>
                                                <button class="btn btn-outline" style="border-color: var(--success); color: var(--success);" onclick="resolveItem(<?= $post['id'] ?>)">Resolve</button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);" onclick="deleteItem(<?= $post['id'] ?>)">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MESSAGES TAB -->
            <div id="messages" class="tab-pane <?= $active_tab === 'messages' ? 'active' : '' ?>">
                <h2 class="dash-title">Messages</h2>
                <div class="chat-container">
                    <div class="chat-sidebar">
                        <div class="chat-sidebar-header">Conversations</div>
                        <div class="thread-list" id="threadList">
                            <!-- Threads populated by JS -->
                            <div style="padding:1.5rem; text-align:center; color:var(--text-muted); font-size:0.9rem;">Loading...</div>
                        </div>
                    </div>
                    <div class="chat-main" id="chatMain" style="display:none;">
                        <div class="chat-header">
                            <h3 id="chatHeaderName" style="font-weight:700;">Select a conversation</h3>
                            <span id="chatHeaderItem" style="font-size:0.85rem; color:var(--text-muted);"></span>
                        </div>
                        <div class="chat-messages" id="chatMessages">
                            <!-- Messages populated by JS -->
                        </div>
                        <div class="chat-input">
                            <input type="text" id="messageInput" placeholder="Type a message..." onkeypress="handleKeyPress(event)">
                            <button class="btn btn-primary" onclick="sendMessage()">Send</button>
                        </div>
                    </div>
                    <div class="chat-main" id="chatEmptyState">
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 1.5rem;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No conversation selected</h3>
                            <p style="color: var(--text-muted);">Choose a thread from the left to start coordinating.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- NOTIFICATIONS TAB -->
            <div id="notifications" class="tab-pane <?= $active_tab === 'notifications' ? 'active' : '' ?>">
                <h2 class="dash-title">Notifications</h2>
                <div class="card" style="padding: 2rem;">
                    <?php if(empty($notifications)): ?>
                        <div class="empty-state" style="text-align:center; padding: 2rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 1.5rem;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">All caught up!</h3>
                            <p style="color: var(--text-muted);">You have no notifications at the moment.</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list" style="display:flex; flex-direction:column; gap:1rem;">
                            <?php foreach($notifications as $notif): ?>
                                <div class="notification-item" id="notif-<?= $notif['id'] ?>" style="display:flex; align-items:center; justify-content:space-between; padding:1.25rem; border-radius:var(--radius-md); border:1px solid var(--border-color); background: <?= $notif['is_read'] ? 'transparent' : 'rgba(94, 106, 210, 0.05)' ?>;">
                                    <div style="display:flex; align-items:center; gap:1rem;">
                                        <div class="notif-dot" style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); display: <?= $notif['is_read'] ? 'none' : 'block' ?>;"></div>
                                        <div>
                                            <div style="font-weight: 500; color: var(--text-main);"><?= htmlspecialchars($notif['message']) ?></div>
                                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:0.25rem;"><?= date('M d, Y h:i A T', strtotime($notif['created_at'])) ?></div>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline" style="font-size:0.75rem; padding:0.25rem 0.50rem; border-color:var(--danger); color:var(--danger);" onclick="deleteNotification(<?= $notif['id'] ?>, this)">Delete</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECURITY TAB -->
            <div id="security" class="tab-pane <?= $active_tab === 'security' ? 'active' : '' ?>">
                <h2 class="dash-title">Security Settings</h2>
                <div class="settings-card" style="margin: 1.5rem 0;">
                    <h3 style="font-size:1.4rem; font-weight:700; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.5rem; color:var(--text-main);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--primary-color);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        Change Password
                    </h3>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:2rem;">
                        Update your account password. Active account: <strong><?= htmlspecialchars($user_info['name']) ?></strong> (<?= htmlspecialchars($user_info['email']) ?>).
                    </p>
                    
                    <form action="dashboard.php?tab=security" method="POST" class="modern-form">
                        <?php 
                        $is_google_user = (isset($_SESSION['login_method']) && $_SESSION['login_method'] === 'google');
                        if ($is_google_user): 
                        ?>
                            <!-- Google User Notice -->
                            <div class="alert alert-success" style="background: rgba(94, 106, 210, 0.1); border: 1px solid rgba(94, 106, 210, 0.2); color: #cbd5e1; margin-bottom: 2rem; border-radius: var(--radius-md); padding: 1.25rem; font-size: 0.9rem; line-height: 1.5; display: flex; align-items: flex-start; gap: 0.75rem;">
                                <span style="font-size: 1.2rem; line-height: 1;">🔒</span>
                                <div>
                                    <strong>Google Authenticated:</strong> You logged in via Google. You can set a password below to enable standard email + password login, without needing a current password!
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required placeholder="Enter your current password">
                            </div>
                        <?php endif; ?>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required placeholder="Min. 6 characters">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required placeholder="Confirm your new password">
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary" style="margin-top:1rem; padding: 0.75rem 2rem;">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab Logic
        function switchTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.dash-nav-item').forEach(el => el.classList.remove('active'));
            
            const tabPane = document.getElementById(tabId);
            if (tabPane) {
                tabPane.classList.add('active');
            }
            
            // Bulletproof search for active sidebar button
            document.querySelectorAll('.dash-nav-item').forEach(el => {
                const onclickAttr = el.getAttribute('onclick') || '';
                if (onclickAttr.includes(`'${tabId}'`) || onclickAttr.includes(`"${tabId}"`)) {
                    el.classList.add('active');
                }
            });

            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            if(tabId === 'messages') {
                loadThreads();
            } else if(tabId === 'notifications') {
                markNotificationsAsRead();
            }
        }

        // Image Preview Logic
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewText = document.getElementById('previewText');

        if(imageInput) {
            imageInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                        previewText.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                } else {
                    imagePreview.src = "";
                    imagePreview.style.display = 'none';
                    previewText.style.display = 'block';
                }
            });
        }

        // Post Management AJAX
        function resolveItem(itemId) {
            if(confirm('Are you sure you want to mark this item as resolved?')) {
                fetch('../backend/ajax_handlers.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=resolve_item&item_id=${itemId}`
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') location.reload();
                    else alert(data.message);
                });
            }
        }

        function deleteItem(itemId) {
            if(confirm('Are you sure you want to delete this item permanently?')) {
                fetch('../backend/ajax_handlers.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete_item&item_id=${itemId}`
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') location.reload();
                    else alert(data.message);
                });
            }
        }

        // --- Chat Logic ---
        let currentContactId = <?= $contact_id ? $contact_id : 'null' ?>;
        let currentItemId = <?= $item_id_param ? $item_id_param : 'null' ?>;
        let lastMessageId = 0;
        let chatInterval = null;
        let loggedInUserId = <?= $_SESSION['user_id'] ?>;

        function loadThreads() {
            fetch('../backend/ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=fetch_threads`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    const list = document.getElementById('threadList');
                    list.innerHTML = '';
                    if(data.threads.length === 0 && !currentContactId) {
                        list.innerHTML = '<div style="padding:2rem; text-align:center; color:var(--text-muted); font-weight:500;">No active conversations</div>';
                    } else {
                        let found = false;
                        data.threads.forEach(t => {
                            if(t.id == currentContactId && t.item_id == currentItemId) found = true;
                            renderThread(t);
                        });
                        
                        if(currentContactId && currentItemId && !found) {
                            renderThread({id: currentContactId, name: 'New Conversation', item_id: currentItemId, title: 'Item context'});
                        }
                    }
                }
            });
        }

        function renderThread(t) {
            const list = document.getElementById('threadList');
            const isActive = (t.id == currentContactId && t.item_id == currentItemId);
            
            const div = document.createElement('div');
            div.className = `thread-item ${isActive ? 'active' : ''}`;
            div.onclick = () => openChat(t.id, t.name, t.item_id, t.title);
            div.innerHTML = `
                <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.25rem;">${escapeHtml(t.name)}</h4>
                <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">RE: ${escapeHtml(t.title)}</p>
            `;
            list.appendChild(div);
            
            if(isActive) {
                openChat(t.id, t.name, t.item_id, t.title, false);
            }
        }

        function openChat(contactId, contactName, itemId, itemTitle, reloadThreads = true) {
            currentContactId = contactId;
            currentItemId = itemId;
            lastMessageId = 0;
            
            document.getElementById('chatEmptyState').style.display = 'none';
            document.getElementById('chatMain').style.display = 'flex';
            document.getElementById('chatHeaderName').textContent = contactName;
            document.getElementById('chatHeaderItem').textContent = 'RE: ' + itemTitle;
            document.getElementById('chatMessages').innerHTML = '';
            
            if(reloadThreads) loadThreads(); 
            
            if(chatInterval) clearInterval(chatInterval);
            fetchMessages();
            chatInterval = setInterval(fetchMessages, 2000);
        }

        function fetchMessages() {
            if(!currentContactId || !currentItemId) return;
            fetch('../backend/ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=fetch_messages&contact_id=${currentContactId}&item_id=${currentItemId}&last_id=${lastMessageId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success' && data.messages.length > 0) {
                    const container = document.getElementById('chatMessages');
                    let shouldScroll = container.scrollTop + container.clientHeight === container.scrollHeight;
                    
                    data.messages.forEach(m => {
                        const isSent = m.sender_id == loggedInUserId;
                        const div = document.createElement('div');
                        div.className = `message ${isSent ? 'message-sent' : 'message-received'}`;
                        div.innerHTML = `
                            <div>${escapeHtml(m.message)}</div>
                            <div class="message-meta" style="opacity: 0.7;">${formatTime(m.timestamp)}</div>
                        `;
                        container.appendChild(div);
                        lastMessageId = Math.max(lastMessageId, m.id);
                    });
                    
                    container.scrollTop = container.scrollHeight;
                }
            });
        }

        function sendMessage() {
            const input = document.getElementById('messageInput');
            const msg = input.value.trim();
            if(!msg || !currentContactId || !currentItemId) return;
            
            input.value = '';
            
            fetch('../backend/ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=send_message&receiver_id=${currentContactId}&item_id=${currentItemId}&message=${encodeURIComponent(msg)}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    fetchMessages();
                } else {
                    alert(data.message);
                }
            });
        }

        function handleKeyPress(e) {
            if(e.key === 'Enter') sendMessage();
        }

        function escapeHtml(unsafe) {
            return (unsafe||'').toString()
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }
        
        function formatTime(timestamp) {
            const d = new Date(timestamp);
            return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }

        function markNotificationsAsRead() {
            fetch('../backend/ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_notifications_read'
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const badge = document.getElementById('notif-badge');
                    if (badge) badge.remove();
                    document.querySelectorAll('.notif-dot').forEach(el => el.style.display = 'none');
                    document.querySelectorAll('.notification-item').forEach(el => el.style.background = 'transparent');
                }
            });
        }

        function deleteNotification(notifId, btn) {
            if (confirm('Delete this notification?')) {
                fetch('../backend/ajax_handlers.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=delete_notification&notification_id=${notifId}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const item = document.getElementById('notif-' + notifId);
                        if (item) {
                            item.remove();
                            const list = document.querySelector('.notifications-list');
                            if (list && list.children.length === 0) {
                                location.reload();
                            }
                        }
                    } else {
                        alert(data.message);
                    }
                });
            }
        }

        if('<?= $active_tab ?>' === 'messages') {
            loadThreads();
        } else if('<?= $active_tab ?>' === 'notifications') {
            markNotificationsAsRead();
        }
    </script>
</body>
</html>
