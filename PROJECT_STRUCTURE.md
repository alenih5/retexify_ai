# 📁 ReTexify AI - Projektstruktur v4.3.0

## 🏗️ **Übersicht**
ReTexify AI ist ein universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen. Das Plugin bietet umfassende SEO-Optimierung, intelligente Keyword-Recherche und Export/Import-Funktionalitäten.

## 📂 **Hauptverzeichnis**
```
retexify_ai/
├── 📄 retexify.php                    # Haupt-Plugin-Datei (3.448 Zeilen)
├── 📄 README.md                       # Projekt-Dokumentation
├── 📄 PROJECT_STRUCTURE.md            # Diese Datei
├── 📄 LICENSE                         # GPL v2 Lizenz
├── 📄 .gitignore                      # Git-Ignore-Regeln
├── 📁 assets/                         # Frontend-Assets
├── 📁 includes/                       # PHP-Klassen
├── 📁 backup/                         # Backup-Versionen
└── 📁 backup_new/                     # Neue Backup-Versionen
```

## 🎨 **Assets-Verzeichnis** (`assets/`)
Frontend-Dateien für das Admin-Interface:

### 📄 **CSS-Dateien**
- `admin-style.css` (1.843 Zeilen) - Haupt-Styling für Admin-Interface
- `admin_styles_extended.css` (1.901 Zeilen) - Erweiterte Styles
- `system-status-fixes.css` (770 Zeilen) - System-Status Styling
- `preview-styles.css` (127 Zeilen) - Vorschau-Styling

### 📄 **JavaScript-Dateien**
- `admin-script.js` (1.807 Zeilen) - Haupt-JavaScript für Admin-Funktionen
- `export_import.js` (856 Zeilen) - Export/Import-Funktionalität
- `intelligent-progress.js` (614 Zeilen) - Intelligente Fortschrittsanzeige
- `dashboard-manager.js` (56 Zeilen) - Dashboard-Management

## 🔧 **Includes-Verzeichnis** (`includes/`)
PHP-Klassen für modulare Funktionalität:

### 📄 **Core-Klassen**
- `class-ai-engine.php` (776 Zeilen) - KI-Engine für API-Integration
- `class-german-content-analyzer.php` (778 Zeilen) - Deutscher Content-Analyzer
- `class-api-manager.php` (664 Zeilen) - API-Management
- `class_retexify_config.php` (430 Zeilen) - Plugin-Konfiguration

### 📄 **SEO-Klassen**
- `class-seo-generator.php` (158 Zeilen) - SEO-Generator
- `class-intelligent-keyword-research.php` (892 Zeilen) - Intelligente Keyword-Recherche

### 📄 **System-Klassen**
- `class-system-status.php` (305 Zeilen) - System-Status und Diagnostik
- `class-export-import-manager.php` (1.133 Zeilen) - Export/Import-Management

## 🚀 **Haupt-Plugin-Datei** (`retexify.php`)

### 📋 **Plugin-Header**
```php
Plugin Name: ReTexify AI - Universal SEO Optimizer
Version: 4.3.0
Author: Imponi
Text Domain: retexify_ai_pro
```

### 🔧 **Hauptklasse: ReTexify_AI_Pro_Universal**
- **Zeilen**: 3.448 insgesamt
- **Funktionen**: 50+ Methoden
- **AJAX-Handler**: 30+ registrierte Actions

### 📊 **Funktionsbereiche**

#### 🎯 **SEO-Optimierung**
- `handle_generate_meta_title()` - Meta-Titel generieren
- `handle_generate_meta_description()` - Meta-Beschreibung generieren
- `handle_generate_keywords()` - Keywords generieren
- `handle_generate_complete_seo()` - Komplette SEO-Optimierung
- `handle_save_seo_data()` - SEO-Daten speichern

#### 🤖 **KI-Integration**
- `handle_ai_test_connection()` - API-Verbindung testen
- `handle_save_api_key()` - API-Schlüssel speichern
- `handle_switch_provider()` - Provider wechseln
- `handle_test_all_providers()` - Alle Provider testen

#### 📊 **System & Diagnostik**
- `ajax_test_system()` - System-Tests
- `ajax_get_system_info()` - System-Informationen
- `ajax_check_requirements()` - Anforderungen prüfen
- `ajax_diagnostic_report()` - Diagnose-Bericht

#### 📤 **Export/Import**
- `handle_export_content_csv()` - CSV-Export
- `handle_import_csv_data()` - CSV-Import
- `handle_get_export_preview()` - Export-Vorschau
- `handle_download_export_file()` - Export-Datei herunterladen

#### 🔍 **Keyword-Recherche**
- `handle_keyword_research()` - Keyword-Recherche
- `handle_analyze_competition()` - Wettbewerbsanalyse
- `handle_get_suggestions()` - Vorschläge generieren

## 🔌 **AJAX-Handler Übersicht**

### 📊 **Dashboard & Stats**
- `retexify_get_stats` - Statistiken abrufen
- `retexify_refresh_stats` - Statistiken aktualisieren

### 🎯 **SEO-Optimierung**
- `retexify_load_content` - Content laden
- `retexify_generate_single_seo` - Einzelne SEO-Generierung
- `retexify_generate_meta_title` - Meta-Titel generieren
- `retexify_generate_meta_description` - Meta-Beschreibung generieren
- `retexify_generate_keywords` - Keywords generieren
- `retexify_generate_complete_seo` - Komplette SEO-Generierung
- `retexify_save_seo_data` - SEO-Daten speichern
- `retexify_get_page_content` - Seiteninhalt abrufen
- `retexify_analyze_content` - Content analysieren

### 🤖 **KI-Einstellungen**
- `retexify_save_settings` - Einstellungen speichern
- `retexify_test_api_connection` - API-Verbindung testen
- `retexify_switch_provider` - Provider wechseln
- `retexify_get_api_keys` - API-Schlüssel abrufen
- `retexify_save_api_key` - API-Schlüssel speichern
- `retexify_test_all_providers` - Alle Provider testen

### 🔍 **Keyword-Recherche**
- `retexify_keyword_research` - Keyword-Recherche
- `retexify_analyze_competition` - Wettbewerbsanalyse
- `retexify_get_suggestions` - Vorschläge abrufen
- `retexify_research_keywords` - Keywords recherchieren

### 🔧 **System & Diagnostik**
- `retexify_test_system` - System testen
- `retexify_test_research_apis` - Research-APIs testen
- `retexify_get_system_info` - System-Informationen
- `retexify_check_requirements` - Anforderungen prüfen
- `retexify_diagnostic_report` - Diagnose-Bericht

### 📤 **Export/Import**
- `retexify_export_data` - Daten exportieren
- `retexify_import_data` - Daten importieren
- `retexify_get_export_stats` - Export-Statistiken
- `retexify_get_export_preview` - Export-Vorschau
- `retexify_export_content_csv` - CSV-Export
- `retexify_import_csv_data` - CSV-Import
- `retexify_get_import_preview` - Import-Vorschau
- `retexify_save_imported_data` - Importierte Daten speichern
- `retexify_delete_upload` - Upload löschen
- `retexify_download_export_file` - Export-Datei herunterladen

### 📝 **Content-Management**
- `retexify_bulk_optimize` - Bulk-Optimierung
- `retexify_schedule_optimization` - Optimierung planen
- `retexify_get_optimization_queue` - Optimierungs-Queue

## 🎨 **Admin-Interface**

### 📊 **Dashboard**
- Statistiken und Übersicht
- System-Status
- Schnellzugriff auf Funktionen

### 🎯 **SEO-Optimizer**
- Meta-Titel Generator
- Meta-Beschreibung Generator
- Keyword Generator
- Komplette SEO-Optimierung

### 🤖 **KI-Einstellungen**
- API-Schlüssel Verwaltung
- Provider-Auswahl
- Verbindungstests
- Einstellungen

### 🔍 **Keyword-Recherche**
- Intelligente Keyword-Suche
- Wettbewerbsanalyse
- Vorschläge und Trends

### 📤 **Export/Import**
- CSV-Export von Inhalten
- CSV-Import mit Vorschau
- Datenverwaltung

### 🔧 **System-Status**
- System-Diagnostik
- API-Tests
- Anforderungsprüfung
- Fehlerprotokollierung

## 🔧 **Technische Details**

### 📊 **Dateigrößen**
- **Gesamt**: ~159 KB (retexify.php)
- **Assets**: ~8 CSS/JS Dateien
- **Includes**: 8 PHP-Klassen
- **Zeilen**: 3.448 (Hauptdatei)

### 🔌 **WordPress-Integration**
- **Hooks**: admin_menu, admin_enqueue_scripts, wp_ajax_*
- **Capabilities**: manage_options
- **Text Domain**: retexify_ai_pro
- **Version**: 4.3.0

### 🛡️ **Sicherheit**
- Nonce-Verifikation für alle AJAX-Calls
- Capability-Checks
- Input-Validierung
- Error-Logging

### 📈 **Performance**
- Lazy Loading von Komponenten
- Optimierte Asset-Einbindung
- Caching von API-Responses
- Intelligente Fortschrittsanzeige

## 🚀 **Entwicklung**

### 📋 **Nächste Versionen**
- v4.4.0: Erweiterte KI-Modelle
- v4.5.0: Bulk-Processing
- v5.0.0: Neue UI/UX

### 🔧 **Wartung**
- Regelmäßige API-Updates
- WordPress-Kompatibilität
- Sicherheitsupdates
- Performance-Optimierungen

---

**Letzte Aktualisierung**: 02.07.2025  
**Version**: 4.3.0  
**Entwickler**: Imponi 

# Projektstruktur: ReTexify AI (ab Version 4.4.0)

## Übersicht
- **Modular, wartbar, performant**
- Keine Altlasten mehr: `class-german-content-analyzer.php` und `class-seo-generator.php` entfernt
- Details zu Änderungen: siehe `CHANGELOG.md`

## includes/
- `class-intelligent-keyword-research.php` – Hauptkoordinator für Analyse & Strategie
- `class-german-text-processor.php` – Textvorverarbeitung (Stopwords, Sätze, Silben etc.)
- `class-keyword-analyzer.php` – Keyword-Extraktion, N-Grams, Powerwords
- `class-content-classifier.php` – Content-Typ, Suchintention, Lesbarkeit
- `class-swiss-local-analyzer.php` – Schweizer Relevanz-Analyse
- `class-keyword-strategy.php` – Strategie- und Prompt-Generator
- `class-ai-engine.php` – KI-Engine
- `class-admin-renderer.php` – Admin-Interface
- `class-api-manager.php` – API-Management
- `class-export-import-manager.php` – Export/Import
- `class-system-status.php` – System-Status
- `class_retexify_config.php` – Konfiguration

## assets/
- Admin- und Frontend-Skripte, CSS

## Hauptdateien
- `retexify.php` – Haupt-Plugin-Datei
- `README.md`, `CHANGELOG.md`, `LICENSE`

## ZIP-Archive
- `retexify_ai_v4.4.0_cleaned.zip` (bereinigte Version)
- `retexify_ai_v4.4.0_final.zip` (finale Version inkl. Changelog)

**Hinweis ab Version 4.7.0:**
- Alle Benachrichtigungen und Erfolgsmeldungen sind emoji-frei (kein ✅ mehr)

**Hinweis ab Version 4.7.1:**
- Export-Vorschau zeigt drei Icons (Gesamt-Posts, Spalten, Vorschau) nebeneinander 