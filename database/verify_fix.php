<?php
// Verify Schema Fix
require_once __DIR__ . '/../app/config/database.php';

$config = require __DIR__ . '/../app/config/database.php';
$conn = sqlsrv_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

echo "Testing INSERT with SC-9999...\n";

// Attempt insert with string ticket_id
$sql = "INSERT INTO script_request (ticket_id, script_number, title, jenis, produk, kategori, media, mode, created_by, status, created_at) 
        VALUES (?, 'TEST-001', 'Test Schema', 'Konvensional', 'KPR', 'Pre Due', 'WA', 'FREE_INPUT', 'maker01', 'TEST', GETDATE())";

$params = ['SC-9999']; 
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo "SUCCESS: Inserted 'SC-9999'. Schema accepts VARCHAR.\n";
    // Cleanup
    sqlsrv_query($conn, "DELETE FROM script_request WHERE ticket_id = 'SC-9999'");
} else {
    echo "FAILED: Could not insert string.\n";
    print_r(sqlsrv_errors());
    
    // Try checking column type info
    $sqlHelper = "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' AND COLUMN_NAME = 'ticket_id'";
    $stmtHelper = sqlsrv_query($conn, $sqlHelper);
    if ($stmtHelper && $row = sqlsrv_fetch_array($stmtHelper, SQLSRV_FETCH_ASSOC)) {
        echo "Current Column Type: " . $row['DATA_TYPE'] . "\n";
    }
}

sqlsrv_close($conn);
?>
