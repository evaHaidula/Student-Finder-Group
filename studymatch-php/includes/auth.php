<?php
// Login helpers used on every page

// Start the session (this is how PHP remembers who is logged in)
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Returns the logged-in user (id, name, role) or null if nobody is logged in
function current_user() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'   => (int)$_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role']
    ];
}

// Where each role goes after login
function home_for_role($role) {
    if ($role === 'admin') {
        return 'admin.php';
    }
    return 'dashboard.php';
}

// Put at the top of pages that need a login
function require_login() {
    start_secure_session();
    $user = current_user();
    if ($user === null) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }
    return $user;
}

// Put at the top of admin pages
function require_admin() {
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        echo '<h1>Access denied</h1><p>This area is for administrators only.</p>';
        echo '<p><a href="dashboard.php">Back to dashboard</a></p>';
        exit;
    }
    return $user;
}

// Go to another page
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// Makes text safe to print inside HTML (use it on everything from users or the database)
function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// CSRF token: a secret code that proves a form came from our own site
function csrf_token() {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

// Put csrf_field() inside every POST form
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

// Checks the token when a form is submitted
function csrf_valid() {
    return isset($_POST['csrf_token']) && isset($_SESSION['csrf']) && $_POST['csrf_token'] === $_SESSION['csrf'];
}

// A message that shows once on the next page
function set_flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Shows the message and then removes it
function render_flash() {
    if (!isset($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $class = 'message-error';
    if ($flash['type'] === 'ok') {
        $class = 'message-ok';
    }
    return '<p class="' . $class . '" role="status">' . e($flash['message']) . '</p>';
}

// Accepts name@nust.na and name@students.nust.na
function is_nust_email($email) {
    $email = strtolower($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return str_ends_with($email, '@nust.na') || str_ends_with($email, '@students.nust.na');
}