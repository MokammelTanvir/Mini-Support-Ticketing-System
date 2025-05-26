<?php
// RateLimit - Simple rate limiting helper

class RateLimit
{
    private static $rateLimitFile = __DIR__ . '/../database/rate_limits.json';

    /**
     * Check if user has exceeded rate limit
     * 
     * @param string $identifier - User ID, IP address, or other identifier
     * @param string $action - Action type (e.g., 'ticket_creation', 'login_attempt')
     * @param int $maxAttempts - Maximum attempts allowed
     * @param int $timeWindow - Time window in seconds
     * @return bool - True if rate limit exceeded, false if allowed
     */
    public static function isExceeded($identifier, $action = 'default', $maxAttempts = 5, $timeWindow = 3600)
    {
        $rateLimits = self::loadRateLimits();
        $key = $action . '_' . $identifier;
        $currentTime = time();

        // Clean up expired entries first
        self::cleanupExpiredEntries($rateLimits, $currentTime);

        // Check if identifier exists
        if (!isset($rateLimits[$key])) {
            return false; // No previous attempts, allow
        }

        $userLimits = $rateLimits[$key];

        // Filter attempts within the time window
        $recentAttempts = array_filter($userLimits, function ($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) <= $timeWindow;
        });

        return count($recentAttempts) >= $maxAttempts;
    }

    /**
     * Record an attempt
     * 
     * @param string $identifier - User ID, IP address, or other identifier
     * @param string $action - Action type
     */
    public static function recordAttempt($identifier, $action = 'default')
    {
        $rateLimits = self::loadRateLimits();
        $key = $action . '_' . $identifier;
        $currentTime = time();

        // Initialize if doesn't exist
        if (!isset($rateLimits[$key])) {
            $rateLimits[$key] = [];
        }

        // Add current timestamp
        $rateLimits[$key][] = $currentTime;

        // Keep only last 100 attempts to prevent file from growing too large
        if (count($rateLimits[$key]) > 100) {
            $rateLimits[$key] = array_slice($rateLimits[$key], -100);
        }

        // Save back to file
        self::saveRateLimits($rateLimits);
    }

    /**
     * Get remaining attempts for user
     * 
     * @param string $identifier - User ID, IP address, or other identifier
     * @param string $action - Action type
     * @param int $maxAttempts - Maximum attempts allowed
     * @param int $timeWindow - Time window in seconds
     * @return array - ['remaining' => int, 'reset_time' => timestamp]
     */
    public static function getRemainingAttempts($identifier, $action = 'default', $maxAttempts = 5, $timeWindow = 3600)
    {
        $rateLimits = self::loadRateLimits();
        $key = $action . '_' . $identifier;
        $currentTime = time();

        if (!isset($rateLimits[$key])) {
            return [
                'remaining' => $maxAttempts,
                'reset_time' => null
            ];
        }

        $userLimits = $rateLimits[$key];

        // Filter attempts within the time window
        $recentAttempts = array_filter($userLimits, function ($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) <= $timeWindow;
        });

        $attemptCount = count($recentAttempts);
        $remaining = max(0, $maxAttempts - $attemptCount);

        // Calculate reset time (when oldest attempt expires)
        $resetTime = null;
        if ($attemptCount > 0) {
            $oldestAttempt = min($recentAttempts);
            $resetTime = $oldestAttempt + $timeWindow;
        }

        return [
            'remaining' => $remaining,
            'reset_time' => $resetTime
        ];
    }

    /**
     * Clear rate limits for a specific identifier and action
     */
    public static function clearLimits($identifier, $action = 'default')
    {
        $rateLimits = self::loadRateLimits();
        $key = $action . '_' . $identifier;

        unset($rateLimits[$key]);
        self::saveRateLimits($rateLimits);
    }

    /**
     * Load rate limits from file
     */
    private static function loadRateLimits()
    {
        if (!file_exists(self::$rateLimitFile)) {
            return [];
        }

        $content = file_get_contents(self::$rateLimitFile);
        $data = json_decode($content, true);

        return $data ?: [];
    }

    /**
     * Save rate limits to file
     */
    private static function saveRateLimits($rateLimits)
    {
        $json = json_encode($rateLimits, JSON_PRETTY_PRINT);
        file_put_contents(self::$rateLimitFile, $json);
    }

    /**
     * Clean up expired entries to keep file size manageable
     */
    private static function cleanupExpiredEntries($rateLimits, $currentTime, $maxAge = 86400)
    {
        $cleaned = false;

        foreach ($rateLimits as $key => $attempts) {
            // Remove attempts older than maxAge (24 hours by default)
            $validAttempts = array_filter($attempts, function ($timestamp) use ($currentTime, $maxAge) {
                return ($currentTime - $timestamp) <= $maxAge;
            });

            if (count($validAttempts) !== count($attempts)) {
                $rateLimits[$key] = array_values($validAttempts);
                $cleaned = true;
            }

            // Remove empty entries
            if (empty($rateLimits[$key])) {
                unset($rateLimits[$key]);
                $cleaned = true;
            }
        }

        // Save if we cleaned anything
        if ($cleaned) {
            self::saveRateLimits($rateLimits);
        }
    }

    /**
     * Get client IP address (considering proxies)
     */
    public static function getClientIP()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Middleware to check rate limits and respond with headers
     */
    public static function checkLimit($identifier, $action, $maxAttempts, $timeWindow, $recordAttempt = true)
    {
        // Check if rate limit exceeded
        if (self::isExceeded($identifier, $action, $maxAttempts, $timeWindow)) {
            $remaining = self::getRemainingAttempts($identifier, $action, $maxAttempts, $timeWindow);

            // Set rate limit headers
            header("X-RateLimit-Limit: $maxAttempts");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: " . ($remaining['reset_time'] ?: time() + $timeWindow));

            Response::error('Rate limit exceeded. Too many requests.', 429);
        }

        // Record this attempt
        if ($recordAttempt) {
            self::recordAttempt($identifier, $action);
        }

        // Set rate limit headers for successful requests
        $remaining = self::getRemainingAttempts($identifier, $action, $maxAttempts, $timeWindow);
        header("X-RateLimit-Limit: $maxAttempts");
        header("X-RateLimit-Remaining: " . ($remaining['remaining'] - 1)); // -1 because we just recorded
        if ($remaining['reset_time']) {
            header("X-RateLimit-Reset: " . $remaining['reset_time']);
        }
    }
}
