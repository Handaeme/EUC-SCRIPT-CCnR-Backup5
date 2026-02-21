<?php
/**
 * Helper script to integrate version timeline into audit detail view
 * Run once to update the file
 */

$file = 'd:/xampp/htdocs/citra8/app/views/audit/detail.php';
$content = file_get_contents($file);

// Find and replace the old preview section
$oldPattern = '/<!-- File Preview -->\s*<\?php.*?<\?php else: \?>/s';
$replacement = '<!-- File Preview - Version Timeline -->
                    <?php include \'app/views/audit/_version_timeline.php\'; ?>
                <?php else: ?>';

$newContent = preg_replace($oldPattern, $replacement, $content, 1);

if ($newContent && $newContent !== $content) {
    // Backup original
    copy($file, $file . '.backup.' . date('YmdHis'));
    file_put_contents($file, $newContent);
    echo "[OK] Successfully updated audit/detail.php\n";
    echo "[OK] Backup created: " . basename($file) . ".backup." . date('YmdHis') . "\n";
} else {
    echo "[ERROR] Pattern not found or no changes made\n";
}
?>
