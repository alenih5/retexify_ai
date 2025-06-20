# ReTexify – WordPress Text Export/Import Plugin

Ein WordPress-Plugin für den vollständigen Export und Import von Inhalten – optimiert für WPBakery/Salient, Yoast SEO und moderne Content-Workflows.

## Hauptfunktionen

- Export & Import von Beiträgen, Seiten und Custom Post Types
- **WPBakery/Salient-Support:** Extrahiert alle sichtbaren Texte, Meta-Titel & Meta-Content aus WPBakery-Blöcken
- Export von Yoast SEO Meta-Titel, Meta-Beschreibung, Focus Keyphrase
- Export aller Bilder mit Alt-Texten
- **CSV-Format:** Klare Trennung zwischen Original- und Neu-Texten (jede Änderung nachvollziehbar)
- Vorschau- und Debug-Tools im Backend
- System-Status- und Content-Dashboard
- Intelligente Import-Logik: Nur ausgefüllte (Neu)-Felder werden übernommen

## Installation

1. Plugin herunterladen
2. Im WordPress-Backend unter Plugins > Installieren > Hochladen
3. Plugin aktivieren

## Verwendung

1. Gehe zu Werkzeuge > ReTexify
2. Wähle Post-Typen, Status und Inhaltstypen (z. B. Content, Meta, WPBakery Text)
3. Starte den Export und lade die CSV herunter
4. Bearbeite die (Neu)-Spalten in der CSV (z. B. für Übersetzung, SEO, Redaktion)
5. Importiere die CSV – nur ausgefüllte Felder werden übernommen

## CSV-Format (Beispiel)

| ID | Typ | Titel | Content (Original) | Content (Neu) | WPBakery Text (Original) | WPBakery Text (Neu) | ... |
|----|-----|-------|-------------------|---------------|--------------------------|---------------------|-----|
| 123| page| ...   | ...               |               | ...                      |                     |     |

- **Content (Original):** Kompletter Seiteninhalt (inkl. Shortcodes)
- **WPBakery Text (Original):** Einzelne Textbausteine aus WPBakery-Blöcken
- **(Neu)-Spalten:** Hier neue Texte eintragen – nur diese werden importiert

## Systemanforderungen

- WordPress 5.0 oder höher
- PHP 7.2 oder höher
- MySQL 5.6 oder höher

## Unterstützte Plugins & Themes

- Yoast SEO
- WPBakery Page Builder
- Salient Theme

## Version

2.4.0

## Autor

Imponi

## Lizenz

GPL v2 oder später