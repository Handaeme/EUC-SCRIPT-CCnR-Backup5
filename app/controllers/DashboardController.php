<?php
namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $role = $_SESSION['user']['dept'] ?? '';
        
        // Route based on Role Code (MAKER, SPV, PIC, PROCEDURE)
        switch ($role) {
            case 'ADMIN':
                header("Location: ?controller=user&action=index");
                exit;
                break;
            case 'SPV':
                $this->spv();
                break;
            case 'PIC':
                // Placeholder for PIC Dashboard
                $this->pic();
                break;
            case 'PROCEDURE':
                // Placeholder for Procedure Dashboard
                $this->procedure();
                break;
            case 'MAKER':
                // MAKER default landing = Create New Request
                header("Location: ?controller=request&action=create");
                exit;
                break;
            case 'VIEWER':
            default:
                // Unrecognized users or explicit VIEWERS go to Library to look around
                header("Location: ?controller=dashboard&action=library");
                exit;
                break;
        }
    }

    public function maker() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        $filterOptions = [];
        
        foreach ($filterCols as $col) {
            // Fetch availble options
            $filterOptions[$col] = $reqModel->getDistinctRequestValues($col);
            
            // Capture selected filters AND VALIDATE
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }
        
        // Fetch all MAKER pending requests (includes PENDING_MAKER_CONFIRMATION now)
        $allPending = $reqModel->getPendingRequests($user['userid'], 'MAKER', $startDate, $endDate, $filters);
        
        // Separate: Revisions vs Confirmations
        $revisions = [];
        $confirmations = [];
        foreach ($allPending as $item) {
            if ($item['status'] === 'PENDING_MAKER_CONFIRMATION') {
                $confirmations[] = $item;
            } else {
                $revisions[] = $item;
            }
        }
        
        // ENRICHMENT: Fetch Content for Display (Robust Fallback)
        $this->enrichRequestContent($revisions, $reqModel);
        $this->enrichRequestContent($confirmations, $reqModel);
        
        // Fetch Stats
        $stats = $reqModel->getMakerStats($user['userid']);
        
        $this->view('dashboard/maker', [
            'revisions' => $revisions,
            'confirmations' => $confirmations,
            'confirmCount' => count($confirmations),
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters
        ]);
    }

    public function spv() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $viewMode = $_GET['view_mode'] ?? 'pending';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

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
        
        // Get data based on view mode
        $pendingRequests = [];
        $historyRequests = [];
        
        if ($viewMode === 'history') {
            $historyRequests = $reqModel->getUserApprovalHistory($user['userid'], $startDate, $endDate, $filters);
            $this->enrichRequestContent($historyRequests, $reqModel);
        } else {
            $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'SPV', $startDate, $endDate, $filters);
            $this->enrichRequestContent($pendingRequests, $reqModel);
        }
        
        // Stats
        $stats = $reqModel->getApprovalStats($user['userid'], 'SPV');
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'historyRequests' => $historyRequests,
            'viewMode' => $viewMode,
            'stats' => $stats,
            'role' => 'SPV',
            'pageTitle' => 'Need to Approval',
            'pageDesc' => '',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters
        ]);
    }

    public function pic() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $viewMode = $_GET['view_mode'] ?? 'pending';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;

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
        
        // Get data based on view mode
        $pendingRequests = [];
        $historyRequests = [];
        
        if ($viewMode === 'history') {
            $historyRequests = $reqModel->getUserApprovalHistory($user['userid'], $startDate, $endDate, $filters);
            $this->enrichRequestContent($historyRequests, $reqModel);
        } else {
            $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'PIC', $startDate, $endDate, $filters);
            $this->enrichRequestContent($pendingRequests, $reqModel);
        }
        
        // Stats
        $stats = $reqModel->getApprovalStats($user['userid'], 'PIC');
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'historyRequests' => $historyRequests,
            'viewMode' => $viewMode,
            'stats' => $stats,
            'role' => 'PIC',
            'pageTitle' => 'Need to Approval',
            'pageDesc' => '',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters
        ]);
    }

    public function procedure() {
        $user = $_SESSION['user'];
        $reqModel = $this->model('RequestModel');
        
        $viewMode = $_GET['view_mode'] ?? 'pending';
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
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
        
        // Get data based on view mode
        $pendingRequests = [];
        $historyRequests = [];
        
        if ($viewMode === 'history') {
            $historyRequests = $reqModel->getUserApprovalHistory($user['userid'], $startDate, $endDate, $filters);
            $this->enrichRequestContent($historyRequests, $reqModel);
        } else {
            $pendingRequests = $reqModel->getPendingRequests($user['userid'], 'PROCEDURE', $startDate, $endDate, $filters);
            $this->enrichRequestContent($pendingRequests, $reqModel);
        }
        
        // Stats
        $stats = $reqModel->getApprovalStats($user['userid'], 'PROCEDURE');
        
        $this->view('dashboard/approval', [
            'pendingRequests' => $pendingRequests,
            'historyRequests' => $historyRequests,
            'viewMode' => $viewMode,
            'stats' => $stats,
            'role' => 'PROCEDURE',
            'pageTitle' => 'Need to Approval',
            'pageDesc' => '',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters
        ]);
    }

    public function library() {
        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $dateType = $_GET['date_type'] ?? 'created_at'; // Filter by Published or Start Date
        $sortPublished = $_GET['sort_published'] ?? 'DESC'; // Default Newest First
        
        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        $filterOptions = [];
        
        foreach ($filterCols as $col) {
            // Fetch availble options
            $filterOptions[$col] = $reqModel->getDistinctLibraryValues($col);
            
            // Capture selected filters (expecting arrays from checkboxes)
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }

        // Validate Sort
        if (!in_array(strtoupper($sortPublished), ['ASC', 'DESC'])) {
            $sortPublished = 'DESC';
        }

        // New Sort Field Logic
        $sortBy = $_GET['sort_by'] ?? 'created_at'; // Default to Published Date (created_at in library)
        if (!in_array($sortBy, ['created_at', 'request_created_at', 'start_date'])) {
            $sortBy = 'created_at';
        }

        // NEW: Search Parameter
        $search = $_GET['search'] ?? null;
        
        // VISIBILITY LOGIC:
        // Agents (ROLE 'USER' or similar?) -> Show ONLY Active
        // Makers, SPV, PIC, Procedure, Admin -> Show ALL (so they can activate them)
        // NOTE: In this app, ROLE is stored in 'dept' session key (derived on login)
        $userRole = $_SESSION['user']['dept'] ?? 'USER';
        
        // Assume 'USER' is Agent/Viewer. All others have higher privileges.
        $showInactive = ($userRole !== 'USER'); 
        
        // Use the aggregated fetcher (same as Export) to handle Duplicates & Content formatting consistently
        $allLibraryItems = $reqModel->getLibraryItemsWithContent($startDate, $endDate, $sortPublished, $filters, $showInactive, $sortBy, $dateType, $search);
        
        // === PAGINATION LOGIC ===
        $perPage = 10;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $totalItems = count($allLibraryItems);
        $totalPages = max(1, ceil($totalItems / $perPage));
        
        // Clamp page to valid range
        if ($page > $totalPages) $page = $totalPages;
        
        // Slice array for current page
        $offset = ($page - 1) * $perPage;
        $libraryItems = array_slice($allLibraryItems, $offset, $perPage);
        
        // AJAX Handler for Live Search
        if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
            header('Content-Type: application/json');
            
            // Helper for extractCleanSnippet
            include_once __DIR__ . '/../views/dashboard/library_helpers.php';
            
            ob_start();
            include __DIR__ . '/../views/dashboard/library_rows.php';
            $rowsHtml = ob_get_clean();

            ob_start();
            include __DIR__ . '/../views/dashboard/library_grid.php';
            $gridHtml = ob_get_clean();
            
            ob_start();
            include __DIR__ . '/../views/dashboard/library_pagination.php';
            $paginationHtml = ob_get_clean();
            
            echo json_encode([
                'rows' => $rowsHtml,
                'grid' => $gridHtml,
                'pagination' => $paginationHtml,
                'total' => $totalItems
            ]);
            exit;
        }
        
        $sqlServerToday = $reqModel->getSqlServerTodayDate();

        $this->view('dashboard/library', [
            'libraryItems' => $libraryItems,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateType' => $dateType,
            'sortPublished' => $sortPublished,
            'sortBy' => $sortBy,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'canManage' => $showInactive,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'perPage' => $perPage,
            'search' => $search,
            'sqlServerToday' => $sqlServerToday
        ]);
    }
    
    public function exportLibrary() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $reqModel = $this->model('RequestModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $dateType = $_GET['date_type'] ?? 'created_at';
        
        // Advanced Filters
        $filters = [];
        $filterCols = ['jenis', 'produk', 'kategori', 'media'];
        
        foreach ($filterCols as $col) {
            if (isset($_GET[$col]) && is_array($_GET[$col])) {
                $filters[$col] = $_GET[$col];
            }
        }
        
        $libraryItems = $reqModel->getLibraryItemsWithContent($startDate, $endDate, 'DESC', $filters, true, 'created_at', $dateType);
        
        // Filename
        $filename = "Script_Library_Export_" . date('Ymd_His') . ".xls";
        
        // Headers for HTML-Excel
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Start HTML Output
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<!--[if gte mso 9]><xml>';
        echo '<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Script Library</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
        echo '</xml><![endif]-->';
        echo '<style>';
        echo 'body, table { font-family: Arial, sans-serif; font-size: 11pt; }';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000000; padding: 8px; vertical-align: top; white-space: nowrap; }'; // Added nowrap and increased padding
        echo 'th { background-color: #f2f2f2; font-weight: bold; text-align: left; }';
        echo '.text { mso-number-format:"\@"; }'; // Force text format
        echo '</style>';
        echo '</head>';
        echo '<body>';
        
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Ticket ID</th>';
        echo '<th>Script Number</th>';
        echo '<th>Title</th>';
        echo '<th>Type (Jenis)</th>';
        echo '<th>Product</th>';
        echo '<th>Category</th>';
        echo '<th>Media Channel</th>';
        echo '<th style="min-width:500px; width:500px;">Isi Script / Content</th>';
        echo '<th>Status</th>';
        echo '<th>Request Date</th>';
        echo '<th>Published Date</th>';
        echo '<th>Start Date</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($libraryItems as $item) {
            // Published Date (created_at in library table)
            $publishedDateRaw = $item['created_at'] ?? null;
            if ($publishedDateRaw instanceof \DateTime) {
                $publishedDate = $publishedDateRaw->format('d M Y H:i');
            } elseif (is_string($publishedDateRaw) && !empty($publishedDateRaw)) {
                $publishedDate = date('d M Y H:i', strtotime($publishedDateRaw));
            } else {
                $publishedDate = '-';
            }

            // Start Date
            $startDateRaw = $item['start_date'] ?? null;
            if ($startDateRaw instanceof \DateTime) {
                $startDateVal = $startDateRaw->format('d M Y');
            } elseif (is_string($startDateRaw) && !empty($startDateRaw)) {
                $startDateVal = date('d M Y', strtotime($startDateRaw));
            } else {
                $startDateVal = '-';
            }

            // Original Request Date
            $createdDateRaw = $item['request_created_at'] ?? null;
            if ($createdDateRaw instanceof \DateTime) {
                $createdDate = $createdDateRaw->format('d M Y H:i');
            } elseif (is_string($createdDateRaw) && !empty($createdDateRaw)) {
                $createdDate = date('d M Y H:i', strtotime($createdDateRaw));
            } else {
                $createdDate = '-';
            }

            // Use aggregated content (already handled: Filename for File Upload, Text for Free Input)
            $contentRaw = $item['content_aggregated'] ?? $item['content'] ?? '';
            
            // Format for Excel Cell (Multiline)
            $finalContent = str_replace(["\r\n", "\r", "\n"], "<br style='mso-data-placement:same-cell;' />", htmlspecialchars($contentRaw));

            $tId = $item['ticket_id'] ?? '-';
            if (is_numeric($tId)) $tId = sprintf("SC-%04d", $tId);

            echo '<tr>';
            echo '<td class="text">' . htmlspecialchars($tId) . '</td>';
            echo '<td class="text">' . htmlspecialchars($item['script_number'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['title'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['jenis'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['produk'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['kategori'] ?? '-') . '</td>';
            echo '<td>' . htmlspecialchars($item['request_media'] ?? '-') . '</td>';
            echo '<td style="white-space:normal; width:500px;">' . $finalContent . '</td>';
            echo '<td>LIBRARY</td>';
            echo '<td>' . htmlspecialchars($createdDate) . '</td>';
            echo '<td>' . htmlspecialchars($publishedDate) . '</td>';
            echo '<td>' . htmlspecialchars($startDateVal) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    private function enrichRequestContent(&$requests, $reqModel, $separator = ' | ') {
        foreach ($requests as &$req) {
            if (empty($req['content']) || trim($req['content']) === '') {
                // 1. Try Free Input (Previews)
                $previews = $reqModel->getPreviewContent($req['id']);
                if (!empty($previews)) {
                    $combined = [];
                    foreach ($previews as $p) {
                        $combined[] = "{$p['media']}: " . strip_tags($p['content']);
                    }
                    $req['content'] = implode($separator, $combined);
                } 
                // 2. If still empty, Try File Upload
                if (empty($req['content'])) {
                     $files = $reqModel->getFiles($req['id']);
                     if (!empty($files)) {
                         $names = array_map(function($f) { return $f['original_filename']; }, $files);
                         $req['content'] = implode(', ', $names);
                     }
                }
            }
        }
        unset($req);
    }
    
    public function getLibraryData() {
        // Prevent any previous output (e.g. PHP warnings) from breaking JSON
        ob_clean(); 
        header('Content-Type: application/json');
        
        try {
            $templateModel = $this->model('TemplateModel');
            
            // Get all templates from Template Library
            $items = $templateModel->getAll(null, null, []);
            
            // Format for JSON response
            $formatted = array_map(function($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'filename' => $item['filename'] ?? '',
                    'filepath' => $item['filepath'] ?? '',
                    'uploaded_by' => $item['uploaded_by'] ?? '',
                    'description' => $item['description'] ?? '',
                    'created_at' => isset($item['created_at']) ? (($item['created_at'] instanceof \DateTime) ? $item['created_at']->format('Y-m-d') : date('Y-m-d', strtotime($item['created_at']))) : ''
                ];
            }, $items);
            
            echo json_encode(['success' => true, 'items' => $formatted]);
        } catch (\Exception $e) {
            // Catch connection errors or model errors
            http_response_code(500); // Optional, but good for debugging
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Throwable $t) {
            // Catch Fatal Errors (PHP 7+)
            http_response_code(500); 
            echo json_encode(['success' => false, 'message' => 'Internal Server Error: ' . $t->getMessage()]);
        }
        exit;
    }

    public function activateScript() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.0 403 Forbidden');
            echo "Method not allowed";
            exit;
        }

        $user = $_SESSION['user'];
        // Authorization: Only Makers, SPV, Admin, Procedure, PIC can manage
        // NOTE: Role is stored in 'dept' session key
        if (($user['dept'] ?? 'USER') === 'USER') {
             header('HTTP/1.0 401 Unauthorized');
             echo "Unauthorized";
             exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = $input['request_id'] ?? null;
        $isActive = $input['is_active'] ?? false; // true/false

        if (!$requestId) {
            echo json_encode(['success' => false, 'message' => 'Missing Request ID']);
            exit;
        }

        $reqModel = $this->model('RequestModel');
        $success = $reqModel->toggleScriptActivation($requestId, $isActive, $user['userid']);

        echo json_encode([
            'success' => $success, 
            'message' => $success ? 'Status updated' : 'Failed to update status',
            'new_state' => $isActive
        ]);
    }
}
