@echo off
if not exist "assets\js" mkdir "assets\js"
echo Downloading SheetJS...
powershell -Command "Invoke-WebRequest -Uri https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js -OutFile assets/js/xlsx.full.min.js"
echo Downloading Mammoth...
powershell -Command "Invoke-WebRequest -Uri https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js -OutFile assets/js/mammoth.browser.min.js"
echo Done.
dir assets\js
