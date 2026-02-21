<?php
$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$title = "Debug Template";
$filename = "debug.xlsx";
$filepath = "uploads/debug.xlsx";
$user = "debug_user";
$description = "This is a debug description.";

$sql = "INSERT INTO script_templates (title, filename, filepath, uploaded_by, description) VALUES (?, ?, ?, ?, ?)";
$params = [$title, $filename, $filepath, $user, $description];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo "INSERT FAILED.\n";
    print_r(sqlsrv_errors());
} else {
    echo "INSERT SUCCESS.\n";
    // Cleanup
    $sqlDel = "DELETE FROM script_templates WHERE title = 'Debug Template'";
    sqlsrv_query($conn, $sqlDel);
}
?>
