<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    json_response(['error' => 'You must be logged in'], 401);
}

$user = getCurrentUser();
$userId = (int) $user['id'];

// Ensure the fixed bots exist (idempotent).
$seedBots = [
    ['slug' => 'ahmed',  'name' => 'Ahmed',  'reply' => 'Hey! Thanks for your message. Talk to me more!'],
    ['slug' => 'islam',  'name' => 'Islam',  'reply' => 'Hey! I am Islam. Tell me more about your day!'],
    ['slug' => 'ferhat', 'name' => 'Ferhat', 'reply' => 'Hey! I am Ferhat. What are you working on today?'],
];

foreach ($seedBots as $b) {
    $slugEsc = mysqli_real_escape_string($conn, $b['slug']);
    $nameEsc = mysqli_real_escape_string($conn, $b['name']);
    $replyEsc = mysqli_real_escape_string($conn, $b['reply']);
    mysqli_query(
        $conn,
        "INSERT IGNORE INTO bots (slug, name, reply_template) VALUES ('$slugEsc', '$nameEsc', '$replyEsc')"
    );
}

// Fetch bot ids in the order we want to display them.
$botIds = [];
foreach (['ahmed', 'islam', 'ferhat'] as $slug) {
    $slugEsc = mysqli_real_escape_string($conn, $slug);
    $res = mysqli_query($conn, "SELECT id FROM bots WHERE slug = '$slugEsc' LIMIT 1");
    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        json_response(['error' => 'Bot not found: ' . $slug], 500);
    }
    $botIds[] = (int) $row['id'];
}

// Ensure the logged-in user has one conversation per bot.
foreach ($botIds as $botId) {
    $res = mysqli_query(
        $conn,
        "SELECT id FROM conversations WHERE created_by_user_id = $userId AND bot_id = $botId LIMIT 1"
    );
    $row = mysqli_fetch_assoc($res);
    if ($row) {
        $convId = (int) $row['id'];
    } else {
        $resBot = mysqli_query($conn, "SELECT name FROM bots WHERE id = $botId LIMIT 1");
        $bot = mysqli_fetch_assoc($resBot);
        $botNameEsc = mysqli_real_escape_string($conn, $bot['name'] ?? 'Bot');
        mysqli_query(
            $conn,
            "INSERT INTO conversations (title, created_by_user_id, bot_id, created_at)
             VALUES ('Chat with $botNameEsc', $userId, $botId, NOW())"
        );
        $convId = (int) mysqli_insert_id($conn);
    }

    mysqli_query(
        $conn,
        "INSERT IGNORE INTO conversation_members (conversation_id, user_id, created_at)
         VALUES ($convId, $userId, NOW())"
    );
}

// Return conversations for this user (exactly these bots, in order).
$sql = "SELECT
            c.id,
            c.title,
            c.created_at,
            b.slug AS bot_slug,
            b.name AS bot_name,
            (SELECT m.message
             FROM messages m
             WHERE m.conversation_id = c.id
             ORDER BY m.created_at DESC
             LIMIT 1) AS last_message,
            (SELECT m.created_at
             FROM messages m
             WHERE m.conversation_id = c.id
             ORDER BY m.created_at DESC
             LIMIT 1) AS last_message_at
        FROM conversations c
        JOIN conversation_members cm ON cm.conversation_id = c.id
        JOIN bots b ON b.id = c.bot_id
        WHERE cm.user_id = $userId
          AND b.slug IN ('ahmed', 'islam', 'ferhat')
        ORDER BY FIELD(b.slug, 'ahmed', 'islam', 'ferhat')";

$result = mysqli_query($conn, $sql);
$items = [];
while ($row = mysqli_fetch_assoc($result)) {
    $items[] = [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'bot' => [
            'slug' => $row['bot_slug'],
            'name' => $row['bot_name'],
        ],
        'created_at' => $row['created_at'],
        'last_message' => $row['last_message'],
        'last_message_at' => $row['last_message_at'],
    ];
}

json_response($items);

