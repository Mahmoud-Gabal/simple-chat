<?php
require_once __DIR__ . '/bootstrap.php';

// Only handle POST; GET should just show the front-end page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../front-end/html/register.html');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['password_confirm'] ?? '';
$name     = trim($_POST['name'] ?? '');

if ($email === '' || $password === '' || $name === '') {
    $error = 'All fields are required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
} elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
} elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
} else {
    $emailEsc = mysqli_real_escape_string($conn, $email);
    $res = mysqli_query(
        $conn,
        "SELECT id FROM users WHERE email = '$emailEsc'"
    );
    if (mysqli_fetch_assoc($res)) {
        $error = 'This email is already registered.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $nameEsc = mysqli_real_escape_string($conn, $name);
        $hashEsc = mysqli_real_escape_string($conn, $hash);
        $sql = "INSERT INTO users (email, password_hash, name, created_at)
                VALUES ('$emailEsc', '$hashEsc', '$nameEsc', NOW())";
        if (mysqli_query($conn, $sql)) {
            // Registration successful: send user to login with a success flag.
            header('Location: ../front-end/html/login.html?registered=1');
            exit;
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

header('Location: ../front-end/html/register.html?error=' . urlencode($error));
exit;
