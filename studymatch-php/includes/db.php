<?php
// Database connection used by every page
// These are the default XAMPP settings
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'studymatch';

function db() {
    global $db_host, $db_user, $db_pass, $db_name;

    // Connect only once, then reuse the same connection
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    // Show database errors instead of failing silently
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
    return $conn;
}