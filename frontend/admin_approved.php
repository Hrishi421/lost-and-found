<?php
// admin_approved.php
require '../backend/config.php';
requireAdmin();

// Fetch Approved Items
$stmt = $pdo->query("SELECT i.*, u.name as user_name FROM items i JOIN users u ON i.user_id = u.id WHERE i.status = 'approved' ORDER BY i.created_at DESC");
$approved_items = $stmt->fetchAll();

$active_page = 'approved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Items - Admin Dashboard</title>
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
            <!-- APPROVED ITEMS TAB -->
            <div id="approved" class="tab-pane active">
                <h2 class="dash-title">Approved Items</h2>
                
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
                            <?php if(empty($approved_items)): ?>
                            <tr class="no-results"><td colspan="4" style="text-align:center;">No approved items.</td></tr>
                            <?php else: ?>
                                <?php foreach($approved_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($item['title']) ?></strong>
                                    </td>
                                    <td><span class="badge badge-<?= $item['type'] ?>"><?= ucfirst($item['type']) ?></span></td>
                                    <td><?= htmlspecialchars($item['user_name']) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-success" onclick="adminAction('admin_resolve_item', <?= $item['id'] ?>, 'Force resolve this item?')">Resolve</button>
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
            
            fetch('../backend/ajax_handlers.php', {
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
