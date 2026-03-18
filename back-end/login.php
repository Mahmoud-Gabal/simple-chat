<?php
require_once __DIR__ . '/bootstrap.php';

// If already logged in, go straight to chat UI.
if (isLoggedIn()) {
    header('Location: ../front-end/html/chat.html');
    exit;
}

// Only handle POST; GET should just show the front-end page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../front-end/html/login.html');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $error = 'Email and password are required.';
    header('Location: ../front-end/html/login.html?error=' . urlencode($error));
    exit;
}

$emailEsc = mysqli_real_escape_string($conn, $email);
$res = mysqli_query(
    $conn,
    "SELECT id, email, name, password_hash FROM users WHERE email = '$emailEsc' LIMIT 1"
);
$user = mysqli_fetch_assoc($res);

if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name']  = $user['name'];
    header('Location: ../front-end/html/chat.html');
    exit;
}

$error = 'Invalid email or password.';
header('Location: ../front-end/html/login.html?error=' . urlencode($error));
exit;
