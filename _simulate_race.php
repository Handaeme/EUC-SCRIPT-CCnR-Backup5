<?php
// _simulate_race.php
// Script to simulate CONCURRENT requests (Race Condition) to Create Request

echo "Preparing Race Condition Simulation...\n";

// Configuration
$targetUrl = "http://localhost/EUC-Script-CCnR-Migrasi/index.php?controller=request&action=store";
// Adjust URL if needed based on your local setup
// If CLI, we can't easily hit controller without knowing auth cookie.
// So this script is best run from BROWSER or needs a valid Session ID.

// Alternative: We simulate the MODEL call directly since we are on the server.
// This is actually better because it tests the LOCK logic in isolation without HTTP overhead.

require_once 'app/init.php';
require_once 'app/models/RequestModel.php';

// Mock User Session
$_SESSION['user'] = [
    'userid' => 'TEST_RACE_USER',
    'full_name' => 'Tester Race Condition',
    'role' => 'Maker',
    'dept' => 'Testing'
];

echo "User: " . $_SESSION['user']['userid'] . "\n";

// Forking process is hard in Windows PHP.
// We will use pthreads if available, or just rely on multiple background processes via exec().

$numberOfRequests = 5;
$title = "RACE_TEST_" . time();

echo "Spawning $numberOfRequests concurrent processes to create request: '$title'...\n";

$pids = [];
for ($i = 0; $i < $numberOfRequests; $i++) {
    // We execute a small worker script to do the actual insert
    $cmd = "start /B php _worker_create.php \"$title\" \"TEST_RACE_USER\" > NUL";
    pclose(popen($cmd, "r"));
    echo "Spawned process $i\n";
}

echo "Waiting for processes to finish...\n";
sleep(3); // Wait a bit

// Check Database
echo "\nChecking Results in Database...\n";
$sql = "SELECT id, ticket_id, created_at FROM script_request WHERE title = ?";
$stmt = db_query($conn, $sql, [$title]);

$count = 0;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $count++;
    echo "Result $count: ID=" . $row['id'] . " Ticket=" . $row['ticket_id'] . " Time=" . ($row['created_at'] instanceof DateTime ? $row['created_at']->format('H:i:s.u') : $row['created_at']) . "\n";
}

if ($count === 1) {
    echo "\n[SUCCESS] Race Condition Prevented! Only 1 request created.\n";
} else if ($count === 0) {
    echo "\n[FAIL] No requests created (Check errors in worker).\n";
} else {
    echo "\n[FAIL] Race Condition Detected! $count requests created.\n";
}
