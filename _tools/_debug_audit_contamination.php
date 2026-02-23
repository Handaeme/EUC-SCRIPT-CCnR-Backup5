<?php
/**
 * Debug Script: Check cross-sheet contamination in script_preview_content
 * for request ID 94 (SC-0094)
 */
require_once __DIR__ . '/app/config/database.php';

$conn = db_connect();
if (!$conn) { die("DB Connection Failed"); }

// Get all versions for request 94
$sql = "SELECT 
            id, media, workflow_stage, created_by, 
            CONVERT(varchar, created_at, 120) as created_at,
            LEN(content) as content_length,
            -- Check for red spans (auto-red contamination markers)
            CASE WHEN content LIKE '%revision-span%' THEN 'YES' ELSE 'NO' END as has_revision_spans,
            CASE WHEN content LIKE '%text-decoration: line-through%' OR content LIKE '%text-decoration:line-through%' THEN 'YES' ELSE 'NO' END as has_strikethrough,
            CASE WHEN content LIKE '%color: red%' OR content LIKE '%color:red%' OR content LIKE '%color:#dc2626%' OR content LIKE '%color: #dc2626%' THEN 'YES' ELSE 'NO' END as has_red_color,
            CASE WHEN content LIKE '%Prolusion%' OR content LIKE '%prolusion%' THEN 'YES' ELSE 'NO' END as has_prolusion_text,
            CASE WHEN content LIKE '%data-comment-id%' THEN 'YES' ELSE 'NO' END as has_comments,
            -- Count occurrences of revision-span
            (LEN(content) - LEN(REPLACE(content, 'revision-span', ''))) / LEN('revision-span') as revision_span_count
        FROM script_preview_content 
        WHERE request_id = 94
        ORDER BY created_at ASC, id ASC";

$stmt = db_query($conn, $sql, []);

echo "<h2>Preview Content for Request 94</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse:collapse; font-size:12px;'>";
echo "<tr style='background:#f0f0f0;'><th>ID</th><th>Media</th><th>Stage</th><th>Created By</th><th>Created At</th><th>Content Len</th><th>Has Revision Spans</th><th>Has Strikethrough</th><th>Has Red Color</th><th>Has 'Prolusion'</th><th>Has Comments</th><th>Revision Span Count</th></tr>";

$rows = [];
while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
    $rows[] = $row;
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['media']}</td>";
    echo "<td>{$row['workflow_stage']}</td>";
    echo "<td>{$row['created_by']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "<td>{$row['content_length']}</td>";
    echo "<td style='color:" . ($row['has_revision_spans'] === 'YES' ? 'red' : 'green') . "'>{$row['has_revision_spans']}</td>";
    echo "<td style='color:" . ($row['has_strikethrough'] === 'YES' ? 'red' : 'green') . "'>{$row['has_strikethrough']}</td>";
    echo "<td style='color:" . ($row['has_red_color'] === 'YES' ? 'red' : 'green') . "'>{$row['has_red_color']}</td>";
    echo "<td style='color:" . ($row['has_prolusion_text'] === 'YES' ? 'orange' : 'green') . "'>{$row['has_prolusion_text']}</td>";
    echo "<td>{$row['has_comments']}</td>";
    echo "<td>{$row['revision_span_count']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// Now show a snippet of content for each SPV row that has contamination
echo "<h2>Contamination Detail (SPV stage rows with revision spans)</h2>";

$sql2 = "SELECT id, media, workflow_stage, 
            SUBSTRING(content, 1, 500) as content_preview
         FROM script_preview_content 
         WHERE request_id = 94 
           AND workflow_stage LIKE '%SPV%'
         ORDER BY id ASC";

$stmt2 = db_query($conn, $sql2, []);
while ($row = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
    echo "<div style='margin:10px 0; padding:10px; border:1px solid #ddd; background:#fafafa;'>";
    echo "<strong>ID: {$row['id']} | Media: {$row['media']} | Stage: {$row['workflow_stage']}</strong><br>";
    echo "<pre style='white-space:pre-wrap; font-size:11px;'>" . htmlspecialchars($row['content_preview']) . "</pre>";
    echo "</div>";
}

echo "<p style='color:gray;'>Done.</p>";
