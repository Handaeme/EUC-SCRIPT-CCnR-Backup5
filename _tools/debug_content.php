<?php
require_once 'config/database.php';
require_once 'app/models/RequestModel.php';

$db = new Database();
$conn = $db->getConnection();
$model = new RequestModel($conn);

// Get latest request
$sql = "SELECT TOP 1 * FROM script_request ORDER BY created_at DESC";
$stmt = sqlsrv_query($conn, $sql);
$req = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$req) {
    die("No requests found.");
}

echo "Request ID: " . $req['id'] . "\n";
echo "Script No: " . $req['script_number'] . "\n";
echo "Mode: " . $req['mode'] . "\n";

// Check Content
$content = $model->getPreviewContent($req['id']);
echo "Content Count: " . count($content) . "\n";
if (count($content) > 0) {
    echo "Content[0] Length: " . strlen($content[0]['content']) . "\n";
    echo "Content[0] SAMPLE: " . substr($content[0]['content'], 0, 100) . "...\n";
} else {
    echo "NO CONTENT FOUND IN DB.\n";
}

// Check Files
$files = $model->getFiles($req['id']);
echo "Files Count: " . count($files) . "\n";
