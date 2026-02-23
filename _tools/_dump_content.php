<?php
require_once __DIR__ . '/app/config/database.php';
$conn = db_connect();

$id = 94; // Request ID SC-0094
$sql = "SELECT workflow_stage, content FROM script_preview_content WHERE request_id = $id AND workflow_stage LIKE '%SPV%'";
$stmt = db_query($conn, $sql);
$row = db_fetch_array($stmt, DB_FETCH_ASSOC);

if ($row) {
    file_put_contents('debug_content_94.html', $row['content']);
    echo "Content dumped to debug_content_94.html\n";
} else {
    echo "No content found for ID 94 SPV\n";
}
