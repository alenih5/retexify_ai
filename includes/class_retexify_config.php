<?php
/**
 * ReTexify AI Pro - Konfigurationsklasse
 * 
 * Zentrale Konfigurationsverwaltung für bessere Wartbarkeit
 * 
 * @package ReTexify_AI_Pro
 * @since 4.23.0
 * @version 4.23.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Config {
    
    /**
     * Plugin-Konstanten
     */
    const VERSION = '4.23.0';
    const MIN_WP_VERSION = '5.0';
    const MIN_PHP_VERSION = '7.4';
    
    /**
     * Standard-Einstellungen
     */
    private static $default_settings = array(
        'ai' => array(
            'api_provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'max_tokens' => 2000,
            'temperature' => 0.7,
            'default_language' => 'de-ch',
            'optimization_focus' => 'complete_seo'
        ),
        'business' => array(
            'business_context' => 'Ihr Unternehmen in der Schweiz',
            'target_audience' => 'Schweizer Kunden und Interessenten',
            'brand_voice' => 'professional',
            'target_cantons' => array('BE', 'ZH', 'LU', 'SG'),
            'use_swiss_german' => true
        ),
        'export_import' => array(
            'max_file_size' => '10MB',
            'allowed_extensions' => array('csv'),
            'cleanup_after_hours' => 24,
            'download_expiry_hours' => 24
        ),
        'performance' => array(
            'cache_duration' => 3600,
            'cleanup_frequency' => 'daily',
            'minify_assets' => false
        )
    );
    
    /**
     * KI-Provider-Konfiguration
     */
    private static $ai_providers = array(
        'openai' => array(
            'name' => 'OpenAI (GPT-4o, GPT-4o Mini)',
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'key_format' => '/^sk-/',
            'key_example' => 'sk-proj-...',
            'signup_url' => 'https://platform.openai.com/api-keys',
            'models' => array(
                'gpt-4o-mini' => array(
                    'name' => 'GPT-4o Mini (Empfohlen - Günstig & Schnell)',
                    'cost_per_1k_tokens' => array('input' => 0.15, 'output' => 0.60),
                    'max_tokens' => 128000,
                    'features' => array('text', 'fast', 'economical')
                ),
                'gpt-4o' => array(
                    'name' => 'GPT-4o (Premium - Beste Qualität)',
                    'cost_per_1k_tokens' => array('input' => 2.50, 'output' => 10.00),
                    'max_tokens' => 128000,
                    'features' => array('text', 'premium', 'multimodal')
                ),
                'o1-mini' => array(
                    'name' => 'o1 Mini (Reasoning - Sehr smart)',
                    'cost_per_1k_tokens' => array('input' => 3.00, 'output' => 12.00),
                    'max_tokens' => 65536,
                    'features' => array('reasoning', 'complex_tasks')
                ),
                'gpt-4-turbo' => array(
                    'name' => 'GPT-4 Turbo (Ausgewogen)',
                    'cost_per_1k_tokens' => array('input' => 10.00, 'output' => 30.00),
                    'max_tokens' => 128000,
                    'features' => array('text', 'balanced')
                ),
                'gpt-3.5-turbo' => array(
                    'name' => 'GPT-3.5 Turbo (Budget)',
                    'cost_per_1k_tokens' => array('input' => 0.50, 'output' => 1.50),
                    'max_tokens' => 16385,
                    'features' => array('text', 'economical')
                )
            )
        ),
        'anthropic' => array(
            'name' => 'Anthropic Claude (3.5 Sonnet, Haiku)',
            'api_url' => 'https://api.anthropic.com/v1/messages',
            'key_format' => '/^sk-ant-/',
            'key_example' => 'sk-ant-api03-...',
            'signup_url' => 'https://console.anthropic.com/',
            'models' => array(
                'claude-3-5-sonnet-20241022' => array(
                    'name' => 'Claude 3.5 Sonnet (Empfohlen)',
                    'cost_per_1k_tokens' => array('input' => 3.00, 'output' => 15.00),
                    'max_tokens' => 200000,
                    'features' => array('text', 'analysis', 'reasoning')
                ),
                'claude-3-5-haiku-20241022' => array(
                    'name' => 'Claude 3.5 Haiku (Neu - Schnell)',
                    'cost_per_1k_tokens' => array('input' => 1.00, 'output' => 5.00),
                    'max_tokens' => 200000,
                    'features' => array('text', 'fast', 'economical')
                ),
                'claude-3-opus-20240229' => array(
                    'name' => 'Claude 3 Opus (Premium)',
                    'cost_per_1k_tokens' => array('input' => 15.00, 'output' => 75.00),
                    'max_tokens' => 200000,
                    'features' => array('text', 'premium', 'complex_reasoning')
                ),
                'claude-3-haiku-20240307' => array(
                    'name' => 'Claude 3 Haiku (Budget)',
                    'cost_per_1k_tokens' => array('input' => 0.25, 'output' => 1.25),
                    'max_tokens' => 200000,
                    'features' => array('text', 'fast', 'budget')
                )
            )
        ),
        'gemini' => array(
            'name' => 'Google Gemini (Pro, Flash)',
            'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'key_format' => '/^AIza/',
            'key_example' => 'AIzaSy...',
            'signup_url' => 'https://makersuite.google.com/app/apikey',
            'models' => array(
                'gemini-1.5-pro-latest' => array(
                    'name' => 'Gemini 1.5 Pro (Premium)',
                    'cost_per_1k_tokens' => array('input' => 1.25, 'output' => 5.00),
                    'max_tokens' => 2000000,
                    'features' => array('text', 'multimodal', 'large_context')
                ),
                'gemini-1.5-flash-latest' => array(
                    'name' => 'Gemini 1.5 Flash (Empfohlen)',
                    'cost_per_1k_tokens' => array('input' => 0.075, 'output' => 0.30),
                    'max_tokens' => 1000000,
                    'features' => array('text', 'fast', 'multimodal', 'economical')
                ),
                'gemini-1.5-flash-8b-latest' => array(
                    'name' => 'Gemini 1.5 Flash-8B (Ultra-schnell)',
                    'cost_per_1k_tokens' => array('input' => 0.0375, 'output' => 0.15),
                    'max_tokens' => 1000000,
                    'features' => array('text', 'ultra_fast', 'ultra_economical')
                ),
                'gemini-1.0-pro-latest' => array(
                    'name' => 'Gemini 1.0 Pro (Bewährt)',
                    'cost_per_1k_tokens' => array('input' => 0.50, 'output' => 1.50),
                    'max_tokens' => 32768,
                    'features' => array('text', 'stable')
                ),
                'gemini-exp-1206' => array(
                    'name' => 'Gemini Experimental (Beta)',
                    'cost_per_1k_tokens' => array('input' => 0.50, 'output' => 1.50),
                    'max_tokens' => 32768,
                    'features' => array('text', 'experimental', 'latest_features')
                )
            )
        )
    );
    
    /**
     * Schweizer Kantone
     */
    private static $swiss_cantons = array(
        'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
        'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
        'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
        'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
        'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
        'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
        'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
    );
    
    /**
     * Optimierungsfokus-Optionen
     */
    private static $optimization_focus_options = array(
        'complete_seo' => 'Vollständige SEO-Optimierung für maximale Sichtbarkeit',
        'local_seo_swiss' => 'Schweizer Local SEO mit Fokus auf regionale Begriffe',
        'conversion' => 'Conversion-optimiert für höhere Klickraten und Verkäufe',
        'readability' => 'Lesbarkeit und Verständlichkeit für breitere Zielgruppe',
        'branding' => 'Markenaufbau und Vertrauensbildung bei der Zielgruppe',
        'ecommerce' => 'E-Commerce optimiert für Online-Shops und Produktverkauf',
        'b2b' => 'B2B und Professional Services für Geschäftskunden',
        'news_blog' => 'News und Blog-Content für aktuelle Themen und Engagement'
    );
    
    /**
     * Standard-Einstellungen abrufen
     */
    public static function get_default_settings($section = null) {
        if ($section && isset(self::$default_settings[$section])) {
            return self::$default_settings[$section];
        }
        
        return $section ? array() : self::$default_settings;
    }
    
    /**
     * KI-Provider-Konfiguration abrufen
     */
    public static function get_ai_providers($provider = null) {
        if ($provider && isset(self::$ai_providers[$provider])) {
            return self::$ai_providers[$provider];
        }
        
        return $provider ? null : self::$ai_providers;
    }
    
    /**
     * Verfügbare Provider-Namen abrufen
     */
    public static function get_provider_names() {
        $names = array();
        foreach (self::$ai_providers as $key => $config) {
            $names[$key] = $config['name'];
        }
        return $names;
    }
    
    /**
     * Modelle für Provider abrufen
     */
    public static function get_models_for_provider($provider) {
        $provider_config = self::get_ai_providers($provider);
        if (!$provider_config || !isset($provider_config['models'])) {
            return array();
        }
        
        $models = array();
        foreach ($provider_config['models'] as $key => $config) {
            $models[$key] = $config['name'];
        }
        return $models;
    }
    
    /**
     * Schweizer Kantone abrufen
     */
    public static function get_swiss_cantons() {
        return self::$swiss_cantons;
    }
    
    /**
     * Optimierungsfokus-Optionen abrufen
     */
    public static function get_optimization_focus_options() {
        return self::$optimization_focus_options;
    }
    
    /**
     * Aktuelle Plugin-Einstellungen abrufen (mit Fallbacks)
     */
    public static function get_settings($section = null) {
        $saved_settings = get_option('retexify_ai_settings', array());
        $default_settings = self::get_default_settings();
        
        // Merge mit Defaults
        $settings = wp_parse_args($saved_settings, array_merge(
            $default_settings['ai'],
            $default_settings['business']
        ));
        
        if ($section && isset($default_settings[$section])) {
            $section_settings = array();
            foreach ($default_settings[$section] as $key => $default_value) {
                $section_settings[$key] = $settings[$key] ?? $default_value;
            }
            return $section_settings;
        }
        
        return $settings;
    }
    
    /**
     * Plugin-Pfade abrufen
     */
    public static function get_paths() {
        return array(
            'plugin_dir' => RETEXIFY_PLUGIN_PATH,
            'plugin_url' => RETEXIFY_PLUGIN_URL,
            'assets_dir' => RETEXIFY_PLUGIN_PATH . 'assets/',
            'assets_url' => RETEXIFY_PLUGIN_URL . 'assets/',
            'includes_dir' => RETEXIFY_PLUGIN_PATH . 'includes/',
            'uploads_dir' => wp_upload_dir()['basedir'] . '/retexify-imports/',
            'uploads_url' => wp_upload_dir()['baseurl'] . '/retexify-imports/'
        );
    }
    
    /**
     * System-Anforderungen prüfen
     */
    public static function check_requirements() {
        $checks = array();
        
        // WordPress-Version
        $checks['wp_version'] = array(
            'required' => self::MIN_WP_VERSION,
            'current' => get_bloginfo('version'),
            'status' => version_compare(get_bloginfo('version'), self::MIN_WP_VERSION, '>=')
        );
        
        // PHP-Version
        $checks['php_version'] = array(
            'required' => self::MIN_PHP_VERSION,
            'current' => phpversion(),
            'status' => version_compare(phpversion(), self::MIN_PHP_VERSION, '>=')
        );
        
        // Erforderliche PHP-Erweiterungen
        $required_extensions = array('curl', 'json', 'mbstring');
        $checks['php_extensions'] = array();
        
        foreach ($required_extensions as $ext) {
            $checks['php_extensions'][$ext] = extension_loaded($ext);
        }
        
        // WordPress-Berechtigungen
        $checks['permissions'] = array(
            'manage_options' => current_user_can('manage_options'),
            'upload_files' => current_user_can('upload_files')
        );
        
        // Upload-Verzeichnis
        $upload_dir = wp_upload_dir();
        $checks['upload_dir'] = array(
            'exists' => $upload_dir && !$upload_dir['error'],
            'writable' => $upload_dir && is_writable($upload_dir['basedir'])
        );
        
        return $checks;
    }
    
    /**
     * Feature-Flags für Plugin-Funktionen
     */
    public static function is_feature_enabled($feature) {
        $features = get_option('retexify_feature_flags', array(
            'export_import' => true,
            'multi_ai' => true,
            'swiss_local_seo' => true,
            'content_analysis' => true,
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ));
        
        return $features[$feature] ?? false;
    }
    
    /**
     * Performance-Einstellungen
     */
    public static function get_performance_config() {
        return array(
            'cache_duration' => self::get_default_settings('performance')['cache_duration'],
            'minify_assets' => self::get_default_settings('performance')['minify_assets'],
            'async_loading' => true,
            'defer_non_critical' => true
        );
    }
    
    /**
     * Kosten für Request schätzen
     */
    public static function estimate_request_cost($provider, $model, $input_tokens, $output_tokens) {
        $provider_config = self::get_ai_providers($provider);
        
        if (!$provider_config || !isset($provider_config['models'][$model])) {
            return array('error' => 'Modell nicht gefunden');
        }
        
        $model_config = $provider_config['models'][$model];
        $costs = $model_config['cost_per_1k_tokens'];
        
        $input_cost = ($input_tokens / 1000) * $costs['input'];
        $output_cost = ($output_tokens / 1000) * $costs['output'];
        $total_cost = $input_cost + $output_cost;
        
        return array(
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $input_tokens + $output_tokens,
            'input_cost_usd' => round($input_cost, 6),
            'output_cost_usd' => round($output_cost, 6),
            'total_cost_usd' => round($total_cost, 6),
            'cost_per_1000_requests' => round($total_cost * 1000, 2),
            'model' => $model,
            'provider' => $provider
        );
    }
    
    /**
     * API-Key-Format validieren
     */
    public static function validate_api_key_format($provider, $api_key) {
        $provider_config = self::get_ai_providers($provider);
        
        if (!$provider_config || empty($api_key)) {
            return false;
        }
        
        return preg_match($provider_config['key_format'], $api_key);
    }
}

// Globale Hilfsfunktion
if (!function_exists('retexify_config')) {
    function retexify_config($method = null, ...$args) {
        if (!$method) {
            return ReTexify_Config::class;
        }
        
        if (method_exists(ReTexify_Config::class, $method)) {
            return call_user_func_array([ReTexify_Config::class, $method], $args);
        }
        
        return null;
    }
}