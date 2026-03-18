<?php
require_once __DIR__ . '/bootstrap.php';

$_SESSION = [];
session_destroy();

header('Location: ../front-end/html/login.html');
exit;
