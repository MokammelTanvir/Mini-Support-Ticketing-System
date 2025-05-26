<?php
// UserController - User Management API

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // Get all users (admin only)
    public function index()
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Get all users
        $users = $this->userModel->getAll();

        Response::success('Users retrieved successfully', $users);
    }

    // Get user by ID (admin only or own profile)
    public function show($id)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Admin can view any user, others can only view their own profile
        if ($current_user_role !== 'admin' && $current_user_id != $id) {
            Response::forbidden('You can only view your own profile');
        }

        // Find user
        $user = $this->userModel->findById($id);

        if (!$user) {
            Response::notFound('User not found');
        }

        // Remove password hash
        unset($user['password_hash']);

        Response::success('User retrieved successfully', $user);
    }

    // Create new user (admin only)
    public function store()
    {
        // Require admin authentication
        Auth::requireAdmin();

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

            Response::created('User created successfully', $user);
        } else {
            Response::error('Failed to create user', 500);
        }
    }

    // Update user (admin only or own profile)
    public function update($id)
    {
        // Require authentication
        Auth::requireAuth();

        $current_user_id = Auth::getUserId();
        $current_user_role = Auth::getUserRole();

        // Admin can update any user, others can only update their own profile
        if ($current_user_role !== 'admin' && $current_user_id != $id) {
            Response::forbidden('You can only update your own profile');
        }

        // Check if user exists
        $user = $this->userModel->findById($id);
        if (!$user) {
            Response::notFound('User not found');
        }

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        $updateData = [];

        // Validate and set name if provided
        if (isset($data['name'])) {
            if (empty(trim($data['name']))) {
                Response::error('Name cannot be empty', 400);
            }
            $updateData['name'] = trim($data['name']);
        }

        // Validate and set email if provided
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                Response::error('Invalid email format', 400);
            }

            // Check if email already exists (exclude current user)
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $id) {
                Response::error('Email already exists', 409);
            }

            $updateData['email'] = trim(strtolower($data['email']));
        }

        // Validate and set password if provided
        if (isset($data['password'])) {
            if (strlen($data['password']) < 6) {
                Response::error('Password must be at least 6 characters long', 400);
            }
            $updateData['password'] = $data['password'];
        }

        // Only admin can change role
        if (isset($data['role'])) {
            if ($current_user_role !== 'admin') {
                Response::forbidden('Only admin can change user roles');
            }

            if (!in_array($data['role'], ['admin', 'agent'])) {
                Response::error('Invalid role. Must be admin or agent', 400);
            }

            $updateData['role'] = $data['role'];
        }

        // If no data to update
        if (empty($updateData)) {
            Response::error('No valid fields to update', 400);
        }

        // Update user
        if ($this->userModel->update($id, $updateData)) {
            // Get updated user data
            $updatedUser = $this->userModel->findById($id);
            unset($updatedUser['password_hash']);

            Response::success('User updated successfully', $updatedUser);
        } else {
            Response::error('Failed to update user', 500);
        }
    }

    // Delete user (admin only)
    public function destroy($id)
    {
        // Require admin authentication
        Auth::requireAdmin();

        $current_user_id = Auth::getUserId();

        // Prevent self deletion
        if ($current_user_id == $id) {
            Response::error('You cannot delete your own account', 400);
        }

        // Check if user exists
        $user = $this->userModel->findById($id);
        if (!$user) {
            Response::notFound('User not found');
        }

        // Delete user
        if ($this->userModel->delete($id)) {
            Response::success('User deleted successfully');
        } else {
            Response::error('Failed to delete user', 500);
        }
    }

    // Get users by role (admin only)
    public function getByRole($role)
    {
        // Require admin authentication
        Auth::requireAdmin();

        // Validate role
        if (!in_array($role, ['admin', 'agent'])) {
            Response::error('Invalid role. Must be admin or agent', 400);
        }

        // Get users by role
        $users = $this->userModel->getByRole($role);

        Response::success("Users with role '$role' retrieved successfully", $users);
    }
}
