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
$conversationId = (int) ($_POST['conversation_id'] ?? 0);

if ($message === '') {
    json_response(
        ['error' => 'Message is required'],
        400
    );
}

// If conversation_id is missing, use the user's oldest conversation.
if ($conversationId <= 0) {
    $sqlConv = "SELECT c.id
                FROM conversations c
                JOIN conversation_members cm ON cm.conversation_id = c.id
                WHERE cm.user_id = $userId
                ORDER BY c.created_at ASC
                LIMIT 1";
    $resConv = mysqli_query($conn, $sqlConv);
    $rowConv = mysqli_fetch_assoc($resConv);
    $conversationId = (int) ($rowConv['id'] ?? 0);
}

// If the user has no conversations yet, create a default one (Ahmed bot).
if ($conversationId <= 0) {
    $resBot = mysqli_query($conn, "SELECT id FROM bots WHERE slug = 'ahmed' LIMIT 1");
    $botRow = mysqli_fetch_assoc($resBot);
    $botId = (int) ($botRow['id'] ?? 0);
    if ($botId <= 0) {
        json_response(['error' => 'Default bot not found. Run migrations.'], 500);
    }

    $sqlCreate = "INSERT INTO conversations (title, created_by_user_id, bot_id, created_at)
                  VALUES ('Chat with Ahmed', $userId, $botId, NOW())";
    mysqli_query($conn, $sqlCreate);
    $conversationId = (int) mysqli_insert_id($conn);

    mysqli_query($conn, "INSERT IGNORE INTO conversation_members (conversation_id, user_id, created_at)
                         VALUES ($conversationId, $userId, NOW())");
}

// Authorization: user must be a member of this conversation.
$sqlAuth = "SELECT 1 FROM conversation_members
            WHERE conversation_id = $conversationId AND user_id = $userId
            LIMIT 1";
$resAuth = mysqli_query($conn, $sqlAuth);
if (!mysqli_fetch_assoc($resAuth)) {
    json_response(['error' => 'Forbidden'], 403);
}

$nameEsc = mysqli_real_escape_string($conn, $name);
$messageEsc = mysqli_real_escape_string($conn, $message);

$sql = "INSERT INTO messages (conversation_id, user_id, name, message, created_at)
        VALUES ($conversationId, $userId, '$nameEsc', '$messageEsc', NOW())";

if (!mysqli_query($conn, $sql)) {
    json_response(
        ['error' => 'Insert failed'],
        500
    );
}

// Bot auto-reply for this conversation
$sqlBot = "SELECT b.name, b.reply_template
           FROM conversations c
           JOIN bots b ON b.id = c.bot_id
           WHERE c.id = $conversationId
           LIMIT 1";
$resBot = mysqli_query($conn, $sqlBot);
$bot = mysqli_fetch_assoc($resBot);
if (!$bot) {
    json_response(['error' => 'Bot not found for this conversation'], 500);
}

$botNameEsc = mysqli_real_escape_string($conn, $bot['name']);
$botReplyEsc = mysqli_real_escape_string($conn, $bot['reply_template']);

$sqlReply = "INSERT INTO messages (conversation_id, user_id, name, message, created_at)
             VALUES ($conversationId, NULL, '$botNameEsc', '$botReplyEsc', NOW())";

mysqli_query($conn, $sqlReply);

json_response(['success' => true, 'conversation_id' => $conversationId]);
