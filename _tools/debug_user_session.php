<?php
/**
 * DEBUG USER SESSION & DB DATA
 * Use this to check why the name is not appearing in the header.
 */
session_start();
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🔍 Diagnostic User Display</h2>";

// 1. SESSION DATA
echo "<h3>1. Session Data</h3>";
echo "<div style='background:white; padding:15px; border-radius:8px; border:1px solid #e2e8f0;'>";
if (isset($_SESSION['user'])) {
    echo "<b>Nested Session (\$_SESSION['user']):</b><pre>";
    print_r($_SESSION['user']);
    echo "</pre>";
} else {
    echo "<p style='color:red;'>⚠️ \$_SESSION['user'] is NOT set.</p>";
}

echo "<b>Raw Session (\$_SESSION):</b><pre>";
// Filter out suspicious keys for safety if any, but since it's debug let's show all
$safeSession = $_SESSION;
unset($safeSession['password']); // just in case
print_r($safeSession);
echo "</pre></div>";

// 2. DATABASE DATA
echo "<h3>2. Database Check (tbluser)</h3>";
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    echo "<p style='color:red;'>❌ Database connection failed.</p>";
} else {
    $userid = $_SESSION['user']['userid'] ?? $_SESSION['NIK'] ?? null;
    
    if ($userid) {
        $sql = "SELECT USERID, FULLNAME, DEPT, JOB_FUNCTION, DIVISI, [GROUP], AKTIF FROM tbluser WHERE USERID = ?";
        $stmt = db_query($conn, $sql, [$userid]);
        
        if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            echo "<div style='background:#f0fdf4; padding:15px; border-radius:8px; border:1px solid #10b981;'>";
            echo "<b>Found user in database:</b><pre>";
            print_r($row);
            echo "</pre></div>";
            
            if (empty(trim($row['FULLNAME'] ?? ''))) {
                echo "<p style='color:orange;'>⚠️ <b>Issue Found:</b> FULLNAME column in database is EMPTY or NULL for this user.</p>";
            }
        } else {
            echo "<p style='color:red;'>⚠️ User <b>$userid</b> NOT FOUND in tbluser.</p>";
        }
    } else {
        echo "<p style='color:orange;'>⚠️ No UserID found in session to check database.</p>";
    }
}

echo "<h3>3. Common Fixes</h3>";
echo "<ul>
    <li>If <b>Session Data</b> has the name but <b>Nested Session</b> doesn't, we need to update the SSO mapping in <code>index.php</code>.</li>
    <li>If <b>Database Check</b> shows an empty FULLNAME, the data in <code>tbluser</code> needs to be updated.</li>
    <li>If <b>Raw Session</b> doesn't have the name at all, the office portal might be using a different index (e.g., NM_USER).</li>
</ul>";

echo "</body></html>";
?>
