<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/helpers/DbAdapter.php';

$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

echo "--- CHECKING SCRIPT LIBRARY STATUS ---\n";

// 1. Check Row Count
$countSql = "SELECT COUNT(*) as total FROM script_library";
$stmt = db_query($conn, $countSql);
$row = db_fetch_array($stmt, DB_FETCH_ASSOC);
echo "Total Rows in script_library: " . $row['total'] . "\n";

// 2. Check Columns
echo "\n--- COLUMNS ---\n";
$colSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_library'";
$stmtCol = db_query($conn, $colSql);
$columns = [];
while ($c = db_fetch_array($stmtCol, DB_FETCH_ASSOC)) {
    $columns[] = $c['COLUMN_NAME'];
}
echo implode(", ", $columns) . "\n";

if (!in_array('is_active', $columns)) {
    echo "\n[CRITICAL] 'is_active' column is MISSING!\n";
} else {
    echo "\n[OK] 'is_active' column exists.\n";
}

// 3. Check Data Sample
if ($row['total'] > 0) {
    echo "\n--- DATA SAMPLE (First 5) ---\n";
    $dataSql = "SELECT TOP 5 id, request_id, created_at, is_active FROM script_library ORDER BY created_at DESC";
    $stmtData = db_query($conn, $dataSql);
    while ($d = db_fetch_array($stmtData, DB_FETCH_ASSOC)) {
        echo "ID: " . $d['id'] . " | ReqID: " . $d['request_id'] . " | Active: " . ($d['is_active'] === null ? 'NULL' : $d['is_active']) . "\n";
    }
    
    // Check Inactive Count
    $inactiveSql = "SELECT COUNT(*) as total FROM script_library WHERE is_active = 0 OR is_active IS NULL";
    $stmtInactive = db_query($conn, $inactiveSql);
    $rowInactive = db_fetch_array($stmtInactive, DB_FETCH_ASSOC);
    echo "\nTotal Inactive Scripts: " . $rowInactive['total'] . "\n";
} else {
    echo "\n[INFO] Table is empty. No scripts have been published to the library yet.\n";
    
    // Check if there are Approved requests that SHOULD be in library?
    echo "\n--- CHECKING APPROVED REQUESTS ---\n";
    $reqSql = "SELECT COUNT(*) as total FROM script_request WHERE status = 'APPROVED'";
    $stmtReq = db_query($conn, $reqSql);
    $rowReq = db_fetch_array($stmtReq, DB_FETCH_ASSOC);
    echo "Total APPROVED Requests in script_request: " . $rowReq['total'] . "\n";
}
