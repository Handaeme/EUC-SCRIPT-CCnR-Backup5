<?php
require_once __DIR__ . '/../config/database.php';

// Connect to DB using config
$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$sql = "SELECT * FROM tbluser";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "| USERID | FULLNAME | LDAP | ROLE | GROUP | IS_ACTIVE |\n";
echo "| :--- | :--- | :--- | :--- | :--- | :--- |\n";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "| " . trim($row['USERID']) . " ";
    echo "| " . trim($row['FULLNAME']) . " ";
    echo "| " . ($row['LDAP'] == 1 ? 'YES (1)' : 'NO (0)') . " ";
    echo "| " . trim($row['DEPT']) . " ";
    echo "| " . trim($row['GROUP_NAME']) . " ";
    echo "| " . $row['IS_ACTIVE'] . " |\n";
}

sqlsrv_close($conn);
