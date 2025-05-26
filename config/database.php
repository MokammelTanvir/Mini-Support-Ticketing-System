<?php
// Database Configuration

class Database
{
    private static $instance = null;
    private $connection;

    // Database configuration
    private $host = 'localhost';
    private $db_name = 'ticketing_system';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';

    // For SQLite (easier for development)
    private $sqlite_path = __DIR__ . '/../database/ticketing_system.db';

    // Choose database type: 'mysql' or 'sqlite'
    private $db_type = 'sqlite';

    private function __construct()
    {
        try {
            if ($this->db_type === 'sqlite') {
                // Create database directory if it doesn't exist
                $db_dir = dirname($this->sqlite_path);
                if (!is_dir($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }

                $this->connection = new PDO("sqlite:" . $this->sqlite_path);
                $this->connection->exec("PRAGMA foreign_keys = ON");
            } else {
                // MySQL connection
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
                $this->connection = new PDO($dsn, $this->username, $this->password);
            }

            // Set PDO options
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserializing
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }
}

// Create database instance
$db = Database::getInstance()->getConnection();
