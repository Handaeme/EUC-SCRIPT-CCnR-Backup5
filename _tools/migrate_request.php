<?php
require_once 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die("Connection Fail: " . print_r(sqlsrv_errors(), true));

// Check if column exists
$checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='script_request' AND COLUMN_NAME='selected_spv'";
$stmt = sqlsrv_query($conn, $checkSql);
if (sqlsrv_has_rows($stmt)) {
    echo "Column 'selected_spv' already exists.\n";
} else {
    echo "Column 'selected_spv' missing. Adding...\n";
    $addSql = "ALTER TABLE script_request ADD selected_spv VARCHAR(50)";
    $res = sqlsrv_query($conn, $addSql);
    if ($res) echo "SUCCESS: Column added.\n";
    else echo "FAIL: " . print_r(sqlsrv_errors(), true) . "\n";
}
?>
