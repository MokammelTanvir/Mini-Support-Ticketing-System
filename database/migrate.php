<?php
// Database Migration Script

require_once __DIR__ . '/../config/database.php';

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();

    // Read schema file
    $schema = file_get_contents(__DIR__ . '/schema.sql');

    if ($schema === false) {
        throw new Exception("Could not read schema.sql file");
    }

    // Execute schema directly
    $db->exec($schema);

    echo "Starting database migration...\n";
    echo "✓ Schema executed successfully!\n";

    echo "\nMigration completed successfully!\n";
    echo "Tables created:\n";
    echo "- users\n";
    echo "- departments\n";
    echo "- tickets\n";
    echo "- ticket_notes\n";
    echo "- ticket_attachments\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
