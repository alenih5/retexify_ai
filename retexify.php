<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Plugin URI: https://imponi.ch/
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen.
 * Version: 4.6.0
 * Author: Imponi
 * Author URI: https://imponi.ch/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: retexify_ai_pro
 * Network: false
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
if (!defined('RETEXIFY_VERSION')) {
    define('RETEXIFY_VERSION', '4.6.0');
}
if (!defined('RETEXIFY_PLUGIN_URL')) {
    define('RETEXIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('RETEXIFY_PLUGIN_PATH')) {
    define('RETEXIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// ============================================================================
// 🔧 ALLE ERFORDERLICHEN DATEIEN LADEN
// ============================================================================

$required_files = array(
    // Core-Klassen (erforderlich)
    'includes/class-ai-engine.php',
    'includes/class-admin-renderer.php',
    
    // Erweiterte Handler
    'includes/class-system-status.php',
    'includes/class-performance-optimizer.php',
    
    // Intelligente Features
    'includes/class-api-manager.php',
    'includes/class-intelligent-keyword-research.php',
    'includes/class-retexify-config.php'
);

foreach ($required_files as $file) {
    $file_path = RETEXIFY_PLUGIN_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log('ReTexify AI: File missing: ' . $file);
    }
}

// Export/Import Manager optional laden
$export_import_file = RETEXIFY_PLUGIN_PATH . 'includes/class-export-import-manager.php';
if (file_exists($export_import_file)) {
    require_once $export_import_file;
}

// ============================================================================
// 🚀 HAUPT-PLUGIN-KLASSE - VOLLSTÄNDIGE VERSION
// ============================================================================

if (!class_exists('ReTexify_AI_Pro_Universal')) {
class ReTexify_AI_Pro_Universal {
    
    /**
     * Content-Analyzer Instanz
     */
    private $content_analyzer;
    
    /**
     * AI-Engine Instanz
     */
    private $ai_engine;
    
    /**
     * Export/Import Manager Instanz (optional)
     */
    private $export_import_manager = null;
    
    private $admin_renderer;
    
    public function __construct() {
        // Klassen initialisieren
        $this->init_classes();
        
        // WordPress Hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX-Handler registrieren (VOLLSTÄNDIG)
        $this->register_ajax_handlers();
        
        // Aktivierung
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        
        $this->admin_renderer = new ReTexify_Admin_Renderer($this->ai_engine, $this->export_import_manager);
    }
    
    // ========================================================================
    // 🔧 INITIALISIERUNG
    // ========================================================================
    
    private function init_classes() {
        // Performance-Optimierungen aktivieren
        if (class_exists('ReTexify_Performance_Optimizer')) {
            ReTexify_Performance_Optimizer::enable_optimizations();
        }
        
        // Content-Analyzer initialisieren (entfernt - Funktionalität durch Intelligent Keyword Research ersetzt)
        $this->content_analyzer = null;
        // AI-Engine initialisieren - ERWEITERTE VERSUCHE
        if (function_exists('retexify_get_ai_engine')) {
            $this->ai_engine = retexify_get_ai_engine();
            error_log('ReTexify: AI-Engine via function loaded: ' . (is_object($this->ai_engine) ? get_class($this->ai_engine) : 'NULL'));
        } 
        elseif (class_exists('ReTexify_AI_Engine')) {
            $this->ai_engine = new ReTexify_AI_Engine();
            error_log('ReTexify: AI-Engine via class loaded: ' . get_class($this->ai_engine));
        }
        else {
            // AI-Engine-Datei manuell laden falls nicht automatisch geladen
            $ai_engine_file = RETEXIFY_PLUGIN_PATH . 'includes/class-ai-engine.php';
            if (file_exists($ai_engine_file)) {
                require_once $ai_engine_file;
                if (class_exists('ReTexify_AI_Engine')) {
                    $this->ai_engine = new ReTexify_AI_Engine();
                    error_log('ReTexify: AI-Engine manually loaded: ' . get_class($this->ai_engine));
                }
            }
        }
        if (!$this->ai_engine) {
            error_log('ReTexify: AI-Engine konnte nicht geladen werden!');
        } else {
            error_log('ReTexify: AI-Engine erfolgreich initialisiert: ' . get_class($this->ai_engine));
        }
        // Export/Import Manager (optional)
        if (class_exists('ReTexify_Export_Import_Manager')) {
            try {
                $this->export_import_manager = new ReTexify_Export_Import_Manager();
            } catch (Exception $e) {
                error_log('ReTexify Export/Import Manager fehlt: ' . $e->getMessage());
                $this->export_import_manager = null;
            }
        }
    }
    
    /**
     * ✅ VOLLSTÄNDIGE AJAX-HANDLER REGISTRIERUNG
     * Alle AJAX-Actions für logged-in UND non-logged-in User
     */
    private function register_ajax_handlers() {
        
        $ajax_actions = array(
            // Dashboard & Stats
            'retexify_get_stats' => 'ajax_get_stats',
            
            // SEO Optimizer - ALLE HANDLER
            'retexify_load_content' => 'handle_load_seo_content',
            'retexify_generate_single_seo' => 'handle_generate_single_seo',
            'retexify_generate_complete_seo' => 'handle_generate_complete_seo',
            'retexify_save_seo_data' => 'handle_save_seo_data',
            'retexify_get_page_content' => 'handle_get_page_content',
            'retexify_analyze_content' => 'handle_analyze_content',
            
            // KI-Einstellungen - ERWEITERT
            'retexify_save_settings' => 'handle_ai_save_settings',
            'retexify_test_api_connection' => 'handle_ai_test_connection',
            'retexify_switch_provider' => 'handle_switch_provider',
            'retexify_get_api_keys' => 'handle_get_api_keys',
            'retexify_save_api_key' => 'handle_save_api_key',
            'retexify_test_all_providers' => 'handle_test_all_providers',
            
            // Intelligent Keyword Research
            'retexify_keyword_research' => 'handle_keyword_research',
            'retexify_analyze_competition' => 'handle_analyze_competition',
            'retexify_get_suggestions' => 'handle_get_suggestions',
            
            // System & Diagnostics
            'retexify_test_system' => 'ajax_test_system',
            'retexify_get_system_info' => 'ajax_get_system_info',
            'retexify_check_requirements' => 'ajax_check_requirements',
            'retexify_diagnostic_report' => 'ajax_diagnostic_report',
            'retexify_get_performance_metrics' => 'ajax_get_performance_metrics',
            
            // Export/Import (falls verfügbar)
            'retexify_get_export_stats' => 'handle_get_export_stats',
            'retexify_export_content_csv' => 'handle_export_content_csv',
            'retexify_import_csv_data' => 'handle_import_csv_data',
            'retexify_get_import_preview' => 'handle_get_import_preview',
            'retexify_get_export_preview' => 'handle_get_export_preview',
            'retexify_save_imported_data' => 'handle_save_imported_data',
            'retexify_delete_upload' => 'handle_delete_upload',
            'retexify_download_export_file' => 'handle_download_export_file'
        );
        
        // Für jeden AJAX-Action beide Handler registrieren
        foreach ($ajax_actions as $action => $method) {
            
            // Prüfen ob Method in dieser Klasse existiert
            if (method_exists($this, $method)) {
                add_action('wp_ajax_' . $action, array($this, $method));
                add_action('wp_ajax_nopriv_' . $action, array($this, $method));
            }
            
            // Export/Import Manager separat behandeln
            elseif (in_array($action, array(
                'retexify_export_content_csv', 'retexify_import_csv_data', 'retexify_get_import_preview',
                'retexify_get_export_preview', 'retexify_save_imported_data', 'retexify_delete_upload', 'retexify_download_export_file'
            ))) {
                if ($this->export_import_manager && method_exists($this->export_import_manager, $method)) {
                    add_action('wp_ajax_' . $action, array($this->export_import_manager, $method));
                    add_action('wp_ajax_nopriv_' . $action, array($this->export_import_manager, $method));
                }
            }
            
            // Intelligent Research separat behandeln
            elseif (in_array($action, array(
                'retexify_keyword_research', 'retexify_analyze_competition', 
                'retexify_get_suggestions'
            ))) {
                if (class_exists('ReTexify_Intelligent_Keyword_Research')) {
                    $research_instance = new ReTexify_Intelligent_Keyword_Research();
                    if (method_exists($research_instance, $method)) {
                        add_action('wp_ajax_' . $action, array($research_instance, $method));
                        add_action('wp_ajax_nopriv_' . $action, array($research_instance, $method));
                    }
                }
            }
        }
    }
    
    public function activate_plugin() {
        // Aktivierungszeit speichern
        if (!get_option('retexify_activation_time')) {
            add_option('retexify_activation_time', current_time('mysql'));
        }
        
        // Standard-Einstellungen setzen falls nicht vorhanden
        if (!get_option('retexify_ai_settings')) {
            add_option('retexify_ai_settings', array(
                'api_provider' => 'openai',
                'api_key' => '',
                'model' => 'gpt-4o-mini',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'default_language' => 'de-ch',
                'business_context' => 'Ihr Unternehmen in der Schweiz',
                'target_audience' => 'Schweizer Kunden und Interessenten',
                'brand_voice' => 'professional',
                'target_cantons' => array('BE', 'ZH', 'LU', 'SG'),
                'use_swiss_german' => true,
                'optimization_focus' => 'complete_seo',
                'premium_tone' => false,
                'include_cantons' => true
            ));
        }
            
        // Separate API-Key Speicherung initialisieren
        if (!get_option('retexify_api_keys')) {
            add_option('retexify_api_keys', array(
                'openai' => '',
                'anthropic' => '',
                'gemini' => ''
            ));
        }
        
        // Migration: Alte API-Schlüssel bereinigen und in neue Struktur überführen
        $this->migrate_and_cleanup_old_api_keys();
        
        // Upload-Verzeichnis für Export/Import erstellen
        $upload_dir = wp_upload_dir();
        $retexify_dir = $upload_dir['basedir'] . '/retexify-ai/';
        if (!file_exists($retexify_dir)) {
            wp_mkdir_p($retexify_dir);
            
            // .htaccess für Sicherheit
            $htaccess_content = "Order deny,allow\nDeny from all\n<Files *.csv>\nAllow from all\n</Files>";
            file_put_contents($retexify_dir . '.htaccess', $htaccess_content);
        }
    }
    
    // ========================================================================
    // 🎨 ADMIN-INTERFACE
    // ========================================================================
    
    public function add_admin_menu() {
        add_menu_page(
            'ReTexify AI',
            'ReTexify AI', 
            'manage_options',
            'retexify-ai-pro',
            array($this, 'admin_page'),
            'dashicons-admin-customizer',
            25
        );
    }
    
    public function enqueue_assets($hook) {
        // Nur auf unserer Admin-Seite laden
        if ($hook !== 'toplevel_page_retexify-ai-pro') {
            return;
        }
        
        // CSS-Dateien mit verbesserter Reihenfolge laden
        
        // 1. Haupt-CSS (Basis-Styles)
        $css_file = RETEXIFY_PLUGIN_PATH . 'assets/admin-style.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'retexify-admin-style', 
                RETEXIFY_PLUGIN_URL . 'assets/admin-style.css', 
                array(), 
                RETEXIFY_VERSION . '-' . filemtime($css_file), // Cache-busting mit filemtime
                'all'
            );
        }
        
        // 2. Erweiterte CSS (zusätzliche Features)
        $extended_css_file = RETEXIFY_PLUGIN_PATH . 'assets/admin_styles_extended.css';
        if (file_exists($extended_css_file)) {
            wp_enqueue_style(
                'retexify-admin-style-extended', 
                RETEXIFY_PLUGIN_URL . 'assets/admin_styles_extended.css', 
                array('retexify-admin-style'), // Abhängigkeit von Haupt-CSS
                RETEXIFY_VERSION . '-' . filemtime($extended_css_file),
                'all'
            );
        }
        
        // 3. 🆕 NEUE MODERNE SYSTEM-STATUS CSS (ERSETZT system-status-fixes.css)
        $modern_system_css_file = RETEXIFY_PLUGIN_PATH . 'assets/modern-system-status.css';
        if (file_exists($modern_system_css_file)) {
            wp_enqueue_style(
                'retexify-modern-system-status', 
                RETEXIFY_PLUGIN_URL . 'assets/modern-system-status.css', 
                array('retexify-admin-style', 'retexify-admin-style-extended'),
                RETEXIFY_VERSION . '-' . filemtime($modern_system_css_file),
                'all'
            );
        }
        
        // 4. Inline-CSS für kritische Fixes (ERWEITERT für modernes Design)
        $inline_css = '
            /* Kritische System-Status-Fixes - sofort geladen */
            #retexify-system-status {
                min-height: 200px;
                position: relative;
                background: transparent;
                border-radius: 0;
                overflow: visible;
            }
            
            .retexify-loading-wrapper {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                background: rgba(248, 249, 250, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                z-index: 10;
            }
            
            .retexify-system-status-content {
                opacity: 0;
                animation: fadeInModernSystem 0.8s ease-in-out forwards;
            }
            
            @keyframes fadeInModernSystem {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Moderne Button-Styles für Header-Badges */
            #retexify-test-system-badge,
            #retexify-test-research-badge {
                cursor: pointer;
                user-select: none;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                transform: translateZ(0);
            }
            #retexify-test-system-badge:hover,
            #retexify-test-research-badge:hover {
                transform: translateY(-2px) translateZ(0);
                box-shadow: 0 8px 32px rgba(102, 126, 234, 0.35);
            }
            /* Status-spezifische Optimierungen */
            .retexify-modern-system-container .status-item {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
            }
            /* Responsive Fallbacks */
            @media (max-width: 768px) {
                .retexify-modern-system-container {
                    margin: 12px 0;
                }
            }
        ';
        wp_add_inline_style('retexify-admin-style', $inline_css);
        
        // JavaScript einbinden - mit Fallback-prüfung
        wp_enqueue_script('jquery');
        
        $js_file = RETEXIFY_PLUGIN_PATH . 'assets/admin-script.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'retexify-admin-script',
                RETEXIFY_PLUGIN_URL . 'assets/admin-script.js',
                array('jquery'),
                RETEXIFY_VERSION . '-' . filemtime($js_file), // Cache-busting
                true
            );
            
            // JavaScript-Variablen mit erweiterten Debug-Informationen
            wp_localize_script('retexify-admin-script', 'retexify_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('retexify_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'ai_enabled' => $this->is_ai_enabled(),
                'plugin_version' => RETEXIFY_VERSION,
                'user_can_manage' => current_user_can('manage_options'),
                'system_status_url' => admin_url('admin-ajax.php?action=retexify_test_system'),
                'cache_buster' => time() // Für Cache-Probleme bei AJAX-Calls
            ));
        }
        
        // Export/Import JavaScript (falls verfügbar)
        $export_import_js_file = RETEXIFY_PLUGIN_PATH . 'assets/export_import.js';
        if (file_exists($export_import_js_file) && $this->export_import_manager) {
            wp_enqueue_script(
                'retexify-export-import',
                RETEXIFY_PLUGIN_URL . 'assets/export_import.js',
                array('jquery', 'retexify-admin-script'),
                RETEXIFY_VERSION . '-' . filemtime($export_import_js_file),
                true
            );
            
            // Zusätzliche AJAX-Daten für Export/Import
            wp_localize_script('retexify-export-import', 'retexify_export_import_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('retexify_nonce'),
                'max_file_size' => wp_max_upload_size(),
                'upload_dir' => wp_upload_dir()['basedir'] . '/retexify-ai/'
            ));
        }
        
        // Intelligent Progress Script (falls verfügbar)
        $intelligent_js_file = RETEXIFY_PLUGIN_PATH . 'assets/intelligent-progress.js';
        if (file_exists($intelligent_js_file)) {
            wp_enqueue_script(
                'retexify-intelligent-progress',
                RETEXIFY_PLUGIN_URL . 'assets/intelligent-progress.js',
                array('jquery', 'retexify-admin-script'),
                RETEXIFY_VERSION . '-' . filemtime($intelligent_js_file),
                true
            );
        }
        
        // Debugging: CSS/JS-Loading protokollieren
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ReTexify CSS/JS Loading: ' . wp_json_encode(array(
                'css_loaded' => file_exists($css_file),
                'extended_css_loaded' => file_exists($extended_css_file),
                'js_loaded' => file_exists($js_file),
                'export_import_js' => file_exists($export_import_js_file),
                'cache_buster' => time()
            )));
        }
    }
    
    private function is_ai_enabled() {
        $api_keys = get_option('retexify_api_keys', array());
        $settings = get_option('retexify_ai_settings', array());
        $current_provider = $settings['api_provider'] ?? 'openai';
        
        return !empty($api_keys[$current_provider]);
    }
        
    /**
     * Alle API-Keys abrufen (für JavaScript)
     */
    private function get_all_api_keys() {
        if (!current_user_can('manage_options')) {
            return array();
        }
        return get_option('retexify_api_keys', array());
    }
    
    public function admin_page() {
        $this->admin_renderer->render_admin_page();
    }
    
    // ========================================================================
    // 🎯 AJAX-HANDLER FÜR SEO-OPTIMIZER - VOLLSTÄNDIG
    // ========================================================================
    
    public function handle_load_seo_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler - Berechtigung verweigert');
            return;
        }
        
        try {
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'page');
            
            error_log('ReTexify: Loading SEO content for post_type: ' . $post_type);
            
            // Posts/Pages laden
            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => 50,
                'orderby' => 'modified',
                'order' => 'DESC'
            ));
            
            if (empty($posts)) {
                wp_send_json_error('Keine ' . $post_type . ' gefunden');
            return;
        }
        
            $seo_data = array();
            
            foreach ($posts as $post) {
                // SEO-Daten von verschiedenen Plugins abrufen
                $meta_title = $this->get_meta_title($post->ID);
                $meta_description = $this->get_meta_description($post->ID);
                $focus_keyword = $this->get_focus_keyword($post->ID);
                
                // Content verarbeiten
                $full_content = wp_strip_all_tags($post->post_content);
                
                $item = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit'),
                    'status' => $post->post_status,
                    'modified' => get_the_modified_date('d.m.Y H:i', $post->ID),
                    'type' => $post->post_type,
                    
                    // Aktuelle SEO-Daten
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'focus_keyword' => $focus_keyword,
                    
                    // Content
                    'full_content' => $full_content,
                    'content_excerpt' => wp_trim_words($full_content, 50),
                    
                    // Optimierungs-Status
                    'needs_optimization' => empty($meta_title) || empty($meta_description) || empty($focus_keyword)
                );
                
                $seo_data[] = $item;
            }
            
            error_log('ReTexify: Loaded ' . count($seo_data) . ' items for ' . $post_type);
            
            wp_send_json_success(array(
                'items' => $seo_data,
                'total' => count($seo_data),
                'post_type' => $post_type,
                'message' => count($seo_data) . ' ' . $post_type . ' Einträge geladen'
            ));
            
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_load_seo_content: ' . $e->getMessage());
            wp_send_json_error('Fehler beim Laden der SEO-Daten: ' . $e->getMessage());
        }
    }
    
    public function handle_generate_single_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler - Berechtigung verweigert');
            return;
        }
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $seo_type = sanitize_text_field($_POST['seo_type'] ?? '');
            if ($post_id <= 0) {
                wp_send_json_error('Ungültige Post-ID: ' . $post_id);
                return;
            }
            if (!in_array($seo_type, array('meta_title', 'meta_description', 'focus_keyword'))) {
                wp_send_json_error('Ungültiger SEO-Typ: ' . $seo_type);
                return;
            }
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            // ⚠️ NEUE LOGIK: Für einzelne Generierung auch intelligente Analyse verwenden
            // Aber nur für den spezifischen Typ optimieren
            $settings = get_option('retexify_ai_settings', array());
            $api_keys = get_option('retexify_api_keys', array());
            $current_provider = $settings['api_provider'] ?? 'openai';
            $settings['api_key'] = $api_keys[$current_provider] ?? '';
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel für ' . ucfirst($current_provider) . ' konfiguriert');
                return;
            }
            error_log('ReTexify: Generating intelligent single ' . $seo_type . ' for post ' . $post_id);
            // Komplette Suite generieren und spezifischen Typ extrahieren
            $full_suite = $this->generate_intelligent_seo_suite($post, $settings, true, false);
            if (empty($full_suite[$seo_type])) {
                wp_send_json_error('Intelligente Generierung für ' . $seo_type . ' fehlgeschlagen');
                return;
            }
            wp_send_json_success(array(
                'generated_text' => $full_suite[$seo_type],
                'seo_type' => $seo_type,
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'research_mode' => $full_suite['research_mode'] ?? 'intelligent',
                'analysis_used' => true
            ));
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_generate_single_seo: ' . $e->getMessage());
            wp_send_json_error('Intelligente Einzelgenerierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_generate_complete_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            if ($post_id <= 0) {
                wp_send_json_error('Ungültige Post-ID: ' . $post_id);
                return;
            }
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            if (!$this->ai_engine) {
                wp_send_json_error('AI-Engine nicht verfügbar');
                return;
            }
            // Settings und API-Keys laden
            $settings = get_option('retexify_ai_settings', array());
            $api_keys = get_option('retexify_api_keys', array());
            $current_provider = $settings['api_provider'] ?? 'openai';
            $settings['api_key'] = $api_keys[$current_provider] ?? '';
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel für ' . ucfirst($current_provider) . ' konfiguriert');
                return;
            }
            // ✅ NEUE LOGIK: Intelligente Analyse verwenden
            error_log('ReTexify: Starting INTELLIGENT SEO generation for post ' . $post_id);
            // Parameter aus AJAX-Request
            $include_cantons = !empty($_POST['include_cantons']) || !empty($settings['target_cantons']);
            $premium_tone = !empty($_POST['premium_tone']) || ($settings['brand_voice'] ?? '') === 'premium';
            // ⚠️ HAUPTVERBESSERUNG: Intelligente Keyword-Research verwenden
            $results = $this->generate_intelligent_seo_suite($post, $settings, $include_cantons, $premium_tone);
            if (empty($results)) {
                wp_send_json_error('Keine SEO-Texte generiert - Intelligente Analyse fehlgeschlagen');
                return;
            }
            $generated_count = count(array_filter($results));
            error_log("ReTexify: INTELLIGENT SEO generation finished - Generated $generated_count high-quality items: " . implode(', ', array_keys(array_filter($results))));
            // Erfolgreiche Antwort mit Analyse-Info
            wp_send_json_success(array_merge($results, array(
                'generated_count' => $generated_count,
                'research_mode' => 'intelligent',
                'analysis_used' => true,
                'timestamp' => current_time('mysql')
            )));
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_generate_complete_seo: ' . $e->getMessage());
            wp_send_json_error('Intelligente SEO-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_save_seo_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $meta_title = sanitize_text_field($_POST['meta_title'] ?? '');
            $meta_description = sanitize_textarea_field($_POST['meta_description'] ?? '');
            $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
            
            if ($post_id <= 0) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
            $saved_count = 0;
            
            // Meta-Titel speichern
            if (!empty($meta_title)) {
                if ($this->save_meta_title($post_id, $meta_title)) {
                $saved_count++;
                }
            }
            
            // Meta-Beschreibung speichern
            if (!empty($meta_description)) {
                if ($this->save_meta_description($post_id, $meta_description)) {
                $saved_count++;
                }
            }
            
            // Focus-Keyword speichern
            if (!empty($focus_keyword)) {
                if ($this->save_focus_keyword($post_id, $focus_keyword)) {
                $saved_count++;
                }
            }
            
            if ($saved_count === 0) {
                wp_send_json_error('Keine Daten gespeichert - prüfe SEO-Plugin-Installation');
                return;
            }
            
            wp_send_json_success(array(
                'message' => $saved_count . ' SEO-Elemente erfolgreich gespeichert',
                'saved_count' => $saved_count,
                'post_id' => $post_id
            ));
            
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_save_seo_data: ' . $e->getMessage());
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_get_page_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            
            // Content-Analyzer entfernt - Funktionalität durch Intelligent Keyword Research ersetzt
            $result = array(
                'content' => wp_strip_all_tags($post->post_content),
                'word_count' => str_word_count(wp_strip_all_tags($post->post_content)),
                'char_count' => strlen(wp_strip_all_tags($post->post_content)),
                'has_images' => has_post_thumbnail($post_id)
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden des Contents: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // 🔧 SYSTEM-STATUS AJAX-HANDLER - VOLLSTÄNDIG
    // ========================================================================
    
    public function ajax_test_system() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        try {
            $system_status = ReTexify_System_Status::get_instance();
            $system_tests = $system_status->get_modern_status_tests();
            $research_tests = $system_status->get_research_status_tests();
            $html = $system_status->generate_unified_modern_status_html($system_tests, $research_tests);
            wp_send_json_success($html);
        } catch (Exception $e) {
            wp_send_json_error('System-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    

    
    public function ajax_get_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            global $wpdb;
            $post_types_in = "('post', 'page')";
            $post_status_in = "('publish')";

            // Beiträge und Seiten getrennt zählen
            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status IN {$post_status_in}");
            $total_pages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status IN {$post_status_in}");

            // Yoast Meta-Titel
            $yoast_meta_titles = $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in} AND pm.meta_key = '_yoast_wpseo_title' AND pm.meta_value <> ''");
            // Yoast Meta-Beschreibungen
            $yoast_meta_descriptions = $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in} AND pm.meta_key = '_yoast_wpseo_metadesc' AND pm.meta_value <> ''");
            // Yoast Focus-Keywords
            $yoast_focus_keywords = $wpdb->get_var("SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in} AND pm.meta_key = '_yoast_wpseo_focuskw' AND pm.meta_value <> ''");

            // Medien (Alt-Texte)
            $total_media = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");
            $media_with_alt = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%' AND pm.meta_key = '_wp_attachment_image_alt' AND pm.meta_value <> ''");

            $ai_enabled = $this->is_ai_enabled();
            $ai_settings = get_option('retexify_ai_settings', array());

            // Dashboard-HTML generieren
            $html = '<div class="retexify-modern-dashboard">';

            // Content Karte
            $html .= '<div class="retexify-dashboard-card retexify-card-content">';
            $html .= '<div class="retexify-card-header-modern">';
            $html .= '<div class="retexify-card-icon">📄</div>';
            $html .= '<h3>Content</h3>';
            $html .= '</div>';
            $html .= '<div class="retexify-card-stats">';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($total_posts) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">BEITRÄGE/POSTS</div>';
            $html .= '</div>';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($total_pages) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">SEITEN/PAGES</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            // SEO Meta-Daten Karte
            $html .= '<div class="retexify-dashboard-card retexify-card-seo">';
            $html .= '<div class="retexify-card-header-modern">';
            $html .= '<div class="retexify-card-icon">🎯</div>';
            $html .= '<h3>SEO Meta-Daten</h3>';
            $html .= '</div>';
            $html .= '<div class="retexify-card-stats">';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($yoast_meta_titles) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">META-TITEL</div>';
            $html .= '</div>';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($yoast_meta_descriptions) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">META-BESCHREIBUNGEN</div>';
            $html .= '</div>';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($yoast_focus_keywords) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">FOCUS-KEYWORDS</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            // Keywords & Medien Karte
            $html .= '<div class="retexify-dashboard-card retexify-card-keywords">';
            $html .= '<div class="retexify-card-header-modern">';
            $html .= '<div class="retexify-card-icon">🔍</div>';
            $html .= '<h3>Keywords & Medien</h3>';
            $html .= '</div>';
            $html .= '<div class="retexify-card-stats">';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($yoast_focus_keywords) . '/' . intval($total_posts + $total_pages) . '</div>';
            $html .= '<div class="retexify-stat-label">KEYWORDS GESETZT</div>';
            $html .= '</div>';
            $html .= '<div class="retexify-stat-row">';
            $html .= '<div class="retexify-stat-number">' . intval($media_with_alt) . '/' . intval($total_media) . '</div>';
            $html .= '<div class="retexify-stat-label">ALT-TEXTE MEDIEN</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';

            $html .= '</div>';

            // System-Info Modern
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🖥️ System-Status:</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            $html .= '<span><strong>WordPress:</strong> ' . get_bloginfo('version') . '</span>';
            $html .= '<span><strong>Theme:</strong> ' . get_template() . '</span>';
            $html .= '<span><strong>KI-Status:</strong> ' . ($ai_enabled ? '✅ Aktiv (' . ucfirst($ai_settings['api_provider'] ?? 'Unbekannt') . ')' : '❌ Nicht konfiguriert') . '</span>';
            if ($ai_enabled && !empty($ai_settings['target_cantons'])) {
                $html .= '<span><strong>Kantone:</strong> ' . count($ai_settings['target_cantons']) . ' ausgewählt</span>';
            }
            if (!empty($ai_settings['business_context'])) {
                $html .= '<span><strong>Business:</strong> ' . wp_trim_words($ai_settings['business_context'], 4) . '</span>';
            }
            if ($this->export_import_manager) {
                $html .= '<span><strong>Export/Import:</strong> ✅ Verfügbar</span>';
            }
            $html .= '</div>';
            $html .= '</div>';

            wp_send_json_success($html);
        } catch (Exception $e) {
            wp_send_json_error('Statistik-Fehler: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // 🎨 KI-EINSTELLUNGEN AJAX-HANDLER - VOLLSTÄNDIG
    // ========================================================================
    
    // ==== API-KEY MANAGEMENT AJAX HANDLERS ====
    
    public function handle_get_api_keys() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $api_keys = $this->get_all_api_keys();
        wp_send_json_success($api_keys);
    }
            
    public function handle_save_api_key() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
                return;
            }
            
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (!$provider) {
            wp_send_json_error('Ungültiger Provider');
                return;
            }
            
        $api_keys = $this->get_all_api_keys();
        $api_keys[$provider] = $api_key;
        
        update_option('retexify_api_keys', $api_keys);
        
        wp_send_json_success('API-Schlüssel gespeichert');
    }
    
    public function handle_ai_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $settings = get_option('retexify_ai_settings', array());
            $api_keys = $this->get_all_api_keys();
            $current_provider = $settings['api_provider'] ?? 'openai';
            
            // Aktuellen API-Key verwenden
            $settings['api_key'] = $api_keys[$current_provider] ?? '';
            
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel für ' . ucfirst($current_provider) . ' konfiguriert. Bitte geben Sie zuerst einen API-Schlüssel ein.');
                return;
            }
            
            if ($this->ai_engine && method_exists($this->ai_engine, 'test_connection')) {
                $test_result = $this->ai_engine->test_connection($settings);
                
                if ($test_result['success']) {
                    wp_send_json_success($test_result['message']);
            } else {
                    wp_send_json_error($test_result['message']);
                }
            } else {
                wp_send_json_error('KI-Engine nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Verbindungsfehler: ' . $e->getMessage());
        }
    }
    
    public function handle_ai_save_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        try {
            $provider = sanitize_text_field($_POST['api_provider'] ?? 'openai');
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            // API-Key NUR in retexify_api_keys speichern, niemals in Settings!
            $api_keys = $this->get_all_api_keys();
            $api_keys[$provider] = $api_key;
            update_option('retexify_api_keys', $api_keys);
            // Settings OHNE API-Key speichern
            $raw_settings = array(
                'api_provider' => $provider,
                'model' => sanitize_text_field($_POST['model'] ?? 'gpt-4o-mini'),
                'optimization_focus' => sanitize_text_field($_POST['optimization_focus'] ?? 'complete_seo'),
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'default_language' => 'de-ch',
                'business_context' => sanitize_textarea_field($_POST['business_context'] ?? ''),
                'target_audience' => sanitize_text_field($_POST['target_audience'] ?? ''),
                'brand_voice' => sanitize_text_field($_POST['brand_voice'] ?? 'professional'),
                'target_cantons' => array_map('sanitize_text_field', $_POST['target_cantons'] ?? array()),
                'use_swiss_german' => true
            );
            if ($this->ai_engine && method_exists($this->ai_engine, 'validate_settings')) {
                $settings = $this->ai_engine->validate_settings($raw_settings);
            } else {
                $settings = $raw_settings;
            }
            // Niemals API-Key in Settings speichern!
            unset($settings['api_key']);
            update_option('retexify_ai_settings', $settings);
            wp_send_json_success('KI-Einstellungen sicher gespeichert! ' . count($settings['target_cantons']) . ' Kantone ausgewählt.');
        } catch (Exception $e) {
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_switch_provider() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $new_provider = sanitize_text_field($_POST['provider'] ?? 'openai');
            
            $settings = get_option('retexify_ai_settings', array());
            $settings['api_provider'] = $new_provider;
            
            update_option('retexify_ai_settings', $settings);
            
            wp_send_json_success('Provider gewechselt zu: ' . ucfirst($new_provider));
            
        } catch (Exception $e) {
            wp_send_json_error('Provider-Wechsel fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_test_all_providers() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
                $api_keys = $this->get_all_api_keys();
            $results = array();
            
            foreach (['openai', 'anthropic', 'gemini'] as $provider) {
                if (!empty($api_keys[$provider])) {
                    // Quick test für jeden Provider
                    $results[$provider] = $this->quick_test_provider($provider, $api_keys[$provider]);
            } else {
                    $results[$provider] = array(
                        'status' => 'error',
                        'message' => 'Kein API-Key konfiguriert'
                    );
                }
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error('Provider-Tests fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // 🛠️ HELPER-METHODEN FÜR SEO-GENERIERUNG - VOLLSTÄNDIG
    // ========================================================================
    
    /**
     * Meta-Titel generieren
     */
    private function generate_meta_title_content($post, $content) {
        error_log('ReTexify: Einstieg generate_meta_title_content für Post-ID: ' . $post->ID);
        if (!$this->ai_engine) {
            error_log('ReTexify: AI-Engine ist null in generate_meta_title_content!');
            return '';
        }
        $settings = get_option('retexify_ai_settings', array());
        $masked_key = isset($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : 'NICHT GESETZT';
        error_log('ReTexify: Settings (API-Key maskiert): ' . print_r(array_merge($settings, ['api_key' => $masked_key]), true));
        if (empty($settings['api_key'])) {
            error_log('ReTexify: Kein API-Key in den Einstellungen gefunden!');
            return '';
        }
        $include_cantons = !empty($settings['target_cantons']);
        $premium_tone = !empty($settings['brand_voice']) && $settings['brand_voice'] === 'premium';
        try {
            $response = $this->ai_engine->generate_single_seo_item($post, 'meta_title', $settings, $include_cantons, $premium_tone);
            return trim(str_replace('"', '', $response));
        } catch (Exception $e) {
            error_log('ReTexify Meta-Title Generation Error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Meta-Beschreibung generieren
     */
    private function generate_meta_description_content($post, $content) {
        error_log('ReTexify: Einstieg generate_meta_description_content für Post-ID: ' . $post->ID);
        if (!$this->ai_engine) {
            error_log('ReTexify: AI-Engine ist null in generate_meta_description_content!');
            return '';
        }
        $settings = get_option('retexify_ai_settings', array());
        $masked_key = isset($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : 'NICHT GESETZT';
        error_log('ReTexify: Settings (API-Key maskiert): ' . print_r(array_merge($settings, ['api_key' => $masked_key]), true));
        $include_cantons = !empty($settings['target_cantons']);
        $premium_tone = !empty($settings['brand_voice']) && $settings['brand_voice'] === 'premium';
        try {
            $response = $this->ai_engine->generate_single_seo_item($post, 'meta_description', $settings, $include_cantons, $premium_tone);
            return trim(str_replace('"', '', $response));
        } catch (Exception $e) {
            error_log('ReTexify Meta-Description Generation Error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Focus-Keyword generieren
     */
    private function generate_focus_keyword_content($post, $content) {
        error_log('ReTexify: Einstieg generate_focus_keyword_content für Post-ID: ' . $post->ID);
        if (!$this->ai_engine) {
            error_log('ReTexify: AI-Engine ist null in generate_focus_keyword_content!');
            return '';
        }
        $settings = get_option('retexify_ai_settings', array());
        $masked_key = isset($settings['api_key']) ? substr($settings['api_key'], 0, 4) . '****' : 'NICHT GESETZT';
        error_log('ReTexify: Settings (API-Key maskiert): ' . print_r(array_merge($settings, ['api_key' => $masked_key]), true));
        $include_cantons = !empty($settings['target_cantons']);
        $premium_tone = !empty($settings['brand_voice']) && $settings['brand_voice'] === 'premium';
        try {
            $response = $this->ai_engine->generate_single_seo_item($post, 'focus_keyword', $settings, $include_cantons, $premium_tone);
            return trim(strtolower(str_replace('"', '', $response)));
        } catch (Exception $e) {
            error_log('ReTexify Focus-Keyword Generation Error: ' . $e->getMessage());
            return '';
        }
    }
    
    // ========================================================================
    // 🛠️ SEO-DATEN HELPER - DELEGIERT AN EXPORT-IMPORT-MANAGER
    // ========================================================================
    
    /**
     * Meta-Daten abrufen (delegiert an Export-Import-Manager)
     */
    private function get_meta_title($post_id) {
        return $this->export_import_manager->get_meta_title($post_id);
    }
    
    private function get_meta_description($post_id) {
        return $this->export_import_manager->get_meta_description($post_id);
    }
    
    private function get_focus_keyword($post_id) {
        return $this->export_import_manager->get_focus_keyword($post_id);
    }
    
    /**
     * Meta-Daten speichern (delegiert an Export-Import-Manager)
     */
    private function save_meta_title($post_id, $meta_title) {
        return $this->export_import_manager->save_meta_title($post_id, $meta_title);
    }
    
    private function save_meta_description($post_id, $meta_description) {
        return $this->export_import_manager->save_meta_description($post_id, $meta_description);
    }
    
    private function save_focus_keyword($post_id, $focus_keyword) {
        return $this->export_import_manager->save_focus_keyword($post_id, $focus_keyword);
    }
    
    // ========================================================================
    // 🛠️ HTML-GENERATOR-METHODEN - VOLLSTÄNDIG
    // ========================================================================
    
    // HTML-Generator-Funktionen wurden in ReTexify_System_Status verschoben
    
    // ========================================================================
    // 🧪 API-TEST-METHODEN - VOLLSTÄNDIG
    // ========================================================================
    
    // API-Test-Funktionen wurden in ReTexify_System_Status verschoben
    
    /**
     * Schnelle Provider-Tests für KI-APIs
     */
    private function quick_test_provider($provider, $api_key) {
        switch ($provider) {
            case 'openai':
                return $this->quick_test_openai($api_key);
            case 'anthropic':
                return $this->quick_test_anthropic($api_key);
            case 'gemini':
                return $this->quick_test_gemini($api_key);
            default:
                return array('status' => 'error', 'message' => 'Unbekannter Provider');
        }
    }
    
    private function quick_test_openai($api_key) {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 5,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent' => 'ReTexify-AI-Plugin'
            )
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => 'Verbindungsfehler');
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            return array('status' => 'success', 'message' => 'API-Key gültig');
        } else {
            return array('status' => 'error', 'message' => 'API-Key ungültig (Code: ' . $code . ')');
        }
    }
    
    private function quick_test_anthropic($api_key) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 5,
            'headers' => array(
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 1,
                'messages' => array(array('role' => 'user', 'content' => 'test'))
            ))
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => 'Verbindungsfehler');
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200 || $code === 400) { // 400 ist OK für unseren Test
            return array('status' => 'success', 'message' => 'API-Key gültig');
        } else {
            return array('status' => 'error', 'message' => 'API-Key ungültig (Code: ' . $code . ')');
        }
    }
    
    private function quick_test_gemini($api_key) {
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key, array(
            'timeout' => 5
        ));
        
        if (is_wp_error($response)) {
            return array('status' => 'error', 'message' => 'Verbindungsfehler');
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            return array('status' => 'success', 'message' => 'API-Key gültig');
        } else {
            return array('status' => 'error', 'message' => 'API-Key ungültig (Code: ' . $code . ')');
        }
    }
    
    // ========================================================================
    // 🛠️ EXPORT/IMPORT AJAX-HANDLER (Falls verfügbar)
    // ========================================================================
    
    public function handle_get_export_stats() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $stats = $this->export_import_manager->get_export_stats();
            wp_send_json_success($stats);
        } catch (Exception $e) {
            wp_send_json_error('Statistik-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_export_content_csv() {
        error_log('ReTexify: Einstieg handle_export_content_csv');
        if (!$this->export_import_manager) {
            error_log('ReTexify: Export/Import Manager nicht verfügbar!');
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            error_log('ReTexify: Sicherheitsfehler in handle_export_content_csv');
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        try {
            $post_types = array_map('sanitize_text_field', $_POST['post_types'] ?? array('post', 'page'));
            $status_types = array_map('sanitize_text_field', $_POST['status'] ?? array('publish'));
            $content_types = array_map('sanitize_text_field', $_POST['content'] ?? array());
            $result = $this->export_import_manager->export_to_csv($post_types, $status_types, $content_types);
            error_log('ReTexify: Export-Result: ' . print_r($result, true));
            if ($result['success'] && !empty($result['filename'])) {
                $download_nonce = wp_create_nonce('retexify_download_nonce');
                $download_url = admin_url('admin-ajax.php?action=retexify_download_export_file&filename=' . urlencode(basename($result['filename'])) . '&nonce=' . $download_nonce);
                error_log('ReTexify: Download-URL: ' . $download_url);
                wp_send_json_success(array(
                    'message' => 'Export erfolgreich. Download startet...',
                    'download_url' => $download_url,
                    'filename' => basename($result['filename']),
                    'file_size' => $result['file_size'],
                    'row_count' => $result['row_count']
                ));
            } else {
                error_log('ReTexify: Export-Fehler: ' . ($result['message'] ?? 'Unbekannter Fehler'));
                wp_send_json_error($result['message'] ?? 'Unbekannter Export-Fehler.');
            }
        } catch (Exception $e) {
            error_log('ReTexify Export-Fehler: ' . $e->getMessage());
            wp_send_json_error('Export-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_download_export_file() {
        error_log('ReTexify: Einstieg handle_download_export_file');
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'retexify_download_nonce') || !current_user_can('manage_options')) {
            error_log('ReTexify: Sicherheitsfehler in handle_download_export_file');
            wp_die('Sicherheitsfehler!');
        }
        $filename = sanitize_file_name(basename($_GET['filename'] ?? ''));
        if (empty($filename)) {
            error_log('ReTexify: Ungültiger Dateiname in handle_download_export_file');
            wp_die('Ungültiger Dateiname.');
        }
        $upload_dir = wp_upload_dir();
        $imports_dir = $upload_dir['basedir'] . '/retexify-ai/';
        $filepath = $imports_dir . $filename;
        error_log('ReTexify: Download-Filepath: ' . $filepath . ' (exists: ' . (file_exists($filepath) ? 'JA' : 'NEIN') . ')');
        if (!file_exists($filepath) || strpos(realpath($filepath), realpath($imports_dir)) !== 0) {
            error_log('ReTexify: Datei nicht gefunden oder Zugriff verweigert: ' . $filepath);
            wp_die('Datei nicht gefunden oder Zugriff verweigert.');
        }
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        flush(); 
        readfile($filepath);
        // unlink($filepath); // HOTFIX: Datei NICHT mehr löschen!
        exit;
    }
    
    public function handle_import_csv_data() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            if (!isset($_FILES['csv_file'])) {
                wp_send_json_error('Keine Datei hochgeladen');
                return;
            }
            
            $result = $this->export_import_manager->handle_csv_upload($_FILES['csv_file']);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Upload-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_get_import_preview() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $filename = sanitize_file_name($_POST['filename'] ?? '');
            
            if (empty($filename)) {
                wp_send_json_error('Ungültiger Dateiname');
                return;
            }
            
            $result = $this->export_import_manager->get_import_preview($filename);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Vorschau-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_save_imported_data() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $filename = sanitize_file_name($_POST['filename'] ?? '');
            $column_mapping = $_POST['column_mapping'] ?? array();
            
            if (empty($filename)) {
                wp_send_json_error('Ungültiger Dateiname');
                return;
            }
            
            $result = $this->export_import_manager->import_csv_data($filename, $column_mapping);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Import-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_get_export_preview() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_types = array_map('sanitize_text_field', $_POST['post_types'] ?? array('post', 'page'));
            $status_types = array_map('sanitize_text_field', $_POST['status'] ?? array('publish'));
            $content_types = array_map('sanitize_text_field', $_POST['content'] ?? array());
            
            $result = $this->export_import_manager->get_export_preview($post_types, $status_types, $content_types);
            
            if ($result['success']) {
                wp_send_json_success($result['data']);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Export-Vorschau-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_delete_upload() {
        if (!$this->export_import_manager) {
            wp_send_json_error('Export/Import Manager nicht verfügbar');
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $filename = sanitize_file_name($_POST['filename'] ?? '');
            
            if (empty($filename)) {
                wp_send_json_error('Ungültiger Dateiname');
                return;
            }
            
            $result = $this->export_import_manager->delete_uploaded_file($filename);
            
            if ($result['success']) {
                wp_send_json_success($result['message']);
            } else {
                wp_send_json_error($result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Lösch-Fehler: ' . $e->getMessage());
        }
    }
        
    // ========================================================================
    // 🔍 CONTENT-MANAGEMENT HANDLER
    // ========================================================================
    
    public function handle_analyze_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            
            if ($this->content_analyzer) {
                $analysis = $this->content_analyzer->analyze_content($post->post_content, $post->post_title);
                wp_send_json_success($analysis);
            } else {
                wp_send_json_error('Content-Analyzer nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Analyse-Fehler: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // 🔍 INTELLIGENT KEYWORD RESEARCH HANDLER
    // ========================================================================
    
    public function handle_keyword_research() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            $language = sanitize_text_field($_POST['language'] ?? 'de');
            
            if (empty($keyword)) {
                wp_send_json_error('Kein Keyword angegeben');
                return;
            }
            
            if ($this->keyword_research) {
                $results = $this->keyword_research->research_keywords($keyword, $language);
                wp_send_json_success($results);
            } else {
                wp_send_json_error('Keyword-Research nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Keyword-Research fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_analyze_competition() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            
            if (empty($keyword)) {
                wp_send_json_error('Kein Keyword angegeben');
                return;
            }
            
            if ($this->keyword_research) {
                $analysis = $this->keyword_research->analyze_competition($keyword);
                wp_send_json_success($analysis);
            } else {
                wp_send_json_error('Keyword-Research nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Competition-Analyse fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_get_suggestions() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $topic = sanitize_text_field($_POST['topic'] ?? '');
            
            if (empty($topic)) {
                wp_send_json_error('Kein Topic angegeben');
                return;
            }
            
            if ($this->keyword_research) {
                $suggestions = $this->keyword_research->get_suggestions($topic);
                wp_send_json_success($suggestions);
            } else {
                wp_send_json_error('Keyword-Research nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Suggestions-Abruf fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    // ========================================================================
    // 🛠️ LEGACY-HANDLER FÜR KOMPATIBILITÄT
    // ========================================================================
    
    // ========================================================================
    // 🛠️ UTILITY-HELPER-METHODEN - DELEGIERT AN HILFSKLASSEN
    // ========================================================================
    
    /**
     * API-Tests (delegiert an API-Manager)
     */
    private function test_api($url) {
        return ReTexify_API_Manager::test_api($url);
    }
    
    /**
     * Keyword/Topic-Suggestions (delegiert an API-Manager)
     */
    private function get_keyword_suggestions($keyword, $language = 'de') {
        return ReTexify_API_Manager::get_keyword_suggestions($keyword, $language);
    }
    
    private function get_topic_suggestions($topic) {
        return ReTexify_API_Manager::get_topic_suggestions($topic);
    }
    
    /**
     * SEO-Generierung (delegiert an AI-Engine)
     */
    private function generate_single_seo_for_post($post_id, $seo_type) {
        if (!$this->ai_engine) {
            return array('success' => false, 'error' => 'AI-Engine nicht verfügbar');
        }
        
        $post = get_post($post_id);
        if (!$post) {
            return array('success' => false, 'error' => 'Post nicht gefunden');
        }
        
        $settings = get_option('retexify_ai_settings', array());
        $include_cantons = !empty($settings['target_cantons']);
        $premium_tone = !empty($settings['brand_voice']) && $settings['brand_voice'] === 'premium';
        
        try {
            $generated_text = $this->ai_engine->generate_single_seo_item($post, $seo_type, $settings, $include_cantons, $premium_tone);
            
            if (empty($generated_text)) {
                return array('success' => false, 'error' => 'Generierung fehlgeschlagen');
            }
            
            return array(
                'success' => true,
                'data' => array(
                    'post_id' => $post_id,
                    'seo_type' => $seo_type,
                    'generated_text' => $generated_text
                )
            );
            
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }
    
    /**
     * Schweizer Kantone (delegiert an API-Manager)
     */
    private function get_swiss_cantons() {
        return ReTexify_API_Manager::get_swiss_cantons_list();
    }
    
    // ========================================================================
    // 🔧 SYSTEM-DIAGNOSTICS HANDLER - DELEGIERT AN SYSTEM-STATUS
    // ========================================================================
    
    public function ajax_get_system_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $system_info = $this->system_status->get_system_info();
            wp_send_json_success($system_info);
        } catch (Exception $e) {
            wp_send_json_error('System-Info-Abruf fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function ajax_check_requirements() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $requirements = $this->system_status->check_requirements();
            wp_send_json_success($requirements);
        } catch (Exception $e) {
            wp_send_json_error('Requirements-Check fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function ajax_diagnostic_report() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $report = $this->system_status->generate_diagnostic_report();
            wp_send_json_success($report);
        } catch (Exception $e) {
            wp_send_json_error('Diagnostic-Report fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function debug_ai_engine_status() {
        $status = array(
            'ai_engine_loaded' => is_object($this->ai_engine),
            'ai_engine_class' => is_object($this->ai_engine) ? get_class($this->ai_engine) : 'NULL',
            'ai_engine_methods' => is_object($this->ai_engine) ? get_class_methods($this->ai_engine) : array(),
            'settings' => get_option('retexify_ai_settings', array()),
            'api_keys' => array_map(function($key) { 
                return empty($key) ? 'EMPTY' : 'SET (' . substr($key, 0, 8) . '...)'; 
            }, get_option('retexify_api_keys', array()))
        );
        error_log('ReTexify AI-Engine Debug: ' . print_r($status, true));
        return $status;
    }
    
    /**
     * Performance-Metriken abrufen
     */
    public function ajax_get_performance_metrics() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $metrics = array();
            
            // Performance-Optimizer Metriken
            if (class_exists('ReTexify_Performance_Optimizer')) {
                $metrics['performance_optimizer'] = ReTexify_Performance_Optimizer::get_metrics();
            }
            
            // Gespeicherte Metriken
            $saved_metrics = get_option('retexify_performance_metrics', array());
            $metrics['saved_metrics'] = $saved_metrics;
            
            // System-Performance
            $metrics['system'] = array(
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version')
            );
            
            wp_send_json_success($metrics);
            
        } catch (Exception $e) {
            wp_send_json_error('Performance-Metriken Fehler: ' . $e->getMessage());
        }
    }
    
    /**
     * Migration: Alte API-Schlüssel bereinigen und in neue Struktur überführen
     */
    private function migrate_and_cleanup_old_api_keys() {
        // Neue API-Schlüssel-Struktur abrufen
        $new_api_keys = get_option('retexify_api_keys', array());
        
        // Alte API-Schlüssel prüfen und migrieren
        $old_keys_migrated = false;
        
        // OpenAI
        $old_openai_key = get_option('retexify_openai_api_key', '');
        if (!empty($old_openai_key) && empty($new_api_keys['openai'])) {
            $new_api_keys['openai'] = $old_openai_key;
            $old_keys_migrated = true;
            error_log('ReTexify: Alten OpenAI API-Schlüssel migriert');
        }
        
        // Anthropic
        $old_anthropic_key = get_option('retexify_anthropic_api_key', '');
        if (!empty($old_anthropic_key) && empty($new_api_keys['anthropic'])) {
            $new_api_keys['anthropic'] = $old_anthropic_key;
            $old_keys_migrated = true;
            error_log('ReTexify: Alten Anthropic API-Schlüssel migriert');
        }
        
        // Gemini
        $old_gemini_key = get_option('retexify_gemini_api_key', '');
        if (!empty($old_gemini_key) && empty($new_api_keys['gemini'])) {
            $new_api_keys['gemini'] = $old_gemini_key;
            $old_keys_migrated = true;
            error_log('ReTexify: Alten Gemini API-Schlüssel migriert');
        }
        
        // Neue Struktur speichern falls Migration erfolgt
        if ($old_keys_migrated) {
            update_option('retexify_api_keys', $new_api_keys);
        }
        
        // Alte API-Schlüssel-Optionen LÖSCHEN (Sicherheit!)
        delete_option('retexify_openai_api_key');
        delete_option('retexify_anthropic_api_key');
        delete_option('retexify_gemini_api_key');
        
        error_log('ReTexify: Alte API-Schlüssel-Optionen bereinigt');
    }

    private function generate_intelligent_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false) {
        error_log('ReTexify: Starting intelligent analysis for post: ' . $post->post_title);
        try {
            // 1. ✅ INTELLIGENTE CONTENT-ANALYSE
            $content = wp_strip_all_tags($post->post_content);
            if (!class_exists('ReTexify_Intelligent_Keyword_Research')) {
                error_log('ReTexify: Intelligent Keyword Research class not found, falling back to simple generation');
                return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            $analysis_settings = array_merge($settings, array(
                'include_cantons' => $include_cantons,
                'premium_tone' => $premium_tone,
                'business_context' => $settings['business_context'] ?? '',
                'target_audience' => $settings['target_audience'] ?? 'Schweizer KMU',
                'brand_voice' => $premium_tone ? 'premium' : ($settings['brand_voice'] ?? 'professional'),
                'target_cantons' => $settings['target_cantons'] ?? array(),
                'optimization_focus' => 'complete_seo'
            ));
            error_log('ReTexify: Running intelligent content analysis...');
            $analysis = ReTexify_Intelligent_Keyword_Research::analyze_content($content, $analysis_settings);
            if (empty($analysis) || empty($analysis['keyword_strategy'])) {
                error_log('ReTexify: Intelligent analysis failed, using fallback');
                return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            error_log('ReTexify: Intelligent analysis completed successfully. Primary keywords: ' . implode(', ', $analysis['primary_keywords'] ?? array()));
            $premium_prompt = ReTexify_Intelligent_Keyword_Research::create_premium_seo_prompt($content, $analysis_settings);
            if (empty($premium_prompt)) {
                error_log('ReTexify: Premium prompt generation failed');
                return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            error_log('ReTexify: Premium prompt generated, length: ' . strlen($premium_prompt));
            $seo_suite_prompt = $this->build_intelligent_seo_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone);
            error_log('ReTexify: Calling AI with intelligent prompt...');
            $ai_response = $this->ai_engine->call_ai_api($seo_suite_prompt, $settings);
            if (empty($ai_response)) {
                error_log('ReTexify: AI API returned empty response');
                return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            $parsed_suite = $this->parse_intelligent_seo_response($ai_response, $analysis);
            error_log('ReTexify: Intelligent SEO suite generated successfully');
            return $parsed_suite;
        } catch (Exception $e) {
            error_log('ReTexify: Exception in intelligent SEO generation: ' . $e->getMessage());
            return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
        }
    }

    private function build_intelligent_seo_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone) {
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        $business_context = !empty($settings['business_context']) ? $settings['business_context'] : 'Schweizer Unternehmen';
        $canton_text = '';
        if ($include_cantons && !empty($settings['target_cantons'])) {
            $cantons = is_array($settings['target_cantons']) ? implode(', ', $settings['target_cantons']) : $settings['target_cantons'];
            $canton_text = "Ziel-Kantone: {$cantons}";
        }
        $tone_instruction = $premium_tone ? 'Verwende einen premium, professionellen Business-Ton' : 'Verwende einen freundlichen, professionellen Ton';
        $primary_keywords = !empty($analysis['primary_keywords']) ? implode(', ', array_slice($analysis['primary_keywords'], 0, 5)) : '';
        $focus_keyword_suggestion = !empty($analysis['keyword_strategy']['focus_keyword']) ? $analysis['keyword_strategy']['focus_keyword'] : '';
        $long_tail_keywords = !empty($analysis['long_tail_keywords']) ? implode(', ', array_slice($analysis['long_tail_keywords'], 0, 3)) : '';
        $semantic_themes = !empty($analysis['semantic_themes']) ? implode(', ', array_slice($analysis['semantic_themes'], 0, 3)) : '';
        $prompt = "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst eine komplette, hochwertige SEO-Suite basierend auf einer detaillierten Content-Analyse.\n\n=== CONTENT-INFORMATIONEN ===\nTitel: {$title}\nContent: " . substr($content, 0, 1000) . "\n\n=== INTELLIGENTE ANALYSE-ERGEBNISSE ===\nPrimäre Keywords (aus Analyse): {$primary_keywords}\nEmpfohlenes Focus-Keyword: {$focus_keyword_suggestion}\nLong-Tail Keywords: {$long_tail_keywords}\nSemantische Themen: {$semantic_themes}\nContent-Qualität: " . ($analysis['content_quality']['overall_score'] ?? 'N/A') . "/100\nReadability-Score: " . ($analysis['readability_score'] ?? 'N/A') . "/100\n\n=== BUSINESS-KONTEXT ===\n{$business_context}\n{$canton_text}\n\n=== OPTIMIERUNGS-ANWEISUNGEN ===\n{$tone_instruction}\n\n=== PREMIUM-PROMPT-INTEGRATION ===\n{$premium_prompt}\n\n=== AUFGABE ===\nErstelle basierend auf der obigen INTELLIGENTEN ANALYSE eine komplette SEO-Suite mit hohem Mehrwert:\n\n1. **META_TITEL** (exakt 55-60 Zeichen):\n   - Nutze das empfohlene Focus-Keyword intelligent\n   - Berücksichtige die semantischen Themen\n   - Optimiert für Schweizer Suchverhalten\n   - Hohe Click-Through-Rate\n\n2. **META_BESCHREIBUNG** (exakt 150-155 Zeichen):\n   - Integriere primäre Keywords natürlich\n   - Nutze Long-Tail Keywords für mehr Relevanz\n   - Klarer Call-to-Action\n   - Lokaler Bezug zu den Ziel-Kantonen\n\n3. **FOCUS_KEYWORD** (1-3 Wörter):\n   - Basierend auf der Keyword-Analyse\n   - Hohes Suchvolumen in der Schweiz\n   - Kommerzieller Such-Intent\n   - Perfekt zum Content passend\n\nANTWORT-FORMAT (exakt so, damit automatisch geparst werden kann):\nMETA_TITEL: [dein optimierter Meta-Titel]\nMETA_BESCHREIBUNG: [deine optimierte Meta-Beschreibung]\nFOCUS_KEYWORD: [dein optimiertes Focus-Keyword]\n\nWichtig: Antworte NUR mit den drei Zeilen im angegebenen Format, nichts anderes!";
        return $prompt;
    }

    private function parse_intelligent_seo_response($ai_response, $analysis = null) {
        $lines = explode("\n", trim($ai_response));
        $suite = array(
            'meta_title' => '',
            'meta_description' => '',
            'focus_keyword' => ''
        );
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'META_TITEL:') === 0) {
                $suite['meta_title'] = trim(str_replace('META_TITEL:', '', $line));
            } elseif (strpos($line, 'META_BESCHREIBUNG:') === 0) {
                $suite['meta_description'] = trim(str_replace('META_BESCHREIBUNG:', '', $line));
            } elseif (strpos($line, 'FOCUS_KEYWORD:') === 0) {
                $suite['focus_keyword'] = trim(str_replace('FOCUS_KEYWORD:', '', $line));
            }
        }
        if (empty($suite['meta_title']) || empty($suite['meta_description']) || empty($suite['focus_keyword'])) {
            $clean_lines = array_filter(array_map('trim', $lines));
            $clean_lines = array_values($clean_lines);
            if (count($clean_lines) >= 3) {
                if (empty($suite['meta_title'])) $suite['meta_title'] = $clean_lines[0];
                if (empty($suite['meta_description'])) $suite['meta_description'] = $clean_lines[1];
                if (empty($suite['focus_keyword'])) $suite['focus_keyword'] = $clean_lines[2];
            }
        }
        if ($analysis) {
            $suite['analysis_data'] = array(
                'primary_keywords' => $analysis['primary_keywords'] ?? array(),
                'long_tail_keywords' => $analysis['long_tail_keywords'] ?? array(),
                'content_quality_score' => $analysis['content_quality']['overall_score'] ?? 0,
                'readability_score' => $analysis['readability_score'] ?? 0,
                'semantic_themes' => $analysis['semantic_themes'] ?? array(),
                'processing_time' => $analysis['processing_time'] ?? 0
            );
        }
        return $suite;
    }

    private function generate_simple_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false) {
        error_log('ReTexify: Using simple SEO generation as fallback');
        $results = array();
        $seo_types = array('meta_title', 'meta_description', 'focus_keyword');
        foreach ($seo_types as $seo_type) {
            try {
                error_log("ReTexify: Generating simple $seo_type...");
                $generated_text = $this->ai_engine->generate_single_seo_item(
                    $post, 
                    $seo_type, 
                    $settings,
                    $include_cantons,
                    $premium_tone
                );
                if (!empty($generated_text)) {
                    $results[$seo_type] = $generated_text;
                    error_log("ReTexify: Generated simple $seo_type: " . substr($generated_text, 0, 50) . "...");
                } else {
                    error_log("ReTexify: Empty result for simple $seo_type");
                }
                usleep(500000);
            } catch (Exception $e) {
                error_log("ReTexify: Error generating simple $seo_type: " . $e->getMessage());
            }
        }
        $results['fallback_used'] = true;
        $results['research_mode'] = 'simple';
        return $results;
    }

    private function call_ai_api_direct($prompt, $settings) {
        if (method_exists($this->ai_engine, 'call_ai_api')) {
            return $this->ai_engine->call_ai_api($prompt, $settings);
        } elseif (method_exists($this->ai_engine, 'generate_content')) {
            return $this->ai_engine->generate_content($prompt, $settings);
        } else {
            throw new Exception('AI-Engine hat keine verfügbare API-Call-Methode');
        }
    }
}
}

// ============================================================================
// 🚀 SICHERE PLUGIN-INITIALISIERUNG
// ============================================================================

try {
    // Plugin nur initialisieren wenn WordPress bereit ist
    if (defined('ABSPATH') && !wp_installing()) {
        new ReTexify_AI_Pro_Universal();
    }
} catch (Exception $e) {
    // Fehler protokollieren ohne WordPress zum Absturz zu bringen
    error_log('ReTexify AI Plugin Initialization Error: ' . $e->getMessage());
    
    // Admin-Benachrichtigung hinzufügen
    if (is_admin()) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>ReTexify AI:</strong> Plugin konnte nicht geladen werden. ';
            echo 'Fehler: ' . esc_html($e->getMessage());
            echo '</p></div>';
        });
    }
}

?>
