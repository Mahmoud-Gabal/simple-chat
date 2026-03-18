<?php
/**
 * Application bootstrap for the back-end layer.
 *
 * - Loads configuration and database connection
 * - Starts / shares the session & auth helpers
 * - Provides a small helper for JSON API responses
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Send a JSON response and terminate the request.
 */
function json_response(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

