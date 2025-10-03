@echo off
echo.
echo ========================================
echo    ReTexify AI - Plugin ZIP Creator
echo ========================================
echo.

REM Prüfe ob wir im richtigen Verzeichnis sind
if not exist "retexify.php" (
    echo FEHLER: retexify.php nicht gefunden!
    echo Bitte führe dieses Script im Plugin-Verzeichnis aus.
    pause
    exit /b 1
)

echo [1/5] Lösche alte ZIP-Dateien...
if exist "retexify-ai.zip" del "retexify-ai.zip"
if exist "temp-plugin-folder" rmdir /s /q "temp-plugin-folder"

echo [2/5] Erstelle temporären Plugin-Ordner...
mkdir "temp-plugin-folder"

echo [3/5] Kopiere alle Plugin-Dateien...
copy "retexify.php" "temp-plugin-folder\retexify.php"
if exist "includes" xcopy "includes" "temp-plugin-folder\includes" /E /I /Q
if exist "assets" xcopy "assets" "temp-plugin-folder\assets" /E /I /Q
if exist "*.md" copy "*.md" "temp-plugin-folder\"
if exist "LICENSE" copy "LICENSE" "temp-plugin-folder\"
if exist ".gitignore" copy ".gitignore" "temp-plugin-folder\"

echo [4/5] Erstelle WordPress-kompatible ZIP-Datei...
powershell -Command "Compress-Archive -Path 'temp-plugin-folder' -DestinationPath 'retexify-ai.zip' -Force"

echo [5/5] Aufräumen...
rmdir /s /q "temp-plugin-folder"

echo.
echo ========================================
echo           ERFOLGREICH ERSTELLT!
echo ========================================
echo.
echo ZIP-Datei: retexify-ai.zip
echo Größe: 
for %%I in (retexify-ai.zip) do echo %%~zI Bytes
echo.
echo Diese ZIP-Datei kann direkt in WordPress installiert werden!
echo.
echo Installation:
echo 1. WordPress Admin → Plugins → Installieren
echo 2. Plugin hochladen → retexify-ai.zip auswählen
echo 3. Installieren → Aktivieren
echo.
pause
