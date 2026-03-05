@echo off
echo ====================================================
echo Mengunduh Chart.js untuk kebutuhan Lokal EUC Script
echo ====================================================

:: Buat folder js jika belum ada
if not exist "public\js" mkdir "public\js"

echo.
echo Mencoba mengunduh menggunakan CURL...
curl -k -A "Mozilla/5.0" -L "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" -o "public\js\chart.umd.min.js"

if exist "public\js\chart.umd.min.js" (
    echo.
    echo BERHASIL! File chart.umd.min.js sudah tersimpan di public\js\
) else (
    echo.
    echo CURL gagal. Mencoba menggunakan PowerShell...
    powershell -Command "[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js' -OutFile 'public\js\chart.umd.min.js'"
    
    if exist "public\js\chart.umd.min.js" (
        echo.
        echo BERHASIL! File chart.umd.min.js sudah tersimpan di public\js\
    ) else (
        echo.
        echo GAGAL! Komputer Anda benar-benar memblokir download dari Command Prompt.
        echo Tolong download manual via browser ya.
    )
)

echo.
pause
