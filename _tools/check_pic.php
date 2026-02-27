<?php
session_start();
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/helpers/DbAdapter.php';
$config = require __DIR__ . '/../config/database.php';

echo "<html><body style='font-family:sans-serif; padding:20px;'>";
echo "<h2>🔍 Diagnostic: PIC Users</h2>";

$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("<p style='color:red;'>❌ Database connection failed.</p>");
}

echo "<h3>1. Users where DEPT = 'PIC' (Current Query)</h3>";
$sql = "SELECT USERID, FULLNAME, DEPT, JOB_FUNCTION, DIVISI, [GROUP], AKTIF FROM tbluser WHERE UPPER(DEPT) = 'PIC' OR UPPER(JOB_FUNCTION) = 'PIC' OR UPPER(DIVISI) LIKE '%PIC%'";
$stmt = db_query($conn, $sql);

if ($stmt && db_has_rows($stmt)) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
    echo "<tr style='background:#f1f5f9;'><th>USERID</th><th>FULLNAME</th><th>DEPT</th><th>JOB_FUNCTION</th><th>DIVISI</th><th>GROUP</th><th>AKTIF</th></tr>";
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['USERID']}</td>";
        echo "<td>{$row['FULLNAME']}</td>";
        echo "<td>{$row['DEPT']}</td>";
        echo "<td>{$row['JOB_FUNCTION']}</td>";
        echo "<td>{$row['DIVISI']}</td>";
        echo "<td>{$row['GROUP']}</td>";
        echo "<td>{$row['AKTIF']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ Tidak ada user dengan tulisan 'PIC' di kolom DEPT, JOB_FUNCTION, atau DIVISI.</p>";
}

echo "<h3>2. What are the unique DEPTs in tbluser?</h3>";
$sql2 = "SELECT DISTINCT DEPT FROM tbluser WHERE AKTIF = 1";
$stmt2 = db_query($conn, $sql2);
if ($stmt2) {
    echo "<ul>";
    while ($row = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($row['DEPT'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
}

echo "<h3>3. What are the unique JOB_FUNCTIONs in tbluser?</h3>";
$sql3 = "SELECT DISTINCT JOB_FUNCTION FROM tbluser WHERE AKTIF = 1";
$stmt3 = db_query($conn, $sql3);
if ($stmt3) {
    echo "<ul>";
    while ($row = db_fetch_array($stmt3, DB_FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($row['JOB_FUNCTION'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
}

echo "</body></html>";
