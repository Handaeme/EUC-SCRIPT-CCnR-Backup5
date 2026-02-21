<?php
// Fix Paths
$dbAdapter = __DIR__ . '/../app/helpers/DbAdapter.php';
$envLoader = __DIR__ . '/../app/helpers/EnvLoader.php';
$configFile = __DIR__ . '/../config/database.php';

// Load Env
if (file_exists($envLoader)) {
    require_once $envLoader;
    if (class_exists('App\Helpers\EnvLoader')) {
        App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
    }
}

// Load DB Adapter
if (file_exists($dbAdapter)) {
    require_once $dbAdapter;
} else {
    die("Error: DbAdapter not found.");
}

// Load Config
if (!file_exists($configFile)) die("Error: config/database.php not found.");
$config = require $configFile;

// Connect
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Database Connection Failed: " . print_r(db_errors(), true));
}

function addColumnIfNotExists($conn, $table, $column, $type) {
    // Check if column exists
    $checkSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ?";
    $stmt = db_query($conn, $checkSql, [$table, $column]);
    
    if ($stmt && db_has_rows($stmt)) {
        echo "Column [$column] already exists in table [$table]. Skipping...\n<br>";
    } else {
        $sql = "ALTER TABLE $table ADD $column $type";
        $stmt = db_query($conn, $sql);
        if ($stmt) {
            echo "SUCCESS: Added column [$column] to table [$table].\n<br>";
        } else {
            echo "ERROR: Failed to add column [$column] to table [$table].\n<br>";
            print_r(db_errors());
        }
    }
}

echo "Starting Migration...<br>\n";

// 1. script_request
addColumnIfNotExists($conn, 'script_request', 'start_date', 'DATE NULL');

// 2. script_library
addColumnIfNotExists($conn, 'script_library', 'start_date', 'DATE NULL');

echo "Migration Completed.<br>\n";
?>
