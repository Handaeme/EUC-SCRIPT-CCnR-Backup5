<?php
require_once 'app/helpers/DbAdapter.php';
require_once 'config/database.php';

$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database'=>$config['dbname'],'UID'=>$config['user'],'PWD'=>$config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_library'";
$stmt = db_query($conn, $sql);

if ($stmt) {
    echo "Columns in script_library:\n";
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        echo "- " . $row['COLUMN_NAME'] . "\n";
    }
} else {
    echo "Query failed: " . print_r(db_errors(), true);
}
?>
