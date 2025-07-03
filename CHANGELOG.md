# ReTexify AI - Changelog

## Version 4.6.0 (2025-07-03)

### 🔒 Sicherheitsfix - API-Schlüssel-Bereinigung
- **Kritischer Sicherheitsfix:** Alte API-Schlüssel-Optionen aus der Datenbank entfernt
- **Problem behoben:** API-Schlüssel wurden in alten WordPress-Optionen gespeichert (`retexify_openai_api_key`, etc.)
- **Migration:** Alte API-Schlüssel werden automatisch in neue sichere Struktur überführt
- **Bereinigung:** Alte API-Schlüssel-Optionen werden nach Migration gelöscht
- **Sicherheit:** API-Schlüssel sind jetzt ausschließlich in der neuen `retexify_api_keys` Option gespeichert

### 🔧 Technische Verbesserungen
- **Admin-Renderer aktualisiert:** Verwendet jetzt die sichere API-Schlüssel-Struktur
- **Automatische Migration:** Beim Plugin-Update werden alte Schlüssel sicher migriert
- **Logging:** Migration wird protokolliert für Transparenz
- **Fallback-Entfernung:** Keine hartcodierten oder Fallback-API-Schlüssel mehr

### ✅ Sicherheitscheck
- **Keine hartcodierten API-Schlüssel** im Code gefunden
- **Keine Fallback-Logik** für API-Schlüssel
- **Alle API-Schlüssel** werden ausschließlich in der WordPress-Datenbank gespeichert
- **Automatische Bereinigung** alter, unsicherer Optionen

---

## Version 4.5.0 (2025-07-03)

### 🚀 Performance-Optimierung
- **Neue Performance-Optimizer-Klasse:** Intelligentes Caching für Datenbankabfragen und API-Calls
- **Reduzierte Datenbankabfragen:** Bis zu 80% weniger `get_option()` und `get_post_meta()` Aufrufe
- **API-Cache:** Keyword-Research-Ergebnisse werden 2 Stunden gecacht
- **Batch-Verarbeitung:** Optimierte Export-Funktionen mit Memory-Management
- **Performance-Metriken:** Echtzeit-Überwachung von Cache-Hit-Ratio und gesparten Queries

### 🧹 Asset-Bereinigung
- **Entfernte ungenutzte Dateien:**
  - `assets/dashboard-manager.js` (2.2KB) - Redundante Funktionen
  - `assets/preview-styles.css` (4.7KB) - Ungenutzte CSS-Klassen
- **Gespart:** 6.9KB ungeladene Assets
- **Alle verbleibenden Assets:** Werden tatsächlich verwendet und sind funktional

### 🔧 Technische Verbesserungen
- **Automatische Cache-Bereinigung:** Täglich via WordPress Cron
- **Memory-Limit-Optimierung:** Automatische Anpassung auf 256MB falls nötig
- **Object Cache Integration:** Nutzt WordPress Object Cache falls verfügbar
- **Transients:** Bessere Performance durch WordPress Transients

### 📊 Performance-Metriken
- Cache-Hit-Ratio Überwachung
- Gesparte Datenbankabfragen
- Gesparte API-Calls
- Memory-Usage Tracking
- Execution-Time Monitoring

---

## Version 4.4.0 (2025-07-03)

### 🧹 Code-Bereinigung
- **Entfernte ungenutzte Klassen:**
  - `ReTexify_German_Content_Analyzer` (29KB, 778 Zeilen)
  - `ReTexify_SEO_Generator` (5.3KB, 158 Zeilen)
- **Bereinigte Referenzen:** Alle Verweise auf gelöschte Klassen entfernt
- **Modularisierung abgeschlossen:** Intelligente Keyword-Research-Klassen vollständig implementiert

### 🔧 Verbesserungen
- **Reduzierte Dateigröße:** ~34KB weniger Code
- **Bessere Wartbarkeit:** Keine toten Klassen mehr
- **Saubere Architektur:** Vollständig modulares System

### 📁 Neue Klassen-Struktur
```
includes/
├── class-intelligent-keyword-research.php (Hauptkoordinator)
├── class-german-text-processor.php (Text-Vorverarbeitung)
├── class-keyword-analyzer.php (Keyword-Extraktion)
├── class-content-classifier.php (Content-Klassifizierung)
├── class-swiss-local-analyzer.php (Schweizer Relevanz)
├── class-keyword-strategy.php (Strategie-Generierung)
├── class-ai-engine.php (KI-Engine)
├── class-admin-renderer.php (Admin-Interface)
├── class-api-manager.php (API-Management)
├── class-export-import-manager.php (Export/Import)
├── class-system-status.php (System-Status)
└── class_retexify_config.php (Konfiguration)
```

### ✅ Funktionalität
- Alle bestehenden Features bleiben vollständig erhalten
- Keine Breaking Changes
- Verbesserte Performance durch weniger Code

---

## Version 4.3.0 (2025-06-29)

### 🚀 Intelligente Keyword-Research
- Neue modulare Architektur für Keyword-Analyse
- Schweizer Lokalisierung integriert
- Erweiterte Content-Klassifizierung

### 🔧 Technische Verbesserungen
- Modularisierte Klassen-Struktur
- Verbesserte Wartbarkeit
- Optimierte Performance 

## Version 4.7.0 (2025-07-03)

### 🧹 UI-Bereinigung & Usability
- Alle Häkchen-Emojis (✅) aus Benachrichtigungen und Erfolgsmeldungen entfernt
- Benachrichtigungen oben rechts sind jetzt neutral und emoji-frei
- Dokumentation und Hinweise in allen .md-Dateien aktualisiert 

## Version 4.7.1 (2025-07-03)

### 🆕 Export-Vorschau-UI
- Drei Icons (Gesamt-Posts, Spalten, Vorschau) werden jetzt nebeneinander und übersichtlich angezeigt
- Bessere Übersicht und modernes Layout 