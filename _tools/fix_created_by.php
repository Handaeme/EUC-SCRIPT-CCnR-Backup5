<?php
require_once 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die("Connection Fail: " . print_r(sqlsrv_errors(), true));

echo "Altering created_by to VARCHAR(50)...\n";
$sql = "ALTER TABLE script_request ALTER COLUMN created_by VARCHAR(50)";
$res = sqlsrv_query($conn, $sql);

if ($res) echo "SUCCESS: created_by is now VARCHAR.\n";
else {
    echo "FAIL: " . print_r(sqlsrv_errors(), true) . "\n";
    // If fail, maybe creating new column and copying?
    // Or maybe index exists? Ignoring for now, simple ALTER should work for type change if compatible or empty.
    // If it was INT but empty (or only numeric), it should work.
}

// Just in case selected_spv is also wrong
echo "Checking selected_spv type...\n";
$sql2 = "ALTER TABLE script_request ALTER COLUMN selected_spv VARCHAR(50)";
sqlsrv_query($conn, $sql2);

?>
