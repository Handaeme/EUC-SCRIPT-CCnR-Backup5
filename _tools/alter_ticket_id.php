<?php
$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], isset($config['options']) ? $config['options'] : []);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "ALTER TABLE script_request ALTER COLUMN ticket_id VARCHAR(20)";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    echo "Successfully altered ticket_id to VARCHAR(20).\n";
} else {
    print_r(sqlsrv_errors());
}
sqlsrv_close($conn);
