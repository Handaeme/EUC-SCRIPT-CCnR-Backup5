<?php
require_once 'app/init.php';

$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request'";
$stmt = sqlsrv_query($conn, $sql);

$cols = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $cols[] = $row['COLUMN_NAME'];
}

echo "COLUMNS: " . implode(', ', $cols);
