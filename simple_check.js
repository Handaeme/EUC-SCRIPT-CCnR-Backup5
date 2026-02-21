const fs = require('fs');

try {
    const path = 'app/views/audit/detail.php';
    if (!fs.existsSync(path)) {
        console.error('File not found at:', path);
        process.exit(1);
    }

    const content = fs.readFileSync(path, 'utf8');
    console.log('Read file. Length:', content.length);
    
    const startToken = 'function downloadAuditExcel() {';
    const start = content.indexOf(startToken);
    console.log('Start index:', start);
    
    const endToken = 'XLSX.writeFile(workbook, \'Audit_Export.xlsx\');';
    const endStub = content.indexOf(endToken);
    console.log('End token index:', endStub);
    
    if (start !== -1 && endStub !== -1) {
        // Find the closing brace after endStub
        // It should be shortly after endStub
        // "XLSX.writeFile(...);\n}"
        
        let js = content.substring(start, endStub + endToken.length + 10);
        // Find the actual closing brace
        const lastBrace = js.lastIndexOf('}');
        js = js.substring(0, lastBrace + 1);
        
        console.log('Extracted JS length:', js.length);
        
        // Mock PHP
        js = js.replace(/<\?php[\s\S]*?\?>/g, 'true');
        
        fs.writeFileSync('temp_check.js', js);
        console.log('Wrote temp_check.js');
    } else {
        console.log('Could not find start/end tokens');
    }

} catch (e) {
    console.error('Exception:', e);
}
