<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hardware_pos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL for redirection
define('BASE_URL', 'http://localhost/hardware_pos');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create PDO instance
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    
    // Set PDO attributes
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch(PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect to specified URL
 */
function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    header("Location: $url");
    exit;
}

/**
 * Get base URL for assets and links
 */
function base_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();
?>