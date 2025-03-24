$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $projectRoot

$env:BASE_URL = "http://localhost:8000"

Write-Output "Starting PHP built-in server on port 8000..."
$server = Start-Process -FilePath "php" -ArgumentList "-S localhost:8000 -t ." -NoNewWindow -PassThru

Start-Sleep -Seconds 5

Write-Output "Running PHPUnit tests..."
$phpunit = Start-Process -FilePath "php" -ArgumentList "vendor\bin\phpunit --configuration phpunit.xml" -NoNewWindow -Wait -PassThru
$exitCode = $phpunit.ExitCode

Write-Output "Stopping PHP built-in server (PID $($server.Id))..."
Stop-Process -Id $server.Id

Set-Location "scripts"
exit $exitCode
