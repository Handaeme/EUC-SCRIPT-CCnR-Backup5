<?php
require_once 'config/database.php';
$config = require 'config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die(json_encode(['error' => 'Connection failed', 'details' => sqlsrv_errors()]));
}

echo "<h2>Debug: File Upload Verification</h2>";

// 1. Check latest request
echo "<h3>1. Latest Requests</h3>";
$sql = "SELECT TOP 5 id, ticket_id, script_number, mode, created_at FROM script_request ORDER BY created_at DESC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt) {
    echo "<table border='1'><tr><th>ID</th><th>Ticket ID</th><th>Script Number</th><th>Mode</th><th>Created At</th></tr>";
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $created = $row['created_at'] instanceof DateTime ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
        echo "<tr><td>{$row['id']}</td><td>{$row['ticket_id']}</td><td>{$row['script_number']}</td><td>{$row['mode']}</td><td>{$created}</td></tr>";
    }
    echo "</table>";
}

// 2. Check files table
echo "<h3>2. Latest Files in script_files</h3>";
$sql = "SELECT TOP 5 id, request_id, file_type, original_filename, filepath, uploaded_at FROM script_files ORDER BY uploaded_at DESC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt) {
    echo "<table border='1'><tr><th>ID</th><th>Request ID</th><th>File Type</th><th>Original Filename</th><th>Filepath</th><th>Uploaded At</th></tr>";
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $uploaded = $row['uploaded_at'] instanceof DateTime ? $row['uploaded_at']->format('Y-m-d H:i:s') : $row['uploaded_at'];
        echo "<tr><td>{$row['id']}</td><td>{$row['request_id']}</td><td>{$row['file_type']}</td><td>{$row['original_filename']}</td><td>{$row['filepath']}</td><td>{$uploaded}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>Error: " . print_r(sqlsrv_errors(), true) . "</p>";
}

// 3. Check preview content
echo "<h3>3. Latest Preview Content</h3>";
$sql = "SELECT TOP 5 id, request_id, media, LEFT(content, 50) as content_snippet FROM script_preview_content ORDER BY updated_at DESC";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt) {
    echo "<table border='1'><tr><th>ID</th><th>Request ID</th><th>Media</th><th>Content Snippet</th></tr>";
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr><td>{$row['id']}</td><td>{$row['request_id']}</td><td>{$row['media']}</td><td>" . htmlspecialchars($row['content_snippet']) . "</td></tr>";
    }
    echo "</table>";
}

sqlsrv_close($conn);
echo "<p><a href='index.php'>Back to App</a></p>";
?>
