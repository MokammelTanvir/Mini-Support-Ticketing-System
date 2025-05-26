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
            'GET /auth/profile' => 'Get user profile',
            'GET /users' => 'Get all users (admin only)',
            'GET /departments' => 'Get all departments (authenticated)',
            'POST /departments' => 'Create department (admin only)',
            'GET /tickets' => 'Get tickets (role-based access)',
            'POST /tickets' => 'Create new ticket (authenticated)',
            'PUT /tickets/{id}' => 'Update ticket (role-based access)',
            'DELETE /tickets/{id}' => 'Delete ticket (admin only)',
            'POST /tickets/{id}/assign' => 'Assign ticket to agent (admin/agent)',
            'PUT /tickets/{id}/status' => 'Change ticket status (admin/agent)',
            'GET /tickets/stats/summary' => 'Get ticket statistics (admin/agent)',
            'GET /tickets/assigned/me' => 'Get my assigned tickets (agent)',
            'GET /tickets/{id}/notes' => 'Get ticket notes (role-based access)',
            'POST /tickets/{id}/notes' => 'Add note to ticket (authenticated)',
            'PUT /tickets/{ticketId}/notes/{noteId}' => 'Update ticket note (note creator/admin)',
            'DELETE /tickets/{ticketId}/notes/{noteId}' => 'Delete ticket note (note creator/admin)',
            'POST /tickets/{id}/attachments' => 'Upload files to ticket (multipart/form-data)',
            'GET /tickets/{id}/attachments' => 'Get ticket attachments (role-based access)',
            'GET /tickets/{id}/attachments/{attachmentId}/download' => 'Download attachment file',
            'DELETE /tickets/{id}/attachments/{attachmentId}' => 'Delete attachment (uploader/owner/admin)',
            'GET /admin/storage/stats' => 'Get file storage statistics (admin only)'
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
    $controller = new UserController();
    $controller->index();
});

route('GET', 'users/{id}', function ($id) {
    $controller = new UserController();
    $controller->show($id);
});

route('POST', 'users', function () {
    $controller = new UserController();
    $controller->store();
});

route('PUT', 'users/{id}', function ($id) {
    $controller = new UserController();
    $controller->update($id);
});

route('DELETE', 'users/{id}', function ($id) {
    $controller = new UserController();
    $controller->destroy($id);
});

route('GET', 'users/role/{role}', function ($role) {
    $controller = new UserController();
    $controller->getByRole($role);
});

// Department routes (authenticated users can view, admin only for CUD operations)
route('GET', 'departments', function () {
    $controller = new DepartmentController();
    $controller->index();
});

route('GET', 'departments/{id}', function ($id) {
    $controller = new DepartmentController();
    $controller->show($id);
});

route('POST', 'departments', function () {
    $controller = new DepartmentController();
    $controller->store();
});

route('PUT', 'departments/{id}', function ($id) {
    $controller = new DepartmentController();
    $controller->update($id);
});

route('DELETE', 'departments/{id}', function ($id) {
    $controller = new DepartmentController();
    $controller->destroy($id);
});

route('GET', 'departments/stats/ticket-count', function () {
    $controller = new DepartmentController();
    $controller->getWithTicketCount();
});

// Ticket routes
route('GET', 'tickets', function () {
    $controller = new TicketController();
    $controller->index();
});

route('GET', 'tickets/{id}', function ($id) {
    $controller = new TicketController();
    $controller->show($id);
});

route('POST', 'tickets', function () {
    $controller = new TicketController();
    $controller->store();
});

route('PUT', 'tickets/{id}', function ($id) {
    $controller = new TicketController();
    $controller->update($id);
});

route('DELETE', 'tickets/{id}', function ($id) {
    $controller = new TicketController();
    $controller->destroy($id);
});

route('POST', 'tickets/{id}/assign', function ($id) {
    $controller = new TicketController();
    $controller->assign($id);
});

route('PUT', 'tickets/{id}/status', function ($id) {
    $controller = new TicketController();
    $controller->changeStatus($id);
});

route('GET', 'tickets/stats/summary', function () {
    $controller = new TicketController();
    $controller->getStats();
});

route('GET', 'tickets/assigned/me', function () {
    $controller = new TicketController();
    $controller->getMyAssigned();
});

// Ticket Notes routes
route('GET', 'tickets/{ticketId}/notes', function ($ticketId) {
    $controller = new TicketNoteController();
    $controller->index($ticketId);
});

route('GET', 'tickets/{ticketId}/notes/{noteId}', function ($ticketId, $noteId) {
    $controller = new TicketNoteController();
    $controller->show($ticketId, $noteId);
});

route('POST', 'tickets/{ticketId}/notes', function ($ticketId) {
    $controller = new TicketNoteController();
    $controller->store($ticketId);
});

route('PUT', 'tickets/{ticketId}/notes/{noteId}', function ($ticketId, $noteId) {
    $controller = new TicketNoteController();
    $controller->update($ticketId, $noteId);
});

route('DELETE', 'tickets/{ticketId}/notes/{noteId}', function ($ticketId, $noteId) {
    $controller = new TicketNoteController();
    $controller->destroy($ticketId, $noteId);
});

route('GET', 'notes/user/{userId}', function ($userId) {
    $controller = new TicketNoteController();
    $controller->getByUser($userId);
});

// File Upload/Attachment routes
route('POST', 'tickets/{ticketId}/attachments', function ($ticketId) {
    $controller = new AttachmentController();
    $controller->upload($ticketId);
});

route('GET', 'tickets/{ticketId}/attachments', function ($ticketId) {
    $controller = new AttachmentController();
    $controller->index($ticketId);
});

route('GET', 'tickets/{ticketId}/attachments/{attachmentId}/download', function ($ticketId, $attachmentId) {
    $controller = new AttachmentController();
    $controller->download($ticketId, $attachmentId);
});

route('DELETE', 'tickets/{ticketId}/attachments/{attachmentId}', function ($ticketId, $attachmentId) {
    $controller = new AttachmentController();
    $controller->destroy($ticketId, $attachmentId);
});

route('GET', 'admin/storage/stats', function () {
    $controller = new AttachmentController();
    $controller->getStorageStats();
});
