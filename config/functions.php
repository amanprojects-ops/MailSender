<?php
/**
 * Global Utility Functions and Security Helpers
 */

// Start application session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * XSS Prevention: Sanitize output data
 */
function hb($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token from a POST request
 */
function validate_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("Security Check Failed: Invalid CSRF Token.");
    }
    return true;
}

/**
 * Simple Rate Limiting (per-session-based)
 * Prevent rapid-fire requests.
 */
function rate_limit($limit_seconds = 2) {
    $current_time = time();
    if (isset($_SESSION['last_request_time'])) {
        $elapsed = $current_time - $_SESSION['last_request_time'];
        if ($elapsed < $limit_seconds) {
            die("Error: Please wait a few seconds before another request.");
        }
    }
    $_SESSION['last_request_time'] = $current_time;
}

/**
 * Simple Redirect function
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Check if the user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login to access a page
 */
function require_login() {
    if (!is_logged_in()) {
        redirect('../auth/login.php');
    }
}
?>
