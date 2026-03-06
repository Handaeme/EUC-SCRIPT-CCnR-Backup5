<?php
namespace App\Controllers;

use App\Core\Controller;

/**
 * LegacyImportController
 * 
 * Handles bulk import of legacy scripts into the Library.
 * Access: ADMIN role only.
 * 
 * Flow: Upload Excel + ZIP → Preview/Validate → Confirm → Import to DB + Move Files
 * 
 * [START UPDATE 04-Mar-2026] Feature: Legacy Script Importer (Admin Only)
 */
class LegacyImportController extends Controller {

    /**
     * Show the import form page
     */
    public function index() {
        $this->guardAdmin();
        $this->view('admin/legacy_import', [
            'step' => 'upload', // upload | preview | result
        ]);
    }

    /**
     * Generate and download the CSV template for migration
     */
    public function downloadTemplate() {
        $this->guardAdmin();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Template_Migrasi_Legacy.csv"');
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fwrite($out, "\xEF\xBB\xBF");

        // Header row
        fputcsv($out, ['No_Tiket (Kosongkan utk Auto)', 'Judul_Script', 'Jenis', 'Produk', 'Kategori', 'Media', 'Tgl_Dibuat', 'Nama_File', 'File_Legal (Opsional)', 'File_CX (Opsional)', 'File_Syariah (Opsional)', 'File_LPP (Opsional)']);

        // Example rows
        fputcsv($out, ['LGCY-001', 'Script Tagihan Past Due', 'Konvensional', 'Kartu Kredit', 'Past Due', 'WhatsApp', '2023-05-10', 'Script_CC_PastDue.xlsx', 'Legal_CC.pdf', '', '', '']);
        fputcsv($out, ['LGCY-002', 'Script KPR Syariah Promo', 'Syariah', 'KPR Syariah', 'Promo', 'Email', '2024-01-15', 'Script_KPR_Promo.docx', '', 'CX_KPR.pdf', 'Syariah_KPR.pdf', 'LPP_KPR.pdf']);

        fclose($out);
        exit;
    }

    /**
     * Process uploaded CSV + ZIP and show validation preview
     */
    public function preview() {
        $this->guardAdmin();

        $errors = [];
        $rows   = [];
        $zipContents = [];
        $extractHtml = !empty($_POST['extract_html']);

        // ── Validate Uploads ───────────────────────────────────
        if (empty($_FILES['excel_file']['tmp_name'])) {
            $errors[] = 'File CSV Template wajib diupload.';
        }
        if (empty($_FILES['zip_file']['tmp_name'])) {
            $errors[] = 'File ZIP Dokumen wajib diupload.';
        }

        if (!empty($errors)) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => $errors,
            ]);
            return;
        }

        // ── Read ZIP contents (list of filenames inside) ───────
        $zipPath = $_FILES['zip_file']['tmp_name'];
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                // Skip directories and __MACOSX junk
                if (substr($name, -1) === '/' || strpos($name, '__MACOSX') !== false) continue;
                // Only keep the basename (in case files are in subfolders inside ZIP)
                $zipContents[basename($name)] = $name; // basename => full path in zip
            }
            $zip->close();
        } else {
            $errors[] = 'Gagal membuka file ZIP. Pastikan format valid.';
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        // ── Read CSV ───────────────────────────────────────────
        $csvPath = $_FILES['excel_file']['tmp_name'];
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) {
                throw new \Exception('Gagal membaca file CSV.');
            }

            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Read header row
            $header = fgetcsv($handle);
            if (!$header || count($header) < 8) {
                throw new \Exception('Format CSV tidak valid. Pastikan ada minimal 8 kolom (sesuai template).');
            }

            $r = 1;
            while (($line = fgetcsv($handle)) !== false) {
                $r++;
                if (count($line) < 8) continue;

                $ticket    = trim($line[0] ?? '');
                $title     = trim($line[1] ?? '');
                $jenis     = trim($line[2] ?? '');
                $produk    = trim($line[3] ?? '');
                $kategori  = trim($line[4] ?? '');
                $media     = trim($line[5] ?? '');
                $tglDibuat = trim($line[6] ?? '');
                $namaFile  = trim($line[7] ?? '');
                // Review doc columns (optional)
                $fileLegal   = trim($line[8] ?? '');
                $fileCx      = trim($line[9] ?? '');
                $fileSyariah = trim($line[10] ?? '');
                $fileLpp     = trim($line[11] ?? '');

                // Skip completely empty rows
                if (empty($ticket) && empty($title) && empty($namaFile)) continue;

                $rowErrors = [];
                if (empty($title))     $rowErrors[] = 'Judul kosong';
                if (empty($jenis))     $rowErrors[] = 'Jenis kosong';
                if (empty($namaFile))  $rowErrors[] = 'Nama_File kosong';

                // Validate Jenis
                if (!empty($jenis) && !in_array($jenis, ['Konvensional', 'Syariah'])) {
                    $rowErrors[] = "Jenis harus 'Konvensional' atau 'Syariah'";
                }

                // Validate file exists in ZIP
                $fileFound = isset($zipContents[$namaFile]);
                if (!$fileFound && empty($rowErrors)) {
                    $rowErrors[] = "File '$namaFile' tidak ditemukan di ZIP";
                }

                // Validate review doc files exist in ZIP (optional, warn only)
                $reviewDocFiles = [
                    'legal' => $fileLegal, 'cx' => $fileCx,
                    'syariah' => $fileSyariah, 'lpp' => $fileLpp
                ];
                $reviewDocFound = [];
                foreach ($reviewDocFiles as $docKey => $docFile) {
                    if (!empty($docFile)) {
                        $reviewDocFound[$docKey] = isset($zipContents[$docFile]);
                        if (!$reviewDocFound[$docKey]) {
                            $rowErrors[] = "File " . strtoupper($docKey) . " '$docFile' tidak ditemukan di ZIP";
                        }
                    }
                }

                // Parse date
                $parsedDate = null;
                if (!empty($tglDibuat)) {
                    $ts = strtotime($tglDibuat);
                    $parsedDate = $ts ? date('Y-m-d', $ts) : null;
                    if (!$parsedDate) $rowErrors[] = "Format tanggal tidak valid: $tglDibuat";
                }

                $rows[] = [
                    'row_num'    => $r,
                    'ticket'     => $ticket,
                    'title'      => $title,
                    'jenis'      => $jenis,
                    'produk'     => $produk,
                    'kategori'   => $kategori,
                    'media'      => $media,
                    'tgl_dibuat' => $parsedDate ?? date('Y-m-d'),
                    'nama_file'  => $namaFile,
                    'file_found' => $fileFound,
                    'file_legal'   => $fileLegal,
                    'file_cx'      => $fileCx,
                    'file_syariah' => $fileSyariah,
                    'file_lpp'     => $fileLpp,
                    'review_doc_found' => $reviewDocFound,
                    'errors'     => $rowErrors,
                    'status'     => empty($rowErrors) ? 'ready' : 'error',
                ];
            }
            fclose($handle);
        } catch (\Exception $e) {
            $errors[] = 'Gagal membaca file CSV: ' . $e->getMessage();
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        if (empty($rows)) {
            $errors[] = 'File CSV kosong atau tidak ada data di baris ke-2 dan seterusnya.';
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        // ── Validate max 10 rows for Extract HTML mode ────────
        if ($extractHtml && count($rows) > 10) {
            $errors[] = 'Mode "Ekstrak Excel ke HTML" dibatasi maksimal 10 file sekali upload untuk mencegah server timeout. Anda memiliki ' . count($rows) . ' baris data. Silakan bagi CSV menjadi beberapa file.';
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        // ── Save temp files for execute step ───────────────────
        $tempDir = dirname(__DIR__, 2) . '/storage/temp_zips/';
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

        $sessionKey = 'legacy_import_' . time();
        $tempCsv   = $tempDir . $sessionKey . '.csv';
        $tempZip   = $tempDir . $sessionKey . '.zip';
        move_uploaded_file($_FILES['excel_file']['tmp_name'], $tempCsv);
        move_uploaded_file($_FILES['zip_file']['tmp_name'], $tempZip);

        // ── Pre-parse Excel files for preview (Extract HTML mode) ──
        if ($extractHtml) {
            require_once dirname(__DIR__) . '/helpers/FileHandler.php';
            $previewZip = new \ZipArchive();
            if ($previewZip->open($tempZip) === true) {
                // Build zip contents map
                $previewZipMap = [];
                for ($zi = 0; $zi < $previewZip->numFiles; $zi++) {
                    $zn = $previewZip->getNameIndex($zi);
                    if (substr($zn, -1) === '/' || strpos($zn, '__MACOSX') !== false) continue;
                    $previewZipMap[basename($zn)] = $zn;
                }

                foreach ($rows as &$row) {
                    if ($row['status'] !== 'ready') continue;
                    $fn = $row['nama_file'];
                    $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['xls', 'xlsx', 'doc', 'docx'])) continue;

                    $zipPath2 = $previewZipMap[$fn] ?? null;
                    if (!$zipPath2) continue;

                    $tmpFile = $tempDir . 'prev_' . time() . '_' . $fn;
                    $fData = $previewZip->getFromName($zipPath2);
                    if ($fData !== false) {
                        file_put_contents($tmpFile, $fData);
                        $pr = \App\Helpers\FileHandler::parseFile($tmpFile, $ext);
                        if (is_array($pr) && !empty($pr['preview_html'])) {
                            $row['content_preview_html'] = $pr['preview_html'];
                        } elseif (is_string($pr)) {
                            $row['content_preview_html'] = $pr;
                        }
                        @unlink($tmpFile);
                    }
                }
                unset($row);
                $previewZip->close();
            }
        }

        // Store in session for the execute step
        $_SESSION['legacy_import'] = [
            'key'          => $sessionKey,
            'excel_path'   => $tempCsv,
            'zip_path'     => $tempZip,
            'rows'         => $rows,
            'extract_html' => $extractHtml,
        ];

        $hasErrors = !empty(array_filter($rows, fn($r) => $r['status'] === 'error'));

        $this->view('admin/legacy_import', [
            'step'         => 'preview',
            'rows'         => $rows,
            'hasErrors'    => $hasErrors,
            'totalRows'    => count($rows),
            'readyRows'    => count(array_filter($rows, fn($r) => $r['status'] === 'ready')),
            'extract_html' => $extractHtml,
        ]);
    }

    /**
     * Execute the actual import into DB and move files
     */
    public function execute() {
        $this->guardAdmin();

        if (empty($_SESSION['legacy_import'])) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => ['Sesi import tidak ditemukan. Silakan upload ulang.'],
            ]);
            return;
        }

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $importData  = $_SESSION['legacy_import'];
        $excelPath   = $importData['excel_path'];
        $zipPath     = $importData['zip_path'];
        $rows        = $importData['rows'];
        $extractHtml = $importData['extract_html'] ?? false;
        $baseDir     = dirname(__DIR__, 2);
        $uploadsDir  = $baseDir . '/storage/uploads/';

        // Filter only ready rows
        $readyRows = array_filter($rows, fn($r) => $r['status'] === 'ready');

        if (empty($readyRows)) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => ['Tidak ada data yang siap untuk diimport.'],
            ]);
            return;
        }

        // ── Open ZIP ───────────────────────────────────────────
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => ['Gagal membuka file ZIP dari sesi. Silakan upload ulang.'],
            ]);
            return;
        }

        // Build basename => full zip path map
        $zipContents = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (substr($name, -1) === '/' || strpos($name, '__MACOSX') !== false) continue;
            $zipContents[basename($name)] = $name;
        }

        $reqModel = $this->model('RequestModel');
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        $userId   = $_SESSION['user']['userid'] ?? 'ADMIN_MIGRATION';
        $results  = [];
        $successCount = 0;
        $failCount    = 0;

        foreach ($readyRows as $row) {
            $ticket   = trim($row['ticket']);
            $title    = $row['title'];
            $jenis    = $row['jenis'];
            $produk   = $row['produk'];
            $kategori = $row['kategori'];
            $media    = $row['media'];
            $tglDibuat = $row['tgl_dibuat'];
            $namaFile  = $row['nama_file'];

            try {
                // ── 0. Auto-generate Ticket & Script Number if empty ──
                if (empty($ticket)) {
                    // Generate Ticket ID (SC-XXXX)
                    $lastSql = "SELECT TOP 1 ticket_id FROM script_request WHERE ticket_id LIKE 'SC-%' ORDER BY id DESC";
                    $lastStmt = db_query($conn, $lastSql);
                    $nextNumber = 1;
                    if ($lastStmt && $lastRow = db_fetch_array($lastStmt, DB_FETCH_ASSOC)) {
                        $parts = explode('-', $lastRow['ticket_id'] ?? '');
                        if (count($parts) === 2 && is_numeric($parts[1])) {
                            $nextNumber = intval($parts[1]) + 1;
                        }
                    }
                    $ticket = sprintf("SC-%04d", $nextNumber);
                }
                
                // Generate Script Number based on rules
                $jenisCode = ($jenis === 'Konvensional') ? 'KONV' : 'SYR';
                $mediaMapping = ['WhatsApp'=>'WA', 'Robocoll'=>'RC', 'Surat'=>'SR', 'Email'=>'EM', 'VB'=>'VB', 'Chatbot'=>'CB', 'SMS'=>'SM', 'Others'=>'OT'];
                $mediaParts = array_map('trim', explode(',', $media));
                $abbreviations = [];
                foreach ($mediaParts as $part) {
                    $abbreviations[] = isset($mediaMapping[$part]) ? $mediaMapping[$part] : 'OT';
                }
                $mediaCode = implode('/', array_unique($abbreviations));
                $dateCode = date('d/m/y', strtotime($tglDibuat)); 
                
                $counterSql = "SELECT TOP 1 script_number FROM script_request WHERE script_number LIKE ? ORDER BY id DESC";
                $pattern = $jenisCode . '-' . $mediaCode . '-%';
                $counterStmt = db_query($conn, $counterSql, [$pattern]);
                
                $nextCounter = 1;
                if ($counterStmt && $counterRow = db_fetch_array($counterStmt, DB_FETCH_ASSOC)) {
                    $lastSn = $counterRow['script_number'];
                    $snParts = explode('-', $lastSn);
                    if (count($snParts) >= 4 && is_numeric($snParts[3])) {
                        $nextCounter = intval($snParts[3]) + 1;
                    }
                }
                $counterParam = sprintf("%04d", $nextCounter);
                $scriptNumber = sprintf("%s-%s-%s-%s-%02d", $jenisCode, $mediaCode, $dateCode, $counterParam, 1);

                // ── 1. Insert into script_request ──────────────
                $sqlReq = "INSERT INTO script_request (
                    ticket_id, script_number, title, jenis, produk, kategori, media, mode,
                    status, current_role, version, created_by, created_at, updated_at, is_deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'FILE_UPLOAD', 'LIBRARY', 'DONE', 1, ?, ?, GETDATE(), 0);
                SELECT SCOPE_IDENTITY() as id";

                $paramsReq = [$ticket, $scriptNumber, $title, $jenis, $produk, $kategori, $media, $userId, $tglDibuat];
                $stmtReq = db_query($conn, $sqlReq, $paramsReq);

                if ($stmtReq === false) {
                    throw new \Exception('Gagal insert script_request: ' . print_r(db_errors(), true));
                }

                db_next_result($stmtReq);
                $idRow = db_fetch_array($stmtReq, DB_FETCH_ASSOC);
                $requestId = $idRow['id'] ?? null;

                if (!$requestId) {
                    throw new \Exception('Gagal mendapatkan Request ID setelah insert.');
                }

                // ── 2. Create folder & resolve ZIP path ────────
                $safeTicket = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ticket);
                $scriptFolder = $uploadsDir . $safeTicket . '/';
                if (!is_dir($scriptFolder)) {
                    mkdir($scriptFolder, 0777, true);
                }

                $zipInternalPath = $zipContents[$namaFile] ?? null;
                $destPath = $scriptFolder . $namaFile;
                $relativePath = 'storage/uploads/' . $safeTicket . '/' . $namaFile;

                // ── 3. Insert into script_library ──────────────
                $htmlContent = null;

                // If Extract HTML is enabled, parse Excel/Word file to HTML
                if ($extractHtml && $zipInternalPath) {
                    $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
                    if (in_array($ext, ['xls', 'xlsx', 'doc', 'docx'])) {
                        // Extract file to temp location for parsing
                        $tempExtractDir = $baseDir . '/storage/temp_zips/';
                        $tempFilePath = $tempExtractDir . 'parse_' . time() . '_' . $namaFile;
                        $fileData = $zip->getFromName($zipInternalPath);
                        if ($fileData !== false) {
                            file_put_contents($tempFilePath, $fileData);
                            $parseResult = \App\Helpers\FileHandler::parseFile($tempFilePath, $ext);
                            if (is_array($parseResult) && !empty($parseResult['preview_html'])) {
                                $htmlContent = $parseResult['preview_html'];
                            } elseif (is_string($parseResult)) {
                                $htmlContent = $parseResult;
                            }
                            @unlink($tempFilePath);
                        }
                    }
                }

                $sqlLib = "INSERT INTO script_library (
                    request_id, script_number, media, content, version, is_active, start_date, created_at
                ) VALUES (?, ?, ?, ?, 1, 1, ?, GETDATE())";

                $paramsLib = [$requestId, $scriptNumber, $media, $htmlContent, $tglDibuat];
                if (db_query($conn, $sqlLib, $paramsLib) === false) {
                    throw new \Exception('Gagal insert script_library: ' . print_r(db_errors(), true));
                }

                if ($zipInternalPath) {
                    // Extract the specific file from ZIP to dest
                    $fileContent = $zip->getFromName($zipInternalPath);
                    if ($fileContent !== false) {
                        file_put_contents($destPath, $fileContent);
                    }
                }

                // ── 4. Insert into script_files ────────────────
                $reqModel->saveFileInfo($requestId, 'TEMPLATE', $namaFile, $relativePath, $userId);

                // ── 5. Insert review doc files (optional) ──────
                $reviewDocMap = [
                    'file_legal'   => 'LEGAL',
                    'file_cx'      => 'CX',
                    'file_syariah' => 'LEGAL_SYARIAH',
                    'file_lpp'     => 'LPP',
                ];
                foreach ($reviewDocMap as $rowKey => $dbType) {
                    $docFileName = trim($row[$rowKey] ?? '');
                    if (empty($docFileName)) continue;

                    $docZipPath = $zipContents[$docFileName] ?? null;
                    if ($docZipPath) {
                        $docDest = $scriptFolder . $docFileName;
                        $docData = $zip->getFromName($docZipPath);
                        if ($docData !== false) {
                            file_put_contents($docDest, $docData);
                        }
                        $docRelPath = 'storage/uploads/' . $safeTicket . '/' . $docFileName;
                        $reqModel->saveFileInfo($requestId, $dbType, $docFileName, $docRelPath, $userId);
                    }
                }

                // ── 6. Insert audit trail ──────────────────────
                $reqModel->logAudit(
                    $requestId, $scriptNumber,
                    'LEGACY_IMPORT',
                    'ADMIN',
                    $userId,
                    "Imported from Legacy Data (Bulk Migration Tool)"
                );

                $importMsg = $htmlContent ? 'Berhasil diimport + HTML extracted' : 'Berhasil diimport';
                $results[] = ['ticket' => $ticket, 'title' => $title, 'status' => 'success', 'message' => $importMsg];
                $successCount++;

            } catch (\Exception $e) {
                $results[] = ['ticket' => $ticket, 'title' => $title, 'status' => 'error', 'message' => $e->getMessage()];
                $failCount++;
            }
        }

        $zip->close();

        // ── Cleanup temp files ─────────────────────────────────
        @unlink($excelPath);
        @unlink($zipPath);
        unset($_SESSION['legacy_import']);

        $this->view('admin/legacy_import', [
            'step'         => 'result',
            'results'      => $results,
            'successCount' => $successCount,
            'failCount'    => $failCount,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  MODE B: SINGLE-FILE TEXT IMPORT (No ZIP needed)
    // ═══════════════════════════════════════════════════════════

    /**
     * Download CSV template for text-based import (Mode B)
     */
    public function downloadTextTemplate() {
        $this->guardAdmin();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="Template_Migrasi_Teks.csv"');
        header('Cache-Control: max-age=0');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

        fputcsv($out, ['No_Tiket (Kosongkan utk Auto)', 'Judul_Script', 'Jenis', 'Produk', 'Kategori', 'Media', 'Tgl_Dibuat', 'Isi_Teks_Script', 'File_Legal (Opsional)', 'File_CX (Opsional)', 'File_Syariah (Opsional)', 'File_LPP (Opsional)']);

        // Example rows
        fputcsv($out, ['', 'Script Tagihan Past Due', 'Konvensional', 'Kartu Kredit', 'Past Due', 'WhatsApp', '2023-05-10', 'Selamat pagi Bapak/Ibu, kami dari bagian penagihan ingin menginformasikan bahwa tagihan Anda sudah jatuh tempo. Mohon segera melakukan pembayaran.', 'Legal_CC.pdf', '', '', '']);
        fputcsv($out, ['', 'Script Promo KPR Syariah', 'Syariah', 'KPR Syariah', 'Promo', 'Email', '2024-01-15', 'Yth Nasabah, nikmati promo spesial KPR Syariah dengan margin kompetitif. Hubungi cabang terdekat untuk informasi lebih lanjut.', '', '', 'Syariah_KPR.pdf', '']);

        fclose($out);
        exit;
    }

    /**
     * Preview for text-based import (Mode B) — single CSV, no ZIP
     */
    public function previewText() {
        $this->guardAdmin();

        $errors = [];
        $rows   = [];
        $zipContents = [];

        if (empty($_FILES['text_csv_file']['tmp_name'])) {
            $errors[] = 'File CSV Template Teks wajib diupload.';
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        // ── Read ZIP if provided (for review doc attachments) ──
        $hasZip = !empty($_FILES['text_zip_file']['tmp_name']);
        if ($hasZip) {
            $zipPath = $_FILES['text_zip_file']['tmp_name'];
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (substr($name, -1) === '/' || strpos($name, '__MACOSX') !== false) continue;
                    $zipContents[basename($name)] = $name;
                }
                $zip->close();
            } else {
                $errors[] = 'Gagal membuka file ZIP. Pastikan format valid.';
                $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
                return;
            }
        }

        $csvPath = $_FILES['text_csv_file']['tmp_name'];
        try {
            $handle = fopen($csvPath, 'r');
            if (!$handle) throw new \Exception('Gagal membaca file CSV.');

            // Skip BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") rewind($handle);

            // Read header
            $header = fgetcsv($handle);
            if (!$header || count($header) < 8) {
                throw new \Exception('Format CSV tidak valid. Pastikan ada minimal 8 kolom (sesuai template).');
            }

            // Read all raw lines first
            $rawLines = [];
            while (($line = fgetcsv($handle)) !== false) {
                $rawLines[] = $line;
            }
            fclose($handle);

            // ── Orphan Line Repair Engine ─────────────────────
            $mergedLines = [];
            foreach ($rawLines as $line) {
                if (count($line) >= 8) {
                    $mergedLines[] = $line;
                } elseif (!empty($mergedLines)) {
                    $lastIdx = count($mergedLines) - 1;
                    // Merge into column 7 (Isi_Teks_Script), not the last column
                    $orphanText = implode(' ', array_map('trim', $line));
                    $mergedLines[$lastIdx][7] = ($mergedLines[$lastIdx][7] ?? '') . "\n" . $orphanText;
                }
            }

            $r = 1;
            foreach ($mergedLines as $line) {
                $r++;

                $ticket    = trim($line[0] ?? '');
                $title     = trim($line[1] ?? '');
                $jenis     = trim($line[2] ?? '');
                $produk    = trim($line[3] ?? '');
                $kategori  = trim($line[4] ?? '');
                $media     = trim($line[5] ?? '');
                $tglDibuat = trim($line[6] ?? '');
                $isiTeks   = trim($line[7] ?? '');
                // Review doc columns (optional)
                $fileLegal   = trim($line[8] ?? '');
                $fileCx      = trim($line[9] ?? '');
                $fileSyariah = trim($line[10] ?? '');
                $fileLpp     = trim($line[11] ?? '');

                if (empty($title) && empty($isiTeks)) continue;

                $rowErrors = [];
                if (empty($title))    $rowErrors[] = 'Judul kosong';
                if (empty($jenis))    $rowErrors[] = 'Jenis kosong';
                if (empty($isiTeks))  $rowErrors[] = 'Isi Teks Script kosong';

                if (!empty($jenis) && !in_array($jenis, ['Konvensional', 'Syariah'])) {
                    $rowErrors[] = "Jenis harus 'Konvensional' atau 'Syariah'";
                }

                // Validate review doc files
                $reviewDocFiles = [
                    'legal' => $fileLegal, 'cx' => $fileCx,
                    'syariah' => $fileSyariah, 'lpp' => $fileLpp
                ];
                $hasAnyDoc = !empty($fileLegal) || !empty($fileCx) || !empty($fileSyariah) || !empty($fileLpp);
                $reviewDocFound = [];

                if ($hasAnyDoc && !$hasZip) {
                    $rowErrors[] = 'Kolom file review diisi tapi tidak ada file ZIP yang diupload';
                } elseif ($hasAnyDoc && $hasZip) {
                    foreach ($reviewDocFiles as $docKey => $docFile) {
                        if (!empty($docFile)) {
                            $reviewDocFound[$docKey] = isset($zipContents[$docFile]);
                            if (!$reviewDocFound[$docKey]) {
                                $rowErrors[] = "File " . strtoupper($docKey) . " '$docFile' tidak ditemukan di ZIP";
                            }
                        }
                    }
                }

                $parsedDate = null;
                if (!empty($tglDibuat)) {
                    $ts = strtotime($tglDibuat);
                    $parsedDate = $ts ? date('Y-m-d', $ts) : null;
                    if (!$parsedDate) $rowErrors[] = "Format tanggal tidak valid: $tglDibuat";
                }

                // Build 80-char preview
                $cleanText = str_replace(["\r\n", "\r", "\n"], ' ', $isiTeks);
                $contentPreview = mb_strlen($cleanText) > 80
                    ? mb_substr($cleanText, 0, 80) . '...'
                    : $cleanText;

                $rows[] = [
                    'row_num'         => $r,
                    'ticket'          => $ticket,
                    'title'           => $title,
                    'jenis'           => $jenis,
                    'produk'          => $produk,
                    'kategori'        => $kategori,
                    'media'           => $media,
                    'tgl_dibuat'      => $parsedDate ?? date('Y-m-d'),
                    'isi_teks'        => $isiTeks,
                    'content_preview' => $contentPreview,
                    'file_legal'      => $fileLegal,
                    'file_cx'         => $fileCx,
                    'file_syariah'    => $fileSyariah,
                    'file_lpp'        => $fileLpp,
                    'review_doc_found' => $reviewDocFound,
                    'errors'          => $rowErrors,
                    'status'          => empty($rowErrors) ? 'ready' : 'error',
                ];
            }
        } catch (\Exception $e) {
            $errors[] = 'Gagal membaca file CSV: ' . $e->getMessage();
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        if (empty($rows)) {
            $errors[] = 'File CSV kosong atau tidak ada data.';
            $this->view('admin/legacy_import', ['step' => 'upload', 'errors' => $errors]);
            return;
        }

        // ── Save temp ZIP for execute step ──────────────────────
        $tempZipPath = null;
        if ($hasZip) {
            $tempDir = dirname(__DIR__, 2) . '/storage/temp_zips/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $tempZipPath = $tempDir . 'text_import_' . time() . '.zip';
            move_uploaded_file($_FILES['text_zip_file']['tmp_name'], $tempZipPath);
        }

        // Save to session
        $_SESSION['legacy_import_text'] = [
            'rows'     => $rows,
            'zip_path' => $tempZipPath,
        ];

        $hasErrors = !empty(array_filter($rows, fn($r) => $r['status'] === 'error'));

        $this->view('admin/legacy_import', [
            'step'        => 'preview',
            'import_mode' => 'text',
            'rows'        => $rows,
            'hasErrors'   => $hasErrors,
            'totalRows'   => count($rows),
            'readyRows'   => count(array_filter($rows, fn($r) => $r['status'] === 'ready')),
        ]);
    }

    /**
     * Execute text-based import (Mode B) — insert content directly into DB
     */
    public function executeText() {
        $this->guardAdmin();

        if (empty($_SESSION['legacy_import_text'])) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => ['Sesi import teks tidak ditemukan. Silakan upload ulang.'],
            ]);
            return;
        }

        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $importData  = $_SESSION['legacy_import_text'];
        $rows        = $importData['rows'];
        $zipPath     = $importData['zip_path'] ?? null;
        $readyRows   = array_filter($rows, fn($r) => $r['status'] === 'ready');

        if (empty($readyRows)) {
            $this->view('admin/legacy_import', [
                'step' => 'upload',
                'errors' => ['Tidak ada data yang siap untuk diimport.'],
            ]);
            return;
        }

        // ── Open ZIP if available (for review doc files) ───────
        $zip = null;
        $zipContents = [];
        if ($zipPath && file_exists($zipPath)) {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (substr($name, -1) === '/' || strpos($name, '__MACOSX') !== false) continue;
                    $zipContents[basename($name)] = $name;
                }
            } else {
                $zip = null;
            }
        }

        $reqModel = $this->model('RequestModel');
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $conn = db_connect($config['host'], ['Database' => $config['dbname'], 'UID' => $config['user'], 'PWD' => $config['pass']]);
        $userId = $_SESSION['user']['userid'] ?? 'ADMIN_MIGRATION';
        $baseDir    = dirname(__DIR__, 2);
        $uploadsDir = $baseDir . '/storage/uploads/';
        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($readyRows as $row) {
            $ticket   = trim($row['ticket']);
            $title    = $row['title'];
            $jenis    = $row['jenis'];
            $produk   = $row['produk'];
            $kategori = $row['kategori'];
            $media    = $row['media'];
            $tglDibuat = $row['tgl_dibuat'];
            $isiTeks   = $row['isi_teks'];

            try {
                // ── 0. Auto-generate Ticket & Script Number ──
                if (empty($ticket)) {
                    $lastSql = "SELECT TOP 1 ticket_id FROM script_request WHERE ticket_id LIKE 'SC-%' ORDER BY id DESC";
                    $lastStmt = db_query($conn, $lastSql);
                    $nextNumber = 1;
                    if ($lastStmt && $lastRow = db_fetch_array($lastStmt, DB_FETCH_ASSOC)) {
                        $parts = explode('-', $lastRow['ticket_id'] ?? '');
                        if (count($parts) === 2 && is_numeric($parts[1])) {
                            $nextNumber = intval($parts[1]) + 1;
                        }
                    }
                    $ticket = sprintf("SC-%04d", $nextNumber);
                }

                $jenisCode = ($jenis === 'Konvensional') ? 'KONV' : 'SYR';
                $mediaMapping = ['WhatsApp'=>'WA', 'Robocoll'=>'RC', 'Surat'=>'SR', 'Email'=>'EM', 'VB'=>'VB', 'Chatbot'=>'CB', 'SMS'=>'SM', 'Others'=>'OT'];
                $mediaParts = array_map('trim', explode(',', $media));
                $abbreviations = [];
                foreach ($mediaParts as $part) {
                    $abbreviations[] = isset($mediaMapping[$part]) ? $mediaMapping[$part] : 'OT';
                }
                $mediaCode = implode('/', array_unique($abbreviations));
                $dateCode = date('d/m/y', strtotime($tglDibuat));

                $counterSql = "SELECT TOP 1 script_number FROM script_request WHERE script_number LIKE ? ORDER BY id DESC";
                $pattern = $jenisCode . '-' . $mediaCode . '-%';
                $counterStmt = db_query($conn, $counterSql, [$pattern]);
                $nextCounter = 1;
                if ($counterStmt && $counterRow = db_fetch_array($counterStmt, DB_FETCH_ASSOC)) {
                    $snParts = explode('-', $counterRow['script_number']);
                    if (count($snParts) >= 4 && is_numeric($snParts[3])) {
                        $nextCounter = intval($snParts[3]) + 1;
                    }
                }
                $scriptNumber = sprintf("%s-%s-%s-%s-%02d", $jenisCode, $mediaCode, $dateCode, sprintf("%04d", $nextCounter), 1);

                // ── 1. Insert into script_request (FREE_INPUT mode) ──
                $sqlReq = "INSERT INTO script_request (
                    ticket_id, script_number, title, jenis, produk, kategori, media, mode,
                    status, current_role, version, created_by, created_at, updated_at, is_deleted
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'FREE_INPUT', 'LIBRARY', 'DONE', 1, ?, ?, GETDATE(), 0);
                SELECT SCOPE_IDENTITY() as id";

                $paramsReq = [$ticket, $scriptNumber, $title, $jenis, $produk, $kategori, $media, $userId, $tglDibuat];
                $stmtReq = db_query($conn, $sqlReq, $paramsReq);

                if ($stmtReq === false) {
                    throw new \Exception('Gagal insert script_request: ' . print_r(db_errors(), true));
                }

                db_next_result($stmtReq);
                $idRow = db_fetch_array($stmtReq, DB_FETCH_ASSOC);
                $requestId = $idRow['id'] ?? null;

                if (!$requestId) {
                    throw new \Exception('Gagal mendapatkan Request ID setelah insert.');
                }

                // ── 2. Convert text to HTML and insert into script_library ──
                $htmlContent = nl2br(htmlspecialchars($isiTeks, ENT_QUOTES, 'UTF-8'));

                $sqlLib = "INSERT INTO script_library (
                    request_id, script_number, media, content, version, is_active, start_date, created_at
                ) VALUES (?, ?, ?, ?, 1, 1, ?, GETDATE())";

                $paramsLib = [$requestId, $scriptNumber, $media, $htmlContent, $tglDibuat];
                if (db_query($conn, $sqlLib, $paramsLib) === false) {
                    throw new \Exception('Gagal insert script_library: ' . print_r(db_errors(), true));
                }

                // ── 3. Insert review doc files (optional) ──
                if ($zip) {
                    $safeTicket = preg_replace('/[\/\\\\:*?"<>|]/', '_', $ticket);
                    $scriptFolder = $uploadsDir . $safeTicket . '/';
                    if (!is_dir($scriptFolder)) mkdir($scriptFolder, 0777, true);

                    $reviewDocMap = [
                        'file_legal'   => 'LEGAL',
                        'file_cx'      => 'CX',
                        'file_syariah' => 'LEGAL_SYARIAH',
                        'file_lpp'     => 'LPP',
                    ];
                    foreach ($reviewDocMap as $rowKey => $dbType) {
                        $docFileName = trim($row[$rowKey] ?? '');
                        if (empty($docFileName)) continue;

                        $docZipPath = $zipContents[$docFileName] ?? null;
                        if ($docZipPath) {
                            $docDest = $scriptFolder . $docFileName;
                            $docData = $zip->getFromName($docZipPath);
                            if ($docData !== false) {
                                file_put_contents($docDest, $docData);
                            }
                            $docRelPath = 'storage/uploads/' . $safeTicket . '/' . $docFileName;
                            $reqModel->saveFileInfo($requestId, $dbType, $docFileName, $docRelPath, $userId);
                        }
                    }
                }

                // ── 4. Audit trail ──
                $reqModel->logAudit(
                    $requestId, $scriptNumber,
                    'LEGACY_IMPORT',
                    'ADMIN',
                    $userId,
                    "Imported from Legacy Data (Text Mode - Bulk Migration)"
                );

                $results[] = ['ticket' => $ticket, 'title' => $title, 'status' => 'success', 'message' => 'Berhasil diimport (Teks)'];
                $successCount++;

            } catch (\Exception $e) {
                $results[] = ['ticket' => $ticket, 'title' => $title, 'status' => 'error', 'message' => $e->getMessage()];
                $failCount++;
            }
        }

        if ($zip) $zip->close();
        if ($zipPath) @unlink($zipPath);
        unset($_SESSION['legacy_import_text']);

        $this->view('admin/legacy_import', [
            'step'         => 'result',
            'results'      => $results,
            'successCount' => $successCount,
            'failCount'    => $failCount,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════

    private function guardAdmin() {
        if (!isset($_SESSION['user']) || ($_SESSION['user']['dept'] ?? '') !== 'ADMIN') {
            http_response_code(403);
            die('Access Denied: Admin only.');
        }
    }
}
// [END UPDATE 04-Mar-2026]
