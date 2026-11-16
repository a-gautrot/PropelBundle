[CmdletBinding()]
param(
    [string] $PhpBinary = 'php'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$modelDirectory = Join-Path $PSScriptRoot 'Fixtures/Model'
$temporaryRoot = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$generationDirectory = Join-Path $temporaryRoot ('propel-fixtures-' + [guid]::NewGuid().ToString('N'))

New-Item -ItemType Directory -Path $generationDirectory | Out-Null
Push-Location $projectRoot
try
{
    & $PhpBinary Tests/build-models.php $generationDirectory

    if ($LASTEXITCODE -ne 0)
    {
        throw "Propel model generation failed (exit code $LASTEXITCODE)."
    }

    $generatedModels = Join-Path $generationDirectory 'Propel/Bundle/PropelBundle/Tests/Fixtures/Model'
    $generatedFiles = @(
        'Base/Book.php', 'Base/BookQuery.php', 'Map/BookTableMap.php',
        'Base/User.php', 'Base/UserQuery.php', 'Map/UserTableMap.php'
    )

    foreach ($file in $generatedFiles)
    {
        if (!(Test-Path -LiteralPath (Join-Path $generatedModels $file) -PathType Leaf))
        {
            throw "Propel did not generate the expected file: $file"
        }
    }

    foreach ($file in $generatedFiles)
    {
        $destination = Join-Path $modelDirectory $file
        New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force | Out-Null
        Copy-Item -LiteralPath (Join-Path $generatedModels $file) -Destination $destination
    }

    $files = @('Book.php', 'BookQuery.php', 'User.php', 'UserQuery.php')

    foreach ($file in $files)
    {
        $destination = Join-Path $modelDirectory $file
        if (!(Test-Path -LiteralPath $destination))
        {
            Copy-Item -LiteralPath (Join-Path $generatedModels $file) -Destination $destination
        }
    }

    Write-Host 'Book and User fixture models generated successfully.'
}
finally
{
    Pop-Location
    $resolvedGenerationDirectory = [System.IO.Path]::GetFullPath($generationDirectory)
    if ((Split-Path -Parent $resolvedGenerationDirectory).TrimEnd('\', '/') -eq $temporaryRoot.TrimEnd('\', '/') `
         -and (Split-Path -Leaf $resolvedGenerationDirectory) -match '^propel-fixtures-[a-f0-9]{32}$')
    {
        Remove-Item -LiteralPath $resolvedGenerationDirectory -Recurse -Force
    }
}
