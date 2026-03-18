<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(
        ['error' => 'Only POST allowed'],
        405
    );
}

if (!isLoggedIn()) {
    json_response(
        ['error' => 'You must be logged in to send messages'],
        401
    );
}

$user = getCurrentUser();
$userId = (int) $user['id'];
$name = trim($_POST['name'] ?? '') ?: $user['name'];
$message = trim($_POST['message'] ?? '');

if ($message === '') {
    json_response(
        ['error' => 'Message is required'],
        400
    );
}

$nameEsc = mysqli_real_escape_string($conn, $name);
$messageEsc = mysqli_real_escape_string($conn, $message);

$sql = "INSERT INTO messages (user_id, name, message, created_at)
        VALUES ($userId, '$nameEsc', '$messageEsc', NOW())";

if (!mysqli_query($conn, $sql)) {
    json_response(
        ['error' => 'Insert failed'],
        500
    );
}

// Ahmed auto-reply for this user's conversation
$ahmedReply = 'Hey! Thanks for your message. Talk to me more!';
$ahmedReplyEsc = mysqli_real_escape_string($conn, $ahmedReply);

$sqlReply = "INSERT INTO messages (user_id, name, message, created_at)
             VALUES ($userId, 'Ahmed', '$ahmedReplyEsc', NOW())";

mysqli_query($conn, $sqlReply);

json_response(['success' => true]);
