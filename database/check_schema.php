<?php
require_once __DIR__ . '/../app/config/database.php';
$config = require __DIR__ . '/../app/config/database.php';
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "Column: " . $row['COLUMN_NAME'] . "\n";
    echo "Type: " . $row['DATA_TYPE'] . "\n";
    echo "Length: " . $row['CHARACTER_MAXIMUM_LENGTH'] . "\n";
} else {
    echo "Column not found or error.\n";
}
?>
