<?php
/**
 * ReTexify Intelligent Keyword Research Engine
 * 
 * Kombiniert API-basierte Intelligenz mit universeller Traffic-Optimierung
 * Multi-API-Integration mit intelligentem Fallback-System
 * 
 * @package ReTexify_AI
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
     * HAUPT-FUNKTION: Intelligente Prompt-Generierung mit API-Fallback
     * Diese Funktion entscheidet automatisch zwischen API-basierter und universeller Generierung
     */
    public static function create_super_prompt($content, $settings = array()) {
        $start_time = time();
        
        try {
            // 1. Prüfen ob APIs verfügbar sind
            if (class_exists('ReTexify_API_Manager')) {
                $api_status = ReTexify_API_Manager::test_apis();
                $apis_working = !empty(array_filter($api_status));
                
                if ($apis_working) {
                    // Plan A: APIs funktionieren → echte intelligente Generierung mit APIs
                    return self::generate_intelligent_prompt_with_apis($content, $settings, $start_time);
                }
            }
            
            // Plan B: APIs offline → universelle Traffic-Engine ohne APIs
            return self::create_universal_traffic_prompt($content, $settings);
            
        } catch (Exception $e) {
            error_log('ReTexify Intelligent Research Error: ' . $e->getMessage());
            // Fallback auf universelle Engine
            return self::create_universal_traffic_prompt($content, $settings);
        }
    }
    
    /**
     * ECHTE intelligente Prompt-Generierung mit APIs (Plan A)
     */
    private static function generate_intelligent_prompt_with_apis($content, $settings, $start_time) {
        // 1. Content analysieren
        $content_analysis = self::analyze_content($content);
        
        // Zeitprüfung nach Content-Analyse
        if (time() - $start_time > self::$max_research_time) {
            return self::create_universal_traffic_prompt($content, $settings);
        }
        
        // 2. API-Research durchführen
        $keyword_research = self::perform_keyword_research($content_analysis, $start_time);
        
        // 3. Regionale Optimierung hinzufügen
        $regional_data = self::get_regional_optimization($settings);
        
        // 4. Intelligenten Prompt mit API-Daten zusammenbauen
        return self::build_intelligent_api_prompt($content_analysis, $keyword_research, $regional_data, $settings);
    }
    
    /**
     * Content-Analyse für API-basierte Generierung
     */
    private static function analyze_content($content) {
        $analysis = array(
            'main_keywords' => array(),
            'industry' => 'general',
            'topic' => '',
            'intent' => 'informational',
            'swiss_places' => array(),
            'language' => 'german'
        );
        
        if (empty($content)) {
            return $analysis;
        }
        
        // Text normalisieren
        $clean_content = strtolower(strip_tags($content));
        $words = explode(' ', $clean_content);
        
        // Hauptkeywords extrahieren (längste Wörter)
        $filtered_words = array_filter($words, function($word) {
            return strlen($word) > 3 && !in_array($word, array('und', 'oder', 'der', 'die', 'das', 'mit', 'für', 'von', 'bei', 'nach', 'über', 'durch', 'ohne', 'unter'));
        });
        
        // Nach Häufigkeit sortieren
        $word_counts = array_count_values($filtered_words);
        arsort($word_counts);
        $analysis['main_keywords'] = array_slice(array_keys($word_counts), 0, 5);
        
        // Branche erkennen
        $analysis['industry'] = self::detect_industry($clean_content);
        
        // Topic extrahieren (erstes signifikantes Keyword)
        $analysis['topic'] = !empty($analysis['main_keywords']) ? $analysis['main_keywords'][0] : 'allgemein';
        
        // Search Intent analysieren
        $analysis['intent'] = self::analyze_search_intent($clean_content);
        
        // Schweizer Orte suchen
        $analysis['swiss_places'] = self::find_swiss_places($content);
        
        return $analysis;
    }
    
    /**
     * Branche erkennen für API-Research
     */
    private static function detect_industry($content) {
        $industry_keywords = array(
            'restaurant' => array('restaurant', 'gastronomie', 'essen', 'küche', 'menü', 'speisen', 'catering'),
            'hotel' => array('hotel', 'übernachtung', 'zimmer', 'urlaub', 'ferien', 'tourismus', 'gasthaus'),
            'arzt' => array('arzt', 'praxis', 'medizin', 'behandlung', 'therapie', 'gesundheit', 'klinik'),
            'anwalt' => array('anwalt', 'rechtsanwalt', 'recht', 'beratung', 'kanzlei', 'jura', 'legal'),
            'handwerk' => array('handwerk', 'reparatur', 'installation', 'service', 'werkstatt', 'montage'),
            'immobilien' => array('immobilien', 'wohnung', 'haus', 'miete', 'verkauf', 'makler', 'eigenheim'),
            'ecommerce' => array('shop', 'kaufen', 'bestellen', 'online', 'versand', 'produkt', 'kauf'),
            'beratung' => array('beratung', 'consulting', 'strategie', 'analyse', 'optimierung', 'lösung')
        );
        
        foreach ($industry_keywords as $industry => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    return $industry;
                }
            }
        }
        return 'general';
    }
    
    /**
     * Search Intent analysieren
     */
    private static function analyze_search_intent($content) {
        $intent_patterns = array(
            'informational' => array('was', 'wie', 'warum', 'wann', 'wo', 'anleitung', 'tipps', 'guide'),
            'commercial' => array('vergleich', 'test', 'bewertung', 'erfahrung', 'beste', 'günstig', 'kosten'),
            'transactional' => array('kaufen', 'bestellen', 'buchen', 'termin', 'anmeldung', 'kontakt', 'jetzt'),
            'navigational' => array('adresse', 'öffnungszeiten', 'standort', 'telefon', 'website', 'firma')
        );
        
        foreach ($intent_patterns as $intent => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($content, $pattern) !== false) {
                    return $intent;
                }
            }
        }
        return 'informational';
    }
    
    /**
     * Schweizer Orte im Content finden
     */
    private static function find_swiss_places($content) {
        $places = array();
        
        // Schweizer Kantone suchen
        $swiss_cantons = array(
            'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
            'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
            'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
            'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
            'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen',
            'SO' => 'Solothurn', 'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin',
            'UR' => 'Uri', 'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
        );
        
        $content_lower = strtolower($content);
        foreach ($swiss_cantons as $code => $name) {
            if (strpos($content_lower, strtolower($name)) !== false) {
                $places[] = $name;
            }
        }
        
        // Große Schweizer Städte suchen
        $major_cities = array('zürich', 'geneva', 'genf', 'basel', 'lausanne', 'bern', 'winterthur', 'luzern', 'st. gallen', 'lugano', 'biel', 'thun', 'köniz');
        
        foreach ($major_cities as $city) {
            if (strpos($content_lower, $city) !== false) {
                $places[] = ucfirst($city);
            }
        }
        
        return array_unique($places);
    }
    
    /**
     * API-basierte Keyword-Research
     */
    private static function perform_keyword_research($content_analysis, $start_time) {
        $research_data = array(
            'suggestions' => array(),
            'related_terms' => array(),
            'definitions' => array(),
            'swiss_locations' => array()
        );
        
        if (empty($content_analysis['main_keywords'])) {
            return $research_data;
        }
        
        $main_keyword = $content_analysis['main_keywords'][0];
        
        try {
            // 1. Google Suggest API
            if (time() - $start_time < self::$max_research_time - 3) {
                $suggestions = ReTexify_API_Manager::google_suggest($main_keyword, 'de');
                if (!empty($suggestions)) {
                    $research_data['suggestions'] = array_slice($suggestions, 0, 8);
                }
            }
            
            // 2. Wikipedia API für verwandte Begriffe
            if (time() - $start_time < self::$max_research_time - 2) {
                $related = ReTexify_API_Manager::wikipedia_search($main_keyword, 'de');
                if (!empty($related)) {
                    $research_data['related_terms'] = array_slice($related, 0, 10);
                }
            }
            
            // 3. Wiktionary für Definitionen
            if (time() - $start_time < self::$max_research_time - 1) {
                $definitions = ReTexify_API_Manager::wiktionary_search($main_keyword, 'de');
                if (!empty($definitions)) {
                    $research_data['definitions'] = array_slice($definitions, 0, 3);
                }
            }
            
            // 4. Schweizer Orte recherchieren (falls relevant)
            if (!empty($content_analysis['swiss_places']) && time() - $start_time < self::$max_research_time) {
                foreach ($content_analysis['swiss_places'] as $place) {
                    $locations = ReTexify_API_Manager::osm_swiss_places($place);
                    if (!empty($locations)) {
                        $research_data['swiss_locations'] = array_merge($research_data['swiss_locations'], $locations);
                    }
                    break; // Nur ersten Ort recherchieren
                }
            }
            
        } catch (Exception $e) {
            error_log('ReTexify API Research Error: ' . $e->getMessage());
        }
        
        return $research_data;
    }
    
    /**
     * Intelligenten Prompt mit API-Daten zusammenbauen
     */
    private static function build_intelligent_api_prompt($content_analysis, $keyword_research, $regional_data, $settings) {
        $prompt_parts = array();
        
        // Basis-Content
        $main_topic = $content_analysis['topic'];
        $industry = $content_analysis['industry'];
        $intent = $content_analysis['intent'];
        $local_context = $regional_data['local_context'];
        
        // Header
        $prompt_parts[] = "Erstelle intelligente SEO-Meta-Texte für: {$main_topic}";
        $prompt_parts[] = "";
        
        // KEYWORD-RESEARCH-SEKTION (von APIs)
        if (!empty($keyword_research['suggestions']) || !empty($keyword_research['related_terms'])) {
            $prompt_parts[] = "🔍 KEYWORD-RESEARCH (von APIs):";
            
            if (!empty($keyword_research['suggestions'])) {
                $trending_keywords = implode(', ', array_slice($keyword_research['suggestions'], 0, 5));
                $prompt_parts[] = "- Trending-Suchen: {$trending_keywords}";
            }
            
            if (!empty($keyword_research['related_terms'])) {
                $related_keywords = implode(', ', array_slice($keyword_research['related_terms'], 0, 5));
                $prompt_parts[] = "- Verwandte Begriffe: {$related_keywords}";
            }
            
            if (!empty($content_analysis['main_keywords'])) {
                $main_keywords = implode(', ', array_slice($content_analysis['main_keywords'], 0, 3));
                $prompt_parts[] = "- Haupt-Keywords: {$main_keywords}";
            }
            
            $prompt_parts[] = "";
        }
        
        // REGIONALE OPTIMIERUNG (aus gewählten Kantonen)
        if (!empty($regional_data['regional_keywords'])) {
            $prompt_parts[] = "📍 REGIONALE OPTIMIERUNG:";
            $prompt_parts[] = "- Zielregion: {$local_context}";
            $prompt_parts[] = "- Lokale Keywords: " . implode(', ', array_slice($regional_data['regional_keywords'], 0, 6));
            $prompt_parts[] = "- Fokus: Lokale Sichtbarkeit in der gewählten Region";
            $prompt_parts[] = "";
        }
        
        // Search Intent und Branche
        $intent_mapping = array(
            'informational' => 'Informationssuche (How-to, Erklärungen)',
            'commercial' => 'Vergleichssuche (Tests, Bewertungen)',
            'transactional' => 'Kaufabsicht (Bestellung, Buchung)',
            'navigational' => 'Zielgerichtete Suche (Unternehmen finden)'
        );
        
        $prompt_parts[] = "🧠 KONTEXT-ANALYSE:";
        $prompt_parts[] = "- Branche: " . ucfirst(str_replace('_', ' ', $industry));
        $prompt_parts[] = "- Suchintention: " . ($intent_mapping[$intent] ?? 'Informationssuche');
        $prompt_parts[] = "";
        
        // Optimierungs-Anweisungen
        $prompt_parts[] = "⚙️ SEO-OPTIMIERUNG:";
        $prompt_parts[] = "- Meta-Titel: Max. 55 Zeichen, Haupt-Keyword am Anfang";
        $prompt_parts[] = "- Meta-Description: 150-155 Zeichen, Call-to-Action";
        $prompt_parts[] = "- Focus-Keyword: Natürlich integriert";
        
        // Intent-spezifische Optimierung
        switch ($intent) {
            case 'transactional':
                $prompt_parts[] = "- Call-to-Action: 'Jetzt', 'Sofort', 'Bestellen'";
                break;
            case 'commercial':
                $prompt_parts[] = "- Vertrauenssignale: 'Beste', 'Testsieger', 'Empfohlen'";
                break;
            case 'informational':
                $prompt_parts[] = "- Informations-Fokus: 'Anleitung', 'Tipps', 'Guide'";
                break;
            case 'navigational':
                $prompt_parts[] = "- Lokale Signale: Standort, Öffnungszeiten, Kontakt";
                break;
        }
        
        $prompt_parts[] = "- Sprache: Schweizer Hochdeutsch, professionell";
        $prompt_parts[] = "";
        
        // Business-Context aus Settings
        if (!empty($settings['business_context'])) {
            $prompt_parts[] = "🏢 BUSINESS-KONTEXT: " . $settings['business_context'];
            $prompt_parts[] = "";
        }
        
        // Final prompt instruction
        $prompt_parts[] = "🚀 ZIEL: Nutze die API-Research-Daten für präzise, traffic-optimierte Meta-Texte!";
        
        return implode("\n", $prompt_parts);
    }
    
    // ===== AB HIER: UNIVERSELLE TRAFFIC-ENGINE (OHNE APIs) =====
    
    /**
     * Universelle Traffic-optimierte Prompt-Generierung (FALLBACK ohne APIs)
     * Funktioniert für ALLE Branchen und nutzt die Regionen-Einstellungen
     */
    public static function create_universal_traffic_prompt($content, $settings = array()) {
        return self::generate_universal_traffic_prompt($content, $settings);
    }
    
    /**
     * Universelle Traffic-Prompt-Generierung (funktioniert ohne APIs)
     */
    private static function generate_universal_traffic_prompt($content, $settings) {
        if (empty($content)) {
            return self::generate_basic_fallback($settings);
        }
        
        // Content analysieren
        $analysis = self::analyze_universal_content($content);
        
        // Regionen aus Settings lesen
        $regional_data = self::get_regional_optimization($settings);
        
        // Traffic-Keywords generieren
        $traffic_keywords = self::generate_universal_traffic_keywords($analysis);
        
        // Universellen Prompt zusammenbauen
        return self::build_universal_prompt($analysis, $regional_data, $traffic_keywords, $settings);
    }
    
    /**
     * Universelle Content-Analyse (funktioniert für alle Branchen)
     */
    private static function analyze_universal_content($content) {
        $content_lower = strtolower(strip_tags($content));
        $words = explode(' ', $content_lower);
        
        // Stop-Words entfernen
        $stop_words = array('und', 'oder', 'der', 'die', 'das', 'mit', 'für', 'von', 'bei', 'nach', 'über', 'durch', 'ohne', 'unter', 'sind', 'ist', 'war', 'hat', 'haben', 'wird', 'werden', 'kann', 'soll', 'auch', 'noch', 'nur', 'aber', 'doch', 'schon', 'sehr', 'mehr', 'alle', 'eine', 'einer', 'einem', 'einen', 'sein', 'ihre', 'ihre');
        
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words) && !is_numeric($word);
        });
        
        // Wort-Häufigkeit analysieren
        $word_counts = array_count_values($filtered_words);
        arsort($word_counts);
        
        $analysis = array(
            'main_keywords' => array_slice(array_keys($word_counts), 0, 5),
            'content_type' => self::detect_content_type($content_lower),
            'search_intent' => self::detect_search_intent($content_lower),
            'target_audience' => self::detect_target_audience($content_lower),
            'business_category' => self::detect_universal_business_category($content_lower),
            'tone' => self::detect_content_tone($content_lower)
        );
        
        return $analysis;
    }
    
    /**
     * Universelle Business-Kategorie erkennen (erweitert für alle Branchen)
     */
    private static function detect_universal_business_category($content) {
        $categories = array(
            // Dienstleistungen
            'beratung' => array('beratung', 'consulting', 'coach', 'strategie', 'analyse', 'experte', 'spezialist'),
            'marketing' => array('marketing', 'werbung', 'social media', 'seo', 'website', 'design', 'logo'),
            'immobilien' => array('immobilien', 'wohnung', 'haus', 'miete', 'verkauf', 'makler', 'eigenheim'),
            'versicherung' => array('versicherung', 'vorsorge', 'police', 'schutz', 'rente', 'krankenversicherung'),
            'anwalt' => array('anwalt', 'rechtsanwalt', 'recht', 'kanzlei', 'jura', 'legal', 'verteidigung'),
            'steuerberatung' => array('steuer', 'buchhaltung', 'finanzen', 'revision', 'buchführung', 'treuhänder'),
            
            // Handwerk & Technik
            'handwerk' => array('handwerk', 'installation', 'reparatur', 'montage', 'renovierung', 'sanierung'),
            'elektrik' => array('elektriker', 'elektro', 'installation', 'strom', 'beleuchtung', 'smart home'),
            'sanitär' => array('sanitär', 'heizung', 'klempner', 'bad', 'dusche', 'wasser', 'rohre'),
            'bau' => array('bau', 'bauen', 'hausbau', 'architekt', 'bauunternehmen', 'neubau', 'umbau'),
            'garten' => array('garten', 'landschaft', 'pflanzen', 'rasenpflege', 'gartenbau', 'outdoor'),
            
            // Gesundheit & Wellness
            'gesundheit' => array('arzt', 'praxis', 'medizin', 'behandlung', 'therapie', 'heilung', 'diagnose'),
            'zahnarzt' => array('zahnarzt', 'zähne', 'zahnpflege', 'implantate', 'bleaching', 'prophylaxe'),
            'wellness' => array('wellness', 'spa', 'massage', 'entspannung', 'beauty', 'kosmetik', 'pflege'),
            'fitness' => array('fitness', 'training', 'sport', 'gym', 'personal trainer', 'abnehmen'),
            'physiotherapie' => array('physiotherapie', 'krankengymnastik', 'rehabilitation', 'rücken', 'schmerzen'),
            
            // Handel & Einzelhandel
            'einzelhandel' => array('shop', 'geschäft', 'verkauf', 'laden', 'boutique', 'store', 'einkauf'),
            'mode' => array('mode', 'kleidung', 'fashion', 'stil', 'bekleidung', 'accessoires', 'schuhe'),
            'elektronik' => array('elektronik', 'computer', 'smartphone', 'technik', 'gadgets', 'software'),
            'auto' => array('auto', 'fahrzeug', 'garage', 'werkstatt', 'reparatur', 'service', 'verkauf'),
            
            // Gastronomie & Hotellerie
            'restaurant' => array('restaurant', 'gastronomie', 'essen', 'küche', 'menü', 'catering', 'bar'),
            'hotel' => array('hotel', 'übernachtung', 'zimmer', 'urlaub', 'ferien', 'tourismus', 'pension'),
            'catering' => array('catering', 'event', 'hochzeit', 'buffet', 'party', 'veranstaltung'),
            
            // Bildung & Kultur
            'bildung' => array('schule', 'bildung', 'unterricht', 'kurs', 'training', 'weiterbildung', 'lernen'),
            'kultur' => array('kultur', 'kunst', 'museum', 'theater', 'konzert', 'veranstaltung', 'event'),
            
            // Transport & Logistik
            'transport' => array('transport', 'umzug', 'logistik', 'lieferung', 'spedition', 'versand'),
            
            // Online & Digital
            'ecommerce' => array('online shop', 'webshop', 'ecommerce', 'bestellen', 'lieferung', 'versand'),
            'software' => array('software', 'app', 'digital', 'programmierung', 'entwicklung', 'it'),
            
            // Finanzdienstleistungen
            'bank' => array('bank', 'kredit', 'finanzierung', 'hypothek', 'anlage', 'investment'),
            
            // Sonstige
            'reinigung' => array('reinigung', 'putzen', 'sauber', 'hygiene', 'gebäudereinigung'),
            'sicherheit' => array('sicherheit', 'alarm', 'überwachung', 'schutz', 'videoüberwachung')
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'allgemein';
    }
    
    /**
     * Content-Typ erkennen
     */
    private static function detect_content_type($content) {
        if (strpos($content, 'anleitung') !== false || strpos($content, 'schritt') !== false || strpos($content, 'tutorial') !== false) {
            return 'anleitung';
        } elseif (strpos($content, 'vergleich') !== false || strpos($content, 'test') !== false || strpos($content, 'vs') !== false) {
            return 'vergleich';
        } elseif (strpos($content, 'news') !== false || strpos($content, 'aktuell') !== false || strpos($content, 'neue') !== false) {
            return 'news';
        } elseif (strpos($content, 'angebot') !== false || strpos($content, 'produkt') !== false || strpos($content, 'service') !== false) {
            return 'kommerziell';
        } else {
            return 'informativ';
        }
    }
    
    /**
     * Search Intent erkennen
     */
    private static function detect_search_intent($content) {
        $commercial_signals = array('kaufen', 'bestellen', 'buchen', 'reservieren', 'preise', 'kosten', 'angebot', 'günstig', 'aktion');
        $informational_signals = array('was', 'wie', 'warum', 'wann', 'wo', 'anleitung', 'tipps', 'guide', 'information');
        $navigational_signals = array('adresse', 'kontakt', 'öffnungszeiten', 'standort', 'telefon', 'website');
        
        $commercial_score = 0;
        $informational_score = 0;
        $navigational_score = 0;
        
        foreach ($commercial_signals as $signal) {
            if (strpos($content, $signal) !== false) $commercial_score++;
        }
        foreach ($informational_signals as $signal) {
            if (strpos($content, $signal) !== false) $informational_score++;
        }
        foreach ($navigational_signals as $signal) {
            if (strpos($content, $signal) !== false) $navigational_score++;
        }
        
        if ($commercial_score >= $informational_score && $commercial_score >= $navigational_score) {
            return 'commercial';
        } elseif ($navigational_score > $informational_score) {
            return 'navigational';
        } else {
            return 'informational';
        }
    }
    
    /**
     * Zielgruppe erkennen
     */
    private static function detect_target_audience($content) {
        if (strpos($content, 'unternehmen') !== false || strpos($content, 'business') !== false || strpos($content, 'b2b') !== false) {
            return 'b2b';
        } elseif (strpos($content, 'familie') !== false || strpos($content, 'kinder') !== false || strpos($content, 'eltern') !== false) {
            return 'familien';
        } elseif (strpos($content, 'senior') !== false || strpos($content, 'älter') !== false) {
            return 'senioren';
        } elseif (strpos($content, 'student') !== false || strpos($content, 'jung') !== false) {
            return 'jugendliche';
        } else {
            return 'allgemein';
        }
    }
    
    /**
     * Content-Ton erkennen
     */
    private static function detect_content_tone($content) {
        if (strpos($content, 'exklusiv') !== false || strpos($content, 'premium') !== false || strpos($content, 'luxus') !== false) {
            return 'premium';
        } elseif (strpos($content, 'günstig') !== false || strpos($content, 'preiswert') !== false || strpos($content, 'sparen') !== false) {
            return 'preisorientiert';
        } elseif (strpos($content, 'schnell') !== false || strpos($content, 'sofort') !== false || strpos($content, 'express') !== false) {
            return 'zeitkritisch';
        } else {
            return 'professionell';
        }
    }
    
    /**
     * Regionale Optimierung aus Settings (nutzt die gewählten Kantone)
     */
    private static function get_regional_optimization($settings) {
        $regional_data = array(
            'target_regions' => array(),
            'regional_keywords' => array(),
            'local_context' => ''
        );
        
        // Gewählte Kantone aus Settings lesen
        $target_cantons = $settings['target_cantons'] ?? array();
        
        if (empty($target_cantons)) {
            $regional_data['local_context'] = 'Schweiz';
            $regional_data['regional_keywords'] = array('Schweiz', 'schweizerisch', 'swiss');
            return $regional_data;
        }
        
        // Erweiterte Kantone-Daten für bessere Keywords
        $canton_data = array(
            'AG' => array('name' => 'Aargau', 'region' => 'Mittelland', 'major_cities' => array('Aarau', 'Baden', 'Wettingen'), 'keywords' => array('Aargau', 'Aarau', 'Mittelland', 'Nordwestschweiz')),
            'AI' => array('name' => 'Appenzell Innerrhoden', 'region' => 'Ostschweiz', 'major_cities' => array('Appenzell'), 'keywords' => array('Appenzell', 'Ostschweiz')),
            'AR' => array('name' => 'Appenzell Ausserrhoden', 'region' => 'Ostschweiz', 'major_cities' => array('Herisau', 'Heiden'), 'keywords' => array('Appenzell', 'Ostschweiz')),
            'BE' => array('name' => 'Bern', 'region' => 'Mittelland', 'major_cities' => array('Bern', 'Köniz', 'Thun', 'Biel', 'Steffisburg'), 'keywords' => array('Bern', 'Bundesstadt', 'Berner Oberland', 'Hauptstadt', 'Mittelland')),
            'BL' => array('name' => 'Basel-Landschaft', 'region' => 'Nordwestschweiz', 'major_cities' => array('Liestal', 'Allschwil', 'Reinach'), 'keywords' => array('Basel-Land', 'Nordwestschweiz', 'Region Basel')),
            'BS' => array('name' => 'Basel-Stadt', 'region' => 'Nordwestschweiz', 'major_cities' => array('Basel'), 'keywords' => array('Basel', 'Dreiländereck', 'Nordwestschweiz', 'Rhein')),
            'FR' => array('name' => 'Freiburg', 'region' => 'Westschweiz', 'major_cities' => array('Freiburg', 'Bulle', 'Murten'), 'keywords' => array('Freiburg', 'Fribourg', 'Westschweiz')),
            'GE' => array('name' => 'Genf', 'region' => 'Westschweiz', 'major_cities' => array('Genf', 'Vernier', 'Lancy'), 'keywords' => array('Genf', 'Genfersee', 'Westschweiz', 'Geneva')),
            'GL' => array('name' => 'Glarus', 'region' => 'Ostschweiz', 'major_cities' => array('Glarus'), 'keywords' => array('Glarus', 'Glarnerland', 'Ostschweiz')),
            'GR' => array('name' => 'Graubünden', 'region' => 'Ostschweiz', 'major_cities' => array('Chur', 'Davos', 'St. Moritz'), 'keywords' => array('Graubünden', 'Chur', 'Bündnerland', 'Alpen')),
            'JU' => array('name' => 'Jura', 'region' => 'Westschweiz', 'major_cities' => array('Delsberg'), 'keywords' => array('Jura', 'Delsberg', 'Westschweiz')),
            'LU' => array('name' => 'Luzern', 'region' => 'Zentralschweiz', 'major_cities' => array('Luzern', 'Emmen', 'Kriens'), 'keywords' => array('Luzern', 'Zentralschweiz', 'Vierwaldstättersee')),
            'NE' => array('name' => 'Neuenburg', 'region' => 'Westschweiz', 'major_cities' => array('Neuenburg', 'La Chaux-de-Fonds'), 'keywords' => array('Neuenburg', 'Neuchâtel', 'Westschweiz')),
            'NW' => array('name' => 'Nidwalden', 'region' => 'Zentralschweiz', 'major_cities' => array('Stans'), 'keywords' => array('Nidwalden', 'Zentralschweiz', 'Innerschweiz')),
            'OW' => array('name' => 'Obwalden', 'region' => 'Zentralschweiz', 'major_cities' => array('Sarnen'), 'keywords' => array('Obwalden', 'Zentralschweiz', 'Innerschweiz')),
            'SG' => array('name' => 'St. Gallen', 'region' => 'Ostschweiz', 'major_cities' => array('St. Gallen', 'Rapperswil', 'Wil'), 'keywords' => array('St. Gallen', 'Ostschweiz', 'Bodensee')),
            'SH' => array('name' => 'Schaffhausen', 'region' => 'Nordschweiz', 'major_cities' => array('Schaffhausen'), 'keywords' => array('Schaffhausen', 'Rheinfall', 'Nordschweiz')),
            'SO' => array('name' => 'Solothurn', 'region' => 'Mittelland', 'major_cities' => array('Solothurn', 'Olten', 'Grenchen'), 'keywords' => array('Solothurn', 'Mittelland', 'Jura')),
            'SZ' => array('name' => 'Schwyz', 'region' => 'Zentralschweiz', 'major_cities' => array('Schwyz', 'Einsiedeln'), 'keywords' => array('Schwyz', 'Zentralschweiz', 'Innerschweiz')),
            'TG' => array('name' => 'Thurgau', 'region' => 'Ostschweiz', 'major_cities' => array('Frauenfeld', 'Kreuzlingen'), 'keywords' => array('Thurgau', 'Ostschweiz', 'Bodensee')),
            'TI' => array('name' => 'Tessin', 'region' => 'Südschweiz', 'major_cities' => array('Bellinzona', 'Lugano', 'Locarno'), 'keywords' => array('Tessin', 'Ticino', 'Südschweiz', 'Lago Maggiore')),
            'UR' => array('name' => 'Uri', 'region' => 'Zentralschweiz', 'major_cities' => array('Altdorf'), 'keywords' => array('Uri', 'Zentralschweiz', 'Innerschweiz', 'Gotthard')),
            'VD' => array('name' => 'Waadt', 'region' => 'Westschweiz', 'major_cities' => array('Lausanne', 'Yverdon', 'Montreux'), 'keywords' => array('Waadt', 'Vaud', 'Genfersee', 'Westschweiz', 'Lausanne')),
            'VS' => array('name' => 'Wallis', 'region' => 'Alpenregion', 'major_cities' => array('Sion', 'Martigny', 'Brig'), 'keywords' => array('Wallis', 'Valais', 'Alpen', 'Rhonetal')),
            'ZG' => array('name' => 'Zug', 'region' => 'Zentralschweiz', 'major_cities' => array('Zug', 'Baar'), 'keywords' => array('Zug', 'Zentralschweiz', 'Zugersee')),
            'ZH' => array('name' => 'Zürich', 'region' => 'Ostschweiz', 'major_cities' => array('Zürich', 'Winterthur', 'Uster', 'Dübendorf'), 'keywords' => array('Zürich', 'Limmattal', 'Grossraum Zürich', 'Wirtschaftszentrum'))
        );
        
        // Regionale Keywords basierend auf gewählten Kantonen generieren
        foreach ($target_cantons as $canton_code) {
            if (isset($canton_data[$canton_code])) {
                $canton_info = $canton_data[$canton_code];
                $regional_data['target_regions'][] = $canton_info['name'];
                $regional_data['regional_keywords'] = array_merge($regional_data['regional_keywords'], $canton_info['keywords']);
                $regional_data['regional_keywords'] = array_merge($regional_data['regional_keywords'], $canton_info['major_cities']);
            }
        }
        
        // Lokalen Kontext bestimmen
        if (count($target_cantons) === 1) {
            $canton_info = $canton_data[$target_cantons[0]] ?? null;
            $regional_data['local_context'] = $canton_info ? $canton_info['name'] : 'Schweiz';
        } elseif (count($target_cantons) <= 3) {
            $regional_data['local_context'] = implode(', ', $regional_data['target_regions']);
        } else {
            $regional_data['local_context'] = 'Schweiz';
        }
        
        $regional_data['regional_keywords'] = array_unique($regional_data['regional_keywords']);
        
        return $regional_data;
    }
    
    /**
     * Universelle Traffic-Keywords generieren
     */
    private static function generate_universal_traffic_keywords($analysis) {
        $base_traffic_keywords = array(
            'commercial' => array('beste', 'günstig', 'top', 'empfehlung', 'angebot', 'aktion', 'neu', 'jetzt', 'sofort'),
            'informational' => array('tipps', 'anleitung', 'guide', 'hilfe', 'einfach', 'schritt für schritt', 'kostenlos'),
            'navigational' => array('kontakt', 'adresse', 'öffnungszeiten', 'telefon', 'vor ort', 'in der nähe')
        );
        
        $search_intent = $analysis['search_intent'];
        $traffic_keywords = $base_traffic_keywords[$search_intent] ?? $base_traffic_keywords['commercial'];
        
        // Business-spezifische Keywords hinzufügen
        $business_category = $analysis['business_category'];
        $business_keywords = array(
            'beratung' => array('professionell', 'erfahren', 'kompetent', 'kostenlose beratung'),
            'einzelhandel' => array('shopping', 'sale', 'rabatt', 'versandkostenfrei'),
            'restaurant' => array('reservieren', 'frisch', 'regional', 'gemütlich'),
            'hotel' => array('buchen', 'zentral', 'komfortabel', 'wellness'),
            'gesundheit' => array('termin', 'behandlung', 'heilung', 'vertrauen'),
            'handwerk' => array('zuverlässig', 'schnell', 'qualität', 'garantie')
        );
        
        if (isset($business_keywords[$business_category])) {
            $traffic_keywords = array_merge($traffic_keywords, $business_keywords[$business_category]);
        }
        
        return array_unique($traffic_keywords);
    }
    
    /**
     * Universellen Prompt zusammenbauen
     */
    private static function build_universal_prompt($analysis, $regional_data, $traffic_keywords, $settings) {
        $prompt_parts = array();
        
        $main_topic = !empty($analysis['main_keywords']) ? $analysis['main_keywords'][0] : 'Ihr Angebot';
        $business_category = $analysis['business_category'];
        $search_intent = $analysis['search_intent'];
        $local_context = $regional_data['local_context'];
        
        // Header
        $prompt_parts[] = "Erstelle traffic-optimierte SEO-Meta-Texte für: {$main_topic}";
        $prompt_parts[] = "";
        
        // REGIONALE OPTIMIERUNG (aus gewählten Kantonen)
        if (!empty($regional_data['regional_keywords'])) {
            $prompt_parts[] = "📍 REGIONALE OPTIMIERUNG:";
            $prompt_parts[] = "- Zielregion: {$local_context}";
            $prompt_parts[] = "- Lokale Keywords: " . implode(', ', array_slice($regional_data['regional_keywords'], 0, 6));
            $prompt_parts[] = "- Fokus: Lokale Sichtbarkeit in der gewählten Region";
            $prompt_parts[] = "";
        }
        
        // TRAFFIC-OPTIMIERUNG
        $prompt_parts[] = "🎯 TRAFFIC-OPTIMIERUNG:";
        $prompt_parts[] = "- High-Traffic Keywords: " . implode(', ', array_slice($traffic_keywords, 0, 6));
        $prompt_parts[] = "- Suchintention: " . ucfirst($search_intent);
        
        if ($business_category !== 'allgemein') {
            $prompt_parts[] = "- Branche: " . ucfirst(str_replace('_', ' ', $business_category));
        }
        $prompt_parts[] = "";
        
        // SEO-TECHNISCHE ANFORDERUNGEN
        $prompt_parts[] = "⚙️ SEO-ANFORDERUNGEN:";
        $prompt_parts[] = "- Meta-Titel: Max. 55 Zeichen, Haupt-Keyword am Anfang";
        $prompt_parts[] = "- Meta-Description: 150-155 Zeichen mit überzeugender Call-to-Action";
        $prompt_parts[] = "- Focus-Keyword: Natürlich integriert, regional optimiert";
        $prompt_parts[] = "- Sprache: Schweizer Hochdeutsch, verständlich und vertrauensvoll";
        $prompt_parts[] = "";
        
        // INTENT-SPEZIFISCHE OPTIMIERUNG
        $prompt_parts[] = "🎯 OPTIMIERUNG FÜR {$search_intent}:";
        switch ($search_intent) {
            case 'commercial':
                $prompt_parts[] = "- Meta-Titel: Kaufsignale einbauen ('Beste', 'Günstig', 'Jetzt')";
                $prompt_parts[] = "- Description: Klare Vorteile und starke Call-to-Action";
                break;
            case 'informational':
                $prompt_parts[] = "- Meta-Titel: Informative Keywords ('Tipps', 'Anleitung', 'Guide')";
                $prompt_parts[] = "- Description: Wissen und Expertise betonen";
                break;
            case 'navigational':
                $prompt_parts[] = "- Meta-Titel: Firmenname und lokale Begriffe";
                $prompt_parts[] = "- Description: Standort und Kontaktmöglichkeiten";
                break;
        }
        $prompt_parts[] = "";
        
        // BUSINESS-CONTEXT
        if (!empty($settings['business_context'])) {
            $prompt_parts[] = "🏢 BUSINESS-KONTEXT: " . $settings['business_context'];
            $prompt_parts[] = "";
        }
        
        $prompt_parts[] = "🚀 ZIEL: Maximiere Click-Through-Rate für mehr qualifizierte Besucher aus {$local_context}!";
        
        return implode("\n", $prompt_parts);
    }
    
    /**
     * Basic Fallback für leeren Content
     */
    private static function generate_basic_fallback($settings) {
        $regional_data = self::get_regional_optimization($settings);
        $local_context = $regional_data['local_context'];
        
        $prompt_parts = array();
        $prompt_parts[] = "Erstelle professionelle SEO-Meta-Texte";
        $prompt_parts[] = "";
        $prompt_parts[] = "REGIONAL: Optimiert für {$local_context}";
        $prompt_parts[] = "ANFORDERUNGEN:";
        $prompt_parts[] = "- Meta-Titel: Max. 55 Zeichen, regional optimiert";
        $prompt_parts[] = "- Meta-Description: 150-155 Zeichen mit Call-to-Action";
        $prompt_parts[] = "- Sprache: Schweizer Hochdeutsch";
        
        if (!empty($settings['business_context'])) {
            $prompt_parts[] = "- Business: " . $settings['business_context'];
        }
        
        return implode("\n", $prompt_parts);
    }
    
    /**
     * Test Research Capabilities
     */
    public static function test_research_capabilities() {
        $start_time = microtime(true);
        
        try {
            // Test mit Beispiel-Content
            $test_content = "IT-Beratung für Schweizer KMU. Professionelle Beratung in Zürich und Bern.";
            $test_settings = array(
                'business_context' => 'IT-Beratung',
                'target_cantons' => array('ZH', 'BE')
            );
            
            $prompt = self::create_super_prompt($test_content, $test_settings);
            
            $end_time = microtime(true);
            $execution_time = round($end_time - $start_time, 3);
            
            return array(
                'prompt_generation' => !empty($prompt),
                'execution_time' => $execution_time,
                'prompt_length' => strlen($prompt)
            );
            
        } catch (Exception $e) {
            return array(
                'prompt_generation' => false,
                'error' => $e->getMessage()
            );
        }
    }
}

/**
 * Helper-Funktion für globalen Zugriff
 */
function retexify_get_keyword_research() {
    return new ReTexify_Intelligent_Keyword_Research();
}