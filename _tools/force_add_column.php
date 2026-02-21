<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// 1. Check if column exists
$checkSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_templates' AND COLUMN_NAME = 'description'";
$checkStmt = sqlsrv_query($conn, $checkSql);

if ($checkStmt && sqlsrv_has_rows($checkStmt)) {
    echo "STATUS: Column 'description' ALREADY EXISTS.\n";
} else {
    echo "STATUS: Column 'description' MISSING. Attempting to ADD...\n";
    
    // 2. Add Column
    $addSql = "ALTER TABLE script_templates ADD description NVARCHAR(MAX)";
    $addStmt = sqlsrv_query($conn, $addSql);
    
    if ($addStmt) {
        echo "SUCCESS: Column 'description' added successfully.\n";
    } else {
        echo "ERROR: Failed to add column.\n";
        print_r(sqlsrv_errors());
    }
}
?>
