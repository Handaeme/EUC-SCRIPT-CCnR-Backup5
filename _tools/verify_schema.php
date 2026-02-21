<?php
require_once 'config/database.php';
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

$tables = ['script_request', 'script_preview_content', 'script_files', 'script_audit_trail', 'script_library', 'script_templates'];
$schema = [];

foreach ($tables as $table) {
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?";
    $stmt = sqlsrv_query($conn, $sql, [$table]);
    $cols = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cols[] = $row['COLUMN_NAME'];
    }
    $schema[$table] = $cols;
}

header('Content-Type: application/json');
echo json_encode($schema, JSON_PRETTY_PRINT);
?>
