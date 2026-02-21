<?php
require_once 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request'";
$stmt = sqlsrv_query($conn, $sql);

echo "<h3>Columns in script_request:</h3><ul>";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<li>" . $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")</li>";
}
echo "</ul>";
?>
