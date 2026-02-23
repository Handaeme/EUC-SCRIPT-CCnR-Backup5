$files = @(
    "app/models/RequestModel.php",
    "app/models/TemplateModel.php",
    "app/views/request/create.php",
    "app/views/request/review.php",
    "app/views/template/index.php",
    "_simulate_race.php",
    "_worker_create.php"
)

$zipFile = "patch_fix_final.zip"

if (Test-Path $zipFile) {
    Remove-Item $zipFile
}

Compress-Archive -Path $files -DestinationPath $zipFile

Write-Host "Created $zipFile with updated files."
