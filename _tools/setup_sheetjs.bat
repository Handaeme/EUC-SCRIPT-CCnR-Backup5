@echo off
cd /d %~dp0
echo ==========================================
echo      SETUP SHEETJS (EXCEL LIBRARY)
echo ==========================================
echo.
echo 1. Checking folder...

if not exist "public\assets\js" (
    mkdir "public\assets\js"
    echo    - Folder created: public\assets\js
) else (
    echo    - Folder exists: public\assets\js
)

echo.
echo 2. Downloading xlsx.full.min.js (With Style Support)...

powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js' -OutFile 'public\assets\js\xlsx.full.min.js'"

if exist "public\assets\js\xlsx.full.min.js" (
    echo.
    echo [SUCCESS] File downloaded successfully!
    echo Location: public\assets\js\xlsx.full.min.js
) else (
    echo.
    echo [ERROR] Download failed. Please download manually from:
    echo https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js
)

echo.
pause
