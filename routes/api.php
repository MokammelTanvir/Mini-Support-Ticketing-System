<?php
// API Routes

// Simple routing function
function route($method, $pattern, $callback)
{
    global $uri;
    $request_method = $_SERVER['REQUEST_METHOD'];

    error_log("=== Route Debug ===");
    error_log("Request Method: " . $request_method);
    error_log("Expected Method: " . $method);
    error_log("Current URI: " . $uri);
    error_log("Pattern to match: " . $pattern);

    if ($request_method !== $method) {
        error_log("Method mismatch: Expected $method, got $request_method");
        return false;
    }

    // Convert pattern to regex
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';
    error_log("Regex pattern: $pattern");

    if (preg_match($pattern, $uri, $matches)) {
        error_log("Route matched successfully!");
        array_shift($matches); // Remove full match
        call_user_func_array($callback, $matches);
        return true;
    }

    error_log("Route did not match");
    error_log("=== End Route Debug ===");
    return false;
}

// Test route
route('GET', '', function () {
    Response::success('Mini Support Ticketing System API', [
        'version' => '1.0.0',
        'endpoints' => [
            'POST /auth/login' => 'User login',
            'POST /auth/logout' => 'User logout',
            'GET /auth/profile' => 'Get user profile'
        ]
    ]);
});

// Test health check
route('GET', 'health', function () {
    Response::success('API is healthy', [
        'database' => 'connected',
        'server_time' => date('Y-m-d H:i:s')
    ]);
});

// Registration Route
route('POST', 'auth/register', function () {
    $controller = new AuthController();
    $controller->register();
});

// Authentication Routes
route('POST', 'auth/login', function () {
    error_log("Login route handler called");
    $controller = new AuthController();
    $controller->login();
});

route('POST', 'auth/logout', function () {
    $controller = new AuthController();
    $controller->logout();
});

route('GET', 'auth/profile', function () {
    $controller = new AuthController();
    $controller->profile();
});

// User routes
route('GET', 'users', function () {
    Response::error('Not implemented yet', 501);
});

// Department routes  
route('GET', 'departments', function () {
    Response::error('Not implemented yet', 501);
});

route('POST', 'departments', function () {
    Response::error('Not implemented yet', 501);
});

// Ticket routes
route('GET', 'tickets', function () {
    Response::error('Not implemented yet', 501);
});

route('POST', 'tickets', function () {
    Response::error('Not implemented yet', 501);
});

route('PUT', 'tickets/{id}', function ($id) {
    Response::error('Not implemented yet', 501);
});
