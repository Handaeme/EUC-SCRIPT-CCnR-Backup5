<?php
require_once __DIR__ . '/../config/database.php';
$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "UPDATE tbluser SET PASSWORD = '123' WHERE LDAP = 1 AND (PASSWORD IS NULL OR PASSWORD = '')";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) die(print_r(sqlsrv_errors(), true));

echo "Passwords updated successfully for LDAP users.\n";
sqlsrv_close($conn);
