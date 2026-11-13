#requires -Version 7.0
[CmdletBinding()]
param(
    [ValidateSet('Start', 'Stop')]
    [string] $Action = 'Start'
)

$ErrorActionPreference = 'Stop'

if (!$IsWindows -or !$env:RUNNER_TEMP -or !$env:GITHUB_ENV) {
    throw 'This script requires a GitHub Actions Windows runner.'
}

if ($Action -eq 'Stop') {
    if (!$env:ORACLE_CI_HOME) {
        return
    }
    $relative = [System.IO.Path]::GetRelativePath(($env:SystemDrive + '\'), $env:ORACLE_CI_HOME).Replace('\', '/')
    if ($relative -cnotmatch '\Aoracle-ci-[a-f0-9]{32}/dbhomeFree\z') {
        throw 'Refusing to stop Oracle outside its dedicated runner directory.'
    }
    Get-CimInstance Win32_Service | Where-Object {
        $_.PathName -like ('*' + $env:ORACLE_CI_HOME + '\*')
    } | ForEach-Object {
        Stop-Service -Name $_.Name -Force
    }
    return
}

# Oracle's Windows download and SHA256, published at:
# https://www.oracle.com/database/free/get-started/
$url = 'https://download.oracle.com/otn-pub/otn_software/db-free/oracle-ai-database-free-26ai-23.26.3.windows.x64.zip'
$sha256 = '178D6CFD5A8109EA9D26894A597116DB4D95320CD8861D16CA10D4D8C384DAB3'
$directory = Join-Path $env:RUNNER_TEMP ('oracle-ci-' + [guid]::NewGuid().ToString('N'))
$null = New-Item -ItemType Directory -Path $directory
$archive = Join-Path $directory 'oracle.zip'
# Let Oracle create its own tree with the required ACLs. RUNNER_TEMP's
# ancestors may be writable by Authenticated Users, which Oracle disallows.
$installDirectory = Join-Path ($env:SystemDrive + '\') ('oracle-ci-' + [guid]::NewGuid().ToString('N'))
$oracleHome = Join-Path $installDirectory 'dbhomeFree'
$log = Join-Path $directory 'setup.log'
$response = Join-Path $directory 'install.rsp'
$password = 'Ci_' + [guid]::NewGuid().ToString('N')
Write-Output "::add-mask::$password"

Invoke-WebRequest -Uri $url -OutFile $archive -TimeoutSec 600
if ((Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash -cne $sha256) {
    throw 'The Oracle archive checksum does not match the reviewed release.'
}
Expand-Archive -LiteralPath $archive -DestinationPath $directory
$setup = Get-ChildItem -LiteralPath $directory -Filter setup.exe -Recurse | Select-Object -First 1
if ($null -eq $setup) {
    throw 'The Oracle installer was not found in the archive.'
}

# Follow the silent response-file installation documented by Oracle.
# setup-php may have configured Instant Client; the installer requires these unset.
$clientEnvironment = @{}
foreach ($name in @('ORACLE_HOME', 'ORACLE_SID', 'TNS_ADMIN', 'PATH')) {
    $clientEnvironment[$name] = [Environment]::GetEnvironmentVariable($name, 'Process')
}
$template = Get-ChildItem -LiteralPath $directory -Filter FREEInstall.rsp -Recurse | Select-Object -First 1
if ($null -eq $template) {
    throw 'The Oracle installation response file was not found.'
}
$settings = Get-Content -LiteralPath $template.FullName -Raw
foreach ($setting in @{
    INSTALLDIR = $installDirectory
    PASSWORD = $password
    LISTENER_PORT = '1521'
    CHAR_SET = 'AL32UTF8'
    SOFTWARE_ONLY = 'FALSE'
}.GetEnumerator()) {
    $pattern = '(?m)^' + $setting.Key + '=.*$'
    if ($settings -notmatch $pattern) {
        throw "The Oracle response file does not define $($setting.Key)."
    }
    $value = $setting.Key + '=' + $setting.Value
    $settings = [regex]::Replace($settings, $pattern, [System.Text.RegularExpressions.MatchEvaluator] { $value })
}
$settings | Set-Content -LiteralPath $response
Add-Content -LiteralPath $env:GITHUB_ENV -Value "ORACLE_CI_HOME=$oracleHome"
try {
    Remove-Item Env:ORACLE_HOME, Env:TNS_ADMIN -ErrorAction SilentlyContinue
    $installer = Start-Process -FilePath $setup.FullName -PassThru -WindowStyle Hidden -ArgumentList @(
        '/s', "/v`"RSP_FILE=$response`"", "/v`"/L*v $log`"", '/v"/qn"'
    )
    if (!$installer.WaitForExit(900000)) {
        throw 'Oracle installation did not finish within 15 minutes.'
    }
    if ($installer.ExitCode -notin @(0, 3010)) {
        throw "Oracle installation failed with exit code $($installer.ExitCode)."
    }

    $sqlplus = Join-Path $oracleHome 'bin/sqlplus.exe'
    # Use the server's configuration and DLLs, not setup-php's Instant Client.
    $env:ORACLE_HOME = $oracleHome
    $env:ORACLE_SID = 'FREE'
    $env:TNS_ADMIN = Join-Path $oracleHome 'network/admin'
    $env:PATH = (Join-Path $oracleHome 'bin') + ';' + $env:PATH
    # The bootstrap installer may exit before its MSI/database configuration finishes.
    $deadline = [DateTime]::UtcNow.AddMinutes(15)
    $ready = $false
    $lastProbe = 'sqlplus.exe has not been installed yet.'
    $reportedProbe = ''
    while ([DateTime]::UtcNow -lt $deadline) {
        if (Test-Path -LiteralPath $sqlplus) {
            $probe = @(
                'WHENEVER OSERROR EXIT FAILURE',
                'WHENEVER SQLERROR EXIT FAILURE',
                "CONNECT sys/`"$password`"@//127.0.0.1:1521/FREEPDB1 AS SYSDBA",
                'SELECT 1 FROM dual;',
                'EXIT SUCCESS'
            ) | & $sqlplus -L -S /nolog 2>&1
            if ($LASTEXITCODE -eq 0) {
                $ready = $true
                break
            }
            $lastProbe = "sqlplus exit code ${LASTEXITCODE}: " + ($probe | Out-String).Trim().Replace($password, '[REDACTED]')
        }
        if ($lastProbe -ne $reportedProbe) {
            Write-Output "Waiting for Oracle FREEPDB1: $lastProbe"
            $reportedProbe = $lastProbe
        }
        Start-Sleep -Seconds 5
    }
    if (!$ready) {
        throw "Oracle FREEPDB1 did not become ready within 15 minutes. Last probe: $lastProbe"
    }
    @(
        'WHENEVER OSERROR EXIT FAILURE',
        'WHENEVER SQLERROR EXIT FAILURE',
        "CONNECT sys/`"$password`"@//127.0.0.1:1521/FREEPDB1 AS SYSDBA",
        'CREATE USER propel_bundle IDENTIFIED BY "bundle_test" DEFAULT TABLESPACE users;',
        'GRANT CONNECT, RESOURCE, CREATE VIEW TO propel_bundle;',
        'ALTER USER propel_bundle QUOTA UNLIMITED ON users;',
        'EXIT SUCCESS'
    ) | & $sqlplus -L -S /nolog
    if ($LASTEXITCODE -ne 0) {
        throw 'Cannot create the Oracle test schema.'
    }
} catch {
    # MSI success alone does not diagnose database creation or listener failures.
    Get-CimInstance Win32_Service -ErrorAction Continue | Where-Object {
        $_.PathName -like ('*' + $oracleHome + '\*')
    } | Select-Object Name, State, PathName | Format-Table -AutoSize | Out-String | Write-Output
    foreach ($folder in @('cfgtoollogs', 'diag')) {
        $path = Join-Path $installDirectory $folder
        if (Test-Path -LiteralPath $path) {
            Get-ChildItem -LiteralPath $path -Recurse -File -Filter '*.log' -ErrorAction Continue |
                Sort-Object LastWriteTime -Descending | Select-Object -First 5 | ForEach-Object {
                    Write-Output "Oracle diagnostic log: $($_.FullName)"
                    Get-Content -LiteralPath $_.FullName -Tail 60 -ErrorAction Continue |
                        ForEach-Object { $_.Replace($password, '[REDACTED]') }
                }
        }
    }
    if (Test-Path -LiteralPath $log) {
        Get-Content -LiteralPath $log -Tail 40 | ForEach-Object { $_.Replace($password, '[REDACTED]') }
    }
    throw
} finally {
    foreach ($name in $clientEnvironment.Keys) {
        [Environment]::SetEnvironmentVariable($name, $clientEnvironment[$name], 'Process')
    }
    Remove-Item -LiteralPath $response -ErrorAction SilentlyContinue
}
