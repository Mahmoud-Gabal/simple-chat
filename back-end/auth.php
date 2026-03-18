<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $loginPath = (basename(dirname($_SERVER['SCRIPT_NAME'])) === 'apis') ? '../login.php' : 'login.php';
        header('Location: ' . $loginPath);
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'name'  => $_SESSION['user_name'] ?? '',
    ];
}