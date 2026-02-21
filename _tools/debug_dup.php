<?php
// Debug Script for Content Duplication

// 1. Connect
$serverName = "LAPTOP-T9BEF7E1\SQLEXPRESS"; // From config
$connectionOptions = [
    "Database" => "CITRA",
    "CharacterSet" => "UTF-8"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die("Connection failed: " . print_r(sqlsrv_errors(), true));
}

// 2. Get ID
$scriptNum = 'KONV-RC-27/01/26-0008-01';
$sql = "SELECT id FROM requests WHERE script_number = ?";
$stmt = sqlsrv_query($conn, $sql, [$scriptNum]);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) die("Script not found");
$id = $row['id'];
echo "Script ID: " . $id . "\n";

// 3. Get Content
$sql = "SELECT media, content FROM script_preview_content WHERE request_id = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
$rows = [];
while ($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $r;
}
echo "Row Count: " . count($rows) . "\n\n";

// 4. Analyze
foreach ($rows as $i => $r) {
    echo "--- ROW $i ({$r['media']}) ---\n";
    $content = $r['content'];
    echo "Length: " . strlen($content) . "\n";
    echo "Start: " . substr($content, 0, 100) . "...\n";
    
    $terms = ['sheet-tabs-nav', 'btn-sheet', 'btn-media-tab', 'media-pane'];
    foreach ($terms as $t) {
        $files = stripos($content, $t) !== false ? "FOUND" : "NOT FOUND";
        echo "Check '$t': $files\n";
    }
    echo "\n";
}
?>
