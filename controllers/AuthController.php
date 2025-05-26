<?php
// AuthController

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // Login user (token-based)
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

        // Generate token
        $token = Auth::generateToken();

        // Save token
        Auth::saveToken($user['id'], $token);

        // Remove password hash from response
        unset($user['password_hash']);

        // Return user data with token
        Response::json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    // Logout user (token-based)
    public function logout()
    {
        // Get token from request
        $token = Auth::getTokenFromRequest();

        if (!$token) {
            Response::error('No token provided', 401);
        }

        // Validate token
        if (!Auth::validateToken($token)) {
            Response::error('Invalid or expired token', 401);
        }

        // Delete token
        Auth::deleteToken($token);

        // Return success message
        Response::json([
            'message' => 'Logout successful'
        ]);
    }

    // Get user profile (token-based)
    public function profile()
    {
        // Check authentication
        Auth::requireAuth();

        // Get user data
        $user = Auth::getUser();

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
