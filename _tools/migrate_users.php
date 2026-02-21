<?php
require_once 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die("Connection Fail: " . print_r(sqlsrv_errors(), true));

// 1. ADD COLUMN
$sql = "ALTER TABLE script_users ADD role_code VARCHAR(20)";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt) echo "Column role_code added.\n";
else echo "Column add warning/skip (might exist).\n";

// 2. UPDATE DATA
$updates = [
    'maker01' => 'MAKER',
    'spv01' => 'SPV',
    'pic01' => 'PIC',
    'proc01' => 'PROCEDURE',
    'proc02' => 'PROCEDURE',
    'admin' => 'ADMIN'
];

foreach ($updates as $user => $role) {
    echo "Updating $user to $role... ";
    $sql = "UPDATE script_users SET role_code = ? WHERE userid = ?";
    $res = sqlsrv_query($conn, $sql, [$role, $user]);
    if ($res) echo "OK\n";
    else echo "FAIL\n";
}
?>
