<?php
// DIAGNOSTIC SCRIPT
require_once __DIR__ . '/../app/config/database.php';
$config = require __DIR__ . '/../app/config/database.php';

echo "Connecting to " . $config['host'] . " DB: " . $config['dbname'] . "\n";
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

// 1. CHECK SCHEMA
$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
$stmt = sqlsrv_query($conn, $sql);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "1. Schema Check:\n";
if ($row) {
    echo "   Column: " . $row['COLUMN_NAME'] . " | Type: " . $row['DATA_TYPE'] . "\n";
    if (strpos(strtolower($row['DATA_TYPE']), 'int') !== false) {
        echo "   [WARNING] Column is still INT!\n";
    } else {
        echo "   [OK] Column is NOT int.\n";
    }
} else {
    echo "   [ERROR] Column not found!\n";
}

// 2. ATTEMPT INSERT
echo "\n2. Insert Test:\n";
$sqlVal = "INSERT INTO script_request (ticket_id, script_number, title, jenis, produk, kategori, media, mode, created_by, status, created_at) 
           VALUES (?, 'TEST-DIAG', 'Diagnostic', 'Konv', 'Prod', 'Cat', 'WA', 'FREE', 'diag', 'TEST', GETDATE())";

$params = ['SC-DIAG-001'];
$stmtInsert = sqlsrv_query($conn, $sqlVal, $params);

if ($stmtInsert) {
    echo "   [SUCCESS] Inserted 'SC-DIAG-001' successfully.\n";
    // Cleanup
    sqlsrv_query($conn, "DELETE FROM script_request WHERE script_number = 'TEST-DIAG'");
} else {
    echo "   [FAILED] Insert failed.\n";
    print_r(sqlsrv_errors());
}

sqlsrv_close($conn);
?>
