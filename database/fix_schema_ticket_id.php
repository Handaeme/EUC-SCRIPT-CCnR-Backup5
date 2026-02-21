<?php
// Fix Database Schema: script_request.ticket_id -> VARCHAR(50)
require_once __DIR__ . '/../app/config/database.php';

$config = require __DIR__ . '/../app/config/database.php';
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Connected to SQL Server.\n";

// 1. Check current column type (optional, but good for debug)
echo "Attempting to alter table schema...\n";

// We need to drop constraints first if any (like defaults), but usually ticket_id standard column might not have unique constraint unless PK.
// Assuming it's NOT PK (ID is PK).

$sql = "ALTER TABLE script_request ALTER COLUMN ticket_id VARCHAR(50)";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    echo "SUCCESS: ticket_id column altered to VARCHAR(50).\n";
} else {
    echo "ERROR: Failed to alter column.\n";
    print_r(sqlsrv_errors());
}

// 2. Also check if we need to update existing rows that might be converted to '0' or something?
// Ideally we keep them as is.

sqlsrv_close($conn);
?>
