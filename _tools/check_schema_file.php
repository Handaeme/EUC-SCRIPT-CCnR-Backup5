<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

$status = "UNKNOWN";
if ($conn) {
    $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_templates' AND COLUMN_NAME = 'description'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt && sqlsrv_has_rows($stmt)) {
        $status = "EXISTS";
    } else {
        $status = "MISSING";
    }
} else {
    $status = "DBCONNECTION_FAILED";
}
file_put_contents("schema_status.txt", $status);
?>
