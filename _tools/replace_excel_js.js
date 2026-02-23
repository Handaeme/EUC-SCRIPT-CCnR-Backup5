const fs = require('fs');
const file = 'd:/xampp/htdocs/EUC-Script-CCnR-Migrasi/app/views/request/review.php';
let c = fs.readFileSync(file, 'utf8');

const start = c.indexOf('function downloadReviewExcel() {');
const endIdx = c.indexOf("let currentColorMode = 'RED';");
const exactEnd = c.lastIndexOf('}', endIdx) + 1;

const newCode = `async function downloadReviewExcel() {
    if (typeof ExcelJS === 'undefined') {
        Swal.fire({
            title: 'Module Missing', 
            html: 'ExcelJS libraries not found. <br><small>Please save exceljs.min.js in public/assets/js/</small>', 
            icon: 'error'
        });
        return;
    }

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

    const workbook = new ExcelJS.Workbook();
    workbook.creator = 'System';
    workbook.created = new Date();

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
        
        // Prevent duplicate tab names
        let finalSheetName = sheetName;
        let suffix = 1;
        while (workbook.getWorksheet(finalSheetName)) {
             finalSheetName = \`\${sheetName} (\${suffix})\`;
             suffix++;
        }
        
        console.log(\`Processing sheet: "\${finalSheetName}"\`);
        
        // Add native worksheet tab!
        const worksheet = workbook.addWorksheet(finalSheetName);
        hasContent = true;

        const cleanSheet = sheet.cloneNode(true);
        cleanSheet.querySelectorAll('.deletion-span').forEach(el => el.remove());
        
        let table = cleanSheet.querySelector('table');
        
        if (table) {
            let cols = [];
            const headerRow = table.querySelector('tr');
            if (headerRow) {
                Array.from(headerRow.cells).forEach((cell, i) => {
                    let w = 25;
                    if (i === 1) w = 10;
                    if (i === 3) w = 100;
                    cols.push({ header: cell.innerText.trim(), width: w });
                });
            }
            if (cols.length === 0) Object.assign(cols, [ {width: 25}, {width: 10}, {width: 25}, {width: 100}, {width: 25}, {width: 25} ]);
            worksheet.columns = cols;
            
            // Format Header
            worksheet.getRow(1).eachCell((cell) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0E0E0' } };
                cell.font = { bold: true };
                cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            const rows = table.querySelectorAll('tr');
            rows.forEach((tr, rowIndex) => {
                if (rowIndex === 0 && headerRow) return; 
                
                const excelRow = worksheet.addRow([]);
                Array.from(tr.cells).forEach((td, cellIndex) => {
                    const cell = excelRow.getCell(cellIndex + 1);
                    cell.alignment = { vertical: 'top', wrapText: true };
                    cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };

                    const richTextParams = [];
                    function parseNodeEx(node, isRed) {
                        if (node.nodeType === 3) {
                            if (node.textContent) {
                                let txt = node.textContent.replace(/\\n\\s*\\n/g, '\\n'); 
                                richTextParams.push({ text: txt, font: isRed ? { color: { argb: 'FFFF0000' }, bold: true } : {} });
                            }
                        } else if (node.nodeType === 1) {
                            if (node.tagName.toLowerCase() === 'br') {
                                richTextParams.push({ text: '\\n' });
                            } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                                if (richTextParams.length > 0 && !richTextParams[richTextParams.length-1].text.endsWith('\\n')) {
                                    richTextParams.push({ text: '\\n' });
                                }
                                node.childNodes.forEach(n => parseNodeEx(n, isRed));
                                richTextParams.push({ text: '\\n' });
                            } else {
                                let currentlyRed = isRed || node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444';
                                node.childNodes.forEach(n => parseNodeEx(n, currentlyRed));
                            }
                        }
                    }
                    td.childNodes.forEach(n => parseNodeEx(n, false));
                    
                    if (richTextParams.length > 0) {
                        cell.value = { richText: richTextParams };
                    } else {
                        cell.value = td.innerText;
                    }
                });
            });
        } else {
            worksheet.columns = [
                { header: 'Content Type', width: 25 },
                { header: 'Script Content', width: 100 }
            ];
            worksheet.getRow(1).eachCell((cell) => {
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE0E0E0' } };
                cell.font = { bold: true };
                cell.alignment = { vertical: 'middle', horizontal: 'center' };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            const richTextParams = [];
            function parseNodeEx(node, isRed) {
                if (node.nodeType === 3) {
                    if (node.textContent) {
                        richTextParams.push({ text: node.textContent, font: isRed ? { color: { argb: 'FFFF0000' }, bold: true } : {} });
                    }
                } else if (node.nodeType === 1) {
                    if (node.tagName.toLowerCase() === 'br') {
                        richTextParams.push({ text: '\\n' });
                    } else if (node.tagName.toLowerCase() === 'div' || node.tagName.toLowerCase() === 'p') {
                        if (richTextParams.length > 0 && !richTextParams[richTextParams.length-1].text.endsWith('\\n')) {
                            richTextParams.push({ text: '\\n' });
                        }
                        node.childNodes.forEach(n => parseNodeEx(n, isRed));
                        richTextParams.push({ text: '\\n' });
                    } else {
                        let currentlyRed = isRed || node.classList.contains('revision-span') || node.style.color === 'red' || node.style.color === 'rgb(255, 0, 0)' || node.style.color === '#ef4444';
                        node.childNodes.forEach(n => parseNodeEx(n, currentlyRed));
                    }
                }
            }
            cleanSheet.childNodes.forEach(n => parseNodeEx(n, false));

            const excelRow = worksheet.addRow([]);
            const cellType = excelRow.getCell(1);
            cellType.value = finalSheetName;
            cellType.alignment = { vertical: 'top', wrapText: true };
            cellType.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };

            const cellContent = excelRow.getCell(2);
            cellContent.value = { richText: richTextParams };
            cellContent.alignment = { vertical: 'top', wrapText: true };
            cellContent.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        }
    });

    if (!hasContent) {
        Swal.fire('Error', 'No content to download', 'warning');
        return;
    }

    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = \`Script_\${scriptNum}.xlsx\`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    console.log('✓ ExcelJS Download complete!');
}
`;

fs.writeFileSync(file, c.substring(0, start) + newCode + '\n\n' + c.substring(endIdx));
console.log('Successfully replaced function with ExcelJS logic');
