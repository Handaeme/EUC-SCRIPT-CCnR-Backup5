<?php
echo "Testing DB Connection...\n";
$config = require 'config/database.php';
$serverName = $config['host'];
$connectionOptions = [
    "Database" => $config['dbname'],
    "UID" => $config['user'],
    "PWD" => $config['pass']
];

// Try SQLSRV
echo "Attempting SQLSRV connection...\n";
if (function_exists('sqlsrv_connect')) {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if ($conn) {
        echo "SQLSRV Connected successfully.\n";
    } else {
        echo "SQLSRV Connection failed.\n";
        print_r(sqlsrv_errors());
    }
} else {
    echo "SQLSRV extension not loaded.\n";
}
