<?php
// admin_pending.php
require 'config.php';
requireAdmin();

// Fetch Pending Items
$stmt = $pdo->query("SELECT i.*, u.name as user_name FROM items i JOIN users u ON i.user_id = u.id WHERE i.status = 'pending' ORDER BY i.created_at ASC");
$pending_items = $stmt->fetchAll();

$active_page = 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Review - Admin Dashboard</title>
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
            <!-- PENDING REVIEW TAB -->
            <div id="pending" class="tab-pane active">
                <h2 class="dash-title">Pending Review</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Details</th>
                                <th>Type</th>
                                <th>Reported By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($pending_items)): ?>
                            <tr class="no-results"><td colspan="4" style="text-align:center;">No items pending review.</td></tr>
                            <?php else: ?>
                                <?php foreach($pending_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                                    </td>
                                    <td><span class="badge badge-<?= $item['type'] ?>"><?= ucfirst($item['type']) ?></span></td>
                                    <td><?= htmlspecialchars($item['user_name']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="adminAction('admin_approve_item', <?= $item['id'] ?>)" class="btn btn-outline" style="color:var(--success); border-color:var(--success); font-size:0.8rem; padding:0.3rem 0.6rem;">Approve</button>
                                            <button onclick="adminAction('admin_reject_item', <?= $item['id'] ?>)" class="btn btn-outline" style="color:var(--warning); border-color:var(--warning); font-size:0.8rem; padding:0.3rem 0.6rem;">Reject</button>
                                            <button class="btn btn-danger" onclick="adminAction('admin_delete_item', <?= $item['id'] ?>, 'Delete this item permanently?')">Delete</button>
                                        </div>
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
                        tr.innerHTML = `<td colspan="4" style="text-align:center; color: var(--text-muted);">No matching items found.</td>`;
                        tbody.appendChild(tr);
                    } else {
                        noResRow.style.display = '';
                        noResRow.querySelector('td').textContent = "No matching items found.";
                    }
                } else if (noResRow) {
                    noResRow.style.display = 'none';
                }
            }
        }

        function adminAction(actionName, itemId, confirmMsg = null) {
            if(confirmMsg && !confirm(confirmMsg)) return;
            
            fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=${actionName}&item_id=${itemId}`
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
