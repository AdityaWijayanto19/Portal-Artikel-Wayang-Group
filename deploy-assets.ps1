# deploy-assets.ps1
# Build frontend di Laragon lalu upload public/build ke server Hostinger otomatis.
# Cara pakai (dari folder project):  .\deploy-assets.ps1
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

$user   = 'u711185757'
$hostip = '193.203.172.201'
$port   = '65002'
$remoteRoot = '/home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang/public/build'

Write-Host "== 1/3 Build frontend ==" -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { throw "Build gagal. Periksa error di atas." }

Write-Host "`n== 2/3 Hapus folder build lama di server ==" -ForegroundColor Cyan
ssh -p $port "$user@$hostip" "rm -rf $remoteRoot && mkdir -p $remoteRoot"
if ($LASTEXITCODE -ne 0) { throw "Gagal hapus build lama di server." }

Write-Host "`n== 3/3 Upload public/build ke server ==" -ForegroundColor Cyan
scp -r -P $port -o StrictHostKeyChecking=no "$PSScriptRoot\public\build" "$user@$hostip`:$remoteRoot"
if ($LASTEXITCODE -ne 0) { throw "Upload gagal." }

Write-Host "`nSelesai. Di server jalankan:" -ForegroundColor Green
Write-Host "cd /home/u711185757/domains/wayang.group/public_html/portal_artikel_wayang"
Write-Host "git pull"
Write-Host "/opt/alt/php84/usr/bin/php artisan view:clear config:cache route:cache view:cache"