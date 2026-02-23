<?php
require_once 'app/init.php';

echo "Checking Simulation Results...\n";
$sql = "SELECT id, ticket_id, created_at, title FROM script_request WHERE title LIKE 'RACE_TEST_%' ORDER BY id DESC";
$stmt = db_query($conn, $sql);

$count = 0;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $count++;
    echo "Found Request: ID=" . $row['id'] . " Title=" . $row['title'] . "\n";
}

if ($count === 0) {
    echo "No simulation data found. (Simulation script didn't run?)\n";
} else if ($count === 1) {
    echo "[PASS] Race Condition Prevented! Only 1 request found per run (assuming 1 run).\n";
} else {
    echo "[FAIL/CHECK] Found $count requests. Check if multiple runs or duplicates.\n";
}
