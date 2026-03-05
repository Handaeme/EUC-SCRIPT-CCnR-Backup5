$url = "https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"
$out = "d:\xampp\htdocs\EUC-Script-CCnR-Migrasi\public\js\chart.umd.min.js"
New-Item -ItemType Directory -Force -Path (Split-Path $out)
Invoke-WebRequest -Uri $url -OutFile $out
Write-Host "Downloaded Chart.js to $out"
