<?php
// cleanup_fix.php
// Place this in your root folder: d:\xampp\htdocs\EUC-Script-CCnR-Migrasi\cleanup_fix.php
// Access via: http://localhost/EUC-Script-CCnR-Migrasi/cleanup_fix.php

// Load Environment Variables (Crucial for getenv to work)
require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');

// Load Config
$config = require_once __DIR__ . '/config/database.php';

// Connect to DB
$serverName = $config['host'];
$connectionInfo = [
    "Database" => $config['dbname'],
    "UID" => $config['user'],
    "PWD" => $config['pass'],
    "TrustServerCertificate" => true
];
// Merge extra options if any
if (isset($config['options'])) {
    $connectionInfo = array_merge($connectionInfo, $config['options']);
}

$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("<h3>Database Connection Failed</h3><pre>" . print_r(sqlsrv_errors(), true) . "</pre>");
}

echo "<html><body style='font-family:sans-serif; padding:20px;'>";
echo "<h1>🧹 Library Cleaning Tool</h1>";

function cleanReviewMarks($html) {
    if (empty($html)) return $html;
    $original = $html;

    // 1. Remove revision spans (class contains 'revision-span')
    // Matches: <span ... class="... revision-span ..."> ... </span>
    $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    
    // 2. Remove inline comment spans (class contains 'inline-comment')
    $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    
    // 3. Remove any span with red color styling (Aggressive match for color: red/rbg)
    // Matches attributes in any order. The style part targets color: followed by red-ish values
    $html = preg_replace('/<span[^>]*style="[^"]*color:\s*(red|#ef4444|rgb\(\s*255,\s*0,\s*0\s*\)|rgb\(\s*239,\s*68,\s*68\s*\))[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    
    // 4. Remove any span with yellow background styling (Strict or Loose)
    $html = preg_replace('/<span[^>]*style="[^"]*background(-color)?:\s*#fef08a[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    
    // 5. Cleanup: Remove data-comment attributes from ANY tag (just in case)
    $html = preg_replace('/\sdata-comment-[a-z]+="[^"]*"/i', '', $html);
    $html = preg_replace('/\sid="rev-[0-9]+"/i', '', $html); // Clean rev attributes
    
    // 6. Remove empty spans
    $html = preg_replace('/<span>(.*?)<\/span>/is', '$1', $html);
    $html = preg_replace('/<span\s*>(.*?)<\/span>/is', '$1', $html); 

    // REPEAT CLEANING? Sometimes spans are nested (e.g. revision inside inline-comment)
    // Run one more pass for safety if length changed
    if (strlen($html) !== strlen($original)) {
         $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
         $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    }
    
    return $html;
}

// Fetch all library entries
$sql = "SELECT id, script_number, content FROM script_library";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

// Upgrade to Dashboard
$search = $_GET['search'] ?? '';
$searchSql = "";
$params = [];
if (!empty($search)) {
    $searchSql = "WHERE script_number LIKE ? OR content LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$sql = "SELECT id, script_number, content FROM script_library $searchSql ORDER BY id DESC";
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

echo "<h2>🧹 Library Cleaning Dashboard</h2>";
echo "<form method='GET'><input type='text' name='search' placeholder='Search Script No / Content' value='".htmlspecialchars($search)."' style='padding:5px; width:300px;'> <button type='submit' style='padding:5px;'>Search</button> <a href='cleanup_fix.php'>Reset</a></form><br>";

echo "<table border='1' cellpadding='5' style='border-collapse:collapse; width:100%; font-size:12px;'>";
echo "<tr style='background:#eee;'><th>ID</th><th>Script No</th><th>Content Preview (Raw)</th><th>Status</th><th>Action</th></tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['id'];
    $original = $row['content'];
    if (is_resource($original)) $original = stream_get_contents($original);
    
    // Check Status
    $clean = cleanReviewMarks($original);
    $isDirty = ($clean !== $original);
    
    // Detect specifically what makes it dirty
    $reason = "";
    if (preg_match('/revision-span/', $original)) $reason .= "[revision-span] ";
    if (preg_match('/color:\s*red/', $original)) $reason .= "[color:red] ";
    if (preg_match('/rgb\(\s*239/', $original)) $reason .= "[rgb(239)] ";
    
    $status = $isDirty ? "<span style='color:red; font-weight:bold;'>DIRTY $reason</span>" : "<span style='color:green; font-weight:bold;'>CLEAN</span>";
    
    // Action: Clean specifically this ID
    if (isset($_POST['clean_id']) && $_POST['clean_id'] == $id) {
        $updateSql = "UPDATE script_library SET content = ? WHERE id = ?";
        sqlsrv_query($conn, $updateSql, [$clean, $id]);
        echo "<tr><td colspan='5' style='background:#dcfce7; text-align:center;'>✅ ID $id Cleaned! Refresh to see status.</td></tr>";
        $status = "<span style='color:blue; font-weight:bold;'>JUST CLEANED</span>";
        $original = $clean; // Update visual
    }

    echo "<tr>";
    echo "<td>{$id}</td>";
    echo "<td>{$row['script_number']}</td>";
    echo "<td><textarea style='width:500px; height:300px; font-size:11px; white-space:pre-wrap;'>" . htmlspecialchars($original) . "</textarea></td>";
    echo "<td>{$status}</td>";
    echo "<td>";
    if ($isDirty) {
        echo "<form method='POST' style='margin:0;'>
              <input type='hidden' name='clean_id' value='$id'>
              <button type='submit' style='background:red; color:white; cursor:pointer;'>CLEAN NOW</button>
              </form>";
    } else {
        echo "OK";
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</body></html>";
exit; // Stop running old logic
