$f = 'g:\wwwroot\StarryNight\starrynight-frontend\src\components\layout\UserLayout.vue'
$c = Get-Content $f -Raw
$c = $c -replace '\$spacing-', '$space-'
Set-Content $f -Value $c -NoNewline
Write-Host 'Fixed UserLayout.vue'
