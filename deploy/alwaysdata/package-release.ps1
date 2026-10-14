<#
  Sofra: build a ready-to-upload release on YOUR Windows computer.

  Why: alwaysdata's free plan has only 256 MB of RAM, too little to run
  "composer install" and the asset build there. So everything is built here and
  uploaded as one file. The package contains only committed code, production
  dependencies and the built CSS/JS. It never contains .env, tests or dev tools.

    powershell -ExecutionPolicy Bypass -File deploy\alwaysdata\package-release.ps1

  Result: sofra-release.tar.gz in the project folder (ignored by git).
#>
param([string]$Out = 'sofra-release.tar.gz')

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
Set-Location $root

function Step($text) { Write-Host "`n==> $text" -ForegroundColor Green }
function Run($file, [string[]]$arguments) {
    & $file @arguments
    if ($LASTEXITCODE -ne 0) { throw "$file failed (exit $LASTEXITCODE)" }
}

$git = (Get-Command git -ErrorAction SilentlyContinue).Source
if (-not $git) { $git = 'C:\laragon\bin\git\cmd\git.exe' }
if (-not (Test-Path $git)) { throw 'git was not found.' }

if (git -C $root status --porcelain) {
    throw 'There are uncommitted changes. Commit them first: the release is built from the last commit.'
}

$composer = (Get-Command composer -ErrorAction SilentlyContinue).Source
$composerArgs = @()
if (-not $composer) {
    $phar = 'C:\laragon\bin\composer\composer.phar'
    if (-not (Test-Path $phar)) { throw 'Composer was not found.' }
    $composer = (Get-Command php).Source
    $composerArgs = @($phar)
}

$tmp = Join-Path ([IO.Path]::GetTempPath()) ('sofra-release-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $tmp | Out-Null

try {
    Step 'Exporting the committed code'
    $archive = Join-Path $tmp 'src.tar'
    Run $git @('archive', '--format=tar', '-o', $archive, 'HEAD')
    $src = Join-Path $tmp 'app'
    New-Item -ItemType Directory -Path $src | Out-Null
    Run 'tar' @('-xf', $archive, '-C', $src)
    Remove-Item $archive

    Step 'Installing production dependencies (no dev tools)'
    Run $composer ($composerArgs + @('install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--no-progress', '--ignore-platform-reqs', "--working-dir=$src"))

    Step 'Building the CSS and JavaScript'
    if (-not (Test-Path (Join-Path $root 'node_modules'))) { Run 'npm' @('ci', '--no-audit', '--no-fund') }
    Run 'npm' @('run', 'build')
    Copy-Item (Join-Path $root 'public\build') (Join-Path $src 'public\build') -Recurse

    Step 'Removing what the server does not need'
    foreach ($name in '.claude', 'tests', 'AGENTS.md', 'CLAUDE.md', 'boost.json', 'phpunit.xml', '.editorconfig') {
        $path = Join-Path $src $name
        if (Test-Path $path) { Remove-Item $path -Recurse -Force }
    }
    if (Test-Path (Join-Path $src '.env')) { throw 'A .env file ended up in the package. Stopping.' }

    Step "Creating $Out"
    $target = Join-Path $root $Out
    if (Test-Path $target) { Remove-Item $target -Force }
    Run 'tar' @('-czf', $target, '-C', $src, '.')

    $size = [math]::Round((Get-Item $target).Length / 1MB, 1)
    $files = (Get-ChildItem $src -Recurse -File | Measure-Object).Count
    $hash = (Get-FileHash $target -Algorithm SHA256).Hash
    Write-Host "`nDone: $target" -ForegroundColor Green
    Write-Host "Size: $size MB, $files files"
    Write-Host "SHA256: $hash"
}
finally {
    Remove-Item $tmp -Recurse -Force -ErrorAction SilentlyContinue
}
