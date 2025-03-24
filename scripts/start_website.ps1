$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $projectRoot

$env:BASE_URL = "http://localhost:8000"

Write-Output "Starting the website on http://localhost:8000 ..."
Write-Output "Press Ctrl+C to stop the server."

& php -S localhost:8000 -t .

Set-Location "scripts"