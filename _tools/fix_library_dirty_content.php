<?php
// fix_library_dirty_content.php
// Utility to clean existing library content that has red text
require_once __DIR__ . '/../app/config/database.php';

// Connect to DB
$serverName = DB_HOST;
$connectionOptions = array(
    "Database" => DB_NAME,
    "Uid" => DB_USER,
    "PWD" => DB_PASS,
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

echo "Connected to DB. Scanning for dirty library content...\n";

// Function to clean review marks (Duplicated from RequestModel for standalone use)
function cleanReviewMarks($html) {
    if (empty($html)) return $html;
    
    // 1. Remove revision spans (class contains 'revision-span')
    // Matches: <span ... class="... revision-span ..."> ... </span>
    $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    
    // 2. Remove inline comment spans (class contains 'inline-comment')
    $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    
    // 3. Remove any span with red color styling (Aggressive match for color: red/rbg)
    // Matches attributes in any order. The style part targets color: followed by red-ish values
    // We use a broader pattern for style content to be safe
    // FIX: Included all variations
    $html = preg_replace('/<span[^>]*style="[^"]*color:\s*(red|#ef4444|rgb\(\s*255,\s*0,\s*0\s*\)|rgb\(\s*239,\s*68,\s*68\s*\))[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    
    // 4. Remove any span with yellow background styling
    $html = preg_replace('/<span[^>]*style="[^"]*background(-color)?:\s*#fef08a[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    
    // 5. Cleanup: Remove data-comment attributes from ANY tag (just in case)
    $html = preg_replace('/\sdata-comment-[a-z]+="[^"]*"/i', '', $html);
    $html = preg_replace('/\sid="rev-[0-9]+"/i', '', $html); // Clean rev attributes
    
    // 6. Remove empty spans (recursively if needed, but one pass is usually enough for library)
    $html = preg_replace('/<span>(.*?)<\/span>/is', '$1', $html);
    $html = preg_replace('/<span\s*>(.*?)<\/span>/is', '$1', $html); // Empty attributes
    
    return $html;
}

// Fetch all library entries
$sql = "SELECT id, content FROM script_library";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$count = 0;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $original = $row['content'];
    
    // Check if it's a stream (SQL Server TEXT/NVARCHAR(MAX))
    if (is_resource($original)) {
        $original = stream_get_contents($original);
    }
    
    $clean = cleanReviewMarks($original);
    
    if ($clean !== $original) {
        $id = $row['id'];
        // Update DB
        $updateSql = "UPDATE script_library SET content = ? WHERE id = ?";
        $updateStmt = sqlsrv_query($conn, $updateSql, [$clean, $id]);
        if ($updateStmt === false) {
             echo "Failed to update ID $id\n";
             print_r(sqlsrv_errors());
        } else {
             echo "Cleaned ID: $id\n";
             $count++;
        }
    }
}

echo "Done! Cleaned $count records.\n";
?>
