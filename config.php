<?php
// config.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// --- LOCAL XAMPP DATABASE CREDENTIALS ---
$host = '127.0.0.1';      
$dbname = 'lostfound';     
$username = 'root';             
$password = ''; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

// Helper function to sanitize input
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Ensure the user is logged in
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Ensure the user is an admin
function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }
}

// Function to log admin actions globally
function logAdminAction($pdo, $actionStr, $adminName) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (action, performed_by) VALUES (?, ?)");
        $stmt->execute([$actionStr, $adminName]);
    } catch (PDOException $e) {
        // Silently handle if table/database errors occur during logging
    }
}
?>