# 🇨🇭 ReTexify AI Pro - Universal SEO Optimizer

**Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen. Multi-KI-Support (OpenAI, Claude, Gemini), Schweizer Qualität.**

---

## ✨ Hauptfunktionen

- **🤖 Multi-KI-Support:** OpenAI (GPT-4o, GPT-4o Mini, o1), Anthropic (Claude 3.5 Sonnet, Haiku, Opus), Google (Gemini Pro, Flash)
- **🚀 Intelligenter SEO-Optimizer:** Analysieren und optimieren Sie Ihre Beiträge und Seiten mit einem Klick
- **📤 Export/Import-System:** CSV-Export und -Import für SEO-Daten mit erweiterten Sicherheitsfeatures
- **🎯 Automatische SEO-Generierung:** Meta-Titel, Meta-Beschreibungen und Fokus-Keywords basierend auf Ihrem Content
- **🇨🇭 Schweizer Local SEO:** Speziell optimiert für alle 26 Schweizer Kantone
- **🔧 Flexible Optimierungs-Ziele:** Conversion, E-Commerce, B2B, Local SEO und mehr
- **📊 Content-Dashboard:** Übersichtlicher SEO-Score und Content-Analyse
- **🔒 Plugin-Kompatibilität:** Unterstützt Yoast SEO, Rank Math, All in One SEO, SEOPress

---

## 🔧 Installation

1. **Plugin-Dateien hochladen:**
   ```bash
   # ZIP-Datei in WordPress hochladen oder per FTP
   wp-content/plugins/retexify_ai/
   ```

2. **Plugin aktivieren:**
   - WordPress-Admin → Plugins → ReTexify AI aktivieren

3. **KI-Provider konfigurieren:**
   - ReTexify AI → KI-Einstellungen
   - Provider wählen (OpenAI, Claude oder Gemini)
   - API-Schlüssel eingeben
   - Business-Kontext anpassen

---

## 🚀 Schnellstart

### 1. KI-Setup (2 Minuten)
```
1. Wählen Sie Ihren bevorzugten KI-Provider
2. API-Schlüssel eintragen (siehe Anbieter-Website)
3. Business-Kontext und Zielgruppe definieren
4. Schweizer Kantone auswählen
5. Verbindung testen ✅
```

### 2. SEO-Optimierung (1 Klick)
```
1. SEO-Optimizer öffnen
2. Content-Typ wählen (Posts/Seiten)
3. Seite auswählen
4. "Alle SEO-Texte generieren" klicken
5. Ergebnisse prüfen und speichern
```

---

## 📋 Systemanforderungen

- **WordPress:** 5.0 oder höher
- **PHP:** 7.4 oder höher
- **RAM:** Mindestens 128MB
- **KI-Provider:** API-Schlüssel für OpenAI, Claude oder Gemini

### Unterstützte SEO-Plugins
- ✅ Yoast SEO
- ✅ Rank Math
- ✅ All in One SEO Pack
- ✅ SEOPress

---

## 🔑 API-Schlüssel erhalten

### OpenAI (Empfohlen für Einsteiger)
- **Website:** [platform.openai.com](https://platform.openai.com/api-keys)
- **Format:** `sk-proj-...` oder `sk-...`
- **Kosten:** Ab $0.15/1K Tokens (sehr günstig)
- **Empfehlung:** GPT-4o Mini für optimales Preis-Leistungs-Verhältnis

### Anthropic Claude (Premium-Qualität)
- **Website:** [console.anthropic.com](https://console.anthropic.com/)
- **Format:** `sk-ant-api03-...`
- **Kosten:** Ab $3.00/1K Tokens
- **Empfehlung:** Claude 3.5 Sonnet für beste Textqualität

### Google Gemini (Ultra-günstig)
- **Website:** [makersuite.google.com](https://makersuite.google.com/app/apikey)
- **Format:** `AIzaSy...`
- **Kosten:** Ab $0.075/1K Tokens (günstigster Anbieter)
- **Empfehlung:** Gemini 1.5 Flash für maximale Kosteneffizienz

---

## 📤 Export/Import Features

### CSV-Export
- **Unterstützte Daten:** Meta-Titel, Meta-Beschreibungen, Alt-Texte, WPBakery-Daten
- **Sicherheit:** Zeitlich begrenzte Download-Links (24h)
- **Format:** Excel-kompatibles CSV mit UTF-8 BOM

### CSV-Import
- **Validierung:** Automatische Dateityp- und Inhalts-Validierung
- **Sicherheit:** Sichere Upload-Verzeichnisse mit .htaccess-Schutz
- **Mapping:** Intelligente Spalten-Zuordnung für SEO-Daten

---

## 🇨🇭 Schweizer Kantone (Local SEO)

Das Plugin unterstützt alle 26 Schweizer Kantone für optimales Local SEO:

**Deutschschweiz:** AG, AI, AR, BE, BL, BS, GL, GR, LU, NW, OW, SG, SH, SO, SZ, TG, UR, ZG, ZH

**Romandie:** FR, GE, JU, NE, VD, VS

**Tessin:** TI

---

## 🎯 Optimierungsfokus-Optionen

- **Vollständige SEO-Optimierung:** Maximale Suchmaschinen-Sichtbarkeit
- **Schweizer Local SEO:** Regional optimiert mit Kantonen-Fokus
- **Conversion-optimiert:** Höhere Klickraten und Verkäufe
- **E-Commerce:** Speziell für Online-Shops
- **B2B:** Professional Services und Geschäftskunden
- **Lesbarkeit:** Verständlich für breitere Zielgruppe
- **Markenaufbau:** Vertrauen und Markenwahrnehmung
- **News & Blog:** Aktuelle Themen und Engagement

---

## 🔐 Sicherheitsfeatures

### Version 3.5.9 Verbesserungen
- ✅ **Sichere Datei-Uploads:** Erweiterte Validierung und Berechtigungen
- ✅ **Zeitlich begrenzte Downloads:** 24h-Gültigkeit für Export-Links
- ✅ **Path Traversal Protection:** Schutz vor Directory-Traversal-Angriffen
- ✅ **Automatische Bereinigung:** Alte Dateien werden automatisch gelöscht
- ✅ **Enhanced .htaccess:** Verbesserter Verzeichnisschutz
- ✅ **Audit-Logging:** Protokollierung wichtiger Aktionen

---

## 📊 Performance & Caching

- **JavaScript-Optimierung:** Modulare Struktur für bessere Ladezeiten
- **Datenbank-Optimierung:** Effiziente SQL-Queries mit prepared statements
- **Memory Management:** Optimierter Speicher-Verbrauch
- **AJAX-Optimierung:** Intelligente Request-Batching

---

## 🛠️ Entwicklung & Beitrag

### Projektstruktur
```
retexify_ai/
├── assets/                     # Frontend-Assets (CSS, JS)
├── includes/                   # PHP-Klassen
│   ├── class-ai-engine.php    # Multi-KI-Engine
│   ├── class-german-content-analyzer.php
│   ├── class-export-import-manager.php
│   └── class-retexify-config.php  # Zentrale Konfiguration
├── retexify.php               # Haupt-Plugin-Datei
└── README.md                  # Diese Datei
```

### Code-Standards
- **PSR-12** Coding Standards
- **WordPress Coding Standards**
- **Vollständige Nonce-Verifizierung**
- **Sanitization** aller Inputs
- **PHPDoc** Dokumentation

---

## 📞 Support & Updates

### Changelog Version 3.5.9
- ✅ Verbesserte Upload-Sicherheit
- ✅ Zeitlich begrenzte Download-Links
- ✅ Zentrale Konfigurationsklasse
- ✅ Enhanced .gitignore
- ✅ Performance-Optimierungen

### Geplante Features
- 🔄 JavaScript-Modularisierung
- 🔄 Rate Limiting für API-Calls
- 🔄 Automatische Backups
- 🔄 Multi-Language Support

---

## 📝 Lizenz

**GPL v2 oder später**

---

## 👨‍💻 Autor

**Imponi** - WordPress-Entwicklung & KI-Integration

---

*ReTexify AI Pro - Schweizer Qualität für universelle SEO-Optimierung* 🇨🇭