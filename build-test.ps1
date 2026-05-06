Set-Location "g:\wwwroot\StarryNight\starrynight-frontend"
node .\node_modules\vite\bin\vite.js build 2>&1
Write-Host "EXIT_CODE: $LASTEXITCODE"
