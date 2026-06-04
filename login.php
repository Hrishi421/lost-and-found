<?php
// login.php
require 'config.php';

// ==========================================
// 1. CONFIGURE YOUR CLIENT ID & SECRET
// ==========================================
$google_client_id = "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com";
$google_client_secret = "YOUR_GOOGLE_CLIENT_SECRET";

// Set to true if you want to force Simulator Mode for local development testing
$force_simulator = false; 

$is_google_configured = ($google_client_id !== "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com" && !$force_simulator);

// ==========================================
// DYNAMIC REDIRECT URI BUILDER
// ==========================================
// Computes absolute path so Google GSI redirects cleanly in subdirectories & live domains alike
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$uri = $_SERVER['REQUEST_URI'];
$dir = dirname($uri);
$dir = ($dir === '\\' || $dir === '/') ? '' : $dir;
$google_login_uri = $protocol . $host . $dir . '/google-login.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Both fields are required.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Setup session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_method'] = 'email';

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <!-- Google Identity Services Library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="auth-page">
    <div class="auth-left">
        <div class="auth-graphic-text">
            <h2>Welcome Back</h2>
            <p style="color:var(--text-muted); font-size:1.1rem;">Securely access the college portal.</p>
        </div>
    </div>
    
    <div class="auth-right">
        <div class="auth-container">
            <h2 class="auth-title">Log In</h2>
            <p class="auth-subtitle">Enter your credentials to continue.</p>
            
            <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #34d399; margin-bottom: 1.5rem;">
                    <span>✓</span> Password reset successful! Log in below.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>⚠️</span> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-floating">
                    <input type="email" id="email" name="email" required placeholder="name@college.edu">
                    <label for="email">Campus Email</label>
                </div>
                
                <div class="form-floating" style="margin-bottom: 0.5rem;">
                    <input type="password" id="password" name="password" required placeholder="Password">
                    <label for="password">Password</label>
                    <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
                </div>
                <div style="text-align: right; margin-bottom: 1.5rem;">
                    <a href="forgot-password.php" style="color: var(--text-muted); font-size: 0.85rem; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-muted)'">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <!-- Visual "Or" Divider -->
            <div style="text-align:center; margin: 1.5rem 0; color: var(--text-muted); position: relative; font-size: 0.9rem;">
                <span style="background: var(--card-bg); padding: 0 1rem; position: relative; z-index: 1;">or</span>
                <div style="border-top: 1px solid var(--border-color); position: absolute; top: 50%; left: 0; right: 0; z-index: 0;"></div>
            </div>

            <div class="google-signin-wrapper" style="position: relative; margin-top: 0.5rem;">
                <?php if ($is_google_configured): ?>
                    <!-- Official Google One-Tap & Sign-In Buttons (Will try to render, but may be blocked by adblockers/origin constraints) -->
                    <div id="g_id_onload"
                         data-client_id="<?= htmlspecialchars($google_client_id) ?>"
                         data-context="signin"
                         data-ux_mode="redirect"
                         data-login_uri="<?= htmlspecialchars($google_login_uri) ?>"
                         data-auto_prompt="false">
                    </div>

                    <div class="g_id_signin" id="officialGoogleBtn"
                         data-type="standard"
                         data-shape="rectangular"
                         data-theme="outline"
                         data-text="signin_with"
                         data-size="large"
                         data-logo_alignment="left"
                         data-width="100%">
                    </div>
                <?php endif; ?>

                <!-- Custom Simulated / Fallback Google Sign-In Button (Always visible by default, hidden via JS if Google successfully renders) -->
                <button type="button" id="fallbackGoogleBtn" class="btn-google-custom" onclick="openGoogleSimulator()">
                    <svg viewBox="0 0 24 24" width="100%" height="100%">
                        <path fill="#EA4335" d="M12.24 10.285V14.4h6.887c-.648 2.41-2.519 4.114-5.136 4.114A5.85 5.85 0 0 1 8.1 12.657a5.85 5.85 0 0 1 5.89-5.857c1.477 0 2.8.54 3.82 1.423l3.22-3.22C19.06 3.12 16.64 2 13.99 2 8.47 2 4 6.47 4 12s4.47 10 9.99 10c5.8 0 9.77-4.08 9.77-9.94 0-.616-.055-1.21-.165-1.775H12.24Z"/>
                    </svg>
                    <span>Sign in with Google <small style="color:var(--text-muted); font-weight:normal; margin-left:2px;">(Simulator)</small></span>
                </button>
            </div>
        
            <div class="auth-footer">
                Don't have an account? <a href="register.php" style="font-weight:600;">Create one</a>
                <br><br>
                <a href="index.php" style="color:var(--text-muted);">← Back to Platform</a>
            </div>
        </div>
    </div>

    <!-- Google Sign-In Simulator Modal Overlay (Always available as a fallback/interactive simulation tool) -->
    <div id="googleSimulator" class="sim-overlay" onclick="if(event.target === this) closeGoogleSimulator()">
        <div class="sim-modal">
            <button class="sim-close" onclick="closeGoogleSimulator()">&times;</button>
            
            <div class="sim-header">
                <div class="sim-title">Google Sign-In Simulator</div>
                <?php if ($is_google_configured): ?>
                    <div class="sim-subtitle" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 0.75rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; color: #fef08a; font-size: 0.85rem; text-align: left;">
                        ⚠️ <strong>Local Origin Restriction:</strong> Google blocks rendering the official button on unauthorized localhost origins. To run live Google auth, add your current URL to the "Authorized JavaScript origins" in Google Console. In the meantime, select a mock profile below to test!
                    </div>
                <?php else: ?>
                    <div class="sim-subtitle">Development Mode active. Select a mock campus email to test the Swiggy/Zomato frictionless login logic:</div>
                <?php endif; ?>
            </div>
            
            <div class="sim-accounts-list">
                <!-- Option 1: Existing Student -->
                <div class="sim-account-option" onclick="submitMockLogin('mock_token_satya')">
                    <div class="sim-avatar">S</div>
                    <div class="sim-info">
                        <div class="sim-name">Satya (Student)</div>
                        <div class="sim-email">satya@gmail.com</div>
                    </div>
                    <span class="sim-badge sim-badge-student">Exists</span>
                </div>
                
                <!-- Option 2: Existing Admin -->
                <div class="sim-account-option" onclick="submitMockLogin('mock_token_admin')">
                    <div class="sim-avatar admin">A</div>
                    <div class="sim-info">
                        <div class="sim-name">System Admin (Admin)</div>
                        <div class="sim-email">admin@college.edu</div>
                    </div>
                    <span class="sim-badge sim-badge-admin">Admin</span>
                </div>
                
                <!-- Option 3: New User Auto-Register -->
                <div class="sim-account-option" onclick="submitMockLogin('mock_token_new')">
                    <div class="sim-avatar new">AS</div>
                    <div class="sim-info">
                        <div class="sim-name">Alex Smith (New User)</div>
                        <div class="sim-email">alex.smith@college.edu</div>
                    </div>
                    <span class="sim-badge sim-badge-new">Auto-Reg</span>
                </div>
            </div>
            
            <div class="sim-footer-note">
                🔒 <strong>Zero Setup Required:</strong> This simulator generates a mock identity JWT and cookie CSRF tokens, verifying they execute the registration & session login gates.
            </div>
        </div>
    </div>

    <script>
        function togglePassword(id) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }

        function openGoogleSimulator() {
            document.getElementById('googleSimulator').classList.add('active');
        }

        function closeGoogleSimulator() {
            document.getElementById('googleSimulator').classList.remove('active');
        }

        function submitMockLogin(mockToken) {
            // Generate a mock CSRF token
            const mockCsrf = 'mock_csrf_val_123';
            
            // Set the g_csrf_token cookie to match
            document.cookie = "g_csrf_token=" + mockCsrf + "; path=/; max-age=3600; SameSite=Lax";
            
            // Create form programmatically
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'google-login.php';
            
            const credentialInput = document.createElement('input');
            credentialInput.type = 'hidden';
            credentialInput.name = 'credential';
            credentialInput.value = mockToken;
            form.appendChild(credentialInput);
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'g_csrf_token';
            csrfInput.value = mockCsrf;
            form.appendChild(csrfInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        <?php if ($is_google_configured): ?>
        // Smart Visibility Engine: If Google GSI iframe successfully renders, hide our fallback simulator button.
        // Otherwise (due to blocked origins or adblockers), our custom button stays active and opens the simulator!
        window.addEventListener('load', () => {
            let checks = 0;
            const checkGsi = setInterval(() => {
                const officialBtn = document.getElementById('officialGoogleBtn');
                if (officialBtn && officialBtn.querySelector('iframe')) {
                    // Google rendered successfully! Hide our fallback button.
                    document.getElementById('fallbackGoogleBtn').style.display = 'none';
                    clearInterval(checkGsi);
                }
                checks++;
                if (checks > 15) {
                    clearInterval(checkGsi); // Stop checking after 3 seconds
                }
            }, 200);
        });
        <?php endif; ?>
    </script>
</body>
</html>
