<?php
// repair_templates.php
// 1. Ensures 'description' column exists.
// 2. Backfills description for existing templates by reading files.

$config = require 'app/config/database.php';
$conn = sqlsrv_connect($config['host'], $config['options']);

if (!$conn) {
    die("DB Connection Failed: " . print_r(sqlsrv_errors(), true));
}

echo "=== STARTING REPAIR ===\n";

// --- STEP 1: Ensure Column Exists ---
$colCheckSql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'script_templates' AND COLUMN_NAME = 'description'";
$stmt = sqlsrv_query($conn, $colCheckSql);
if ($stmt && sqlsrv_has_rows($stmt)) {
    echo "[OK] Column 'description' exists.\n";
} else {
    echo "[FIX] Column 'description' MISSING. Adding it...\n";
    $addSql = "ALTER TABLE script_templates ADD description NVARCHAR(MAX)";
    $res = sqlsrv_query($conn, $addSql);
    if ($res) echo "      -> Success.\n";
    else {
        echo "      -> FAILED to add column. Check permissions.\n";
        print_r(sqlsrv_errors());
        die(); // Cannot proceed if no column
    }
}

// --- STEP 2: Backfill Logic ---
echo "\n=== BACKFILLING DATA ===\n";
$sql = "SELECT id, filename, filepath, description FROM script_templates";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['id'];
    $path = $row['filepath'];
    $desc = $row['description'];

    // Only process if description is empty
    if (empty($desc) || trim($desc) == '') {
        echo "Processing ID $id ({$row['filename']})... ";
        
        if (!file_exists($path)) {
            echo "File Not Found ($path)\n";
            continue;
        }

        $text = "";
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls'])) {
            $text = extractXlsxText($path);
        } elseif (in_array($ext, ['docx', 'doc'])) {
            $text = extractDocxText($path);
        }

        if ($text) {
            // Limit to 500 chars
            if (strlen($text) > 500) $text = substr($text, 0, 500) . "...";
            
            // Update DB
            $updSql = "UPDATE script_templates SET description = ? WHERE id = ?";
            $updStmt = sqlsrv_query($conn, $updSql, [$text, $id]);
            
            if ($updStmt) echo "UPDATED.\n";
            else echo "Update Failed.\n";
        } else {
            echo "No text extracted.\n";
        }
    } else {
        // echo "Skipping ID $id (Already has desc)\n";
    }
}

echo "=== DONE ===\n";


// --- HELPER FUNCTIONS ---

function extractDocxText($filename) {
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

function extractXlsxText($filename) {
    $zip = new \ZipArchive();
    if ($zip->open($filename) !== true) return '';

    // 1. Shared Strings
    $strings = [];
    if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
        $xml = $zip->getFromIndex($index);
        if (preg_match_all('/<t[^>]*>(.*?)<\/t>/is', $xml, $matches)) {
            $strings = $matches[1];
        }
    }

    // 2. Sheet 2 or Sheet 1
    $targetSheet = 'xl/worksheets/sheet2.xml';
    if ($zip->locateName($targetSheet) === false) {
         $targetSheet = 'xl/worksheets/sheet1.xml';
    }

    $content = '';
    if (($index = $zip->locateName($targetSheet)) !== false) {
        $xml = $zip->getFromIndex($index);
        
        // Rough parse row > c > v
        preg_match_all('/<row[^>]*>(.*?)<\/row>/is', $xml, $rowMatches);
        $rows = $rowMatches[1]; 

        $targetColIndex = null;
        $foundContent = [];

        foreach ($rows as $rIndex => $rowXml) {
            preg_match_all('/<c r="([A-Z]+)[0-9]+"\s*(?:t="([a-z]+)")?[^>]*>(.*?)<\/c>/is', $rowXml, $cellMatches, PREG_SET_ORDER);
            
            foreach ($cellMatches as $cell) {
                $colLetter = $cell[1];
                $type = $cell[2] ?? '';
                $inner = $cell[3] ?? '';
                $val = '';

                if ($type == 's') { 
                    if (preg_match('/<v>(.*?)<\/v>/', $inner, $vMatch)) {
                        $idx = intval($vMatch[1]);
                        $val = $strings[$idx] ?? '';
                    }
                } elseif ($type == 'inlineStr') {
                    if (preg_match('/<t[^>]*>(.*?)<\/t>/', $inner, $tMatch)) {
                        $val = $tMatch[1];
                    }
                } else {
                    if (preg_match('/<v>(.*?)<\/v>/', $inner, $vMatch)) {
                        $val = $vMatch[1];
                    }
                }

                $val = trim($val);
                if ($val === '') continue;

                if ($rIndex == 0) {
                    if (stripos($val, 'Bahasa Script') !== false) {
                        $targetColIndex = $colLetter;
                    }
                }

                if ($targetColIndex !== null) {
                    if ($colLetter === $targetColIndex && $rIndex > 0) $foundContent[] = $val;
                } else {
                    if ($rIndex > 0) $foundContent[] = $val;
                }
            }
            if (count($foundContent) >= 5) break; 
        }
        $content = implode("\n", $foundContent);
    }
    $zip->close();
    return trim($content);
}
?>
