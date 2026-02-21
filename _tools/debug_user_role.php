<?php
require_once 'config/database.php';
require_once 'app/core/Database.php';

$config = require 'config/database.php';
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

$user = 'admin_script';
$sql = "SELECT * FROM tbluser WHERE USERID = ?";
$stmt = db_query($conn, $sql, [$user]);

if ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "User: " . $row['USERID'] . "\n";
    echo "Dept: '" . $row['DEPT'] . "'\n"; // Quote it to see spaces or case
    echo "Job Function: " . $row['JOB_FUNCTION'] . "\n";
} else {
    echo "User $user not found.\n";
}
