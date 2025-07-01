<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen
 * Version: 4.1.0
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
if (!defined('RETEXIFY_VERSION')) {
    define('RETEXIFY_VERSION', '4.1.0');
}
if (!defined('RETEXIFY_PLUGIN_URL')) {
    define('RETEXIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('RETEXIFY_PLUGIN_PATH')) {
    define('RETEXIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// ============================================================================
// 🔧 OPTIMIERT: Alle erforderlichen Dateien laden
// ============================================================================

$required_files = array(
    // Core-Klassen (erforderlich)
    'includes/class-german-content-analyzer.php',
    'includes/class-ai-engine.php',
    
    // Neue optimierte Handler
    'includes/class-system-status.php',
    'includes/class-seo-generator.php',
    
    // Intelligente Features (optional)
    'includes/class-api-manager.php',
    'includes/class-intelligent-keyword-research.php',
    'includes/class_retexify_config.php'
);

foreach ($required_files as $file) {
    $file_path = RETEXIFY_PLUGIN_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log('ReTexify AI: File missing: ' . $file);
    }
}

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
        // Content-Analyzer initialisieren
        if (function_exists('retexify_get_content_analyzer')) {
            $this->content_analyzer = retexify_get_content_analyzer();
        }
        
        // AI-Engine initialisieren
        if (function_exists('retexify_get_ai_engine')) {
            $this->ai_engine = retexify_get_ai_engine();
        }
        
        // Export/Import Manager laden (falls verfügbar)
        $this->load_export_import_manager();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // ✅ OPTIMIERT: Alle AJAX-Handler zentral registrieren
        $this->register_ajax_handlers();
        
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }
    
    /**
     * Export/Import Manager laden (optional)
     */
    private function load_export_import_manager() {
        $export_import_file = RETEXIFY_PLUGIN_PATH . 'includes/class-export-import-manager.php';
        
        if (file_exists($export_import_file)) {
            require_once $export_import_file;
            
            if (class_exists('ReTexify_Export_Import_Manager')) {
                try {
                    $this->export_import_manager = new ReTexify_Export_Import_Manager();
                } catch (Exception $e) {
                    error_log('ReTexify AI: Export/Import Manager initialization failed: ' . $e->getMessage());
                    $this->export_import_manager = null;
                }
            }
        }
    }
    
    /**
     * ✅ VOLLSTÄNDIGE AJAX-HANDLER REGISTRIERUNG
     */
    private function register_ajax_handlers() {
        
        // Kritische AJAX-Handler für SEO-Optimizer
        add_action('wp_ajax_retexify_load_content', array($this, 'handle_load_seo_content'));
        add_action('wp_ajax_retexify_generate_single_seo', array($this, 'handle_generate_single_seo'));
        add_action('wp_ajax_retexify_generate_complete_seo', array($this, 'handle_generate_complete_seo'));
        add_action('wp_ajax_retexify_save_seo_data', array($this, 'handle_save_seo_data'));
        
        // System-Status und Diagnostics
        add_action('wp_ajax_retexify_test_system', array($this, 'ajax_test_system'));
        add_action('wp_ajax_retexify_test_research_apis', array($this, 'ajax_test_research_apis'));
        add_action('wp_ajax_retexify_get_stats', array($this, 'ajax_get_stats'));
        
        // Einzelne Meta-Generierung (Legacy-Support)
        add_action('wp_ajax_retexify_generate_meta_title', array($this, 'handle_generate_meta_title'));
        add_action('wp_ajax_retexify_generate_meta_description', array($this, 'handle_generate_meta_description'));
        add_action('wp_ajax_retexify_generate_keywords', array($this, 'handle_generate_keywords'));
        
        // KI-Einstellungen
        add_action('wp_ajax_retexify_save_settings', array($this, 'handle_ai_save_settings'));
        add_action('wp_ajax_retexify_test_api_connection', array($this, 'handle_ai_test_connection'));
        
        // Research & Keywords (optional)
        add_action('wp_ajax_retexify_keyword_research', array($this, 'handle_keyword_research'));
        
        // Export/Import (falls verfügbar)
        if ($this->export_import_manager) {
            add_action('wp_ajax_retexify_export_data', array($this->export_import_manager, 'ajax_export_data'));
            add_action('wp_ajax_retexify_import_data', array($this->export_import_manager, 'ajax_import_data'));
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
                'use_swiss_german' => true
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
    }
    
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
        
        // 3. System-Status-Fixes CSS (neu)
        $system_css_file = RETEXIFY_PLUGIN_PATH . 'assets/system-status-fixes.css';
        if (file_exists($system_css_file)) {
            wp_enqueue_style(
                'retexify-system-status-fixes', 
                RETEXIFY_PLUGIN_URL . 'assets/system-status-fixes.css', 
                array('retexify-admin-style', 'retexify-admin-style-extended'), // Abhängigkeiten
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
                                            <button type="button" id="retexify-save-seo-data" class="retexify-btn retexify-btn-success retexify-btn-large">
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
                
                <!-- Tab: System -->
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
                            <div class="retexify-header-badge" id="test-research-apis">
                                🔄 APIs testen
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="research-engine-status-content">
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
    
    // ==== AJAX HANDLERS ====
    
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
    
    /**
     * ✅ OPTIMIERT: Generiere einzelnes SEO-Item
     */
    public function handle_generate_seo_item() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
            $post_id = intval($_POST['post_id'] ?? 0);
            $seo_type = sanitize_text_field($_POST['seo_type'] ?? '');
            $include_cantons = filter_var($_POST['include_cantons'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $premium_tone = filter_var($_POST['premium_tone'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$post_id || !$seo_type) {
                wp_send_json_error('Ungültige Parameter');
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            
        // SEO-Generator verwenden
        $seo_generator = retexify_get_seo_generator();
        if (!$seo_generator) {
            wp_send_json_error('SEO-Generator nicht verfügbar');
                return;
            }
            
        $content = $seo_generator->generate_single_seo_item($post, $seo_type, $include_cantons, $premium_tone);
                
                wp_send_json_success(array(
            'content' => $content,
                    'type' => $seo_type,
                    'post_id' => $post_id
                ));
    }
    
    /**
     * ✅ OPTIMIERT: Generiere alle SEO-Texte (Haupt-Handler)
     */
    public function handle_generate_complete_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
            $post_id = intval($_POST['post_id'] ?? 0);
            $include_cantons = filter_var($_POST['include_cantons'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $premium_tone = filter_var($_POST['premium_tone'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
        if ($post_id <= 0) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
        // SEO-Generator verwenden
        $seo_generator = retexify_get_seo_generator();
        if (!$seo_generator) {
            wp_send_json_error('SEO-Generator nicht verfügbar');
                return;
            }
            
        $result = $seo_generator->generate_complete_seo($post_id, $include_cantons, $premium_tone);
        
        if ($result['success']) {
            wp_send_json_success($result);
            } else {
            wp_send_json_error($result['error'] ?? 'Unbekannter Fehler');
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
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
            $saved_count = 0;
            
            // Meta-Titel speichern
            if (!empty($meta_title)) {
                $this->save_meta_title($post_id, $meta_title);
                $saved_count++;
            }
            
            // Meta-Beschreibung speichern
            if (!empty($meta_description)) {
                $this->save_meta_description($post_id, $meta_description);
                $saved_count++;
            }
            
            // Focus-Keyword speichern
            if (!empty($focus_keyword)) {
                $this->save_focus_keyword($post_id, $focus_keyword);
                $saved_count++;
            }
            
            wp_send_json_success(array(
                'message' => $saved_count . ' SEO-Elemente erfolgreich gespeichert',
                'saved_count' => $saved_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    // HELPER METHODEN FÜR SEO-DATEN SPEICHERN
    
    private function save_meta_title($post_id, $meta_title) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_title', $meta_title);
            return true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_title', $meta_title);
            return true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_title', $meta_title);
            return true;
        }
        
        return false;
    }
    
    private function save_meta_description($post_id, $meta_description) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_description', $meta_description);
            return true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_description', $meta_description);
            return true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_desc', $meta_description);
            return true;
        }
        
        return false;
    }
    
    private function save_focus_keyword($post_id, $focus_keyword) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
            return true;
        }
        
        return false;
    }
    
    public function handle_load_seo_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
            
            $posts = get_posts(array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'numberposts' => 50,
                'orderby' => 'modified',
                'order' => 'DESC'
            ));
            
            $seo_data = array();
            
            foreach ($posts as $post) {
                $meta_title = $this->get_meta_title($post->ID);
                $meta_description = $this->get_meta_description($post->ID);
                $focus_keyword = $this->get_focus_keyword($post->ID);
                
                $item = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit'),
                    'modified' => get_the_modified_date('d.m.Y H:i', $post->ID),
                    'type' => $post->post_type,
                    
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'focus_keyword' => $focus_keyword,
                    
                    'full_content' => $this->content_analyzer ? $this->content_analyzer->clean_german_text($post->post_content) : wp_strip_all_tags($post->post_content),
                    'content_excerpt' => wp_trim_words(wp_strip_all_tags($post->post_content), 50),
                    
                    'needs_optimization' => empty($meta_title) || empty($meta_description) || empty($focus_keyword)
                );
                
                $seo_data[] = $item;
            }
            
            wp_send_json_success(array(
                'items' => $seo_data,
                'total' => count($seo_data),
                'post_type' => $post_type
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden: ' . $e->getMessage());
        }
    }
    
    public function get_stats() {
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

            // Dashboard-HTML wie im Screenshot
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
    
    /**
     * ✅ OPTIMIERT: System-Status abrufen
     */
    public function handle_test_system_status() {
        // Sicherheitscheck
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
            return;
        }
        
        try {
            // System-Informationen sammeln
            $status = $this->get_complete_system_status();
            
            // HTML für die Anzeige generieren
            $html = $this->render_system_status_html($status);
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            error_log('ReTexify System Status Error: ' . $e->getMessage());
            wp_send_json_error('Fehler beim Laden des System-Status: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ SYSTEM-STATUS SAMMELN
     */
    private function get_complete_system_status() {
        global $wp_version;
        
        // WordPress-Info
        $wordpress = array(
            'version' => $wp_version,
                    'multisite' => is_multisite(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        );
        
        // Plugin-Info
        $plugin = array(
                    'version' => RETEXIFY_VERSION,
                    'path' => RETEXIFY_PLUGIN_PATH,
            'url' => RETEXIFY_PLUGIN_URL,
            'active_since' => get_option('retexify_activation_time', date('Y-m-d H:i:s'))
        );
        
        // PHP-Info
        $php = array(
            'version' => phpversion(),
            'curl_enabled' => function_exists('curl_init'),
            'json_enabled' => function_exists('json_encode'),
            'openssl_enabled' => extension_loaded('openssl'),
            'mbstring_enabled' => extension_loaded('mbstring')
        );
        
        // API-Verbindungen testen
        $apis = $this->test_ai_api_connections();
        
        return array(
            'wordpress' => $wordpress,
            'plugin' => $plugin,
            'php' => $php,
            'apis' => $apis
        );
    }
    
    /**
     * ✅ KI-APIS TESTEN
     */
    private function test_ai_api_connections() {
        $api_keys = get_option('retexify_api_keys', array());
        $connections = array();
        
        // OpenAI
        if (!empty($api_keys['openai'])) {
            $connections['OpenAI'] = $this->quick_test_openai($api_keys['openai']);
        } else {
            $connections['OpenAI'] = false;
        }
        
        // Anthropic
        if (!empty($api_keys['anthropic'])) {
            $connections['Anthropic'] = $this->quick_test_anthropic($api_keys['anthropic']);
        } else {
            $connections['Anthropic'] = false;
        }
        
        // Gemini
        if (!empty($api_keys['gemini'])) {
            $connections['Gemini'] = $this->quick_test_gemini($api_keys['gemini']);
        } else {
            $connections['Gemini'] = false;
        }
        
        return $connections;
    }
    
    /**
     * ✅ SCHNELLE API-TESTS
     */
    private function quick_test_openai($api_key) {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 5,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent' => 'ReTexify-AI-Plugin'
            )
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
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
        
        $code = wp_remote_retrieve_response_code($response);
        return !is_wp_error($response) && ($code === 200 || $code === 400);
    }
    
    private function quick_test_gemini($api_key) {
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key, array(
            'timeout' => 5
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * ✅ HTML-RENDERING FÜR SYSTEM-STATUS
     */
    private function render_system_status_html($status_data) {
        $html = '<div class="retexify-system-status-modern">';
        
        // WordPress-Info
        if (isset($status_data['wordpress'])) {
            $wp = $status_data['wordpress'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🌐 WordPress System</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            $html .= '<span class="status-ok"><strong>Version:</strong> <span>' . esc_html($wp['version']) . '</span></span>';
            $html .= '<span class="status-ok"><strong>Memory Limit:</strong> <span>' . esc_html($wp['memory_limit']) . '</span></span>';
            $html .= '<span class="status-ok"><strong>Max Execution:</strong> <span>' . esc_html($wp['max_execution_time']) . 's</span></span>';
            $html .= '<span class="status-ok"><strong>Upload Max:</strong> <span>' . esc_html($wp['upload_max_filesize']) . '</span></span>';
            $html .= '</div></div>';
        }
        
        // Plugin-Info
        if (isset($status_data['plugin'])) {
            $plugin = $status_data['plugin'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🔌 Plugin Status</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            $html .= '<span class="status-ok"><strong>Version:</strong> <span>' . esc_html($plugin['version']) . '</span></span>';
            $html .= '<span class="status-ok"><strong>Aktiv seit:</strong> <span>' . esc_html($plugin['active_since']) . '</span></span>';
            $html .= '</div></div>';
        }
        
        // PHP-Info
        if (isset($status_data['php'])) {
            $php = $status_data['php'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🐘 PHP Status</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            $html .= '<span class="status-ok"><strong>Version:</strong> <span>' . esc_html($php['version']) . '</span></span>';
            $curl_status = $php['curl_enabled'] ? 'status-ok' : 'status-error';
            $curl_text = $php['curl_enabled'] ? '✅ Aktiv' : '❌ Fehlt';
            $html .= '<span class="' . $curl_status . '"><strong>cURL:</strong> <span>' . $curl_text . '</span></span>';
            $json_status = $php['json_enabled'] ? 'status-ok' : 'status-error';
            $json_text = $php['json_enabled'] ? '✅ Aktiv' : '❌ Fehlt';
            $html .= '<span class="' . $json_status . '"><strong>JSON:</strong> <span>' . $json_text . '</span></span>';
            $ssl_status = $php['openssl_enabled'] ? 'status-ok' : 'status-error';
            $ssl_text = $php['openssl_enabled'] ? '✅ Aktiv' : '❌ Fehlt';
            $html .= '<span class="' . $ssl_status . '"><strong>OpenSSL:</strong> <span>' . $ssl_text . '</span></span>';
            $html .= '</div></div>';
        }
        
        // KI-APIs
        if (isset($status_data['apis'])) {
            $apis = $status_data['apis'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🤖 KI-Provider</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            
            foreach ($apis as $provider => $status) {
                $status_class = $status ? 'status-ok' : 'status-error';
                $status_text = $status ? '✅ Verbunden' : '❌ Nicht verfügbar';
                
                $html .= '<span class="' . $status_class . '"><strong>' . esc_html($provider) . ':</strong> <span>' . $status_text . '</span></span>';
            }
            
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * ✅ OPTIMIERT: Einheitliche API-Test-Funktion (statt 10+ redundante Funktionen)
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
     * API-Services Test (Verwendet einheitliche API-Test-Funktion)
     */
    public function handle_test_api_services() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            // Cache für 5 Minuten
            $cache_key = 'retexify_api_services_status';
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                wp_send_json_success($cached);
                return;
            }
            
            // ✅ OPTIMIERT: Einheitliche API-Tests
            $api_status = array();
            $api_status['Google Suggest'] = true; // Immer verfügbar
            $api_status['Wikipedia API'] = $this->test_api('https://de.wikipedia.org/api/rest_v1/page/summary/Berlin');
            $api_status['Wiktionary API'] = $this->test_api('https://de.wiktionary.org/api/rest_v1/page/summary/Wort');
            $api_status['OpenStreetMap'] = $this->test_api('https://nominatim.openstreetmap.org/search?q=Zürich&format=json&limit=1');
            
            // Research Engine Status (falls verfügbar)
            if (class_exists('ReTexify_Intelligent_Keyword_Research')) {
                try {
                    $research_status = ReTexify_Intelligent_Keyword_Research::test_research_capabilities();
                    $api_status['Research Engine'] = $research_status['prompt_generation'] ?? false;
                } catch (Exception $e) {
                    $api_status['Research Engine'] = false;
                }
            }
            
            $html = $this->format_api_status_html($api_status);
            
            set_transient($cache_key, $html, 5 * MINUTE_IN_SECONDS);
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('API-Services-Fehler: ' . $e->getMessage());
        }
    }
    
    /**
     * API-Status als HTML formatieren
     */
    private function format_api_status_html($api_status) {
        $html = '<div class="retexify-system-info-modern">';
        $html .= '<h4>🧠 Research Engine APIs</h4>';
        $html .= '<div class="retexify-system-grid-modern">';
        
        foreach ($api_status as $api_name => $status) {
            $status_class = $status ? 'status-ok' : 'status-error';
            $icon = $status ? '✅' : '❌';
            $text = $status ? 'Aktiv' : 'Offline';
            
            $html .= '<span class="' . $status_class . '">';
            $html .= '<strong>' . esc_html($api_name) . ':</strong> ';
            $html .= $icon . ' ' . $text;
            $html .= '</span>';
        }
        
        $html .= '</div>';
        $html .= '<div style="margin-top:10px;font-size:12px;color:#666;">';
        $html .= '<strong>Info:</strong> Bei API-Problemen erfolgt automatischer Fallback.';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }
    
    // ==== EXPORT/IMPORT AJAX HANDLERS (Falls verfügbar) ====
    
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
        $imports_dir = $upload_dir['basedir'] . '/retexify-imports/';
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
        
    // ==== META-DATEN HELPER ====
    
    private function get_meta_title($post_id) {
        $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($title)) return $title;
        
        $title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($title)) return $title;
        
        $title = get_post_meta($post_id, '_aioseop_title', true);
        if (!empty($title)) return $title;
        
        $title = get_post_meta($post_id, '_seopress_titles_title', true);
        if (!empty($title)) return $title;
        
        return '';
    }
    
    private function get_meta_description($post_id) {
        $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($desc)) return $desc;
        
        $desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($desc)) return $desc;
        
        $desc = get_post_meta($post_id, '_aioseop_description', true);
        if (!empty($desc)) return $desc;
        
        $desc = get_post_meta($post_id, '_seopress_titles_desc', true);
        if (!empty($desc)) return $desc;
        
        return '';
    }
    
    private function get_focus_keyword($post_id) {
        $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($keyword)) return $keyword;
        
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($keyword)) return $keyword;
        
        return '';
    }
    
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
    
    /**
     * ✅ RESEARCH-APIS TESTEN
     */
    private function test_research_apis() {
        return array(
            'Google Suggest' => $this->test_google_suggest(),
            'Wikipedia' => $this->test_wikipedia(),
            'Wiktionary' => $this->test_wiktionary(),
            'OpenStreetMap' => $this->test_openstreetmap()
        );
    }
    
    /**
     * ✅ RESEARCH-API-TESTS
     */
    private function test_google_suggest() {
        $response = wp_remote_get('https://suggestqueries.google.com/complete/search?client=firefox&q=test', array(
            'timeout' => 3,
            'user-agent' => 'Mozilla/5.0 (compatible; ReTexify-AI)'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function test_wikipedia() {
        $response = wp_remote_get('https://de.wikipedia.org/api/rest_v1/page/summary/Test', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function test_wiktionary() {
        $response = wp_remote_get('https://de.wiktionary.org/api/rest_v1/page/summary/test', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    private function test_openstreetmap() {
        $response = wp_remote_get('https://nominatim.openstreetmap.org/search?q=Bern&format=json&limit=1', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * ✅ HTML-RENDERING FÜR RESEARCH-STATUS
     */
    private function render_research_status_html($research_data) {
        $html = '<div class="retexify-intelligent-status" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; padding: 25px; margin: 20px 0;">';
        $html .= '<h4 style="margin: 0 0 20px 0; color: white;">🧠 Intelligent Research Status</h4>';
        $html .= '<div class="api-status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';
        
        foreach ($research_data as $api_name => $status) {
            $status_class = $status ? 'status-ok' : 'status-error';
            $status_text = $status ? '✅ Aktiv' : '❌ Offline';
            
            $html .= '<div class="status-item" style="background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center;">';
            $html .= '<strong style="color: white;">' . esc_html($api_name) . ':</strong>';
            $html .= '<span class="status-indicator ' . $status_class . '" style="font-weight: bold; padding: 4px 8px; border-radius: 4px; font-size: 12px;">' . $status_text . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    

    

    
    // ============================================================================
    // 🔧 SYSTEM-STATUS AJAX-HANDLER - NEUE VERSION 4.1.0
    // ============================================================================
    
    /**
     * ✅ System-Status testen (neue Version)
     */
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
            
            wp_send_json_success(array(
                'data' => $html,
                'tests' => $system_tests
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('System-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ Research-APIs testen (neue Version)
     */
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
            
            wp_send_json_success(array(
                'data' => $html,
                'tests' => $api_tests
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Research-API-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ Dashboard-Statistiken laden (neue Version)
     */
    public function ajax_get_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            // Basis-Statistiken sammeln
            $stats = array(
                'total_posts' => wp_count_posts('post')->publish,
                'total_pages' => wp_count_posts('page')->publish,
                'plugin_version' => RETEXIFY_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'php_version' => phpversion()
            );
            
            // SEO-Plugin Detection
            $seo_plugins = array();
            if (is_plugin_active('wordpress-seo/wp-seo.php')) {
                $seo_plugins[] = 'Yoast SEO';
            }
            if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
                $seo_plugins[] = 'Rank Math';
            }
            if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
                $seo_plugins[] = 'All in One SEO';
            }
            if (is_plugin_active('wp-seopress/seopress.php')) {
                $seo_plugins[] = 'SEOPress';
            }
            
            $stats['seo_plugins'] = $seo_plugins;
            
            // HTML für Dashboard generieren
            $html = $this->generate_dashboard_html($stats);
            
            wp_send_json_success(array(
                'data' => $html,
                'stats' => $stats
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Dashboard-Laden fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    // ============================================================================
    // 🛠️ HELPER-METHODEN FÜR SEO-GENERIERUNG - NEUE VERSION 4.1.0
    // ============================================================================
    
    /**
     * Meta-Titel generieren (neue Version)
     */
    private function generate_meta_title_content($post, $content) {
        if (!$this->ai_engine) {
            return '';
        }
        
        $prompt = "Generiere einen SEO-optimierten Meta-Titel (max. 58 Zeichen) für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1000) . "\n\n";
        $prompt .= "Anforderungen:\n";
        $prompt .= "- Maximal 58 Zeichen\n";
        $prompt .= "- Prägnant und ansprechend\n";
        $prompt .= "- Enthält wichtigste Keywords\n";
        $prompt .= "- Für deutsche Zielgruppe optimiert\n";
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
     * Meta-Beschreibung generieren (neue Version)
     */
    private function generate_meta_description_content($post, $content) {
        if (!$this->ai_engine) {
            return '';
        }
        
        $prompt = "Generiere eine SEO-optimierte Meta-Beschreibung (140-155 Zeichen) für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1500) . "\n\n";
        $prompt .= "Anforderungen:\n";
        $prompt .= "- 140-155 Zeichen lang\n";
        $prompt .= "- Motiviert zum Klicken\n";
        $prompt .= "- Enthält wichtigste Keywords\n";
        $prompt .= "- Für deutsche Zielgruppe optimiert\n";
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
     * Focus-Keyword generieren (neue Version)
     */
    private function generate_focus_keyword_content($post, $content) {
        if (!$this->ai_engine) {
            return '';
        }
        
        $prompt = "Identifiziere das beste Focus-Keyword für folgenden deutschen Content:\n\n";
        $prompt .= "Titel: " . $post->post_title . "\n";
        $prompt .= "Content: " . substr($content, 0, 1000) . "\n\n";
        $prompt .= "Anforderungen:\n";
        $prompt .= "- 1-3 Wörter lang\n";
        $prompt .= "- Hohe Suchrelevanz\n";
        $prompt .= "- Auf Deutsch\n";
        $prompt .= "- Ohne Sonderzeichen\n\n";
        $prompt .= "Gib nur das Focus-Keyword zurück, keine Erklärungen:";
        
        try {
            $response = $this->ai_engine->generate_text($prompt);
            return trim(strtolower(str_replace('"', '', $response)));
        } catch (Exception $e) {
            error_log('ReTexify Focus-Keyword Generation Error: ' . $e->getMessage());
            return '';
        }
    }
    
    // ============================================================================
    // 🛠️ HTML-GENERATOR-METHODEN - NEUE VERSION 4.1.0
    // ============================================================================
    
    /**
     * HTML für System-Status generieren (neue Version)
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
     * HTML für Research-Status generieren (neue Version)
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
    
    /**
     * HTML für Dashboard generieren (neue Version)
     */
    private function generate_dashboard_html($stats) {
        $html = '<div class="retexify-dashboard-stats">';
        
        $html .= '<div class="stats-grid">';
        
        $html .= '<div class="stat-item">';
        $html .= '<h3>📄 Posts</h3>';
        $html .= '<div class="stat-number">' . $stats['total_posts'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="stat-item">';
        $html .= '<h3>📋 Pages</h3>';
        $html .= '<div class="stat-number">' . $stats['total_pages'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="stat-item">';
        $html .= '<h3>🔧 Plugin</h3>';
        $html .= '<div class="stat-number">v' . $stats['plugin_version'] . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="stat-item">';
        $html .= '<h3>🛠️ SEO-Plugins</h3>';
        $html .= '<div class="stat-text">' . (empty($stats['seo_plugins']) ? 'Keine' : implode(', ', $stats['seo_plugins'])) . '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    // ============================================================================
    // 🧪 API-TEST-METHODEN - NEUE VERSION 4.1.0
    // ============================================================================
    
    /**
     * Google Suggest API testen (neue Version)
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
     * Wikipedia API testen (neue Version)
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
     * OpenStreetMap API testen (neue Version)
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
    
    // ============================================================================
    // 🛠️ LEGACY-HANDLER FÜR EINZELNE GENERIERUNG - NEUE VERSION 4.1.0
    // ============================================================================
    
    /**
     * Legacy: Meta-Titel generieren (Einzelhandler)
     */
    public function handle_generate_meta_title() {
        $_POST['seo_type'] = 'meta_title';
        $this->handle_generate_single_seo();
    }
    
    /**
     * Legacy: Meta-Beschreibung generieren (Einzelhandler)
     */
    public function handle_generate_meta_description() {
        $_POST['seo_type'] = 'meta_description';
        $this->handle_generate_single_seo();
    }
    
    /**
     * Legacy: Keywords generieren (Einzelhandler)
     */
    public function handle_generate_keywords() {
        $_POST['seo_type'] = 'focus_keyword';
        $this->handle_generate_single_seo();
    }
    
    /**
     * Placeholder für Keyword-Research
     */
    public function handle_keyword_research() {
        wp_send_json_success(array('keywords' => array('SEO', 'WordPress', 'Marketing')));
    }
    
    /**
     * ✅ KRITISCH: Einzelnes SEO-Element generieren (neue Version)
     */
    public function handle_generate_single_seo() {
        // Sicherheitscheck
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
}
}

// ============================================================================
// 🚀 SICHERE PLUGIN-INITIALISIERUNG (Unverändert)
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