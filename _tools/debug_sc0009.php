<?php
// Debug script for SC-0009 data
require_once 'config/database.php';

$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

$requestId = 9; // SC-0009

echo "=== DEBUGGING REQUEST ID: $requestId ===\n\n";

// 1. Check script_request table
echo "1. SCRIPT_REQUEST TABLE:\n";
echo "------------------------\n";
$sql = "SELECT * FROM script_request WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);
if ($stmt && sqlsrv_has_rows($stmt)) {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo "Columns in script_request:\n";
    foreach ($row as $key => $value) {
        if (is_object($value) && $value instanceof DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }
        echo "  - $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
} else {
    echo "  No data found\n";
}

echo "\n\n";

// 2. Check script_preview_content
echo "2. SCRIPT_PREVIEW_CONTENT TABLE:\n";
echo "--------------------------------\n";
$sql = "SELECT * FROM script_preview_content WHERE request_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);
$count = 0;
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "  Row #$count:\n";
        echo "    - id: {$row['id']}\n";
        echo "    - media: {$row['media']}\n";
        echo "    - content: " . substr($row['content'], 0, 100) . "...\n";
        echo "    - workflow_stage: {$row['workflow_stage']}\n";
    }
}
if ($count === 0) {
    echo "  No data found\n";
}

echo "\n\n";

// 3. Check script_library
echo "3. SCRIPT_LIBRARY TABLE:\n";
echo "------------------------\n";
$sql = "SELECT * FROM script_library WHERE request_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);
$count = 0;
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "  Row #$count:\n";
        echo "    - id: {$row['id']}\n";
        echo "    - media: {$row['media']}\n";
        echo "    - content: " . substr($row['content'], 0, 100) . "...\n";
    }
}
if ($count === 0) {
    echo "  No data found\n";
}

echo "\n\n";

// 4. Check script_files
echo "4. SCRIPT_FILES TABLE:\n";
echo "----------------------\n";
$sql = "SELECT * FROM script_files WHERE request_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);
$count = 0;
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "  Row #$count:\n";
        echo "    - file_type: {$row['file_type']}\n";
        echo "    - original_filename: {$row['original_filename']}\n";
        echo "    - filepath: {$row['filepath']}\n";
    }
}
if ($count === 0) {
    echo "  No data found\n";
}

echo "\n\n";

// 5. Check table structure for legacy columns
echo "5. CHECKING TABLE STRUCTURE:\n";
echo "----------------------------\n";
$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_request' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);
echo "Columns in script_request table:\n";
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "  - " . $row['COLUMN_NAME'] . "\n";
    }
}

sqlsrv_close($conn);
echo "\n=== DEBUG COMPLETE ===\n";
