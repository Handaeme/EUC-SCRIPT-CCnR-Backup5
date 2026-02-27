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
        $filterCols = ['jenis', 'produk', 'kategori', 'media', 'status'];
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

        // ====== ASSEMBLE SINGLE-SHEET HTML EXCEL ======
        $filename = 'Audit_Detail_' . preg_replace('/[^a-zA-Z0-9-]/', '_', $ticketId);
        if ($selectedVer > 0) $filename .= '_V' . $selectedVer;
        $filename .= '_' . date('Ymd_His');

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml>';
        echo '<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Audit Detail</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
        echo '</xml><![endif]-->';
        echo '<style>';
        echo 'body, table { font-family: "Times New Roman", serif; font-size: 11pt; }';
        echo 'table { border-collapse: collapse; margin-bottom: 20px; width: 100%; }';
        echo 'th, td { border: 1px solid #000000; padding: 8px; vertical-align: top; }';
        echo 'th { background-color: #f2f2f2; font-weight: bold; text-align: left; }';
        echo '.text { mso-number-format:"\@"; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        // 1. General Info
        echo '<h2>Ticket Information</h2>';
        echo '<table>';
        echo '<tr><th width="150">Ticket ID</th><td class="text">' . htmlspecialchars($ticketId) . '</td></tr>';
        echo '<tr><th>Script Number</th><td class="text">' . htmlspecialchars($req['script_number'] ?? '-') . '</td></tr>';
        echo '<tr><th>Title</th><td>' . htmlspecialchars($req['title'] ?? '-') . '</td></tr>';
        echo '<tr><th>Status</th><td>' . htmlspecialchars($req['status'] ?? '-') . '</td></tr>';
        echo '</table><br>';

        // 2. Script Content Versions
        echo '<h2>Script History / Content</h2>';
        
        if (($req['mode'] ?? '') !== 'FILE_UPLOAD' && !empty($content['all_versions'])) {
            // Group all versions by media
            $mediaGroups = [];
            foreach ($content['all_versions'] as $ver) {
                $media = $ver['media'] ?? 'Content';
                if (!isset($mediaGroups[$media])) $mediaGroups[$media] = [];
                $mediaGroups[$media][] = $ver;
            }

            foreach ($mediaGroups as $media => $versions) {
                echo '<h3>Media: ' . htmlspecialchars($media) . '</h3>';
                echo '<table>';
                echo '<tr>';
                echo '<th width="60">No</th>';
                echo '<th width="120">Tahap</th>';
                echo '<th width="150">User</th>';
                echo '<th width="150">Tanggal</th>';
                echo '<th width="500">Isi Script</th>';
                echo '</tr>';

                $no = 1;
                foreach ($versions as $ver) {
                    $stage = $ver['workflow_stage'] ?? 'DRAFT';
                    $user = $ver['user_full_name'] ?? $ver['version_user'] ?? $ver['created_by'] ?? '-';
                    $date = $ver['formatted_date'] ?? '';

                    $stageDisplay = $stage;
                    if (stripos($stage, 'MAKER') !== false || $stage === 'DRAFT' || $stage === 'CREATED') $stageDisplay = 'MAKER';
                    elseif (stripos($stage, 'SPV') !== false) $stageDisplay = 'SPV';
                    elseif (stripos($stage, 'PIC') !== false) $stageDisplay = 'PIC';
                    elseif (stripos($stage, 'PROC') !== false) $stageDisplay = 'PROCEDURE';

                    $cleanContent = $cleanHtml($ver['content']);
                    $excelContent = str_replace(["\r\n", "\r", "\n"], "<br style='mso-data-placement:same-cell;' />", htmlspecialchars($cleanContent));

                    echo '<tr>';
                    echo '<td style="text-align:center;">' . $no++ . '</td>';
                    echo '<td>' . htmlspecialchars($stageDisplay) . '</td>';
                    echo '<td>' . htmlspecialchars($user) . '</td>';
                    echo '<td>' . $fmtDate($date) . '</td>';
                    echo '<td>' . $excelContent . '</td>';
                    echo '</tr>';
                }
                echo '</table><br>';
            }
        } elseif (($req['mode'] ?? '') === 'FILE_UPLOAD' && !empty($content['versions'])) {
            // File upload: Show ACTUAL CONTENT per version
            $cleanFileHtml = function($html) {
                if (empty($html)) return '<table border="1"><tr><td>(Empty)</td></tr></table>';
                
                // Clean HTML
                $html = preg_replace('/<span[^>]*class="[^"]*deletion-span[^"]*"[^>]*>.*?<\/span>/is', '', $html);
                $html = preg_replace('/<del>(.*?)<\/del>/is', '', $html);
                $html = preg_replace('/<s>(.*?)<\/s>/is', '', $html);
                $html = preg_replace('/<strike>(.*?)<\/strike>/is', '', $html);
                $html = preg_replace('/<[^>]*style="[^"]*text-decoration:\s*line-through[^"]*"[^>]*>.*?<\/[^>]*>/is', '', $html);
                
                $html = preg_replace('/<span[^>]*class="[^"]*revision-span[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*class="[^"]*inline-comment[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*style="[^"]*color:\s*red[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                $html = preg_replace('/<span[^>]*style="[^"]*color:\s*#ff0000[^"]*"[^>]*>(.*?)<\/span>/is', '<font color="#FF0000"><b>$1</b></font>', $html);
                
                $html = preg_replace('/<div[^>]*class="[^"]*sheet-tabs-nav[^"]*"[^>]*>.*?<\/div>/is', '', $html);
                $html = preg_replace('/<button[^>]*>.*?<\/button>/is', '', $html);
                $html = preg_replace('/<span[^>]*>(.*?)<\/span>/is', '$1', $html);
                
                if (preg_match('/<table[\s\S]*<\/table>/is', $html, $tableMatch)) {
                    return $tableMatch[0];
                }
                
                $html = preg_replace('/<div[^>]*>/i', '', $html);
                $html = preg_replace('/<\/div>/i', '', $html);
                return '<table border="1"><tr><td style="font-family:\'Times New Roman\',serif; font-size:11pt; mso-number-format:\'@\';">' . trim($html) . '</td></tr></table>';
            };

            foreach ($content['versions'] as $ver) {
                $stage = $ver['workflow_stage'] ?? 'DRAFT';
                $user = $ver['user_full_name'] ?? $ver['created_by'] ?? '-';
                $vNum = $ver['version_number'] ?? '?';
                
                $stageDisplay = $stage;
                if (stripos($stage, 'MAKER') !== false || $stage === 'DRAFT' || $stage === 'CREATED') $stageDisplay = 'MAKER';
                elseif (stripos($stage, 'SPV') !== false) $stageDisplay = 'SPV';
                elseif (stripos($stage, 'PIC') !== false) $stageDisplay = 'PIC';
                elseif (stripos($stage, 'PROC') !== false) $stageDisplay = 'PROCEDURE';

                $verContent = $ver['content'] ?? '';
                $sheetNameBase = "V{$vNum} {$stageDisplay}";
                
                if (preg_match_all('/<div[^>]*class=["\']sheet-pane["\'][^>]*data-sheet-name=["\']([^"\']*)["\'][^>]*>(.*?)<\/div>\s*(?=<div|<\/div>)/is', $verContent, $paneMatches, PREG_SET_ORDER)) {
                    foreach ($paneMatches as $pane) {
                        $sheetMedia = $pane[1] ?: 'Sheet';
                        $cleanTable = $cleanFileHtml($pane[2]);
                        echo '<h3>' . htmlspecialchars($sheetNameBase . ' (' . $sheetMedia . ')') . '</h3>';
                        echo $cleanTable . '<br>';
                    }
                } else {
                    echo '<h3>' . htmlspecialchars($sheetNameBase) . '</h3>';
                    echo $cleanFileHtml($verContent) . '<br>';
                }
            }
        }

        // 3. Review Notes
        echo '<h2>Review & Audit Notes</h2>';
        echo '<table>';
        echo '<tr>';
        echo '<th width="60">No</th>';
        echo '<th width="120">Action</th>';
        echo '<th width="100">Role</th>';
        echo '<th width="150">User</th>';
        echo '<th width="120">Group</th>';
        echo '<th width="150">Date</th>';
        echo '<th width="300">Details</th>';
        echo '</tr>';

        $no = 1;
        foreach ($logs as $log) {
            $action = $log['action'] ?? '-';
            $role = $log['user_role'] ?? '-';
            $user = $log['full_name'] ?? $log['user_id'] ?? '-';
            $group = $log['group_name'] ?? '-';
            $date = $log['created_at'] ?? '';
            $details = $log['details'] ?? '-';

            echo '<tr>';
            echo '<td style="text-align:center;">' . $no++ . '</td>';
            echo '<td>' . htmlspecialchars($action) . '</td>';
            echo '<td>' . htmlspecialchars($role) . '</td>';
            echo '<td>' . htmlspecialchars($user) . '</td>';
            echo '<td>' . htmlspecialchars($group) . '</td>';
            echo '<td>' . $fmtDate($date) . '</td>';
            echo '<td>' . htmlspecialchars($details) . '</td>';
            echo '</tr>';
        }
        echo '</table>';

        echo '</body></html>';
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
