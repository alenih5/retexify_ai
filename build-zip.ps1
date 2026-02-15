# ReTexify AI Plugin ZIP Builder
# Version 4.24.0 - Media SEO Fix
$ErrorActionPreference = "Stop"

$pluginDir = "c:\Users\Alen_\OneDrive\Desktop\Plugin Imponi\retexify_ai"
$tempDir = "$env:TEMP\retexify_build_temp"
$zipFile = "$pluginDir\retexify_ai_4.25.0.zip"

# Alte Temp-Dateien bereinigen
if (Test-Path $tempDir) { Remove-Item $tempDir -Recurse -Force }
if (Test-Path $zipFile) { Remove-Item $zipFile -Force }

# Plugin-Verzeichnis erstellen
$pluginTarget = "$tempDir\retexify_ai"
New-Item -ItemType Directory -Path $pluginTarget -Force | Out-Null

# Hauptdatei kopieren
Copy-Item "$pluginDir\retexify.php" "$pluginTarget\retexify.php"

# Includes kopieren
New-Item -ItemType Directory -Path "$pluginTarget\includes" -Force | Out-Null
Get-ChildItem "$pluginDir\includes\*.php" | ForEach-Object {
    Copy-Item $_.FullName "$pluginTarget\includes\$($_.Name)"
    Write-Host "  Kopiert: includes/$($_.Name)"
}

# Assets kopieren
New-Item -ItemType Directory -Path "$pluginTarget\assets" -Force | Out-Null
Get-ChildItem "$pluginDir\assets\*" | ForEach-Object {
    Copy-Item $_.FullName "$pluginTarget\assets\$($_.Name)"
    Write-Host "  Kopiert: assets/$($_.Name)"
}

# README und CHANGELOG kopieren
if (Test-Path "$pluginDir\README.md") { Copy-Item "$pluginDir\README.md" "$pluginTarget\README.md" }
if (Test-Path "$pluginDir\CHANGELOG.md") { Copy-Item "$pluginDir\CHANGELOG.md" "$pluginTarget\CHANGELOG.md" }
if (Test-Path "$pluginDir\LICENSE") { Copy-Item "$pluginDir\LICENSE" "$pluginTarget\LICENSE" }

# ZIP erstellen
Write-Host "`nErstelle ZIP-Datei..."
Compress-Archive -Path "$tempDir\retexify_ai" -DestinationPath $zipFile -Force

# Aufräumen
Remove-Item $tempDir -Recurse -Force

# Ergebnis
$size = (Get-Item $zipFile).Length
Write-Host "`n=========================================="
Write-Host "ZIP erstellt: $zipFile"
Write-Host "Groesse: $([math]::Round($size / 1KB, 1)) KB"
Write-Host "=========================================="
