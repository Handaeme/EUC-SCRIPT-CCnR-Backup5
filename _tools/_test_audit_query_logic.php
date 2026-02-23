<?php

// Function from RequestModel::getRequestDetail
function getBasePattern($scriptNumber) {
    if (empty($scriptNumber)) return ['base' => '', 'pattern' => ''];

    $parts = explode('-', $scriptNumber);
    $basePattern = $scriptNumber; // fallback
    
    // CURRENT LOGIC (Suspect)
    if (count($parts) > 1) {
        array_pop($parts);
        $basePattern = implode('-', $parts) . '-%';
    }
    
    return $basePattern;
}

// PROPOSED LOGIC
function getBasePatternFixed($scriptNumber) {
    if (empty($scriptNumber)) return ['base' => '', 'pattern' => ''];

    // Check if it ends with version pattern (e.g. -01, -02, -99)
    // Regex: hyphen followed by 2 digits at end of string
    if (preg_match('/-(\d{2})$/', $scriptNumber, $matches)) {
        // It has a version suffix! Remove it.
        $base = preg_replace('/-(\d{2})$/', '', $scriptNumber);
        return [
            'base' => $base, 
            'pattern' => $base . '-%',
            'is_versioned' => true
        ];
    } else {
        // No version suffix (It IS the parent)
        return [
            'base' => $scriptNumber,
            'pattern' => $scriptNumber . '-%',
            'is_versioned' => false
        ];
    }
}


$samples = [
    'KONV-RC-12/02/26-0037',       // Original (Parent)
    'KONV-RC-12/02/26-0037-01',    // Revision 1
    'KONV-RC-12/02/26-0037-02',    // Revision 2
    'OLD-FORMAT-NO-DATE',          // Edge case
    'SCRIPT-WITH-DASH-BUT-NO-VER', // Edge case
    'ABC-01',                      // Is this version 1 or just code ending in 01?
];

echo "--- TESTING CURRENT LOGIC ---\n";
foreach ($samples as $s) {
    echo "Input: $s\n";
    echo "Query LIKE: " . getBasePattern($s) . "\n";
    echo "----------------\n";
}

echo "\n\n--- TESTING PROPOSED LOGIC ---\n";
foreach ($samples as $s) {
    echo "Input: $s\n";
    $res = getBasePatternFixed($s);
    echo "Base: " . $res['base'] . "\n";
    echo "Pattern: " . $res['pattern'] . "\n";
    echo "Is Versioned: " . ($res['is_versioned'] ? 'YES' : 'NO') . "\n";
    echo "SQL Logic: WHERE script_number = '{$res['base']}' OR script_number LIKE '{$res['pattern']}'\n";
    echo "----------------\n";
}
