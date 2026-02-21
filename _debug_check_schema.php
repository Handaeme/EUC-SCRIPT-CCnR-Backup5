<?php
require_once 'app/helpers/DbAdapter.php';
require_once 'app/models/RequestModel.php';

echo "Checking RequestModel methods...\n";
$methods = get_class_methods('App\Models\RequestModel');
if (in_array('getLibraryItemsWithContent', $methods)) {
    echo "getLibraryItemsWithContent FOUND.\n";
} else {
    echo "getLibraryItemsWithContent NOT FOUND.\n";
}

echo "\nChecking 'requests' table columns...\n";
$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

$stmt = db_query($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'requests'");
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
}
