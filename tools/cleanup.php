<?php
/**
 * ============================================================
 * CLEANUP SCRIPT: Remove Old Review Docs Folders
 * ============================================================
 * 
 * This script deletes the old `review_docs` folders and
 * migration log files after you have successfully run the
 * `migrate_review_docs.php` script.
 * 
 * Run this from the CLI:
 *   php tools/cleanup.php
 * 
 * Or from the browser:
 *   http://your-server/EUC-Script-CCnR-Migrasi/tools/cleanup.php
 * ============================================================
 */

$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>Cleanup Tool - EUC Script</title>
    <style>body{max-width:800px;margin:40px auto;padding:0 20px;background:#f8fafc;font-family:Inter,sans-serif;}
    pre{background:#1e293b;color:#f8fafc;padding:20px;border-radius:12px;overflow-x:auto;font-size:13px;line-height:1.5;}</style>
    </head><body><pre>";
}

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  CLEANUP TOOL: Post-Migration Cleanup              ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$ROOT = dirname(__DIR__);

$itemsToDelete = [
    $ROOT . '/uploads/review_docs',
    $ROOT . '/storage/uploads/review_docs'
];

$deletedCount = 0;
$errorCount = 0;

// 1. Delete old folders
foreach ($itemsToDelete as $dir) {
    if (is_dir($dir)) {
        echo "Deleting directory: " . str_replace($ROOT, '', $dir) . " ... ";
        if (deleteDirectory($dir)) {
            echo "✅ SUCCESS\n";
            $deletedCount++;
        } else {
            echo "❌ FAILED (might be denied permission or files open)\n";
            $errorCount++;
        }
    } else {
        echo "Directory already gone: " . str_replace($ROOT, '', $dir) . "\n";
    }
}

// 2. Delete old CSV logs in tools/
echo "\nScanning for old migration logs...\n";
$toolsDir = __DIR__;
$logsFound = 0;
foreach (glob($toolsDir . '/migration_log_*.csv') as $file) {
    $logsFound++;
    echo "Deleting log: " . basename($file) . " ... ";
    if (@unlink($file)) {
         echo "✅ SUCCESS\n";
         $deletedCount++;
    } else {
         echo "❌ FAILED\n";
         $errorCount++;
    }
}

if ($logsFound === 0) {
    echo "No old migration logs found.\n";
}

echo "\n======================================================\n";
echo "CLEANUP SUMMARY:\n";
echo "- $deletedCount item(s) successfully deleted.\n";
if ($errorCount > 0) {
    echo "- ⚠ $errorCount item(s) failed to delete.\n";
    echo "      (Make sure no files are open in another program!)\n";
} else {
    echo "🎉 Your folder structure is perfectly clean!\n";
}
echo "======================================================\n";

if (!$isCLI) {
    echo "</pre></body></html>";
}

// --- Helper Function ---
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}
