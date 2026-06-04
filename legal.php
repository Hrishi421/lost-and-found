<?php
// legal.php
require 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal & Guidelines - College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .legal-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2.5rem;
            margin: 3rem 0;
            align-items: start;
        }
        .legal-sidebar {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            position: sticky;
            top: 2rem;
            box-shadow: var(--shadow-sm);
        }
        .legal-menu-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 1rem;
            padding-left: 0.75rem;
        }
        .legal-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            color: var(--text-muted);
            font-weight: 500;
            border-radius: var(--radius-sm);
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 0.95rem;
        }
        .legal-menu-item:hover {
            color: var(--primary-color);
            background: rgba(94, 106, 210, 0.05);
        }
        .legal-menu-item.active {
            color: var(--primary-color);
            background: rgba(94, 106, 210, 0.08);
            font-weight: 600;
        }
        .legal-content-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 3rem;
            box-shadow: var(--shadow-sm);
        }
        .legal-tab-pane {
            display: none;
        }
        .legal-tab-pane.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        .legal-doc h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.75rem;
        }
        .legal-doc h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .legal-doc p, .legal-doc li {
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 1.2rem;
            font-size: 1.05rem;
        }
        .legal-doc ul, .legal-doc ol {
            padding-left: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .legal-doc li {
            margin-bottom: 0.5rem;
        }
        .last-updated {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            display: inline-block;
            background: var(--bg-app);
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            border: 1px solid var(--border-color);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 900px) {
            .legal-layout {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .legal-sidebar {
                position: static;
            }
            .legal-content-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo-container">
                <div class="logo-icon-wrapper">
                    <svg class="logo-svg-back" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="6" /></svg>
                    <svg class="logo-svg-front" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" /><line x1="21" y1="21" x2="16.65" y2="16.65" /><path d="M11 8v6M8 11h6" /></svg>
                </div>
                <span class="logo-text">College L&F<span class="logo-dot"></span></span>
            </a>
            <div class="nav-links">
                <a href="index.php" style="margin-right: 1.5rem;">Home</a>
                <?php if($is_logged_in): ?>
                    <a href="<?= $is_admin ? 'admin_dashboard.php' : 'dashboard.php' ?>" class="btn btn-outline" style="padding: 0.4rem 1rem;">Dashboard</a>
                <?php else: ?>
                    <a href="login.php">Log in</a>
                    <a href="register.php" class="btn btn-primary">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container">
        <div class="legal-layout">
            <!-- Sidebar Selection Menu -->
            <aside class="legal-sidebar">
                <div class="legal-menu-title">Legal Documents</div>
                <button class="legal-menu-item active" onclick="switchLegalTab('privacy')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    Privacy Policy
                </button>
                <button class="legal-menu-item" onclick="switchLegalTab('terms')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    Terms of Service
                </button>
                <button class="legal-menu-item" onclick="switchLegalTab('guidelines')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Campus Guidelines
                </button>
            </aside>

            <!-- Documents View Pane -->
            <main class="legal-content-card">
                <!-- PRIVACY POLICY PANE -->
                <div id="privacy" class="legal-tab-pane legal-doc active">
                    <span class="last-updated">Last Updated: May 22, 2026</span>
                    <h2>Privacy Policy</h2>
                    <p>Welcome to the College Lost & Found Portal. We respect your privacy and are committed to protecting the personal data of our campus community. This policy outlines how we handle information submitted to our platform.</p>
                    
                    <h3>1. Information We Collect</h3>
                    <p>To facilitate the matching and return of lost items, we collect the following limited information:</p>
                    <ul>
                        <li><strong>Profile Data:</strong> Full name and campus email address provided during registration.</li>
                        <li><strong>Report Details:</strong> Description of the item, category, location where it was lost or found, and any optional uploaded photographs.</li>
                        <li><strong>Chat Logs:</strong> Messages sent between students to coordinate the verification and return of items.</li>
                    </ul>

                    <h3>2. How We Use Your Data</h3>
                    <p>Your personal data is strictly utilized to operate, secure, and improve the Lost and Found portal services:</p>
                    <ul>
                        <li>Displaying reported item details to enable other students to identify their belongings.</li>
                        <li>Notifying you when items are approved, rejected, or when you receive coordinating messages.</li>
                        <li>Allowing students to safely communicate via our integrated direct chat system without exposing private telephone numbers or personal details.</li>
                    </ul>

                    <h3>3. Information Sharing & Disclosure</h3>
                    <p>We do not sell, trade, or transfer your personal data to external parties. Information is only visible to:</p>
                    <ul>
                        <li>Authorized System Administrators for safety moderation and claim approvals.</li>
                        <li>Other registered campus students interacting with your public lost or found posts.</li>
                    </ul>

                    <h3>4. Data Retention & Security</h3>
                    <p>Account data is retained as long as your student account is active. Administrators have options to permanently purge accounts and their corresponding posts. All data is securely stored in campus database registries under standard access security protections.</p>
                </div>

                <!-- TERMS OF SERVICE PANE -->
                <div id="terms" class="legal-tab-pane legal-doc">
                    <span class="last-updated">Last Updated: May 22, 2026</span>
                    <h2>Terms of Service</h2>
                    <p>By registering or using the College Lost & Found Portal, you agree to comply with the terms and conditions outlined below. Please read them carefully.</p>
                    
                    <h3>1. Eligibility & Accounts</h3>
                    <p>Access to this portal is restricted to active students, faculty, and authorized administrators of the campus community. You must register with an official campus email address. You are responsible for keeping your login credentials secure.</p>

                    <h3>2. Rules of Platform Conduct</h3>
                    <p>We expect all members of the campus community to act with integrity. When submitting reports or utilizing chats, you agree not to:</p>
                    <ul>
                        <li>Submit false, fraudulent, or intentionally misleading lost or found reports.</li>
                        <li>Post images or descriptions of illegal, prohibited, or hazardous items.</li>
                        <li>Harass, abuse, or spam other students through the message feature.</li>
                        <li>Claim ownership of an item that you know does not belong to you.</li>
                    </ul>

                    <h3>3. Liability Limitations</h3>
                    <p>This platform acts solely as a matching utility to connect finders and owners. The university and platform administrators are not liable for the actual physical condition of returned items, nor do they guarantee that all reported lost items will be recovered. Students coordinate handoffs at their own risk.</p>

                    <h3>4. Termination & Auditing</h3>
                    <p>Administrators reserves the right to audit platform usage, reject reports that violate campus policies, and delete user accounts that engage in fraudulent behavior or harass other users. Suspicious actions are flagged automatically in the system audit logs.</p>
                </div>

                <!-- CAMPUS GUIDELINES PANE -->
                <div id="guidelines" class="legal-tab-pane legal-doc">
                    <span class="last-updated">Last Updated: May 22, 2026</span>
                    <h2>Campus Guidelines</h2>
                    <p>To ensure a safe, efficient, and friendly lost-and-found experience on campus, we ask all students to follow these standard operational guidelines.</p>
                    
                    <h3>1. Reporting Found Items</h3>
                    <p>If you find an item on campus, please follow these steps:</p>
                    <ol>
                        <li><strong>Submit a Report:</strong> Post the item immediately on the platform with a clear description and the location where you found it.</li>
                        <li><strong>Hide Critical Details:</strong> Keep one or two distinct features private (e.g., the serial number, phone lock-screen photo, or exact currency amount inside a wallet). This allows you to verify the actual owner during coordination.</li>
                        <li><strong>Safe Storage:</strong> Keep the item securely in your possession, or turn it in to the nearest department reception or Campus Security Office, noting this in your report's location field.</li>
                    </ol>

                    <h3>2. Verification & Safe Coordination</h3>
                    <p>When someone reaches out to claim an item you found:</p>
                    <ul>
                        <li>Ask the claimant to verify the private features you withheld (e.g. "What color is the phone cover?" or "What stickers are on the laptop laptop?").</li>
                        <li><strong>Arrange Handoffs Safely:</strong> Always meet in broad daylight at a busy, public campus location (e.g., the Central Library lobby, the student center dining court, or outside the Campus Security Office).</li>
                        <li>Never meet in secluded, off-campus locations or share private residential addresses.</li>
                    </ul>

                    <h3>3. Handoff Completion</h3>
                    <p>Once an item has been successfully returned to its rightful owner, please navigate to your **My Items** tab on the student dashboard and click **Resolve**. This marks the item resolved and removes it from the public listings to keep the database tidy.</p>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer Area -->
    <footer class="site-footer" style="margin-top: 5rem;">
        <div class="container" style="text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            &copy; <?= date('Y') ?> College Lost and Found. All rights reserved.
        </div>
    </footer>

    <script>
        // Switch Active Document Tab
        function switchLegalTab(tabId) {
            // Remove active classes
            document.querySelectorAll('.legal-tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.legal-menu-item').forEach(el => el.classList.remove('active'));
            
            // Activate selected tab pane
            const pane = document.getElementById(tabId);
            if (pane) {
                pane.classList.add('active');
            }
            
            // Highlight correct menu button
            document.querySelectorAll('.legal-menu-item').forEach(btn => {
                const onclickAttr = btn.getAttribute('onclick') || '';
                if (onclickAttr.includes(`'${tabId}'`) || onclickAttr.includes(`"${tabId}"`)) {
                    btn.classList.add('active');
                }
            });
            
            // Update URL without reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);
            
            // Scroll to the top of the content card smoothly on mobile
            if (window.innerWidth <= 900) {
                document.querySelector('.legal-content-card').scrollIntoView({ behavior: 'smooth' });
            }
        }

        // On load, parse URL parameter to select appropriate tab
        window.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const targetTab = params.get('tab');
            if (targetTab && ['privacy', 'terms', 'guidelines'].includes(targetTab)) {
                switchLegalTab(targetTab);
            }
        });
    </script>
</body>
</html>
