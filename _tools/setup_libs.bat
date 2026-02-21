@echo off
if not exist "public\js" mkdir "public\js"
echo Downloading SheetJS...
powershell -Command "Invoke-WebRequest -Uri https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js -OutFile public/js/xlsx.full.min.js"
echo Downloading Mammoth...
powershell -Command "Invoke-WebRequest -Uri https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js -OutFile public/js/mammoth.browser.min.js"
echo Done.
dir public\js
