# 🎓 College Lost & Found Portal

A premium, modern, and fully featured lost and found platform built specifically for college campuses. It allows students to report lost or found items, chat securely with finders, and track claims while providing administrators with a powerful, real-time approval and moderation pipeline.

This repository features a **state-of-the-art UI/UX** with beautiful slate-navy ambient mesh glows, seamless light/dark/system theme synchronization, secure authentication, and a developer-friendly Google Sign-In simulator.

---

## ✨ Key Features

### 🌟 Premium UI & Modern Design
* **Ambient Slate Theme**: Upgraded from flat black to a sophisticated slate-navy space theme (`#0b0d16` / `#121526`) with ambient mesh background glows.
* **Responsive Layouts**: Designed to look stunning on mobile, tablet, and desktop viewports alike.
* **Flicker-Free Theme Engine**: Seamlessly switch between **Light Mode**, **Dark Mode**, and **System Settings**. Powered by a native JavaScript engine (`theme-engine.js`) that reads local storage instantly to prevent flickering.

### 👤 Student Dashboard & Features
* **Report & Browse Items**: Log lost/found items with categories, locations, dates, descriptions, and optional photo uploads.
* **Direct Context-Aware Messaging**: Integrated chat system to communicate with the finder or owner of an item.
* **Live Notifications**: Immediate visual alerts when items are approved, rejected, resolved, or when a new chat message arrives.
* **Profile Management**: Update display details and passwords securely. Integrated checks prevent conflicts for Google Sign-In accounts.
* **Self-Service Password Recovery**: Secure password reset flow using secure hashed validation checks.

### 🛡️ Admin Dashboard & Moderation Control
* **Modular Dashboard**: Quick-glance statistical counters tracking total users, pending logs, approved items, and resolved cases.
* **Real-Time Item Verification**: Browse pending requests, approve postings to make them public, or reject/resolve them instantly.
* **User Management Console**: Review and manage registered student accounts.
* **System Audit Trail**: Complete action logging tracker (`audit_logs`) tracking administrative actions for security and transparency.

---

## 🛠️ Technology Stack

* **Backend**: PHP 7.4 / 8.x (using **PDO** with strictly prepared statements for robust security against SQL injection).
* **Frontend**: Vanilla HTML5, Modern CSS3 with Custom variables for seamless theme tokens, and clean Vanilla JavaScript.
* **Database**: MySQL / MariaDB.
* **Authentication**: Native secure session handlers + Google Identity OAuth 2.0 Platform (GSI).

---

## 📦 Project Directory Structure

```plaintext
Orignal_LF/
├── config.php            # Global PDO database connections, helpers, and session setup
├── database.sql          # Core database schema (tables, constraints, default data)
├── style.css             # Main styling system, layout grids, variables, and themes
├── theme-engine.js       # Light/Dark/System sync manager (no-flicker loader)
├── index.php             # Public landing page with portal overview and live item grids
├── login.php             # User login portal (supports Google Identity + custom credentials)
├── register.php          # Student account creation page
├── forgot-password.php   # Account recovery request form
├── reset-password.php    # Hashed token verification & password changer
├── dashboard.php         # Student home panel (browse, report items, chats, settings)
├── google-login.php      # Google OAuth backend verification handler
├── ajax_handlers.php     # Async API for chats, theme storage, notifications, and actions
├── legal.php             # Terms of service, Privacy statement, and campus guidelines
├── uploads/              # Storage directory for uploaded item photos
│
└── [Admin Module Files]
    ├── admin_header.php
    ├── admin_sidebar.php
    ├── admin_dashboard.php
    ├── admin_pending.php
    ├── admin_approved.php
    ├── admin_users.php
    └── admin_logs.php
```

---

## 🚀 Local Setup & Installation

### Prerequisites
* **Local Web Server**: Install [XAMPP](https://www.apachefriends.org/), WAMP, or MAMP.
* **PHP**: Version 7.4 or newer.
* **Database**: MySQL.

---

### Step 1: Clone or Copy the Repository
Place the `Orignal_LF` project directory directly into your web server's public document root:
* **XAMPP (Windows)**: `C:\xampp\htdocs\Orignal_LF\`
* **XAMPP (macOS)**: `/Applications/XAMPP/xamppfiles/htdocs/Orignal_LF/`

---

### Step 2: Import the Database
1. Start **Apache** and **MySQL** in your XAMPP Control Panel.
2. Open your web browser and navigate to **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Click the **Databases** tab, type `lostfound` as the database name, and click **Create**.
4. Select the newly created `lostfound` database in the left sidebar.
5. Go to the **Import** tab at the top.
6. Click **Choose File** and select `database.sql` from your project folder.
7. Scroll down and click **Import** (or **Go**). 

---

### Step 3: Configure Database Settings
1. Open `config.php` in a text editor.
2. Locate the configuration credentials section (Lines 8–11):
   ```php
   $host = '127.0.0.1';      
   $dbname = 'lostfound';     
   $username = 'root';             
   $password = ''; 
   ```
3. If you have customized your local MySQL username or password, update these values. Otherwise, the default XAMPP settings (user: `root`, password: `""`) will connect instantly.

---

### Step 4: Set Up Google OAuth 2.0 Credentials (Optional)
To use the live Google Sign-In button locally:
1. Visit the [Google Cloud Console](https://console.cloud.google.com/).
2. Navigate to **APIs & Services > Credentials** and create an OAuth 2.0 Client ID for a Web Application.
3. Configure the OAuth Consent Screen and add these local addresses under the application settings:
   * **Authorized JavaScript origins**: `http://localhost`
   * **Authorized redirect URIs**: `http://localhost/Orignal_LF/google-login.php`
4. Copy your **Client ID** and **Client Secret** and add them to:
   * **`login.php` (Lines 8–9)**:
     ```php
     $google_client_id = "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com";
     $google_client_secret = "YOUR_GOOGLE_CLIENT_SECRET";
     ```
   * **`google-login.php` (Lines 8–9)**:
     ```php
     $google_client_id = "YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com";
     $google_client_secret = "YOUR_GOOGLE_CLIENT_SECRET";
     ```

#### 🛠️ Developer Simulator Bypass Mode
If you do not wish to set up Google Cloud credentials yet, you can test the entire Google authentication pipeline locally using **Simulator Mode**:
1. Open `login.php` and `google-login.php`.
2. Toggle `$force_simulator` to `true`:
   ```php
   $force_simulator = true;
   ```
3. A friendly simulator widget will appear on the login page allowing you to test both the `System Admin` and `Student` roles instantly.

---

### Step 5: Test the Portal
1. Open your web browser and go to: `http://localhost/Orignal_LF/`
2. You can log in using the default local administrative credentials:
   * **Admin Email**: `admin@college.edu`
   * **Admin Password**: `admin123`
3. Register new student accounts on the portal or test using the simulation options.

---

## 🌐 Live Deployment (Hosting on InfinityFree)

If you decide to host this project on **InfinityFree.com**, keep these key adjustments in mind:

1. **Database Config**: Update `config.php` with the live database server details provided in your InfinityFree Client Area. Do not use `localhost` or `127.0.0.1`.
2. **Google OAuth Config**: Google restricts redirect requests. You must replace `http://localhost/...` with your live domain name (e.g. `http://yourdomain.infinityfreeapp.com`) in your Google Cloud Console OAuth setup.
3. **Upload Subfolder Rule**: Using an FTP client like FileZilla, upload only the **contents** of your local `Orignal_LF` folder directly into the remote server's `htdocs/` folder (such that `index.php` is located at `htdocs/index.php`), not the parent directory itself.

---

## 📄 License
This project is for educational use. Feel free to clone, customize, and deploy it to modernize your campus community safety and resource logging!
