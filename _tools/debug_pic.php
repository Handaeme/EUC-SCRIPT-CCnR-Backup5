<?php
/**
 * DEBUG PIC DROPDOWN - V2
 * Tests multiple query approaches to find why PIC users don't appear.
 */
session_start();
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px; background:#f8fafc;'>";
echo "<h2>🔍 Debug PIC Dropdown</h2>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
if (!$conn) die("<p style='color:red;'>❌ DB connection failed</p>");

// Test 1: Exact match (current query)
echo "<h3>Test 1: UPPER(DEPT) = 'PIC' (Original)</h3>";
$sql1 = "SELECT USERID, FULLNAME, DEPT FROM tbluser WHERE UPPER(DEPT) = 'PIC'";
$stmt1 = db_query($conn, $sql1);
$count1 = 0;
if ($stmt1) { while ($r = db_fetch_array($stmt1, DB_FETCH_ASSOC)) { $count1++; echo "<p>Found: {$r['USERID']} - {$r['FULLNAME']} - DEPT=[{$r['DEPT']}]</p>"; } }
echo $count1 == 0 ? "<p style='color:red;'>❌ No results</p>" : "";

// Test 2: LIKE match
echo "<h3>Test 2: DEPT LIKE '%PIC%'</h3>";
$sql2 = "SELECT USERID, FULLNAME, DEPT FROM tbluser WHERE DEPT LIKE '%PIC%'";
$stmt2 = db_query($conn, $sql2);
$count2 = 0;
if ($stmt2) { while ($r = db_fetch_array($stmt2, DB_FETCH_ASSOC)) { $count2++; echo "<p>Found: {$r['USERID']} - {$r['FULLNAME']} - DEPT=[{$r['DEPT']}]</p>"; } }
echo $count2 == 0 ? "<p style='color:red;'>❌ No results</p>" : "";

// Test 3: LTRIM RTRIM (SQL Server compatible TRIM)
echo "<h3>Test 3: LTRIM(RTRIM(UPPER(DEPT))) = 'PIC'</h3>";
$sql3 = "SELECT USERID, FULLNAME, DEPT FROM tbluser WHERE LTRIM(RTRIM(UPPER(DEPT))) = 'PIC'";
$stmt3 = db_query($conn, $sql3);
$count3 = 0;
if ($stmt3) { while ($r = db_fetch_array($stmt3, DB_FETCH_ASSOC)) { $count3++; echo "<p>Found: {$r['USERID']} - {$r['FULLNAME']} - DEPT=[{$r['DEPT']}]</p>"; } }
echo $count3 == 0 ? "<p style='color:red;'>❌ No results</p>" : "";

// Test 4: Check specific user EC33067X
echo "<h3>Test 4: Direct lookup for EC33067X</h3>";
$sql4 = "SELECT USERID, FULLNAME, DEPT, JOB_FUNCTION, AKTIF, LEN(DEPT) as dept_length FROM tbluser WHERE USERID = 'EC33067X'";
$stmt4 = db_query($conn, $sql4);
if ($stmt4 && $r = db_fetch_array($stmt4, DB_FETCH_ASSOC)) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white;'>";
    echo "<tr><th>Field</th><th>Value</th><th>Hex Dump</th></tr>";
    echo "<tr><td>USERID</td><td>[{$r['USERID']}]</td><td>" . bin2hex($r['USERID']) . "</td></tr>";
    echo "<tr><td>FULLNAME</td><td>[{$r['FULLNAME']}]</td><td>-</td></tr>";
    echo "<tr><td>DEPT</td><td>[{$r['DEPT']}]</td><td>" . bin2hex($r['DEPT']) . "</td></tr>";
    echo "<tr><td>DEPT Length</td><td>{$r['dept_length']}</td><td>-</td></tr>";
    echo "<tr><td>JOB_FUNCTION</td><td>[{$r['JOB_FUNCTION']}]</td><td>-</td></tr>";
    echo "<tr><td>AKTIF</td><td>[{$r['AKTIF']}]</td><td>-</td></tr>";
    echo "</table>";
    
    // Key insight
    $deptLen = $r['dept_length'];
    if ($deptLen != 3) {
        echo "<p style='color:orange;'>⚠️ DEPT has length $deptLen instead of 3. There are hidden characters!</p>";
    }
    if ($r['AKTIF'] != 1) {
        echo "<p style='color:red;'>⚠️ User is NOT ACTIVE (AKTIF={$r['AKTIF']}). Query filters by AKTIF=1 in some places!</p>";
    }
} else {
    echo "<p style='color:red;'>❌ User EC33067X not found in this database!</p>";
}

// Test 5: Show ALL distinct DEPT values with their lengths
echo "<h3>Test 5: All DEPT values with character lengths</h3>";
$sql5 = "SELECT DISTINCT DEPT, LEN(DEPT) as dept_len, COUNT(*) as user_count FROM tbluser GROUP BY DEPT ORDER BY DEPT";
$stmt5 = db_query($conn, $sql5);
if ($stmt5) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; background:white;'>";
    echo "<tr style='background:#f1f5f9;'><th>DEPT Value</th><th>String Length</th><th>Users Count</th><th>Hex</th></tr>";
    while ($r = db_fetch_array($stmt5, DB_FETCH_ASSOC)) {
        $hex = $r['DEPT'] ? bin2hex($r['DEPT']) : 'NULL';
        $highlight = (stripos($r['DEPT'] ?? '', 'PIC') !== false) ? "background:#dcfce7;" : "";
        echo "<tr style='$highlight'><td>[{$r['DEPT']}]</td><td>{$r['dept_len']}</td><td>{$r['user_count']}</td><td><code>$hex</code></td></tr>";
    }
    echo "</table>";
}

// Test 6: Try the actual getPICs query from RequestModel
echo "<h3>Test 6: Actual getPICs() Query (from RequestModel)</h3>";
$sql6 = "SELECT USERID as userid, FULLNAME as fullname FROM tbluser WHERE LTRIM(RTRIM(UPPER(DEPT))) = 'PIC' OR UPPER(DEPT) LIKE '%PIC%' ORDER BY FULLNAME ASC";
$stmt6 = db_query($conn, $sql6);
$count6 = 0;
if ($stmt6) { 
    while ($r = db_fetch_array($stmt6, DB_FETCH_ASSOC)) { 
        $count6++; 
        echo "<p style='color:green;'>✅ {$r['userid']} - {$r['fullname']}</p>"; 
    } 
}
echo $count6 == 0 ? "<p style='color:red;'>❌ No results - PIC dropdown will be EMPTY</p>" : "<p><b>Total: $count6 PIC(s) found</b></p>";

echo "</body></html>";
