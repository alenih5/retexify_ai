<?php
/**
 * ReTexify Intelligent Keyword Research - Hauptklasse
 * 
 * Koordiniert die verschiedenen Analyse-Komponenten und stellt die öffentliche API bereit
 * 
 * @package ReTexify_AI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Intelligent_Keyword_Research {
    
    /**
     * Debug-Modus
     */
    private static $debug_mode = false;
    
    /**
     * Text-Processor Instanz
     */
    private static $text_processor = null;
    
    /**
     * Keyword-Analyzer Instanz
     */
    private static $keyword_analyzer = null;
    
    /**
     * Content-Classifier Instanz
     */
    private static $content_classifier = null;
    
    /**
     * Swiss-Local-Analyzer Instanz
     */
    private static $swiss_analyzer = null;
    
    /**
     * Keyword-Strategy Instanz
     */
    private static $strategy_generator = null;
    
    /**
     * Initialisiert die Komponenten
     */
    private static function init_components() {
        if (self::$text_processor === null) {
            require_once RETEXIFY_PLUGIN_PATH . 'includes/class-german-text-processor.php';
            self::$text_processor = new ReTexify_German_Text_Processor();
        }
        
        if (self::$keyword_analyzer === null) {
            require_once RETEXIFY_PLUGIN_PATH . 'includes/class-keyword-analyzer.php';
            self::$keyword_analyzer = new ReTexify_Keyword_Analyzer();
        }
        
        if (self::$content_classifier === null) {
            require_once RETEXIFY_PLUGIN_PATH . 'includes/class-content-classifier.php';
            self::$content_classifier = new ReTexify_Content_Classifier();
        }
        
        if (self::$swiss_analyzer === null) {
            require_once RETEXIFY_PLUGIN_PATH . 'includes/class-swiss-local-analyzer.php';
            self::$swiss_analyzer = new ReTexify_Swiss_Local_Analyzer();
        }
        
        if (self::$strategy_generator === null) {
            require_once RETEXIFY_PLUGIN_PATH . 'includes/class-keyword-strategy.php';
            self::$strategy_generator = new ReTexify_Keyword_Strategy();
        }
    }
    
    /**
     * Hauptmethode für intelligente Keyword-Analyse
     * 
     * @param string $content Content zu analysieren
     * @param array $settings Plugin-Settings
     * @return array Vollständige Analyse
     */
    public static function analyze_content($content, $settings = array()) {
        self::init_components();
        
        $start_time = microtime(true);
        
        try {
            // 1. Text vorverarbeiten
            $processed_text = self::$text_processor->process_text($content);
            
            // 2. Keyword-Analyse durchführen
            $keyword_analysis = self::$keyword_analyzer->analyze_keywords($processed_text);
            
            // 3. Content klassifizieren
            $content_classification = self::$content_classifier->classify_content($processed_text, $keyword_analysis);
            
            // 4. Schweizer Relevanz analysieren
            $swiss_analysis = self::$swiss_analyzer->analyze_swiss_relevance($processed_text, $settings);
            
            // 5. Keyword-Strategie entwickeln
            $keyword_strategy = self::$strategy_generator->develop_strategy($keyword_analysis, $content_classification, $swiss_analysis, $settings);
            
            // 6. Ergebnisse zusammenführen
            $analysis = array_merge(
                $keyword_analysis,
                $content_classification,
                $swiss_analysis,
                array(
                    'keyword_strategy' => $keyword_strategy,
                    'processing_time' => microtime(true) - $start_time,
                    'analysis_timestamp' => current_time('mysql')
                )
            );
            
            return $analysis;
            
        } catch (Exception $e) {
            error_log('ReTexify Keyword Research Error: ' . $e->getMessage());
            return self::get_fallback_analysis($content, $settings);
        }
    }
    
    /**
     * Premium SEO Prompt erstellen
     * 
     * @param string $content Content
     * @param array $settings Settings
     * @return string Optimierter Prompt
     */
    public static function create_premium_seo_prompt($content, $settings = array()) {
        self::init_components();
        
        $analysis = self::analyze_content($content, $settings);
        
        return self::$strategy_generator->create_premium_prompt($content, $analysis, $settings);
    }
    
    /**
     * Super Prompt erstellen
     * 
     * @param string $content Content
     * @param array $settings Settings
     * @return string Super Prompt
     */
    public static function create_super_prompt($content, $settings = array()) {
        self::init_components();
        
        $analysis = self::analyze_content($content, $settings);
        
        return self::$strategy_generator->create_super_prompt($content, $analysis, $settings);
    }
    
    /**
     * Universal Traffic Prompt erstellen
     * 
     * @param string $content Content
     * @param array $settings Settings
     * @return string Universal Traffic Prompt
     */
    public static function create_universal_traffic_prompt($content, $settings = array()) {
        self::init_components();
        
        $analysis = self::analyze_content($content, $settings);
        
        return self::$strategy_generator->create_universal_traffic_prompt($content, $analysis, $settings);
    }
    
    /**
     * Research-Capabilities testen
     * 
     * @return array Capabilities-Test
     */
    public static function test_research_capabilities() {
        self::init_components();
        
        $capabilities = array(
            'core_features' => array(
                'enhanced_engine_active' => true,
                'german_nlp_optimized' => true,
                'swiss_optimization_ready' => true,
                'settings_integration_active' => true,
                'competitive_intelligence' => true,
                'api_integration_ready' => class_exists('ReTexify_API_Manager')
            ),
            'component_status' => array(
                'text_processor' => is_object(self::$text_processor),
                'keyword_analyzer' => is_object(self::$keyword_analyzer),
                'content_classifier' => is_object(self::$content_classifier),
                'swiss_analyzer' => is_object(self::$swiss_analyzer),
                'strategy_generator' => is_object(self::$strategy_generator)
            ),
            'performance_features' => array(
                'caching_system' => true,
                'quality_assessment' => true,
                'failsafe_prompts' => true,
                'debug_mode' => self::$debug_mode,
                'time_optimization' => true
            )
        );
        
        // Test-Prompt generieren
        try {
            $test_content = "Professionelle Beratung für Schweizer Unternehmen in Bern und Umgebung.";
            $test_settings = array(
                'business_context' => 'Beratungsunternehmen',
                'target_audience' => 'KMU',
                'brand_voice' => 'professional',
                'target_cantons' => array('BE'),
                'optimization_focus' => 'complete_seo'
            );
            
            $test_prompt = self::create_premium_seo_prompt($test_content, $test_settings);
            $capabilities['test_results'] = array(
                'prompt_generation' => !empty($test_prompt),
                'prompt_length' => strlen($test_prompt),
                'settings_integrated' => (strpos($test_prompt, 'Beratungsunternehmen') !== false),
                'swiss_content' => (strpos($test_prompt, 'Bern') !== false || strpos($test_prompt, 'Schweiz') !== false)
            );
        } catch (Exception $e) {
            $capabilities['test_results'] = array(
                'error' => $e->getMessage(),
                'prompt_generation' => false
            );
        }
        
        return $capabilities;
    }
    
    /**
     * Fallback-Analyse bei Fehlern
     * 
     * @param string $content Content
     * @param array $settings Settings
     * @return array Basis-Analyse
     */
    private static function get_fallback_analysis($content, $settings = array()) {
        return array(
            'word_count' => str_word_count($content),
            'main_topic' => 'content',
            'primary_keywords' => array(),
            'long_tail_keywords' => array(),
            'semantic_themes' => array(),
            'content_quality' => array('overall_score' => 50),
            'readability_score' => 60,
            'swiss_relevance' => 0,
            'keyword_strategy' => array(
                'focus_keyword' => '',
                'primary_variations' => array(),
                'strategy_confidence' => 0
            ),
            'processing_time' => 0,
            'analysis_timestamp' => current_time('mysql'),
            'error' => 'Fallback analysis used'
        );
    }
    
    /**
     * Debug-Modus aktivieren/deaktivieren
     * 
     * @param bool $enabled Debug-Modus
     */
    public static function set_debug_mode($enabled = true) {
        self::$debug_mode = $enabled;
    }
    
    // ===== ADVANCED SEO FEATURES - NEUE METHODEN =====
    
    /**
     * Verwandte Keywords von Google Suggest abrufen
     * 
     * @param string $keyword Haupt-Keyword
     * @param int $limit Maximale Anzahl Keywords
     * @return array Verwandte Keywords
     */
    public static function get_related_keywords($keyword, $limit = 10) {
        $cache_key = 'retexify_related_keywords_' . md5($keyword . '_' . $limit);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $related_keywords = array();
        
        try {
            // Google Suggest API simulieren
            $suggest_url = 'https://www.google.com/complete/search?client=firefox&q=' . urlencode($keyword);
            
            $response = wp_remote_get($suggest_url, array(
                'timeout' => 10,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ));
            
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                if (isset($data[1]) && is_array($data[1])) {
                    $related_keywords = array_slice($data[1], 0, $limit);
                }
            }
            
        } catch (Exception $e) {
            error_log('ReTexify Keyword Research: Google Suggest Error - ' . $e->getMessage());
            
            // Fallback: Einfache Keyword-Variationen generieren
            $related_keywords = self::generate_fallback_related_keywords($keyword, $limit);
        }
        
        // Cache speichern (24 Stunden)
        set_transient($cache_key, $related_keywords, DAY_IN_SECONDS);
        
        return $related_keywords;
    }
    
    /**
     * LSI Keywords generieren
     * 
     * @param string $keyword Haupt-Keyword
     * @param array $settings Plugin-Settings
     * @return array LSI Keywords
     */
    public static function generate_lsi_keywords($keyword, $settings = array()) {
        $cache_key = 'retexify_lsi_keywords_' . md5($keyword . '_' . serialize($settings));
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $lsi_keywords = array();
        
        try {
            // 1. Verwandte Keywords abrufen
            $related = self::get_related_keywords($keyword, 20);
            
            // 2. Semantische Variationen generieren
            $semantic_variations = self::generate_semantic_variations($keyword, $settings);
            
            // 3. Schweizer-spezifische Keywords hinzufügen
            $swiss_keywords = self::generate_swiss_lsi_keywords($keyword, $settings);
            
            // 4. Alles kombinieren und filtern
            $all_keywords = array_merge($related, $semantic_variations, $swiss_keywords);
            $lsi_keywords = array_unique(array_filter($all_keywords));
            
            // 5. Nach Relevanz sortieren
            $lsi_keywords = self::rank_lsi_keywords($lsi_keywords, $keyword, $settings);
            
        } catch (Exception $e) {
            error_log('ReTexify Keyword Research: LSI Generation Error - ' . $e->getMessage());
            
            // Fallback
            $lsi_keywords = self::generate_fallback_lsi_keywords($keyword);
        }
        
        // Cache speichern (7 Tage)
        set_transient($cache_key, $lsi_keywords, WEEK_IN_SECONDS);
        
        return array_slice($lsi_keywords, 0, 15);
    }
    
    /**
     * Long-Tail Keywords generieren
     * 
     * @param string $keyword Haupt-Keyword
     * @param int $count Anzahl Keywords
     * @return array Long-Tail Keywords
     */
    public static function generate_long_tail_keywords($keyword, $count = 10) {
        $cache_key = 'retexify_longtail_' . md5($keyword . '_' . $count);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $long_tail_keywords = array();
        
        // Deutsche Long-Tail-Modifier
        $modifiers = array(
            'wie', 'was', 'warum', 'wo', 'wann', 'wer', 'welche', 'welcher',
            'beste', 'top', 'günstig', 'billig', 'teuer', 'preiswert',
            'kaufen', 'bestellen', 'online', 'shop', 'store',
            'anleitung', 'tipp', 'tutorial', 'guide', 'ratgeber',
            '2024', '2025', 'neu', 'aktuell', 'modern',
            'beratung', 'service', 'support', 'hilfe'
        );
        
        // Long-Tail-Kombinationen generieren
        foreach ($modifiers as $modifier) {
            $long_tail_keywords[] = $modifier . ' ' . $keyword;
            $long_tail_keywords[] = $keyword . ' ' . $modifier;
        }
        
        // Schweizer-spezifische Long-Tail-Keywords
        $swiss_modifiers = array('schweiz', 'schweizer', 'ch', 'kanton', 'region');
        foreach ($swiss_modifiers as $modifier) {
            $long_tail_keywords[] = $keyword . ' ' . $modifier;
        }
        
        // Cache speichern (24 Stunden)
        set_transient($cache_key, $long_tail_keywords, DAY_IN_SECONDS);
        
        return array_slice($long_tail_keywords, 0, $count);
    }
    
    /**
     * Google Trends Analyse (vereinfacht)
     * 
     * @param string $keyword Keyword
     * @param string $location Standort (CH, DE, AT)
     * @return array Trends-Daten
     */
    public static function analyze_google_trends($keyword, $location = 'CH') {
        $cache_key = 'retexify_trends_' . md5($keyword . '_' . $location);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return $cached_result;
        }
        
        $trends_data = array(
            'keyword' => $keyword,
            'location' => $location,
            'trend_score' => 50, // 0-100
            'trend_direction' => 'stable', // rising, falling, stable
            'seasonality' => 'none', // high, medium, low, none
            'competition_level' => 'medium', // high, medium, low
            'search_volume_estimate' => 'medium' // high, medium, low
        );
        
        try {
            // Vereinfachte Trend-Analyse basierend auf Keyword-Eigenschaften
            $trends_data['trend_score'] = self::estimate_trend_score($keyword);
            $trends_data['trend_direction'] = self::estimate_trend_direction($keyword);
            $trends_data['seasonality'] = self::detect_seasonality($keyword);
            $trends_data['competition_level'] = self::estimate_competition_level($keyword);
            $trends_data['search_volume_estimate'] = self::estimate_search_volume($keyword);
            
        } catch (Exception $e) {
            error_log('ReTexify Keyword Research: Trends Analysis Error - ' . $e->getMessage());
        }
        
        // Cache speichern (24 Stunden)
        set_transient($cache_key, $trends_data, DAY_IN_SECONDS);
        
        return $trends_data;
    }
    
    /**
     * Suchintention klassifizieren
     * 
     * @param string $keyword Keyword
     * @return string Suchintention
     */
    public static function classify_search_intent($keyword) {
        $keyword_lower = strtolower($keyword);
        
        // Transactional Keywords
        $transactional_patterns = array(
            '/\b(kaufen|bestellen|preis|kosten|shop|store|online|günstig|billig)\b/',
            '/\b(anbieter|lieferant|hersteller|vertrieb)\b/'
        );
        
        foreach ($transactional_patterns as $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                return 'Transactional';
            }
        }
        
        // Navigational Keywords
        $navigational_patterns = array(
            '/\b(website|homepage|kontakt|impressum|öffnungszeiten)\b/',
            '/\b(firma|unternehmen|geschäft)\b/'
        );
        
        foreach ($navigational_patterns as $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                return 'Navigational';
            }
        }
        
        // Informational Keywords
        $informational_patterns = array(
            '/\b(was|wie|warum|wo|wann|wer|tutorial|anleitung|tipp|guide)\b/',
            '/\b(definition|bedeutung|erklärung|hilfe)\b/'
        );
        
        foreach ($informational_patterns as $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                return 'Informational';
            }
        }
        
        // Commercial Investigation
        $commercial_patterns = array(
            '/\b(vergleich|test|bewertung|review|empfehlung)\b/',
            '/\b(beste|top|qualität|marken)\b/'
        );
        
        foreach ($commercial_patterns as $pattern) {
            if (preg_match($pattern, $keyword_lower)) {
                return 'Commercial Investigation';
            }
        }
        
        return 'Informational'; // Default
    }
    
    /**
     * Keyword-Schwierigkeit schätzen
     * 
     * @param string $keyword Keyword
     * @return array Schwierigkeits-Daten
     */
    public static function estimate_keyword_difficulty($keyword) {
        $word_count = str_word_count($keyword);
        $char_count = strlen($keyword);
        
        $difficulty_score = 0;
        
        // Basierend auf Wortanzahl
        if ($word_count == 1) {
            $difficulty_score += 80; // Einzelwörter sind meist schwieriger
        } elseif ($word_count == 2) {
            $difficulty_score += 50;
        } else {
            $difficulty_score += 20; // Long-Tail ist einfacher
        }
        
        // Basierend auf Charakterlänge
        if ($char_count < 10) {
            $difficulty_score += 30;
        } elseif ($char_count > 30) {
            $difficulty_score -= 20;
        }
        
        // Basierend auf speziellen Begriffen
        $competitive_terms = array('kaufen', 'preis', 'günstig', 'beste', 'top');
        foreach ($competitive_terms as $term) {
            if (stripos($keyword, $term) !== false) {
                $difficulty_score += 25;
                break;
            }
        }
        
        $difficulty_score = max(0, min(100, $difficulty_score));
        
        if ($difficulty_score >= 70) {
            $level = 'Hoch';
        } elseif ($difficulty_score >= 40) {
            $level = 'Mittel';
        } else {
            $level = 'Niedrig';
        }
        
        return array(
            'score' => $difficulty_score,
            'level' => $level,
            'word_count' => $word_count,
            'char_count' => $char_count,
            'recommendation' => self::get_difficulty_recommendation($level)
        );
    }
    
    // ===== HILFSMETHODEN FÜR ADVANCED FEATURES =====
    
    private static function generate_fallback_related_keywords($keyword, $limit) {
        $variations = array();
        $words = explode(' ', $keyword);
        
        if (count($words) > 1) {
            $variations[] = implode(' ', array_reverse($words));
        }
        
        // Einfache Variationen
        $variations[] = $keyword . ' schweiz';
        $variations[] = $keyword . ' online';
        $variations[] = 'beste ' . $keyword;
        $variations[] = $keyword . ' preis';
        
        return array_slice($variations, 0, $limit);
    }
    
    private static function generate_semantic_variations($keyword, $settings) {
        $variations = array();
        
        // Branche-spezifische Synonyme
        $industry = $settings['industry'] ?? '';
        
        if ($industry === 'Beratung' || $industry === 'Consulting') {
            $variations[] = str_replace('beratung', 'consulting', $keyword);
            $variations[] = str_replace('beratung', 'coaching', $keyword);
        }
        
        if ($industry === 'Technologie' || $industry === 'IT') {
            $variations[] = str_replace('software', 'programm', $keyword);
            $variations[] = str_replace('app', 'anwendung', $keyword);
        }
        
        return $variations;
    }
    
    private static function generate_swiss_lsi_keywords($keyword, $settings) {
        $swiss_keywords = array();
        $cantons = $settings['selected_cantons'] ?? array();
        
        foreach ($cantons as $canton) {
            $swiss_keywords[] = $keyword . ' ' . $canton;
            $swiss_keywords[] = $keyword . ' schweiz ' . $canton;
        }
        
        // Schweizer-spezifische Begriffe
        $swiss_terms = array('schweiz', 'schweizer', 'ch', 'helvetia', 'alpen');
        foreach ($swiss_terms as $term) {
            $swiss_keywords[] = $keyword . ' ' . $term;
        }
        
        return $swiss_keywords;
    }
    
    private static function rank_lsi_keywords($keywords, $main_keyword, $settings) {
        $ranked = array();
        
        foreach ($keywords as $keyword) {
            $score = 0;
            
            // Ähnlichkeit zum Haupt-Keyword
            similar_text($main_keyword, $keyword, $similarity);
            $score += $similarity;
            
            // Schweizer-Relevanz
            if (stripos($keyword, 'schweiz') !== false || stripos($keyword, 'ch') !== false) {
                $score += 20;
            }
            
            // Kantons-Relevanz
            $cantons = $settings['selected_cantons'] ?? array();
            foreach ($cantons as $canton) {
                if (stripos($keyword, $canton) !== false) {
                    $score += 15;
                }
            }
            
            // Länge-Bonus (Long-Tail)
            if (str_word_count($keyword) > str_word_count($main_keyword)) {
                $score += 10;
            }
            
            $ranked[$keyword] = $score;
        }
        
        arsort($ranked);
        return array_keys($ranked);
    }
    
    private static function generate_fallback_lsi_keywords($keyword) {
        return array(
            $keyword . ' schweiz',
            $keyword . ' online',
            'beste ' . $keyword,
            $keyword . ' preis',
            $keyword . ' service',
            $keyword . ' beratung',
            $keyword . ' anbieter'
        );
    }
    
    private static function estimate_trend_score($keyword) {
        $score = 50; // Neutral
        
        // Aktuelle Begriffe
        $trending_terms = array('2024', '2025', 'neu', 'digital', 'nachhaltig', 'klima');
        foreach ($trending_terms as $term) {
            if (stripos($keyword, $term) !== false) {
                $score += 20;
            }
        }
        
        // Saisonale Begriffe
        $seasonal_terms = array('sommer', 'winter', 'weihnachten', 'ostern', 'urlaub');
        foreach ($seasonal_terms as $term) {
            if (stripos($keyword, $term) !== false) {
                $score += 15;
            }
        }
        
        return min(100, $score);
    }
    
    private static function estimate_trend_direction($keyword) {
        $trending_indicators = array('neu', '2024', '2025', 'digital', 'nachhaltig');
        
        foreach ($trending_indicators as $indicator) {
            if (stripos($keyword, $indicator) !== false) {
                return 'rising';
            }
        }
        
        return 'stable';
    }
    
    private static function detect_seasonality($keyword) {
        $seasonal_keywords = array(
            'sommer' => array('sommer', 'urlaub', 'reise', 'strand'),
            'winter' => array('winter', 'skifahren', 'schnee', 'weihnachten'),
            'ostern' => array('ostern', 'ostern', 'hasen'),
            'general' => array('jahreszeit', 'saison')
        );
        
        foreach ($seasonal_keywords as $season => $terms) {
            foreach ($terms as $term) {
                if (stripos($keyword, $term) !== false) {
                    return $season === 'general' ? 'medium' : 'high';
                }
            }
        }
        
        return 'none';
    }
    
    private static function estimate_competition_level($keyword) {
        $competitive_terms = array('kaufen', 'preis', 'günstig', 'beste', 'top', 'shop');
        $word_count = str_word_count($keyword);
        
        foreach ($competitive_terms as $term) {
            if (stripos($keyword, $term) !== false) {
                return 'high';
            }
        }
        
        if ($word_count == 1) {
            return 'high';
        } elseif ($word_count >= 3) {
            return 'low';
        }
        
        return 'medium';
    }
    
    private static function estimate_search_volume($keyword) {
        $word_count = str_word_count($keyword);
        
        // Einzelwörter haben meist höheres Suchvolumen
        if ($word_count == 1) {
            return 'high';
        } elseif ($word_count == 2) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private static function get_difficulty_recommendation($level) {
        switch ($level) {
            case 'Hoch':
                return 'Verwenden Sie Long-Tail-Variationen oder fokussieren Sie auf Nischen-Keywords.';
            case 'Mittel':
                return 'Gute Balance zwischen Suchvolumen und Wettbewerb. Optimieren Sie Content-Qualität.';
            case 'Niedrig':
                return 'Einfach zu ranken, aber möglicherweise niedriges Suchvolumen.';
            default:
                return 'Weitere Analyse empfohlen.';
        }
    }
} 