<?php
/**
 * DEBUG PROCEDURE VISIBILITY
 * Tests what pending tickets are visible to a specific Procedure user.
 */
session_start();
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🔍 Debug Procedure Ticket Visibility</h2>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
if (!$conn) die("<p style='color:red;'>❌ DB connection failed</p>");

// Check tickets with status APPROVED_PIC
echo "<h3>1. All Tickets with Status 'APPROVED_PIC'</h3>";
$sql1 = "SELECT id, script_no, title, status, created_by, selected_spv, selected_pic FROM script_request WHERE status = 'APPROVED_PIC'";
$stmt1 = db_query($conn, $sql1);
$count1 = 0;
if ($stmt1) { 
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white;'>";
    echo "<tr style='background:#f1f5f9;'><th>ID</th><th>Script No</th><th>Title</th><th>Status</th><th>Maker</th><th>SPV</th><th>PIC</th></tr>";
    while ($r = db_fetch_array($stmt1, DB_FETCH_ASSOC)) { 
        $count1++; 
        echo "<tr><td>{$r['id']}</td><td>{$r['script_no']}</td><td>{$r['title']}</td><td>{$r['status']}</td><td>{$r['created_by']}</td><td>{$r['selected_spv']}</td><td>{$r['selected_pic']}</td></tr>"; 
    }
    echo "</table>";
}
echo $count1 == 0 ? "<p style='color:red;'>❌ No tickets with status 'APPROVED_PIC' found.</p>" : "<p><b>Total: $count1 tickets found</b></p>";

// Simulating getPendingRequests for Procedure
// Assuming logged in user is the procedure
$procedureUserId = $_SESSION['user']['userid'] ?? 'Unknown Procedure';

echo "<h3>2. Simulating getPendingRequests for Procedure ($procedureUserId)</h3>";
$sql2 = "SELECT id, script_no, title, status, created_by FROM script_request WHERE (status = 'APPROVED_PIC' OR (status = 'DRAFT_TEMP' AND created_by = ?)) AND is_deleted = 0";
$stmt2 = db_query($conn, $sql2, [$procedureUserId]);
$count2 = 0;
if ($stmt2) { 
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white;'>";
    echo "<tr style='background:#f1f5f9;'><th>ID</th><th>Script No</th><th>Title</th><th>Status</th><th>Maker</th></tr>";
    while ($r = db_fetch_array($stmt2, DB_FETCH_ASSOC)) { 
        $count2++; 
        echo "<tr><td>{$r['id']}</td><td>{$r['script_no']}</td><td>{$r['title']}</td><td>{$r['status']}</td><td>{$r['created_by']}</td></tr>"; 
    }
    echo "</table>";
}
echo $count2 == 0 ? "<p style='color:red;'>❌ No pending tickets found for Procedure.</p>" : "<p><b>Total: $count2 tickets found</b></p>";

// If count1 > 0 but count2 == 0, check is_deleted
if ($count1 > 0 && $count2 == 0) {
    echo "<h3>3. Checking if APPROVED_PIC tickets are deleted</h3>";
    $sql3 = "SELECT id, is_deleted FROM script_request WHERE status = 'APPROVED_PIC'";
    $stmt3 = db_query($conn, $sql3);
    if ($stmt3) {
        while ($r = db_fetch_array($stmt3, DB_FETCH_ASSOC)) { 
             echo "<p>Ticket ID {$r['id']} has is_deleted = {$r['is_deleted']}</p>";
        }
    }
}

// Current User Session
echo "<h3>4. Current User Session Details</h3>";
echo "<pre style='background:white; padding:10px; border:1px solid #ccc;'>" . print_r($_SESSION['user'] ?? "No session data", true) . "</pre>";

echo "</body></html>";
