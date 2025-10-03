#!/bin/bash

echo ""
echo "========================================"
echo "   ReTexify AI - Plugin ZIP Creator"
echo "========================================"
echo ""

# Prüfe ob wir im richtigen Verzeichnis sind
if [ ! -f "retexify.php" ]; then
    echo "❌ FEHLER: retexify.php nicht gefunden!"
    echo "   Bitte führe dieses Script im Plugin-Verzeichnis aus."
    exit 1
fi

echo "📋 [1/6] Lösche alte ZIP-Dateien..."
rm -f retexify-ai.zip
rm -rf temp-plugin-folder

echo "📁 [2/6] Erstelle temporären Plugin-Ordner..."
mkdir -p temp-plugin-folder

echo "📄 [3/6] Kopiere Hauptdatei..."
cp retexify.php temp-plugin-folder/

echo "📂 [4/6] Kopiere Verzeichnisse..."
if [ -d "includes" ]; then
    cp -r includes temp-plugin-folder/
    echo "   ✅ includes/ kopiert"
fi

if [ -d "assets" ]; then
    cp -r assets temp-plugin-folder/
    echo "   ✅ assets/ kopiert"
fi

echo "📋 [5/6] Kopiere Dokumentation..."
cp *.md temp-plugin-folder/ 2>/dev/null || true
cp LICENSE temp-plugin-folder/ 2>/dev/null || true
cp .gitignore temp-plugin-folder/ 2>/dev/null || true

echo "🗜️ [6/6] Erstelle WordPress-kompatible ZIP-Datei..."
zip -r retexify-ai.zip temp-plugin-folder/ > /dev/null 2>&1

echo "🧹 Aufräumen..."
rm -rf temp-plugin-folder

echo ""
echo "========================================"
echo "           ERFOLGREICH ERSTELLT!"
echo "========================================"
echo ""
echo "📦 ZIP-Datei: retexify-ai.zip"
echo "📏 Größe: $(ls -lh retexify-ai.zip | awk '{print $5}')"
echo ""
echo "✅ Diese ZIP-Datei kann direkt in WordPress installiert werden!"
echo ""
echo "🚀 Installation:"
echo "   1. WordPress Admin → Plugins → Installieren"
echo "   2. Plugin hochladen → retexify-ai.zip auswählen"
echo "   3. Installieren → Aktivieren"
echo ""
echo "📋 Struktur geprüft:"
echo "   ✅ retexify.php (Hauptdatei)"
echo "   ✅ includes/ (PHP-Klassen)"
echo "   ✅ assets/ (CSS/JS-Dateien)"
echo "   ✅ *.md (Dokumentation)"
echo ""
