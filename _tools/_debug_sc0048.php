<?php
require_once 'app/init.php';

$ticket = 'SC-0048';
echo "Checking for $ticket...\n";

$sql = "SELECT id, created_at, title, script_number FROM script_request WHERE ticket_id = ?";
$stmt = db_query($conn, $sql, [$ticket]);

$count = 0;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $count++;
    echo "Row $count: ID=" . $row['id'] . " | Number=" . $row['script_number'] . " | Created=" . ($row['created_at'] instanceof DateTime ? $row['created_at']->format('Y-m-d H:i:s.u') : $row['created_at']) . "\n";
    
    // Check Audit Logs for this ID
    $logSql = "SELECT id, action, created_at FROM script_audit_trail WHERE request_id = ?";
    $logStmt = db_query($conn, $logSql, [$row['id']]);
    while ($l = db_fetch_array($logStmt, DB_FETCH_ASSOC)) {
        echo "   - Log: " . $l['action'] . " | ID=" . $l['id'] . " | " . ($l['created_at'] instanceof DateTime ? $l['created_at']->format('Y-m-d H:i:s.u') : $l['created_at']) . "\n";
    }
}
