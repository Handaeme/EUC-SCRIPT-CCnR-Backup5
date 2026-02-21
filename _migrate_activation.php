<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/helpers/DbAdapter.php';

$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

echo "Starting Script Activation Migration...\n";

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = db_query($conn, $sql, [$table, $column]);
    return db_has_rows($stmt);
}

// 1. Add is_active
if (!columnExists($conn, 'script_library', 'is_active')) {
    echo "Adding 'is_active' column...\n";
    $sql = "ALTER TABLE script_library ADD is_active TINYINT DEFAULT 0";
    if (db_query($conn, $sql)) {
        echo "SUCCESS: is_active added.\n";
        // Update existing rows to ACTIVE (1) so current library doesn't vanish
        db_query($conn, "UPDATE script_library SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
        echo "UPDATED: All existing scripts set to Active.\n";
    } else {
        echo "FAILED: " . print_r(db_errors(), true) . "\n";
    }
} else {
    echo "SKIPPED: 'is_active' already exists.\n";
}

// 2. Add activated_at
if (!columnExists($conn, 'script_library', 'activated_at')) {
    echo "Adding 'activated_at' column...\n";
    $sql = "ALTER TABLE script_library ADD activated_at DATETIME NULL";
    if (db_query($conn, $sql)) {
        echo "SUCCESS: activated_at added.\n";
        // Backfill date for existing active scripts
        db_query($conn, "UPDATE script_library SET activated_at = created_at WHERE is_active = 1 AND activated_at IS NULL");
        echo "UPDATED: Backfilled activation dates.\n";
    } else {
        echo "FAILED: " . print_r(db_errors(), true) . "\n";
    }
} else {
    echo "SKIPPED: 'activated_at' already exists.\n";
}

// 3. Add activated_by
if (!columnExists($conn, 'script_library', 'activated_by')) {
    echo "Adding 'activated_by' column...\n";
    $sql = "ALTER TABLE script_library ADD activated_by VARCHAR(50) NULL";
    if (db_query($conn, $sql)) {
        echo "SUCCESS: activated_by added.\n";
        // Backfill with 'System Migration' or similar
        db_query($conn, "UPDATE script_library SET activated_by = 'System Migration' WHERE is_active = 1 AND activated_by IS NULL");
        echo "UPDATED: Backfilled activation user.\n";
    } else {
        echo "FAILED: " . print_r(db_errors(), true) . "\n";
    }
} else {
    echo "SKIPPED: 'activated_by' already exists.\n";
}

echo "Migration Complete.\n";
