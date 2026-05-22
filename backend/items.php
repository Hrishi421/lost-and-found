<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'post':
        postItem();
        break;
    case 'get_all':
        getAllItems();
        break;
    case 'get_pending':
        getPendingItems();
        break;
    case 'get_my':
        getMyItems();
        break;
    case 'approve':
        moderateItem('approved');
        break;
    case 'reject':
        moderateItem('rejected');
        break;
    case 'resolve':
        resolveItem();
        break;
    case 'delete':
        deleteItem();
        break;
    case 'search':
        searchItems();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login first', 'redirect' => 'login.html']);
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
}

function postItem() {
    global $conn;
    requireLogin();

    $user_id = $_SESSION['user_id'];
    $type = sanitize($conn, $_POST['type'] ?? '');
    $title = sanitize($conn, $_POST['title'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? '');
    $location = sanitize($conn, $_POST['location'] ?? '');
    $date_reported = sanitize($conn, $_POST['date_reported'] ?? date('Y-m-d'));
    $contact_info = sanitize($conn, $_POST['contact_info'] ?? '');

    if (empty($type) || empty($title) || empty($description) || empty($location) || empty($contact_info)) {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }

    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
            $image_url = $upload_dir . $filename;
        }
    }

    $stmt = $conn->prepare("INSERT INTO items (user_id, type, title, description, category, location, date_reported, image_url, contact_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssss", $user_id, $type, $title, $description, $category, $location, $date_reported, $image_url, $contact_info);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Item posted successfully! Awaiting admin approval.', 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to post item']);
    }
}

function getAllItems() {
    global $conn;
    $type_filter = $_GET['type'] ?? '';
    $category_filter = $_GET['category'] ?? '';
    $where = "WHERE i.status = 'approved'";
    if ($type_filter) $where .= " AND i.type = '" . $conn->real_escape_string($type_filter) . "'";
    if ($category_filter) $where .= " AND i.category = '" . $conn->real_escape_string($category_filter) . "'";

    $sql = "SELECT i.*, u.full_name as poster_name FROM items i 
            JOIN users u ON i.user_id = u.id 
            $where ORDER BY i.created_at DESC";
    $result = $conn->query($sql);

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
}

function getPendingItems() {
    global $conn;
    requireAdmin();
    $sql = "SELECT i.*, u.full_name as poster_name, u.email as poster_email FROM items i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status = 'pending' ORDER BY i.created_at DESC";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
}

function getMyItems() {
    global $conn;
    requireLogin();
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM items WHERE user_id = $user_id ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
}

function moderateItem($status) {
    global $conn;
    requireAdmin();
    $item_id = intval($_POST['item_id'] ?? 0);
    if (!$item_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        return;
    }
    $conn->query("UPDATE items SET status='$status' WHERE id=$item_id");
    $admin_id = $_SESSION['user_id'];
    $conn->query("INSERT INTO admin_logs (admin_id, action, item_id) VALUES ($admin_id, 'Item $status', $item_id)");
    echo json_encode(['success' => true, 'message' => "Item $status successfully"]);
}

function resolveItem() {
    global $conn;
    requireLogin();
    $item_id = intval($_POST['item_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $conn->query("UPDATE items SET status='resolved' WHERE id=$item_id AND user_id=$user_id");
    echo json_encode(['success' => true, 'message' => 'Item marked as resolved']);
}

function deleteItem() {
    global $conn;
    requireLogin();
    $item_id = intval($_POST['item_id'] ?? 0);
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        $conn->query("DELETE FROM items WHERE id=$item_id");
    } else {
        $conn->query("DELETE FROM items WHERE id=$item_id AND user_id=$user_id");
    }
    echo json_encode(['success' => true, 'message' => 'Item deleted']);
}

function searchItems() {
    global $conn;
    $query = $conn->real_escape_string($_GET['q'] ?? '');
    $sql = "SELECT i.*, u.full_name as poster_name FROM items i 
            JOIN users u ON i.user_id = u.id 
            WHERE i.status='approved' AND (i.title LIKE '%$query%' OR i.description LIKE '%$query%' OR i.location LIKE '%$query%' OR i.category LIKE '%$query%')
            ORDER BY i.created_at DESC";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode(['success' => true, 'items' => $items]);
}
?>
