<?php
// Disable output buffering
if (ob_get_level()) ob_end_clean();

echo "Starting Republish Tool...\n";

// Path Checks
$envLoaderPath = __DIR__ . '/../app/helpers/EnvLoader.php';
if (!file_exists($envLoaderPath)) die("Error: EnvLoader not found at $envLoaderPath\n");

require_once $envLoaderPath;

// Load Environment Variables
echo "Loading Environment...\n";
$envLoader = new App\Helpers\EnvLoader(__DIR__ . '/../.env');
$envLoader->load();

// Database Connection
$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');

echo "Connecting to DB ($dbHost)...\n";

$conn = sqlsrv_connect($dbHost, [
    "Database" => $dbName,
    "UID" => $dbUser,
    "PWD" => $dbPass,
    "CharacterSet" => "UTF-8"
]);

if (!$conn) {
    die("Connection Failed: " . print_r(sqlsrv_errors(), true));
}
echo "Connected.\n";

// Function to clean review marks (COPIED FROM RequestModel)
function cleanReviewMarks($html) {
    if (empty($html)) return $html;
    $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $html);
    $html = preg_replace('/<span[^>]*style="[^"]*color:\s*(red|#ef4444|rgb\(\s*255,\s*0,\s*0\s*\)|rgb\(\s*239,\s*68,\s*68\s*\))[^\"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    $html = preg_replace('/<span[^>]*style="[^"]*background(-color)?:\s*#fef08a[^"]*"[^>]*>(.*?)<\/span>/is', '$2', $html);
    $html = preg_replace('/<span[^>]*data-comment-[^=]*="[^"]*"[^>]*>/is', '<span>', $html);
    $html = preg_replace('/<span>\s*<\/span>/is', '', $html);
    return $html;
}

// Get ID
$requestId = $argv[1] ?? null;
if (!$requestId) {
    die("Usage: php republish_library.php <request_id>\n");
}

echo "Republishing Library Content for ID: $requestId\n";

// 1. Get Request Info (for Script Number & Version)
$sqlReq = "SELECT * FROM script_request WHERE id = ?";
$stmtReq = sqlsrv_query($conn, $sqlReq, [$requestId]);
if ($stmtReq === false || !sqlsrv_has_rows($stmtReq)) {
    die("Request ID not found.\n");
}
$req = sqlsrv_fetch_array($stmtReq, SQLSRV_FETCH_ASSOC);
echo "Found Request: " . $req['script_number'] . " (Mode: ". $req['mode'] .")\n";

// 2. Clear Existing Library Entries
echo "Clearing existing library entries...\n";
$sqlDel = "DELETE FROM script_library WHERE request_id = ?";
$stmtDel = sqlsrv_query($conn, $sqlDel, [$requestId]);
if ($stmtDel === false) die("Failed to clear library: " . print_r(sqlsrv_errors(), true));

// 3. Fetch Preview Content (Source)
echo "Fetching source content...\n";
$sqlSource = "SELECT * FROM script_preview_content WHERE request_id = ? ORDER BY id ASC";
$stmtSource = sqlsrv_query($conn, $sqlSource, [$requestId]);

if ($stmtSource === false) die("Failed to fetch source: " . print_r(sqlsrv_errors(), true));

$count = 0;
while ($row = sqlsrv_fetch_array($stmtSource, SQLSRV_FETCH_ASSOC)) {
    $content = $row['content'];
    
    // Handle binary string if needed
    if (is_resource($content)) {
        $content = stream_get_contents($content);
    }

    $cleanContent = cleanReviewMarks($content);
    $media = $row['media']; // This will capture "Robo CC", "Robo PL", etc.

    echo "Processing: $media... ";
    
    $sqlIns = "INSERT INTO script_library (request_id, script_number, media, content, version, created_at) VALUES (?, ?, ?, ?, ?, GETDATE())";
    $paramsIns = [$requestId, $req['script_number'], $media, $cleanContent, $req['version']];
    
    $stmtIns = sqlsrv_query($conn, $sqlIns, $paramsIns);
    if ($stmtIns === false) {
        echo "Error: " . print_r(sqlsrv_errors(), true) . "\n";
    } else {
        echo "Inserted.\n";
        $count++;
    }
}

echo "Done. Republished $count rows.\n";
?>
