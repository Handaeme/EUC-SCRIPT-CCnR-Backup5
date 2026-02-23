const fs = require('fs');
const path = 'd:/xampp/htdocs/EUC-Script-CCnR-Migrasi/app/views/audit/detail.php';

try {
    const content = fs.readFileSync(path, 'utf8');
    
    // Find start of function
    const startIdx = content.indexOf('function downloadAuditExcel() {');
    if (startIdx === -1) throw new Error('Function not found');
    
    // Find logic end (before footer include)
    const endIdx = content.indexOf('<?php require_once');
    
    let jsCode = content.substring(startIdx, endIdx);
    
    // Replace PHP tag with valid JS
    // Line 1060: const isFileUpload = <?php ... ?>;
    // We replace the entire <?php ... ?> block with 'true'
    jsCode = jsCode.replace(/<\?php[\s\S]*?\?>/g, 'true');
    
    // Write to temp file
    fs.writeFileSync('temp_check.js', jsCode);
    console.log('Extracted JS to temp_check.js');
    
} catch (err) {
    console.error('Error:', err.message);
}
