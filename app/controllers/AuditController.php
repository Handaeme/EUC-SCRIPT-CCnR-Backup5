<?php
namespace App\Controllers;

use App\Core\Controller;

class AuditController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $search = $_GET['search'] ?? null; // Capture Search
        
        // Sorting Logic
        $sortUpdated = $_GET['sort_updated'] ?? null;
        $sortColumn = 'created_at'; // Default
        $sortOrder = 'DESC'; // Default
        
        if ($sortUpdated) {
             $sortColumn = 'updated_at';
             $sortOrder = $sortUpdated;
        }

        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        $filterOptions = [];
        
        foreach ($filterCols as $col) {
            $filterOptions[$col] = $reqModel->getDistinctRequestValues($col);
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }
        
        // Fetch Data with Search Support
        $logs = $reqModel->getAuditExportData($startDate, $endDate, $sortColumn, $sortOrder, $filters, $search);
        
        // Pagination
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 10;
        $totalItems = count($logs);
        $totalPages = max(1, ceil($totalItems / $perPage));
        $paginatedLogs = array_slice($logs, ($page - 1) * $perPage, $perPage);
        
        // AJAX Handler for Live Search
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            header('Content-Type: application/json');
            
            // Render Table Rows
            ob_start();
            $logs = $paginatedLogs; // View expects $logs
            include __DIR__ . '/../views/audit/table_rows.php';
            $rowsHtml = ob_get_clean();
            
            // Render Pagination
            ob_start();
            $currentPage = $page; // View expects these
            include __DIR__ . '/../views/layouts/pagination.php';
            $paginationHtml = ob_get_clean();
            
            echo json_encode([
                'rows' => $rowsHtml,
                'pagination' => $paginationHtml,
                'total' => $totalItems
            ]);
            exit;
        }
        
        $this->view('audit/index', [
            'logs' => $paginatedLogs,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'sortUpdated' => $sortUpdated, // Pass for UI toggle
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'search' => $search
        ]);
    }

    public function export() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Capture Advanced Filters for Export
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        foreach ($filterCols as $col) {
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }
        
        // Capture Search Filter for Export
        $search = $_GET['search'] ?? null;
        
        // Pass filters to model (including search)
        $data = $reqModel->getAuditExportData($startDate, $endDate, 'created_at', 'DESC', $filters, $search);
        
        // Set headers for Excel download
        header('Content-Type: application/vnd.ms-excel');
        // Dynamic Filename based on Filters
        $filename = 'Audit_Trail_' . date('Y-m-d_His');
        
        if (!empty($_GET['ticket_id'])) {
            $filename = 'Audit_Trail_' . preg_replace('/[^a-zA-Z0-9-]/', '', $_GET['ticket_id']);
        } elseif (!empty($startDate) && !empty($endDate)) {
            $filename = 'Audit_Trail_' . str_replace('-', '', $startDate) . '_to_' . str_replace('-', '', $endDate);
        }
        
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Generate Excel (HTML table format)
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<style>body, table, th, td { font-family: "Times New Roman", serif; font-size: 11pt; font-weight: normal !important; }</style>';
        echo '<body style="font-weight: normal;">';
        echo '<table border="1">';
        echo '<thead>';
        echo '<tr style="background-color: #f3f4f6; color: #1f2937; font-weight: normal; font-family: \'Times New Roman\', serif;">';
        echo '<th>No</th>';
        echo '<th>Ticket ID</th>';
        echo '<th>Nomor Script</th>';
        echo '<th>Jenis</th>';
        echo '<th>Produk</th>';
        echo '<th>Kategori</th>';
        echo '<th>Status</th>';
        echo '<th>Channel</th>';
        echo '<th>Script Content / Filename</th>';
        echo '<th>Created Date</th>';
        echo '<th>Maker</th>';
        echo '<th>SPV</th>';
        echo '<th>Status SPV</th>';
        echo '<th>Timestamp SPV</th>';
        echo '<th>PIC</th>';
        echo '<th>Status PIC</th>';
        echo '<th>Timestamp PIC</th>';
        echo '<th>Procedure</th>';
        echo '<th>Status Procedure</th>';
        echo '<th>Timestamp Procedure</th>';
        echo '<th>Legal Review</th>';
        echo '<th>CX Review</th>';
        echo '<th>Legal Syariah</th>';
        echo '<th>LPP</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Helper for Date Format
        $fmtDate = function($val, $withTime = false) {
            if (empty($val) || $val == '-' || $val == '0000-00-00 00:00:00') return '-';
            
            // Fix: Handle DateTime object directly
            if ($val instanceof \DateTime) {
                $ts = $val->getTimestamp();
            } else {
                $ts = strtotime($val);
            }

            if (!$ts) return '-';
            $format = $withTime ? 'd M Y, H:i' : 'd M Y';
            return date($format, $ts) . ($withTime ? ' WIB' : '');
        };

        $no = 1;
        foreach ($data as $row) {
            echo '<tr style="font-family: \'Times New Roman\', serif; font-size: 11pt; font-weight: normal; vertical-align: top;">';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $no++ . '</td>';
            // Format Ticket ID
            $tID = $row['ticket_id'];
            if(is_numeric($tID)) $tID = sprintf("SC-%04d", $tID);
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($tID) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['script_number']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['jenis']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['produk']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['kategori']) . '</td>';
            
            // Granular Status Logic for Export (compute BEFORE display)
            $exportStatus = $row['status'];
            if ($row['status_pic'] === 'APPROVE_PIC') {
                if (($row['raw_status'] ?? $row['status']) === 'PENDING_MAKER_CONFIRMATION') {
                    $exportStatus = 'WAITING MAKER CONFIRMATION';
                } else {
                    $hasLegal = $row['has_legal'] ?? 0;
                    $hasCX = $row['has_cx'] ?? 0;
                    $hasSyariah = $row['has_syariah'] ?? 0;
                    $hasLPP = $row['has_lpp'] ?? 0;

                    $docs = [];
                    if ($hasLegal > 0) $docs[] = 'LEGAL';
                    if ($hasCX > 0) $docs[] = 'CX';
                    if ($hasSyariah > 0) $docs[] = 'SYARIAH';
                    if ($hasLPP > 0) $docs[] = 'LPP';

                    if (count($docs) > 0) {
                        $exportStatus = implode(', ', $docs) . ' UPLOADED';
                    } else {
                        $exportStatus = 'WAITING PROCEDURE';
                    }
                }
            } elseif ($row['status_procedure'] === 'APPROVE_PROCEDURE') {
                if (empty($row['status_spv']) && empty($row['status_pic'])) {
                    $exportStatus = 'DIRECT PUBLISH';
                } else {
                    $exportStatus = 'LIBRARY';
                }
            } elseif ($row['status_spv'] === 'APPROVE_SPV') {
                $exportStatus = 'WAITING PIC';
            }

            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($exportStatus) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['media'] ?? $row['channel'] ?? '-') . '</td>';
            
            // Logic: Filename Only for File Upload, Clean Text for Free Input
            $contentDisplay = '';
            if (($row['mode'] ?? '') === 'FILE_UPLOAD') {
                 // Use Original Filename if available
                 $contentDisplay = !empty($row['script_content']) ? $row['script_content'] : ($row['script_number'] . '.xlsx');
            } else {
                // Free Input: Model already aggregated as "MEDIA:\nContent".
                $raw = $row['script_content'];
                $clean = htmlspecialchars($raw); 
                // Use <br> with MS-Excel specific style for in-cell break (triggers Auto-Wrap usually)
                $contentDisplay = str_replace(["\r\n", "\r", "\n"], "<br style='mso-data-placement:same-cell;' />", $clean);
            }
            
            // Remove 'white-space: pre-wrap' (can prevent auto-height) and use 'mso-number-format' for Text
            echo '<td style="font-family: \'Times New Roman\', serif; vertical-align: top; mso-number-format:\@;">' . $contentDisplay . '</td>';
            
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDate($row['created_date'], true) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['maker']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['spv']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['status_spv']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDate($row['timestamp_spv'], true) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['pic']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['status_pic']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDate($row['timestamp_pic'], true) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['procedure'] ?? '') . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . htmlspecialchars($row['status_procedure']) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDate($row['timestamp_procedure'], true) . '</td>';
            // Legal/CX/LegalSyariah/LPP: Format upload timestamp
            $legalTs = $row['legal_review'] ?? '';
            $cxTs = $row['cx_review'] ?? '';
            $lsTs = $row['legal_syariah'] ?? '';
            $lppTs = $row['lpp'] ?? '';
            // Extract timestamp from "Uploaded (YYYY-MM-DD HH:MM:SS)" and format
            $fmtDocStatus = function($val) use ($fmtDate) {
                if (empty($val)) return '-';
                if (preg_match('/Uploaded \((.+)\)/', $val, $m)) {
                    return $fmtDate($m[1], true);
                }
                return $val;
            };
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDocStatus($legalTs) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDocStatus($cxTs) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDocStatus($lsTs) . '</td>';
            echo '<td style="font-family: \'Times New Roman\', serif;">' . $fmtDocStatus($lppTs) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    public function exportDetail() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: ?controller=audit");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $data = $reqModel->getRequestDetail($id);

        if (!$data) {
            echo "Request not found.";
            exit;
        }

        $req = $data['request'];
        $logs = $data['logs'];
        $files = $data['files'];
        $content = $data['content'];

        // Format Ticket ID
        $ticketId = $req['ticket_id'] ?? $id;
        if (is_numeric($ticketId)) $ticketId = sprintf("SC-%04d", $ticketId);

        // Date formatter
        $fmtDate = function($val, $withTime = true) {
            if (empty($val) || $val == '-' || $val == '0000-00-00 00:00:00') return '-';
            if ($val instanceof \DateTime) $ts = $val->getTimestamp();
            else $ts = strtotime($val);
            if (!$ts) return '-';
            return date($withTime ? 'd M Y, H:i' : 'd M Y', $ts);
        };

        // HTML tag cleaner for content
        $cleanHtml = function($html) {
            if (empty($html)) return '';
            $text = html_entity_decode($html);
            // Remove deletion spans entirely
            $text = preg_replace('/<span[^>]*class="[^"]*deletion-span[^"]*"[^>]*>.*?<\/span>/is', '', $text);
            // Remove strikethrough
            $text = preg_replace('/<del>(.*?)<\/del>/is', '', $text);
            $text = preg_replace('/<s>(.*?)<\/s>/is', '', $text);
            $text = preg_replace('/<[^>]*style="[^"]*line-through[^"]*"[^>]*>.*?<\/[^>]*>/is', '', $text);
            // Keep revision span text (unwrap)
            $text = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $text);
            $text = preg_replace('/<span[^>]*style="[^"]*color:\s*red[^"]*"[^>]*>(.*?)<\/span>/is', '$1', $text);
            // Convert block elements to newlines
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $text);
            $text = preg_replace('/<\/p>/i', "\n", $text);
            $text = preg_replace('/<\/div>/i', "\n", $text);
            $text = strip_tags($text);
            return trim($text);
        };

        // Resolve user full names from logs
        $userRoles = [];
        // Maker
        $makerName = $req['created_by'] ?? 'MAKER';
        $userRoles['MAKER'] = $makerName;
        
        foreach ($logs as $log) {
            $role = $log['user_role'] ?? '';
            $fullname = $log['full_name'] ?? $log['user_id'] ?? '';
            $userId = $log['user_id'] ?? '';
            if ($role === 'SPV') $userRoles['SPV'] = $fullname ?: $userId;
            if ($role === 'PIC') $userRoles['PIC'] = $fullname ?: $userId;
            if ($role === 'PROCEDURE') $userRoles['PROCEDURE'] = $fullname ?: $userId;
        }

        // ---- Version Filter ----
        $selectedVer = isset($_GET['ver']) ? intval($_GET['ver']) : 0; // 0 = all versions
        
        // Filter versions if a specific version is selected
        if ($selectedVer > 0) {
            // Filter file upload versions (by version_number)
            if (!empty($content['versions'])) {
                $content['versions'] = array_filter($content['versions'], function($v) use ($selectedVer) {
                    return ($v['version_number'] ?? 0) == $selectedVer;
                });
                $content['versions'] = array_values($content['versions']); // Re-index
            }
            
            // Filter free input all_versions (map version_number from grouped versions)
            if (!empty($content['all_versions']) && !empty($content['versions'])) {
                // The selected version's meta tells us which raw rows belong to it
                $selectedMeta = $content['versions'][0] ?? null;
                if ($selectedMeta) {
                    $selectedDate = substr($selectedMeta['formatted_date'] ?? '', 0, 16); // Minute precision
                    $selectedStage = $selectedMeta['workflow_stage'] ?? '';
                    
                    $content['all_versions'] = array_filter($content['all_versions'], function($v) use ($selectedDate, $selectedStage) {
                        $vDate = substr($v['formatted_date'] ?? '', 0, 16);
                        $vStage = $v['workflow_stage'] ?? '';
                        return $vDate === $selectedDate && $vStage === $selectedStage;
                    });
                    $content['all_versions'] = array_values($content['all_versions']);
                }
            }
        }

        // ---- Build MHTML Multi-Sheet Excel ----
        $boundary = "----=_NextPart_AuditDetail_" . time();
        $filename = 'Audit_Detail_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $ticketId);
        if ($selectedVer > 0) $filename .= '_V' . $selectedVer;
        $filename .= '_' . date('Ymd');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $cellStyle = "font-family:'Times New Roman',serif; font-size:11pt; vertical-align:top;";
        $headerStyle = "font-family:'Times New Roman',serif; font-size:11pt; font-weight:bold; background-color:#f3f4f6;";

        // Collect all sheet definitions
        $sheets = [];

        // ====== SHEET 1: REQUEST INFO ======
        $infoHtml = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
        $infoHtml .= '<table border="1" style="border-collapse:collapse;">';
        
        $infoFields = [
            ['Ticket ID', $ticketId],
            ['Script Number', $req['script_number'] ?? '-'],
            ['Status', $req['status'] ?? '-'],
            ['Title / Purpose', $req['title'] ?? '-'],
            ['Jenis', $req['jenis'] ?? '-'],
            ['Produk', $req['produk'] ?? '-'],
            ['Kategori', $req['kategori'] ?? '-'],
            ['Media', $req['media'] ?? '-'],
            ['Mode', $req['mode'] ?? '-'],
            ['Maker', $userRoles['MAKER'] ?? '-'],
            ['SPV', $userRoles['SPV'] ?? '-'],
            ['PIC', $userRoles['PIC'] ?? '-'],
            ['Procedure', $userRoles['PROCEDURE'] ?? '-'],
            ['Created Date', $fmtDate($req['created_at'] ?? '')],
            ['Last Updated', $fmtDate($req['updated_at'] ?? '')],
        ];
        
        foreach ($infoFields as $field) {
            $infoHtml .= '<tr>';
            $infoHtml .= '<td style="' . $headerStyle . ' width:200px;">' . htmlspecialchars($field[0]) . '</td>';
            $infoHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($field[1]) . '</td>';
            $infoHtml .= '</tr>';
        }
        
        $infoHtml .= '</table></body></html>';
        $sheets[] = ['name' => 'Request Info', 'html' => $infoHtml];

        // ====== SHEETS PER MEDIA CHANNEL ======
        if (($req['mode'] ?? '') !== 'FILE_UPLOAD' && !empty($content['all_versions'])) {
            // Group all versions by media
            $mediaGroups = [];
            foreach ($content['all_versions'] as $ver) {
                $media = $ver['media'] ?? 'Content';
                if (!isset($mediaGroups[$media])) $mediaGroups[$media] = [];
                $mediaGroups[$media][] = $ver;
            }

            foreach ($mediaGroups as $media => $versions) {
                $mediaHtml = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
                $mediaHtml .= '<table border="1" style="border-collapse:collapse;">';
                
                // Header row
                $mediaHtml .= '<tr>';
                $mediaHtml .= '<th style="' . $headerStyle . ' width:60px;">No</th>';
                $mediaHtml .= '<th style="' . $headerStyle . ' width:120px;">Tahap</th>';
                $mediaHtml .= '<th style="' . $headerStyle . ' width:150px;">User</th>';
                $mediaHtml .= '<th style="' . $headerStyle . ' width:150px;">Tanggal</th>';
                $mediaHtml .= '<th style="' . $headerStyle . ' width:500px; mso-number-format:\'@\';">Isi Script</th>';
                $mediaHtml .= '</tr>';

                $no = 1;
                foreach ($versions as $ver) {
                    $stage = $ver['workflow_stage'] ?? 'DRAFT';
                    $user = $ver['user_full_name'] ?? $ver['version_user'] ?? $ver['created_by'] ?? '-';
                    $date = $ver['formatted_date'] ?? '';

                    // Stage display name
                    $stageDisplay = $stage;
                    if (stripos($stage, 'MAKER') !== false || $stage === 'DRAFT' || $stage === 'CREATED') $stageDisplay = 'MAKER';
                    elseif (stripos($stage, 'SPV') !== false) $stageDisplay = 'SPV';
                    elseif (stripos($stage, 'PIC') !== false) $stageDisplay = 'PIC';
                    elseif (stripos($stage, 'PROC') !== false) $stageDisplay = 'PROCEDURE';

                    $cleanContent = $cleanHtml($ver['content']);
                    // Convert newlines for Excel
                    $excelContent = str_replace(["\r\n", "\r", "\n"], "<br style='mso-data-placement:same-cell;' />", htmlspecialchars($cleanContent));

                    $mediaHtml .= '<tr style="' . $cellStyle . '">';
                    $mediaHtml .= '<td style="' . $cellStyle . ' text-align:center;">' . $no++ . '</td>';
                    $mediaHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($stageDisplay) . '</td>';
                    $mediaHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($user) . '</td>';
                    $mediaHtml .= '<td style="' . $cellStyle . '">' . $fmtDate($date) . '</td>';
                    $mediaHtml .= '<td style="' . $cellStyle . ' mso-number-format:\'@\';">' . $excelContent . '</td>';
                    $mediaHtml .= '</tr>';
                }

                $mediaHtml .= '</table></body></html>';
                
                $safeMediaName = preg_replace('/[:\\\\\/\?\*\[\]]/', '_', $media);
                $sheets[] = ['name' => substr($safeMediaName, 0, 31), 'html' => $mediaHtml];
            }
        } elseif (($req['mode'] ?? '') === 'FILE_UPLOAD' && !empty($content['versions'])) {
            // File upload: Show ACTUAL CONTENT per version per sheet
            // Each version has HTML content containing table data with possible red spans (reviewer additions)
            
            // Helper: Clean HTML for Excel output (keep red text, remove strikethrough)
            $cleanFileHtml = function($html) {
                if (empty($html)) return '<table border="1"><tr><td>(Empty)</td></tr></table>';
                
                // 1. Remove deletion spans entirely (strikethrough text)
                $html = preg_replace('/<span[^>]*class="[^"]*deletion-span[^"]*"[^>]*>.*?<\/span>/is', '', $html);
                $html = preg_replace('/<del>(.*?)<\/del>/is', '', $html);
                $html = preg_replace('/<s>(.*?)<\/s>/is', '', $html);
                $html = preg_replace('/<strike>(.*?)<\/strike>/is', '', $html);
                // Remove elements with line-through style
                $html = preg_replace('/<[^>]*style="[^"]*text-decoration:\s*line-through[^"]*"[^>]*>.*?<\/[^>]*>/is', '', $html);
                
                // 2. Convert revision spans / red spans to <font color=red><b>
                $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*style="[^"]*color:\s*red[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*style="[^"]*color:\s*#ff0000[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                
                // 3. Remove UI elements (buttons, tab nav, badges)
                $html = preg_replace('/<div[^>]*class="[^"]*sheet-tabs-nav[^"]*"[^>]*>.*?<\/div>/is', '', $html);
                $html = preg_replace('/<button[^>]*>.*?<\/button>/is', '', $html);
                
                // 4. Remove remaining non-red spans (keep content)
                $html = preg_replace('/<span[^>]*>(.*?)<\/span>/is', '$1', $html);
                
                // 5. Extract just the table if present
                if (preg_match('/<table[\s\S]*<\/table>/is', $html, $tableMatch)) {
                    return $tableMatch[0];
                }
                
                // If no table found, wrap content in a simple table
                $html = preg_replace('/<div[^>]*>/i', '', $html);
                $html = preg_replace('/<\/div>/i', '', $html);
                return '<table border="1"><tr><td style="font-family:\'Times New Roman\',serif; font-size:11pt; mso-number-format:\'@\';">' . trim($html) . '</td></tr></table>';
            };

            foreach ($content['versions'] as $ver) {
                $stage = $ver['workflow_stage'] ?? 'DRAFT';
                $user = $ver['user_full_name'] ?? $ver['created_by'] ?? '-';
                $vNum = $ver['version_number'] ?? '?';
                
                // Stage display name
                $stageDisplay = $stage;
                if (stripos($stage, 'MAKER') !== false || $stage === 'DRAFT' || $stage === 'CREATED') $stageDisplay = 'MAKER';
                elseif (stripos($stage, 'SPV') !== false) $stageDisplay = 'SPV';
                elseif (stripos($stage, 'PIC') !== false) $stageDisplay = 'PIC';
                elseif (stripos($stage, 'PROC') !== false) $stageDisplay = 'PROCEDURE';

                // Parse inner sheet panes from the version HTML
                $verContent = $ver['content'] ?? '';
                
                // Try to extract individual sheet panes
                if (preg_match_all('/<div[^>]*class=["\']sheet-pane["\'][^>]*data-sheet-name=["\']([^"\']*)["\'][^>]*>(.*?)<\/div>\s*(?=<div|<\/div>)/is', $verContent, $paneMatches, PREG_SET_ORDER)) {
                    // Multiple sheets in this version
                    foreach ($paneMatches as $pane) {
                        $sheetMedia = $pane[1] ?: 'Sheet';
                        $sheetContent = $pane[2];
                        
                        $cleanTable = $cleanFileHtml($sheetContent);
                        
                        $sheetHtml = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8">';
                        $sheetHtml .= '<style>td { font-family: "Times New Roman", serif; font-size:11pt; mso-number-format:"@"; }</style>';
                        $sheetHtml .= '</head><body>' . $cleanTable . '</body></html>';
                        
                        $sheetName = "V{$vNum} {$stageDisplay} ({$sheetMedia})";
                        $sheetName = preg_replace('/[:\\\\\/\?\*\[\]]/', '_', $sheetName);
                        $sheets[] = ['name' => substr($sheetName, 0, 31), 'html' => $sheetHtml];
                    }
                } else {
                    // Single content block or no sheet-pane structure — output the whole version content
                    $cleanTable = $cleanFileHtml($verContent);
                    
                    $sheetHtml = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8">';
                    $sheetHtml .= '<style>td { font-family: "Times New Roman", serif; font-size:11pt; mso-number-format:"@"; }</style>';
                    $sheetHtml .= '</head><body>' . $cleanTable . '</body></html>';
                    
                    $sheetName = "V{$vNum} {$stageDisplay}";
                    $sheetName = preg_replace('/[:\\\\\/\?\*\[\]]/', '_', $sheetName);
                    $sheets[] = ['name' => substr($sheetName, 0, 31), 'html' => $sheetHtml];
                }
            }
        }

        // ====== SHEET: REVIEW NOTES ======
        $notesHtml = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="utf-8"></head><body>';
        $notesHtml .= '<table border="1" style="border-collapse:collapse;">';
        $notesHtml .= '<tr>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:60px;">No</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:120px;">Action</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:100px;">Role</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:150px;">User</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:120px;">Group</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:150px;">Date</th>';
        $notesHtml .= '<th style="' . $headerStyle . ' width:300px;">Details</th>';
        $notesHtml .= '</tr>';

        $no = 1;
        foreach ($logs as $log) {
            $action = $log['action'] ?? '-';
            $role = $log['user_role'] ?? '-';
            $user = $log['full_name'] ?? $log['user_id'] ?? '-';
            $group = $log['group_name'] ?? '-';
            $date = $log['created_at'] ?? '';
            $details = $log['details'] ?? '-';

            $notesHtml .= '<tr style="' . $cellStyle . '">';
            $notesHtml .= '<td style="' . $cellStyle . ' text-align:center;">' . $no++ . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($action) . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($role) . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($user) . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($group) . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . $fmtDate($date) . '</td>';
            $notesHtml .= '<td style="' . $cellStyle . '">' . htmlspecialchars($details) . '</td>';
            $notesHtml .= '</tr>';
        }
        $notesHtml .= '</table></body></html>';
        $sheets[] = ['name' => 'Review Notes', 'html' => $notesHtml];

        // ====== ASSEMBLE MHTML ======
        $mhtml = "MIME-Version: 1.0\r\nContent-Type: multipart/related; boundary=\"{$boundary}\"\r\n\r\n";

        // Workbook Definition
        $mhtml .= "--{$boundary}\r\nContent-Location: file:///C:/dummy/workbook.htm\r\nContent-Type: text/html; charset=\"utf-8\"\r\n\r\n";
        $mhtml .= '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
        
        foreach ($sheets as $idx => $sheet) {
            $mhtml .= '<x:ExcelWorksheet><x:Name>' . htmlspecialchars($sheet['name']) . '</x:Name>';
            $mhtml .= '<x:WorksheetSource HRef="sheet' . $idx . '.htm"/></x:ExcelWorksheet>';
        }
        
        $mhtml .= '</x:ExcelWorksheets></x:ExcelWorkbook></xml></head><body></body></html>';
        $mhtml .= "\r\n\r\n";

        // Sheet Parts
        foreach ($sheets as $idx => $sheet) {
            $mhtml .= "--{$boundary}\r\nContent-Location: file:///C:/dummy/sheet{$idx}.htm\r\nContent-Type: text/html; charset=\"utf-8\"\r\n\r\n";
            $mhtml .= $sheet['html'];
            $mhtml .= "\r\n\r\n";
        }

        $mhtml .= "--{$boundary}--\r\n";

        echo $mhtml;
        exit;
    }

    public function detail() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: ?controller=audit");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $detailFn = 'getRequestDetail'; // Just to be safe with method existence check if needed, but direct call is fine.
        
        $data = $reqModel->getRequestDetail($id);

        if (!$data) {
            // Handle not found
            echo "Request not found.";
            exit;
        }

        $this->view('audit/detail', $data);
    }

    public function delete() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        // 1. Strict Role Check (Admin Only)
        $role = $_SESSION['user']['dept'] ?? '';
        $userid = strtolower($_SESSION['user']['userid'] ?? '');
        
        // Allow if Role is ADMIN OR User is 'admin_script' (or 'admin')
        if ($role !== 'ADMIN' && !in_array($userid, ['admin', 'admin_script'])) {
            header("Location: ?controller=audit&error=" . urlencode("Unauthorized Access"));
            exit;
        }

        // 2. Get ID
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: ?controller=audit&error=" . urlencode("Invalid Request ID"));
            exit;
        }

        // 3. Process Delete
        $reqModel = $this->model('RequestModel');
        $result = $reqModel->deleteRequest($id, $_SESSION['user']['userid']);

        $redirect = $_GET['redirect'] ?? 'audit';
        $location = "?controller=audit"; // Default
        
        if ($redirect === 'library') {
            $location = "?controller=dashboard&action=library";
        }
        
        if ($result) {
            header("Location: " . $location . "&msg=" . urlencode("Script successfully removed from view."));
        } else {
            header("Location: " . $location . "&error=" . urlencode("Failed to delete script."));
        }
        exit;
    }
}
