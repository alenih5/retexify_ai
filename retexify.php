<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen
 * Version: 4.2.0
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
if (!defined('RETEXIFY_VERSION')) {
    define('RETEXIFY_VERSION', '4.2.0');
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
    'includes/class-german-content-analyzer.php',
    'includes/class-ai-engine.php',
    
    // Erweiterte Handler
    'includes/class-system-status.php',
    'includes/class-seo-generator.php',
    
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
    }
    
    // ========================================================================
    // 🔧 INITIALISIERUNG
    // ========================================================================
    
    private function init_classes() {
        // Content-Analyzer initialisieren
        if (function_exists('retexify_get_content_analyzer')) {
            $this->content_analyzer = retexify_get_content_analyzer();
        }
        
        // AI-Engine initialisieren
        if (function_exists('retexify_get_ai_engine')) {
            $this->ai_engine = retexify_get_ai_engine();
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
            'retexify_refresh_stats' => 'ajax_get_stats',
            
            // SEO Optimizer - ALLE HANDLER
            'retexify_load_content' => 'handle_load_seo_content',
            'retexify_generate_single_seo' => 'handle_generate_single_seo',
            'retexify_generate_meta_title' => 'handle_generate_meta_title',
            'retexify_generate_meta_description' => 'handle_generate_meta_description',
            'retexify_generate_keywords' => 'handle_generate_keywords',
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
            'retexify_research_keywords' => 'handle_research_keywords',
            
            // System & Diagnostics
            'retexify_test_system' => 'ajax_test_system',
            'retexify_test_research_apis' => 'ajax_test_research_apis',
            'retexify_test_api_services' => 'ajax_test_research_apis', // Alias
            'retexify_get_system_info' => 'ajax_get_system_info',
            'retexify_check_requirements' => 'ajax_check_requirements',
            'retexify_diagnostic_report' => 'ajax_diagnostic_report',
            
            // Export/Import (falls verfügbar)
            'retexify_export_data' => 'handle_export_content_csv',
            'retexify_import_data' => 'handle_import_csv_data',
            'retexify_get_export_stats' => 'handle_get_export_stats',
            'retexify_export_content_csv' => 'handle_export_content_csv',
            'retexify_import_csv_data' => 'handle_import_csv_data',
            'retexify_get_import_preview' => 'handle_get_import_preview',
            'retexify_save_imported_data' => 'handle_save_imported_data',
            'retexify_delete_upload' => 'handle_delete_upload',
            'retexify_download_export_file' => 'handle_download_export_file',
            
            // Content-Management
            'retexify_bulk_optimize' => 'handle_bulk_optimize',
            'retexify_schedule_optimization' => 'handle_schedule_optimization',
            'retexify_get_optimization_queue' => 'handle_get_optimization_queue'
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
                'retexify_export_data', 'retexify_import_data', 'retexify_get_export_stats',
                'retexify_export_content_csv', 'retexify_import_csv_data', 'retexify_get_import_preview',
                'retexify_save_imported_data', 'retexify_delete_upload', 'retexify_download_export_file'
            ))) {
                if ($this->export_import_manager && method_exists($this->export_import_manager, $method)) {
                    add_action('wp_ajax_' . $action, array($this->export_import_manager, $method));
                    add_action('wp_ajax_nopriv_' . $action, array($this->export_import_manager, $method));
                }
            }
            
            // Intelligent Research separat behandeln
            elseif (in_array($action, array(
                'retexify_keyword_research', 'retexify_analyze_competition', 
                'retexify_get_suggestions', 'retexify_research_keywords'
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
        
        // 3. System-Status-Fixes CSS
        $system_css_file = RETEXIFY_PLUGIN_PATH . 'assets/system-status-fixes.css';
        if (file_exists($system_css_file)) {
            wp_enqueue_style(
                'retexify-system-status-fixes', 
                RETEXIFY_PLUGIN_URL . 'assets/system-status-fixes.css', 
                array('retexify-admin-style', 'retexify-admin-style-extended'),
                RETEXIFY_VERSION . '-' . filemtime($system_css_file),
                'all'
            );
        }
        
        // 4. Inline-CSS für kritische System-Status-Fixes
        $inline_css = '
            /* Kritische System-Status-Fixes - sofort geladen */
            #retexify-system-status {
                min-height: 150px;
                position: relative;
                background: #fff;
                border-radius: 6px;
                overflow: hidden;
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
                z-index: 10;
            }
            
            .retexify-system-status-content {
                opacity: 0;
                animation: fadeInSystem 0.5s ease-in-out forwards;
            }
            
            @keyframes fadeInSystem {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Sicherstellen dass Header-Badge klickbar ist */
            #retexify-test-system-badge {
                cursor: pointer;
                user-select: none;
                transition: all 0.2s ease;
            }
            
            /* Status-Box-Rendering-Fix */
            .status-item {
                display: flex !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 8px 0 !important;
                border-bottom: 1px solid #f0f0f1 !important;
            }
            
            .status-indicator {
                flex-shrink: 0 !important;
                min-width: 80px !important;
                text-align: center !important;
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
        return get_option('retexify_api_keys', array(
            'openai' => '',
            'anthropic' => '',
            'gemini' => ''
        ));
    }
    
    public function admin_page() {
        $ai_settings = get_option('retexify_ai_settings', array());
        $ai_enabled = $this->is_ai_enabled();
        $api_keys = $this->get_all_api_keys();
        $export_import_available = $this->export_import_manager !== null;
        
        // KI-Engine Instanz für Provider/Model-Daten
        $available_providers = array();
        if ($this->ai_engine && method_exists($this->ai_engine, 'get_available_providers')) {
            $available_providers = $this->ai_engine->get_available_providers();
        } else {
            // Fallback Provider
            $available_providers = array(
                'openai' => 'OpenAI (GPT-4o, GPT-4o Mini)',
                'anthropic' => 'Anthropic Claude (3.5 Sonnet, Haiku)',
                'gemini' => 'Google Gemini (Pro, Flash)'
            );
        }
        ?>
        <div class="retexify-light-wrap">
            <div class="retexify-header">
                <h1>🇨🇭 ReTexify AI - Universeller SEO-Optimizer</h1>
                <p class="retexify-subtitle">Intelligente SEO-Optimierung für alle Branchen • Multi-KI-Support (OpenAI, Claude, Gemini) • Version <?php echo RETEXIFY_VERSION; ?></p>
            </div>

            <div class="retexify-tabs">
                <div class="retexify-tab-nav">
                    <button class="retexify-tab-btn active" data-tab="dashboard">📊 Dashboard</button>
                    <button class="retexify-tab-btn" data-tab="seo-optimizer">🚀 SEO-Optimizer</button>
                    <button class="retexify-tab-btn" data-tab="ai-settings">⚙️ KI-Einstellungen</button>
                    <?php if ($export_import_available): ?>
                    <button class="retexify-tab-btn" data-tab="export-import">📤 Export/Import</button>
                    <?php endif; ?>
                    <button class="retexify-tab-btn" data-tab="system">🔧 System</button>
                </div>
                
                <!-- Tab 1: Dashboard -->
                <div class="retexify-tab-content active" id="tab-dashboard">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>📊 Content-Dashboard</h2>
                            <div class="retexify-header-badge" id="retexify-refresh-stats-badge">
                                🔄 Aktualisieren
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="retexify-dashboard-content">
                                <div class="retexify-loading">Lade Dashboard...</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 2: SEO-Optimizer -->
                <div class="retexify-tab-content" id="tab-seo-optimizer">
                    <?php if ($ai_enabled): ?>
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🚀 Intelligenter SEO-Optimizer</h2>
                            <div class="retexify-header-badge">
                                🤖 Aktiv: <?php echo $available_providers[$ai_settings['api_provider']] ?? 'Unbekannt'; ?>
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            
                            <!-- SEO Controls -->
                            <div class="retexify-seo-controls">
                                <div class="retexify-control-group">
                                    <label for="seo-post-type">Post-Typ wählen:</label>
                                    <select id="seo-post-type" class="retexify-select">
                                        <option value="post">Beiträge</option>
                                        <option value="page">Seiten</option>
                                    </select>
                                </div>
                                
                                <button type="button" id="retexify-load-seo-content" class="retexify-btn retexify-btn-primary">
                                    📄 SEO-Content laden
                                </button>
                            </div>
                            
                            <!-- SEO Content List -->
                            <div id="retexify-seo-content-list" style="display: none;">
                                <div class="retexify-seo-navigation">
                                    <button type="button" id="retexify-seo-prev" class="retexify-btn retexify-btn-secondary" disabled>
                                        ← Vorherige
                                    </button>
                                    <span id="retexify-seo-counter" class="retexify-counter">1 / 10</span>
                                    <button type="button" id="retexify-seo-next" class="retexify-btn retexify-btn-secondary">
                                        Nächste →
                                    </button>
                                </div>
                                
                                <!-- Current Page Info -->
                                <div class="retexify-current-page-info">
                                    <h3 id="retexify-current-page-title">Seite wird geladen...</h3>
                                    <div class="retexify-page-meta">
                                        <p id="retexify-page-info">Seiten-Informationen...</p>
                                        <div class="retexify-page-actions">
                                            <a id="retexify-page-url" href="#" target="_blank" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                🔗 Seite anzeigen
                                            </a>
                                            <a id="retexify-edit-page" href="#" target="_blank" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                ✏️ Bearbeiten
                                            </a>
                                            <button type="button" id="retexify-show-content" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                📄 Vollständigen Content anzeigen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Content Display -->
                                <div id="retexify-full-content" class="retexify-content-display" style="display: none;">
                                    <h4>📄 Vollständiger Seiteninhalt:</h4>
                                    <div id="retexify-content-text" class="retexify-content-box">
                                        Content wird geladen...
                                    </div>
                                    <div class="retexify-content-stats">
                                        <span id="retexify-word-count">0 Wörter</span> • 
                                        <span id="retexify-char-count">0 Zeichen</span>
                                    </div>
                                </div>
                                
                                <!-- SEO Editing Area -->
                                <div class="retexify-seo-editor">
                                    <div class="retexify-seo-current">
                                        <h4>🔍 Aktuelle SEO-Daten:</h4>
                                        <div class="retexify-seo-grid">
                                            <div class="retexify-seo-item">
                                                <label>Meta-Titel (aktuell):</label>
                                                <div id="retexify-current-meta-title" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label>Meta-Beschreibung (aktuell):</label>
                                                <div id="retexify-current-meta-description" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label>Focus-Keyword (aktuell):</label>
                                                <div id="retexify-current-focus-keyword" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-seo-new">
                                        <h4>✨ Neue SEO-Daten (KI-optimiert):</h4>
                                        <div class="retexify-seo-grid">
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-meta-title">Meta-Titel (neu):</label>
                                                <input type="text" id="retexify-new-meta-title" class="retexify-input" placeholder="Neuer Meta-Titel...">
                                                <div class="retexify-input-footer">
                                                    <div class="retexify-char-counter">
                                                        <span id="title-chars">0</span>/60 Zeichen
                                                    </div>
                                                    <button type="button" class="retexify-generate-single" data-type="meta_title">
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-meta-description">Meta-Beschreibung (neu):</label>
                                                <textarea id="retexify-new-meta-description" class="retexify-textarea" placeholder="Neue Meta-Beschreibung..."></textarea>
                                                <div class="retexify-input-footer">
                                                    <div class="retexify-char-counter">
                                                        <span id="description-chars">0</span>/160 Zeichen
                                                    </div>
                                                    <button type="button" class="retexify-generate-single" data-type="meta_description">
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-focus-keyword">Focus-Keyword (neu):</label>
                                                <input type="text" id="retexify-new-focus-keyword" class="retexify-input" placeholder="Neues Focus-Keyword...">
                                                <div class="retexify-input-footer keyword">
                                                    <button type="button" class="retexify-generate-single" data-type="focus_keyword">
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Generation Options -->
                                        <div class="retexify-generation-options">
                                            <h5>🛠️ Generierungs-Optionen:</h5>
                                            <label class="retexify-checkbox">
                                                <input type="checkbox" id="retexify-include-cantons" checked>
                                                Schweizer Kantone berücksichtigen
                                            </label>
                                            <label class="retexify-checkbox">
                                                <input type="checkbox" id="retexify-premium-tone" checked>
                                                Premium Business-Ton verwenden
                                            </label>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="retexify-seo-actions">
                                            <button type="button" id="retexify-generate-all-seo" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                ✨ Alle Texte generieren
                                            </button>
                                            <button type="button" id="retexify-save-seo-texts" class="retexify-btn retexify-btn-success retexify-btn-large">
                                                💾 Änderungen speichern
                                            </button>
                                            <button type="button" id="retexify-clear-seo-fields" class="retexify-btn retexify-btn-secondary retexify-btn-large">
                                                🗑️ Felder leeren
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Generation Results -->
                                <div id="retexify-seo-results"></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="retexify-card">
                        <div class="retexify-card-body">
                            <div class="retexify-warning">
                                <h3>🔧 KI-Setup erforderlich</h3>
                                <p>Bitte konfigurieren Sie zuerst Ihren KI-Provider und API-Schlüssel in den KI-Einstellungen.</p>
                                <button type="button" class="retexify-btn retexify-btn-primary" onclick="jQuery('.retexify-tab-btn[data-tab=ai-settings]').click();">
                                    Zu den KI-Einstellungen
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 3: KI-Einstellungen -->
                <div class="retexify-tab-content" id="tab-ai-settings">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>⚙️ KI-Einstellungen</h2>
                            <div class="retexify-header-badge">
                                🤖 Multi-KI Support (OpenAI, Claude, Gemini)
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <form id="retexify-ai-settings-form">
                                <div class="retexify-settings-grid">
                                    
                                    <!-- API Settings -->
                                    <div class="retexify-settings-group retexify-settings-provider">
                                        <h3>🔑 KI-Provider & API-Einstellungen</h3>
                                        
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-provider">KI-Provider wählen:</label>
                                            <select id="ai-provider" name="api_provider" class="retexify-select">
                                                <?php foreach ($available_providers as $provider_key => $provider_name): ?>
                                                <option value="<?php echo esc_attr($provider_key); ?>" 
                                                        <?php selected($ai_settings['api_provider'] ?? 'openai', $provider_key); ?>>
                                                    <?php echo esc_html($provider_name); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small>Wählen Sie Ihren bevorzugten KI-Provider</small>
                                        </div>
                                        
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-api-key">API-Schlüssel:</label>
                                            <input type="password" id="ai-api-key" name="api_key" 
                                                   value="<?php echo esc_attr($api_keys[$ai_settings['api_provider'] ?? 'openai'] ?? ''); ?>" 
                                                   class="retexify-input" placeholder="Ihr API-Schlüssel...">
                                            <small id="api-key-help">
                                                OpenAI: Erhältlich auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a><br>
                                                Anthropic: Erhältlich auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a><br>
                                                Google: Erhältlich auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a>
                                            </small>
                                        </div>
                                        
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-model">KI-Modell:</label>
                                            <select id="ai-model" name="model" class="retexify-select">
                                                <option value="gpt-4o-mini">GPT-4o Mini (Empfohlen)</option>
                                                <option value="gpt-4o">GPT-4o (Premium)</option>
                                                <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet</option>
                                                <option value="gemini-1.5-flash-latest">Gemini 1.5 Flash</option>
                                            </select>
                                            <small id="model-help">Das Modell bestimmt Qualität und Kosten der KI-Generierung</small>
                                        </div>
                                        
                                        <!-- Provider-Vergleich -->
                                        <div class="retexify-provider-comparison">
                                            <h4 id="current-provider-title">📊 OpenAI GPT:</h4>
                                            <div id="current-provider-info" class="retexify-provider-card">
                                                <ul>
                                                    <li>Sehr günstig (GPT-4o Mini)</li>
                                                    <li>Bewährt für SEO</li>
                                                    <li>Schnell & zuverlässig</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Business Context -->
                                    <div class="retexify-settings-group retexify-settings-business">
                                        <h3>🏢 Business-Kontext</h3>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-business-context">Ihr Business/Branche:</label>
                                            <textarea id="ai-business-context" name="business_context" 
                                                      class="retexify-textarea" rows="3"
                                                      placeholder="z.B. Online-Shop für Sportartikel, IT-Beratung für KMU, Restaurant in Zürich..."><?php echo esc_textarea($ai_settings['business_context'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-target-audience">Zielgruppe:</label>
                                            <input type="text" id="ai-target-audience" name="target_audience" 
                                                   value="<?php echo esc_attr($ai_settings['target_audience'] ?? ''); ?>" 
                                                   class="retexify-input" 
                                                   placeholder="z.B. Geschäftskunden, Familien, Technik-Enthusiasten...">
                                        </div>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-brand-voice">Markenstimme:</label>
                                            <select id="ai-brand-voice" name="brand_voice" class="retexify-select">
                                                <option value="professional" <?php selected($ai_settings['brand_voice'] ?? 'professional', 'professional'); ?>>
                                                    Professionell
                                                </option>
                                                <option value="friendly" <?php selected($ai_settings['brand_voice'] ?? '', 'friendly'); ?>>
                                                    Freundlich & einladend
                                                </option>
                                                <option value="expert" <?php selected($ai_settings['brand_voice'] ?? '', 'expert'); ?>>
                                                    Experte & kompetent
                                                </option>
                                                <option value="premium" <?php selected($ai_settings['brand_voice'] ?? '', 'premium'); ?>>
                                                    Premium & exklusiv
                                                </option>
                                                <option value="casual" <?php selected($ai_settings['brand_voice'] ?? '', 'casual'); ?>>
                                                    Locker & modern
                                                </option>
                                            </select>
                                        </div>

                                        <div class="retexify-field">
                                            <label for="seo-optimization-focus">Optimierungs-Fokus:</label>
                                            <select id="seo-optimization-focus" name="optimization_focus" class="retexify-select">
                                                <option value="complete_seo" <?php selected($ai_settings['optimization_focus'] ?? 'complete_seo', 'complete_seo'); ?>>Komplette SEO-Optimierung</option>
                                                <option value="local_seo_swiss" <?php selected($ai_settings['optimization_focus'] ?? '', 'local_seo_swiss'); ?>>Schweizer Local SEO</option>
                                                <option value="conversion" <?php selected($ai_settings['optimization_focus'] ?? '', 'conversion'); ?>>Conversion-optimiert</option>
                                                <option value="readability" <?php selected($ai_settings['optimization_focus'] ?? '', 'readability'); ?>>Lesbarkeit & Verständlichkeit</option>
                                                <option value="branding" <?php selected($ai_settings['optimization_focus'] ?? '', 'branding'); ?>>Markenaufbau & Trust</option>
                                                <option value="ecommerce" <?php selected($ai_settings['optimization_focus'] ?? '', 'ecommerce'); ?>>E-Commerce & Verkauf</option>
                                                <option value="b2b" <?php selected($ai_settings['optimization_focus'] ?? '', 'b2b'); ?>>B2B & Professional</option>
                                                <option value="news_blog" <?php selected($ai_settings['optimization_focus'] ?? '', 'news_blog'); ?>>News & Blog-Content</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Schweizer Kantone -->
                                    <div class="retexify-settings-group retexify-full-width">
                                        <h3>🇨🇭 Schweizer Kantone für Local SEO</h3>
                                        <p class="retexify-description">
                                            Wählen Sie die Kantone aus, in denen Ihr Business aktiv ist:
                                        </p>
                                        
                                        <div class="retexify-canton-grid">
                                            <?php 
                                            $swiss_cantons = $this->get_swiss_cantons();
                                            $selected_cantons = $ai_settings['target_cantons'] ?? array();
                                            foreach ($swiss_cantons as $code => $name): 
                                            ?>
                                            <label class="retexify-canton-item">
                                                <input type="checkbox" name="target_cantons[]" value="<?php echo $code; ?>" 
                                                       <?php checked(in_array($code, $selected_cantons)); ?>>
                                                <span class="retexify-canton-code"><?php echo $code; ?></span>
                                                <span class="retexify-canton-name"><?php echo $name; ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <div class="retexify-canton-actions">
                                            <button type="button" id="retexify-select-all-cantons" class="retexify-btn retexify-btn-secondary">
                                                Alle auswählen
                                            </button>
                                            <button type="button" id="retexify-select-main-cantons" class="retexify-btn retexify-btn-secondary">
                                                Hauptkantone
                                            </button>
                                            <button type="button" id="retexify-clear-cantons" class="retexify-btn retexify-btn-secondary">
                                                Alle abwählen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="retexify-settings-actions">
                                    <button type="button" id="retexify-ai-test-connection" class="retexify-btn retexify-btn-secondary">
                                        🔗 Verbindung testen
                                    </button>
                                    <button type="submit" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                        💾 Einstellungen speichern
                                    </button>
                                </div>
                            </form>
                            
                            <div id="retexify-ai-settings-result"></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($export_import_available): ?>
                <!-- Tab 4: Export/Import -->
                <div class="retexify-tab-content" id="tab-export-import">
                    <div class="retexify-export-import-container">
                        
                        <!-- Export Sektion -->
                        <div class="retexify-card">
                            <div class="retexify-card-header">
                                <h2>📤 Content Export</h2>
                                <div class="retexify-header-badge">CSV-Export für SEO-Daten</div>
                            </div>
                            <div class="retexify-card-body">
                                <div class="retexify-export-controls">
                                    <div class="retexify-export-options">
                                        <h4>📋 Export-Optionen:</h4>
                                        
                                        <!-- Post-Typen Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">🗂️ Post-Typen:</label>
                                            <div class="retexify-checkbox-grid">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_post_types[]" value="post" checked>
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Beiträge
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_post_types[]" value="page" checked>
                                                    <span class="retexify-checkbox-icon">📄</span>
                                                    Seiten
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">🔘 Status:</label>
                                            <div class="retexify-checkbox-grid">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_status[]" value="publish" checked>
                                                    <span class="retexify-checkbox-icon">✅</span>
                                                    Veröffentlicht
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_status[]" value="draft">
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Entwürfe
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <!-- Content-Typen Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">📋 Inhalte:</label>
                                            <div class="retexify-checkbox-grid" id="retexify-export-content-options">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="title" checked>
                                                    <span class="retexify-checkbox-icon">🏷️</span>
                                                    Titel
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_meta_title" checked>
                                                    <span class="retexify-checkbox-icon">🎯</span>
                                                    Yoast Meta-Titel
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_meta_description" checked>
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Yoast Meta-Beschreibung
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_focus_keyword" checked>
                                                    <span class="retexify-checkbox-icon">🔍</span>
                                                    Yoast Focus-Keyword
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="alt_texts">
                                                    <span class="retexify-checkbox-icon">🖼️</span>
                                                    Alt-Texte
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-export-actions">
                                        <button type="button" id="retexify-preview-export" class="retexify-btn retexify-btn-secondary retexify-btn-large">
                                            👁️ Vorschau anzeigen
                                        </button>
                                        <button type="button" id="retexify-start-export" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                            📤 CSV Export starten
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Export Vorschau -->
                                <div id="retexify-export-preview" class="retexify-export-preview" style="display: none;">
                                    <!-- Dynamisch per JavaScript gefüllt -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Import Sektion -->
                        <div class="retexify-card">
                            <div class="retexify-card-header">
                                <h2>📥 Content Import</h2>
                                <div class="retexify-header-badge">CSV-Import für SEO-Daten</div>
                            </div>
                            <div class="retexify-card-body">
                                <div class="retexify-import-controls">
                                    
                                    <!-- Upload Bereich -->
                                    <div class="retexify-upload-area" id="retexify-csv-upload-area">
                                        <div class="retexify-upload-icon">📁</div>
                                        <div class="retexify-upload-text">
                                            <h4>CSV-Datei hier ablegen oder klicken</h4>
                                            <p>Unterstützte Formate: .csv (max. <?php echo size_format(wp_max_upload_size()); ?>)</p>
                                        </div>
                                        <input type="file" id="retexify-csv-file-input" accept=".csv" style="display: none;">
                                    </div>
                                    
                                    <!-- Import Ergebnisse -->
                                    <div id="retexify-import-results" style="display: none;">
                                        <!-- Dynamisch per JavaScript gefüllt -->
                                    </div>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hilfe-Text für Import -->
                    <div class="retexify-card" style="margin-top: 20px;">
                        <div class="retexify-card-header">
                            <h2>💡 Import-Anleitung</h2>
                            <div class="retexify-header-badge">Wie funktioniert der Import?</div>
                        </div>
                        <div class="retexify-card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                
                                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                    <h4 style="margin: 0 0 10px 0; color: #007bff;">📤 1. Exportieren</h4>
                                    <p style="margin: 0; font-size: 14px; color: #495057;">
                                        Exportieren Sie zuerst Ihre aktuellen SEO-Daten mit dem Export-Tool.
                                    </p>
                                </div>
                                
                                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                    <h4 style="margin: 0 0 10px 0; color: #28a745;">✏️ 2. Bearbeiten</h4>
                                    <p style="margin: 0; font-size: 14px; color: #495057;">
                                        Bearbeiten Sie die CSV-Datei und füllen Sie die "Neu"-Spalten mit Ihren gewünschten Inhalten.
                                    </p>
                                </div>
                                
                                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                    <h4 style="margin: 0 0 10px 0; color: #6f42c1;">📥 3. Importieren</h4>
                                    <p style="margin: 0; font-size: 14px; color: #495057;">
                                        Ziehen Sie die bearbeitete CSV-Datei in den Import-Bereich und starten Sie den Import.
                                    </p>
                                </div>
                                
                            </div>
                            
                            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-top: 20px;">
                                <p style="margin: 0; font-size: 13px; color: #856404;">
                                    <strong>⚠️ Wichtig:</strong> Nur die "Neu"-Spalten werden beim Import übernommen. 
                                    Die "Original"-Spalten dienen zur Orientierung und werden nicht verändert.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tab 5: System -->
                <div class="retexify-tab-content" id="tab-system">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🔧 System-Status</h2>
                            <div class="retexify-header-badge" id="retexify-test-system-badge">
                                🧪 System testen
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="retexify-system-status">
                                <div class="retexify-loading">Lade System-Status...</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Intelligent Research Status -->
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🧠 Intelligent Research Engine</h2>
                            <div class="retexify-header-badge" id="retexify-test-research-badge">
                                🔄 APIs testen
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="retexify-research-engine-status">
                                <div class="retexify-loading">Lade Research-Status...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript für dynamische Provider/Model Auswahl -->
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('ReTexify Admin Page JavaScript startet...');
            
            // Verfügbare Modelle für jeden Provider
            var providerModels = {
                'openai': {
                    'gpt-4o-mini': 'GPT-4o Mini (Empfohlen - Günstig & Schnell)',
                    'gpt-4o': 'GPT-4o (Premium - Beste Qualität)',
                    'o1-mini': 'o1 Mini (Reasoning - Sehr smart)',
                    'gpt-4-turbo': 'GPT-4 Turbo (Ausgewogen)',
                    'gpt-3.5-turbo': 'GPT-3.5 Turbo (Günstig)'
                },
                'anthropic': {
                    'claude-3-5-sonnet-20241022': 'Claude 3.5 Sonnet (Empfohlen - Beste Balance)',
                    'claude-3-5-haiku-20241022': 'Claude 3.5 Haiku (Neu - Schnell & Günstig)',
                    'claude-3-opus-20240229': 'Claude 3 Opus (Premium - Beste Qualität)',
                    'claude-3-haiku-20240307': 'Claude 3 Haiku (Budget)'
                },
                'gemini': {
                    'gemini-1.5-pro-latest': 'Gemini 1.5 Pro (Empfohlen - Beste Qualität)',
                    'gemini-1.5-flash-latest': 'Gemini 1.5 Flash (Schnell & Günstig)',
                    'gemini-1.0-pro-latest': 'Gemini 1.0 Pro (Bewährt)'
                }
            };
            
            // API Keys für jeden Provider
            var apiKeys = <?php echo json_encode($api_keys); ?>;
            
            var currentProvider = '<?php echo esc_js($ai_settings['api_provider'] ?? 'openai'); ?>';
            var currentModel = '<?php echo esc_js($ai_settings['model'] ?? ''); ?>';
            
            console.log('Provider Models:', providerModels);
            console.log('Current Provider:', currentProvider);
            
            // Provider Wechsel Handler
            $('#ai-provider').change(function() {
                var selectedProvider = $(this).val();
                console.log('Provider gewechselt zu:', selectedProvider);
                currentProvider = selectedProvider;
                
                updateModelsForProvider(selectedProvider);
                updateApiKeyHelp(selectedProvider);
                updateProviderComparison(selectedProvider);
                
                // API-Key für neuen Provider laden
                $('#ai-api-key').val(apiKeys[selectedProvider] || '');
            });
            
            function updateModelsForProvider(provider) {
                var $modelSelect = $('#ai-model');
                var models = providerModels[provider] || {};
                
                console.log('Lade Modelle für Provider:', provider, models);
                
                $modelSelect.empty();
                
                $.each(models, function(modelKey, modelName) {
                    var selected = (modelKey === currentModel) ? 'selected' : '';
                    $modelSelect.append('<option value="' + modelKey + '" ' + selected + '>' + modelName + '</option>');
                });
                
                if ($modelSelect.find('option').length === 0) {
                    $modelSelect.append('<option value="">Keine Modelle verfügbar</option>');
                }
            }
            
            function updateApiKeyHelp(provider) {
                var helpTexts = {
                    'openai': 'OpenAI: Erhältlich auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a><br>Format: sk-...',
                    'anthropic': 'Anthropic: Erhältlich auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a><br>Format: sk-ant-...',
                    'gemini': 'Google: Erhältlich auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a><br>Format: AIza...'
                };
                
                $('#api-key-help').html(helpTexts[provider] || 'API-Schlüssel eingeben');
            }
            
            function updateProviderComparison(provider) {
                var providerInfo = {
                    'openai': {
                        title: '📊 OpenAI GPT:',
                        features: [
                            'Sehr günstig (GPT-4o Mini)',
                            'Bewährt für SEO',
                            'Schnell & zuverlässig'
                        ]
                    },
                    'anthropic': {
                        title: '📊 Anthropic Claude:',
                        features: [
                            'Ausgezeichnete Textqualität',
                            'Sehr präzise Anweisungen',
                            'Ethisch ausgerichtet'
                        ]
                    },
                    'gemini': {
                        title: '📊 Google Gemini:',
                        features: [
                            'Innovative Technologie',
                            'Sehr kostengünstig',
                            'Schnelle Performance'
                        ]
                    }
                };
                
                var info = providerInfo[provider];
                if (info) {
                    $('#current-provider-title').text(info.title);
                    
                    var featuresHtml = '<ul>';
                    info.features.forEach(function(feature) {
                        featuresHtml += '<li>' + feature + '</li>';
                    });
                    featuresHtml += '</ul>';
                    
                    $('#current-provider-info').html(featuresHtml);
                }
            }
            
            // Initial Setup
            updateModelsForProvider(currentProvider);
            updateApiKeyHelp(currentProvider);
            updateProviderComparison(currentProvider);
            
            console.log('ReTexify Admin JavaScript Setup abgeschlossen');
        });
        </script>
        <?php
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
                $full_content = $post->post_content;
                if ($this->content_analyzer) {
                    $full_content = $this->content_analyzer->clean_german_text($full_content);
                } else {
                    $full_content = wp_strip_all_tags($full_content);
                }
                
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
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $seo_type = sanitize_text_field($_POST['seo_type'] ?? '');
            
            if (!$post_id || !$seo_type) {
                wp_send_json_error('Ungültige Parameter (Post-ID: ' . $post_id . ', SEO-Type: ' . $seo_type . ')');
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post mit ID ' . $post_id . ' nicht gefunden');
                return;
            }
            
            error_log('ReTexify: Generating ' . $seo_type . ' for post ' . $post_id);
            
            // SEO-Generator verwenden
            if (!$this->ai_engine) {
                wp_send_json_error('AI-Engine nicht verfügbar - prüfe KI-Einstellungen');
                return;
            }
            
            // Content für Generierung vorbereiten
            $content = $post->post_content;
            if ($this->content_analyzer) {
                $content = $this->content_analyzer->clean_german_text($content);
            } else {
                $content = wp_strip_all_tags($content);
            }
            
            // Je nach SEO-Type verschiedene Prompts
            $generated_text = '';
            
            switch ($seo_type) {
                case 'meta_title':
                    $generated_text = $this->generate_meta_title_content($post, $content);
                    break;
                    
                case 'meta_description':
                    $generated_text = $this->generate_meta_description_content($post, $content);
                    break;
                    
                case 'focus_keyword':
                    $generated_text = $this->generate_focus_keyword_content($post, $content);
                    break;
                    
                default:
                    wp_send_json_error('Unbekannter SEO-Type: ' . $seo_type);
                    return;
            }
            
            if (empty($generated_text)) {
                wp_send_json_error('Generierung fehlgeschlagen - keine Antwort von KI');
                return;
            }
            
            wp_send_json_success(array(
                'generated_text' => $generated_text,
                'seo_type' => $seo_type,
                'post_id' => $post_id,
                'post_title' => $post->post_title
            ));
            
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_generate_single_seo: ' . $e->getMessage());
            wp_send_json_error('Generierungs-Fehler: ' . $e->getMessage());
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
            
            error_log('ReTexify: Generating complete SEO for post ' . $post_id);
            
            // Content vorbereiten
            $content = $post->post_content;
            if ($this->content_analyzer) {
                $content = $this->content_analyzer->clean_german_text($content);
            } else {
                $content = wp_strip_all_tags($content);
            }
            
            // Alle SEO-Elemente generieren
            $results = array();
            
            // Meta-Titel generieren
            $meta_title = $this->generate_meta_title_content($post, $content);
            if ($meta_title) {
                $results['meta_title'] = $meta_title;
            }
            
            // Meta-Beschreibung generieren
            $meta_description = $this->generate_meta_description_content($post, $content);
            if ($meta_description) {
                $results['meta_description'] = $meta_description;
            }
            
            // Focus-Keyword generieren
            $focus_keyword = $this->generate_focus_keyword_content($post, $content);
            if ($focus_keyword) {
                $results['focus_keyword'] = $focus_keyword;
            }
            
            if (empty($results)) {
                wp_send_json_error('Keine SEO-Texte generiert - prüfe KI-Einstellungen');
                return;
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            error_log('ReTexify Error in handle_generate_complete_seo: ' . $e->getMessage());
            wp_send_json_error('Komplett-Generierung fehlgeschlagen: ' . $e->getMessage());
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
            
            if ($this->content_analyzer && method_exists($this->content_analyzer, 'analyze_content')) {
                $analysis = $this->content_analyzer->analyze_content($post->post_content, $post->post_title);
                $seo_score = $this->content_analyzer->calculate_seo_score($analysis);
                
                $result = array_merge($analysis, array(
                    'seo_score' => $seo_score,
                    'has_images' => has_post_thumbnail($post_id)
                ));
            } else {
                // Fallback ohne Content-Analyzer
                $result = array(
                    'content' => wp_strip_all_tags($post->post_content),
                    'word_count' => str_word_count(wp_strip_all_tags($post->post_content)),
                    'char_count' => strlen(wp_strip_all_tags($post->post_content)),
                    'has_images' => has_post_thumbnail($post_id)
                );
            }
            
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
            $system_tests = array();
            
            // WordPress-Test
            $system_tests['wordpress'] = array(
                'name' => 'WordPress',
                'status' => 'success',
                'message' => 'Version ' . get_bloginfo('version'),
                'details' => 'WordPress läuft einwandfrei'
            );
            
            // PHP-Test
            $php_version = phpversion();
            $system_tests['php'] = array(
                'name' => 'PHP',
                'status' => version_compare($php_version, '7.4', '>=') ? 'success' : 'warning',
                'message' => 'Version ' . $php_version,
                'details' => version_compare($php_version, '7.4', '>=') ? 'PHP-Version kompatibel' : 'PHP-Version veraltet'
            );
            
            // cURL-Test
            $system_tests['curl'] = array(
                'name' => 'cURL',
                'status' => function_exists('curl_init') ? 'success' : 'error',
                'message' => function_exists('curl_init') ? 'Verfügbar' : 'Nicht verfügbar',
                'details' => function_exists('curl_init') ? 'cURL für API-Calls verfügbar' : 'cURL erforderlich für API-Calls'
            );
            
            // AI-Engine Test
            if ($this->ai_engine) {
                $system_tests['ai_engine'] = array(
                    'name' => 'AI-Engine',
                    'status' => 'success',
                    'message' => 'Verfügbar',
                    'details' => 'AI-Engine geladen und einsatzbereit'
                );
            } else {
                $system_tests['ai_engine'] = array(
                    'name' => 'AI-Engine',
                    'status' => 'warning',
                    'message' => 'Nicht verfügbar',
                    'details' => 'AI-Engine konnte nicht geladen werden'
                );
            }
            
            // Content-Analyzer Test
            if ($this->content_analyzer) {
                $system_tests['content_analyzer'] = array(
                    'name' => 'Content-Analyzer',
                    'status' => 'success',
                    'message' => 'Verfügbar',
                    'details' => 'Deutscher Content-Analyzer aktiv'
                );
            } else {
                $system_tests['content_analyzer'] = array(
                    'name' => 'Content-Analyzer',
                    'status' => 'warning',
                    'message' => 'Nicht verfügbar',
                    'details' => 'Content-Analyzer konnte nicht geladen werden'
                );
            }
            
            // HTML für System-Status generieren
            $html = $this->generate_system_status_html($system_tests);
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('System-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function ajax_test_research_apis() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $api_tests = array();
            
            // Google Suggest API Test
            $google_test = $this->test_google_suggest_api();
            $api_tests['google'] = $google_test;
            
            // Wikipedia API Test
            $wikipedia_test = $this->test_wikipedia_api();
            $api_tests['wikipedia'] = $wikipedia_test;
            
            // OpenStreetMap API Test
            $osm_test = $this->test_openstreetmap_api();
            $api_tests['openstreetmap'] = $osm_test;
            
            // HTML für Research-Status generieren
            $html = $this->generate_research_status_html($api_tests);
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('Research-API-Test fehlgeschlagen: ' . $e->getMessage());
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
            
            // API-Key separat speichern
            $api_keys = $this->get_all_api_keys();
            $api_keys[$provider] = $api_key;
            update_option('retexify_api_keys', $api_keys);
            
            // Settings ohne API-Key speichern
            $raw_settings = array(
                'api_provider' => $provider,
                'api_key' => $api_key, // Temporär für Validierung
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
            
            // API-Key aus Settings entfernen (wird separat gespeichert)
            unset($settings['api_key']);
            
            update_option('retexify_ai_settings', $settings);
            
            wp_send_json_success('KI-Einstellungen erfolgreich gespeichert! ' . count($settings['target_cantons']) . ' Kantone ausgewählt.');
            
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
        if (!$this->ai_engine) {
            return '';
        }
        
        $settings = get_option('retexify_ai_settings', array());
        $business_context = $settings['business_context'] ?? '';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        $target_cantons = $settings['target_cantons'] ?? array();
        
        $prompt = "Generiere einen SEO-optimierten Meta-Titel (max. 58 Zeichen) für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1000) . "\n\n";
        
        if (!empty($business_context)) {
            $prompt .= "Business-Kontext: " . $business_context . "\n";
        }
        
        if (!empty($target_cantons)) {
            $prompt .= "Relevante Schweizer Kantone: " . implode(', ', $target_cantons) . "\n";
        }
        
        $prompt .= "\nAnforderungen:\n";
        $prompt .= "- Maximal 58 Zeichen\n";
        $prompt .= "- Prägnant und ansprechend\n";
        $prompt .= "- Enthält wichtigste Keywords\n";
        $prompt .= "- Für deutsche/Schweizer Zielgruppe optimiert\n";
        $prompt .= "- Markenstimme: " . $brand_voice . "\n";
        $prompt .= "- Keine Anführungszeichen verwenden\n\n";
        $prompt .= "Gib nur den Meta-Titel zurück, keine Erklärungen:";
        
        try {
            $response = $this->ai_engine->generate_text($prompt);
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
        if (!$this->ai_engine) {
            return '';
        }
        
        $settings = get_option('retexify_ai_settings', array());
        $business_context = $settings['business_context'] ?? '';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        $target_audience = $settings['target_audience'] ?? '';
        
        $prompt = "Generiere eine SEO-optimierte Meta-Beschreibung (140-155 Zeichen) für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1500) . "\n\n";
        
        if (!empty($business_context)) {
            $prompt .= "Business-Kontext: " . $business_context . "\n";
        }
        
        if (!empty($target_audience)) {
            $prompt .= "Zielgruppe: " . $target_audience . "\n";
        }
        
        $prompt .= "\nAnforderungen:\n";
        $prompt .= "- 140-155 Zeichen lang\n";
        $prompt .= "- Motiviert zum Klicken\n";
        $prompt .= "- Enthält wichtigste Keywords\n";
        $prompt .= "- Für deutsche/Schweizer Zielgruppe optimiert\n";
        $prompt .= "- Markenstimme: " . $brand_voice . "\n";
        $prompt .= "- Keine Anführungszeichen verwenden\n\n";
        $prompt .= "Gib nur die Meta-Beschreibung zurück, keine Erklärungen:";
        
        try {
            $response = $this->ai_engine->generate_text($prompt);
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
        if (!$this->ai_engine) {
            return '';
        }
        
        $settings = get_option('retexify_ai_settings', array());
        $business_context = $settings['business_context'] ?? '';
        
        $prompt = "Identifiziere das beste Focus-Keyword für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1000) . "\n\n";
        
        if (!empty($business_context)) {
            $prompt .= "Business-Kontext: " . $business_context . "\n";
        }
        
        $prompt .= "\nAnforderungen:\n";
        $prompt .= "- 1-3 Wörter lang\n";
        $prompt .= "- Hohe Suchrelevanz\n";
        $prompt .= "- Auf Deutsch\n";
        $prompt .= "- Ohne Sonderzeichen\n";
        $prompt .= "- Für Schweizer/Deutsche Suchintention optimiert\n\n";
        $prompt .= "Gib nur das Focus-Keyword zurück, keine Erklärungen:";
        
        try {
            $response = $this->ai_engine->generate_text($prompt);
            return trim(strtolower(str_replace('"', '', $response)));
        } catch (Exception $e) {
            error_log('ReTexify Focus-Keyword Generation Error: ' . $e->getMessage());
            return '';
        }
    }
    
    // ========================================================================
    // 🛠️ SEO-DATEN HELPER - VOLLSTÄNDIG (alle SEO-Plugins)
    // ========================================================================
    
    /**
     * Meta-Titel von SEO-Plugins abrufen
     */
    private function get_meta_title($post_id) {
        // Yoast SEO
        $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($title)) return $title;
        
        // Rank Math
        $title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($title)) return $title;
        
        // All in One SEO
        $title = get_post_meta($post_id, '_aioseop_title', true);
        if (!empty($title)) return $title;
        
        // SEOPress
        $title = get_post_meta($post_id, '_seopress_titles_title', true);
        if (!empty($title)) return $title;
        
        // The SEO Framework
        $title = get_post_meta($post_id, '_genesis_title', true);
        if (!empty($title)) return $title;
        
        return '';
    }
    
    /**
     * Meta-Beschreibung von SEO-Plugins abrufen
     */
    private function get_meta_description($post_id) {
        // Yoast SEO
        $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($desc)) return $desc;
        
        // Rank Math
        $desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($desc)) return $desc;
        
        // All in One SEO
        $desc = get_post_meta($post_id, '_aioseop_description', true);
        if (!empty($desc)) return $desc;
        
        // SEOPress
        $desc = get_post_meta($post_id, '_seopress_titles_desc', true);
        if (!empty($desc)) return $desc;
        
        // The SEO Framework
        $desc = get_post_meta($post_id, '_genesis_description', true);
        if (!empty($desc)) return $desc;
        
        return '';
    }
    
    /**
     * Focus-Keyword von SEO-Plugins abrufen
     */
    private function get_focus_keyword($post_id) {
        // Yoast SEO
        $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($keyword)) return $keyword;
        
        // Rank Math
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($keyword)) return $keyword;
        
        // All in One SEO - Primary Focus Keyword
        $keyword = get_post_meta($post_id, '_aioseo_focus_keyphrase', true);
        if (!empty($keyword)) return $keyword;
        
        return '';
    }
    
    /**
     * Meta-Titel in SEO-Plugins speichern
     */
    private function save_meta_title($post_id, $meta_title) {
        $saved = false;
        
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
            $saved = true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_title', $meta_title);
            $saved = true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_title', $meta_title);
            $saved = true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_title', $meta_title);
            $saved = true;
        }
        
        // The SEO Framework
        if (is_plugin_active('autodescription/autodescription.php')) {
            update_post_meta($post_id, '_genesis_title', $meta_title);
            $saved = true;
        }
        
        return $saved;
    }
    
    /**
     * Meta-Beschreibung in SEO-Plugins speichern
     */
    private function save_meta_description($post_id, $meta_description) {
        $saved = false;
        
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            $saved = true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_description', $meta_description);
            $saved = true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_description', $meta_description);
            $saved = true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_desc', $meta_description);
            $saved = true;
        }
        
        // The SEO Framework
        if (is_plugin_active('autodescription/autodescription.php')) {
            update_post_meta($post_id, '_genesis_description', $meta_description);
            $saved = true;
        }
        
        return $saved;
    }
    
    /**
     * Focus-Keyword in SEO-Plugins speichern
     */
    private function save_focus_keyword($post_id, $focus_keyword) {
        $saved = false;
        
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
            $saved = true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
            $saved = true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseo_focus_keyphrase', $focus_keyword);
            $saved = true;
        }
        
        return $saved;
    }
    
    // ========================================================================
    // 🛠️ HTML-GENERATOR-METHODEN - VOLLSTÄNDIG
    // ========================================================================
    
    /**
     * HTML für System-Status generieren
     */
    private function generate_system_status_html($tests) {
        $html = '<div class="retexify-system-status-results">';
        
        foreach ($tests as $key => $test) {
            $status_class = 'status-' . $test['status'];
            $icon = $test['status'] === 'success' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
            
            $html .= '<div class="retexify-status-item ' . $status_class . '">';
            $html .= '<div class="status-icon">' . $icon . '</div>';
            $html .= '<div class="status-content">';
            $html .= '<h4>' . $test['name'] . '</h4>';
            $html .= '<p class="status-message">' . $test['message'] . '</p>';
            $html .= '<small class="status-details">' . $test['details'] . '</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * HTML für Research-Status generieren
     */
    private function generate_research_status_html($tests) {
        $html = '<div class="retexify-research-status-results">';
        
        foreach ($tests as $key => $test) {
            $status_class = 'status-' . $test['status'];
            $icon = $test['status'] === 'success' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
            
            $html .= '<div class="retexify-status-item ' . $status_class . '">';
            $html .= '<div class="status-icon">' . $icon . '</div>';
            $html .= '<div class="status-content">';
            $html .= '<h4>' . $test['name'] . '</h4>';
            $html .= '<p class="status-message">' . $test['message'] . '</p>';
            $html .= '<small class="status-details">' . $test['details'] . '</small>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    // ========================================================================
    // 🧪 API-TEST-METHODEN - VOLLSTÄNDIG
    // ========================================================================
    
    /**
     * Google Suggest API testen
     */
    private function test_google_suggest_api() {
        try {
            $test_url = 'http://suggestqueries.google.com/complete/search?client=firefox&q=seo';
            $response = wp_remote_get($test_url, array('timeout' => 5));
            
            if (is_wp_error($response)) {
                return array(
                    'name' => 'Google Suggest',
                    'status' => 'error',
                    'message' => 'Nicht erreichbar',
                    'details' => $response->get_error_message()
                );
            }
            
            return array(
                'name' => 'Google Suggest',
                'status' => 'success',
                'message' => 'Verfügbar',
                'details' => 'API erreichbar für Keyword-Vorschläge'
            );
            
        } catch (Exception $e) {
            return array(
                'name' => 'Google Suggest',
                'status' => 'error',
                'message' => 'Fehler',
                'details' => $e->getMessage()
            );
        }
    }
    
    /**
     * Wikipedia API testen
     */
    private function test_wikipedia_api() {
        try {
            $test_url = 'https://de.wikipedia.org/api/rest_v1/page/summary/SEO';
            $response = wp_remote_get($test_url, array('timeout' => 5));
            
            if (is_wp_error($response)) {
                return array(
                    'name' => 'Wikipedia API',
                    'status' => 'warning',
                    'message' => 'Nicht erreichbar',
                    'details' => 'Normale Funktion nicht beeinträchtigt'
                );
            }
            
            return array(
                'name' => 'Wikipedia API',
                'status' => 'success',
                'message' => 'Verfügbar',
                'details' => 'API erreichbar für Content-Recherche'
            );
            
        } catch (Exception $e) {
            return array(
                'name' => 'Wikipedia API',
                'status' => 'warning',
                'message' => 'Temporär offline',
                'details' => 'Optional - Hauptfunktionen weiterhin verfügbar'
            );
        }
    }
    
    /**
     * OpenStreetMap API testen
     */
    private function test_openstreetmap_api() {
        try {
            $test_url = 'https://nominatim.openstreetmap.org/search?q=Zürich&format=json&limit=1';
            $response = wp_remote_get($test_url, array('timeout' => 5));
            
            if (is_wp_error($response)) {
                return array(
                    'name' => 'OpenStreetMap',
                    'status' => 'warning',
                    'message' => 'Nicht erreichbar',
                    'details' => 'Local SEO temporär eingeschränkt'
                );
            }
            
            return array(
                'name' => 'OpenStreetMap',
                'status' => 'success',
                'message' => 'Verfügbar',
                'details' => 'API erreichbar für Local SEO'
            );
            
        } catch (Exception $e) {
            return array(
                'name' => 'OpenStreetMap',
                'status' => 'warning',
                'message' => 'Temporär offline',
                'details' => 'Optional für Local SEO'
            );
        }
    }
    
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
            
            $result = $this->export_import_manager->export_to_csv($post_types, $status_types, $content_types);
            
            if ($result['success'] && !empty($result['filename'])) {
                $download_nonce = wp_create_nonce('retexify_download_nonce');
                $download_url = admin_url('admin-ajax.php?action=retexify_download_export_file&filename=' . urlencode(basename($result['filename'])) . '&nonce=' . $download_nonce);
                
                wp_send_json_success(array(
                    'message' => 'Export erfolgreich. Download startet...',
                    'download_url' => $download_url,
                    'filename' => basename($result['filename']),
                    'file_size' => $result['file_size'],
                    'row_count' => $result['row_count']
                ));
            } else {
                wp_send_json_error($result['message'] ?? 'Unbekannter Export-Fehler.');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Export-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_download_export_file() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'retexify_download_nonce') || !current_user_can('manage_options')) {
            wp_die('Sicherheitsfehler!');
        }
    
        $filename = sanitize_file_name(basename($_GET['filename'] ?? ''));
        if (empty($filename)) {
            wp_die('Ungültiger Dateiname.');
        }
    
        $upload_dir = wp_upload_dir();
        $imports_dir = $upload_dir['basedir'] . '/retexify-ai/';
        $filepath = $imports_dir . $filename;
    
        if (!file_exists($filepath) || strpos(realpath($filepath), realpath($imports_dir)) !== 0) {
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
        
        unlink($filepath);
        
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
    
    public function handle_bulk_optimize() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_ids = array_map('intval', $_POST['post_ids'] ?? array());
            $seo_types = array_map('sanitize_text_field', $_POST['seo_types'] ?? array());
            
            if (empty($post_ids) || empty($seo_types)) {
                wp_send_json_error('Ungültige Parameter für Bulk-Optimierung');
                return;
            }
            
            $results = array();
            $success_count = 0;
            
            foreach ($post_ids as $post_id) {
                $post_results = array();
                
                foreach ($seo_types as $seo_type) {
                    // Einzelne SEO-Generierung für jeden Post und Type
                    $result = $this->generate_single_seo_for_post($post_id, $seo_type);
                    if ($result['success']) {
                        $post_results[$seo_type] = $result['data'];
                        $success_count++;
                    }
                }
                
                $results[$post_id] = $post_results;
            }
            
            wp_send_json_success(array(
                'results' => $results,
                'success_count' => $success_count,
                'total_operations' => count($post_ids) * count($seo_types)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk-Optimierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_schedule_optimization() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'page');
            $schedule_time = sanitize_text_field($_POST['schedule_time'] ?? 'now');
            
            // Queue für spätere Verarbeitung erstellen
            $queue_data = array(
                'post_type' => $post_type,
                'schedule_time' => $schedule_time,
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            );
            
            // In WordPress-Option speichern (für einfache Implementierung)
            $existing_queue = get_option('retexify_optimization_queue', array());
            $existing_queue[] = $queue_data;
            update_option('retexify_optimization_queue', $existing_queue);
            
            wp_send_json_success('Optimierung erfolgreich geplant');
            
        } catch (Exception $e) {
            wp_send_json_error('Planung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_get_optimization_queue() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $queue = get_option('retexify_optimization_queue', array());
            wp_send_json_success($queue);
            
        } catch (Exception $e) {
            wp_send_json_error('Queue-Abruf fehlgeschlagen: ' . $e->getMessage());
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
            
            // Einfache Keyword-Research mit Google Suggest
            $suggestions = $this->get_keyword_suggestions($keyword, $language);
            
            wp_send_json_success(array(
                'keyword' => $keyword,
                'suggestions' => $suggestions,
                'language' => $language
            ));
            
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
            
            // Einfache Competition-Analyse (Platzhalter)
            $competition_data = array(
                'keyword' => $keyword,
                'difficulty' => 'medium',
                'search_volume' => 'moderate',
                'competition_level' => 0.6,
                'suggestions' => array(
                    'Verwende Long-tail Keywords',
                    'Fokussiere auf lokale Suche',
                    'Erstelle qualitativ hochwertigen Content'
                )
            );
            
            wp_send_json_success($competition_data);
            
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
            
            $suggestions = $this->get_topic_suggestions($topic);
            
            wp_send_json_success($suggestions);
            
        } catch (Exception $e) {
            wp_send_json_error('Suggestions-Abruf fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_research_keywords() {
        // Alias für handle_keyword_research
        $this->handle_keyword_research();
    }
    
    // ========================================================================
    // 🛠️ LEGACY-HANDLER FÜR KOMPATIBILITÄT
    // ========================================================================
    
    public function handle_generate_meta_title() {
        $_POST['seo_type'] = 'meta_title';
        $this->handle_generate_single_seo();
    }
    
    public function handle_generate_meta_description() {
        $_POST['seo_type'] = 'meta_description';
        $this->handle_generate_single_seo();
    }
    
    public function handle_generate_keywords() {
        $_POST['seo_type'] = 'focus_keyword';
        $this->handle_generate_single_seo();
    }
    
    // ========================================================================
    // 🛠️ UTILITY-HELPER-METHODEN
    // ========================================================================
    
    /**
     * Einfacher API-Test (für verschiedene APIs)
     */
    private function test_api($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 5,
            'headers' => array(
                'User-Agent' => 'ReTexify-AI-Plugin/' . RETEXIFY_VERSION
            )
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Keyword-Suggestions abrufen
     */
    private function get_keyword_suggestions($keyword, $language = 'de') {
        try {
            $url = 'http://suggestqueries.google.com/complete/search?client=firefox&q=' . urlencode($keyword) . '&hl=' . $language;
            $response = wp_remote_get($url, array('timeout' => 5));
            
            if (is_wp_error($response)) {
                return array();
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data[1]) && is_array($data[1])) {
                return array_slice($data[1], 0, 10); // Maximal 10 Vorschläge
            }
            
            return array();
            
        } catch (Exception $e) {
            error_log('ReTexify Keyword Suggestions Error: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Topic-Suggestions abrufen
     */
    private function get_topic_suggestions($topic) {
        // Einfache Topic-Suggestions basierend auf häufigen Mustern
        $base_suggestions = array(
            $topic . ' Anleitung',
            $topic . ' Tipps',
            $topic . ' für Anfänger',
            $topic . ' Schweiz',
            'Beste ' . $topic,
            $topic . ' kaufen',
            $topic . ' Test',
            $topic . ' Vergleich',
            $topic . ' 2024',
            $topic . ' kostenlos'
        );
        
        return array(
            'topic' => $topic,
            'suggestions' => $base_suggestions,
            'count' => count($base_suggestions)
        );
    }
    
    /**
     * Einzelne SEO-Generierung für einen Post (Helper für Bulk-Operationen)
     */
    private function generate_single_seo_for_post($post_id, $seo_type) {
        try {
            $post = get_post($post_id);
            if (!$post) {
                return array('success' => false, 'error' => 'Post nicht gefunden');
            }
            
            $content = $post->post_content;
            if ($this->content_analyzer) {
                $content = $this->content_analyzer->clean_german_text($content);
            } else {
                $content = wp_strip_all_tags($content);
            }
            
            switch ($seo_type) {
                case 'meta_title':
                    $generated_text = $this->generate_meta_title_content($post, $content);
                    break;
                    
                case 'meta_description':
                    $generated_text = $this->generate_meta_description_content($post, $content);
                    break;
                    
                case 'focus_keyword':
                    $generated_text = $this->generate_focus_keyword_content($post, $content);
                    break;
                    
                default:
                    return array('success' => false, 'error' => 'Unbekannter SEO-Type');
            }
            
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
     * Schweizer Kantone abrufen
     */
    private function get_swiss_cantons() {
        return array(
            'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
            'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
            'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
            'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
            'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
            'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
            'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
        );
    }
    
    // ========================================================================
    // 🔧 SYSTEM-DIAGNOSTICS HANDLER
    // ========================================================================
    
    public function ajax_get_system_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $system_info = array(
                'plugin' => array(
                    'version' => RETEXIFY_VERSION,
                    'path' => RETEXIFY_PLUGIN_PATH,
                    'url' => RETEXIFY_PLUGIN_URL,
                    'active_since' => get_option('retexify_activation_time')
                ),
                'wordpress' => array(
                    'version' => get_bloginfo('version'),
                    'multisite' => is_multisite(),
                    'language' => get_locale(),
                    'theme' => get_template()
                ),
                'server' => array(
                    'php_version' => phpversion(),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                    'upload_max_filesize' => ini_get('upload_max_filesize')
                ),
                'capabilities' => array(
                    'curl' => function_exists('curl_init'),
                    'json' => function_exists('json_encode'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl')
                )
            );
            
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
            $requirements = array(
                'php_version' => array(
                    'required' => '7.4',
                    'current' => phpversion(),
                    'status' => version_compare(phpversion(), '7.4', '>=') ? 'ok' : 'error'
                ),
                'wordpress_version' => array(
                    'required' => '5.0',
                    'current' => get_bloginfo('version'),
                    'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'ok' : 'error'
                ),
                'curl_extension' => array(
                    'required' => true,
                    'current' => function_exists('curl_init'),
                    'status' => function_exists('curl_init') ? 'ok' : 'error'
                ),
                'json_extension' => array(
                    'required' => true,
                    'current' => function_exists('json_encode'),
                    'status' => function_exists('json_encode') ? 'ok' : 'error'
                )
            );
            
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
            $report = array(
                'timestamp' => current_time('mysql'),
                'plugin_version' => RETEXIFY_VERSION,
                'system_info' => $this->get_diagnostic_system_info(),
                'settings' => $this->get_diagnostic_settings(),
                'recent_logs' => $this->get_recent_error_logs()
            );
            
            wp_send_json_success($report);
            
        } catch (Exception $e) {
            wp_send_json_error('Diagnostic-Report fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    private function get_diagnostic_system_info() {
        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'memory_limit' => ini_get('memory_limit'),
            'active_plugins' => get_option('active_plugins', array()),
            'theme' => get_template()
        );
    }
    
    private function get_diagnostic_settings() {
        $settings = get_option('retexify_ai_settings', array());
        $api_keys = get_option('retexify_api_keys', array());
        
        // API-Keys maskieren für Sicherheit
        $masked_keys = array();
        foreach ($api_keys as $provider => $key) {
            if (!empty($key)) {
                $masked_keys[$provider] = substr($key, 0, 8) . '***' . substr($key, -4);
            } else {
                $masked_keys[$provider] = 'Nicht gesetzt';
            }
        }
        
        return array(
            'provider' => $settings['api_provider'] ?? 'nicht gesetzt',
            'model' => $settings['model'] ?? 'nicht gesetzt',
            'api_keys' => $masked_keys,
            'cantons_count' => count($settings['target_cantons'] ?? array())
        );
    }
    
    private function get_recent_error_logs() {
        // Vereinfachte Implementierung - in der Praxis würde man
        // WordPress-Logs oder eigene Log-Dateien auslesen
        return array(
            'info' => 'Error-Logs würden hier angezeigt',
            'recent_count' => 0
        );
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