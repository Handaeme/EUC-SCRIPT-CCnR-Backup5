<?php
/**
 * Test getRequestDetail logic for a child version
 */
require_once __DIR__ . '/app/helpers/DbAdapter.php';
require_once __DIR__ . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/.env');

// Mock RequestModel
require_once __DIR__ . '/app/models/RequestModel.php';

// Instantiate
$model = new App\Models\RequestModel();

// ID 90 is version 02
$idToCheck = 90; 

echo "Testing getRequestDetail($idToCheck)...\n";

// We need to reflect the method or just copy-paste the logic for testing if we can't instantiate easily
// Actually we can just run the logic manually here to see what the SQL produces.

$conn = db_connect(
    'localhost', 
    ['Database'=>'EUC_SCRIPT_MIGRASI','UID'=>'sa','PWD'=>'password123'] // Adjust if needed, but we can use the app's config
);
// Re-load config properly
$config = require __DIR__ . '/config/database.php';
$connectionInfo = ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']];
if(isset($config['options'])) $connectionInfo = array_merge($connectionInfo, $config['options']);
$conn = db_connect($config['host'], $connectionInfo);

if (!$conn) die("DB Connection Failed");

// 1. Get Request
$sql = "SELECT id, script_number FROM script_request WHERE id = ?";
$stmt = db_query($conn, $sql, [$idToCheck]);
$req = db_fetch_array($stmt, DB_FETCH_ASSOC);

if (!$req) die("Request $idToCheck not found");

$scriptNumber = $req['script_number']; // KONV-RC-12/02/26-0037-02
echo "Script Number: $scriptNumber\n";

// 2. Apply Logic
$basePattern = $scriptNumber . '-%';
$baseExact = $scriptNumber;
if (preg_match('/-(\d{2})$/', $scriptNumber)) {
    $baseExact = preg_replace('/-(\d{2})$/', '', $scriptNumber); // KONV-RC-12/02/26-0037
    $basePattern = $baseExact . '-%'; // KONV-RC-12/02/26-0037-%
}
echo "Base Exact: $baseExact\n";
echo "Base Pattern: $basePattern\n";

// 3. Execute Query
$sql = "SELECT a.id, a.action, a.created_at, r.script_number as req_sn 
        FROM script_audit_trail a 
        LEFT JOIN script_request r ON a.request_id = r.id
        WHERE (r.script_number = ? OR r.script_number LIKE ?)
        ORDER BY a.created_at ASC";

$stmt = db_query($conn, $sql, [$baseExact, $basePattern]);
$count = 0;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $count++;
    echo "[$count] {$row['action']} | ReqSN: {$row['req_sn']} | Date: " . 
         ($row['created_at'] instanceof DateTime ? $row['created_at']->format('Y-m-d H:i:s') : $row['created_at']) . "\n";
}

if ($count == 0) echo "NO LOGS FOUND!\n";
else echo "Found $count logs.\n";
