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
} 