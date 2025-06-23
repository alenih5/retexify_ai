# ReTexify AI - Projektstruktur

## 📁 Ordnerstruktur

```
retexify_ai/
├── .git/                          # Git Repository
├── assets/                        # Frontend Assets
│   ├── admin-style.css           # Haupt-CSS für Admin-Interface
│   ├── admin_styles_extended.css # Erweiterte CSS-Styles
│   ├── admin-script.js           # Haupt-JavaScript für Admin-Interface
│   └── export_import.js          # JavaScript für Export/Import-Funktionalität
├── includes/                      # PHP-Klassen und Includes
│   ├── class-ai-engine.php       # KI-Engine für verschiedene Provider
│   ├── class-german-content-analyzer.php # Content-Analyzer für deutsche Texte
│   └── class-export-import-manager.php # Export/Import-Manager
├── retexify.php                  # Haupt-Plugin-Datei
├── retexify-ai-pro-v3.5.7.zip   # Aktuelles Plugin-Backup
├── README.md                     # Projekt-Dokumentation
├── .gitignore                    # Git Ignore-Datei
└── PROJECT_STRUCTURE.md          # Diese Datei
```

## 📄 Dateien im Detail

### Haupt-Plugin-Datei
- **`retexify.php`** (94KB, 1809 Zeilen)
  - Haupt-Plugin-Datei mit WordPress-Integration
  - Enthält die Hauptklasse `ReTexify_AI_Pro_Universal`
  - Erweiterte Klasse `ReTexify_AI_Pro_Universal_Extended` für Export/Import
  - Admin-Interface und AJAX-Handler

### Assets (Frontend)
- **`assets/admin-style.css`** (35KB, 1872 Zeilen)
  - Haupt-CSS für das Admin-Interface
  - Responsive Design und moderne UI-Komponenten
  
- **`assets/admin_styles_extended.css`** (13KB, 705 Zeilen)
  - Erweiterte CSS-Styles für zusätzliche Funktionen
  
- **`assets/admin-script.js`** (68KB, 1742 Zeilen)
  - Haupt-JavaScript für Admin-Interface
  - Tab-Navigation, AJAX-Calls, UI-Interaktionen
  
- **`assets/export_import.js`** (23KB, 612 Zeilen)
  - JavaScript für Export/Import-Funktionalität
  - CSV-Upload, Export-Vorschau, System-Tests

### Includes (PHP-Klassen)
- **`includes/class-ai-engine.php`** (29KB, 776 Zeilen)
  - KI-Engine für verschiedene Provider (OpenAI, Claude, Gemini)
  - API-Integration und Modell-Management
  
- **`includes/class-german-content-analyzer.php`** (29KB, 778 Zeilen)
  - Content-Analyzer speziell für deutsche Texte
  - SEO-Score-Berechnung und Text-Optimierung
  
- **`includes/class-export-import-manager.php`** (36KB, 1045 Zeilen)
  - Export/Import-Manager für CSV-Funktionalität
  - WPBakery-Integration und SEO-Daten-Export

### Konfiguration & Backup
- **`retexify-ai-pro-v3.5.7.zip`** (61KB)
  - Aktuelles Plugin-Backup (Version 3.5.7)
  - Enthält alle Projektdateien für Installation
  
- **`README.md`** (2.5KB, 58 Zeilen)
  - Projekt-Dokumentation und Installationsanleitung
  
- **`.gitignore`** (133B, 10 Zeilen)
  - Git Ignore-Konfiguration
  
- **`PROJECT_STRUCTURE.md`** (diese Datei)
  - Aktuelle Projektstruktur und Dateiübersicht

## 🔧 Technische Details

### Plugin-Version
- **Aktuelle Version:** 3.5.7
- **WordPress-Kompatibilität:** 5.0+
- **PHP-Version:** 7.2+
- **Autor:** Imponi

### Hauptfunktionen
- 🤖 Multi-KI-Support (OpenAI, Claude, Gemini)
- 🚀 SEO-Optimierung mit KI
- 📦 Export/Import-Funktionalität
- 🇨🇭 Schweizer Local SEO
- 🏗️ WPBakery-Integration
- 📊 Content-Analyse

### Dateigrößen
- **Gesamtgröße:** ~300KB (ohne .git)
- **Größte Datei:** `retexify.php` (94KB)
- **Backup-Größe:** 61KB

---

*Letzte Aktualisierung: Version 3.5.7 mit Export/Import-Funktionalität* 