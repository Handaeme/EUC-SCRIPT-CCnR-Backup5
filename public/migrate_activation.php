<?php
// Migration script accessible via browser
define('BASE_PATH', __DIR__ . '/..');
require_once BASE_PATH . '/app/helpers/DbAdapter.php';
require_once BASE_PATH . '/app/helpers/EnvLoader.php';

// Load Environment Variables
App\Helpers\EnvLoader::load(BASE_PATH . '/.env');

$config = require BASE_PATH . '/config/database.php';

echo "<h1>Script Activation Migration</h1>";

// Connection
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("<h2 style='color:red;'>Connection Failed</h2><pre>" . print_r(db_errors(), true) . "</pre>");
}

echo "<p>Connected to database...</p>";

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = db_query($conn, $sql, [$table, $column]);
    return db_has_rows($stmt);
}

// 1. Add is_active
if (!columnExists($conn, 'script_library', 'is_active')) {
    echo "Adding 'is_active' column... ";
    $sql = "ALTER TABLE script_library ADD is_active TINYINT DEFAULT 0";
    if (db_query($conn, $sql)) {
        echo "<span style='color:green;'>SUCCESS</span><br>";
        // Update existing rows to ACTIVE (1) so current library doesn't vanish
        db_query($conn, "UPDATE script_library SET is_active = 1 WHERE is_active IS NULL OR is_active = 0");
        echo "Updated existing scripts to Active.<br>";
    } else {
        echo "<span style='color:red;'>FAILED</span>: " . print_r(db_errors(), true) . "<br>";
    }
} else {
    echo "Column 'is_active' already exists.<br>";
}

// 2. Add activated_at
if (!columnExists($conn, 'script_library', 'activated_at')) {
    echo "Adding 'activated_at' column... ";
    $sql = "ALTER TABLE script_library ADD activated_at DATETIME NULL";
    if (db_query($conn, $sql)) {
        echo "<span style='color:green;'>SUCCESS</span><br>";
        // Backfill date for existing active scripts
        db_query($conn, "UPDATE script_library SET activated_at = created_at WHERE is_active = 1 AND activated_at IS NULL");
        echo "Backfilled activation dates.<br>";
    } else {
        echo "<span style='color:red;'>FAILED</span>: " . print_r(db_errors(), true) . "<br>";
    }
} else {
    echo "Column 'activated_at' already exists.<br>";
}

// 3. Add activated_by
if (!columnExists($conn, 'script_library', 'activated_by')) {
    echo "Adding 'activated_by' column... ";
    $sql = "ALTER TABLE script_library ADD activated_by VARCHAR(50) NULL";
    if (db_query($conn, $sql)) {
        echo "<span style='color:green;'>SUCCESS</span><br>";
        // Backfill with 'System Migration' or similar
        db_query($conn, "UPDATE script_library SET activated_by = 'System Migration' WHERE is_active = 1 AND activated_by IS NULL");
        echo "Backfilled activation user.<br>";
    } else {
        echo "<span style='color:red;'>FAILED</span>: " . print_r(db_errors(), true) . "<br>";
    }
} else {
    echo "Column 'activated_by' already exists.<br>";
}

echo "<h2>Migration Complete.</h2>";
echo "<p><a href='check_db_status.php'>Check Database Status Again</a></p>";
