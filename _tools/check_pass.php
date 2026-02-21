<?php
require_once __DIR__ . '/../config/database.php';

$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "SELECT USERID, PASSWORD, LDAP FROM tbluser";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) die(print_r(sqlsrv_errors(), true));

echo "| USERID | PASSWORD | LDAP |\n";
echo "| :--- | :--- | :--- |\n";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "| " . trim($row['USERID']) . " ";
    echo "| " . ($row['PASSWORD'] === null ? 'NULL' : trim($row['PASSWORD'])) . " ";
    echo "| " . $row['LDAP'] . " |\n";
}
sqlsrv_close($conn);
