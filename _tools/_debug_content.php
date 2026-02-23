<?php
require_once 'app/core/DbAdapter.php';
require_once 'config/database.php';
$conn = db_connect();

// Get the latest Free Input content to handle
$sql = "SELECT request_id, content, media FROM script_preview_content ORDER BY id DESC LIMIT 5";
$stmt = db_query($conn, $sql);

echo "<h1>Raw Content Debug</h1>";
echo "<pre>";

while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "Request ID: " . $row['request_id'] . "\n";
    echo "Media: " . $row['media'] . "\n";
    echo "RAW HEX: " . bin2hex($row['content']) . "\n";
    echo "RAW CONTENT:\n" . htmlspecialchars($row['content']) . "\n";
    echo "--------------------------\n";
    
    // Test the logic using current Model logic
    $text = html_entity_decode($row['content']);
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text); 
    $text = preg_replace('/<\/p>/i', "\n", $text);
    $text = preg_replace('/<\/div>/i', "\n", $text);
    $text = strip_tags($text);
    $text = trim($text);
    
    echo "PROCESSED OUTPUT:\n" . $text . "\n";
    echo "==========================\n\n";
}
echo "</pre>";
