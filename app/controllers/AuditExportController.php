<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * AuditExportController
 * 
 * Handles bulk export of audit data as ZIP packages.
 * Access: ADMIN role only.
 * 
 * [START UPDATE 03-Mar-2026] Feature: Audit Package Export (Admin Only)
 */
class AuditExportController extends Controller {

    /**
     * Main export action — builds a ZIP containing:
     *  1. Audit Trail summary (CSV)
     *  2. Script content rendered as HTML→PDF-style HTML files
     *  3. Physical files (Master Script + Review Evidence)
     */
    public function export() {
        // ── Guard: Admin Only ──────────────────────────────────
        if (!isset($_SESSION['user']) || ($_SESSION['user']['dept'] ?? '') !== 'ADMIN') {
            http_response_code(403);
            die('Access Denied: Admin only.');
        }

        // ── Increase limits for heavy export ───────────────────
        set_time_limit(300);  // 5 minutes
        ini_set('memory_limit', '512M');

        // ── Read Filters from GET ──────────────────────────────
        $startDate   = $_GET['start_date'] ?? null;
        $endDate     = $_GET['end_date'] ?? null;
        $filters     = [];
        foreach (['jenis', 'produk', 'kategori', 'media'] as $key) {
            if (!empty($_GET[$key])) {
                $filters[$key] = is_array($_GET[$key]) ? $_GET[$key] : [$_GET[$key]];
            }
        }
        if (!empty($_GET['created_by'])) {
            $filters['created_by'] = $_GET['created_by'];
        }

        // ── Fetch Library Items matching filters ───────────────
        $reqModel = $this->model('RequestModel');
        $libraryItems = $reqModel->getLibraryItems(
            $startDate, $endDate, 'DESC', $filters,
            true, // showInactive — include inactive scripts for audit
            'created_at', 'created_at', null
        );

        if (empty($libraryItems)) {
            die('No scripts found matching the selected filters.');
        }

        // ── Prepare temp directory ─────────────────────────────
        $baseDir = dirname(__DIR__, 2);
        $tempDir = $baseDir . '/storage/temp_zips/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        // Clean old temp files (> 1 hour)
        $this->cleanOldTempFiles($tempDir);

        $zipFilename = 'Audit_Export_' . date('Ymd_His') . '.zip';
        $zipPath     = $tempDir . $zipFilename;

        // ── Build ZIP ──────────────────────────────────────────
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            die('Failed to create ZIP file. Check folder permissions on storage/temp_zips/');
        }

        // ── 1. Audit Trail Summary (CSV) ───────────────────────
        $csvContent = $this->buildAuditTrailCSV($reqModel, $libraryItems);
        $zip->addFromString('00_Audit_Trail_Summary.csv', $csvContent);

        // ── 2. Per-Script Folders ──────────────────────────────
        foreach ($libraryItems as $idx => $item) {
            $requestId    = $item['request_id'] ?? $item['id'] ?? null;
            if (!$requestId) continue;

            $scriptNumber = $item['script_number'] ?? 'Unknown';
            $ticketId     = $item['ticket_id'] ?? $scriptNumber;
            $safeTicket   = $this->sanitizeFolderName($ticketId) ?: sprintf('Script_%04d', $idx + 1);
            $folderName   = sprintf('%03d_%s', $idx + 1, $safeTicket);

            // ── 2a. Script Info (metadata txt) ─────────────────
            $infoText = $this->buildScriptInfoText($item);
            $zip->addFromString("$folderName/01_Script_Info.txt", $infoText);

            // ── 2b. Per-script Audit Trail ─────────────────────
            $detail = $reqModel->getRequestDetail($requestId);
            $logs   = $detail['logs'] ?? [];
            if (!empty($logs)) {
                $logCsv = $this->buildPerScriptAuditCSV($logs);
                $zip->addFromString("$folderName/02_Audit_Trail.csv", $logCsv);
            }

            // ── 2c. Library Content (HTML file) ────────────────
            $content = $reqModel->getLibraryContentOnly($requestId);
            if (!empty($content)) {
                $html = $this->buildContentHTML($item, $content);
                $zip->addFromString("$folderName/03_Script_Content.html", $html);
            }

            // ── 2d. Master Script File (.xlsx/.docx) ───────────
            $scriptFile = $reqModel->getScriptFile($requestId);
            if ($scriptFile && !empty($scriptFile['filepath'])) {
                $physicalPath = $this->resolveFilePath($scriptFile['filepath'], $baseDir);
                if ($physicalPath && file_exists($physicalPath)) {
                    $origName = $scriptFile['original_filename'] ?? basename($physicalPath);
                    $safeName = $this->sanitizeFileName($origName);
                    $zip->addFile($physicalPath, "$folderName/04_Master_$safeName");
                } else {
                    $zip->addFromString("$folderName/04_Master_FILE_NOT_FOUND.txt",
                        "File not found on server.\nExpected path: " . ($scriptFile['filepath'] ?? 'N/A'));
                }
            }

            // ── 2e. Review Evidence Documents ──────────────────
            $reviewDocs = $reqModel->getReviewDocuments($requestId);
            if (!empty($reviewDocs)) {
                $evidenceIdx = 1;
                foreach ($reviewDocs as $doc) {
                    $docType = $doc['file_type'] ?? 'DOC';
                    $physicalPath = $this->resolveFilePath($doc['filepath'] ?? '', $baseDir);
                    if ($physicalPath && file_exists($physicalPath)) {
                        $origName = $doc['original_filename'] ?? basename($physicalPath);
                        $safeName = $this->sanitizeFileName($origName);
                        $zip->addFile($physicalPath,
                            "$folderName/05_Evidence/{$docType}_{$evidenceIdx}_{$safeName}");
                    } else {
                        $zip->addFromString(
                            "$folderName/05_Evidence/{$docType}_{$evidenceIdx}_FILE_NOT_FOUND.txt",
                            "File not found on server.\nExpected path: " . ($doc['filepath'] ?? 'N/A'));
                    }
                    $evidenceIdx++;
                }
            }
        }

        $zip->close();

        // ── Stream ZIP to browser ──────────────────────────────
        if (!file_exists($zipPath)) {
            die('ZIP creation failed.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        readfile($zipPath);

        // Clean up the temp file after sending
        @unlink($zipPath);
        exit;
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPER METHODS
    // ═══════════════════════════════════════════════════════════

    /**
     * Build a master CSV summarizing all scripts in this export
     */
    private function buildAuditTrailCSV($reqModel, $items) {
        $csv = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $csv .= "No,Ticket ID,Script Number,Title,Jenis,Produk,Kategori,Media,Status,Created By,Created At,Start Date,Is Active\r\n";

        foreach ($items as $idx => $item) {
            $csv .= implode(',', [
                $idx + 1,
                '"' . str_replace('"', '""', $item['ticket_id'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['script_number'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['title'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['jenis'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['produk'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['kategori'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['media'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['status'] ?? '') . '"',
                '"' . str_replace('"', '""', $item['created_by'] ?? '') . '"',
                '"' . str_replace('"', '""', $this->formatDate($item['request_created_at'] ?? $item['created_at'] ?? '')) . '"',
                '"' . str_replace('"', '""', $this->formatDate($item['start_date'] ?? '')) . '"',
                ($item['is_active'] ?? 1) ? 'Active' : 'Inactive',
            ]) . "\r\n";
        }

        return $csv;
    }

    /**
     * Build per-script audit trail CSV
     */
    private function buildPerScriptAuditCSV($logs) {
        $csv = "\xEF\xBB\xBF";
        $csv .= "No,Date,User,Role,Action,Details\r\n";
        foreach ($logs as $idx => $log) {
            $csv .= implode(',', [
                $idx + 1,
                '"' . str_replace('"', '""', $this->formatDate($log['created_at'] ?? '')) . '"',
                '"' . str_replace('"', '""', $log['user_id'] ?? '') . '"',
                '"' . str_replace('"', '""', $log['user_role'] ?? '') . '"',
                '"' . str_replace('"', '""', $log['action'] ?? '') . '"',
                '"' . str_replace('"', '""', $log['details'] ?? '') . '"',
            ]) . "\r\n";
        }
        return $csv;
    }

    /**
     * Build metadata text file for a script
     */
    private function buildScriptInfoText($item) {
        $lines = [];
        $lines[] = "═══════════════════════════════════════════════";
        $lines[] = "  SCRIPT INFORMATION";
        $lines[] = "═══════════════════════════════════════════════";
        $lines[] = "";
        $lines[] = "Ticket ID      : " . ($item['ticket_id'] ?? '-');
        $lines[] = "Script Number  : " . ($item['script_number'] ?? '-');
        $lines[] = "Title          : " . ($item['title'] ?? '-');
        $lines[] = "Jenis          : " . ($item['jenis'] ?? '-');
        $lines[] = "Produk         : " . ($item['produk'] ?? '-');
        $lines[] = "Kategori       : " . ($item['kategori'] ?? '-');
        $lines[] = "Media          : " . ($item['media'] ?? '-');
        $lines[] = "Status         : " . ($item['status'] ?? '-');
        $lines[] = "Is Active      : " . (($item['is_active'] ?? 1) ? 'Active' : 'Inactive');
        $lines[] = "Created By     : " . ($item['created_by'] ?? '-');
        $lines[] = "Created At     : " . $this->formatDate($item['request_created_at'] ?? $item['created_at'] ?? '');
        $lines[] = "Start Date     : " . $this->formatDate($item['start_date'] ?? '');
        $lines[] = "";
        $lines[] = "Export Date    : " . date('d-M-Y H:i:s');
        $lines[] = "Exported By    : " . ($_SESSION['user']['userid'] ?? 'ADMIN');
        return implode("\r\n", $lines);
    }

    /**
     * Build a self-contained HTML file for the script content.
     * This acts as a "visual PDF" — can be opened in any browser and printed to PDF.
     */
    private function buildContentHTML($item, $contentRows) {
        $title = htmlspecialchars($item['script_number'] ?? 'Script');
        $fullTitle = htmlspecialchars($item['title'] ?? '');

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= "<title>$title</title>";
        $html .= '<style>';
        $html .= 'body { font-family: "Segoe UI", Arial, sans-serif; margin: 30px; color: #1e293b; }';
        $html .= 'h1 { color: #b71c1c; font-size: 18px; border-bottom: 2px solid #b71c1c; padding-bottom: 8px; }';
        $html .= 'h2 { color: #334155; font-size: 15px; margin-top: 25px; background: #f1f5f9; padding: 8px 12px; border-radius: 6px; }';
        $html .= '.info { font-size: 13px; color: #64748b; margin-bottom: 20px; }';
        $html .= 'table { border-collapse: collapse; width: 100%; margin: 10px 0; }';
        $html .= 'td, th { border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 13px; }';
        $html .= 'th { background: #f8fafc; font-weight: 600; }';
        $html .= '.footer { margin-top: 40px; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }';
        $html .= '@media print { body { margin: 15px; } }';
        $html .= '</style></head><body>';
        $html .= "<h1>$title</h1>";
        $html .= "<div class='info'>$fullTitle</div>";

        foreach ($contentRows as $row) {
            $media   = htmlspecialchars($row['media'] ?? 'Content');
            $content = $row['content'] ?? '';
            $html .= "<h2>$media</h2>";
            $html .= "<div class='content-area'>$content</div>";
        }

        $html .= '<div class="footer">';
        $html .= 'Exported on ' . date('d-M-Y H:i:s') . ' by ' . htmlspecialchars($_SESSION['user']['userid'] ?? 'ADMIN');
        $html .= '</div></body></html>';

        return $html;
    }

    /**
     * Resolve file path — handles both absolute and relative paths
     */
    private function resolveFilePath($filepath, $baseDir) {
        if (empty($filepath)) return null;

        // Already absolute (contains drive letter on Windows or starts with /)
        if (strpos($filepath, ':') !== false || strpos($filepath, '/') === 0 || strpos($filepath, '\\') === 0) {
            return $filepath;
        }

        // Relative path — prepend base directory
        return $baseDir . '/' . $filepath;
    }

    /**
     * Sanitize a string for use as a folder name
     */
    private function sanitizeFolderName($name) {
        $safe = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
        $safe = preg_replace('/\s+/', '_', $safe);
        return substr($safe, 0, 80); // Limit length
    }

    /**
     * Sanitize a string for use as a file name
     */
    private function sanitizeFileName($name) {
        $safe = preg_replace('/[\/\\\\:*?"<>|]/', '_', $name);
        return substr($safe, 0, 100);
    }

    /**
     * Format a date value (handles sqlsrv DateTime objects)
     */
    private function formatDate($val) {
        if (empty($val)) return '-';
        if ($val instanceof \DateTime) {
            return $val->format('d-M-Y H:i');
        }
        if (is_string($val)) {
            $ts = strtotime($val);
            return $ts ? date('d-M-Y H:i', $ts) : $val;
        }
        return (string)$val;
    }

    /**
     * Clean temp ZIP files older than 1 hour
     */
    private function cleanOldTempFiles($dir) {
        $files = glob($dir . '*.zip');
        $now   = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > 3600) {
                @unlink($file);
            }
        }
    }
}
// [END UPDATE 03-Mar-2026]
