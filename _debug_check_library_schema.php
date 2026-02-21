<?php
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/app/helpers/DbAdapter.php';

echo "\nChecking 'script_library' table columns...\n";
$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Connection failed: " . print_r(db_errors(), true));
}

$stmt = db_query($conn, "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_library'");
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
}
