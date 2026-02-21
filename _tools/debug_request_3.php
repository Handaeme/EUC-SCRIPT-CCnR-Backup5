<?php
// Deep dive diagnostic for Request ID 3
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

echo "<h2>Deep Dive: Request ID 3 Diagnostic</h2>";

$testId = 3;

// 1. Check raw request data
echo "<h3>1. Raw Request Data (Request ID $testId):</h3>";
$sql1 = "SELECT * FROM script_request WHERE id = ?";
$stmt1 = sqlsrv_query($conn, $sql1, [$testId]);

if ($stmt1 === false) {
    echo "<div style='color:red;'>❌ Query FAILED</div><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else if (!sqlsrv_has_rows($stmt1)) {
    echo "<div style='color:red;'>❌ Request ID $testId NOT FOUND in database</div>";
} else {
    $row = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    foreach ($row as $key => $value) {
        if ($value instanceof DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }
        echo "<tr><td><b>$key</b></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";
    
    $created_by = $row['created_by'] ?? 'NULL';
    echo "<div style='background:#e3f2fd; padding:10px; margin-top:10px;'>";
    echo "<b>created_by value:</b> " . htmlspecialchars($created_by);
    echo "</div>";
}

// 2. Check if user exists
echo "<h3>2. Check Created By User:</h3>";
if (isset($created_by) && $created_by !== 'NULL') {
    $sql2 = "SELECT USERID, FULLNAME, [GROUP] FROM tbluser WHERE USERID = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$created_by]);
    
    if ($stmt2 === false) {
        echo "<div style='color:red;'>❌ Query FAILED</div><pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
    } else if (!sqlsrv_has_rows($stmt2)) {
        echo "<div style='color:orange;'>⚠️ User '$created_by' NOT FOUND in tbluser!</div>";
        echo "<p><b>This is likely the problem!</b> The LEFT JOIN will fail silently.</p>";
    } else {
        $user = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
        echo "<div style='color:green;'>✅ User found:</div>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    }
}

// 3. Test the actual query from getRequestById
echo "<h3>3. Test getRequestById Query (with JOIN):</h3>";
$sql3 = "SELECT r.*, u.[GROUP] as group_name, u.USERID as maker_userid, u.FULLNAME as maker_name 
         FROM script_request r
         LEFT JOIN tbluser u ON r.created_by = u.USERID
         WHERE r.id = ?";
$stmt3 = sqlsrv_query($conn, $sql3, [$testId]);

if ($stmt3 === false) {
    echo "<div style='color:red;'>❌ Query FAILED</div><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else if (!sqlsrv_has_rows($stmt3)) {
    echo "<div style='color:red;'>❌ Query returned NO ROWS (This is the bug!)</div>";
} else {
    echo "<div style='color:green;'>✅ Query returned data:</div>";
    $result = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
    echo "<pre>";
    print_r($result);
    echo "</pre>";
}

// 4. Conclusion
echo "<hr>";
echo "<h3>💡 Diagnosis:</h3>";
echo "<div style='background:#fff3cd; padding:15px; border-left:4px solid #ffc107;'>";

if (!isset($created_by) || $created_by === 'NULL') {
    echo "<p><b style='color:red;'>PROBLEM FOUND:</b> Request ID $testId has NULL or empty <code>created_by</code>!</p>";
    echo "<p><b>Solution:</b> Update script_request to set valid created_by value.</p>";
} else {
    echo "<p>If user '$created_by' was not found in tbluser above, that's the problem.</p>";
    echo "<p><b>Solution:</b></p>";
    echo "<ul>";
    echo "<li>Either create user '$created_by' in tbluser</li>";
    echo "<li>Or update Request ID $testId to use an existing user (e.g., MAKER01)</li>";
    echo "</ul>";
}

echo "</div>";

sqlsrv_close($conn);
?>
