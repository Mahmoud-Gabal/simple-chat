<?php
require_once __DIR__ . '/config.php';

// Use mysqli in a slightly safer, more informative mode.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    // In a real app you might log the detailed error and show a generic message.
    die('Database connection failed');
}
?>