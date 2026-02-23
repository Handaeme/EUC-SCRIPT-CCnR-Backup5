$dest = "_tools"
if (-not (Test-Path $dest)) {
    New-Item -ItemType Directory -Path $dest | Out-Null
}

$patterns = @(
    "check_*", 
    "debug_*", 
    "fix_*", 
    "migrate_*", 
    "setup_*", 
    "test_*", 
    "*.bat", 
    "*.sql", 
    "dump_database.php", 
    "view_logs.php", 
    "verify_schema.php", 
    "update_*.php", 
    "reset_data.php", 
    "repair_templates.php", 
    "list_cols.php", 
    "inspect_schema.php", 
    "force_add_column.php", 
    "fetch_cols.php", 
    "download_*.php", 
    "diag_db.php", 
    "add_template_description.php"
)

foreach ($p in $patterns) {
    Get-ChildItem -Path . -Filter $p | ForEach-Object {
        Move-Item -Path $_.FullName -Destination $dest -Force
        Write-Host "Moved $($_.Name)"
    }
}
