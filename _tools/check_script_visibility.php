<?php
/**
 * Quick Check Tool: Script Visibility Logic
 * Verify if scripts are correctly hidden/shown based on Start Date & Active Status
 */

// 1. Setup Environment
$configFile = __DIR__ . '/../config/database.php';
$dbAdapter = __DIR__ . '/../app/helpers/DbAdapter.php';
$envLoader = __DIR__ . '/../app/helpers/EnvLoader.php';

if (!file_exists($configFile)) die("Error: config/database.php not found.");

// Load Environment Variables (Crucial for getenv to work)
if (file_exists($envLoader)) {
    require_once $envLoader;
    if (class_exists('App\Helpers\EnvLoader')) {
        App\Helpers\EnvLoader::load(__DIR__ . '/../.env');
    }
}

if (!file_exists($dbAdapter)) {
    // Try alternate path if layout differs
    $dbAdapter = __DIR__ . '/../app/core/Database.php';
    if (!file_exists($dbAdapter)) die("Error: DbAdapter/Database helper not found.");
}

require_once $dbAdapter;
$config = require $configFile;

// 2. Connect
$conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);

if (!$conn) {
    die("Database Connection Failed: " . print_r(db_errors(), true));
}

// 3. Query Library Items
echo "<h1>Script Visibility Check</h1>";
echo "<p>Current Server Date: <strong>" . date('Y-m-d H:i:s') . "</strong></p>";
echo "<p><a href='javascript:location.reload()'>Refresh</a></p>";

$sql = "SELECT l.id as lib_id, l.request_id, l.script_number, l.start_date, l.is_active, r.title 
        FROM script_library l 
        JOIN script_request r ON l.request_id = r.id 
        ORDER BY l.request_id DESC";

$stmt = db_query($conn, $sql);

if (!$stmt) {
    die("Query Failed: " . print_r(db_errors(), true));
}

echo "<table border='1' cellpadding='8' style='border-collapse:collapse; font-family:sans-serif;'>
    <tr style='background:#f1f5f9; text-align:left;'>
        <th>ID</th>
        <th>Script Number</th>
        <th>Title</th>
        <th>Start Date</th>
        <th>Is Active?</th>
        <th>Visible to Agent?</th>
        <th>Visible to Maker?</th>
        <th>Note</th>
    </tr>";

$count = 0;
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $count++;
    
    $id = $row['request_id'];
    $sn = $row['script_number'];
    $title = $row['title'];
    $startDate = $row['start_date']; // Can be string 'YYYY-MM-DD' or DateTime object depending on driver
    
    // Normalize Date
    $startDateStr = 'N/A';
    if ($startDate instanceof DateTime) {
        $startDateStr = $startDate->format('Y-m-d');
    } elseif (!empty($startDate)) {
        $startDateStr = $startDate;
    }
    
    $isActive = isset($row['is_active']) ? (int)$row['is_active'] : 1; // Default active if column null (legacy)
    
    // VISIBILITY LOGIC REPLICATION
    $today = date('Y-m-d');
    
    // Rule: Visible if Active AND (Start Date is NULL OR Start Date <= Today)
    $isEffective = ($startDateStr === 'N/A' || $startDateStr <= $today);
    $isVisibleAgent = ($isActive && $isEffective);
    
    // Maker always sees everything
    $isVisibleMaker = true; 
    
    // Styling
    $rowStyle = "";
    if (!$isVisibleAgent) $rowStyle = "background-color: #fef2f2; color: #991b1b;"; // Red tint for hidden
    else $rowStyle = "background-color: #ecfdf5; color: #065f46;"; // Green tint for visible
    
    echo "<tr style='$rowStyle'>";
    echo "<td>$id</td>";
    echo "<td>$sn</td>";
    echo "<td>" . htmlspecialchars($title) . "</td>";
    echo "<td>$startDateStr</td>";
    echo "<td>" . ($isActive ? 'YES' : 'NO') . "</td>";
    echo "<td><strong>" . ($isVisibleAgent ? 'YES' : 'NO') . "</strong></td>";
    echo "<td>YES</td>";
    
    $reasons = [];
    if (!$isActive) $reasons[] = "Inactive Status";
    if (!$isEffective) $reasons[] = "Future Date";
    
    echo "<td>" . (!empty($reasons) ? implode(', ', $reasons) : 'OK') . "</td>";
    echo "</tr>";
}

echo "</table>";
echo "<p>Total Scripts Checked: $count</p>";
?>
