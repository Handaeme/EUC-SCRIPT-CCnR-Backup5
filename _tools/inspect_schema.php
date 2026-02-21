<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

$sql = "sp_columns script_templates";
$stmt = sqlsrv_query($conn, $sql);

if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Columns in script_templates:\n";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['TYPE_NAME'] . ")\n";
}
?>
