<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen
 * Version: 3.5.9
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
if (!defined('RETEXIFY_VERSION')) {
    define('RETEXIFY_VERSION', '3.5.9');
}
if (!defined('RETEXIFY_PLUGIN_URL')) {
    define('RETEXIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('RETEXIFY_PLUGIN_PATH')) {
    define('RETEXIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// Includes laden - mit Sicherheitsprüfungen
$required_files = array(
    'includes/class-german-content-analyzer.php',
    'includes/class-ai-engine.php'
);

foreach ($required_files as $file) {
    $file_path = RETEXIFY_PLUGIN_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log('ReTexify AI: Required file missing: ' . $file);
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
        
        // AJAX Hooks für KI-Funktionalität
        add_action('wp_ajax_retexify_ai_save_settings', array($this, 'handle_ai_save_settings'));
        add_action('wp_ajax_retexify_ai_test_connection', array($this, 'handle_ai_test_connection'));
        add_action('wp_ajax_retexify_load_seo_content', array($this, 'handle_load_seo_content'));
        add_action('wp_ajax_retexify_generate_seo_item', array($this, 'handle_generate_seo_item'));
        add_action('wp_ajax_retexify_generate_complete_seo', array($this, 'handle_generate_complete_seo'));
        add_action('wp_ajax_retexify_save_seo_data', array($this, 'handle_save_seo_data'));
        add_action('wp_ajax_retexify_get_page_content', array($this, 'handle_get_page_content'));
            
        // API-Key Management
        add_action('wp_ajax_retexify_get_api_keys', array($this, 'handle_get_api_keys'));
        add_action('wp_ajax_retexify_save_api_key', array($this, 'handle_save_api_key'));
        
        // System & Debug
        add_action('wp_ajax_retexify_test', array($this, 'test_system'));
        add_action('wp_ajax_retexify_get_stats', array($this, 'get_stats'));
        
        // Export/Import Hooks (falls verfügbar)
        if ($this->export_import_manager) {
            add_action('wp_ajax_retexify_get_export_stats', array($this, 'handle_get_export_stats'));
            add_action('wp_ajax_retexify_export_content_csv', array($this, 'handle_export_content_csv'));
            add_action('wp_ajax_retexify_download_export_file', array($this, 'handle_download_export_file'));
            add_action('wp_ajax_retexify_import_csv_data', array($this, 'handle_import_csv_data'));
            add_action('wp_ajax_retexify_get_import_preview', array($this, 'handle_get_import_preview'));
            add_action('wp_ajax_retexify_save_imported_data', array($this, 'handle_save_imported_data'));
            add_action('wp_ajax_retexify_delete_upload', array($this, 'handle_delete_upload'));
        }
        
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
    
    public function activate_plugin() {
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
        if ('toplevel_page_retexify-ai-pro' !== $hook) {
            return;
        }
        
        // CSS einbinden - mit Fallback prüfung
        $css_file = RETEXIFY_PLUGIN_PATH . 'assets/admin-style.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'retexify-admin-style', 
                RETEXIFY_PLUGIN_URL . 'assets/admin-style.css', 
                array(), 
                RETEXIFY_VERSION
            );
        }
        
        // Erweiterte CSS (falls verfügbar)
        $extended_css_file = RETEXIFY_PLUGIN_PATH . 'assets/admin_styles_extended.css';
        if (file_exists($extended_css_file)) {
            wp_enqueue_style(
                'retexify-admin-style-extended', 
                RETEXIFY_PLUGIN_URL . 'assets/admin_styles_extended.css', 
                array('retexify-admin-style'), 
                RETEXIFY_VERSION
            );
        }
        
        // JavaScript einbinden - mit Fallback prüfung
        wp_enqueue_script('jquery');
        
        $js_file = RETEXIFY_PLUGIN_PATH . 'assets/admin-script.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'retexify-admin-script',
                RETEXIFY_PLUGIN_URL . 'assets/admin-script.js',
                array('jquery'),
                RETEXIFY_VERSION,
                true
            );
        }
        
        // Export/Import JavaScript (falls verfügbar)
        $export_import_js_file = RETEXIFY_PLUGIN_PATH . 'assets/export_import.js';
        if (file_exists($export_import_js_file) && $this->export_import_manager) {
            wp_enqueue_script(
                'retexify-export-import',
                RETEXIFY_PLUGIN_URL . 'assets/export_import.js',
                array('jquery', 'retexify-admin-script'),
                RETEXIFY_VERSION,
                true
            );
            
            // Zusätzliche AJAX-Daten für Export/Import
            wp_localize_script('retexify-export-import', 'retexify_export_import_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('retexify_nonce'),
                'max_file_size' => wp_max_upload_size(),
                'upload_dir' => wp_upload_dir()['basedir'] . '/retexify-imports/'
            ));
        }
        
        // AJAX-Daten für JavaScript bereitstellen
        wp_localize_script('retexify-admin-script', 'retexify_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('retexify_nonce'),
            'ai_enabled' => $this->is_ai_enabled(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'api_keys' => $this->get_all_api_keys(),
            'export_import_available' => $this->export_import_manager !== null
        ));
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
                <!-- Tab 4: Export/Import - CLEAN VERSION -->
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
                                                <!-- Dynamisch per JS/AJAX gefüllt -->
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
                                
                                <div id="retexify-export-preview" class="retexify-export-preview" style="display: none;">
                                    <h4>📋 Export-Vorschau:</h4>
                                    <div id="retexify-preview-content"></div>
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
                                    <div id="retexify-import-results" class="retexify-import-results" style="display: none;">
                                        <h4>✅ Import-Ergebnisse:</h4>
                                        <div id="retexify-import-summary-results"></div>
                                    </div>
                                </div>
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
    
    public function handle_generate_seo_item() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
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
            
            $settings = get_option('retexify_ai_settings', array());
            $api_keys = $this->get_all_api_keys();
            $current_provider = $settings['api_provider'] ?? 'openai';
            $settings['api_key'] = $api_keys[$current_provider] ?? '';
            
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel konfiguriert');
                return;
            }
            
            if ($this->ai_engine && method_exists($this->ai_engine, 'generate_single_seo_item')) {
                $generated_content = $this->ai_engine->generate_single_seo_item(
                    $post, 
                    $seo_type, 
                    $settings, 
                    $include_cantons, 
                    $premium_tone
                );
                
                wp_send_json_success(array(
                    'content' => $generated_content,
                    'type' => $seo_type,
                    'post_id' => $post_id
                ));
            } else {
                wp_send_json_error('KI-Engine nicht verfügbar');
            }
            
        } catch (Exception $e) {
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
            $include_cantons = filter_var($_POST['include_cantons'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $premium_tone = filter_var($_POST['premium_tone'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
                return;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
                return;
            }
            
            $settings = get_option('retexify_ai_settings', array());
            $api_keys = $this->get_all_api_keys();
            $current_provider = $settings['api_provider'] ?? 'openai';
            $settings['api_key'] = $api_keys[$current_provider] ?? '';
            
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel konfiguriert');
                return;
            }
            
            if ($this->ai_engine && method_exists($this->ai_engine, 'generate_complete_seo_suite')) {
                $seo_suite = $this->ai_engine->generate_complete_seo_suite(
                    $post, 
                    $settings, 
                    $include_cantons, 
                    $premium_tone
                );
                
                wp_send_json_success(array(
                    'suite' => $seo_suite,
                    'post_id' => $post_id,
                    'optimization_focus' => $settings['optimization_focus'] ?? 'complete_seo'
                ));
            } else {
                wp_send_json_error('KI-Engine nicht verfügbar');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('SEO-Suite Generierungs-Fehler: ' . $e->getMessage());
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
    
    public function test_system() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $ai_enabled = $this->is_ai_enabled();
        $yoast_active = is_plugin_active('wordpress-seo/wp-seo.php');
        $rankmath_active = is_plugin_active('seo-by-rank-math/rank-math.php');
        $aioseo_active = is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php');
        $seopress_active = is_plugin_active('wp-seopress/seopress.php');
        
        $html = '<div class="retexify-test-results">';
        $html .= '<h4>🔧 System-Test Ergebnisse:</h4>';
        $html .= '<div class="retexify-test-grid">';
        
        $html .= '<div class="retexify-test-item ' . ($ai_enabled ? 'success' : 'warning') . '">';
        $html .= '<span class="retexify-test-icon">' . ($ai_enabled ? '✅' : '⚠️') . '</span>';
        $html .= '<span class="retexify-test-label">KI-Integration: ' . ($ai_enabled ? 'Aktiv' : 'Nicht konfiguriert') . '</span>';
        $html .= '</div>';
        
        $seo_plugins = 0;
        if ($yoast_active) $seo_plugins++;
        if ($rankmath_active) $seo_plugins++;
        if ($aioseo_active) $seo_plugins++;
        if ($seopress_active) $seo_plugins++;
        
        $html .= '<div class="retexify-test-item ' . ($seo_plugins > 0 ? 'success' : 'warning') . '">';
        $html .= '<span class="retexify-test-icon">' . ($seo_plugins > 0 ? '✅' : '⚠️') . '</span>';
        $html .= '<span class="retexify-test-label">SEO-Plugins: ' . $seo_plugins . ' erkannt</span>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-test-item ' . ($this->export_import_manager ? 'success' : 'warning') . '">';
        $html .= '<span class="retexify-test-icon">' . ($this->export_import_manager ? '✅' : '⚠️') . '</span>';
        $html .= '<span class="retexify-test-label">Export/Import: ' . ($this->export_import_manager ? 'Verfügbar' : 'Nicht verfügbar') . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-test-item success">';
        $html .= '<span class="retexify-test-icon">✅</span>';
        $html .= '<span class="retexify-test-label">WordPress: ' . get_bloginfo('version') . '</span>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-test-item success">';
        $html .= '<span class="retexify-test-icon">✅</span>';
        $html .= '<span class="retexify-test-label">PHP: ' . phpversion() . '</span>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        if ($ai_enabled && $seo_plugins > 0) {
            $html .= '<div class="retexify-test-success">';
            $html .= '<strong>🇨🇭 SYSTEM READY!</strong> Alle Komponenten funktional. Plugin bereit für universelle SEO-Optimierung.';
            $html .= '</div>';
        } else {
            $html .= '<div class="retexify-test-warning">';
            $html .= '<strong>⚠️ SETUP UNVOLLSTÄNDIG</strong> Bitte konfigurieren Sie die KI-Einstellungen.';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    // ==== EXPORT/IMPORT AJAX HANDLERS ====
    
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
        
    // META-DATEN HELPER
    
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
}
}

// SICHERE PLUGIN-INITIALISIERUNG
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