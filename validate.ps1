# validate.ps1 - Validasi ParkirTertib satu perintah.
# Lapis 1: phpunit + phpstan + phpcs. Lapis 2: matriks HTTP read-only (akun seed).
# Pemakaian: powershell -ExecutionPolicy Bypass -File validate.ps1 [-BaseUrl http://127.0.0.1:8001]
param(
    [string]$Php = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe",
    [string]$BaseUrl = "http://127.0.0.1:8001"
)

$ErrorActionPreference = 'Continue'
$script:results = @()

function Add-Result([string]$name, [bool]$ok, [string]$detail = "") {
    $script:results += [pscustomobject]@{ Check = $name; Status = $(if ($ok) { "LULUS" } else { "GAGAL" }); Detail = $detail }
}

function Http-Code([string]$url, [string]$jar = "") {
    $args = @("-s", "-o", "NUL", "-w", "%{http_code}", "--max-time", "15", $url)
    if ($jar -ne "") { $args = @("-s", "-o", "NUL", "-w", "%{http_code}", "--max-time", "15", "-b", $jar, "-c", $jar) + @($url) }
    return (curl.exe @args).Trim()
}

function Http-Body([string]$url, [string]$jar = "") {
    if ($jar -ne "") { return curl.exe -s --max-time 15 -b $jar -c $jar $url }
    return curl.exe -s --max-time 15 $url
}

function Get-Csrf([string]$html) {
    $m = [regex]::Match($html, 'name="csrf_token" value="([^"]+)"')
    if ($m.Success) { return $m.Groups[1].Value }
    return ""
}

function Do-Login([string]$jar, [string]$email, [string]$password) {
    if (Test-Path $jar) { Remove-Item $jar -Force }
    $html = Http-Body "$BaseUrl/login" $jar
    $token = Get-Csrf ($html -join "`n")
    if ($token -eq "") { return "no-token" }
    $code = curl.exe -s -o NUL -w "%{http_code}" --max-time 15 -b $jar -c $jar `
        --data-urlencode "csrf_token=$token" --data-urlencode "email=$email" `
        --data-urlencode "password=$password" "$BaseUrl/login"
    return $code.Trim()
}

Write-Output "== Lapis 1: otomatis =="
& $Php vendor\bin\phpunit 2>&1 | Select-Object -Last 2 | ForEach-Object { Write-Output $_ }
Add-Result "phpunit" ($LASTEXITCODE -eq 0) "exit=$LASTEXITCODE"
& $Php vendor\bin\phpstan analyse src/ --level=5 --no-progress 2>&1 | Select-Object -Last 2 | ForEach-Object { Write-Output $_ }
Add-Result "phpstan" ($LASTEXITCODE -eq 0) "exit=$LASTEXITCODE"
& $Php vendor\bin\phpcs --standard=PSR12 src/ tests/ bin/ 2>&1 | Select-Object -Last 3 | ForEach-Object { Write-Output $_ }
Add-Result "phpcs" ($LASTEXITCODE -eq 0) "exit=$LASTEXITCODE"

Write-Output ""
Write-Output "== Lapis 2: HTTP (server uji 127.0.0.1:8001) =="
$proc = Start-Process -FilePath $Php -ArgumentList "-S", "127.0.0.1:8001", "-t", "$PSScriptRoot\public" -PassThru
Start-Sleep -Seconds 3
try {
    # Anonim
    Add-Result "anon /login 200" ((Http-Code "$BaseUrl/login") -eq "200")
    Add-Result "anon /report/create -> login" ((Http-Code "$BaseUrl/report/create") -eq "302")
    Add-Result "anon /admin -> login" ((Http-Code "$BaseUrl/admin") -eq "302")
    Add-Result "anon /api/notifications 302" ((Http-Code "$BaseUrl/api/notifications") -eq "302")
    Add-Result "anon /jalan-ngawur 404" ((Http-Code "$BaseUrl/jalan-ngawur") -eq "404")

    # User seed
    $jarU = "$env:TEMP\validate_user.txt"
    $loginU = Do-Login $jarU "ahmad@mhs.unimed.ac.id" "Password123!"
    Add-Result "login user -> history" ($loginU -eq "302")
    $hist = (Http-Body "$BaseUrl/report/history" $jarU) -join "`n"
    Add-Result "user /report/history 200" ($hist -match "Riwayat Laporan")
    $api = (Http-Body "$BaseUrl/api/notifications" $jarU) -join "`n"
    Add-Result "user /api/notifications JSON" (($api -match '"reports"') -and ($api -match '"server_time"'))
    Add-Result "user /admin 403" ((Http-Code "$BaseUrl/admin" $jarU) -eq "403")
    Add-Result "user /api/admin/updates 403" ((Http-Code "$BaseUrl/api/admin/updates" $jarU) -eq "403")

    # Admin seed
    $jarA = "$env:TEMP\validate_admin.txt"
    $loginA = Do-Login $jarA "satpam@unimed.ac.id" "Password123!"
    Add-Result "login admin -> /admin" ($loginA -eq "302")
    $dash = (Http-Body "$BaseUrl/admin" $jarA) -join "`n"
    Add-Result "admin /admin dashboard" ($dash -match "Dashboard Laporan")
    $apiA = (Http-Body "$BaseUrl/api/admin/updates" $jarA) -join "`n"
    Add-Result "admin /api/admin/updates JSON" (($apiA -match '"counts"') -and ($apiA -match '"server_time"'))
    Add-Result "admin repeat-offenders 200" (((Http-Body "$BaseUrl/admin/repeat-offenders" $jarA) -join "`n") -match "Pelanggar Berulang")
    Add-Result "admin detail hilang 404" ((Http-Code "$BaseUrl/admin/report/detail?id=99999" $jarA) -eq "404")

    # Aset statis
    Add-Result "style.css 200" ((Http-Code "$BaseUrl/assets/css/style.css") -eq "200")
    $js = (Http-Body "$BaseUrl/assets/js/app.js") -join "`n"
    Add-Result "app.js baru (top-4)" ($js -match "top-4 left-1/2")
}
finally {
    Stop-Process -Id $proc.Id -Force
    foreach ($f in @("$env:TEMP\validate_user.txt", "$env:TEMP\validate_admin.txt")) {
        if (Test-Path $f) { Remove-Item $f -Force }
    }
}

Write-Output ""
Write-Output "== Ringkasan =="
$script:results | Format-Table -AutoSize | Out-String | Write-Output
$fail = @($script:results | Where-Object { $_.Status -eq "GAGAL" }).Count
if ($fail -eq 0) { Write-Output "SEMUA LULUS ($($script:results.Count) cek)"; exit 0 }
else { Write-Output "$fail DARI $($script:results.Count) CEK GAGAL"; exit 1 }
