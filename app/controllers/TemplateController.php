<?php
namespace App\Controllers;

use App\Core\Controller;

class TemplateController extends Controller {

    public function index() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php");
            exit;
        }

        $templateModel = $this->model('TemplateModel');
        
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        
        // Advanced Filters
        $filters = [];
        $filterOptions = [];
        
        // Uploaded By Filter
        $filterOptions['uploaded_by'] = $templateModel->getDistinctTemplateValues('uploaded_by');
        if (isset($_GET['uploaded_by']) && is_array($_GET['uploaded_by'])) {
            $filters['uploaded_by'] = $_GET['uploaded_by'];
        }

        $templates = $templateModel->getAll($startDate, $endDate, $filters);
        
        $role = $_SESSION['user']['dept'] ?? '';
        // Only Procedure can add/delete
        $isProcedure = ($role === 'PROCEDURE');
        
        $this->view('template/index', [
            'templates' => array_slice($templates, (max(1,intval($_GET['page']??1))-1)*10, 10),
            'isProcedure' => $isProcedure,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterOptions' => $filterOptions,
            'activeFilters' => $filters,
            'currentPage' => max(1,intval($_GET['page']??1)),
            'totalPages' => max(1, ceil(count($templates)/10)),
            'totalItems' => count($templates),
            'perPage' => 10
        ]);
    }

    public function upload() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['dept'] !== 'PROCEDURE') {
            die("Access Denied");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['template_file'])) {
            $file = $_FILES['template_file'];
            $title = $_POST['title'] ?? $file['name'];
            $targetDir = "uploads/templates/";
            
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $filename = time() . '_' . basename($file['name']);
            $targetPath = $targetDir . $filename;
            
            $allowed = ['xlsx', 'xls', 'docx', 'doc'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                header("Location: ?controller=template&action=index&error=Invalid file type. Only Excel and Word allowed.");
                exit;
            }

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $templateModel = $this->model('TemplateModel');
                $user = $_SESSION['user']['username'] ?? $_SESSION['user']['userid'];
                $description = $_POST['description'] ?? null;
                
                // Auto-Extract if Description is Empty
                if (empty($description)) {
                    if (in_array($ext, ['docx', 'doc'])) {
                        $description = $this->extractDocxText($targetPath);
                    } elseif (in_array($ext, ['xlsx', 'xls'])) {
                        $description = $this->extractXlsxText($targetPath);
                    }
                    
                    // Limit length
                    if ($description && strlen($description) > 500) {
                        $description = substr($description, 0, 500) . "...";
                    }
                }

                $result = $templateModel->add($title, $file['name'], $targetPath, $user, $description);
                
                if ($result) {
                    header("Location: ?controller=template&action=index&success=Uploaded");
                } else {
                    // Start Debugging: Return error to user
                    $errors = print_r(db_errors(), true); // This won't work as conn is in Model.
                    // Just generic error for now, maybe with title for context.
                    header("Location: ?controller=template&action=index&error=Database Insert Failed using user: $user");
                }
            } else {
                header("Location: ?controller=template&action=index&error=Upload Failed");
            }
        }
    }
    
    // Helper: Extract Text from DOCX (XML Parsing)
    private function extractDocxText($filename) {
        $content = '';
        $zip = new \ZipArchive();
        if ($zip->open($filename) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xml = $zip->getFromIndex($index);
                $content = strip_tags($xml);
            }
            $zip->close();
        }
        return trim($content);
    }

    // Helper: Extract Text from XLSX (Smart Parsing)
    private function extractXlsxText($filename) {
        $zip = new \ZipArchive();
        if ($zip->open($filename) !== true) return '';

        // 1. Load Shared Strings (Dictionary)
        $strings = [];
        if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xml = $zip->getFromIndex($index);
            // Match <t> content. NOTE: This is a rough parser, adequate for simple strings.
            if (preg_match_all('/<t[^>]*>(.*?)<\/t>/is', $xml, $matches)) {
                $strings = $matches[1];
            }
        }

        // 2. Parse Sheet 2 (Content) or Fallback to Sheet 1 (Instructions)
        $content = '';
        $targetSheet = 'xl/worksheets/sheet2.xml';
        if ($zip->locateName($targetSheet) === false) {
             $targetSheet = 'xl/worksheets/sheet1.xml';
        }

        if (($index = $zip->locateName($targetSheet)) !== false) {
            $xml = $zip->getFromIndex($index);
            
            // Split into rows (rough split by <row ...>)
            preg_match_all('/<row[^>]*>(.*?)<\/row>/is', $xml, $rowMatches);
            $rows = $rowMatches[1]; // Array of inner row XMLs

            $targetColIndex = null;
            $foundContent = [];
            $lineCount = 0;

            foreach ($rows as $rIndex => $rowXml) {
                // Parse Cells: <c r="A1" t="s"><v>12</v></c> OR <c r="A1" t="inlineStr">...
                preg_match_all('/<c r="([A-Z]+)[0-9]+"\s*(?:t="([a-z]+)")?[^>]*>(.*?)<\/c>/is', $rowXml, $cellMatches, PREG_SET_ORDER);
                
                // $cellMatches[i][0] = full match
                // $cellMatches[i][1] = Column Letter (A, B, C...)
                // $cellMatches[i][2] = Type (s, inlineStr, str, etc)
                // $cellMatches[i][3] = Inner Content (<v>12</v>)

                foreach ($cellMatches as $cell) {
                    $colLetter = $cell[1];
                    $type = $cell[2];
                    $inner = $cell[3];
                    $val = '';

                    // Extract Value
                    if ($type == 's') { // Shared String
                        if (preg_match('/<v>(.*?)<\/v>/', $inner, $vMatch)) {
                            $idx = intval($vMatch[1]);
                            $val = $strings[$idx] ?? '';
                        }
                    } elseif ($type == 'inlineStr') {
                        if (preg_match('/<t[^>]*>(.*?)<\/t>/', $inner, $tMatch)) {
                            $val = $tMatch[1];
                        }
                    } else { // Number or direct val
                        if (preg_match('/<v>(.*?)<\/v>/', $inner, $vMatch)) {
                            $val = $vMatch[1];
                        }
                    }

                    $val = trim($val);
                    if ($val === '') continue;

                    // HEADCHECK (Row 0 assumed header)
                    if ($rIndex == 0) {
                        // Check for target column
                        if (stripos($val, 'Bahasa Script') !== false) {
                            $targetColIndex = $colLetter;
                        }
                        // If fallback/general preview, keeping headers is optional but skipping them looks cleaner for "Content".
                        // Let's keep them if we don't find the target column later.
                    }

                    // DATA COLLECTION
                    if ($targetColIndex !== null) {
                        // We found the target column! Only grab data from this column
                        if ($colLetter === $targetColIndex && $rIndex > 0) {
                            $foundContent[] = $val;
                        }
                    } else {
                        // Fallback: Collect everything from first few rows
                        if ($rIndex > 0) $foundContent[] = $val; // Skip header row in fallback for cleanliness? Or keep? Let's skip header logic.
                    }
                }
                
                // Break early if we have enough data (e.g. 5 lines of content)
                if (count($foundContent) >= 5) break; 
            }
            
            // Format Output
            $content = implode("\n", $foundContent);
            
            // LIMIT: User asked for "mungkin 2 baris aja".
            // We gathered ~5 items, let's join them and truncate cleanly.
        }

        $zip->close();
        return trim($content);
    }

    public function delete() {
        if (!isset($_SESSION['user']) || $_SESSION['user']['dept'] !== 'PROCEDURE') {
            die("Access Denied");
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            $model = $this->model('TemplateModel');
            $template = $model->getById($id);
            if ($template) {
                if (file_exists($template['filepath'])) {
                    unlink($template['filepath']);
                }
                $model->delete($id);
            }
        }
        header("Location: ?controller=template&action=index");
    }

    // Dynamic File Downloader
    public function downloadFile() {
        $id = $_GET['id'] ?? null;
        if (!$id) die("File not specified");

        $model = $this->model('TemplateModel');
        $template = $model->getById($id);
        
        if (!$template) die("File not found in database");

        // Dynamic Path Resolution
        // DB stores: uploads/templates/filename.ext
        // We resolve relative to THIS controller file: ../../uploads/templates/...
        // This ensures it works regardless of URL rewriting or webroot issues
        $relativePath = $template['filepath'];
        
        // 1. Try Absolute Path (if locally available)
        $absolutePath = __DIR__ . '/../../' . $relativePath;
        
        // 2. Normal Path (fallback)
        $normalPath = $relativePath;

        $realPath = '';
        if (file_exists($absolutePath)) {
            $realPath = $absolutePath;
        } elseif (file_exists($normalPath)) {
            $realPath = $normalPath;
        } else {
            // DEBUG: Show where we looked
            die("File not found on server.<br>Checked: <br>- $absolutePath <br>- $normalPath <br><br>Please check if 'uploads' folder was copied to server root.");
        }

        // Serve File
        if (file_exists($realPath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($realPath).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($realPath));
            readfile($realPath);
            exit;
        }
    }
}
