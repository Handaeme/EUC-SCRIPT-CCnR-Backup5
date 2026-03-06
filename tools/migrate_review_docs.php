<?php
/**
 * ============================================================
 * MIGRATION SCRIPT: Reorganize Old Review Docs to SC-XXXX/ Folders
 * ============================================================
 * 
 * This script migrates review documents that are scattered in:
 *   - uploads/review_docs/
 *   - storage/uploads/review_docs/
 * 
 * Into their proper SC-XXXX/ folders under storage/uploads/SC-XXXX/
 * 
 * FILE NAMING PATTERNS HANDLED:
 *   Pattern A: {ID}_{TYPE}_{timestamp}.{ext}  (e.g. 29_CX_1770262813.pdf)
 *   Pattern B: {TYPE}_{YEAR}_{SC-XXXX}_{name}.{ext}  (e.g. CX_2026_SC-0082_filename.pdf)
 * 
 * USAGE:
 *   1. Dry Run (preview only, no changes):
 *      php tools/migrate_review_docs.php
 * 
 *   2. Execute Migration:
 *      php tools/migrate_review_docs.php --execute
 * 
 * SAFETY:
 *   - Files are COPIED first, then originals deleted only after verification
 *   - A CSV log is created at tools/migration_log_{timestamp}.csv
 *   - Dry run mode by default — nothing is changed until you pass --execute
 * ============================================================
 */

// ── SETUP ──
date_default_timezone_set('Asia/Jakarta');
$ROOT = dirname(__DIR__);

// Load environment and DB
require_once $ROOT . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load($ROOT . '/.env');
require_once $ROOT . '/app/helpers/DbAdapter.php';

// Connect to DB (needed to resolve Pattern A files → script_number)
$config = require $ROOT . '/config/database.php';
$connectionInfo = [
    'Database' => $config['dbname'],
    'UID'      => $config['user'],
    'PWD'      => $config['pass'],
];
if (isset($config['options'])) {
    $connectionInfo = array_merge($connectionInfo, $config['options']);
}
$conn = db_connect($config['host'], $connectionInfo);

// ── CONFIG ──
$dryRun = !in_array('--execute', $argv ?? []);
$targetBase = $ROOT . '/storage/uploads';

$sourceDirs = [
    $ROOT . '/uploads/review_docs',
    $ROOT . '/storage/uploads/review_docs',
];

$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    echo "<!DOCTYPE html><html><head><title>Migration Tool - EUC Script</title>
    <style>body{max-width:1000px;margin:40px auto;padding:0 20px;background:#f8fafc;font-family:Inter,sans-serif;}</style>
    </head><body><pre style='background:#1e293b;color:#f8fafc;padding:20px;border-radius:12px;overflow-x:auto;font-size:13px;line-height:1.5;'>";
}

// ── HEADER ──
echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  REVIEW DOCS MIGRATION TOOL                        ║\n";
echo "║  Reorganize old docs into SC-XXXX/ folders         ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

if ($dryRun) {
    echo "[MODE] 🔍 DRY RUN — No files will be moved.\n";
    echo "       Add --execute to perform actual migration.\n\n";
} else {
    echo "[MODE] ⚡ EXECUTE — Files will be moved!\n\n";
}

// ── BUILD REQUEST ID → SCRIPT NUMBER MAP (from DB) ──
$idMap = []; // request_id => script_number
if ($conn) {
    echo "[DB] Connected. Building request ID → script number map...\n";
    $sql = "SELECT id, script_number, ticket_id FROM script_request WHERE is_deleted = 0";
    $stmt = db_query($conn, $sql);
    if ($stmt) {
        while ($row = db_fetch_array($stmt, DB_FETCH_ASSOC)) {
            $idMap[(int)$row['id']] = $row['script_number'];
        }
    }
    echo "[DB] Found " . count($idMap) . " requests in database.\n\n";
} else {
    echo "[DB] ⚠ Could not connect to database. Pattern A files (ID-based) may not be resolved.\n";
    echo "     Pattern B files (with SC-XXXX in name) will still work.\n\n";
}

// Also build a map from script_files table: file path → script_number
$fileDbMap = []; // normalized filepath => script_number
if ($conn) {
    $sql2 = "SELECT sf.filepath, sr.script_number 
             FROM script_files sf 
             JOIN script_request sr ON sf.request_id = sr.id 
             WHERE sr.is_deleted = 0";
    $stmt2 = db_query($conn, $sql2);
    if ($stmt2) {
        while ($row = db_fetch_array($stmt2, DB_FETCH_ASSOC)) {
            $basename = basename($row['filepath']);
            $fileDbMap[$basename] = $row['script_number'];
        }
    }
    echo "[DB] Found " . count($fileDbMap) . " file records in script_files table.\n\n";
}

// ── SCAN & PROCESS ──
$results = [];
$totalFiles = 0;
$resolved = 0;
$unresolved = 0;

foreach ($sourceDirs as $sourceDir) {
    if (!is_dir($sourceDir)) {
        echo "[SKIP] Directory not found: $sourceDir\n";
        continue;
    }

    $files = scandir($sourceDir);
    echo "[SCAN] $sourceDir — " . (count($files) - 2) . " items\n";

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $fullPath = $sourceDir . '/' . $file;
        if (!is_file($fullPath)) continue;

        $totalFiles++;
        $scriptNumber = null;
        $method = '';

        // ── PATTERN B: {TYPE}_{YEAR}_{SC-XXXX}_{name}.{ext} ──
        if (preg_match('/^(LEGAL|CX|LEGAL_SYARIAH|LPP)_\d{4}_(SC-\d{4})_/', $file, $matches)) {
            $scriptNumber = $matches[2]; // e.g. SC-0082
            $method = 'Pattern B (name contains SC-XXXX)';
        }
        
        // ── PATTERN A: {ID}_{TYPE}_{timestamp}.{ext} ──
        elseif (preg_match('/^(\d+)_(LEGAL|CX|LEGAL_SYARIAH|LPP)_(\d+)\.\w+$/', $file, $matches)) {
            $requestId = (int)$matches[1];
            if (isset($idMap[$requestId])) {
                $scriptNumber = $idMap[$requestId];
                // Extract SC-XXXX from ticket_id instead (we need the folder name format)
                // Actually we need to find the ticket_id for this request
                $method = "Pattern A (DB lookup: request_id=$requestId)";
            } else {
                $method = "Pattern A (request_id=$requestId NOT FOUND in DB)";
            }
        }

        // ── FALLBACK: Check script_files table ──
        if (!$scriptNumber && isset($fileDbMap[$file])) {
            $scriptNumber = $fileDbMap[$file];
            $method = 'Fallback (matched in script_files table)';
        }

        // ── RESOLVE script_number → folder name ──
        // The folder should be the ticket_id (SC-XXXX), not the full script_number
        $folderName = null;
        if ($scriptNumber) {
            // Look up ticket_id from script_number
            if ($conn) {
                $ticketSql = "SELECT TOP 1 ticket_id FROM script_request WHERE script_number = ?";
                $ticketStmt = db_query($conn, $ticketSql, [$scriptNumber]);
                if ($ticketStmt && $ticketRow = db_fetch_array($ticketStmt, DB_FETCH_ASSOC)) {
                    $folderName = $ticketRow['ticket_id'];
                }
            }
            // Fallback: if ticket_id not found, use script_number as folder
            if (!$folderName) {
                $folderName = $scriptNumber;
            }
        }

        // For Pattern B, SC-XXXX IS the ticket_id already
        if (!$folderName && preg_match('/SC-\d{4}/', $file, $scMatch)) {
            $folderName = $scMatch[0];
        }

        $result = [
            'file'          => $file,
            'source'        => $fullPath,
            'script_number' => $scriptNumber ?: '—',
            'folder'        => $folderName ?: '(unresolved)',
            'method'        => $method,
            'status'        => 'pending',
        ];

        if ($folderName) {
            $destDir  = $targetBase . '/' . $folderName;
            $destPath = $destDir . '/' . $file;

            $result['destination'] = $destPath;

            if (!$dryRun) {
                // Create target directory
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0777, true);
                }

                // Copy then verify then delete
                if (file_exists($destPath)) {
                    $result['status'] = 'SKIPPED (already exists)';
                } elseif (copy($fullPath, $destPath)) {
                    // Verify copy
                    if (filesize($destPath) === filesize($fullPath)) {
                        unlink($fullPath);
                        $result['status'] = 'MOVED ✅';

                        // Update DB path if we have a record
                        if ($conn) {
                            $updateSql = "UPDATE script_files SET filepath = ? WHERE filepath LIKE ?";
                            db_query($conn, $updateSql, [$destPath, '%' . $file]);
                        }
                    } else {
                        unlink($destPath); // Remove bad copy
                        $result['status'] = 'FAILED (size mismatch)';
                    }
                } else {
                    $result['status'] = 'FAILED (copy error)';
                }
            } else {
                $result['status'] = 'WOULD MOVE';
            }

            $resolved++;
        } else {
            $result['destination'] = '(unknown)';
            $result['status'] = 'UNRESOLVED ⚠';
            $unresolved++;
        }

        $results[] = $result;
    }
}

// ── CLEANUP EMPTY FOLDERS ──
echo "\n[CLEANUP] Checking for empty SC-XXXX folders...\n";
$emptyFolders = [];
$items = scandir($targetBase);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = $targetBase . '/' . $item;
    if (is_dir($path) && preg_match('/^SC-\d+$/', $item)) {
        $contents = array_diff(scandir($path), ['.', '..']);
        if (empty($contents)) {
            $emptyFolders[] = $item;
            if (!$dryRun) {
                rmdir($path);
                echo "  Removed empty folder: $item ✅\n";
            } else {
                echo "  Would remove empty folder: $item\n";
            }
        }
    }
}
if (empty($emptyFolders)) {
    echo "  No empty folders found.\n";
}

// ── RESULTS SUMMARY ──
echo "\n";
echo "┌──────────────────────────────────────────────────────┐\n";
echo "│  MIGRATION SUMMARY                                  │\n";
echo "├──────────────────────────────────────────────────────┤\n";
printf("│  Total files scanned:    %-27s│\n", $totalFiles);
printf("│  Resolved (can move):    %-27s│\n", $resolved);
printf("│  Unresolved (unknown):   %-27s│\n", $unresolved);
printf("│  Empty folders cleaned:  %-27s│\n", count($emptyFolders));
echo "└──────────────────────────────────────────────────────┘\n\n";

// ── DETAILED LOG ──
echo "DETAILED FILE LIST:\n";
echo str_repeat('─', 100) . "\n";
printf("%-45s %-15s %-15s %s\n", "FILE", "FOLDER", "STATUS", "METHOD");
echo str_repeat('─', 100) . "\n";

foreach ($results as $r) {
    printf("%-45s %-15s %-15s %s\n", 
        substr($r['file'], 0, 44), 
        substr($r['folder'], 0, 14),
        substr($r['status'], 0, 14),
        substr($r['method'], 0, 40)
    );
}

// ── SAVE CSV LOG ──
$logFile = __DIR__ . '/migration_log_' . date('Ymd_His') . '.csv';
$fp = fopen($logFile, 'w');
fputcsv($fp, ['File', 'Source', 'Destination', 'Script Number', 'Folder', 'Method', 'Status']);
foreach ($results as $r) {
    fputcsv($fp, [
        $r['file'], $r['source'], $r['destination'] ?? '', 
        $r['script_number'], $r['folder'], $r['method'], $r['status']
    ]);
}
fclose($fp);
echo "\n[LOG] Migration log saved to: $logFile\n";

if ($dryRun && $resolved > 0) {
    echo "\n💡 To execute the migration, run:\n";
    echo "   php tools/migrate_review_docs.php --execute\n\n";
}

if ($conn) db_close($conn);
echo "Done.\n";

if (!$isCLI) {
    echo "</pre></body></html>";
}
