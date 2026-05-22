-- =========================================
-- LOST & FOUND CAMPUS PLATFORM - DATABASE
-- =========================================

CREATE DATABASE IF NOT EXISTS lost_found_db;
USE lost_found_db;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ITEMS TABLE
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    location VARCHAR(200) NOT NULL,
    date_reported DATE NOT NULL,
    image_url VARCHAR(255) DEFAULT NULL,
    contact_info VARCHAR(200) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- REPORTS TABLE (flag inappropriate content)
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    reported_by INT NOT NULL,
    reason TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE CASCADE
);

-- MESSAGES TABLE (contact between users)
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ADMIN LOGS TABLE
CREATE TABLE IF NOT EXISTS admin_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    item_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- SAMPLE DATA
-- =========================================

-- Default Admin (password: admin123)
INSERT INTO users (full_name, email, password, role) VALUES
('Admin User', 'admin@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Student (password: student123)
INSERT INTO users (full_name, email, password, role) VALUES
('John Doe', 'john@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- Sample Items
INSERT INTO items (user_id, type, title, description, category, location, date_reported, contact_info, status) VALUES
(2, 'lost', 'Blue Water Bottle', 'A blue Hydro Flask with stickers on it. Very important to me!', 'Personal Items', 'Library - 2nd Floor', '2026-02-20', 'john@campus.edu', 'approved'),
(2, 'found', 'Black Wallet', 'Found a black leather wallet near the cafeteria. Has student ID inside.', 'Wallets', 'Cafeteria Entrance', '2026-02-21', 'john@campus.edu', 'approved'),
(2, 'lost', 'Dell Laptop Charger', 'Lost a Dell 65W laptop charger in the science building.', 'Electronics', 'Science Block - Room 201', '2026-02-22', 'john@campus.edu', 'pending');
