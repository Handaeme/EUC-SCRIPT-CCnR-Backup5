<?php
/**
 * Simple diagnostic - hardcoded logic
 */
require_once __DIR__ . '/app/helpers/DbAdapter.php';
require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');

header('Content-Type: text/plain; charset=utf-8');

$scriptNumberToCheck = 'KONV-RC-12/02/26-0037';
echo "=== AUDIT TRAIL DIAGNOSTIC ===\n";
echo "Base Script: $scriptNumberToCheck\n\n";

// --- CONNECTION LOGIC COPIED FROM INDEX.PHP ---
if (!file_exists(__DIR__ . '/config/database.php')) {
    die("Error: config/database.php not found.");
}
$config = require __DIR__ . '/config/database.php';

$connectionInfo = [
    'Database' => $config['dbname'],
    'UID' => $config['user'],
    'PWD' => $config['pass']
];
if (isset($config['options'])) {
    $connectionInfo = array_merge($connectionInfo, $config['options']);
}

$conn = db_connect($config['host'], $connectionInfo);
if (!$conn) {
    echo "DB Connection Failed!\n";
    print_r(db_errors());
    exit;
}
echo "[OK] DB Connected.\n\n";
// ---------------------------------------------

// 1. Find ALL related requests
echo "--- ALL RELATED REQUESTS ---\n";
// Manual regex logic simulation for "KONV-RC-12/02/26-0037"
// It's a parent, so we search exact OR with suffix
$sql = "SELECT id, script_number, status, created_by, ticket_id, created_at 
        FROM script_request 
        WHERE script_number = ? OR script_number LIKE ?
        ORDER BY created_at ASC";
$stmt = db_query($conn, $sql, [$scriptNumberToCheck, $scriptNumberToCheck . '-%']);
$requests = [];
if ($stmt) {
    while ($r = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        $requests[] = $r;
        $dt = ($r['created_at'] instanceof DateTime) ? $r['created_at']->format('Y-m-d H:i:s') : $r['created_at'];
        echo "ID={$r['id']} | SN={$r['script_number']} | Status={$r['status']} | By={$r['created_by']} | {$dt}\n";
    }
}
echo "Total: " . count($requests) . "\n\n";

// 2. For EACH request, show its audit trail
echo "--- AUDIT TRAIL PER REQUEST ---\n";
foreach ($requests as $req) {
    echo "\n>> Request ID={$req['id']} (SN={$req['script_number']}):\n";
    $sql2 = "SELECT id, action, user_role, user_id, details, created_at, script_number as audit_sn
             FROM script_audit_trail 
             WHERE request_id = ? 
             ORDER BY created_at ASC";
    $stmt2 = db_query($conn, $sql2, [$req['id']]);
    $count = 0;
    if ($stmt2) {
        while ($row = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
            $count++;
            $dt = ($row['created_at'] instanceof DateTime) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
            echo "  [{$row['id']}] {$row['action']} | {$row['user_role']} | {$row['user_id']} | AuditSN={$row['audit_sn']} | {$dt}\n";
            if (!empty($row['details'])) echo "       Detail: " . substr($row['details'], 0, 80) . "\n";
        }
    }
    if ($count === 0) echo "  (NO ENTRIES!)\n";
    echo "  Total entries: $count\n";
}

echo "\n=== END ===\n";
