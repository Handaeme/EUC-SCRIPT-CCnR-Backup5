<?php
require_once 'config/database.php';
require_once 'app/models/RequestModel.php';

$config = require __DIR__ . '/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

// Get a sample file upload request
$sql = "SELECT TOP 1 id FROM script_request WHERE mode = 'FILE_UPLOAD' AND status = 'LIBRARY' ORDER BY id DESC";
$stmt = sqlsrv_query($conn, $sql);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$row) {
    die("No file upload request found in library");
}

$requestId = $row['id'];

// Get preview content
$reqModel = new \App\Models\RequestModel($conn);
$content = $reqModel->getPreviewContent($requestId);

echo "<h2>Content Structure Debug</h2>";
echo "<p>Request ID: $requestId</p>";
echo "<p>Content rows: " . count($content) . "</p>";

foreach ($content as $idx => $row) {
    echo "<hr>";
    echo "<h3>Row $idx - Media: " . htmlspecialchars($row['media']) . "</h3>";
    echo "<p>Content length: " . strlen($row['content']) . " chars</p>";
    echo "<details>";
    echo "<summary>View HTML</summary>";
    echo "<pre>" . htmlspecialchars($row['content']) . "</pre>";
    echo "</details>";
    echo "<hr>";
    echo "<h4>Rendered:</h4>";
    echo $row['content'];
}

sqlsrv_close($conn);
?>
