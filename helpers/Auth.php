<?php
// Authentication Helper Class

class Auth
{

    private static $tokens_file = __DIR__ . '/../database/tokens.json';

    // Generate a random token
    public static function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    // Save token to file store
    public static function saveToken($user_id, $token)
    {
        $tokens = self::getTokens();
        $tokens[$token] = [
            'user_id' => $user_id,
            'created_at' => time(),
            'expires_at' => time() + (24 * 60 * 60) // 24 hours
        ];
        file_put_contents(self::$tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    // Get all tokens from file
    private static function getTokens()
    {
        if (!file_exists(self::$tokens_file)) {
            return [];
        }
        return json_decode(file_get_contents(self::$tokens_file), true) ?: [];
    }

    // Validate token and return user_id
    public static function validateToken($token)
    {
        $tokens = self::getTokens();

        if (!isset($tokens[$token])) {
            return false;
        }

        // Check if token is expired
        if ($tokens[$token]['expires_at'] < time()) {
            self::deleteToken($token);
            return false;
        }

        return $tokens[$token]['user_id'];
    }

    // Delete token (logout)
    public static function deleteToken($token)
    {
        $tokens = self::getTokens();
        unset($tokens[$token]);
        file_put_contents(self::$tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    // Get token from request headers
    public static function getTokenFromRequest()
    {
        $headers = getallheaders();

        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    // Check if user is authenticated
    public static function check()
    {
        $token = self::getTokenFromRequest();
        if (!$token) {
            return false;
        }

        return self::validateToken($token);
    }

    // Check if user is logged in
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Get current user ID
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    // Get current user role
    public static function getUserRole()
    {
        return $_SESSION['user_role'] ?? null;
    }

    // Check if user is admin
    public static function isAdmin()
    {
        return self::getUserRole() === 'admin';
    }

    // Check if user is agent
    public static function isAgent()
    {
        return self::getUserRole() === 'agent';
    }

    // Require authentication
    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            Response::error('Authentication required', 401);
        }
    }

    // Require admin role
    public static function requireAdmin()
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            Response::error('Admin access required', 403);
        }
    }

    // Require agent role
    public static function requireAgent()
    {
        self::requireAuth();
        if (!self::isAgent()) {
            Response::error('Agent access required', 403);
        }
    }
}
