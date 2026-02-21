<?php
require_once 'app/init.php';

$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// 1. Check Column
$sqlCol = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'has_draft'";
$stmtCol = sqlsrv_query($conn, $sqlCol);
$hasCol = sqlsrv_fetch_array($stmtCol);

echo "Column 'has_draft' exists: " . ($hasCol ? "YES" : "NO") . "\n";

// 2. Check Data
$sqlData = "SELECT id, ticket_id, has_draft FROM script_request WHERE ticket_id = 'SC-0010'";
$stmtData = sqlsrv_query($conn, $sqlData);
$row = sqlsrv_fetch_array($stmtData, SQLSRV_FETCH_ASSOC);

if ($row) {
    echo "ID: " . $row['id'] . "\n";
    echo "Ticket: " . $row['ticket_id'] . "\n";
    echo "Has Draft: ";
    var_dump($row['has_draft']);
} else {
    echo "Record SC-0010 not found.\n";
}
