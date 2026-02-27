<?php
/**
 * DEBUG MISSING SPV TICKETS
 * Run this script to see why tickets disappear after being submitted by the Maker.
 */
session_start();
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🔍 Diagnostic: Missing Tickets for SPV</h2>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("<p style='color:red;'>❌ Database connection failed.</p>");
}

// 1. Get the last 10 requests REGARDLESS of status
echo "<h3>1. Recent Tickets (Last 10 - ALL STATUSES)</h3>";
$sql = "SELECT TOP 10 id, script_number, ticket_id, title, status, created_by, selected_spv, created_at 
        FROM script_request 
        ORDER BY created_at DESC";
$stmt = db_query($conn, $sql);

if ($stmt && db_has_rows($stmt)) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white; font-size:13px;'>";
    echo "<tr style='background:#f1f5f9;'>
            <th>ID</th><th>Script No</th><th>Title</th><th>Status</th><th>Maker (created_by)</th><th>Assigned SPV (selected_spv)</th><th>Created At</th>
          </tr>";
    
    $spvsToCheck = [];
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        if (!empty($row['selected_spv'])) {
            $spvsToCheck[$row['selected_spv']] = true;
        }
        
        // Highlight active SPV tickets
        $rowStyle = ($row['status'] == 'CREATED') ? "background:#dcfce7;" : "";
        
        echo "<tr style='$rowStyle'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['script_number']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td><b>{$row['status']}</b></td>";
        echo "<td>{$row['created_by']}</td>";
        echo "<td><code style='color:blue;'>{$row['selected_spv']}</code></td>";
        
        $date = is_object($row['created_at']) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
        echo "<td>{$date}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><i>* Green rows are tickets waiting for SPV approval (status = CREATED).</i></p>";
    
    // 2. Cross Check SPVs in tbluser
    echo "<h3>2. SPV Check in `tbluser`</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white; font-size:13px;'>";
    echo "<tr style='background:#f1f5f9;'>
            <th>SPV ID in Ticket</th><th>Found in tbluser?</th><th>DEPT</th><th>JOB_FUNCTION</th><th>[GROUP]</th><th>AKTIF</th>
          </tr>";
          
    foreach (array_keys($spvsToCheck) as $spvId) {
        $checkUser = "SELECT USERID, FULLNAME, DEPT, JOB_FUNCTION, [GROUP], AKTIF FROM tbluser WHERE USERID = ?";
        $stmtUser = db_query($conn, $checkUser, [$spvId]);
        
        echo "<tr>";
        echo "<td><code>$spvId</code></td>";
        
        if ($stmtUser && $userRow = db_fetch_array($stmtUser, DB_FETCH_ASSOC)) {
            echo "<td style='color:green;'>✅ Yes (" . htmlspecialchars($userRow['FULLNAME']) . ")</td>";
            echo "<td>" . ($userRow['DEPT'] ?? 'NULL') . "</td>";
            echo "<td>" . ($userRow['JOB_FUNCTION'] ?? 'NULL') . "</td>";
            echo "<td>" . ($userRow['GROUP'] ?? 'NULL') . "</td>";
            echo "<td>" . ($userRow['AKTIF'] ?? 'NULL') . "</td>";
            
            // Validate if condition matches the SPV logic inside index.php
            $isSpv = (strtoupper(trim($userRow['JOB_FUNCTION'] ?? '')) === 'DIVISION HEAD');
            if (!$isSpv) {
                echo "<tr><td colspan='6' style='color:orange; background:#fff7ed; padding:10px;'>
                      ⚠️ <b>Warning:</b> This user's JOB_FUNCTION is not 'DIVISION HEAD'. 
                      The system might not recognize them as an SPV during login, so they might not see the SPV Dashboard!</td></tr>";
            }
        } else {
            echo "<td style='color:red;' colspan='5'>❌ <b>NOT FOUND in tbluser.</b> (Or case mismatched)</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    // 3. Why SPV can't see the tickets?
    echo "<h3>3. Diagnosis & Potential Causes</h3>";
    echo "<ul>
        <li><b>Cause A (Wrong Status):</b> The SPV logic only looks for <code>status = 'CREATED'</code>. If the ticket status is something else (like 'SUBMITTED' instead of 'CREATED'), the SPV won't see it. Check the status in the table above.</li>
        <li><b>Cause B (USERID Mismatch):</b> If the <code>selected_spv</code> contains extra spaces (e.g., <code>'SPV01 '</code> instead of <code>'SPV01'</code>), the SQL <code>selected_spv = ?</code> query will fail.</li>
        <li><b>Cause C (SPV Role Not Granted):</b> If the SPV logs in, their Dashboard only routes to SPV if their <code>JOB_FUNCTION</code> is <code>'DIVISION HEAD'</code>. If it isn't, they are routed to the Viewer/Library page and won't see the pending tickets list.</li>
    </ul>";

} else {
    echo "<p>No recent submitted tickets found.</p>";
}

echo "</body></html>";
?>
