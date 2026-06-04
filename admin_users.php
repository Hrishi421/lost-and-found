<?php
// admin_users.php
require 'config.php';
requireAdmin();

// Fetch Users
$stmt = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.created_at, COUNT(i.id) as post_count 
    FROM users u 
    LEFT JOIN items i ON u.id = i.user_id 
    WHERE u.role = 'student' 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

$active_page = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
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
            <!-- USERS TAB -->
            <div id="users" class="tab-pane active">
                <h2 class="dash-title">User Management</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Join Date</th>
                                <th>Posts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($users)): ?>
                            <tr class="no-results"><td colspan="5" style="text-align:center;">No students registered yet.</td></tr>
                            <?php else: ?>
                                <?php foreach($users as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    <td><?= $u['post_count'] ?></td>
                                    <td>
                                        <button class="btn btn-danger" onclick="adminUserAction('admin_delete_user', <?= $u['id'] ?>, 'Delete this user AND all their posts?')">Delete User</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function filterDashboardTable() {
            const query = document.getElementById('adminSearchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr:not(.no-results)');
            let matches = 0;
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    matches++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Handle empty state if query yields no results
            const noResRow = document.querySelector('.no-results');
            if (rows.length > 0) {
                if (matches === 0) {
                    if (!noResRow) {
                        const tbody = document.querySelector('tbody');
                        const tr = document.createElement('tr');
                        tr.className = 'no-results';
                        tr.innerHTML = `<td colspan="5" style="text-align:center; color: var(--text-muted);">No matching users found.</td>`;
                        tbody.appendChild(tr);
                    } else {
                        noResRow.style.display = '';
                        noResRow.querySelector('td').textContent = "No matching users found.";
                    }
                } else if (noResRow) {
                    noResRow.style.display = 'none';
                }
            }
        }

        function adminUserAction(actionName, userId, confirmMsg = null) {
            if(confirmMsg && !confirm(confirmMsg)) return;
            
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=${actionName}&user_id=${userId}`
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert(data.message);
            });
        }
    </script>
</body>
</html>
