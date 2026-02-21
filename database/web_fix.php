<?php
// Web-based Schema Fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/database.php';

echo "<pre>";
echo "Connecting to DB...\n";
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "Connected.\n";

// 1. Check Schema BEFORE
$sql = "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
$stmt = sqlsrv_query($conn, $sql);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Current Type: " . ($row ? $row['DATA_TYPE'] : 'Not Found') . "\n";

// 2. ALTER
$sqlAlter = "ALTER TABLE script_request ALTER COLUMN ticket_id VARCHAR(50)";
$stmtAlter = sqlsrv_query($conn, $sqlAlter);

if ($stmtAlter) {
    echo "ALTER SUCCESS!\n";
} else {
    echo "ALTER FAILED!\n";
    print_r(sqlsrv_errors());
}

// 3. Check Schema AFTER
$stmtCheck = sqlsrv_query($conn, $sql);
$rowAfter = sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC);
echo "New Type: " . ($rowAfter ? $rowAfter['DATA_TYPE'] : 'Not Found') . "\n";

sqlsrv_close($conn);
echo "</pre>";
?>
