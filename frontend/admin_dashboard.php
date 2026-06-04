<?php
// admin_dashboard.php
require '../backend/config.php';
requireAdmin();

// Fetch overall stats
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_items,
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_items,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending_items,
    SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) as resolved_items
    FROM items");
$item_stats = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'student'");
$user_stats = $stmt->fetch();

// Fetch category stats for progress bars
$stmt = $pdo->query("SELECT category, COUNT(*) as count FROM items GROUP BY category");
$cat_stats = $stmt->fetchAll();
$max_cat_count = max(array_column($cat_stats, 'count') ?: [1]);

$active_page = 'stats';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-nav {
            background: linear-gradient(90deg, #1f2937, #111827) !important;
            border-bottom: 2px solid var(--danger);
        }
        .admin-nav .nav-brand, .admin-nav .nav-links a {
            color: white;
        }
    </style>
</head>
<body class="dashboard-layout">
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="dash-main">
        <?php include 'admin_header.php'; ?>

        <div class="dash-content">
            <!-- STATS TAB -->
            <div id="stats" class="tab-pane active">
                <h2 class="dash-title">Platform Statistics</h2>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="analytics-value"><?= $item_stats['total_items'] ?? 0 ?></div>
                        <div class="analytics-label">Total Items Reported</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--warning);"><?= $item_stats['pending_items'] ?? 0 ?></div>
                        <div class="analytics-label">Pending Review</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--primary-color);"><?= $item_stats['approved_items'] ?? 0 ?></div>
                        <div class="analytics-label">Active Approved</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--success);"><?= $item_stats['resolved_items'] ?? 0 ?></div>
                        <div class="analytics-label">Resolved</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-value" style="color:var(--text-main);"><?= $user_stats['total_users'] ?? 0 ?></div>
                        <div class="analytics-label">Registered Users</div>
                    </div>
                </div>

                <h3 style="margin-top:2rem; margin-bottom:1rem;">Category Breakdown</h3>
                <div class="card" style="padding:2rem;">
                    <?php if (empty($cat_stats)): ?>
                        <div style="color:var(--text-muted); text-align:center;">No reports recorded yet.</div>
                    <?php else: ?>
                        <?php foreach($cat_stats as $cat): 
                            $pct = ($cat['count'] / $max_cat_count) * 100;
                        ?>
                            <div class="progress-wrapper">
                                <div class="progress-label">
                                    <span><?= htmlspecialchars($cat['category']) ?></span>
                                    <span><?= $cat['count'] ?> reports</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Optional placeholder search implementation
        function filterDashboardTable() {
            const query = document.getElementById('adminSearchInput').value.toLowerCase();
            const progressWrappers = document.querySelectorAll('.progress-wrapper');
            progressWrappers.forEach(wrap => {
                const label = wrap.querySelector('.progress-label span').textContent.toLowerCase();
                if (label.includes(query)) {
                    wrap.style.display = 'block';
                } else {
                    wrap.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
