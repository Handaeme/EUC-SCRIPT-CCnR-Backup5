@echo off
title CITRA Script Manager - Install Libraries

echo ==================================================
echo      INSTALLING LOCAL LIBRARIES (OFFLINE MODE)
echo ==================================================
echo.

:: 1. Create Directory
echo [1/3] Checking directory 'assets\js'...
if not exist "assets\js" (
    mkdir "assets\js"
    echo        -> Directory created successfully.
) else (
    echo        -> Directory already exists.
)
echo.

:: 2. Download SheetJS
echo [2/3] Downloading SheetJS (Excel Reader)...
powershell -Command "try { Invoke-WebRequest -Uri https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js -OutFile assets/js/xlsx.full.min.js; Write-Host '       -> Success' } catch { Write-Host '       -> FAILED: ' $_.Exception.Message -ForegroundColor Red }"
echo.

:: 3. Download Mammoth
echo [3/3] Downloading Mammoth (Word Reader)...
powershell -Command "try { Invoke-WebRequest -Uri https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js -OutFile assets/js/mammoth.browser.min.js; Write-Host '       -> Success' } catch { Write-Host '       -> FAILED: ' $_.Exception.Message -ForegroundColor Red }"
echo.

echo ==================================================
echo                  INSTALLATION COMPLETE
echo ==================================================
echo.
echo Please check for any error messages above.
echo If successful, you can now use Auto-Preview without Internet.
echo.
pause
