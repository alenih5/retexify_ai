# ReTexify AI - Universal SEO Optimizer

[![Version](https://img.shields.io/badge/version-4.25.1-blue.svg)](https://github.com/alenih5/retexify_ai)

Ein universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen. Optimiert Meta-Tags, Titel und Beschreibungen automatisch mit Hilfe von künstlicher Intelligenz.

## 🆕 Neue Features in Version 4.25.1

### ✅ **Medien-SEO Optimizer**
- Bilder filtern (alle, ohne Alt-Text, mit Alt-Text, nach Format)
- KI-gestützte Alt-Text-Generierung mit Seitenkontext-Analyse
- Website-weite Bulk-Optimierung aller Bilder ohne Alt-Text
- CSV-Export aller Bild-Metadaten
- Erweiterte Kontext-Analyse (Yoast/RankMath Keywords, Headings, Kategorien)
- Dateinamen-Analyse für intelligentere SEO-Texte

### ✅ **Bugfixes & Stabilität**
- JavaScript Scope-Fix: displayCurrentSeoItem ReferenceError behoben
- jQuery noConflict-Kompatibilität für globale Funktionen
- ob_start/ob_end_clean in allen AJAX-Handlern gegen Output-Störungen
- Diagnose-Endpunkt für Server-seitige Problemanalyse
- Robuste Fehlerbehandlung mit catch(Throwable) in allen PHP-Handlern

### ✅ **Erweiterte Klassen**
- **Advanced Content Analyzer** für tiefgreifende Inhaltsanalyse
- **Performance Optimizer** mit Singleton-Pattern und erweiterten Caching-Funktionen
- **Erweiterte Keyword-Strategy** mit LSI-Keywords und Cluster-Analyse

### ✅ **Performance-Verbesserungen**
- Optimiertes Caching-System
- Verbesserte AJAX-Performance
- Datenbank-Query-Optimierung

## 📋 Hauptfunktionen

- **Multi-KI-Integration** - Unterstützung für OpenAI, Anthropic und Google Gemini
- **Schweizer Kantone-Support** - Alle 26 Kantone ausgeschrieben
- **Advanced SEO Enhancement** - Umfassende Content-Analyse und Keyword-Research
- **Export/Import-System** - CSV-Backup und -Wiederherstellung
- **System-Diagnostik** - API-Tests und Status-Überwachung
- **Bulk-Generierung** - SEO-Texte für mehrere Posts gleichzeitig
- **Intelligente Textlängen-Optimierung** - 65 Zeichen für Titel, 165 für Beschreibungen

## 🔧 Technische Details

- **WordPress:** 5.0+
- **PHP:** 7.2+
- **Version:** 4.23.0
- **Lizenz:** GPLv2 or later

## 📝 Installation

1. Lade das Plugin als ZIP-Datei herunter
2. Gehe zu WordPress Admin → Plugins → Installieren
3. Aktiviere das Plugin
4. Konfiguriere deine API-Keys in den Einstellungen

## 🚀 Verwendung

Nach der Aktivierung findest du ReTexify AI im WordPress Admin-Menü. Dort kannst du:
- SEO-Texte für einzelne Posts generieren
- Bulk-Generierung für mehrere Posts durchführen
- Einstellungen für verschiedene KI-Provider konfigurieren
- System-Status und API-Verbindungen überprüfen

## 📚 Dokumentation

Weitere Informationen findest du im [CHANGELOG.md](CHANGELOG.md).

## 👤 Autor

**Imponi** - https://imponi.ch/

## 📄 Lizenz

Dieses Plugin steht unter der GPLv2 oder späteren Version.
