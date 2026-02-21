<?php
namespace App\Helpers;

use ZipArchive;
use DOMDocument;

class FileHandler {
    
    public static function parseFile($filepath, $extension) {
        $extension = strtolower($extension);
        
        if (in_array($extension, ['xls', 'xlsx'])) {
            return self::parseExcelNative($filepath);
        } elseif (in_array($extension, ['doc', 'docx'])) {
            return self::parseWordNative($filepath);
        } else {
            return "Preview not supported for this file type.";
        }
    }

    // NATIVE XLSX PARSER (Multi-Sheet Support)
    // Returns array with 'preview_html' and 'sheets' array
    private static function parseExcelNative($filepath) {
        $zip = new ZipArchive;
        if ($zip->open($filepath) === TRUE) {
            
            // 1. Get Shared Strings
            $sharedStrings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                $xml = $zip->getFromName('xl/sharedStrings.xml');
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                $si = $dom->getElementsByTagName('si');
                foreach ($si as $val) {
                    $t = $val->getElementsByTagName('t');
                    $sharedStrings[] = $t->length > 0 ? $t->item(0)->nodeValue : '';
                }
            }

            // 2. Map rId to File Path (Relationships)
            $rels = [];
            if ($zip->locateName('xl/_rels/workbook.xml.rels') !== false) {
                $xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                foreach ($dom->getElementsByTagName('Relationship') as $rel) {
                    $rels[$rel->getAttribute('Id')] = $rel->getAttribute('Target'); // e.g., worksheets/sheet1.xml
                }
            }

            // 3. Get Sheet List
            $sheets = [];
            if ($zip->locateName('xl/workbook.xml') !== false) {
                $xml = $zip->getFromName('xl/workbook.xml');
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                foreach ($dom->getElementsByTagName('sheet') as $sheet) {
                    $sheets[] = [
                        'name' => $sheet->getAttribute('name'),
                        'rId' => $sheet->getAttribute('r:id')
                    ];
                }
            }
            
            // 4. Parse each sheet and store separately
            $parsedSheets = [];
            
            foreach ($sheets as $index => $sheet) {
                // Skip the first sheet (Petunjuk_Pengisian) as per requirement
                if ($index === 0) continue;

                $rid = $sheet['rId'];
                $target = isset($rels[$rid]) ? 'xl/' . $rels[$rid] : 'xl/worksheets/sheet' . ($index + 1) . '.xml';
                
                if (strpos($target, 'xl/') !== 0) $target = 'xl/' . $target;

                $sheetContent = self::parseSheetXML($zip, $target, $sharedStrings);
                
                $parsedSheets[] = [
                    'name' => $sheet['name'],
                    'content' => $sheetContent
                ];
            }
            
            // 5. Generate Preview HTML (for display)
            $uniqueId = time() . rand(1000, 9999); 
            $html = '<div class="sheet-container" id="container-' . $uniqueId . '">';
            
            // TABS HEADER (Protected from editing)
            $html .= '<div class="sheet-tabs-nav" contenteditable="false">';
            
            $visibleCount = 0;
            foreach ($parsedSheets as $index => $pSheet) {
                // Smart Skip: specific name check
                // Only skip if we have other sheets to show
                $sheetNameNormal = strtolower(str_replace(['_', ' '], '', $pSheet['name']));
                if (strpos($sheetNameNormal, 'petunjukpengisian') !== false && count($parsedSheets) > 1) continue;

                $visibleCount++;
                $activeClass = ($visibleCount === 1) ? 'active' : '';
                $safeName = htmlspecialchars($pSheet['name']);
                
                // Unique ID construction
                $sheetId = 'sheet-' . $uniqueId . '-' . ($index + 1); 
                $btnId = 'btn-' . $sheetId;
                
                $html .= "<button type='button' id='$btnId' class='btn-sheet $activeClass' onclick=\"changeSheet('$sheetId')\">$safeName</button>";
            }
            $html .= '</div>'; // End Tabs

            // SHEETS CONTENT (Editable)
            $visibleCount = 0;
            foreach ($parsedSheets as $index => $pSheet) {
                 // Smart Skip: specific name check
                 $sheetNameNormal = strtolower(str_replace(['_', ' '], '', $pSheet['name']));
                 if (strpos($sheetNameNormal, 'petunjukpengisian') !== false && count($parsedSheets) > 1) continue;

                $visibleCount++;
                $displayStyle = ($visibleCount === 1) ? 'block' : 'none';
                
                $sheetId = 'sheet-' . $uniqueId . '-' . ($index + 1);
                
                $html .= "<div id='$sheetId' class='sheet-pane' contenteditable='true' style='display:$displayStyle; font-family:\"Inter\", system-ui, -apple-system, sans-serif;'>";
                $html .= $pSheet['content'];
                $html .= "</div>";
            }
            
            $html .= '</div>';
            $zip->close();
            
            // Return both preview HTML and structured sheets data
            return [
                'preview_html' => $html,
                'sheets' => $parsedSheets
            ];
        } else {
            return [
                'preview_html' => "Failed to open Excel file.",
                'sheets' => []
            ];
        }
    }

    private static function parseSheetXML($zip, $filename, $sharedStrings) {
        if ($zip->locateName($filename) === false) return "Sheet data not found ($filename)";

        $xml = $zip->getFromName($filename);
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $rows = $dom->getElementsByTagName('row');
        
        $html = '<table class="table table-bordered excel-preview">';
        foreach ($rows as $row) {
            $html .= '<tr>';
            $cells = $row->getElementsByTagName('c');
            foreach ($cells as $cell) {
                $type = $cell->getAttribute('t');
                $valNode = $cell->getElementsByTagName('v');
                $val = $valNode->length > 0 ? $valNode->item(0)->nodeValue : '';
                
                // Look up string
                if ($type == 's' && is_numeric($val) && isset($sharedStrings[$val])) {
                    $val = $sharedStrings[$val];
                }
                
                $html .= '<td contenteditable="true">' . htmlspecialchars($val) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }

    // NATIVE DOCX PARSER (Unchanged)
    private static function parseWordNative($filepath) {
        $zip = new ZipArchive;
        if ($zip->open($filepath) === TRUE) {
            $content = '<div class="word-preview" contenteditable="true" style="padding:20px;line-height:1.5; font-family:\"Inter\", system-ui, -apple-system, sans-serif;">';
            if ($zip->locateName('word/document.xml') !== false) {
                $xml = $zip->getFromName('word/document.xml');
                $dom = new DOMDocument();
                $dom->loadXML($xml);
                $paragraphs = $dom->getElementsByTagName('p');
                foreach ($paragraphs as $p) {
                    $texts = $p->getElementsByTagName('t');
                    $pText = "";
                    foreach ($texts as $t) $pText .= $t->nodeValue;
                    if (trim($pText) !== "") $content .= "<p>" . htmlspecialchars($pText) . "</p>";
                }
            }
            $content .= '</div>';
            $zip->close();
            return $content;
        }
        return "Failed to open Word file.";
    }
}
