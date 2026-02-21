<?php
require_once __DIR__ . '/config/database.php';
$conn = db_connect($dbConfig['host'], $dbConfig['options'] ?? []);

echo "=== Check 1: Duplicate USERIDs in tbluser ===\n";
$sql = "SELECT USERID, COUNT(*) as cnt FROM tbluser GROUP BY USERID HAVING COUNT(*) > 1";
$stmt = db_query($conn, $sql);
$found = false;
if ($stmt) {
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        echo "DUPLICATE USER: " . $row['USERID'] . " (Count: " . $row['cnt'] . ")\n";
        $found = true;
    }
}
if (!$found) echo "No duplicate USERIDs found.\n";

echo "\n=== Check 2: Audit Logs for latest request ===\n";
$sql = "SELECT TOP 1 id, ticket_id, script_number FROM script_request ORDER BY id DESC";
$stmt = db_query($conn, $sql);
if ($stmt && $req = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "Latest Request: ID=" . $req['id'] . " Ticket=" . $req['ticket_id'] . " SN=" . $req['script_number'] . "\n";
    
    // Count raw audit logs for this request
    $sql2 = "SELECT id, action, user_id, created_at FROM script_audit_trail WHERE request_id = ?";
    $stmt2 = db_query($conn, $sql2, [$req['id']]);
    $logCount = 0;
    if ($stmt2) {
        while ($log = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
            $logCount++;
            echo "  Log $logCount: ID=" . $log['id'] . " Action=" . $log['action'] . " User=" . $log['user_id'] . " Time=" . ($log['created_at'] instanceof DateTime ? $log['created_at']->format('Y-m-d H:i:s.u') : $log['created_at']) . "\n";
        }
    }
    echo "Total audit logs for this request: $logCount\n";

    // Count JOINED result (the query used by audit/detail.php)
    $parts = explode('-', $req['script_number']);
    if (count($parts) > 1) { array_pop($parts); }
    $basePattern = implode('-', $parts) . '-%';
    
    $sql3 = "SELECT a.id as audit_id, a.action, u.USERID, u.FULLNAME
             FROM script_audit_trail a 
             LEFT JOIN tbluser u ON a.user_id = u.USERID 
             LEFT JOIN script_request r ON a.request_id = r.id
             WHERE r.script_number LIKE ?
             ORDER BY a.created_at ASC";
    $stmt3 = db_query($conn, $sql3, [$basePattern]);
    $joinCount = 0;
    if ($stmt3) {
        while ($row = db_fetch_array($stmt3, DB_FETCH_ASSOC)) {
            $joinCount++;
            echo "  JoinedRow $joinCount: AuditID=" . $row['audit_id'] . " Action=" . $row['action'] . " USERID=" . $row['USERID'] . " Name=" . $row['FULLNAME'] . "\n";
        }
    }
    echo "Total JOINED rows: $joinCount\n";
    
    if ($joinCount > $logCount) {
        echo "\n[ISSUE] JOIN produces MORE rows than raw audit logs! Likely duplicate USERIDs in tbluser.\n";
    } else if ($logCount > 1) {
        echo "\n[ISSUE] Multiple raw audit logs exist! logAudit dedup may have failed.\n";
    } else {
        echo "\n[OK] Data looks clean.\n";
    }
}
