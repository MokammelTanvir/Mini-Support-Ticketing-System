<?php
// AuthController

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // Login user
    public function login()
    {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (!isset($data['email']) || !isset($data['password'])) {
            Response::error('Email and password are required', 400);
        }

        // Find user by email
        $user = $this->userModel->findByEmail($data['email']);

        // Check if user exists and password is correct
        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            Response::error('Invalid email or password', 401);
        }

        // Remove password hash from response
        unset($user['password_hash']);

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        // Return user data
        Response::json([
            'message' => 'Login successful',
            'user' => $user
        ]);
    }

    // Logout user
    public function logout()
    {
        // Check if user is logged in
        if (!Auth::isLoggedIn()) {
            Response::error('Not logged in', 401);
        }

        // Destroy session
        session_destroy();

        // Return success message
        Response::json([
            'message' => 'Logout successful'
        ]);
    }

    // Get user profile
    public function profile()
    {
        // Check if user is logged in
        if (!Auth::isLoggedIn()) {
            Response::error('Not logged in', 401);
        }

        // Get user data
        $user = $this->userModel->findById($_SESSION['user_id']);

        // Remove password hash from response
        unset($user['password_hash']);

        // Return user data
        Response::json([
            'user' => $user
        ]);
    }

    // Register new user
    public function register()
    {
        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            Response::error('Name, email and password are required', 400);
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 400);
        }

        // Validate password length
        if (strlen($data['password']) < 6) {
            Response::error('Password must be at least 6 characters long', 400);
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            Response::error('Email already registered', 409);
        }

        // Set default role if not provided
        $role = isset($data['role']) ? $data['role'] : 'agent';

        // Validate role
        if (!in_array($role, ['admin', 'agent'])) {
            Response::error('Invalid role. Must be admin or agent', 400);
        }

        // Create user data
        $userData = [
            'name' => trim($data['name']),
            'email' => trim(strtolower($data['email'])),
            'password' => $data['password'],
            'role' => $role
        ];

        // Create user
        if ($this->userModel->create($userData)) {
            // Get the created user (without password)
            $user = $this->userModel->findByEmail($userData['email']);
            unset($user['password_hash']);

            Response::created('User registered successfully', $user);
        } else {
            Response::error('Failed to create user', 500);
        }
    }
}
