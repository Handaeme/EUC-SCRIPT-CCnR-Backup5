<?php
// debug_cleanup.php
// Access via: http://localhost/EUC-Script-CCnR-Migrasi/debug_cleanup.php

require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');
$config = require_once __DIR__ . '/config/database.php';

$serverName = $config['host'];
$connectionInfo = [
    "Database" => $config['dbname'],
    "UID" => $config['user'],
    "PWD" => $config['pass'],
    "TrustServerCertificate" => true
];
if (isset($config['options'])) {
    $connectionInfo = array_merge($connectionInfo, $config['options']);
}
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) die("DB Connection Failed");

// Fetch ONE record that has red color styling (revision marks)
$sql = "SELECT TOP 1 id, script_number, content FROM script_library 
        WHERE content LIKE '%color:%red%' 
           OR content LIKE '%color: red%'
           OR content LIKE '%#ef4444%' 
           OR content LIKE '%rgb(255%'
           OR content LIKE '%rgb(239%'";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    die("<h3>No records found with RED color styling either!</h3><p>Possible reasons:<ul><li>Data is already clean (Cached browser?)</li><li>Encoding mismatch (UTF-16 vs ASCII)</li><li>Column type is TEXT/IMAGE and LIKE needs CAST</li></ul>");
}

$original = $row['content'];
if (is_resource($original)) {
    $original = stream_get_contents($original);
}

echo "<html><body>";
echo "<h2>Debug Regex Cleaning</h2>";

echo "<h3>1. Raw Content (htmlspecialchars)</h3>";
echo "<textarea style='width:100%; height:150px;'>" . htmlspecialchars($original) . "</textarea>";

echo "<h3>2. Regex Test</h3>";

// TEST 1: Revision Span Regex
$regex1 = '/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is';
$clean1 = preg_replace($regex1, '$1', $original);

echo "<strong>Regex Used:</strong> " . htmlspecialchars($regex1) . "<br>";
echo "<strong>Match Found?</strong> " . ($clean1 !== $original ? "<span style='color:green'>YES</span>" : "<span style='color:red'>NO</span>") . "<br>";

if ($clean1 !== $original) {
    echo "<h3>3. Result Cleaned</h3>";
    echo "<textarea style='width:100%; height:150px;'>" . htmlspecialchars($clean1) . "</textarea>";
}

echo "</body></html>";
?>
