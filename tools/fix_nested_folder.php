<?php
// Quick fix: Move file from nested KONV-WA folder to SC-0129
$ROOT = dirname(__DIR__);
$src = $ROOT . '/storage/uploads/KONV-WA/SM-06/03/26-0016-01/LEGAL_2026_SC-0129_Final_Script_Kartu_Kredit_Past_Due_WhatsApp_SMS.pdf';
$dstDir = $ROOT . '/storage/uploads/SC-0129';
$dst = $dstDir . '/LEGAL_2026_SC-0129_Final_Script_Kartu_Kredit_Past_Due_WhatsApp_SMS.pdf';

if (!is_dir($dstDir)) mkdir($dstDir, 0777, true);

if (file_exists($src)) {
    if (copy($src, $dst)) {
        echo "Copied to SC-0129 OK\n";
        unlink($src);
        // Clean up empty nested dirs
        @rmdir($ROOT . '/storage/uploads/KONV-WA/SM-06/03/26-0016-01');
        @rmdir($ROOT . '/storage/uploads/KONV-WA/SM-06/03');
        @rmdir($ROOT . '/storage/uploads/KONV-WA/SM-06');
        @rmdir($ROOT . '/storage/uploads/KONV-WA');
        echo "Cleaned up nested KONV-WA folders\n";
    } else {
        echo "FAILED to copy\n";
    }
} else {
    echo "Source file not found (already moved?)\n";
}

// Update DB path
require_once $ROOT . '/app/helpers/EnvLoader.php';
App\Helpers\EnvLoader::load($ROOT . '/.env');
require_once $ROOT . '/app/helpers/DbAdapter.php';
$config = require $ROOT . '/config/database.php';
$conn = db_connect($config['host'], array_merge([
    'Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']
], $config['options'] ?? []));

if ($conn) {
    $oldPath = '%KONV-WA%LEGAL_2026_SC-0129%';
    $stmt = db_query($conn, "UPDATE script_files SET filepath = ? WHERE filepath LIKE ?", [$dst, $oldPath]);
    echo "DB path updated: " . db_rows_affected($stmt) . " row(s)\n";
    db_close($conn);
}
echo "Done!\n";
