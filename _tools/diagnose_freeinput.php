<!DOCTYPE html>
<html>
<head>
    <title>Free Input Debug - SC-0009</title>
    <style>
        body { font-family: 'Courier New', monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { background: #252526; border: 1px solid #3e3e42; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .section h2 { color: #4ec9b0; border-bottom: 2px solid #3e3e42; padding-bottom: 10px; margin-top: 0; }
        .info { color: #ce9178; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warn { color: #dcdcaa; }
        pre { background: #1e1e1e; padding: 15px; border-radius: 4px; border: 1px solid #3e3e42; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #3e3e42; }
        th { background: #2d2d30; color: #4ec9b0; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #4ec9b0; color: #1e1e1e; }
        .badge-error { background: #f48771; color: #1e1e1e; }
        .badge-warn { background: #dcdcaa; color: #1e1e1e; }
    </style>
</head>
<body>

<h1>🔍 Free Input Debug Tool</h1>

<?php
require_once __DIR__ . '/../config/database.php';

$config = require __DIR__ . '/../config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    echo '<div class="section"><p class="error">❌ Database Connection Failed</p><pre>';
    print_r(sqlsrv_errors());
    echo '</pre></div>';
    exit;
}

$requestId = isset($_GET['id']) ? intval($_GET['id']) : 9;

echo "<div class='section'>";
echo "<h2>Testing Request ID: <span class='info'>$requestId</span></h2>";
echo "</div>";

// 1. Get Request Metadata
echo "<div class='section'>";
echo "<h2>1️⃣ Request Metadata (script_request)</h2>";
$sql = "SELECT * FROM script_request WHERE id = ?";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);

if ($stmt && sqlsrv_has_rows($stmt)) {
    $request = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    echo "<table>";
    echo "<tr><th>Column</th><th>Value</th></tr>";
    foreach ($request as $key => $value) {
        if (is_object($value) && $value instanceof DateTime) {
            $value = $value->format('Y-m-d H:i:s');
        }
        $displayValue = is_null($value) ? '<span class="warn">NULL</span>' : htmlspecialchars($value);
        echo "<tr><td><strong>$key</strong></td><td>$displayValue</td></tr>";
    }
    echo "</table>";
    
    $mode = $request['mode'] ?? 'UNKNOWN';
    if ($mode === 'FREE_INPUT') {
        echo "<p class='success'>✅ Confirmed: Mode = FREE_INPUT</p>";
    } else {
        echo "<p class='error'>⚠️ Warning: Mode = $mode (Expected FREE_INPUT)</p>";
    }
} else {
    echo "<p class='error'>❌ Request not found</p>";
}
echo "</div>";

// 2. Check script_preview_content (Tier 1)
echo "<div class='section'>";
echo "<h2>2️⃣ Tier 1: script_preview_content</h2>";
$sql = "SELECT id, media, 
        SUBSTRING(content, 1, 100) as content_preview,
        updated_by,
        CONVERT(varchar, updated_at, 120) as updated_at
        FROM script_preview_content 
        WHERE request_id = ?
        ORDER BY updated_at DESC";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);

if ($stmt === false) {
    echo "<p class='error'>❌ Query Failed</p><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    
    if (count($rows) > 0) {
        echo "<p class='success'>✅ Found " . count($rows) . " row(s)</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Media</th><th>Content Preview</th><th>Updated By</th><th>Updated At</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td><strong>{$row['media']}</strong></td>";
            echo "<td>" . htmlspecialchars($row['content_preview']) . "...</td>";
            echo "<td>{$row['updated_by']}</td>";
            echo "<td>{$row['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'><span class='badge badge-success'>TIER 1 SUCCESS</span> Content should display!</p>";
    } else {
        echo "<p class='warn'>⚠️ No data found - Moving to Tier 2</p>";
    }
}
echo "</div>";

// 3. Check script_library (Tier 2)
echo "<div class='section'>";
echo "<h2>3️⃣ Tier 2: script_library</h2>";
$sql = "SELECT id, media, 
        SUBSTRING(content, 1, 100) as content_preview,
        CONVERT(varchar, created_at, 120) as created_at
        FROM script_library 
        WHERE request_id = ?
        ORDER BY created_at DESC";
$stmt = sqlsrv_query($conn, $sql, [$requestId]);

if ($stmt === false) {
    echo "<p class='error'>❌ Query Failed</p><pre>";
    print_r(sqlsrv_errors());
    echo "</pre>";
} else {
    $rows = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $rows[] = $row;
    }
    
    if (count($rows) > 0) {
        echo "<p class='success'>✅ Found " . count($rows) . " row(s)</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Media</th><th>Content Preview</th><th>Created At</th></tr>";
        foreach ($rows as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td><strong>{$row['media']}</strong></td>";
            echo "<td>" . htmlspecialchars($row['content_preview']) . "...</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p class='success'><span class='badge badge-success'>TIER 2 SUCCESS</span> Fallback should work!</p>";
    } else {
        echo "<p class='warn'>⚠️ No data found - Moving to Tier 3</p>";
    }
}
echo "</div>";

// 4. Check Legacy Columns (Tier 3)
echo "<div class='section'>";
echo "<h2>4️⃣ Tier 3: Legacy Columns</h2>";

if (isset($request)) {
    if (isset($request['content']) && !empty($request['content'])) {
        echo "<p class='success'>✅ Found legacy column: <strong>content</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($request['content'], 0, 200)) . "...</pre>";
        echo "<p class='success'><span class='badge badge-success'>TIER 3 SUCCESS</span> Legacy fallback should work!</p>";
    } elseif (isset($request['script_content']) && !empty($request['script_content'])) {
        echo "<p class='success'>✅ Found legacy column: <strong>script_content</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($request['script_content'], 0, 200)) . "...</pre>";
        echo "<p class='success'><span class='badge badge-success'>TIER 3 SUCCESS</span> Legacy fallback should work!</p>";
    } else {
        echo "<p class='error'>❌ No legacy columns found (content, script_content)</p>";
        echo "<p class='warn'>Available columns in request:</p><ul>";
        foreach ($request as $key => $value) {
            echo "<li>$key</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ Request data not available</p>";
}
echo "</div>";

// 5. Diagnosis
echo "<div class='section'>";
echo "<h2>🩺 Diagnosis</h2>";

$tier1 = isset($rows) && count($rows) > 0;
$hasLibrary = false; // From tier 2 check
$hasLegacy = (isset($request['content']) && !empty($request['content'])) || 
             (isset($request['script_content']) && !empty($request['script_content']));

if ($tier1 || $hasLibrary || $hasLegacy) {
    echo "<p class='success'>✅ <strong>DATA FOUND!</strong> Content should be visible.</p>";
    echo "<p class='info'>Recommendation: Check view rendering logic in <code>audit/detail.php</code></p>";
} else {
    echo "<p class='error'>❌ <strong>NO DATA FOUND</strong> in any source!</p>";
    echo "<p class='warn'>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Data was never saved for this request</li>";
    echo "<li>Data was deleted or purged</li>";
    echo "<li>Request was created as FREE_INPUT but content was never submitted</li>";
    echo "<li>Different database being queried</li>";
    echo "</ul>";
}

echo "</div>";

sqlsrv_close($conn);
?>

<div class="section" style="background: #2d2d30; text-align: center;">
    <p style="margin: 0; color: #858585;">Change Request ID: <a href="?id=<?php echo $requestId; ?>" style="color: #4ec9b0;">?id=X</a></p>
</div>

</body>
</html>
