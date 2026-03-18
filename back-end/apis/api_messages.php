<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    json_response(
        ['error' => 'You must be logged in to view messages'],
        401
    );
}

$user = getCurrentUser();
$userId = (int) $user['id'];

$sql = "SELECT name, message, created_at 
        FROM messages 
        WHERE user_id = $userId 
        ORDER BY created_at ASC";

$result = mysqli_query($conn, $sql);

$messages = [];

while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        'name'       => $row['name'],
        'message'    => $row['message'],
        'created_at' => $row['created_at'],
    ];
}

json_response($messages);
