<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$sql = "IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_templates' AND COLUMN_NAME = 'description')
BEGIN
    ALTER TABLE script_templates ADD description NVARCHAR(MAX) NULL;
    PRINT 'Column description added.';
END
ELSE
BEGIN
    PRINT 'Column description already exists.';
END";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    echo "Database Schema Updated Successfully.\n";
} else {
    echo "Error updating schema: " . print_r(sqlsrv_errors(), true);
}
?>
