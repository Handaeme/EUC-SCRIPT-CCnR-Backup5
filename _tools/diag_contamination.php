<?php
/**
 * DIAGNOSTIC: Check cross-sheet contamination in script_preview_content
 * Shows all rows for a given request_id, with content snippets
 * to verify if sheets have duplicated/contaminated content
 */

require_once __DIR__ . '/app/core/db.php';

$conn = db_connect();
if (!$conn) { die("DB Connection failed"); }

// Auto-detect: get the LATEST request_id with multiple preview content rows
$sql = "SELECT TOP 5 request_id, COUNT(*) as cnt, MAX(created_at) as latest
        FROM script_preview_content 
        GROUP BY request_id 
        HAVING COUNT(*) > 3
        ORDER BY MAX(COALESCE(created_at, updated_at)) DESC";
$stmt = db_query($conn, $sql, []);

echo "<h1>Cross-Sheet Contamination Diagnostic</h1>";
echo "<style>
    body { font-family: 'Segoe UI', sans-serif; margin: 20px; background: #f8fafc; }
    h1 { color: #1e293b; }
    h2 { color: #475569; margin-top: 30px; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; background: white; }
    th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 13px; }
    th { background: #f1f5f9; font-weight: 600; color: #334155; }
    .warn { background: #fef2f2; color: #dc2626; }
    .ok { background: #f0fdf4; color: #16a34a; }
    .snippet { max-width: 500px; overflow: hidden; word-break: break-all; font-size: 11px; color: #64748b; }
    .highlight { background: #fef3c7; padding: 2px 4px; border-radius: 3px; }
    .match { background: #fecaca; padding: 2px 4px; border-radius: 3px; font-weight: bold; }
</style>";

// Fetch rows
$requests = [];
if ($stmt) {
    while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
        $requests[] = $row;
    }
}

if (empty($requests)) {
    echo "<p>No multi-sheet requests found.</p>";
    exit;
}

echo "<p>Found " . count($requests) . " recent multi-sheet requests:</p>";

foreach ($requests as $req) {
    $reqId = $req['request_id'];
    echo "<h2>Request ID: {$reqId} ({$req['cnt']} rows)</h2>";
    
    // Get all preview content rows for this request, ordered by timestamp
    $sql2 = "SELECT id, media, workflow_stage, 
                    COALESCE(created_by, updated_by) as [user],
                    CONVERT(varchar, COALESCE(created_at, updated_at), 120) as ts,
                    DATALENGTH(content) as content_size,
                    content
             FROM script_preview_content 
             WHERE request_id = ? 
             ORDER BY COALESCE(created_at, updated_at) ASC, id ASC";
    $stmt2 = db_query($conn, $sql2, [$reqId]);
    
    $rows = [];
    if ($stmt2) {
        while ($r = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
            $rows[] = $r;
        }
    }
    
    // Group by timestamp (same minute = same save action)
    $groups = [];
    foreach ($rows as $r) {
        $key = substr($r['ts'] ?? '', 0, 16) . '_' . ($r['workflow_stage'] ?? 'NULL');
        if (!isset($groups[$key])) $groups[$key] = [];
        $groups[$key][] = $r;
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Media</th><th>Stage</th><th>User</th><th>Time</th><th>Size</th><th>Has Revision Spans?</th><th>Content Snippet (first 200 chars)</th><th>Duplicate?</th></tr>";
    
    foreach ($groups as $key => $group) {
        echo "<tr><td colspan='9' style='background:#e2e8f0; font-weight:bold; font-size:12px;'>Save Group: {$key} (" . count($group) . " sheets)</td></tr>";
        
        // Check for duplicates within this group
        $contentHashes = [];
        foreach ($group as $r) {
            $hash = md5($r['content'] ?? '');
            $contentHashes[$r['media']] = $hash;
        }
        $uniqueHashes = array_unique(array_values($contentHashes));
        $hasDuplicates = count($uniqueHashes) < count($contentHashes);
        
        foreach ($group as $r) {
            $content = $r['content'] ?? '';
            $snippet = htmlspecialchars(substr(strip_tags($content), 0, 200));
            $hasRevSpan = (strpos($content, 'revision-span') !== false || strpos($content, 'deletion-span') !== false || strpos($content, 'color: red') !== false || strpos($content, 'color:red') !== false);
            $hasTabsNav = (strpos($content, 'sheet-tabs-nav') !== false);
            
            $hash = md5($content);
            $isDuplicate = false;
            foreach ($contentHashes as $media => $h) {
                if ($media !== $r['media'] && $h === $hash && strlen($content) > 100) {
                    $isDuplicate = true;
                    break;
                }
            }
            
            $dupClass = $isDuplicate ? 'warn' : 'ok';
            $revClass = $hasRevSpan ? 'match' : '';
            
            // Highlight "Prolusion" in snippet
            $snippet = str_replace('Prolusion', '<span class="highlight">Prolusion</span>', $snippet);
            
            echo "<tr class='{$dupClass}'>";
            echo "<td>{$r['id']}</td>";
            echo "<td><b>{$r['media']}</b>" . ($hasTabsNav ? ' <span class="match">HAS TABS!</span>' : '') . "</td>";
            echo "<td>{$r['workflow_stage']}</td>";
            echo "<td>{$r['user']}</td>";
            echo "<td>{$r['ts']}</td>";
            echo "<td>" . number_format($r['content_size']) . "</td>";
            echo "<td class='{$revClass}'>" . ($hasRevSpan ? '⚠️ YES' : '✅ No') . "</td>";
            echo "<td class='snippet'>{$snippet}</td>";
            echo "<td>" . ($isDuplicate ? '<b>⚠️ DUPLICATE!</b>' : '✅ Unique') . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // DETECTION: Check if any group has the "hasPrebuiltTabs" issue
    foreach ($groups as $key => $group) {
        foreach ($group as $r) {
            if (strpos($r['content'] ?? '', 'sheet-tabs-nav') !== false) {
                echo "<div style='background:#fef2f2; border:2px solid #ef4444; padding:15px; border-radius:8px; margin:15px 0;'>";
                echo "<b>⚠️ CRITICAL: Media '{$r['media']}' (ID {$r['id']}, stage {$r['workflow_stage']}) contains 'sheet-tabs-nav' inside its content!</b><br>";
                echo "This means the content already has tab navigation embedded, which causes the audit trail to use only this sheet's content for ALL tabs.";
                echo "</div>";
            }
        }
    }
}

echo "<hr><p style='color:#94a3b8; font-size:11px;'>Diagnostic complete. " . date('Y-m-d H:i:s') . "</p>";
?>
