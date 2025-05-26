<?php
// API Routes

// Simple routing function
function route($method, $pattern, $callback)
{
    global $uri;
    $request_method = $_SERVER['REQUEST_METHOD'];

    if ($request_method !== $method) {
        return false;
    }

    // Convert pattern to regex
    $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
    $pattern = '#^' . $pattern . '$#';

    if (preg_match($pattern, $uri, $matches)) {
        array_shift($matches); // Remove full match
        call_user_func_array($callback, $matches);
        return true;
    }

    return false;
}

// Test route
route('GET', '', function () {
    Response::success('Mini Support Ticketing System API', [
        'version' => '1.0.0',
        'endpoints' => [
            'POST /auth/register' => 'User registration',
            'POST /auth/login' => 'User login',
            'POST /auth/logout' => 'User logout',
            'GET /users' => 'Get all users (admin only)',
            'GET /departments' => 'Get all departments',
            'POST /departments' => 'Create department (admin only)',
            'GET /tickets' => 'Get tickets',
            'POST /tickets' => 'Create ticket',
            'PUT /tickets/{id}' => 'Update ticket'
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

// Auth routes (will implement these next)
route('POST', 'auth/register', function () {
    Response::error('Not implemented yet', 501);
});

route('POST', 'auth/login', function () {
    Response::error('Not implemented yet', 501);
});

route('POST', 'auth/logout', function () {
    Response::error('Not implemented yet', 501);
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
