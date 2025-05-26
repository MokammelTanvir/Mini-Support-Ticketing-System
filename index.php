<?php
// Front Controller - All requests go through this file

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS (if needed)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Auth.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Department.php';
require_once __DIR__ . '/models/Ticket.php';
require_once __DIR__ . '/models/TicketNote.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Debug request information
error_log("=== Request Debug ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Raw input: " . file_get_contents('php://input'));

// Get request URI
$uri = $_SERVER['REQUEST_URI'];
error_log("Original URI: " . $uri);

// Remove query string
if (($pos = strpos($uri, '?')) !== false) {
    $uri = substr($uri, 0, $pos);
}

// Handle both cases: direct PHP server and with /api prefix
$base_path = '';
if (strpos($uri, '/api') === 0) {
    $base_path = '/api';
    $uri = substr($uri, strlen($base_path));
}

// Remove leading slash to normalize
$uri = ltrim($uri, '/');
// Remove trailing slash
$uri = rtrim($uri, '/');

error_log("URI after processing: '" . $uri . "'");
error_log("=== End Request Debug ===");

// Load routes
require_once __DIR__ . '/routes/api.php';

// Handle 404
Response::error('Route not found', 404);
