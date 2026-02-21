<?php
// CLI Diagnostic - check cross-sheet contamination
require_once __DIR__ . '/app/core/db.php';
$conn = db_connect();
if (!$conn) { die("DB fail\n"); }

// Get latest request with multiple sheets
$sql = "SELECT TOP 3 request_id, COUNT(*) as cnt
        FROM script_preview_content 
        GROUP BY request_id 
        HAVING COUNT(*) > 3
        ORDER BY MAX(COALESCE(created_at, updated_at)) DESC";
$stmt = db_query($conn, $sql, []);
$reqs = [];
while ($r = db_fetch_array($stmt, DB_FETCH_ASSOC)) $reqs[] = $r;

if (empty($reqs)) { die("No multi-sheet requests found\n"); }

foreach ($reqs as $req) {
    $rid = $req['request_id'];
    echo "=== Request $rid ({$req['cnt']} rows) ===\n";
    
    $sql2 = "SELECT id, media, workflow_stage, 
                    DATALENGTH(content) as sz,
                    CONVERT(varchar, COALESCE(created_at, updated_at), 120) as ts,
                    CASE WHEN content LIKE '%sheet-tabs-nav%' THEN 'HAS_TABS' ELSE '-' END as has_tabs,
                    CASE WHEN content LIKE '%revision-span%' OR content LIKE '%deletion-span%' OR content LIKE '%color: red%' OR content LIKE '%color:red%' THEN 'HAS_REVISIONS' ELSE '-' END as has_rev,
                    HASHBYTES('MD5', content) as content_hash
             FROM script_preview_content 
             WHERE request_id = ? 
             ORDER BY COALESCE(created_at, updated_at) ASC, id ASC";
    $stmt2 = db_query($conn, $sql2, [$rid]);
    
    $rows = [];
    while ($r2 = db_fetch_array($stmt2, DB_FETCH_ASSOC)) $rows[] = $r2;
    
    // Group by timestamp
    $lastGroup = '';
    foreach ($rows as $r2) {
        $group = substr($r2['ts'], 0, 16) . '_' . $r2['workflow_stage'];
        if ($group !== $lastGroup) {
            echo "\n  --- Group: $group ---\n";
            $lastGroup = $group;
        }
        
        // Check duplicate within group
        $isDup = false;
        foreach ($rows as $other) {
            if ($other['id'] != $r2['id'] && $other['content_hash'] == $r2['content_hash'] && $r2['sz'] > 100) {
                $otherGroup = substr($other['ts'], 0, 16) . '_' . $other['workflow_stage'];
                if ($otherGroup === $group) {
                    $isDup = true;
                    break;
                }
            }
        }
        
        $dupLabel = $isDup ? '*** DUPLICATE ***' : 'unique';
        echo "  ID:{$r2['id']} | {$r2['media']} | {$r2['workflow_stage']} | size:{$r2['sz']} | {$r2['has_tabs']} | {$r2['has_rev']} | $dupLabel\n";
    }
    echo "\n";
}
echo "Done.\n";
