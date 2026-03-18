<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json');

$user = getCurrentUser();

if ($user) {
    json_response([
        'loggedIn' => true,
        'name'     => $user['name'],
        'email'    => $user['email'],
    ]);
}

json_response([
    'loggedIn' => false,
    'name'     => null,
]);
