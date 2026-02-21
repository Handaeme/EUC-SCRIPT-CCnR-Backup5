<?php
require_once '../config/database.php';
$conn = sqlsrv_connect($config['host'], ['Database'=>$config['dbname'], 'UID'=>$config['user'], 'PWD'=>$config['pass']]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

$tables = ['script_preview_content', 'script_library', 'script_request'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $sql = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table'";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt) {
        echo "<table border='1'><tr><th>Column</th><th>Type</th><th>Length</th></tr>";
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['DATA_TYPE'] . "</td>";
            echo "<td>" . $row['CHARACTER_MAXIMUM_LENGTH'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}
