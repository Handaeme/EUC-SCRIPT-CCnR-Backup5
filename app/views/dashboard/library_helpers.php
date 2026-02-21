<?php
if (!function_exists('extractCleanSnippet')) {
    function extractCleanSnippet($html, $mode) {
        if (empty(strip_tags($html))) return "";
        
        if ($mode !== 'FILE_UPLOAD') {
            return strip_tags($html);
        }

        // FILE UPLOAD MODE: Smarter Extraction
        $dom = new DOMDocument();
        // Suppress warnings for malformed HTML
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);

        // 1. Find all panes (sheets)
        $panes = $xpath->query("//div[contains(@class, 'sheet-pane') or contains(@class, 'media-pane')]");
        $combinedText = [];

        foreach ($panes as $pane) {
            $mediaName = $pane->getAttribute('data-media') ?: $pane->getAttribute('id');
            
            // 1a. Filter by Attribute Name
            if (stripos($mediaName, 'Petunjuk') !== false || stripos($mediaName, 'Instruction') !== false) {
                continue;
            }

            // 1b. Filter by Content (Check first few cells for "Petunjuk")
            $firstCells = $xpath->query(".//table//tr[1]//td | .//table//tr[1]//th", $pane);
            $isInstructionSheet = false;
            foreach ($firstCells as $cell) {
                if (stripos(trim($cell->nodeValue), 'Petunjuk') !== false) {
                    $isInstructionSheet = true;
                    break;
                }
            }
            if ($isInstructionSheet) continue;

            // 2. Find the table in this sheet
            $table = $xpath->query(".//table", $pane)->item(0);
            if (!$table) {
                // Fallback if no table, just take raw text but diringkas
                if (strlen(trim($pane->nodeValue)) > 20) $combinedText[] = strip_tags($pane->nodeValue);
                continue;
            }

            // 3. Find "Script Content" column index
            $colIdx = -1;
            $headers = $xpath->query(".//tr[1]/th | .//tr[1]/td[contains(@style, 'bold') or contains(@style, 'font-weight')]", $table);
            
            foreach ($headers as $idx => $header) {
                $txt = trim($header->nodeValue);
                if (stripos($txt, 'Script Content') !== false || stripos($txt, 'Bahasa Script') !== false || stripos($txt, 'Content') !== false) {
                    $colIdx = $idx + 1; // XPath is 1-indexed
                    break;
                }
            }

            // 4. Extract first 3-5 rows from that column
            if ($colIdx !== -1) {
                $rows = $xpath->query(".//tr[position() > 1 and position() < 6]/td[$colIdx]", $table);
                foreach ($rows as $row) {
                    $val = trim($row->nodeValue);
                    if (!empty($val)) $combinedText[] = $val;
                }
            } else {
                // Fallback: Take first few rows but only if sheet doesn't look like instructions
                $cells = $xpath->query(".//tr[position() > 1 and position() < 5]/td", $table);
                foreach ($cells as $cell) {
                    $val = trim($cell->nodeValue);
                    if (!empty($val)) $combinedText[] = $val;
                }
            }
        }

        return implode(" ", $combinedText);
    }
}
?>
