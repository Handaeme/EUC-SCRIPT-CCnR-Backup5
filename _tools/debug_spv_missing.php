<?php
/**
 * DEBUG MISSING SPV TICKETS - V3 (Deep Diagnostic)
 * Checks: Database name, table existence, row counts, and sample data.
 */
session_start();
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🔍 Deep Diagnostic: Database & Tickets</h2>";

// 0. Show Database Config
echo "<h3>0. Database Configuration</h3>";
echo "<div style='background:white; padding:15px; border-radius:8px; border:1px solid #e2e8f0; font-size:13px;'>";
echo "<b>Host:</b> <code>" . htmlspecialchars($config['host'] ?? 'NOT SET') . "</code><br>";
echo "<b>Database:</b> <code>" . htmlspecialchars($config['dbname'] ?? 'NOT SET') . "</code><br>";
echo "<b>User:</b> <code>" . htmlspecialchars($config['user'] ?? 'NOT SET') . "</code><br>";
echo "<b>Driver:</b> <code>" . (function_exists('db_driver_info') ? json_encode(db_driver_info()) : 'N/A') . "</code><br>";
echo "</div>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    echo "<p style='color:red;'>❌ Database connection FAILED!</p><pre>";
    print_r(db_errors());
    echo "</pre>";
    die("</body></html>");
}

echo "<p style='color:green;'>✅ Database connection OK</p>";

// 1. Check which tables exist and row counts
echo "<h3>1. Table Existence & Row Counts</h3>";
$tables = ['tbluser', 'script_request', 'script_library', 'script_audit_trail', 'script_preview_content', 'script_files'];

echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white; font-size:13px;'>";
echo "<tr style='background:#f1f5f9;'><th>Table</th><th>Exists?</th><th>Row Count</th></tr>";

foreach ($tables as $table) {
    $countSql = "SELECT COUNT(*) as cnt FROM $table";
    $stmt = db_query($conn, $countSql);
    
    if ($stmt && $row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        $cnt = $row['cnt'] ?? $row['CNT'] ?? 0;
        $color = $cnt > 0 ? 'green' : 'orange';
        echo "<tr><td><b>$table</b></td><td style='color:green;'>✅ Yes</td><td style='color:$color;'><b>$cnt</b></td></tr>";
    } else {
        echo "<tr><td><b>$table</b></td><td style='color:red;'>❌ Not Found / Error</td><td>-</td></tr>";
    }
}
echo "</table>";

// 2. Show ALL tickets (no filter)
echo "<h3>2. ALL Tickets in <code>script_request</code></h3>";
$sql = "SELECT TOP 20 id, script_number, ticket_id, title, status, created_by, selected_spv, created_at 
        FROM script_request ORDER BY id DESC";
$stmt = db_query($conn, $sql);

if ($stmt && db_has_rows($stmt)) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white; font-size:13px;'>";
    echo "<tr style='background:#f1f5f9;'>
            <th>ID</th><th>Script No</th><th>Ticket ID</th><th>Title</th><th>Status</th><th>Maker</th><th>SPV</th><th>Created At</th>
          </tr>";
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        $rowStyle = ($row['status'] == 'CREATED') ? "background:#dcfce7;" : "";
        $date = is_object($row['created_at']) ? $row['created_at']->format('Y-m-d H:i:s') : ($row['created_at'] ?? 'NULL');
        echo "<tr style='$rowStyle'>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['script_number']}</td>";
        echo "<td>{$row['ticket_id']}</td>";
        echo "<td>" . htmlspecialchars($row['title'] ?? '') . "</td>";
        echo "<td><b>{$row['status']}</b></td>";
        echo "<td>{$row['created_by']}</td>";
        echo "<td><code>{$row['selected_spv']}</code></td>";
        echo "<td>$date</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='background:#fef2f2; padding:15px; border-radius:8px; border:1px solid #fca5a5; color:#991b1b;'>";
    echo "<b>⚠️ Tabel <code>script_request</code> KOSONG.</b><br>";
    echo "Tidak ada satupun tiket di database ini. Kemungkinan:<br>";
    echo "<ol>
        <li><b>Database yang dipakai berbeda</b> dengan yang digunakan oleh aplikasi EUC Script utama.</li>
        <li><b>Proses submit gagal</b> dan data tidak pernah tersimpan.</li>
        <li><b>Tabel sudah di-reset / belum dimigrasi</b> ke server ini.</li>
    </ol>";
    echo "</div>";
}

// 3. Show sample users
echo "<h3>3. Sample Users in <code>tbluser</code></h3>";
$sqlUsers = "SELECT TOP 10 USERID, FULLNAME, DEPT, JOB_FUNCTION, DIVISI, [GROUP], AKTIF FROM tbluser";
$stmtUsers = db_query($conn, $sqlUsers);

if ($stmtUsers && db_has_rows($stmtUsers)) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white; font-size:13px;'>";
    echo "<tr style='background:#f1f5f9;'><th>USERID</th><th>FULLNAME</th><th>DEPT</th><th>JOB_FUNCTION</th><th>DIVISI</th><th>GROUP</th><th>AKTIF</th></tr>";
    while ($row = db_fetch_array($stmtUsers, DB_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><code>{$row['USERID']}</code></td>";
        echo "<td>" . htmlspecialchars($row['FULLNAME'] ?? 'NULL') . "</td>";
        echo "<td>{$row['DEPT']}</td>";
        echo "<td>{$row['JOB_FUNCTION']}</td>";
        echo "<td>" . htmlspecialchars($row['DIVISI'] ?? '') . "</td>";
        echo "<td>{$row['GROUP']}</td>";
        echo "<td>{$row['AKTIF']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Tabel tbluser juga kosong atau tidak bisa diakses!</p>";
}

// 4. Current Session Info
echo "<h3>4. Current User Session</h3>";
echo "<div style='background:white; padding:15px; border-radius:8px; border:1px solid #e2e8f0;'><pre>";
$safeSession = $_SESSION;
unset($safeSession['password']);
print_r($safeSession);
echo "</pre></div>";

echo "</body></html>";
?>
