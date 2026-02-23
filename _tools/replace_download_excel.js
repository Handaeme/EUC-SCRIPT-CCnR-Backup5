const fs = require('fs');

const file = 'd:/xampp/htdocs/EUC-Script-CCnR-Migrasi/app/views/request/review.php';
let c = fs.readFileSync(file, 'utf8');

const start = c.indexOf('function downloadReviewExcel() {');
const endIdx = c.indexOf("let currentColorMode = 'RED';");
const exactEnd = c.lastIndexOf('}', endIdx) + 1;

const newCode = `function downloadReviewExcel() {
    const scriptNum = '<?php echo htmlspecialchars($request["script_number"]); ?>';

    let sheets = Array.from(document.querySelectorAll('.media-pane, .sheet-pane, .review-tab-content'));
    sheets = [...new Set(sheets)];
    if (sheets.length === 0) {
        sheets = Array.from(document.querySelectorAll('.media-tab-pane, [id^="sheet-"], [id^="tab-media-"]'));
    }
    
    if (sheets.length === 0) {
        Swal.fire('Error', 'No content available to download', 'warning');
        return;
    }

    console.log(\`Found \${sheets.length} sheet(s) in DOM\`);

    let excelHtml = 
    '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">\\n' +
    '<head>\\n' +
    '    <meta charset="utf-8">\\n' +
    '    <style>\\n' +
    '        table { border-collapse: collapse; margin-bottom: 30px; font-family: Arial, sans-serif; font-size: 11pt; }\\n' +
    '        th { background-color: #E0E0E0; font-weight: bold; border: 1px solid #000000; padding: 5px; text-align: center; vertical-align: middle; }\\n' +
    '        td { vertical-align: top; border: 1px solid #000000; padding: 5px; white-space: pre-wrap; }\\n' +
    '    </style>\\n' +
    '</head>\\n' +
    '<body>\\n';

    let hasContent = false;
    
    sheets.forEach((sheet, index) => {
        let sheetName = sheet.getAttribute('data-media');
        
        if (!sheetName && sheet.id) {
            const sheetId = sheet.id;
            let btnId = sheetId.replace('tab-media-', 'tab-media-btn-').replace('tab-', 'tab-btn-review-');
            let btn = document.getElementById(btnId);
            
            if (!btn) {
                const buttons = document.querySelectorAll('.btn-sheet, .btn-media-tab, .btn-media-tab-unified');
                for (let b of buttons) {
                    const clickAttr = b.getAttribute('onclick');
                    if (clickAttr && (clickAttr.includes(\`'\${sheetId}'\`) || clickAttr.includes(\`"\${sheetId}"\`))) {
                        btn = b;
                        break;
                    }
                }
            }

            if (!btn && (sheetId.startsWith('tab-media-') || sheetId.startsWith('tab-'))) {
                 const idx = sheetId.split('-').pop();
                 btn = document.getElementById(\`tab-media-btn-\${idx}\`) || document.getElementById(\`tab-btn-review-\${idx}\`);
            }
            
            if (btn) sheetName = btn.innerText.trim();
            if (!sheetName) sheetName = sheetId.replace('tab-media-', 'Media ').replace('tab-', 'Content ');
        }

        if (!sheetName) sheetName = \`Sheet \${index + 1}\`;
        sheetName = sheetName.replace(/[:\\\\/?*[\\]]/g, '');
        if (sheetName.length > 31) sheetName = sheetName.substring(0, 31);
        
        console.log(\`Processing sheet: "\${sheetName}"\`);
        
        const cleanSheet = sheet.cloneNode(true);
        cleanSheet.querySelectorAll('.deletion-span').forEach(el => el.remove());
        
        let table = cleanSheet.querySelector('table');
        
        if (table) {
            cleanSheet.querySelectorAll('.revision-span, span').forEach(span => {
                if (span.classList.contains('revision-span') || span.style.color === 'red' || span.style.color === 'rgb(255, 0, 0)' || span.style.color === '#ef4444') {
                    const fontNode = document.createElement('font');
                    fontNode.setAttribute('color', '#FF0000');
                    const bNode = document.createElement('b');
                    bNode.innerHTML = span.innerHTML;
                    fontNode.appendChild(bNode);
                    span.parentNode.replaceChild(fontNode, span);
                }
            });
            
            table.setAttribute('border', '1');
            
            const firstRow = table.querySelector('tr');
            if (firstRow) {
                Array.from(firstRow.cells).forEach(cell => {
                    cell.outerHTML = \`<th style="background-color: #E0E0E0; font-weight: bold; border: 1px solid #000000; text-align: center;">\${cell.innerHTML}</th>\`;
                });
            }
            
            excelHtml += \`<h3>\${sheetName}</h3>\\n\` + 
                         table.outerHTML.replace('<table', '<table border="1" style="border-collapse:collapse;"') + 
                         \`\\n<br><br>\\n\`;
            hasContent = true;
        } else {
            let htmlContent = '';
            
            function processNode(node) {
                if (node.nodeType === 3) {
                    const text = node.textContent;
                    htmlContent += text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\\n/g, '<br>');
                } else if (node.nodeType === 1) {
                    if (node.tagName.toLowerCase() === 'br') {
                        htmlContent += '<br>';
                    } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                        if (htmlContent !== '' && !htmlContent.endsWith('<br>')) htmlContent += '<br>';
                        node.childNodes.forEach(processNode);
                        htmlContent += '<br>';
                    } else {
                        let isRed = false;
                        if (node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444') {
                            isRed = true;
                        }
                        
                        if (isRed) htmlContent += '<font color="#FF0000"><b>';
                        node.childNodes.forEach(processNode);
                        if (isRed) htmlContent += '</b></font>';
                    }
                }
            }
            
            cleanSheet.childNodes.forEach(processNode);
            
            excelHtml += \`
            <h3>\${sheetName}</h3>
            <table border="1" style="border-collapse:collapse;">
                <colgroup>
                     <col style="width: 150px">
                     <col style="width: 800px">
                </colgroup>
                <tr>
                    <th style="background-color: #E0E0E0; font-weight: bold; border: 1px solid #000000; text-align: center;">Content Type</th>
                    <th style="background-color: #E0E0E0; font-weight: bold; border: 1px solid #000000; text-align: center;">Script Content</th>
                </tr>
                <tr>
                    <td style="border: 1px solid #000000; vertical-align: top; padding: 5px;">\${sheetName}</td>
                    <td style="border: 1px solid #000000; vertical-align: top; padding: 5px;">\${htmlContent}</td>
                </tr>
            </table><br><br>\`;
            hasContent = true;
        }
    });

    if (!hasContent) {
        Swal.fire('Error', 'No content to download', 'warning');
        return;
    }

    excelHtml += '</body></html>';

    const blob = new Blob([excelHtml], { type: 'application/vnd.ms-excel' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = \`Script_\${scriptNum}.xls\`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    console.log('✓ Download complete! (HTML -> XLS format)');
}
`;

fs.writeFileSync(file, c.substring(0, start) + newCode + '\n\n' + c.substring(endIdx));
console.log('Successfully replaced function');
