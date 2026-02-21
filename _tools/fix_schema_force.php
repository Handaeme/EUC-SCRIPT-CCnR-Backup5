<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Just try to add it. If it fails, print why.
$sql = "ALTER TABLE script_templates ADD description NVARCHAR(MAX)";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "ALTER FAILED (Required if column exists): ";
    print_r(sqlsrv_errors());
} else {
    echo "ALTER SUCCESS. Column added.\n";
}
?>
