<?php
require_once __DIR__ . '/../app/config/database.php';
$config = require __DIR__ . '/../app/config/database.php';

echo "Connecting...\n";
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

echo "Connected. Attempting ALTER COLUMN...\n";

$sql = "ALTER TABLE script_request ALTER COLUMN ticket_id VARCHAR(50)";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    echo "ALTER SUCCESS!\n";
} else {
    echo "ALTER FAILED!\n";
    print_r(sqlsrv_errors());
}

// Check verification
echo "\nVerifying Schema:\n";
$sqlCheck = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
$stmtCheck = sqlsrv_query($conn, $sqlCheck);
if ($row = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
    echo "Current Type: " . $row['DATA_TYPE'] . "\n";
}

?>
