<?php
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
$envLoader = new App\Helpers\EnvLoader(__DIR__ . '/../.env');
$envLoader->load();

$conn = sqlsrv_connect(getenv('DB_HOST'), [
    "Database" => getenv('DB_NAME'),
    "UID" => getenv('DB_USER'),
    "PWD" => getenv('DB_PASS')
]);

if (!$conn) die(print_r(sqlsrv_errors(), true));

echo "Scanning for incomplete Library entries (File Uploads only)...\n";

$sql = "SELECT l.request_id, r.script_number, COUNT(l.id) as lib_count 
        FROM script_library l
        JOIN script_request r ON l.request_id = r.id
        WHERE r.mode = 'FILE_UPLOAD'
        GROUP BY l.request_id, r.script_number
        HAVING COUNT(l.id) = 1";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) die(print_r(sqlsrv_errors(), true));

$matches = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rid = $row['request_id'];
    
    // Check Source
    $chk = sqlsrv_query($conn, "SELECT COUNT(*) as cnt FROM script_preview_content WHERE request_id = ?", [$rid]);
    $res = sqlsrv_fetch_array($chk, SQLSRV_FETCH_ASSOC);
    $sourceCount = $res['cnt'];
    
    if ($sourceCount > 1) {
        $matches[] = [
            'id' => $rid,
            'number' => $row['script_number'],
            'lib' => $row['lib_count'],
            'source' => $sourceCount
        ];
    }
}

echo "Found " . count($matches) . " potentially broken entries:\n";
foreach ($matches as $m) {
    echo "- ID {$m['id']} ({$m['number']}): Lib={$m['lib']}, Source={$m['source']}\n";
}
?>
