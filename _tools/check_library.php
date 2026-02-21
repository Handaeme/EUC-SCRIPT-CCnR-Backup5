<?php
// Check script_library details
require_once __DIR__ . '/../app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load(__DIR__ . '/../.env');

$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

echo "<h2>Script Library Detail Check</h2>";

// 1. Check script_library data
echo "<h3>Script Library Table:</h3>";
$sql = "SELECT l.id as library_id, l.request_id, l.script_number, r.ticket_id, r.title, r.status 
        FROM script_library l
        LEFT JOIN script_request r ON l.request_id = r.id
        ORDER BY l.id DESC";
        
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    echo "<div style='color:red;'>Error:</div><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Library ID</th><th>Request ID</th><th>Ticket ID</th><th>Script Number</th><th>Title</th><th>Status</th></tr>";
    
    $count = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>" . $row['library_id'] . "</td>";
        echo "<td>" . $row['request_id'] . "</td>";
        echo "<td>" . ($row['ticket_id'] ?? '-') . "</td>";
        echo "<td>" . ($row['script_number'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($row['title'] ?? '-') . "</td>";
        echo "<td>" . ($row['status'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($count == 0) {
        echo "<p style='color:orange;'>⚠️ Library is EMPTY!</p>";
    }
}

// 2. Check what request_ids exist
echo "<h3>All Request IDs (for comparison):</h3>";
$sql2 = "SELECT id, ticket_id, title, status FROM script_request ORDER BY id DESC";
$stmt2 = sqlsrv_query($conn, $sql2);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Request ID</th><th>Ticket ID</th><th>Title</th><th>Status</th></tr>";
while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['ticket_id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// 3. Suggestion
echo "<hr>";
echo "<h3>💡 Kesimpulan & Saran:</h3>";
echo "<div style='background:#fff3cd; padding:15px; border-left:4px solid #ffc107;'>";
echo "<p>Jika Library kosong atau hanya ada 2 item, berarti:</p>";
echo "<ul>";
echo "<li>Request yang lain masih status <b>DRAFT</b> atau <b>CREATED</b></li>";
echo "<li>Belum ada yang sampai status <b>LIBRARY</b> (sudah dipublish)</li>";
echo "</ul>";
echo "<p><b>Solusi:</b></p>";
echo "<ul>";
echo "<li>Untuk testing, bisa approve salah satu request sampai masuk Library</li>";
echo "<li>Atau buat request baru dan approve sampai selesai</li>";
echo "</ul>";
echo "</div>";

sqlsrv_close($conn);
?>
