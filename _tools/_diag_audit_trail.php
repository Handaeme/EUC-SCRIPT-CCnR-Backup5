<?php
/**
 * Diagnostic: Check audit trail data for a specific script
 * Usage: php _diag_audit_trail.php
 * Or:   http://localhost/EUC-Script-CCnR-Migrasi/_diag_audit_trail.php
 */

require_once __DIR__ . '/app/helpers/DbAdapter.php';

// --- CONFIG ---
$scriptNumberToCheck = 'KONV-RC-12/02/26-0037-03'; // CHANGE THIS to the script number you want to check
// Or pass via query string: ?sn=KONV-RC-12/02/26-0037-03
if (isset($_GET['sn'])) $scriptNumberToCheck = $_GET['sn'];

echo "<pre style='font-family:monospace; font-size:13px; padding:20px;'>";
echo "=== AUDIT TRAIL DIAGNOSTIC ===\n";
echo "Target Script Number: $scriptNumberToCheck\n\n";

// Connect
$conn = db_connect();
if (!$conn) { echo "DB Connection FAILED!"; exit; }
echo "[OK] Connected to database.\n\n";

// Step 1: Find the request by script_number
echo "--- STEP 1: Find Request ---\n";
$sql = "SELECT id, script_number, status, created_by, created_at, title FROM script_request WHERE script_number = ?";
$stmt = db_query($conn, $sql, [$scriptNumberToCheck]);
$request = db_fetch_array($stmt, DB_FETCH_ASSOC);
if (!$request) {
    echo "Request with script_number '$scriptNumberToCheck' NOT FOUND!\n";
    exit;
}
echo "Found: ID={$request['id']}, Script={$request['script_number']}, Status={$request['status']}, Creator={$request['created_by']}\n";
echo "Title: {$request['title']}\n\n";

// Step 2: Apply MY regex logic
echo "--- STEP 2: Regex Logic ---\n";
$scriptNumber = $request['script_number'];
$basePattern = $scriptNumber . '-%';
$baseExact = $scriptNumber;

if (preg_match('/-(\d{2})$/', $scriptNumber)) {
    $baseExact = preg_replace('/-(\d{2})$/', '', $scriptNumber);
    $basePattern = $baseExact . '-%';
    echo "Versioned script detected.\n";
} else {
    echo "Parent script detected (no version suffix).\n";
}
echo "baseExact  = $baseExact\n";
echo "basePattern = $basePattern\n";
echo "SQL: WHERE (r.script_number = '$baseExact' OR r.script_number LIKE '$basePattern')\n\n";

// Step 3: Find ALL related requests
echo "--- STEP 3: All Related Requests ---\n";
$sql2 = "SELECT id, script_number, status, created_by, ticket_id, created_at 
         FROM script_request 
         WHERE script_number = ? OR script_number LIKE ? 
         ORDER BY created_at ASC";
$stmt2 = db_query($conn, $sql2, [$baseExact, $basePattern]);
$relatedRequests = [];
if ($stmt2) {
    while ($r = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
        $relatedRequests[] = $r;
        $dt = ($r['created_at'] instanceof DateTime) ? $r['created_at']->format('Y-m-d H:i:s') : $r['created_at'];
        echo "  ID={$r['id']} | SN={$r['script_number']} | Status={$r['status']} | Creator={$r['created_by']} | Created={$dt}\n";
    }
}
if (empty($relatedRequests)) {
    echo "  (No related requests found!)\n";
}
echo "\n";

// Step 4: Get ALL audit trail entries for these requests
echo "--- STEP 4: Audit Trail Entries (via JOIN) ---\n";
$sql3 = "SELECT a.id as audit_id, a.request_id, a.script_number as audit_sn, a.action, a.user_role, a.user_id, a.details, a.created_at,
                r.script_number as req_sn
         FROM script_audit_trail a
         LEFT JOIN script_request r ON a.request_id = r.id
         WHERE (r.script_number = ? OR r.script_number LIKE ?)
         ORDER BY a.created_at ASC";
$stmt3 = db_query($conn, $sql3, [$baseExact, $basePattern]);
$auditLogs = [];
if ($stmt3) {
    while ($row = db_fetch_array($stmt3, DB_FETCH_ASSOC)) {
        $auditLogs[] = $row;
        $dt = ($row['created_at'] instanceof DateTime) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
        echo "  [{$row['audit_id']}] ReqID={$row['request_id']} | ReqSN={$row['req_sn']} | AuditSN={$row['audit_sn']} | Action={$row['action']} | Role={$row['user_role']} | User={$row['user_id']} | {$dt}\n";
        if (!empty($row['details'])) {
            echo "         Details: " . substr($row['details'], 0, 100) . "\n";
        }
    }
}
if (empty($auditLogs)) {
    echo "  (No audit trail entries found!)\n";
}
echo "\n";

// Step 5: Also check audit trail entries directly by request_id (without JOIN)
echo "--- STEP 5: Direct Audit Check (by request_id, no JOIN) ---\n";
foreach ($relatedRequests as $rr) {
    $sql4 = "SELECT id, request_id, script_number, action, user_role, user_id, details, created_at 
             FROM script_audit_trail 
             WHERE request_id = ? 
             ORDER BY created_at ASC";
    $stmt4 = db_query($conn, $sql4, [$rr['id']]);
    $count = 0;
    echo "  Request ID={$rr['id']} (SN={$rr['script_number']}):\n";
    if ($stmt4) {
        while ($row = db_fetch_array($stmt4, DB_FETCH_ASSOC)) {
            $count++;
            $dt = ($row['created_at'] instanceof DateTime) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
            echo "    [{$row['id']}] Action={$row['action']} | Role={$row['user_role']} | User={$row['user_id']} | {$dt}\n";
        }
    }
    if ($count === 0) echo "    (No entries)\n";
}
echo "\n";

// Step 6: Check Library Detail sidebar query (how does it fetch history?)
echo "--- STEP 6: Summary ---\n";
echo "Total related requests: " . count($relatedRequests) . "\n";
echo "Total audit entries (via JOIN): " . count($auditLogs) . "\n";
echo "If audit entries are missing for approvals (APPROVE_SPV, APPROVE_PIC, etc.),\n";
echo "it means those actions were performed BEFORE audit logging was implemented.\n";
echo "</pre>";
