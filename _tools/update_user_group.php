<?php
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// Update pic01 specifically
$sql = "UPDATE script_users SET group_name = 'Coordinator Script' WHERE username = 'pic01'"; // User schema uses 'username'
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    $rows = sqlsrv_rows_affected($stmt);
    echo "SUCCESS: Updated 'pic01' group_name to 'Coordinator Script'. Rows affected: " . $rows . "\n";
    
    // Also update any others with old name just in case
    $sql2 = "UPDATE script_users SET group_name = 'Coordinator Script' WHERE group_name = 'PIC Script Reviewer'";
    $stmt2 = sqlsrv_query($conn, $sql2);
    if($stmt2) {
         $rows2 = sqlsrv_rows_affected($stmt2);
         if($rows2 > 0) echo "SUCCESS: Updated $rows2 other users with old group name.\n";
    }
} else {
    echo "ERROR: " . print_r(sqlsrv_errors(), true);
}
