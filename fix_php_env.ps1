$iniFile = (php --ini | Select-String "Loaded Configuration File:").ToString().Split(":", 2)[1].Trim()

if (Test-Path $iniFile) {
    Write-Host "Updating php.ini at: $iniFile" -ForegroundColor Cyan
    
    $content = Get-Content $iniFile -Raw
    $content = $content -replace ';extension=pdo_pgsql', 'extension=pdo_pgsql'
    $content = $content -replace ';extension=pgsql', 'extension=pgsql'
    $content = $content -replace ';extension_dir = "ext"', 'extension_dir = "ext"'
    
    Set-Content -Path $iniFile -Value $content -NoNewline
    Write-Host "php.ini updated successfully." -ForegroundColor Green
} else {
    Write-Host "Could not locate php.ini." -ForegroundColor Red
    exit 1
}

Write-Host "Verifying database connection..." -ForegroundColor Cyan
php artisan db:monitor

Write-Host "Running central migrations..." -ForegroundColor Cyan
php artisan migrate

Write-Host "Running tenant migrations..." -ForegroundColor Cyan
php artisan tenants:migrate
