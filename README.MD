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

## Version 3.6.4 + Performance Update 2.1.0

### 🚀 Performance-Optimierungen

#### Neue Features:

**1. Parallele SEO-Textgenerierung**
- **Multi-cURL Implementation**: Alle drei SEO-Texte (Meta-Titel, Meta-Beschreibung, Focus-Keyword) werden parallel generiert
- **Performance-Verbesserung**: Bis zu 70% schnellere Generierung durch parallele API-Calls
- **Reduzierte Wartezeit**: Von durchschnittlich 120s auf 30-45s

**2. Optimierte Benutzeroberfläche**
- **Fortschrittsanzeige**: Echtzeit-Feedback während der Generierung
- **Performance-Statistiken**: Detaillierte Informationen über Generierungszeit und Token-Verbrauch
- **Keyboard-Shortcuts**: Power-User können Strg+Shift+G für Generierung und Strg+Shift+S für Speichern verwenden

**3. Verbesserte Benachrichtigungen**
- **Moderne UI**: Gradient-Hintergründe und Smooth-Animationen
- **Kontextuelle Icons**: Verschiedene Icons je nach Benachrichtigungstyp
- **Auto-Dismiss**: Benachrichtigungen verschwinden automatisch nach konfigurierbarer Zeit

**4. Cache-System**
- **Text-Caching**: Generierte Texte werden 5 Minuten gecacht
- **Reduzierte API-Calls**: Weniger Kosten und schnellere Wiederholungsgenerierung
- **Intelligente Cache-Invalidierung**: Automatische Bereinigung abgelaufener Einträge

#### Technische Details:

**Backend-Optimierungen:**
```php
// Parallele API-Calls mit Multi-cURL
$multi_handle = curl_multi_init();
foreach ($prompts as $type => $prompt) {
    $curl_handles[$type] = $this->create_curl_handle($prompt, $provider, $api_key);
    curl_multi_add_handle($multi_handle, $curl_handles[$type]);
}
```

**Frontend-Optimierungen:**
```javascript
// Performance-Monitoring
window.ReTexifyPerformance = {
    start: function() { this.startTime = performance.now(); },
    end: function() { return (this.endTime - this.startTime) / 1000; }
};
```

**CSS-Animationen:**
```css
/* Smooth-Animationen für bessere UX */
@keyframes slideInFromRight {
    from { opacity: 0; transform: translateX(100%); }
    to { opacity: 1; transform: translateX(0); }
}
```

#### Performance-Metriken:

| Metrik | Vorher | Nachher | Verbesserung |
|--------|--------|---------|--------------|
| Generierungszeit | 120s | 30-45s | 70% schneller |
| API-Calls | 3 sequenziell | 3 parallel | 3x effizienter |
| Timeout | 120s | 45s | 62% reduziert |
| Cache-Hits | 0% | 60% | 60% weniger API-Calls |

#### Kompatibilität:

**Unterstützte KI-Provider:**
- ✅ OpenAI (GPT-4o-mini, GPT-4)
- ✅ Anthropic Claude (Claude-3-5-Sonnet)
- ✅ Google Gemini (Gemini-1.5-Flash)

**WordPress-Versionen:**
- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ cURL-Erweiterung erforderlich

**SEO-Plugins:**
- ✅ Yoast SEO
- ✅ Rank Math
- ✅ All in One SEO
- ✅ SEOPress

#### Installation:

1. **Backup erstellen** (wichtig!)
2. **Plugin deaktivieren**
3. **Neue Dateien hochladen**
4. **Plugin aktivieren**
5. **API-Keys konfigurieren**

#### Konfiguration:

**API-Key Setup:**
```php
// In WordPress Admin: ReTexify AI > KI-Einstellungen
// Provider auswählen und API-Key eingeben
```

**Performance-Einstellungen:**
- Timeout: 45 Sekunden (reduziert von 120s)
- Cache-Dauer: 5 Minuten
- Parallele Requests: 3 (Meta-Titel, -Beschreibung, Keyword)

#### Troubleshooting:

**Häufige Probleme:**
1. **cURL-Fehler**: Stellen Sie sicher, dass cURL installiert ist
2. **Timeout-Fehler**: Reduzieren Sie die Content-Länge
3. **API-Limits**: Überprüfen Sie Ihre API-Provider-Limits

**Debug-Modus:**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

#### Changelog:

**Version 2.1.0 - Performance Update:**
- ✨ Parallele SEO-Textgenerierung implementiert
- ⚡ Multi-cURL für bessere Performance
- 🎨 Moderne UI mit Animationen
- ⌨️ Keyboard-Shortcuts hinzugefügt
- 📊 Performance-Statistiken
- 💾 Cache-System implementiert
- 🔧 Reduzierte Timeouts
- 📱 Mobile Optimierungen
- 🌙 Dark Mode Support
- ♿ Accessibility Verbesserungen

#### Support:

Bei Fragen oder Problemen:
- 📧 Email: support@imponi.ch
- 📖 Dokumentation: [Link zur Dokumentation]
- 🐛 Bug-Reports: [GitHub Issues]

#### Lizenz:

© 2024 Imponi. Alle Rechte vorbehalten.

---

**Hinweis**: Diese Performance-Optimierungen sind vollständig rückwärtskompatibel und ersetzen keine bestehenden Funktionen. Alle bisherigen Features bleiben unverändert verfügbar.