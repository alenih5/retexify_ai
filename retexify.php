<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen
 * Version: 3.5.5
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
if (!defined('RETEXIFY_VERSION')) {
    define('RETEXIFY_VERSION', '3.5.5');
}
if (!defined('RETEXIFY_PLUGIN_URL')) {
    define('RETEXIFY_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('RETEXIFY_PLUGIN_PATH')) {
    define('RETEXIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

// Includes laden
require_once RETEXIFY_PLUGIN_PATH . 'includes/class-german-content-analyzer.php';
require_once RETEXIFY_PLUGIN_PATH . 'includes/class-ai-engine.php';

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
        
        public function __construct() {
            // Content-Analyzer initialisieren
            $this->content_analyzer = retexify_get_content_analyzer();
            
            // AI-Engine initialisieren
            $this->ai_engine = retexify_get_ai_engine();
            
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
            
            // NEUE: API-Key Management
            add_action('wp_ajax_retexify_get_api_keys', array($this, 'handle_get_api_keys'));
            add_action('wp_ajax_retexify_save_api_key', array($this, 'handle_save_api_key'));
            
            // System & Debug
            add_action('wp_ajax_retexify_test', array($this, 'test_system'));
            add_action('wp_ajax_retexify_get_stats', array($this, 'get_stats'));
            
            register_activation_hook(__FILE__, array($this, 'activate_plugin'));
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
            
            // NEUE: Separate API-Key Speicherung initialisieren
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
            
            // AJAX-Daten für JavaScript bereitstellen
            wp_localize_script('retexify-admin-script', 'retexify_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('retexify_nonce'),
                'ai_enabled' => $this->is_ai_enabled(),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'api_keys' => $this->get_all_api_keys() // Für JavaScript verfügbar machen
            ));
        }
        
        private function is_ai_enabled() {
            $api_keys = get_option('retexify_api_keys', array());
            $settings = get_option('retexify_ai_settings', array());
            $current_provider = $settings['api_provider'] ?? 'openai';
            
            return !empty($api_keys[$current_provider]);
        }
        
        /**
         * NEUE: Alle API-Keys abrufen (für JavaScript)
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
            
            // KI-Engine Instanz für Provider/Model-Daten
            $available_providers = $this->ai_engine->get_available_providers();
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
                                                    <!-- Wird dynamisch mit JavaScript gefüllt -->
                                                </select>
                                                <small id="model-help">Das Modell bestimmt Qualität und Kosten der KI-Generierung</small>
                                            </div>
                                            
                                            <!-- ANGEPASST: Provider-Vergleich nur für aktuellen Provider -->
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
                    
                    <!-- Tab 4: System -->
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
                var providerModels = <?php echo json_encode(array(
                    'openai' => $this->ai_engine->get_models_for_provider('openai'),
                    'anthropic' => $this->ai_engine->get_models_for_provider('anthropic'),
                    'gemini' => $this->ai_engine->get_models_for_provider('gemini')
                )); ?>;
                
                // API Keys für jeden Provider
                var apiKeys = <?php echo json_encode($api_keys); ?>;
                
                var currentProvider = '<?php echo esc_js($ai_settings['api_provider'] ?? 'openai'); ?>';
                var currentModel = '<?php echo esc_js($ai_settings['model'] ?? ''); ?>';
                
                console.log('Provider Models:', providerModels);
                console.log('API Keys:', apiKeys);
                console.log('Current Provider:', currentProvider);
                
                // Provider Wechsel Handler
                $('#ai-provider').change(function() {
                    var selectedProvider = $(this).val();
                    console.log('Provider gewechselt zu:', selectedProvider);
                    currentProvider = selectedProvider;
                    
                    updateModelsForProvider(selectedProvider);
                    updateApiKeyHelp(selectedProvider);
                    updateProviderComparison(selectedProvider);
                    
                    // KORRIGIERT: API-Key für neuen Provider laden
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
                    
                    // Kostenschätzung aktualisieren
                    updateCostEstimation(provider, $modelSelect.val());
                }
                
                function updateApiKeyHelp(provider) {
                    var helpTexts = {
                        'openai': 'OpenAI: Erhältlich auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a><br>Format: sk-...',
                        'anthropic': 'Anthropic: Erhältlich auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a><br>Format: sk-ant-...',
                        'gemini': 'Google: Erhältlich auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a><br>Format: AIza...'
                    };
                    
                    $('#api-key-help').html(helpTexts[provider] || 'API-Schlüssel eingeben');
                }
                
                // NEUE: Provider-Vergleich nur für aktuellen Provider
                function updateProviderComparison(provider) {
                    var providerInfo = {
                        'openai': {
                            title: '📊 OpenAI GPT:',
                            features: [
                                'Sehr günstig (GPT-4o Mini)',
                                'Bewährt für SEO',
                                'Schnell & zuverlässig',
                                'Große Modellauswahl'
                            ]
                        },
                        'anthropic': {
                            title: '📊 Anthropic Claude:',
                            features: [
                                'Ausgezeichnete Textqualität',
                                'Sehr präzise Anweisungen',
                                'Ethisch ausgerichtet',
                                'Lange Kontexte möglich'
                            ]
                        },
                        'gemini': {
                            title: '📊 Google Gemini:',
                            features: [
                                'Innovative Technologie',
                                'Multimodal capabilities',
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
                
                function updateCostEstimation(provider, model) {
                    // Entferne vorherige Kostenschätzung
                    $('.retexify-cost-estimation').remove();
                    
                    if (!model) return;
                    
                    var costs = getCostEstimation(provider, model);
                    
                    if (costs) {
                        var costHtml = `
                            <div class="retexify-cost-estimation">
                                <h5>💰 Kostenschätzung pro Request:</h5>
                                <div class="retexify-cost-grid">
                                    <div class="retexify-cost-item">
                                        <span class="retexify-cost-value">$${costs.perRequest}</span>
                                        <span class="retexify-cost-label">Pro SEO-Suite</span>
                                    </div>
                                    <div class="retexify-cost-item">
                                        <span class="retexify-cost-value">${costs.speed}</span>
                                        <span class="retexify-cost-label">Geschwindigkeit</span>
                                    </div>
                                    <div class="retexify-cost-item">
                                        <span class="retexify-cost-value">${costs.quality}</span>
                                        <span class="retexify-cost-label">Qualität</span>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        $('#ai-model').closest('.retexify-field').after(costHtml);
                    }
                }
                
                function getCostEstimation(provider, model) {
                    var estimates = {
                        'openai': {
                            'gpt-4o-mini': { perRequest: '0.001', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐' },
                            'gpt-4o': { perRequest: '0.015', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐' },
                            'o1-mini': { perRequest: '0.018', speed: '🔄 Mittel', quality: '⭐⭐⭐⭐⭐' },
                            'o1-preview': { perRequest: '0.09', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐' },
                            'gpt-4-turbo': { perRequest: '0.04', speed: '⚡ Mittel', quality: '⭐⭐⭐⭐⭐' },
                            'gpt-4': { perRequest: '0.12', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐' },
                            'gpt-3.5-turbo': { perRequest: '0.002', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐' }
                        },
                        'anthropic': {
                            'claude-3-5-sonnet-20241022': { perRequest: '0.009', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐' },
                            'claude-3-5-haiku-20241022': { perRequest: '0.003', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐' },
                            'claude-3-opus-20240229': { perRequest: '0.045', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐' },
                            'claude-3-sonnet-20240229': { perRequest: '0.009', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐' },
                            'claude-3-haiku-20240307': { perRequest: '0.0008', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐' }
                        },
                        'gemini': {
                            'gemini-1.5-pro-latest': { perRequest: '0.003', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐' },
                            'gemini-1.5-flash-latest': { perRequest: '0.0002', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐' },
                            'gemini-1.5-flash-8b-latest': { perRequest: '0.0001', speed: '⚡ Ultra-schnell', quality: '⭐⭐⭐' },
                            'gemini-1.0-pro-latest': { perRequest: '0.001', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐' },
                            'gemini-exp-1206': { perRequest: '0.001', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐' }
                        }
                    };
                    
                    return estimates[provider] && estimates[provider][model] ? estimates[provider][model] : null;
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
        
        // ==== NEUE API-KEY MANAGEMENT AJAX HANDLERS ====
        
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
        
        // ==== VERBESSERTE AJAX HANDLERS ====
        
        public function handle_ai_test_connection() {
            if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
                wp_send_json_error('Sicherheitsfehler');
                return;
            }
            
            try {
                $settings = get_option('retexify_ai_settings', array());
                $api_keys = $this->get_all_api_keys();
                $current_provider = $settings['api_provider'] ?? 'openai';
                
                // KORRIGIERT: Aktuellen API-Key verwenden
                $settings['api_key'] = $api_keys[$current_provider] ?? '';
                
                // BESSERE VALIDIERUNG: API-Key muss vorhanden sein
                if (empty($settings['api_key'])) {
                    wp_send_json_error('Kein API-Schlüssel für ' . ucfirst($current_provider) . ' konfiguriert. Bitte geben Sie zuerst einen API-Schlüssel ein.');
                    return;
                }
                
                $test_result = $this->ai_engine->test_connection($settings);
                
                if ($test_result['success']) {
                    wp_send_json_success($test_result['message']);
                } else {
                    wp_send_json_error($test_result['message']);
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
                
                $settings = $this->ai_engine->validate_settings($raw_settings);
                
                // API-Key aus Settings entfernen (wird separat gespeichert)
                unset($settings['api_key']);
                
                update_option('retexify_ai_settings', $settings);
                
                wp_send_json_success('KI-Einstellungen erfolgreich gespeichert! ' . count($settings['target_cantons']) . ' Kantone ausgewählt.');
                
            } catch (Exception $e) {
                wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
            }
        }
        
        // ==== ALLE ANDEREN AJAX HANDLERS BLEIBEN GLEICH ====
        
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
        
        // BESTEHENDE AJAX HANDLERS...
        
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
                        
                        'full_content' => $this->content_analyzer->clean_german_text($post->post_content),
                        'content_excerpt' => wp_trim_words($this->content_analyzer->clean_german_text($post->post_content), 50),
                        
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

                $total_posts = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$wpdb->posts} 
                    WHERE post_type IN {$post_types_in} AND post_status IN {$post_status_in}
                ");
                
                $posts_with_meta_titles = $wpdb->get_var("
                    SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in}
                    AND (
                        (pm.meta_key = '_yoast_wpseo_title' AND pm.meta_value <> '') OR
                        (pm.meta_key = 'rank_math_title' AND pm.meta_value <> '') OR
                        (pm.meta_key = '_aioseop_title' AND pm.meta_value <> '') OR
                        (pm.meta_key = '_seopress_titles_title' AND pm.meta_value <> '')
                    )
                ");
                
                $posts_with_meta_descriptions = $wpdb->get_var("
                    SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in}
                    AND (
                        (pm.meta_key = '_yoast_wpseo_metadesc' AND pm.meta_value <> '') OR
                        (pm.meta_key = 'rank_math_description' AND pm.meta_value <> '') OR
                        (pm.meta_key = '_aioseop_description' AND pm.meta_value <> '') OR
                        (pm.meta_key = '_seopress_titles_desc' AND pm.meta_value <> '')
                    )
                ");
                
                $posts_with_focus_keywords = $wpdb->get_var("
                    SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                    WHERE p.post_type IN {$post_types_in} AND p.post_status IN {$post_status_in}
                    AND (
                        (pm.meta_key = '_yoast_wpseo_focuskw' AND pm.meta_value <> '') OR
                        (pm.meta_key = 'rank_math_focus_keyword' AND pm.meta_value <> '')
                    )
                ");
                
                $ai_enabled = $this->is_ai_enabled();
                $ai_settings = get_option('retexify_ai_settings', array());
                
                $html = '<div class="retexify-stats-overview">';
                
                $seo_score = 0;
                if ($total_posts > 0) {
                    $title_ratio = $posts_with_meta_titles / $total_posts;
                    $desc_ratio = $posts_with_meta_descriptions / $total_posts;
                    $keyword_ratio = $posts_with_focus_keywords / $total_posts;
                    $seo_score = round(($title_ratio + $desc_ratio + $keyword_ratio) / 3 * 100);
                }
                
                $html .= '<div class="retexify-seo-score">';
                $html .= '<div class="retexify-score-circle">';
                $html .= '<span class="retexify-score-number">' . $seo_score . '</span>';
                $html .= '<span class="retexify-score-label">SEO Score</span>';
                $html .= '</div>';
                $html .= '</div>';
                
                $html .= '<div class="retexify-stats-grid">';
                
                $html .= '<div class="retexify-stat-item">';
                $html .= '<div class="retexify-stat-number">' . $total_posts . '</div>';
                $html .= '<div class="retexify-stat-label">Posts/Seiten</div>';
                $html .= '</div>';
                
                $html .= '<div class="retexify-stat-item">';
                $html .= '<div class="retexify-stat-number">' . $posts_with_meta_titles . '</div>';
                $html .= '<div class="retexify-stat-label">Meta-Titel</div>';
                $html .= '</div>';
                
                $html .= '<div class="retexify-stat-item">';
                $html .= '<div class="retexify-stat-number">' . $posts_with_meta_descriptions . '</div>';
                $html .= '<div class="retexify-stat-label">Meta-Beschreibungen</div>';
                $html .= '</div>';
                
                $html .= '<div class="retexify-stat-item">';
                $html .= '<div class="retexify-stat-number">' . $posts_with_focus_keywords . '</div>';
                $html .= '<div class="retexify-stat-label">Focus Keywords</div>';
                $html .= '</div>';
                
                $html .= '</div>';
                
                $html .= '<div class="retexify-system-info">';
                $html .= '<h4>🖥️ System-Status:</h4>';
                $html .= '<div class="retexify-system-grid">';
                $html .= '<span><strong>WordPress:</strong> ' . get_bloginfo('version') . '</span>';
                $html .= '<span><strong>Theme:</strong> ' . get_template() . '</span>';
                $html .= '<span><strong>KI-Status:</strong> ' . ($ai_enabled ? '✅ Aktiv (' . ucfirst($ai_settings['api_provider'] ?? 'Unbekannt') . ')' : '❌ Nicht konfiguriert') . '</span>';
                if ($ai_enabled && !empty($ai_settings['target_cantons'])) {
                    $html .= '<span><strong>Kantone:</strong> ' . count($ai_settings['target_cantons']) . ' ausgewählt</span>';
                }
                if (!empty($ai_settings['business_context'])) {
                    $html .= '<span><strong>Business:</strong> ' . wp_trim_words($ai_settings['business_context'], 4) . '</span>';
                }
                $html .= '</div>';
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
                
                $analysis = $this->content_analyzer->analyze_content($post->post_content, $post->post_title);
                $seo_score = $this->content_analyzer->calculate_seo_score($analysis);
                
                $result = array_merge($analysis, array(
                    'seo_score' => $seo_score,
                    'has_images' => has_post_thumbnail($post_id)
                ));
                
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

// Plugin initialisieren
new ReTexify_AI_Pro_Universal();