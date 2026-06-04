<?php
// admin_logs.php
require '../backend/config.php';
requireAdmin();

// Fetch Audit Logs
$stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 50");
$audit_logs = $stmt->fetchAll();

$active_page = 'logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin Dashboard</title>
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
            <!-- LOGS TAB -->
            <div id="logs" class="tab-pane active">
                <h2 class="dash-title">Audit Logs</h2>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Admin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($audit_logs)): ?>
                            <tr class="no-results"><td colspan="3" style="text-align:center;">No audit logs available.</td></tr>
                            <?php else: ?>
                                <?php foreach($audit_logs as $log): ?>
                                <tr>
                                    <td><?= date('Y-m-d H:i:s T', strtotime($log['timestamp'])) ?></td>
                                    <td><strong><?= htmlspecialchars($log['performed_by']) ?></strong></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
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
                        tr.innerHTML = `<td colspan="3" style="text-align:center; color: var(--text-muted);">No matching logs found.</td>`;
                        tbody.appendChild(tr);
                    } else {
                        noResRow.style.display = '';
                        noResRow.querySelector('td').textContent = "No matching logs found.";
                    }
                } else if (noResRow) {
                    noResRow.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
