<?php
require_once __DIR__ . '/../app/config/database.php';

$serverName = DB_HOST;
$connectionOptions = array(
    "Database" => DB_NAME,
    "Uid" => DB_USER,
    "PWD" => DB_PASS,
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) die("Connection failed");

// Check for any remaining red text
$sql = "SELECT COUNT(*) as dirty_count FROM script_library 
        WHERE content LIKE '%color:%red%' 
           OR content LIKE '%color: red%'
           OR content LIKE '%#ef4444%' 
           OR content LIKE '%rgb(255%'
           OR content LIKE '%rgb(239%'";

$stmt = sqlsrv_query($conn, $sql);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "Remaining Dirty Records: " . $row['dirty_count'] . "\n";
?>
