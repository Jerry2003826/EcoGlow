# Eco Glow one-click launcher for Windows.
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $Root

function Write-Step([string]$Message) {
    Write-Host ""
    Write-Host "==> $Message"
}

function Find-Php {
    $candidates = @()
    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd) { $candidates += $cmd.Source }
    $candidates += @(
        "$env:ProgramFiles\PHP\php.exe",
        "$env:ProgramFiles\PHP\8.4\php.exe",
        "$env:ProgramFiles\PHP\8.3\php.exe",
        "$env:ProgramFiles\PHP\8.2\php.exe",
        "C:\xampp\php\php.exe",
        "C:\laragon\bin\php\php.exe"
    )
    Get-ChildItem "C:\laragon\bin\php" -Directory -ErrorAction SilentlyContinue |
        ForEach-Object { $candidates += (Join-Path $_.FullName "php.exe") }
    Get-ChildItem "C:\wamp64\bin\php" -Directory -ErrorAction SilentlyContinue |
        ForEach-Object { $candidates += (Join-Path $_.FullName "php.exe") }

    foreach ($path in $candidates) {
        if (-not (Test-Path $path)) { continue }
        try {
            $version = & $path -r "echo PHP_VERSION_ID;"
            if ([int]$version -ge 80200) { return $path }
        } catch {
            continue
        }
    }
    return $null
}

function Add-TypicalPaths {
    $extra = @(
        "$env:ProgramFiles\PHP",
        "$env:ProgramFiles\PHP\8.4",
        "$env:ProgramFiles\MariaDB 11.4\bin",
        "$env:ProgramFiles\MariaDB 11.3\bin",
        "$env:ProgramFiles\MySQL\MySQL Server 8.4\bin",
        "$env:ProgramFiles\MySQL\MySQL Server 8.0\bin",
        "C:\xampp\php",
        "C:\xampp\mysql\bin",
        "C:\laragon\bin\php",
        "C:\laragon\bin\mysql\bin"
    )
    foreach ($dir in $extra) {
        if ((Test-Path $dir) -and ($env:Path -notlike "*$dir*")) {
            $env:Path = "$dir;$env:Path"
        }
    }
}

function Install-WithWinget([string]$Id) {
    if (-not (Get-Command winget -ErrorAction SilentlyContinue)) {
        return $false
    }
    Write-Step "Installing $Id with winget (may take a few minutes)"
    & winget install --id $Id -e --accept-package-agreements --accept-source-agreements
    return $LASTEXITCODE -eq 0
}

function Start-LocalMysql {
    $services = @("MySQL80", "MySQL84", "MySQL", "MariaDB", "mariadb")
    foreach ($name in $services) {
        $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
        if ($svc -and $svc.Status -ne "Running") {
            try { Start-Service $name } catch { }
        }
    }
    if (Test-Path "C:\xampp\mysql_start.bat") {
        Start-Process -FilePath "C:\xampp\mysql_start.bat" -WindowStyle Hidden
    }
}

Add-TypicalPaths
$php = Find-Php
if (-not $php) {
    Write-Step "PHP 8.2+ not found. Trying winget..."
    Install-WithWinget "PHP.PHP.8.4" | Out-Null
    Add-TypicalPaths
    $env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
        [System.Environment]::GetEnvironmentVariable("Path", "User")
    Add-TypicalPaths
    $php = Find-Php
}

if (-not $php) {
    Write-Host "PHP 8.2+ is required."
    Write-Host "Install XAMPP (https://www.apachefriends.org/) or run:"
    Write-Host "  winget install PHP.PHP.8.4"
    exit 1
}

$mysql = Get-Command mysql -ErrorAction SilentlyContinue
if (-not $mysql) {
    $mysqlGuess = @(
        "C:\xampp\mysql\bin\mysql.exe",
        "$env:ProgramFiles\MariaDB 11.4\bin\mysql.exe",
        "$env:ProgramFiles\MySQL\MySQL Server 8.4\bin\mysql.exe"
    ) | Where-Object { Test-Path $_ } | Select-Object -First 1
    if (-not $mysqlGuess) {
        Write-Step "MySQL/MariaDB client not found. Trying winget MariaDB..."
        Install-WithWinget "MariaDB.Server" | Out-Null
        Add-TypicalPaths
    }
}

Start-LocalMysql
Write-Step "Using PHP $(& $php -r 'echo PHP_VERSION;')"
& $php (Join-Path $Root "bin\dev_up.php") @args
exit $LASTEXITCODE
