<?php
require_once 'app/init.php';

echo "Checking for duplicates...\n";

$sql = "SELECT ticket_id, count(*) as c FROM script_request GROUP BY ticket_id HAVING count(*) > 1";
$stmt = db_query($conn, $sql);
$hasDupes = false;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "Duplicate Ticket ID: " . $row['ticket_id'] . " (Count: " . $row['c'] . ")\n";
    $hasDupes = true;
    
    // Show IDs
    $s2 = db_query($conn, "SELECT id, created_at, title FROM script_request WHERE ticket_id = ?", [$row['ticket_id']]);
    while ($r2 = db_fetch_array($s2, DB_FETCH_ASSOC)) {
        echo " - ID: " . $r2['id'] . " | " . ($r2['created_at'] instanceof DateTime ? $r2['created_at']->format('Y-m-d H:i:s') : $r2['created_at']) . " | " . $r2['title'] . "\n";
    }
}

$sql = "SELECT script_number, count(*) as c FROM script_request GROUP BY script_number HAVING count(*) > 1";
$stmt = db_query($conn, $sql);
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "Duplicate Script Number: " . $row['script_number'] . " (Count: " . $row['c'] . ")\n";
    $hasDupes = true;

    // Show IDs
    $s2 = db_query($conn, "SELECT id, created_at, title FROM script_request WHERE script_number = ?", [$row['script_number']]);
    while ($r2 = db_fetch_array($s2, DB_FETCH_ASSOC)) {
        echo " - ID: " . $r2['id'] . " | " . ($r2['created_at'] instanceof DateTime ? $r2['created_at']->format('Y-m-d H:i:s') : $r2['created_at']) . " | " . $r2['title'] . "\n";
    }
}

if (!$hasDupes) echo "No duplicates found.\n";
