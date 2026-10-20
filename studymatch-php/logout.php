<?php
require __DIR__ . '/includes/auth.php';
start_secure_session();

// Only the Log out button (a POST form with a token) may log you out
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect(current_user() ? 'dashboard.php' : 'login.php');
}

// Clear everything stored in the session
$_SESSION = [];
session_destroy();

// Start a new empty session just to show the message
start_secure_session();
set_flash('ok', 'You have been logged out.');
redirect('login.php');