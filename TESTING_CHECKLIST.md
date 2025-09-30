# 🧪 Testing-Checklist - ReTexify AI v4.2.0

## Vor WordPress-Aktivierung

- [x] Alle PHP-Dateien Syntax-OK (php -l)
- [x] Keine Git-Konflikte
- [x] Version auf 4.2.0 erhöht
- [x] CHANGELOG.md aktualisiert
- [x] 3 neue Sicherheits-Klassen erstellt
- [x] Alle Sicherheits-Fixes implementiert

## Nach WordPress-Aktivierung

### Plugin-Aktivierung
- [ ] Plugin lässt sich aktivieren (keine Fatal Errors)
- [ ] Admin-Menü "ReTexify AI" erscheint
- [ ] Einstellungsseite lädt ohne Fehler
- [ ] Keine PHP-Warnings in wp-content/debug.log

### API-Schlüssel (Sicherheit)
- [ ] API-Schlüssel für OpenAI speicherbar
- [ ] API-Schlüssel für Anthropic speicherbar
- [ ] API-Schlüssel für Gemini speicherbar
- [ ] Maskierte Anzeige funktioniert (nur erste/letzte Zeichen)
- [ ] Schlüssel werden verschlüsselt in Datenbank gespeichert
- [ ] Format-Validierung funktioniert (falsche Formate werden abgelehnt)
- [ ] Schlüssel abrufbar und funktionstüchtig

### SEO-Generierung
- [ ] Post/Page auswählbar
- [ ] "SEO-Texte generieren" funktioniert
- [ ] Titel wird generiert (max 58 Zeichen)
- [ ] Meta-Description wird generiert (140-155 Zeichen)
- [ ] Keywords werden generiert
- [ ] Kantone können ausgewählt werden
- [ ] Speichern funktioniert
- [ ] Intelligente Analyse funktioniert

### AJAX-Sicherheit
- [ ] Browser-Konsole zeigt keine Fehler
- [ ] Network-Tab zeigt "nonce" Parameter in jedem AJAX-Call
- [ ] Ohne Login: AJAX-Calls werden blockiert
- [ ] Ohne Berechtigung: AJAX-Calls werden blockiert
- [ ] Nonce-Validierung funktioniert
- [ ] Input-Sanitization funktioniert

### Rate-Limiting
- [ ] 10x schnell hintereinander "Generieren" klicken
- [ ] Ab einem Punkt: Warnung erscheint
- [ ] Nach 60 Sekunden: Funktioniert wieder
- [ ] Rate-Limit-Status wird angezeigt
- [ ] Statistiken werden gespeichert

### Caching
- [ ] Gleicher Prompt 2x: Zweites Mal schneller (Cache-Hit)
- [ ] Cache-Löschen-Button funktioniert
- [ ] Nach Cache-Löschen: Wieder langsamer (neuer API-Call)
- [ ] Cache-Keys werden korrekt generiert
- [ ] TTL von 1 Stunde funktioniert

### Error-Handling
- [ ] Falscher API-Key: Verständliche Fehlermeldung
- [ ] Kein API-Key: Warnung erscheint
- [ ] Netzwerk-Timeout: Fehler wird abgefangen
- [ ] Alle Provider offline: Fallback-Meldung
- [ ] HTTP-Status-Codes werden korrekt behandelt
- [ ] Retry-Logik funktioniert

### Logs
- [ ] wp-content/debug.log enthält ReTexify-Einträge
- [ ] Keine PHP-Warnings oder Notices
- [ ] API-Calls werden geloggt
- [ ] Rate-Limit-Events werden geloggt
- [ ] Fehler werden strukturiert geloggt
- [ ] Performance-Metriken werden geloggt

## Performance

- [ ] Admin-Seite lädt in < 2 Sekunden
- [ ] SEO-Generierung in 15-45 Sekunden (je nach Modell)
- [ ] Keine Memory-Leaks (WordPress läuft stabil)
- [ ] Datenbank-Queries optimiert (< 50 per Page-Load)
- [ ] Caching reduziert API-Calls deutlich
- [ ] Rate-Limiting verhindert Überlastung

## Kompatibilität

- [ ] Yoast SEO: Keine Konflikte
- [ ] RankMath: Keine Konflikte
- [ ] WPBakery: Integration funktioniert
- [ ] Andere Plugins: Keine JS-Fehler
- [ ] WordPress 6.4: Kompatibel
- [ ] PHP 7.2+: Funktioniert

## Sicherheit

- [ ] API-Schlüssel sind verschlüsselt in Datenbank
- [ ] Nonce-Validierung in allen AJAX-Calls
- [ ] SQL-Injection-Schutz aktiv
- [ ] Input-Sanitization funktioniert
- [ ] Rate-Limiting verhindert Brute-Force
- [ ] Fehlerbehandlung verhindert Information-Leakage

## Migration

- [ ] Alte API-Schlüssel werden automatisch migriert
- [ ] Alte unverschlüsselte Schlüssel werden gelöscht
- [ ] Einstellungen bleiben erhalten
- [ ] Keine Datenverluste
- [ ] Rückwärtskompatibilität gewährleistet

---

## Test-Szenarien

### Szenario 1: Normale Nutzung
1. Plugin aktivieren
2. API-Key eingeben
3. SEO für einen Post generieren
4. Ergebnisse speichern
5. **Erwartung:** Alles funktioniert reibungslos

### Szenario 2: Rate-Limiting
1. 15x schnell hintereinander "Generieren" klicken
2. **Erwartung:** Ab 10-12 Klicks wird blockiert
3. 60 Sekunden warten
4. **Erwartung:** Funktioniert wieder

### Szenario 3: Fehlerbehandlung
1. Falschen API-Key eingeben
2. SEO generieren versuchen
3. **Erwartung:** Klare Fehlermeldung
4. Korrekten API-Key eingeben
5. **Erwartung:** Funktioniert wieder

### Szenario 4: Caching
1. Gleichen Prompt 2x generieren
2. **Erwartung:** Zweites Mal deutlich schneller
3. Cache löschen
4. Gleichen Prompt nochmal generieren
5. **Erwartung:** Wieder langsamer (neuer API-Call)

### Szenario 5: Sicherheit
1. Ohne Login versuchen AJAX-Call zu machen
2. **Erwartung:** Wird blockiert
3. Mit falschem Nonce versuchen
4. **Erwartung:** Wird blockiert
5. Mit korrekten Daten versuchen
6. **Erwartung:** Funktioniert

---

**Status:** [ ] Alle Tests bestanden  
**Datum:** ___________
**Tester:** ___________
**WordPress-Version:** ___________
**PHP-Version:** ___________
**Browser:** ___________

---

## Bei Problemen

### Häufige Probleme und Lösungen:

1. **"Cannot redeclare class"**
   - Lösung: Plugin deaktivieren und wieder aktivieren
   - Prüfen ob alle Dateien korrekt geladen werden

2. **"Fatal error"**
   - Lösung: wp-content/debug.log prüfen
   - Syntax-Fehler in PHP-Dateien beheben

3. **"Nonce verification failed"**
   - Lösung: Browser-Cache leeren
   - Seite neu laden

4. **API-Calls funktionieren nicht**
   - Lösung: API-Key prüfen
   - Rate-Limiting-Status prüfen
   - Netzwerk-Verbindung testen

5. **Cache funktioniert nicht**
   - Lösung: WordPress-Transients prüfen
   - Cache manuell leeren

---

**Erstellt:** 30. Dezember 2024  
**Version:** 4.2.0 Testing Checklist  
**Plugin:** ReTexify AI Pro
