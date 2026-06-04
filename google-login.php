<?php
// google-login.php
require 'config.php';

// ==========================================
// 1. CONFIGURE YOUR CLIENT ID & SECRET
// ==========================================
$google_client_id = "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com";
$google_client_secret = "YOUR_GOOGLE_CLIENT_SECRET";

// Set to true if you want to force Simulator Mode for local development testing
$force_simulator = false;

// The Google Identity library POSTs the JWT to this script in the 'credential' parameter
$id_token = $_POST['credential'] ?? '';
$g_csrf_token = $_POST['g_csrf_token'] ?? '';

// ==========================================
// 2. PREVENT CSRF ATTACKS
// ==========================================
if (empty($g_csrf_token) || !isset($_COOKIE['g_csrf_token']) || $g_csrf_token !== $_COOKIE['g_csrf_token']) {
    die("Security verification failed: CSRF token mismatch.");
}

if (empty($id_token)) {
    die("Authentication failed: No credential received.");
}

// ==========================================
// 3. CRYPTOGRAPHICALLY VERIFY TOKEN SIGNATURE WITH GOOGLE API
// ==========================================
$is_mock = (strpos($id_token, 'mock_token_') === 0 && ($google_client_id === "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com" || $force_simulator));

if ($is_mock) {
    // Simulator Mode validation bypass
    $payload = [];
    if ($id_token === 'mock_token_admin') {
        $payload = [
            'email' => 'admin@college.edu',
            'name' => 'System Admin',
            'aud' => $google_client_id,
            'iss' => 'https://accounts.google.com'
        ];
    } elseif ($id_token === 'mock_token_satya') {
        $payload = [
            'email' => 'satya@gmail.com',
            'name' => 'satya',
            'aud' => $google_client_id,
            'iss' => 'https://accounts.google.com'
        ];
    } elseif ($id_token === 'mock_token_new') {
        $payload = [
            'email' => 'alex.smith@college.edu',
            'name' => 'Alex Smith',
            'aud' => $google_client_id,
            'iss' => 'https://accounts.google.com'
        ];
    } else {
        die("Verification failed: Unknown mock token.");
    }
} else {
    // Fetching directly from Google's official oauth2 endpoint is highly secure 
    // and eliminates any requirements for external JWT decoding SDK dependencies.
    $verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5 // 5 seconds connection timeout
        ]
    ]);

    $response = @file_get_contents($verify_url, false, $ctx);
    if ($response === false) {
        die("Verification failed: Could not establish secure handshake with Google validation servers.");
    }

    $payload = json_decode($response, true);

    if (!$payload || isset($payload['error'])) {
        die("Verification failed: Google validation rejected token. Reason: " . ($payload['error_description'] ?? 'Invalid token signature.'));
    }
}

// ==========================================
// 4. VERIFY ISSUER AND AUDIENCE
// ==========================================
// Check that the token's audience (aud) matches your Google Console OAuth Client ID
if ($payload['aud'] !== $google_client_id) {
    die("Audience verification failed: Token audience does not match this system's Client ID.");
}

// Check that the token's issuer (iss) matches Google's accounts domains
$valid_issuers = ['accounts.google.com', 'https://accounts.google.com'];
if (!in_array($payload['iss'], $valid_issuers)) {
    die("Issuer verification failed: Token issuer is not Google.");
}

// ==========================================
// 5. EXTRACT PROFILE DATA
// ==========================================
$email = sanitize($payload['email'] ?? '');
$name = sanitize($payload['name'] ?? '');

if (empty($email) || empty($name)) {
    die("Verification failed: Profile data could not be parsed from identity token.");
}

// ==========================================
// 6. DB CHECK: LOGIN IF EXISTS / AUTO-REGISTER IF NEW
// ==========================================
try {
    // Check if the user already exists in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // --- LOG IN INSTANTLY ---
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_method'] = 'google';
        
        // Log action if they are an admin
        if ($user['role'] === 'admin') {
            logAdminAction($pdo, "Logged in via Google Sign-In", $user['name']);
            header("Location: admin_dashboard.php");
        } else {
            header("Location: dashboard.php");
        }
        exit;
    } else {
        // --- AUTO-REGISTER USER ---
        // Generate a cryptographically secure, random password placeholder.
        // Users who sign in via Google won't need to type this, but having a strong
        // password placeholder prevents manual injection exploits in local standard login forms.
        $random_password = bin2hex(random_bytes(16)); 
        $hashed = password_hash($random_password, PASSWORD_DEFAULT);
        
        $regStmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'student')");
        if ($regStmt->execute([$name, $email, $hashed])) {
            $new_user_id = $pdo->lastInsertId();
            
            // Set session variables instantly
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = 'student';
            $_SESSION['login_method'] = 'google';
            
            // Redirect straight to dashboard - seamless experience
            header("Location: dashboard.php");
            exit;
        } else {
            die("Registration failed: Database reject.");
        }
    }
} catch (PDOException $e) {
    die("Database transaction failed: " . $e->getMessage());
}
?>
