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
        
        // Pass filters to model (null for sort args as we want default export sort or reusing logic)
        // Note: getAuditExportData params are: ($startDate, $endDate, $sortCol, $sortOrder, $filters)
        $data = $reqModel->getAuditExportData($startDate, $endDate, 'created_at', 'DESC', $filters);
        
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
