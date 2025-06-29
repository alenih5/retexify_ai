<?php
/**
 * ReTexify Intelligent Keyword Research Engine - Vollständige Implementierung
 * 
 * Kombiniert API-basierte Intelligenz mit universeller Traffic-Optimierung
 * Multi-API-Integration mit intelligentem Fallback-System
 * Branchenunabhängige Keyword-Research und regionale Optimierung
 * 
 * @package ReTexify_AI_Pro
 * @version 3.7.0
 * @author Imponi
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Intelligent_Keyword_Research {
    
    /**
     * Maximale Zeit für API-Research (Sekunden)
     */
    private static $max_research_time = 10;
    
    /**
     * Debug-Modus
     */
    private static $debug_mode = false;
    
    /**
     * HAUPT-ENTRY-POINT: Intelligente Prompt-Generierung
     * 
     * Diese Funktion entscheidet automatisch zwischen API-basierter und universeller Generierung
     * 
     * @param string $content Content-Text für Analyse
     * @param array $settings Plugin-Einstellungen
     * @return string Optimierter Prompt für KI-Generierung
     */
    public static function create_super_prompt($content, $settings = array()) {
        $start_time = time();
        
        try {
            if (self::$debug_mode) {
                error_log('ReTexify Research: Starting super prompt generation');
            }
            
            // 1. API-Status prüfen (nur wenn API-Manager verfügbar)
            if (class_exists('ReTexify_API_Manager')) {
                $api_status = ReTexify_API_Manager::test_apis();
                $apis_working = !empty(array_filter($api_status));
                
                if ($apis_working) {
                    // PLAN A: APIs verfügbar → echte API-basierte Intelligenz
                    if (self::$debug_mode) {
                        error_log('ReTexify Research: Using API-enhanced mode');
                    }
                    return self::generate_api_enhanced_prompt($content, $settings, $start_time);
                }
            }
            
            // PLAN B: APIs offline/unavailable → universelle Traffic-Engine
            if (self::$debug_mode) {
                error_log('ReTexify Research: Using universal traffic mode');
            }
            return self::generate_universal_traffic_prompt($content, $settings);
            
        } catch (Exception $e) {
            error_log('ReTexify Intelligent Research Error: ' . $e->getMessage());
            // Fallback auf universelle Engine
            return self::generate_universal_traffic_prompt($content, $settings);
        }
    }
    
    /**
     * Alternative Entry-Point: Universelle Traffic-Optimierung (immer verfügbar)
     * 
     * @param string $content Content-Text
     * @param array $settings Einstellungen
     * @return string Universal-Prompt
     */
    public static function create_universal_traffic_prompt($content, $settings = array()) {
        return self::generate_universal_traffic_prompt($content, $settings);
    }
    
    /**
     * PLAN A: API-Enhanced Prompt Generation
     * Nutzt echte API-Daten für maximale Intelligenz
     * 
     * @param string $content Content-Text
     * @param array $settings Einstellungen
     * @param int $start_time Start-Zeitstempel
     * @return string API-enhanced Prompt
     */
    private static function generate_api_enhanced_prompt($content, $settings, $start_time) {
        // 1. Content analysieren
        $analysis = self::analyze_content_structure($content);
        
        // Zeitcheck nach Content-Analyse
        if (time() - $start_time > self::$max_research_time) {
            return self::generate_universal_traffic_prompt($content, $settings);
        }
        
        // 2. API-Research durchführen
        $research_data = self::perform_api_research($analysis, $start_time);
        
        // 3. Regionale Optimierung
        $regional_data = self::extract_regional_context($settings);
        
        // 4. API-Enhanced Prompt zusammenbauen
        return self::build_api_enhanced_prompt($analysis, $research_data, $regional_data, $settings);
    }
    
    /**
     * PLAN B: Universal Traffic Prompt Generation
     * Funktioniert ohne APIs - basiert auf bewährten SEO-Patterns
     * 
     * @param string $content Content-Text
     * @param array $settings Einstellungen
     * @return string Universal Traffic Prompt
     */
    private static function generate_universal_traffic_prompt($content, $settings = array()) {
        $analysis = self::analyze_content_structure($content);
        $regional_context = self::extract_regional_context($settings);
        
        $prompt_parts = array();
        
        // Header
        $prompt_parts[] = "Erstelle hochkonvertierende Meta-Texte für: {$analysis['main_topic']}";
        $prompt_parts[] = "";
        
        // Universal Traffic Optimization
        $prompt_parts[] = "🚀 UNIVERSAL TRAFFIC OPTIMIZATION:";
        $prompt_parts[] = "- Primär-Keyword: " . $analysis['primary_keyword'];
        $prompt_parts[] = "- Content-Typ: " . ucfirst($analysis['content_type']);
        $prompt_parts[] = "- Zielgruppe: " . $analysis['target_audience'];
        $prompt_parts[] = "- Branche: " . ucfirst($analysis['industry']);
        $prompt_parts[] = "";
        
        // Search Intent Optimization
        $intent_prompts = array(
            'informational' => 'Anleitung, Guide, Tipps, Lernen, Verstehen, Wissen',
            'commercial' => 'Vergleich, Test, Bewertung, Beste, Top, Review',
            'transactional' => 'Kaufen, Bestellen, Buchen, Angebot, Preis, Jetzt',
            'navigational' => 'Offizielle Seite, Kontakt, Standort, Öffnungszeiten'
        );
        
        $prompt_parts[] = "🎯 SEARCH INTENT OPTIMIZATION:";
        $prompt_parts[] = "- Suchintention: " . ucfirst($analysis['search_intent']);
        $prompt_parts[] = "- Intent-Keywords: " . ($intent_prompts[$analysis['search_intent']] ?? 'Universal');
        $prompt_parts[] = "";
        
        // Branchenspezifische Keywords
        $industry_keywords = self::get_industry_keywords($analysis['industry']);
        if (!empty($industry_keywords)) {
            $prompt_parts[] = "🏭 BRANCHENSPEZIFISCHE KEYWORDS:";
            $prompt_parts[] = "- Branche: " . ucfirst($analysis['industry']);
            $prompt_parts[] = "- Relevante Begriffe: " . implode(', ', array_slice($industry_keywords, 0, 8));
            $prompt_parts[] = "";
        }
        
        // Regional Context (falls verfügbar)
        if (!empty($regional_context['enabled'])) {
            $prompt_parts[] = "📍 SCHWEIZER LOCAL SEO:";
            $prompt_parts[] = "- Zielregion: " . $regional_context['target_region'];
            $prompt_parts[] = "- Lokale Keywords: " . implode(', ', $regional_context['local_keywords']);
            $prompt_parts[] = "";
        }
        
        // Universal SEO Best Practices
        $prompt_parts[] = "📊 UNIVERSAL SEO BEST PRACTICES:";
        $prompt_parts[] = "- Emotional Triggers: Nutzen, Lösung, Erfolg, Einfach, Schnell";
        $prompt_parts[] = "- Conversion-Words: Kostenlos, Sofort, Garantiert, Exklusiv, Neu";
        $prompt_parts[] = "- FOMO-Elemente: Jetzt, Begrenzt, 2025, Aktuell, Nur heute";
        $prompt_parts[] = "- Power-Words: Professionell, Zuverlässig, Bewährt, Kompetent";
        $prompt_parts[] = "";
        
        // Content-spezifische Optimierung
        $content_optimization = self::get_content_type_optimization($analysis['content_type']);
        if (!empty($content_optimization)) {
            $prompt_parts[] = "⚡ CONTENT-SPEZIFISCHE OPTIMIERUNG:";
            $prompt_parts[] = "- Content-Typ: " . ucfirst($analysis['content_type']);
            $prompt_parts[] = "- Optimierung: " . $content_optimization;
            $prompt_parts[] = "";
        }
        
        // Technische Vorgaben
        $prompt_parts[] = "⚙️ TECHNISCHE VORGABEN:";
        $prompt_parts[] = "- Meta-Titel: Max. 58 Zeichen, Keyword prominent am Anfang";
        $prompt_parts[] = "- Meta-Description: 140-155 Zeichen, überzeugender Call-to-Action";
        $prompt_parts[] = "- Fokus-Keyword: Natürlich integriert, nicht überstuffed";
        $prompt_parts[] = "- Sprache: Schweizer Deutsch, professionell aber zugänglich";
        $prompt_parts[] = "- Ton: " . ($settings['premium_tone'] ? 'Premium und exklusiv' : 'Vertrauensvoll und kompetent');
        
        return implode("\n", $prompt_parts);
    }
    
    /**
     * Content-Struktur analysieren (einheitlich für beide Modi)
     * 
     * @param string $content Content-Text
     * @return array Analyse-Ergebnisse
     */
    private static function analyze_content_structure($content) {
        $analysis = array(
            'main_topic' => '',
            'primary_keyword' => '',
            'content_type' => 'informational',
            'search_intent' => 'informational',
            'target_audience' => 'allgemein',
            'industry' => 'general',
            'key_concepts' => array(),
            'word_count' => 0,
            'language' => 'german',
            'content_tone' => 'professional',
            'swiss_places' => array()
        );
        
        if (empty($content)) {
            $analysis['main_topic'] = 'Ihr Content-Thema';
            $analysis['primary_keyword'] = 'Haupt-Keyword';
            return $analysis;
        }
        
        // Text normalisieren
        $clean_text = strtolower(strip_tags($content));
        $words = explode(' ', $clean_text);
        $analysis['word_count'] = count($words);
        
        // Stop-Words für deutsche Sprache
        $stop_words = array(
            'und', 'oder', 'der', 'die', 'das', 'mit', 'für', 'von', 'bei', 'nach', 
            'über', 'durch', 'ohne', 'unter', 'auf', 'um', 'an', 'zu', 'vor', 'zwischen',
            'als', 'aber', 'auch', 'aus', 'dem', 'den', 'des', 'ein', 'eine', 'einer',
            'eines', 'im', 'in', 'ist', 'sind', 'war', 'waren', 'hat', 'haben', 'wird',
            'werden', 'kann', 'können', 'soll', 'sollen', 'will', 'wollen', 'nicht',
            'noch', 'nur', 'schon', 'sehr', 'wenn', 'wie', 'was', 'wo', 'wer', 'warum'
        );
        
        // Keywords extrahieren (längste und häufigste Wörter ohne Stop-Words)
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words) && ctype_alpha($word);
        });
        
        $word_counts = array_count_values($filtered_words);
        arsort($word_counts);
        $top_keywords = array_slice(array_keys($word_counts), 0, 10);
        
        $analysis['primary_keyword'] = !empty($top_keywords) ? $top_keywords[0] : 'Hauptkeyword';
        $analysis['key_concepts'] = $top_keywords;
        
        // Topic aus erstem signifikanten Keyword
        $analysis['main_topic'] = !empty($top_keywords) ? ucfirst($top_keywords[0]) : 'Ihr Thema';
        
        // Content-Typ erkennen
        $analysis['content_type'] = self::detect_content_type($clean_text);
        
        // Search Intent erkennen
        $analysis['search_intent'] = self::detect_search_intent($clean_text);
        
        // Branche erkennen
        $analysis['industry'] = self::detect_industry($clean_text);
        
        // Zielgruppe ableiten
        $analysis['target_audience'] = self::detect_target_audience($clean_text, $analysis['industry']);
        
        // Content-Ton erkennen
        $analysis['content_tone'] = self::detect_content_tone($clean_text);
        
        // Schweizer Orte erkennen
        $analysis['swiss_places'] = self::detect_swiss_places($clean_text);
        
        return $analysis;
    }
    
    /**
     * API-Research durchführen (nur für PLAN A)
     * 
     * @param array $analysis Content-Analyse
     * @param int $start_time Start-Zeitstempel
     * @return array Research-Daten
     */
    private static function perform_api_research($analysis, $start_time) {
        $research_data = array(
            'google_suggestions' => array(),
            'wikipedia_related' => array(),
            'wiktionary_synonyms' => array(),
            'swiss_locations' => array()
        );
        
        if (!class_exists('ReTexify_API_Manager')) {
            return $research_data;
        }
        
        $main_keyword = $analysis['primary_keyword'];
        
        try {
            // 1. Google Suggest (falls Zeit verfügbar)
            if (time() - $start_time < self::$max_research_time - 4) {
                $suggestions = ReTexify_API_Manager::google_suggest($main_keyword, 'de');
                if (!empty($suggestions)) {
                    $research_data['google_suggestions'] = array_slice($suggestions, 0, 8);
                }
            }
            
            // 2. Wikipedia Related (falls Zeit verfügbar)
            if (time() - $start_time < self::$max_research_time - 2) {
                $related = ReTexify_API_Manager::wikipedia_search($main_keyword, 'de');
                if (!empty($related)) {
                    $research_data['wikipedia_related'] = array_slice($related, 0, 10);
                }
            }
            
            // 3. Wiktionary Synonyms (falls Zeit verfügbar)
            if (time() - $start_time < self::$max_research_time - 1) {
                $synonyms = ReTexify_API_Manager::wiktionary_search($main_keyword, 'de');
                if (!empty($synonyms)) {
                    $research_data['wiktionary_synonyms'] = array_slice($synonyms, 0, 6);
                }
            }
            
            // 4. Schweizer Orte recherchieren (falls relevant)
            if (!empty($analysis['swiss_places']) && time() - $start_time < self::$max_research_time) {
                foreach ($analysis['swiss_places'] as $place) {
                    $locations = ReTexify_API_Manager::osm_swiss_places($place, 3);
                    if (!empty($locations)) {
                        $research_data['swiss_locations'] = array_merge($research_data['swiss_locations'], $locations);
                    }
                    break; // Nur ersten Ort recherchieren wegen Zeit
                }
            }
            
        } catch (Exception $e) {
            error_log('ReTexify API Research Error: ' . $e->getMessage());
        }
        
        return $research_data;
    }
    
    /**
     * API-Enhanced Prompt zusammenbauen (nur für PLAN A)
     * 
     * @param array $analysis Content-Analyse
     * @param array $research_data API-Research-Daten
     * @param array $regional_data Regionale Daten
     * @param array $settings Plugin-Settings
     * @return string API-Enhanced Prompt
     */
    private static function build_api_enhanced_prompt($analysis, $research_data, $regional_data, $settings) {
        $prompt_parts = array();
        
        // Header
        $prompt_parts[] = "Erstelle intelligente SEO-Meta-Texte für: {$analysis['main_topic']}";
        $prompt_parts[] = "";
        
        // API-Research Sektion (falls Daten vorhanden)
        if (!empty($research_data['google_suggestions']) || !empty($research_data['wikipedia_related'])) {
            $prompt_parts[] = "🔍 API-BASIERTE KEYWORD-RESEARCH:";
            
            if (!empty($research_data['google_suggestions'])) {
                $trending = implode(', ', array_slice($research_data['google_suggestions'], 0, 5));
                $prompt_parts[] = "- Trending Suchen: {$trending}";
            }
            
            if (!empty($research_data['wikipedia_related'])) {
                $related = implode(', ', array_slice($research_data['wikipedia_related'], 0, 5));
                $prompt_parts[] = "- Verwandte Begriffe: {$related}";
            }
            
            if (!empty($research_data['wiktionary_synonyms'])) {
                $synonyms = implode(', ', $research_data['wiktionary_synonyms']);
                $prompt_parts[] = "- Synonyme: {$synonyms}";
            }
            
            $prompt_parts[] = "";
        }
        
        // Content-Analyse
        $prompt_parts[] = "📊 CONTENT-ANALYSE:";
        $prompt_parts[] = "- Hauptkeyword: " . $analysis['primary_keyword'];
        $prompt_parts[] = "- Content-Typ: " . ucfirst($analysis['content_type']);
        $prompt_parts[] = "- Suchintention: " . ucfirst($analysis['search_intent']);
        $prompt_parts[] = "- Branche: " . ucfirst($analysis['industry']);
        $prompt_parts[] = "- Zielgruppe: " . $analysis['target_audience'];
        $prompt_parts[] = "";
        
        // Regionale Optimierung
        if (!empty($regional_data['enabled'])) {
            $prompt_parts[] = "📍 REGIONALE OPTIMIERUNG:";
            $prompt_parts[] = "- Zielregion: " . $regional_data['target_region'];
            $prompt_parts[] = "- Lokale Keywords: " . implode(', ', $regional_data['local_keywords']);
            if (!empty($research_data['swiss_locations'])) {
                $locations = array_slice($research_data['swiss_locations'], 0, 3);
                $location_names = array_map(function($loc) { return $loc['city']; }, $locations);
                $prompt_parts[] = "- Relevante Orte: " . implode(', ', array_filter($location_names));
            }
            $prompt_parts[] = "";
        }
        
        // Technische Vorgaben
        $prompt_parts[] = "⚙️ OPTIMIERUNGSZIELE:";
        $prompt_parts[] = "- Meta-Titel: Max. 58 Zeichen, Keyword prominent platziert";
        $prompt_parts[] = "- Meta-Description: 140-155 Zeichen, überzeugender Call-to-Action";
        $prompt_parts[] = "- Fokus-Keyword: Natürlich integriert, SEO-optimiert";
        $prompt_parts[] = "- Sprache: Schweizer Deutsch, zielgruppengerecht";
        $prompt_parts[] = "- Ton: " . ($settings['premium_tone'] ? 'Premium und exklusiv' : 'Vertrauensvoll und kompetent');
        
        return implode("\n", $prompt_parts);
    }
    
    /**
     * Regionale Context extrahieren aus Settings
     * 
     * @param array $settings Plugin-Settings
     * @return array Regionale Daten
     */
    private static function extract_regional_context($settings) {
        $regional_data = array(
            'enabled' => false,
            'target_region' => 'Schweiz',
            'local_keywords' => array()
        );
        
        // Kantone aus Settings prüfen
        if (!empty($settings['include_cantons']) || !empty($settings['selected_cantons'])) {
            $regional_data['enabled'] = true;
            
            $swiss_cantons = ReTexify_API_Manager::get_swiss_cantons();
            $selected_cantons = $settings['selected_cantons'] ?? array('ZH', 'BE', 'LU');
            $target_regions = array();
            $local_keywords = array();
            
            foreach ($selected_cantons as $canton_code) {
                if (isset($swiss_cantons[$canton_code])) {
                    $canton_name = $swiss_cantons[$canton_code]['name'];
                    $target_regions[] = $canton_name;
                    $local_keywords[] = $canton_name;
                    $local_keywords[] = "Kanton {$canton_name}";
                    $local_keywords[] = "{$canton_name} Schweiz";
                    
                    // Hauptort hinzufügen
                    if (isset($swiss_cantons[$canton_code]['capital'])) {
                        $capital = $swiss_cantons[$canton_code]['capital'];
                        $local_keywords[] = $capital;
                    }
                }
            }
            
            $regional_data['target_region'] = implode(', ', array_slice($target_regions, 0, 3));
            $regional_data['local_keywords'] = array_slice(array_unique($local_keywords), 0, 12);
        }
        
        return $regional_data;
    }
    
    /**
     * Content-Typ erkennen
     * 
     * @param string $text Content-Text (normalisiert)
     * @return string Content-Typ
     */
    private static function detect_content_type($text) {
        $patterns = array(
            'product' => array('kaufen', 'preis', 'angebot', 'produkt', 'bestellen', 'shop', 'online shop', 'verkauf'),
            'service' => array('dienstleistung', 'service', 'beratung', 'unterstützung', 'hilfe', 'consulting'),
            'tutorial' => array('anleitung', 'tutorial', 'schritt', 'lernen', 'guide', 'how to', 'tipps'),
            'blog' => array('artikel', 'beitrag', 'blog', 'news', 'aktuell', 'neuigkeiten'),
            'company' => array('unternehmen', 'firma', 'team', 'über uns', 'kontakt', 'historie'),
            'comparison' => array('vergleich', 'test', 'vs', 'versus', 'unterschied', 'gegenüber'),
            'review' => array('bewertung', 'rezension', 'erfahrung', 'meinung', 'feedback'),
            'event' => array('event', 'veranstaltung', 'termin', 'seminar', 'workshop', 'kurs')
        );
        
        foreach ($patterns as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $type;
                }
            }
        }
        
        return 'informational';
    }
    
    /**
     * Search Intent erkennen
     * 
     * @param string $text Content-Text (normalisiert)
     * @return string Search Intent
     */
    private static function detect_search_intent($text) {
        $intent_patterns = array(
            'transactional' => array('kaufen', 'bestellen', 'buchen', 'anmelden', 'registrieren', 'reservieren', 'mieten'),
            'commercial' => array('vergleich', 'test', 'bewertung', 'beste', 'günstig', 'preis', 'kosten', 'angebot'),
            'navigational' => array('kontakt', 'öffnungszeiten', 'standort', 'telefon', 'adresse', 'anfahrt'),
            'informational' => array('was ist', 'wie', 'warum', 'anleitung', 'tipps', 'definition', 'erklärung')
        );
        
        $scores = array();
        foreach ($intent_patterns as $intent => $keywords) {
            $scores[$intent] = 0;
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $scores[$intent]++;
                }
            }
        }
        
        // Intent mit höchstem Score zurückgeben
        $max_intent = array_keys($scores, max($scores))[0];
        return $max_intent ?: 'informational';
    }
    
    /**
     * Branche erkennen anhand von Keywords
     * 
     * @param string $text Content-Text (normalisiert)
     * @return string Branche
     */
    private static function detect_industry($text) {
        $industry_patterns = array(
            'healthcare' => array('gesundheit', 'medizin', 'arzt', 'therapie', 'behandlung', 'krankenhaus', 'praxis'),
            'finance' => array('bank', 'geld', 'kredit', 'versicherung', 'investment', 'finanzen', 'sparkasse'),
            'technology' => array('software', 'app', 'digital', 'tech', 'computer', 'it', 'programmierung'),
            'education' => array('schule', 'kurs', 'lernen', 'ausbildung', 'studium', 'universität', 'bildung'),
            'retail' => array('shop', 'laden', 'verkauf', 'produkt', 'shopping', 'einzelhandel'),
            'real_estate' => array('immobilie', 'haus', 'wohnung', 'miete', 'kauf', 'makler', 'eigentum'),
            'legal' => array('anwalt', 'recht', 'gesetz', 'beratung', 'kanzlei', 'rechtsanwalt', 'notar'),
            'automotive' => array('auto', 'fahrzeug', 'garage', 'werkstatt', 'reparatur', 'service'),
            'hospitality' => array('hotel', 'restaurant', 'gastronomie', 'tourismus', 'reise', 'urlaub'),
            'construction' => array('bau', 'handwerk', 'renovation', 'architekt', 'bauen', 'sanierung'),
            'beauty' => array('kosmetik', 'schönheit', 'friseur', 'wellness', 'spa', 'pflege'),
            'sports' => array('sport', 'fitness', 'training', 'gym', 'verein', 'bewegung')
        );
        
        foreach ($industry_patterns as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $industry;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Zielgruppe basierend auf Content und Branche ableiten
     * 
     * @param string $text Content-Text
     * @param string $industry Erkannte Branche
     * @return string Zielgruppe
     */
    private static function detect_target_audience($text, $industry) {
        // Spezifische Zielgruppen-Erkennung
        if (strpos($text, 'unternehmen') !== false || strpos($text, 'business') !== false || strpos($text, 'b2b') !== false) {
            return 'B2B-Kunden und Unternehmen';
        } elseif (strpos($text, 'familie') !== false || strpos($text, 'kinder') !== false || strpos($text, 'eltern') !== false) {
            return 'Familien mit Kindern';
        } elseif (strpos($text, 'senior') !== false || strpos($text, 'älter') !== false || strpos($text, '65+') !== false) {
            return 'Senioren und ältere Menschen';
        } elseif (strpos($text, 'student') !== false || strpos($text, 'jung') !== false || strpos($text, 'azubi') !== false) {
            return 'Jugendliche und junge Erwachsene';
        }
        
        // Branchenspezifische Zielgruppen
        $audience_map = array(
            'healthcare' => 'Patienten und Gesundheitsbewusste',
            'finance' => 'Sparer, Investoren und Finanzbewusste',
            'technology' => 'Tech-Interessierte und IT-Professionals',
            'education' => 'Lernende und Weiterbildungsinteressierte',
            'retail' => 'Konsumenten und Online-Käufer',
            'real_estate' => 'Immobilieninteressierte und Investoren',
            'legal' => 'Rechtssuchende und Unternehmen',
            'automotive' => 'Autobesitzer und Fahrzeuginteressierte',
            'hospitality' => 'Reisende und Gastronomiefans',
            'construction' => 'Bauherren und Renovierer',
            'beauty' => 'Beauty- und Wellness-Interessierte',
            'sports' => 'Sportler und Fitness-Enthusiasten'
        );
        
        return $audience_map[$industry] ?? 'Schweizer Zielgruppe';
    }
    
    /**
     * Content-Ton erkennen
     * 
     * @param string $text Content-Text
     * @return string Content-Ton
     */
    private static function detect_content_tone($text) {
        if (strpos($text, 'exklusiv') !== false || strpos($text, 'premium') !== false || strpos($text, 'luxus') !== false) {
            return 'premium';
        } elseif (strpos($text, 'günstig') !== false || strpos($text, 'preiswert') !== false || strpos($text, 'sparen') !== false) {
            return 'preisorientiert';
        } elseif (strpos($text, 'schnell') !== false || strpos($text, 'sofort') !== false || strpos($text, 'express') !== false) {
            return 'zeitkritisch';
        } elseif (strpos($text, 'vertrauen') !== false || strpos($text, 'sicher') !== false || strpos($text, 'qualität') !== false) {
            return 'vertrauensvoll';
        } else {
            return 'professional';
        }
    }
    
    /**
     * Schweizer Orte im Text erkennen
     * 
     * @param string $text Content-Text
     * @return array Gefundene Schweizer Orte
     */
    private static function detect_swiss_places($text) {
        $swiss_places_pattern = '/\b(schweiz|zürich|bern|basel|genf|luzern|st\.?\s?gallen|winterthur|lausanne|biel|thun|köniz|la chaux-de-fonds|fribourg|schaffhausen|vernier|chur|uster|sion|neuenburg|lancy|kriens|yverdon|steffisburg|oftringen|wohlen|renens|bulle|monthey|dietikon|riehen|carouge|weinfelden|aarau|rapperswil|davos|zermatt|interlaken|st\.\s?moritz|andermatt|saas-fee|grindelwald|wengen|mürren|verbier|crans-montana|villars|leysin|gstaad|engelberg)\b/i';
        
        preg_match_all($swiss_places_pattern, $text, $matches);
        return array_unique($matches[0]);
    }
    
    /**
     * Branchenspezifische Keywords abrufen
     * 
     * @param string $industry Branche
     * @return array Branchenspezifische Keywords
     */
    private static function get_industry_keywords($industry) {
        $industry_keywords = array(
            'healthcare' => array('Behandlung', 'Therapie', 'Gesundheit', 'Medizin', 'Praxis', 'Arzt', 'Heilung', 'Vorsorge'),
            'finance' => array('Beratung', 'Investment', 'Sparplan', 'Kredit', 'Zinsen', 'Anlage', 'Vermögen', 'Finanzierung'),
            'technology' => array('Digital', 'Innovation', 'Software', 'Lösung', 'System', 'Automatisierung', 'Cloud', 'Modern'),
            'education' => array('Lernen', 'Weiterbildung', 'Kurs', 'Zertifikat', 'Kompetenz', 'Wissen', 'Ausbildung', 'Karriere'),
            'retail' => array('Qualität', 'Auswahl', 'Service', 'Lieferung', 'Online', 'Shop', 'Produkt', 'Angebot'),
            'real_estate' => array('Lage', 'Immobilie', 'Investment', 'Wohnen', 'Eigentum', 'Miete', 'Makler', 'Bewertung'),
            'legal' => array('Beratung', 'Recht', 'Kompetenz', 'Vertretung', 'Lösung', 'Expertise', 'Vertrauen', 'Erfolg'),
            'automotive' => array('Service', 'Qualität', 'Reparatur', 'Wartung', 'Garantie', 'Zuverlässig', 'Kompetent', 'Schnell'),
            'hospitality' => array('Gastfreundschaft', 'Komfort', 'Erlebnis', 'Entspannung', 'Genuss', 'Atmosphere', 'Service', 'Qualität'),
            'construction' => array('Qualität', 'Handwerk', 'Erfahrung', 'Planung', 'Ausführung', 'Kompetenz', 'Zuverlässig', 'Termine'),
            'beauty' => array('Schönheit', 'Pflege', 'Wellness', 'Entspannung', 'Behandlung', 'Natürlich', 'Qualität', 'Ergebnis'),
            'sports' => array('Training', 'Fitness', 'Gesundheit', 'Motivation', 'Erfolg', 'Leistung', 'Coaching', 'Ziele')
        );
        
        return $industry_keywords[$industry] ?? array();
    }
    
    /**
     * Content-spezifische Optimierung abrufen
     * 
     * @param string $content_type Content-Typ
     * @return string Optimierungs-Tipps
     */
    private static function get_content_type_optimization($content_type) {
        $optimizations = array(
            'product' => 'Produktvorteile hervorheben, Kaufargumente, Verfügbarkeit',
            'service' => 'Nutzen betonen, Expertise zeigen, Vertrauen aufbauen',
            'tutorial' => 'Schritt-für-Schritt betonen, Einfachheit, Lernerfolg',
            'blog' => 'Aktualität, Mehrwert, Engagement',
            'company' => 'Kompetenz, Vertrauen, Unterscheidungsmerkmale',
            'comparison' => 'Objektiv, Entscheidungshilfe, Klarheit',
            'review' => 'Authentizität, Erfahrung, Empfehlung',
            'event' => 'Datum, Ort, Nutzen, Anmeldung'
        );
        
        return $optimizations[$content_type] ?? '';
    }
    
    /**
     * System-Status für Research-Capabilities testen
     * 
     * @return array Status-Informationen
     */
    public static function test_research_capabilities() {
        $capabilities = array(
            'api_manager_available' => class_exists('ReTexify_API_Manager'),
            'universal_engine_active' => true, // Immer verfügbar
            'content_analysis_active' => true, // Immer verfügbar
            'regional_optimization' => true   // Immer verfügbar
        );
        
        // API-Status testen falls verfügbar
        if ($capabilities['api_manager_available']) {
            try {
                $api_status = ReTexify_API_Manager::test_apis();
                $capabilities['api_services_online'] = !empty(array_filter($api_status));
                $capabilities['api_details'] = $api_status;
            } catch (Exception $e) {
                $capabilities['api_services_online'] = false;
                $capabilities['api_details'] = array();
                error_log('ReTexify Research Capabilities Test Error: ' . $e->getMessage());
            }
        } else {
            $capabilities['api_services_online'] = false;
            $capabilities['api_details'] = array();
        }
        
        return $capabilities;
    }
    
    /**
     * Debug-Modus aktivieren/deaktivieren
     * 
     * @param bool $enabled Debug aktiviert
     */
    public static function set_debug_mode($enabled = true) {
        self::$debug_mode = (bool) $enabled;
    }
    
    /**
     * Research-Zeit-Limit anpassen
     * 
     * @param int $seconds Maximale Zeit in Sekunden
     */
    public static function set_research_time_limit($seconds) {
        if ($seconds >= 3 && $seconds <= 30) {
            self::$max_research_time = (int) $seconds;
        }
    }
    
    /**
     * Erweiterte Content-Analyse für komplexe Inhalte
     * 
     * @param string $content Content-Text
     * @return array Detaillierte Analyse
     */
    public static function advanced_content_analysis($content) {
        $basic_analysis = self::analyze_content_structure($content);
        
        // Erweiterte Analyse hinzufügen
        $extended_analysis = array(
            'readability_score' => self::calculate_readability_score($content),
            'keyword_density' => self::calculate_keyword_density($content),
            'semantic_themes' => self::extract_semantic_themes($content),
            'competitor_analysis' => self::analyze_competitive_landscape($basic_analysis['industry']),
            'seasonal_factors' => self::detect_seasonal_factors($content),
            'local_relevance' => self::assess_local_relevance($content)
        );
        
        return array_merge($basic_analysis, $extended_analysis);
    }
    
    /**
     * Lesbarkeits-Score berechnen (vereinfacht)
     * 
     * @param string $content Content-Text
     * @return int Score von 1-100
     */
    private static function calculate_readability_score($content) {
        $clean_text = strip_tags($content);
        $sentences = preg_split('/[.!?]+/', $clean_text);
        $words = explode(' ', $clean_text);
        $syllables = 0;
        
        foreach ($words as $word) {
            $syllables += max(1, preg_match_all('/[aeiouäöü]/i', $word));
        }
        
        $sentence_count = count(array_filter($sentences));
        $word_count = count(array_filter($words));
        
        if ($sentence_count == 0 || $word_count == 0) {
            return 50;
        }
        
        // Vereinfachte Flesch-Formel für Deutsch
        $score = 180 - (($word_count / $sentence_count) * 1.015) - (($syllables / $word_count) * 84.6);
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Keyword-Dichte berechnen
     * 
     * @param string $content Content-Text
     * @return array Keyword-Dichten
     */
    private static function calculate_keyword_density($content) {
        $clean_text = strtolower(strip_tags($content));
        $words = explode(' ', $clean_text);
        $total_words = count(array_filter($words));
        
        if ($total_words == 0) {
            return array();
        }
        
        $word_counts = array_count_values($words);
        $densities = array();
        
        foreach ($word_counts as $word => $count) {
            if (strlen($word) > 4 && $count > 1) {
                $densities[$word] = round(($count / $total_words) * 100, 2);
            }
        }
        
        arsort($densities);
        return array_slice($densities, 0, 10, true);
    }
    
    /**
     * Semantische Themen extrahieren
     * 
     * @param string $content Content-Text
     * @return array Themen-Cluster
     */
    private static function extract_semantic_themes($content) {
        // Vereinfachte semantische Analyse basierend auf Keyword-Clustering
        $analysis = self::analyze_content_structure($content);
        $themes = array();
        
        // Haupt-Thema
        $themes['primary'] = $analysis['main_topic'];
        
        // Sekundäre Themen basierend auf Key-Concepts
        $themes['secondary'] = array_slice($analysis['key_concepts'], 1, 3);
        
        // Thematische Kategorien
        $themes['categories'] = array($analysis['industry'], $analysis['content_type']);
        
        return $themes;
    }
    
    /**
     * Competitive Landscape analysieren (vereinfacht)
     * 
     * @param string $industry Branche
     * @return array Competitor-Insights
     */
    private static function analyze_competitive_landscape($industry) {
        // Statische Competitive-Insights basierend auf Branche
        $competitive_landscape = array(
            'healthcare' => array('keywords' => array('spezialist', 'erfahren', 'modern'), 'focus' => 'Vertrauen und Expertise'),
            'finance' => array('keywords' => array('sicher', 'transparent', 'persönlich'), 'focus' => 'Sicherheit und Beratung'),
            'technology' => array('keywords' => array('innovativ', 'effizient', 'zukunftssicher'), 'focus' => 'Innovation und Effizienz'),
            'education' => array('keywords' => array('praxisnah', 'zertifiziert', 'erfolgreich'), 'focus' => 'Qualität und Erfolg'),
            'retail' => array('keywords' => array('vielfältig', 'schnell', 'kundenfreundlich'), 'focus' => 'Service und Auswahl'),
            'real_estate' => array('keywords' => array('kompetent', 'vertrauensvoll', 'erfolgreich'), 'focus' => 'Expertise und Vertrauen'),
            'legal' => array('keywords' => array('erfahren', 'spezialisiert', 'erfolgreich'), 'focus' => 'Kompetenz und Erfolg')
        );
        
        return $competitive_landscape[$industry] ?? array('keywords' => array(), 'focus' => 'Qualität und Service');
    }
    
    /**
     * Saisonale Faktoren erkennen
     * 
     * @param string $content Content-Text
     * @return array Saisonale Hinweise
     */
    private static function detect_seasonal_factors($content) {
        $seasonal_patterns = array(
            'winter' => array('winter', 'kalt', 'schnee', 'weihnachten', 'neujahr'),
            'spring' => array('frühling', 'ostern', 'blüte', 'frühjahr'),
            'summer' => array('sommer', 'urlaub', 'ferien', 'sonne', 'hitze'),
            'autumn' => array('herbst', 'schule', 'ernte', 'oktoberfest')
        );
        
        $detected_seasons = array();
        $clean_text = strtolower($content);
        
        foreach ($seasonal_patterns as $season => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($clean_text, $keyword) !== false) {
                    $detected_seasons[] = $season;
                    break;
                }
            }
        }
        
        return array_unique($detected_seasons);
    }
    
    /**
     * Lokale Relevanz bewerten
     * 
     * @param string $content Content-Text
     * @return array Lokale Relevanz-Daten
     */
    private static function assess_local_relevance($content) {
        $swiss_places = self::detect_swiss_places($content);
        $local_indicators = array('lokal', 'regional', 'vor ort', 'nähe', 'umgebung');
        
        $local_score = count($swiss_places) * 20; // Basis-Score für Schweizer Orte
        
        $clean_text = strtolower($content);
        foreach ($local_indicators as $indicator) {
            if (strpos($clean_text, $indicator) !== false) {
                $local_score += 10;
            }
        }
        
        return array(
            'score' => min(100, $local_score),
            'detected_places' => $swiss_places,
            'relevance_level' => $local_score > 50 ? 'high' : ($local_score > 20 ? 'medium' : 'low')
        );
    }
}