# 🇨🇭 ReTexify AI Pro - Universal SEO Optimizer

**Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen**

[![Version](https://img.shields.io/badge/version-4.2.2-blue.svg)](https://github.com/your-username/retexify-ai)
[![WordPress](https://img.shields.io/badge/WordPress-5.0+-green.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2+-orange.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL%20v2-yellow.svg)](LICENSE)

## 🚀 Features

### 🤖 **Multi-KI Integration**
- **OpenAI GPT-4o/GPT-4o Mini** - Schnell & kostengünstig
- **Anthropic Claude 3.5** - Ausgezeichnete Textqualität  
- **Google Gemini 1.5** - Innovative Technologie
- **Automatischer Provider-Wechsel** mit separater API-Key-Verwaltung

### 🎯 **Intelligente SEO-Generierung**
- **Meta-Titel** (max. 58 Zeichen)
- **Meta-Beschreibungen** (140-155 Zeichen)
- **Focus-Keywords** mit natürlicher Integration
- **Schweizer Kantone** für Local SEO
- **Premium-Ton** für professionelle Texte

### 🔍 **Intelligent Research Engine**
- **Google Suggest API** für Keyword-Ideen
- **Wikipedia/Wiktionary** für Kontext-Erweiterung
- **OpenStreetMap** für Schweizer Ortsdaten
- **Multi-API Keyword Research** mit Fallback-System

### 📊 **Dashboard & Analytics**
- **Modernes Dashboard** mit SEO-Statistiken
- **Content-Analyse** mit deutscher Textverarbeitung
- **System-Status** mit API-Tests
- **Export/Import** für CSV-Daten

### 🛠️ **Technische Features**
- **Modularisierte Architektur** für bessere Wartbarkeit
- **Responsive Design** für alle Geräte
- **Event-Delegation** für robuste JavaScript-Funktionen
- **Caching-System** für bessere Performance
- **Error-Handling** mit Debug-Logs
- **Multi-SEO-Plugin Support** (Yoast, RankMath, All in One SEO, SEOPress)
- **Backup & Versionierung** mit Git-Integration

## 📋 Voraussetzungen

- **WordPress** 5.0 oder höher
- **PHP** 7.2 oder höher
- **cURL** Extension aktiviert
- **JSON** Extension aktiviert
- **API-Schlüssel** für mindestens einen KI-Provider

## 🛠️ Installation

### 1. **Plugin herunterladen**
```bash
git clone https://github.com/your-username/retexify-ai.git
cd retexify-ai
```

### 2. **In WordPress installieren**
1. Plugin-Ordner in `/wp-content/plugins/` kopieren
2. Plugin in WordPress Admin aktivieren
3. Unter "ReTexify AI" → "KI-Einstellungen" konfigurieren

### 3. **API-Schlüssel konfigurieren**
- **OpenAI**: [platform.openai.com/api-keys](https://platform.openai.com/api-keys)
- **Anthropic**: [console.anthropic.com](https://console.anthropic.com/)
- **Google**: [makersuite.google.com/app/apikey](https://makersuite.google.com/app/apikey)

## 🎯 Verwendung

### **SEO-Texte generieren**
1. **Dashboard** → "SEO Optimizer" Tab
2. **SEO-Content laden** → Post/Page auswählen
3. **Einzelne Texte** oder **Alle Texte generieren**
4. **Ergebnisse speichern** in WordPress

### **System-Status prüfen**
1. **System** Tab öffnen
2. **System-Status** wird automatisch geladen
3. **API-Services testen** für Research-Engine

### **Export/Import**
1. **Export/Import** Tab öffnen
2. **CSV-Export** für bestehende Inhalte
3. **CSV-Import** für Bulk-Updates

## 🔧 Konfiguration

### **KI-Einstellungen**
```php
// Beispiel-Konfiguration
$settings = array(
    'api_provider' => 'openai', // openai, anthropic, gemini
    'api_key' => 'sk-...',
    'model' => 'gpt-4o-mini',
    'target_cantons' => array('ZH', 'BE', 'LU'),
    'business_context' => 'Ihr Business-Kontext',
    'optimization_focus' => 'local_seo'
);
```

### **Schweizer Kantone**
```php
// Verfügbare Kantone
$cantons = array(
    'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden',
    'AR' => 'Appenzell Ausserrhoden', 'BE' => 'Bern',
    'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
    'FR' => 'Freiburg', 'GE' => 'Genf',
    'GL' => 'Glarus', 'GR' => 'Graubünden',
    'JU' => 'Jura', 'LU' => 'Luzern',
    'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
    'OW' => 'Obwalden', 'SG' => 'St. Gallen',
    'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
    'SZ' => 'Schwyz', 'TG' => 'Thurgau',
    'TI' => 'Tessin', 'UR' => 'Uri',
    'VD' => 'Waadt', 'VS' => 'Wallis',
    'ZG' => 'Zug', 'ZH' => 'Zürich'
);
```

## 🏗️ Projektstruktur

```
retexify_ai/
├── assets/                        # Frontend Assets
│   ├── admin-style.css           # Haupt-Styles
│   ├── admin_styles_extended.css # Erweiterte Styles
│   ├── admin-script.js           # Haupt-JavaScript
│   ├── export_import.js          # Export/Import JS
│   ├── intelligent-progress.js   # Progress-Tracking
│   ├── system-status-fixes.css   # System-Status CSS
│   ├── dashboard-manager.js      # Dashboard-Manager
│   ├── tab-manager.js            # Tab-System-Manager
│   ├── system-status-manager.js  # System-Status-Manager
│   └── provider-manager.js       # Provider-Manager
├── includes/                      # PHP-Klassen
│   ├── class-ai-engine.php       # KI-Engine
│   ├── class-api-manager.php     # API-Manager
│   ├── class-export-import-manager.php # Export/Import
│   ├── class-german-content-analyzer.php # Content-Analyse
│   ├── class-intelligent-keyword-research.php # Research
│   ├── class-retexify-config.php # Konfiguration
│   ├── class-system-status.php   # System-Status
│   ├── class-seo-generator.php   # SEO-Generator
│   ├── class-seo-handler.php     # SEO-Handler
│   ├── class-api-tester.php      # API-Tester
│   ├── class-meta-manager.php    # Meta-Manager
│   └── class-admin-renderer.php  # Admin-Renderer
├── backup/                       # Backup-Verzeichnis
├── backup_new/                   # Neues Backup (v4.2.2)
├── retexify.php                  # Haupt-Plugin-Datei
├── retexify_ai_v4.2.1.zip       # Plugin-Backup
├── LICENSE                       # GPL v2 Lizenz
├── README.md                     # Diese Datei
├── PROJECT_STRUCTURE.md          # Projektstruktur
└── .gitignore                    # Git-Ignore
```

## 🔍 API-Integration

### **Unterstützte APIs**
- **Google Suggest** - Keyword-Ideen
- **Wikipedia** - Kontext-Erweiterung
- **Wiktionary** - Synonyme & Definitionen
- **OpenStreetMap** - Schweizer Ortsdaten

### **Rate-Limiting**
- **Respektvolle API-Nutzung** mit automatischem Rate-Limiting
- **Caching-System** für bessere Performance
- **Fallback-Mechanismen** bei API-Ausfällen

## 🐛 Debugging

### **Debug-Logs aktivieren**
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### **Browser-Konsole**
```javascript
// AJAX-Test
console.log('AJAX Test:', retexify_ajax);

// Direkter API-Test
jQuery.post(retexify_ajax.ajax_url, {
    action: 'retexify_generate_complete_seo',
    nonce: retexify_ajax.nonce,
    post_id: 1
}, function(response) {
    console.log('Response:', response);
});
```

## 📈 Performance

### **Optimierungen**
- **Modularisierte Architektur** für bessere Wartbarkeit
- **In-Memory Caching** für API-Antworten
- **Event-Delegation** für JavaScript
- **Lazy Loading** für große Datenmengen
- **Parallel Processing** für SEO-Generierung

### **Benchmarks**
- **SEO-Generierung**: 15-45 Sekunden (abhängig vom Modell)
- **API-Response**: 2-8 Sekunden (mit Caching)
- **Dashboard-Load**: < 1 Sekunde

## 🔄 Modularisierung

### **JavaScript-Module**
- **Dashboard-Manager**: Verwaltung des Haupt-Dashboards
- **Tab-Manager**: Tab-System und Navigation
- **System-Status-Manager**: System-Diagnose und Tests
- **Provider-Manager**: KI-Provider-Verwaltung

### **PHP-Klassen**
- **SEO-Handler**: Zentrale SEO-Funktionalität
- **API-Tester**: API-Tests und Verbindungsprüfung
- **Meta-Manager**: Meta-Daten-Verwaltung
- **Admin-Renderer**: Admin-Interface-Rendering

## 🤝 Beitragen

### **Entwicklung**
1. Repository forken
2. Feature-Branch erstellen (`git checkout -b feature/amazing-feature`)
3. Änderungen committen (`git commit -m 'Add amazing feature'`)
4. Branch pushen (`git push origin feature/amazing-feature`)
5. Pull Request erstellen

### **Bug Reports**
- **Issue erstellen** mit detaillierter Beschreibung
- **Screenshots** beifügen (falls relevant)
- **Debug-Logs** teilen (falls verfügbar)

## 📄 Lizenz

Dieses Projekt steht unter der **GPL v2** Lizenz. Siehe [LICENSE](LICENSE) für Details.

## 🙏 Danksagungen

- **OpenAI** für GPT-Modelle
- **Anthropic** für Claude-Modelle
- **Google** für Gemini-Modelle
- **WordPress Community** für das großartige CMS
- **Schweizer SEO-Community** für Feedback und Testing

## 📞 Support

- **GitHub Issues**: [Issues erstellen](https://github.com/your-username/retexify-ai/issues)
- **Email**: support@imponi.ch
- **Website**: [imponi.ch](https://imponi.ch)

---

**Entwickelt mit ❤️ in der Schweiz für die Schweizer SEO-Community**

*ReTexify AI Pro - Universal SEO Optimizer v4.2.2*