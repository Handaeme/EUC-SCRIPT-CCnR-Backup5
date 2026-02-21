<?php
// _test_search.php
require_once 'app/helpers/DbAdapter.php';
require_once 'config/database.php';
require_once 'app/models/RequestModel.php';

// Connect to DB
$conn = db_connect($config['host'], $connectionInfo);
if (!$conn) {
    die("Database connection failed.\n");
}

// Instantiate Model
$model = new RequestModel($conn);

// Test Search Keyword
$keyword = 'SC-'; // Common prefix, should return many
echo "Testing Search Keyword: '$keyword'\n";

$logs = $model->getAuditExportData(null, null, 'created_at', 'DESC', [], $keyword);
echo "Count with '$keyword': " . count($logs) . "\n";
if (count($logs) > 0) {
    echo "First Result: " . $logs[0]['ticket_id'] . " | " . $logs[0]['script_number'] . "\n";
}

// Test Specific Script Number
$keyword2 = '0037';
echo "\nTesting Search Keyword: '$keyword2'\n";
$logs2 = $model->getAuditExportData(null, null, 'created_at', 'DESC', [], $keyword2);
echo "Count with '$keyword2': " . count($logs2) . "\n";
foreach(array_slice($logs2, 0, 3) as $l) {
    echo "- Found: " . $l['script_number'] . "\n";
}

// Test Maker
$keyword3 = 'maker';
echo "\nTesting Search Keyword: '$keyword3'\n";
$logs3 = $model->getAuditExportData(null, null, 'created_at', 'DESC', [], $keyword3);
echo "Count with '$keyword3': " . count($logs3) . "\n";

?>
