<?php
// index.php
require '../backend/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in = isset($_SESSION['user_id']);

// Fetch items
$stmt = $pdo->prepare("SELECT i.*, u.name as user_name FROM items i JOIN users u ON i.user_id = u.id WHERE i.status = 'approved' ORDER BY i.created_at DESC");
$stmt->execute();
$items = $stmt->fetchAll();

$categories = array_unique(array_column($items, 'category'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Lost and Found</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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
                <?php if($is_logged_in): ?>
                    <a href="<?= $is_admin ? 'admin_dashboard.php' : 'dashboard.php' ?>" class="btn btn-outline" style="padding: 0.4rem 1rem;">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline" style="padding: 0.4rem 1rem;">Logout</a>
                <?php else: ?>
                    <a href="login.php">Log in</a>
                    <a href="register.php" class="btn btn-primary">Sign up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>Lost something? Found something?</h1>
            <p>The official college portal to report and claim lost items on campus.</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <div class="filter-bar">
                <div class="filter-input-group">
                    <input type="text" id="searchInput" placeholder="Search items, locations, keywords...">
                </div>
                <select id="typeFilter">
                    <option value="all">All Types</option>
                    <option value="lost">Lost Items</option>
                    <option value="found">Found Items</option>
                </select>
                <select id="categoryFilter">
                    <option value="all">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Items Grid -->
        <div class="items-grid">
            <?php if(empty($items)): ?>
                <div class="empty-state">
                    <h3>No items found</h3>
                    <p>There are currently no approved items on the platform.</p>
                </div>
            <?php else: ?>
                <?php foreach($items as $item): ?>
                    <div class="item-card" data-type="<?= $item['type'] ?>" data-category="<?= htmlspecialchars($item['category']) ?>">
                        <div class="item-img-container">
                            <span class="badge badge-<?= $item['type'] ?> badge-position"><?= ucfirst($item['type']) ?></span>
                            <?php if($item['image_path'] && file_exists($item['image_path'])): ?>
                                <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="Item Image" class="item-img">
                            <?php else: ?>
                                <div class="item-placeholder">No Image Available</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-content">
                            <h3 class="item-title search-target"><?= htmlspecialchars($item['title']) ?></h3>
                            <div class="item-meta">
                                <span class="badge badge-category" style="font-size:0.65rem; padding:0.15rem 0.5rem;"><?= htmlspecialchars($item['category']) ?></span>
                                <span class="search-target"><?= htmlspecialchars($item['location']) ?></span>
                                <span><?= date('M d, Y', strtotime($item['date_lost_found'])) ?></span>
                            </div>
                            <p class="item-desc search-target"><?= htmlspecialchars($item['description']) ?></p>
                            
                            <button class="btn btn-outline btn-view-details" onclick="openModal(<?= $item['id'] ?>)">View Details</button>
                        </div>
                    </div>

                    <!-- Modal for Item Details -->
                    <div id="modal-<?= $item['id'] ?>" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2><?= htmlspecialchars($item['title']) ?></h2>
                                <button class="close-modal" onclick="closeModal(<?= $item['id'] ?>)">&times;</button>
                            </div>
                            <div class="modal-body">
                                <?php if($item['image_path'] && file_exists($item['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="Item Image" class="modal-img">
                                <?php endif; ?>
                                <p><strong>Category:</strong> <?= htmlspecialchars($item['category']) ?></p>
                                <p><strong>Location:</strong> <?= htmlspecialchars($item['location']) ?></p>
                                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($item['date_lost_found'])) ?></p>
                                <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($item['description'])) ?></p>
                                <p><strong>Reported By:</strong> <?= htmlspecialchars($item['user_name']) ?></p>
                            </div>
                            <div class="modal-footer">
                                <?php if($is_logged_in): ?>
                                    <?php if($_SESSION['user_id'] !== $item['user_id']): ?>
                                        <a href="dashboard.php?tab=messages&contact_id=<?= $item['user_id'] ?>&item_id=<?= $item['id'] ?>" class="btn btn-primary">Message Poster</a>
                                    <?php else: ?>
                                        <span class="badge" style="background:var(--text-muted); color:white;">This is your post</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline">Log in to message</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filtering
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const cards = document.querySelectorAll('.item-card');

        function filterItems() {
            const term = searchInput.value.toLowerCase();
            const type = typeFilter.value;
            const category = categoryFilter.value;

            cards.forEach(card => {
                const cardType = card.getAttribute('data-type');
                const cardCat = card.getAttribute('data-category');
                
                const searchTargets = card.querySelectorAll('.search-target');
                let textContent = '';
                searchTargets.forEach(el => textContent += el.innerText.toLowerCase() + ' ');

                const matchTerm = textContent.includes(term);
                const matchType = type === 'all' || cardType === type;
                const matchCat = category === 'all' || cardCat === category;

                if (matchTerm && matchType && matchCat) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterItems);
        typeFilter.addEventListener('change', filterItems);
        categoryFilter.addEventListener('change', filterItems);

        // Modals
        function openModal(id) {
            document.getElementById('modal-' + id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById('modal-' + id).classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        }
    </script>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="logo-container" style="margin-bottom:1rem;">
                        <div class="logo-icon-wrapper">
                            <svg class="logo-svg-back" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="6" /></svg>
                            <svg class="logo-svg-front" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6" /><line x1="21" y1="21" x2="16.65" y2="16.65" /><path d="M11 8v6M8 11h6" /></svg>
                        </div>
                        <span class="logo-text" style="font-size:1.2rem;">College L&F<span class="logo-dot"></span></span>
                    </div>
                    <p style="color:var(--text-muted); max-width:300px;">
                        The premium, secure, and modern way to find what you lost on campus.
                    </p>
                </div>
                <div class="footer-links">
                    <h4>Platform</h4>
                    <ul>
                        <li><a href="#">Browse Items</a></li>
                        <li><a href="#">Report Lost</a></li>
                        <li><a href="#">Report Found</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="legal.php?tab=privacy">Privacy Policy</a></li>
                        <li><a href="legal.php?tab=terms">Terms of Service</a></li>
                        <li><a href="legal.php?tab=guidelines">Campus Guidelines</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> College Lost and Found. All rights reserved.
            </div>
        </div>
    </footer>
</body>
</html>
