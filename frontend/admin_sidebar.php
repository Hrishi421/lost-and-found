<?php
// admin_sidebar.php
requireLogin();
requireAdmin();

// Fetch pending items count dynamically for the sidebar badge
if (isset($pdo)) {
    $sidebar_stmt = $pdo->query("SELECT COUNT(*) as pending_items FROM items WHERE status='pending'");
    $sidebar_stats = $sidebar_stmt->fetch();
    $pending_count = $sidebar_stats['pending_items'] ?? 0;
} else {
    $pending_count = 0;
}

$active_page = $active_page ?? 'stats';
?>
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

<!-- Left Sidebar -->
<aside class="dash-sidebar">
    <a href="admin_dashboard.php" class="logo-container-admin" style="margin-bottom: 2rem;">
        <div class="logo-icon-wrapper">
            <svg class="logo-svg-back" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="6" /></svg>
            <svg class="logo-svg-front" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        </div>
        <span class="logo-text">Admin Panel<span class="logo-dot"></span></span>
    </a>
    
    <div class="dash-nav">
        <a href="admin_dashboard.php" class="dash-nav-item <?= $active_page === 'stats' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
            Platform Stats
        </a>
        <a href="admin_pending.php" class="dash-nav-item <?= $active_page === 'pending' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Pending Review
            <?php if ($pending_count > 0): ?>
                <span class="badge badge-pending" style="margin-left:auto;"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="admin_approved.php" class="dash-nav-item <?= $active_page === 'approved' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            Approved Items
        </a>
        <a href="admin_users.php" class="dash-nav-item <?= $active_page === 'users' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Users
        </a>
        <a href="admin_logs.php" class="dash-nav-item <?= $active_page === 'logs' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            Audit Logs
        </a>
        <a href="admin_settings.php" class="dash-nav-item <?= $active_page === 'settings' ? 'active' : '' ?>" style="text-decoration: none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0 .33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Settings
        </a>
    </div>
    
    <div class="dash-user" style="border-top-color:rgba(239, 68, 68, 0.2);">
        <div class="avatar" style="width: 40px; height: 40px; font-size: 1.2rem; background:var(--danger-bg); color:var(--danger); border-color:var(--danger);">
            A
        </div>
        <div style="flex:1; overflow:hidden;">
            <div style="font-weight:600; font-size:0.9rem; white-space:nowrap; text-overflow:ellipsis; overflow:hidden;"><?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?></div>
            <div style="font-size:0.75rem; color:var(--danger);">Administrator</div>
        </div>
        <a href="logout.php" style="color:var(--text-muted); cursor:pointer;" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </a>
    </div>
</aside>
