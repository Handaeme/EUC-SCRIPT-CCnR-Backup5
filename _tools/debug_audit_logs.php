<?php
// Debug Script to check Audit Trail for latest revision note
$root = dirname(__DIR__); // Get project root
require_once $root . '/app/helpers/EnvLoader.php';

// IMPORTANT: Load Environment Variables
if (class_exists('App\Helpers\EnvLoader')) {
    App\Helpers\EnvLoader::load($root . '/.env');
}

require_once $root . '/config/database.php';
require_once $root . '/app/helpers/DbAdapter.php';

// Init DB
$dbConfig = require $root . '/config/database.php';
$conn = db_connect($dbConfig['host'], ['Database' => $dbConfig['dbname'], 'UID' => $dbConfig['user'], 'PWD' => $dbConfig['pass']]);

if (!$conn) die("DB Connection Failed");

// Get latest 5 audit trails with MINOR_REVISION action
$sql = "SELECT TOP 5 id, request_id, action, user_role, user_id, details, created_at 
        FROM script_audit_trail 
        WHERE action = 'MINOR_REVISION' 
        ORDER BY id DESC";

$stmt = db_query($conn, $sql);
echo "<h1>Latest 5 Minor Revision Notes</h1>";
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>ReqID</th><th>Action</th><th>Role</th><th>User</th><th>Details (NOTE)</th><th>Time</th></tr>";

while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['request_id']}</td>";
    echo "<td>{$row['action']}</td>";
    echo "<td>{$row['user_role']}</td>";
    echo "<td>{$row['user_id']}</td>";
    echo "<td style='background:#f0fff0'><b>{$row['details']}</b></td>";
    
    // Format date safely
    $dateStr = is_object($row['created_at']) ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at'];
    echo "<td>{$dateStr}</td>";
    echo "</tr>";
}
echo "</table>";
