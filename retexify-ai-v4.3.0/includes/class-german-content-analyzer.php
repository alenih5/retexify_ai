<?php
/**
 * Deutsche Content-Analyse Klasse
 * 
 * Spezialisiert auf deutsche Texte und Schweizer Content
 * Universell für alle Branchen anpassbar
 * Version 3.5.0 - Performance-Optimierungen und erweiterte Analyse
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_German_Content_Analyzer {
    
    /**
     * Deutsche Stopwords für Keyword-Extraktion
     */
    private $german_stopwords = array(
        'der', 'die', 'das', 'und', 'oder', 'aber', 'ist', 'sind', 'war', 'waren', 
        'hat', 'haben', 'wird', 'werden', 'von', 'zu', 'für', 'mit', 'auf', 'in', 
        'an', 'bei', 'durch', 'über', 'unter', 'vor', 'nach', 'zwischen', 'alle',
        'eine', 'einem', 'einen', 'einer', 'eines', 'den', 'dem', 'des', 'im', 'am',
        'zur', 'zum', 'beim', 'vom', 'ins', 'ans', 'aus', 'als', 'wie', 'wenn',
        'dann', 'noch', 'nur', 'auch', 'schon', 'mehr', 'sehr', 'kann', 'muss',
        'soll', 'will', 'darf', 'mag', 'könnte', 'sollte', 'würde', 'hätte', 'dass',
        'sich', 'nicht', 'diese', 'dieser', 'dieses', 'hier', 'dort', 'damit', 'ohne',
        'etwa', 'gegen', 'während', 'wegen', 'trotz', 'seit', 'bis', 'um', 'bei'
    );
    
    /**
     * Universelle Business-Begriffe für alle Branchen
     */
    private $business_patterns = array(
        'service' => array(
            'service', 'dienstleistung', 'beratung', 'kundenservice', 'support', 
            'hilfe', 'unterstützung', 'consulting', 'betreuung'
        ),
        'qualität' => array(
            'qualität', 'professionell', 'erfahrung', 'kompetenz', 'zuverlässig', 
            'fachmann', 'experte', 'hochwertig', 'premium', 'spezialist', 'expertise'
        ),
        'lösung' => array(
            'lösung', 'lösungen', 'projekt', 'projekte', 'umsetzung', 'realisierung', 
            'entwicklung', 'strategie', 'konzept', 'system', 'ansatz'
        ),
        'kunden' => array(
            'kunde', 'kunden', 'kundschaft', 'zielgruppe', 'auftraggeber', 'mandant', 
            'interessent', 'käufer', 'verbraucher', 'nutzer', 'anwender'
        ),
        'unternehmen' => array(
            'unternehmen', 'firma', 'betrieb', 'geschäft', 'business', 'company', 
            'organisation', 'agentur', 'gesellschaft', 'konzern', 'startup'
        ),
        'innovation' => array(
            'innovation', 'innovativ', 'modern', 'zukunft', 'fortschritt', 'technologie', 
            'digital', 'neu', 'neuheit', 'trend', 'entwicklung'
        ),
        'erfolg' => array(
            'erfolg', 'erfolgreich', 'wachstum', 'führend', 'marktführer', 'gewinn', 
            'umsatz', 'profit', 'leistung', 'achievement', 'performance'
        ),
        'regional' => array(
            'schweiz', 'swiss', 'bern', 'zürich', 'basel', 'luzern', 'region', 'regional', 
            'lokal', 'kanton', 'kantonal', 'deutschland', 'österreich', 'dach'
        ),
        'verkauf' => array(
            'verkauf', 'verkaufen', 'verkäufer', 'vertrieb', 'sales', 'angebot', 
            'angebote', 'preis', 'kosten', 'tarif', 'deal', 'geschäft'
        ),
        'kommunikation' => array(
            'kommunikation', 'kontakt', 'beratung', 'gespräch', 'information', 
            'nachricht', 'austausch', 'dialog', 'feedback', 'response'
        )
    );
    
    /**
     * Schweizer Kantone für regionale Analyse
     */
    private $swiss_cantons = array(
        'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
        'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
        'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
        'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
        'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
        'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
        'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
    );
    
    /**
     * Schweizer Städte für regionale Erkennung (erweitert)
     */
    private $swiss_cities = array(
        'zürich', 'bern', 'basel', 'luzern', 'winterthur', 'st. gallen', 'biel', 
        'thun', 'köniz', 'la chaux-de-fonds', 'schaffhausen', 'freiburg', 'vernier',
        'chur', 'neuchâtel', 'uster', 'sion', 'lancy', 'kriens', 'yverdon-les-bains',
        'emmen', 'zug', 'dübendorf', 'dietikon', 'riehen', 'baar', 'frauenfeld',
        'wetzikon', 'rapperswil-jona', 'davos', 'interlaken', 'montreux', 'nyon'
    );
    
    /**
     * Cache für Performance-Optimierung
     */
    private $analysis_cache = array();
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Cache initialisieren
        $this->analysis_cache = array();
    }
    
    /**
     * Deutschen Text bereinigen und normalisieren (optimiert)
     * 
     * @param string $text Roher Text
     * @return string Bereinigter Text
     */
    public function clean_german_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // Cache-Key erstellen
        $cache_key = 'clean_' . md5($text);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        // HTML-Tags entfernen
        $text = wp_strip_all_tags($text);
        
        // HTML-Entitäten dekodieren
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // WordPress-spezifische Shortcodes entfernen
        $text = preg_replace('/\[.*?\]/', '', $text);
        
        // Mehrfache Leerzeichen normalisieren
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Sonderzeichen normalisieren
        $text = preg_replace('/[^\p{L}\p{N}\s\p{P}]/u', '', $text);
        
        $result = trim($text);
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $result;
        
        return $result;
    }
    
    /**
     * Deutsche Wörter korrekt zählen (optimiert)
     * 
     * @param string $text Text zum Analysieren
     * @return int Anzahl Wörter
     */
    public function count_german_words($text) {
        if (empty($text)) return 0;
        
        // Cache-Key erstellen
        $cache_key = 'words_' . md5($text);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        // Deutsche Wörter mit Umlauten und ß richtig zählen
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Nur echte Wörter zählen (mindestens 2 Zeichen, nur Buchstaben/Umlaute)
        $word_count = 0;
        foreach ($words as $word) {
            // Interpunktion entfernen
            $clean_word = preg_replace('/[^\p{L}\p{N}äöüßÄÖÜ]/u', '', $word);
            if (mb_strlen($clean_word, 'UTF-8') >= 2) {
                $word_count++;
            }
        }
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $word_count;
        
        return $word_count;
    }
    
    /**
     * Deutsche Keywords extrahieren (verbessert)
     * 
     * @param string $text Text zum Analysieren
     * @param int $limit Maximale Anzahl Keywords
     * @return array Top Keywords
     */
    public function extract_german_keywords($text, $limit = 10) {
        if (empty($text)) return array();
        
        // Cache-Key erstellen
        $cache_key = 'keywords_' . md5($text . $limit);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $text = mb_strtolower($text, 'UTF-8');
        
        // Deutsche Wörter richtig extrahieren
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $clean_words = array();
        
        foreach ($words as $word) {
            // Interpunktion entfernen, Umlaute beibehalten
            $clean_word = preg_replace('/[^\p{L}äöüßÄÖÜ]/u', '', $word);
            $clean_word = mb_strtolower($clean_word, 'UTF-8');
            
            // Nur Wörter mit mindestens 3 Zeichen
            if (mb_strlen($clean_word, 'UTF-8') >= 3) {
                $clean_words[] = $clean_word;
            }
        }
        
        $word_freq = array_count_values($clean_words);
        
        // Stopwords entfernen
        foreach ($this->german_stopwords as $stopword) {
            unset($word_freq[$stopword]);
        }
        
        // Nach Häufigkeit sortieren
        arsort($word_freq);
        
        // Top Keywords zurückgeben
        $result = array_slice(array_keys($word_freq), 0, $limit);
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $result;
        
        return $result;
    }
    
    /**
     * Business-Themen identifizieren (erweitert und optimiert)
     * 
     * @param string $text Text zum Analysieren
     * @return array Erkannte Business-Themen mit Scores
     */
    public function identify_german_business_themes($text) {
        if (empty($text)) return array();
        
        // Cache-Key erstellen
        $cache_key = 'themes_' . md5($text);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $text = mb_strtolower($text, 'UTF-8');
        $themes = array();
        
        foreach ($this->business_patterns as $theme => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                $count = substr_count($text, $keyword);
                $matches += $count;
            }
            if ($matches > 0) {
                $themes[$theme] = $matches;
            }
        }
        
        // Nach Score sortieren
        arsort($themes);
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $themes;
        
        return $themes;
    }
    
    /**
     * Deutsche Sätze zählen (verbessert)
     * 
     * @param string $text Text zum Analysieren
     * @return int Anzahl Sätze
     */
    public function count_german_sentences($text) {
        if (empty($text)) return 0;
        
        // Cache-Key erstellen
        $cache_key = 'sentences_' . md5($text);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        // Deutsche Sätze richtig zählen (., !, ?, ... berücksichtigen)
        $sentences = preg_split('/[.!?…]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Leere oder sehr kurze "Sätze" herausfiltern
        $valid_sentences = 0;
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 10) { // Mindestens 10 Zeichen für einen gültigen Satz
                $valid_sentences++;
            }
        }
        
        $result = max(1, $valid_sentences); // Mindestens 1 Satz
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $result;
        
        return $result;
    }
    
    /**
     * Content-Qualität für deutsche Texte bewerten (verbessert)
     * 
     * @param string $content Content zum Bewerten
     * @param int $word_count Wort-Anzahl
     * @return int Qualitäts-Score (0-100)
     */
    public function assess_german_content_quality($content, $word_count) {
        if (empty($content)) return 0;
        
        // Cache-Key erstellen
        $cache_key = 'quality_' . md5($content . $word_count);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $score = 0;
        
        // Wort-Anzahl bewerten (für deutsche Texte angepasst)
        if ($word_count >= 500) $score += 40;
        elseif ($word_count >= 300) $score += 35;
        elseif ($word_count >= 200) $score += 25;
        elseif ($word_count >= 100) $score += 15;
        elseif ($word_count >= 50) $score += 10;
        
        // Satz-Struktur bewerten
        $sentence_count = $this->count_german_sentences($content);
        if ($sentence_count > 0) {
            $avg_sentence_length = $word_count / $sentence_count;
            if ($avg_sentence_length >= 8 && $avg_sentence_length <= 20) $score += 25;
            elseif ($avg_sentence_length >= 6 && $avg_sentence_length <= 25) $score += 20;
            elseif ($avg_sentence_length >= 4) $score += 10;
        }
        
        // Absätze prüfen
        $paragraphs = array_filter(explode("\n", $content));
        if (count($paragraphs) >= 5) $score += 20;
        elseif (count($paragraphs) >= 3) $score += 15;
        elseif (count($paragraphs) >= 2) $score += 10;
        
        // Business-Relevanz bewerten (universell)
        $business_score = 0;
        $business_themes = $this->identify_german_business_themes($content);
        foreach ($business_themes as $theme => $count) {
            $business_score += min(5, $count); // Max 5 Punkte pro Thema
        }
        $score += min(15, $business_score);
        
        $result = min(100, $score);
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $result;
        
        return $result;
    }
    
    /**
     * Schweizer regionale Begriffe erkennen (erweitert)
     * 
     * @param string $text Text zum Analysieren
     * @return array Regionale Informationen
     */
    public function detect_swiss_regional_content($text) {
        if (empty($text)) return array();
        
        // Cache-Key erstellen
        $cache_key = 'regional_' . md5($text);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $text = mb_strtolower($text, 'UTF-8');
        $regional_info = array();
        
        // Schweizer Kantone erkennen
        $found_cantons = array();
        foreach ($this->swiss_cantons as $code => $name) {
            if (stripos($text, mb_strtolower($name, 'UTF-8')) !== false) {
                $found_cantons[] = $name;
            }
        }
        $regional_info['cantons'] = $found_cantons;
        
        // Schweizer Städte erkennen
        $found_cities = array();
        foreach ($this->swiss_cities as $city) {
            if (stripos($text, $city) !== false) {
                $found_cities[] = $city;
            }
        }
        $regional_info['cities'] = $found_cities;
        
        // Schweizer spezifische Begriffe (erweitert)
        $swiss_terms = array(
            'schweiz', 'swiss', 'helvetia', 'eidgenossenschaft', 'bundesrat', 
            'kantonal', 'gemeinde', 'swissness', 'schweizerisch', 'schweizerische',
            'dach-region', 'dach', 'alpen', 'bergregion', 'jura', 'mittelland'
        );
        $found_terms = array();
        
        foreach ($swiss_terms as $term) {
            if (stripos($text, $term) !== false) {
                $found_terms[] = $term;
            }
        }
        $regional_info['swiss_terms'] = $found_terms;
        
        // Regional-Score berechnen (verbessert)
        $regional_score = count($found_cantons) * 5 + count($found_cities) * 3 + count($found_terms) * 2;
        $regional_info['regional_score'] = $regional_score;
        $regional_info['is_swiss_focused'] = $regional_score >= 10;
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $regional_info;
        
        return $regional_info;
    }
    
    /**
     * Lesbarkeit für deutsche Texte berechnen (optimiert)
     * 
     * @param string $content Content zum Bewerten
     * @param int $word_count Wort-Anzahl
     * @return int Lesbarkeits-Score (0-100)
     */
    public function calculate_german_readability($content, $word_count) {
        if ($word_count === 0) return 0;
        
        // Cache-Key erstellen
        $cache_key = 'readability_' . md5($content . $word_count);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $sentence_count = $this->count_german_sentences($content);
        if ($sentence_count === 0) return 0;
        
        $avg_sentence_length = $word_count / $sentence_count;
        
        // Verbesserte Lesbarkeits-Heuristik für deutsche Texte
        $score = 100;
        
        // Satzlänge bewerten
        if ($avg_sentence_length > 30) $score -= 40;
        elseif ($avg_sentence_length > 25) $score -= 30;
        elseif ($avg_sentence_length > 20) $score -= 20;
        elseif ($avg_sentence_length > 15) $score -= 10;
        
        // Wortlänge bewerten (durchschnittliche Zeichen pro Wort)
        $avg_word_length = mb_strlen($content, 'UTF-8') / $word_count;
        if ($avg_word_length > 9) $score -= 25;
        elseif ($avg_word_length > 7) $score -= 15;
        elseif ($avg_word_length > 6) $score -= 10;
        
        // Komplexe Wörter (mehr als 3 Silben) abschätzen
        $complex_words = $this->estimate_complex_words($content);
        $complex_ratio = $complex_words / $word_count;
        if ($complex_ratio > 0.4) $score -= 25;
        elseif ($complex_ratio > 0.3) $score -= 20;
        elseif ($complex_ratio > 0.2) $score -= 10;
        
        // Interpunktion bewerten (gut strukturierte Texte)
        $punctuation_count = preg_match_all('/[.!?,:;]/', $content);
        $punctuation_ratio = $punctuation_count / $word_count;
        if ($punctuation_ratio > 0.05 && $punctuation_ratio < 0.15) $score += 10;
        
        $result = max(0, min(100, $score));
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $result;
        
        return $result;
    }
    
    /**
     * Komplexe Wörter schätzen (optimiert)
     * 
     * @param string $content Content zum Analysieren
     * @return int Geschätzte Anzahl komplexer Wörter
     */
    private function estimate_complex_words($content) {
        $words = preg_split('/\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $complex_count = 0;
        
        foreach ($words as $word) {
            $clean_word = preg_replace('/[^\p{L}äöüßÄÖÜ]/u', '', $word);
            
            // Vereinfachte Silbenzählung für deutsche Wörter
            $syllables = $this->count_syllables_german($clean_word);
            
            if ($syllables > 3) {
                $complex_count++;
            }
        }
        
        return $complex_count;
    }
    
    /**
     * Silben in deutschen Wörtern schätzen (verbessert)
     * 
     * @param string $word Wort zum Analysieren
     * @return int Geschätzte Silbenanzahl
     */
    private function count_syllables_german($word) {
        if (empty($word)) return 0;
        
        $word = mb_strtolower($word, 'UTF-8');
        
        // Deutsche Vokale (inkl. Umlaute)
        $vowels = 'aeiouäöüy';
        $syllable_count = 0;
        $previous_was_vowel = false;
        
        for ($i = 0; $i < mb_strlen($word, 'UTF-8'); $i++) {
            $char = mb_substr($word, $i, 1, 'UTF-8');
            $is_vowel = mb_strpos($vowels, $char, 0, 'UTF-8') !== false;
            
            if ($is_vowel && !$previous_was_vowel) {
                $syllable_count++;
            }
            
            $previous_was_vowel = $is_vowel;
        }
        
        // Deutsche Besonderheiten
        // Stummes 'e' am Ende
        if (mb_substr($word, -1, 1, 'UTF-8') === 'e' && $syllable_count > 1) {
            $syllable_count--;
        }
        
        // Mindestens eine Silbe pro Wort
        return max(1, $syllable_count);
    }
    
    /**
     * Vollständige Content-Analyse durchführen (optimiert)
     * 
     * @param string $content Content zum Analysieren
     * @param string $title Titel (optional)
     * @return array Vollständige Analyse-Ergebnisse
     */
    public function analyze_content($content, $title = '') {
        if (empty($content)) {
            return $this->get_empty_analysis();
        }
        
        // Cache-Key für vollständige Analyse
        $cache_key = 'full_analysis_' . md5($content . $title);
        if (isset($this->analysis_cache[$cache_key])) {
            return $this->analysis_cache[$cache_key];
        }
        
        $clean_content = $this->clean_german_text($content);
        $full_text = $clean_content . ' ' . $title;
        
        $word_count = $this->count_german_words($clean_content);
        $char_count = mb_strlen($clean_content, 'UTF-8');
        $sentence_count = $this->count_german_sentences($clean_content);
        $paragraph_count = count(array_filter(explode("\n", $clean_content)));
        
        $analysis = array(
            'content' => $clean_content,
            'word_count' => $word_count,
            'char_count' => $char_count,
            'sentence_count' => $sentence_count,
            'paragraph_count' => $paragraph_count,
            'avg_sentence_length' => $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0,
            'avg_word_length' => $word_count > 0 ? round($char_count / $word_count, 1) : 0,
            'german_keywords' => $this->extract_german_keywords($full_text),
            'business_themes' => $this->identify_german_business_themes($full_text),
            'content_quality' => $this->assess_german_content_quality($clean_content, $word_count),
            'regional_info' => $this->detect_swiss_regional_content($full_text),
            'readability_score' => $this->calculate_german_readability($clean_content, $word_count),
            'analysis_timestamp' => current_time('timestamp')
        );
        
        // In Cache speichern
        $this->analysis_cache[$cache_key] = $analysis;
        
        return $analysis;
    }
    
    /**
     * Leere Analyse zurückgeben
     * 
     * @return array Leere Analyse-Struktur
     */
    private function get_empty_analysis() {
        return array(
            'content' => '',
            'word_count' => 0,
            'char_count' => 0,
            'sentence_count' => 0,
            'paragraph_count' => 0,
            'avg_sentence_length' => 0,
            'avg_word_length' => 0,
            'german_keywords' => array(),
            'business_themes' => array(),
            'content_quality' => 0,
            'regional_info' => array(),
            'readability_score' => 0,
            'analysis_timestamp' => current_time('timestamp')
        );
    }
    
    /**
     * SEO-Score für deutschen Content berechnen (erweitert)
     * 
     * @param array $analysis Analyse-Ergebnisse
     * @return array SEO-Score mit Details
     */
    public function calculate_seo_score($analysis) {
        $score = 0;
        $max_score = 100;
        $details = array();
        
        // Content-Länge (30 Punkte)
        if ($analysis['word_count'] >= 400) {
            $score += 30;
            $details[] = '✅ Optimale Content-Länge (' . $analysis['word_count'] . ' Wörter)';
        } elseif ($analysis['word_count'] >= 250) {
            $score += 20;
            $details[] = '⚠️ Content-Länge könnte besser sein (' . $analysis['word_count'] . ' Wörter)';
        } elseif ($analysis['word_count'] >= 100) {
            $score += 10;
            $details[] = '⚠️ Content etwas kurz (' . $analysis['word_count'] . ' Wörter)';
        } else {
            $details[] = '❌ Content zu kurz (' . $analysis['word_count'] . ' Wörter)';
        }
        
        // Lesbarkeit (25 Punkte)
        if ($analysis['readability_score'] >= 80) {
            $score += 25;
            $details[] = '✅ Ausgezeichnete Lesbarkeit (' . $analysis['readability_score'] . '%)';
        } elseif ($analysis['readability_score'] >= 60) {
            $score += 20;
            $details[] = '✅ Gute Lesbarkeit (' . $analysis['readability_score'] . '%)';
        } elseif ($analysis['readability_score'] >= 40) {
            $score += 10;
            $details[] = '⚠️ Lesbarkeit könnte besser sein (' . $analysis['readability_score'] . '%)';
        } else {
            $details[] = '❌ Schwer lesbar (' . $analysis['readability_score'] . '%)';
        }
        
        // Business-Relevanz (25 Punkte)
        $business_score = count($analysis['business_themes']);
        if ($business_score >= 4) {
            $score += 25;
            $details[] = '✅ Sehr starke Business-Relevanz (' . $business_score . ' Themen)';
        } elseif ($business_score >= 2) {
            $score += 20;
            $details[] = '✅ Gute Business-Relevanz (' . $business_score . ' Themen)';
        } elseif ($business_score >= 1) {
            $score += 10;
            $details[] = '⚠️ Moderate Business-Relevanz (' . $business_score . ' Themen)';
        } else {
            $details[] = '❌ Keine Business-Relevanz erkannt';
        }
        
        // Regionale Relevanz (20 Punkte)
        $regional_score = $analysis['regional_info']['regional_score'] ?? 0;
        if ($regional_score >= 10) {
            $score += 20;
            $details[] = '✅ Sehr starke regionale Relevanz für die Schweiz';
        } elseif ($regional_score >= 5) {
            $score += 15;
            $details[] = '✅ Gute regionale Relevanz';
        } elseif ($regional_score >= 2) {
            $score += 10;
            $details[] = '⚠️ Moderate regionale Relevanz';
        } else {
            $details[] = '❌ Keine regionale Relevanz erkannt';
        }
        
        return array(
            'score' => min($score, $max_score),
            'max_score' => $max_score,
            'percentage' => round(($score / $max_score) * 100),
            'details' => $details,
            'grade' => $this->get_seo_grade($score),
            'recommendations' => $this->get_seo_recommendations($analysis)
        );
    }
    
    /**
     * SEO-Grade basierend auf Score
     * 
     * @param int $score SEO-Score
     * @return string Grade (A+, A, B, C, D, F)
     */
    private function get_seo_grade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
    
    /**
     * SEO-Empfehlungen basierend auf Analyse
     * 
     * @param array $analysis Analyse-Ergebnisse
     * @return array Empfehlungen
     */
    private function get_seo_recommendations($analysis) {
        $recommendations = array();
        
        if ($analysis['word_count'] < 300) {
            $recommendations[] = 'Erweitern Sie den Content auf mindestens 300 Wörter für bessere SEO-Performance';
        }
        
        if ($analysis['readability_score'] < 60) {
            $recommendations[] = 'Verbessern Sie die Lesbarkeit durch kürzere Sätze und einfachere Wörter';
        }
        
        if (count($analysis['business_themes']) < 2) {
            $recommendations[] = 'Integrieren Sie mehr business-relevante Begriffe in Ihren Content';
        }
        
        if (($analysis['regional_info']['regional_score'] ?? 0) < 5) {
            $recommendations[] = 'Fügen Sie regionale Bezüge für bessere Local SEO hinzu';
        }
        
        if ($analysis['sentence_count'] > 0 && ($analysis['word_count'] / $analysis['sentence_count']) > 25) {
            $recommendations[] = 'Kürzen Sie zu lange Sätze für bessere Lesbarkeit';
        }
        
        return $recommendations;
    }
    
    /**
     * Cache leeren (für Performance-Management)
     */
    public function clear_cache() {
        $this->analysis_cache = array();
    }
    
    /**
     * Cache-Statistiken abrufen
     * 
     * @return array Cache-Informationen
     */
    public function get_cache_stats() {
        return array(
            'cache_entries' => count($this->analysis_cache),
            'memory_usage' => memory_get_usage(true),
            'cache_size_bytes' => strlen(serialize($this->analysis_cache))
        );
    }
}

// Instanz für globale Verwendung
if (!function_exists('retexify_get_content_analyzer')) {
    function retexify_get_content_analyzer() {
        static $instance = null;
        if (null === $instance) {
            $instance = new ReTexify_German_Content_Analyzer();
        }
        return $instance;
    }
}