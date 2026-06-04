<?php
// ajax_handlers.php
require 'config.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if action is set
if (!isset($_POST['action']) && !isset($_GET['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'];
$response = ['status' => 'error', 'message' => 'Unknown action'];



try {
    switch ($action) {
        // ----- MESSAGE HANDLING -----
        case 'send_message':
            requireLogin();
            $receiver_id = $_POST['receiver_id'] ?? null;
            $item_id = $_POST['item_id'] ?? null;
            $message = trim($_POST['message'] ?? '');
            
            if (!$receiver_id || !$item_id || empty($message)) {
                $response = ['status' => 'error', 'message' => 'Missing fields'];
                break;
            }
            
            $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, item_id, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$_SESSION['user_id'], $receiver_id, $item_id, sanitize($message)])) {
                // Add notification
                $notifMsg = "You have a new message regarding an item.";
                $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notifStmt->execute([$receiver_id, $notifMsg]);
                
                $response = ['status' => 'success', 'message' => 'Message sent'];
            }
            break;
            
        case 'fetch_messages':
            requireLogin();
            $contact_id = $_POST['contact_id'] ?? null;
            $item_id = $_POST['item_id'] ?? null;
            $last_id = $_POST['last_id'] ?? 0;
            
            if (!$contact_id || !$item_id) {
                $response = ['status' => 'error', 'message' => 'Missing fields'];
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT m.*, u.name as sender_name 
                FROM chat_messages m
                JOIN users u ON m.sender_id = u.id
                WHERE item_id = ? AND m.id > ? AND 
                ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
                ORDER BY timestamp ASC
            ");
            $stmt->execute([$item_id, $last_id, $_SESSION['user_id'], $contact_id, $contact_id, $_SESSION['user_id']]);
            $messages = $stmt->fetchAll();
            $response = ['status' => 'success', 'messages' => $messages];
            break;
            
        case 'fetch_threads':
            requireLogin();
            // Fetch distinct users the logged-in user has chatted with
            $stmt = $pdo->prepare("
                SELECT DISTINCT u.id, u.name, m.item_id, i.title
                FROM chat_messages m
                JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
                JOIN items i ON i.id = m.item_id
                WHERE (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?
            ");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
            $threads = $stmt->fetchAll();
            $response = ['status' => 'success', 'threads' => $threads];
            break;

        // ----- ITEM MANAGEMENT (STUDENT) -----
        case 'resolve_item':
            requireLogin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                $stmt = $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$item_id, $_SESSION['user_id']])) {
                    $response = ['status' => 'success', 'message' => 'Item marked as resolved'];
                }
            }
            break;
            
        case 'delete_item':
            requireLogin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                // Delete image if exists
                $stmt = $pdo->prepare("SELECT image_path FROM items WHERE id = ? AND user_id = ?");
                $stmt->execute([$item_id, $_SESSION['user_id']]);
                $item = $stmt->fetch();
                if ($item && $item['image_path'] && file_exists($item['image_path'])) {
                    unlink($item['image_path']);
                }
                
                $delStmt = $pdo->prepare("DELETE FROM items WHERE id = ? AND user_id = ?");
                if ($delStmt->execute([$item_id, $_SESSION['user_id']])) {
                    $response = ['status' => 'success', 'message' => 'Item deleted'];
                }
            }
            break;

        case 'mark_notifications_read':
            requireLogin();
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            if ($stmt->execute([$_SESSION['user_id']])) {
                $response = ['status' => 'success', 'message' => 'Notifications marked as read'];
            }
            break;
            
        case 'delete_notification':
            requireLogin();
            $notif_id = $_POST['notification_id'] ?? null;
            if ($notif_id) {
                $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
                if ($stmt->execute([$notif_id, $_SESSION['user_id']])) {
                    $response = ['status' => 'success', 'message' => 'Notification deleted'];
                }
            }
            break;

        // ----- ADMIN ACTIONS -----
        case 'admin_approve_item':
            requireAdmin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                // Fetch user_id and title before updating
                $usrStmt = $pdo->prepare("SELECT user_id, title FROM items WHERE id = ?");
                $usrStmt->execute([$item_id]);
                $itemInfo = $usrStmt->fetch();

                $stmt = $pdo->prepare("UPDATE items SET status = 'approved' WHERE id = ?");
                if ($stmt->execute([$item_id])) {
                    logAdminAction($pdo, "Approved item ID: $item_id", $_SESSION['name']);
                    
                    // Notify user
                    if ($itemInfo) {
                         $notifMsg = "Your reported item \"" . $itemInfo['title'] . "\" has been approved and is now live!";
                         $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                         $notifStmt->execute([$itemInfo['user_id'], $notifMsg]);
                    }
                    
                    $response = ['status' => 'success', 'message' => 'Item approved'];
                }
            }
            break;
            
        case 'admin_reject_item':
            requireAdmin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                // Fetch user_id and title before updating
                $usrStmt = $pdo->prepare("SELECT user_id, title FROM items WHERE id = ?");
                $usrStmt->execute([$item_id]);
                $itemInfo = $usrStmt->fetch();

                $stmt = $pdo->prepare("UPDATE items SET status = 'rejected' WHERE id = ?");
                if ($stmt->execute([$item_id])) {
                    logAdminAction($pdo, "Rejected item ID: $item_id", $_SESSION['name']);

                    // Notify user
                    if ($itemInfo) {
                         $notifMsg = "Your reported item \"" . $itemInfo['title'] . "\" has been rejected by the administrator.";
                         $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                         $notifStmt->execute([$itemInfo['user_id'], $notifMsg]);
                    }

                    $response = ['status' => 'success', 'message' => 'Item rejected'];
                }
            }
            break;

        case 'admin_resolve_item':
            requireAdmin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                $stmt = $pdo->prepare("UPDATE items SET status = 'resolved' WHERE id = ?");
                if ($stmt->execute([$item_id])) {
                    logAdminAction($pdo, "Force resolved item ID: $item_id", $_SESSION['name']);
                    $response = ['status' => 'success', 'message' => 'Item resolved'];
                }
            }
            break;
            
        case 'admin_delete_item':
            requireAdmin();
            $item_id = $_POST['item_id'] ?? null;
            if ($item_id) {
                 // Delete image if exists
                $stmt = $pdo->prepare("SELECT image_path FROM items WHERE id = ?");
                $stmt->execute([$item_id]);
                $item = $stmt->fetch();
                if ($item && $item['image_path'] && file_exists($item['image_path'])) {
                    unlink($item['image_path']);
                }
                
                $delStmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
                if ($delStmt->execute([$item_id])) {
                    logAdminAction($pdo, "Deleted item ID: $item_id", $_SESSION['name']);
                    $response = ['status' => 'success', 'message' => 'Item deleted permanently'];
                }
            }
            break;
            
        case 'admin_delete_user':
            requireAdmin();
            $user_id = $_POST['user_id'] ?? null;
            if ($user_id && $user_id != $_SESSION['user_id']) { // prevent self-deletion
                // Images for this user's items might need manual unlinking depending on DB constraints vs FS
                $stmt = $pdo->prepare("SELECT image_path FROM items WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $items = $stmt->fetchAll();
                foreach($items as $i) {
                     if ($i['image_path'] && file_exists($i['image_path'])) {
                        unlink($i['image_path']);
                     }
                }
                
                $delStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                if ($delStmt->execute([$user_id])) {
                    logAdminAction($pdo, "Deleted user ID: $user_id", $_SESSION['name']);
                    $response = ['status' => 'success', 'message' => 'User and all associated data deleted'];
                }
            } else {
                 $response = ['status' => 'error', 'message' => 'Cannot delete this user.'];
            }
            break;
    }
} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

echo json_encode($response);
exit;
?>
