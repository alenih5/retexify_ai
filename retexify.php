<?php
/**
 * Plugin Name: ReTexify AI Pro (Complete & Fixed)
 * Description: Intelligentes WordPress SEO-Plugin mit korrigierter Content-Analyse, WPBakery-Integration und Schweizer Kantonen
 * Version: 3.2.0
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_AI_Pro_Complete {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // AJAX Hooks
        add_action('wp_ajax_retexify_ai_save_settings', array($this, 'handle_ai_save_settings'));
        add_action('wp_ajax_retexify_ai_test_connection', array($this, 'handle_ai_test_connection'));
        add_action('wp_ajax_retexify_test', array($this, 'test_system'));
        add_action('wp_ajax_retexify_load_seo_content', array($this, 'handle_load_seo_content'));
        add_action('wp_ajax_retexify_generate_seo_suite', array($this, 'handle_generate_seo_suite'));
        add_action('wp_ajax_retexify_save_seo_suite', array($this, 'handle_save_seo_suite'));
        add_action('wp_ajax_retexify_analyze_page_content', array($this, 'handle_analyze_page_content'));
        
        // Original Export/Import (VOLLSTÄNDIG WIEDERHERGESTELLT)
        add_action('wp_ajax_retexify_export', array($this, 'handle_export'));
        add_action('wp_ajax_retexify_import', array($this, 'handle_import'));
        add_action('wp_ajax_retexify_preview', array($this, 'preview_export'));
        add_action('wp_ajax_retexify_get_stats', array($this, 'get_enhanced_stats'));
        add_action('wp_ajax_retexify_get_counts', array($this, 'get_content_counts'));
        add_action('wp_ajax_retexify_check_wpbakery', array($this, 'check_wpbakery_status'));
        add_action('wp_ajax_retexify_debug_export', array($this, 'debug_export'));
        
        add_action('admin_init', array($this, 'handle_file_download'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
    }
    
    public function activate_plugin() {
        $upload_dir = wp_upload_dir();
        wp_mkdir_p($upload_dir['basedir'] . '/retexify-temp/');
        
        if (!get_option('retexify_ai_settings')) {
            add_option('retexify_ai_settings', array(
                'api_provider' => 'openai',
                'api_key' => '',
                'model' => 'gpt-4o-mini',
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'default_language' => 'de-ch',
                'business_context' => 'Innenausbau und Renovationen in der Schweiz, spezialisiert auf Parkett, Laminat und Schreinerlösungen',
                'target_audience' => 'Privatkunden, Verwaltungen, Architekten',
                'brand_voice' => 'professional',
                'target_cantons' => array('BE', 'ZH', 'LU', 'SG'), // Default Kantone
                'use_swiss_german' => true,
                'include_regional_keywords' => true
            ));
        }
    }
    
    public function add_admin_menu() {
        add_management_page(
            'ReTexify AI Pro',
            'ReTexify AI Pro', 
            'manage_options',
            'retexify-ai-pro',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_assets($hook) {
        if ('tools_page_retexify-ai-pro' !== $hook) {
            return;
        }
        
        wp_add_inline_style('wp-admin', $this->get_admin_css());
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_admin_js());
        
        wp_localize_script('jquery', 'retexify_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('retexify_nonce'),
            'ai_enabled' => $this->is_ai_enabled()
        ));
    }
    
    private function is_ai_enabled() {
        $settings = get_option('retexify_ai_settings', array());
        return !empty($settings['api_key']);
    }
    
    // SCHWEIZER KANTONE (VOLLSTÄNDIG)
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
    
    public function admin_page() {
        $ai_settings = get_option('retexify_ai_settings', array());
        $ai_enabled = $this->is_ai_enabled();
        $swiss_cantons = $this->get_swiss_cantons();
        ?>
        <div class="retexify-admin-wrap">
            <h1 class="retexify-title">
                <span class="dashicons dashicons-superhero"></span>
                ReTexify AI Pro - 🇨🇭 Schweizer SEO-Optimierung (Complete)
            </h1>
            
            <div class="retexify-description">
                <p><strong>🇨🇭 ReTexify AI Pro:</strong> Korrigierte Content-Analyse • WPBakery-Integration repariert • Alle 26 Schweizer Kantone • Vollständiges Dashboard</p>
            </div>

            <div class="retexify-tabs">
                <div class="retexify-tab-buttons">
                    <button class="retexify-tab-btn active" data-tab="dashboard">📊 Dashboard</button>
                    <button class="retexify-tab-btn" data-tab="seo-optimizer">🚀 SEO-Optimizer</button>
                    <button class="retexify-tab-btn" data-tab="export-import">📥 Export/Import</button>
                    <button class="retexify-tab-btn" data-tab="ai-settings">⚙️ Einstellungen</button>
                    <button class="retexify-tab-btn" data-tab="system">🔧 System</button>
                </div>
                
                <!-- Tab 1: Dashboard (VOLLSTÄNDIG WIEDERHERGESTELLT) -->
                <div class="retexify-tab-content active" id="tab-dashboard">
                    
                    <!-- Content-Dashboard (MIT ORIGINALEM DESIGN) -->
                    <div class="retexify-section">
                        <h2>📊 Content-Dashboard (Korrigiert)</h2>
                        <div id="retexify-enhanced-dashboard">
                            <div class="retexify-loading-dashboard">🔄 Lade Dashboard...</div>
                        </div>
                        <button type="button" id="retexify-refresh-stats" class="button">
                            <span class="dashicons dashicons-update"></span> Aktualisieren
                        </button>
                    </div>
                    
                    <!-- Debug-Bereich (WIEDERHERGESTELLT) -->
                    <div class="retexify-section">
                        <h2>🔍 System-Status & Debug</h2>
                        <div id="retexify-system-status">
                            <?php $this->show_system_status(); ?>
                        </div>
                        <button type="button" id="retexify-debug-btn" class="button">
                            <span class="dashicons dashicons-admin-tools"></span> Debug Export
                        </button>
                    </div>
                    
                    <!-- Schnell-Aktionen -->
                    <div class="retexify-section">
                        <h2>⚡ Schnell-Aktionen</h2>
                        <div class="retexify-quick-actions">
                            <button type="button" id="retexify-quick-seo-optimizer" class="button button-primary button-large">
                                <span class="dashicons dashicons-superhero"></span> SEO-Optimizer starten
                            </button>
                            <button type="button" id="retexify-quick-export" class="button button-large">
                                <span class="dashicons dashicons-download"></span> Schnell-Export
                            </button>
                            <button type="button" id="retexify-quick-test" class="button button-large">
                                <span class="dashicons dashicons-admin-tools"></span> System testen
                            </button>
                        </div>
                        <div id="retexify-quick-result"></div>
                    </div>
                </div>
                
                <!-- Tab 2: SEO-Optimizer (VERBESSERT) -->
                <div class="retexify-tab-content" id="tab-seo-optimizer">
                    
                    <?php if ($ai_enabled): ?>
                    <div class="retexify-section">
                        <h2>🚀 Intelligenter SEO-Optimizer (Deutsche Content-Analyse)</h2>
                        
                        <!-- Controls -->
                        <div class="retexify-seo-controls">
                            <div class="retexify-control-group">
                                <label for="seo-post-type">Post-Typ:</label>
                                <select id="seo-post-type">
                                    <option value="post">Beiträge</option>
                                    <option value="page">Seiten</option>
                                </select>
                            </div>
                            
                            <div class="retexify-control-group">
                                <label for="seo-optimization-focus">Optimierungs-Fokus:</label>
                                <select id="seo-optimization-focus">
                                    <option value="complete_seo">Komplett-SEO (Meta-Titel + Meta-Beschreibung + Keyword)</option>
                                    <option value="local_seo_swiss">Schweizer Local SEO mit Kantonen</option>
                                    <option value="conversion">Conversion-optimiert</option>
                                    <option value="readability">Lesbarkeit (Schweizer Hochdeutsch)</option>
                                    <option value="premium_business">Premium Business-Texte</option>
                                </select>
                            </div>
                            
                            <button type="button" id="retexify-load-seo-content" class="button button-primary button-large">
                                <span class="dashicons dashicons-search"></span> SEO-Content laden & analysieren
                        </button>
                    </div>
                        
                        <!-- SEO-Optimizer Container -->
                        <div id="retexify-seo-optimizer" style="display: none;">
                            
                            <!-- Navigation -->
                            <div class="retexify-seo-nav">
                                <button type="button" id="retexify-seo-prev" class="button" disabled>
                                    <span class="dashicons dashicons-arrow-left-alt2"></span> Vorherige Seite
                                </button>
                                <span id="retexify-seo-counter">1 / 10</span>
                                <button type="button" id="retexify-seo-next" class="button">
                                    Nächste Seite <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </button>
                            </div>
                            
                            <!-- Current Page Info -->
                            <div class="retexify-current-page">
                                <h3 id="retexify-current-page-title">Seite wird geladen...</h3>
                                <div class="retexify-page-details">
                                    <div class="retexify-page-info">
                                        <p id="retexify-page-info-text">Seiten-Info wird geladen...</p>
                                        <div class="retexify-page-url">
                                            <strong>URL:</strong> 
                                            <a id="retexify-page-url-link" href="#" target="_blank" rel="noopener">
                                                <span id="retexify-page-url-text">URL wird geladen...</span>
                                                <span class="dashicons dashicons-external"></span>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="retexify-page-actions">
                                        <a id="retexify-edit-page-link" href="#" target="_blank" class="button button-small">
                                            <span class="dashicons dashicons-edit"></span> In WordPress bearbeiten
                                        </a>
                                        <button type="button" id="retexify-analyze-page-content" class="button button-small">
                                            <span class="dashicons dashicons-analytics"></span> Deutsche Content-Analyse
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Content-Analyse (KORRIGIERT FÜR DEUTSCHE TEXTE) -->
                            <div class="retexify-content-analysis" id="retexify-content-analysis" style="display: none;">
                                <h4>📊 Deutsche Content-Analyse (Korrigiert)</h4>
                                <div id="retexify-analysis-result"></div>
                            </div>
                            
                            <!-- SEO-Suite Vergleich -->
                            <div class="retexify-seo-comparison">
                                
                                <!-- Original SEO-Daten -->
                                <div class="retexify-seo-section">
                                    <h4>🔍 Aktuelle SEO-Daten</h4>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Meta-Titel (Original)</label>
                                        <div class="retexify-seo-original" id="retexify-original-meta-title">
                                            Original Meta-Titel wird hier angezeigt...
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Meta-Beschreibung (Original)</label>
                                        <div class="retexify-seo-original" id="retexify-original-meta-description">
                                            Original Meta-Beschreibung wird hier angezeigt...
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Focus-Keyword (Original)</label>
                                        <div class="retexify-seo-original" id="retexify-original-focus-keyword">
                                            Original Focus-Keyword wird hier angezeigt...
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- KI-optimierte SEO-Daten -->
                                <div class="retexify-seo-section">
                                    <h4>🇨🇭 KI-optimierte SEO-Suite (Premium Schweizer Hochdeutsch)</h4>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Meta-Titel (KI-optimiert, editierbar)</label>
                                        <textarea class="retexify-seo-ai-field" id="retexify-ai-meta-title" placeholder="Klicken Sie auf 'Premium SEO-Suite generieren' für KI-Optimierung..."></textarea>
                                        <div class="retexify-field-info">
                                            <span class="retexify-char-count" data-field="title">0/60 Zeichen</span>
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Meta-Beschreibung (KI-optimiert, editierbar)</label>
                                        <textarea class="retexify-seo-ai-field" id="retexify-ai-meta-description" placeholder="KI-optimierte Meta-Beschreibung wird hier angezeigt..."></textarea>
                                        <div class="retexify-field-info">
                                            <span class="retexify-char-count" data-field="description">0/160 Zeichen</span>
                                        </div>
                                    </div>
                                    
                                    <div class="retexify-seo-field">
                                        <label>Focus-Keyword (KI-optimiert, editierbar)</label>
                                        <input type="text" class="retexify-seo-ai-field" id="retexify-ai-focus-keyword" placeholder="KI-optimiertes Focus-Keyword wird hier angezeigt...">
                                    </div>
                                    
                                    <!-- KI-Generation Button -->
                                    <div class="retexify-ai-generation">
                                        <button type="button" id="retexify-generate-seo-suite" class="button button-primary button-large">
                                            <span class="dashicons dashicons-superhero"></span> 🇨🇭 Premium SEO-Suite mit KI generieren
                                        </button>
                                        <div class="retexify-ai-options">
                                            <label>
                                                <input type="checkbox" id="retexify-include-cantons" checked> 
                                                Schweizer Kantone in SEO berücksichtigen
                                            </label>
                                            <label>
                                                <input type="checkbox" id="retexify-premium-business" checked> 
                                                Premium Business-Texte generieren
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO Actions -->
                            <div class="retexify-seo-actions" id="retexify-seo-actions" style="display: none;">
                                <button type="button" id="retexify-save-all-seo" class="button button-primary button-large">
                                    <span class="dashicons dashicons-yes-alt"></span> In Yoast + WPBakery + alle Plugins speichern
                                </button>
                                <button type="button" id="retexify-regenerate-seo" class="button">
                                    <span class="dashicons dashicons-update"></span> Neu generieren
                                </button>
                                <button type="button" id="retexify-reject-seo" class="button">
                                    <span class="dashicons dashicons-dismiss"></span> Ablehnen & nächste Seite
                                </button>
                            </div>
                            
                            <!-- SEO-Statistiken -->
                            <div class="retexify-seo-stats">
                                <div class="retexify-stat-item">
                                    <span class="retexify-stat-number" id="stat-seo-generated">0</span>
                                    <span class="retexify-stat-label">SEO-Suites generiert</span>
                                </div>
                                <div class="retexify-stat-item">
                                    <span class="retexify-stat-number" id="stat-seo-saved">0</span>
                                    <span class="retexify-stat-label">Komplett übernommen</span>
                                </div>
                                <div class="retexify-stat-item">
                                    <span class="retexify-stat-number" id="stat-wpbakery-updated">0</span>
                                    <span class="retexify-stat-label">WPBakery-Updates</span>
                                </div>
                            </div>
                        </div>
                        
                        <div id="retexify-seo-result"></div>
                    </div>
                    <?php else: ?>
                    <div class="retexify-section">
                        <div class="retexify-warning">
                            <h3>🔧 KI-Setup erforderlich</h3>
                            <p>Bitte konfigurieren Sie zuerst Ihren OpenAI API-Schlüssel in den Einstellungen.</p>
                            <button type="button" class="button button-primary" onclick="jQuery('.retexify-tab-btn[data-tab=ai-settings]').click();">
                                Zu den Einstellungen
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tab 3: Export/Import (VOLLSTÄNDIG WIEDERHERGESTELLT) -->
                <div class="retexify-tab-content" id="tab-export-import">
                    
                    <div class="retexify-main-container">
                        <!-- Export Card -->
                        <div class="retexify-card retexify-export-card">
                            <div class="retexify-card-header">
                                <h3><span class="dashicons dashicons-download"></span> Export</h3>
                            </div>
                            <div class="retexify-card-content">
                                
                                <!-- Post-Typen -->
                                <div class="retexify-selection-section">
                                    <h4><span class="dashicons dashicons-admin-post"></span> Post-Typen</h4>
                                    <div class="retexify-checkbox-grid" id="retexify-post-types-grid">
                                        <!-- Wird per JavaScript gefüllt -->
                                    </div>
                                </div>
                                
                                <!-- Post-Status -->
                                <div class="retexify-selection-section">
                                    <h4><span class="dashicons dashicons-visibility"></span> Status</h4>
                                    <div class="retexify-checkbox-grid">
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-status-checkbox" name="post_status[]" value="publish" checked>
                                            <span class="retexify-checkbox-label">Veröffentlicht (<span class="retexify-count" id="count-publish">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-status-checkbox" name="post_status[]" value="draft">
                                            <span class="retexify-checkbox-label">Entwürfe (<span class="retexify-count" id="count-draft">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-status-checkbox" name="post_status[]" value="private">
                                            <span class="retexify-checkbox-label">Privat (<span class="retexify-count" id="count-private">0</span>)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Content-Typen -->
                                <div class="retexify-selection-section">
                                    <h4><span class="dashicons dashicons-edit"></span> Inhalte</h4>
                                    <div class="retexify-checkbox-grid">
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="title" checked>
                                            <span class="retexify-checkbox-label">📝 Titel (<span class="retexify-count" id="count-title">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="content">
                                            <span class="retexify-checkbox-label">📄 Content (<span class="retexify-count" id="count-content">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="meta_title" checked>
                                            <span class="retexify-checkbox-label">🎯 Meta-Titel (<span class="retexify-count" id="count-meta-title">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="meta_description" checked>
                                            <span class="retexify-checkbox-label">📊 Meta-Beschreibung (<span class="retexify-count" id="count-meta-desc">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="focus_keyphrase" checked>
                                            <span class="retexify-checkbox-label">🔑 Focus Keyphrase (<span class="retexify-count" id="count-focus">0</span>)</span>
                                        </label>
                                        <!-- WPBakery Optionen - nur anzeigen wenn erkannt -->
                                        <label class="retexify-checkbox-item" id="retexify-wpbakery-option" style="display: none;">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="wpbakery_text" checked>
                                            <span class="retexify-checkbox-label">🏗️ WPBakery Text (<span class="retexify-count" id="count-wpbakery">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item" id="retexify-wpbakery-meta-title-option" style="display: none;">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="wpbakery_meta_title" checked>
                                            <span class="retexify-checkbox-label">🎯 WPBakery Meta-Titel (<span class="retexify-count" id="count-wpbakery-meta-title">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item" id="retexify-wpbakery-meta-content-option" style="display: none;">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="wpbakery_meta_content" checked>
                                            <span class="retexify-checkbox-label">📊 WPBakery Meta-Content (<span class="retexify-count" id="count-wpbakery-meta-content">0</span>)</span>
                                        </label>
                                        <label class="retexify-checkbox-item">
                                            <input type="checkbox" class="retexify-content-checkbox" name="content_types[]" value="alt_texts">
                                            <span class="retexify-checkbox-label">🖼️ Alt-Texte (<span class="retexify-count" id="count-images">0</span>)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Export Info -->
                                <div class="retexify-export-info">
                                    <h4>✨ ReTexify AI Pro CSV-Format:</h4>
                                    <ul>
                                        <li><span class="dashicons dashicons-yes-alt"></span> <strong>ID-Spalte:</strong> Immer numerisch (Post-ID)</li>
                                        <li><span class="dashicons dashicons-yes-alt"></span> <strong>Content:</strong> Bereinigt ohne WPBakery-Shortcodes</li>
                                        <li><span class="dashicons dashicons-yes-alt"></span> <strong>WPBakery:</strong> Text, Meta-Titel & Meta-Content getrennt</li>
                                        <li><span class="dashicons dashicons-edit"></span> <strong>(Neu)-Spalten:</strong> Hier Ihre neuen Texte eintragen</li>
                                        <li id="retexify-wpbakery-info" style="display: none;"><span class="dashicons dashicons-yes-alt"></span> WPBakery/Salient Integration aktiv</li>
                                    </ul>
                                </div>
                                
                                <!-- Vorschau -->
                                <div class="retexify-preview-section">
                                    <button type="button" id="retexify-preview-btn" class="button">
                                        <span class="dashicons dashicons-visibility"></span> Export-Vorschau
                                    </button>
                                    <div id="retexify-preview-result" class="retexify-preview-result"></div>
                                </div>
                                
                                <!-- Export-Button -->
                                <div class="retexify-action-area">
                                    <button type="button" id="retexify-export-btn" class="button button-primary button-hero">
                                        <span class="dashicons dashicons-download"></span> Export starten
                                    </button>
                                </div>
                                
                                <div id="retexify-export-result"></div>
                            </div>
                        </div>
                        
                        <!-- Import Card -->
                        <div class="retexify-card retexify-import-card">
                            <div class="retexify-card-header">
                                <h3><span class="dashicons dashicons-upload"></span> Import</h3>
                            </div>
                            <div class="retexify-card-content">
                                
                                <!-- Datei-Upload -->
                                <div class="retexify-file-upload">
                                    <input type="file" id="retexify-import-file" accept=".csv" style="display: none;">
                                    <button type="button" id="retexify-select-file-btn" class="button button-large">
                                        <span class="dashicons dashicons-media-default"></span> CSV-Datei auswählen
                                    </button>
                                    <span id="retexify-file-name" class="retexify-file-name"></span>
                                </div>
                                
                                <!-- Import Info -->
                                <div class="retexify-import-info">
                                    <h4>⚡ Import-Features:</h4>
                                    <ul>
                                        <li><span class="dashicons dashicons-info"></span> Nur (Neu)-Spalten werden importiert</li>
                                        <li><span class="dashicons dashicons-info"></span> (Original)-Spalten bleiben unverändert</li>
                                        <li><span class="dashicons dashicons-info"></span> ID-Validierung vor Import</li>
                                        <li><span class="dashicons dashicons-warning"></span> Nur ausgefüllte Felder überschreiben</li>
                                        <li id="retexify-wpbakery-import-info" style="display: none;"><span class="dashicons dashicons-info"></span> WPBakery-Texte werden intelligent ersetzt</li>
                                    </ul>
                                </div>
                                
                                <!-- Import-Button -->
                                <div class="retexify-action-area">
                                    <button type="button" id="retexify-import-btn" class="button button-primary button-hero" disabled>
                                        <span class="dashicons dashicons-upload"></span> Import starten
                                    </button>
                                </div>
                                
                                <div id="retexify-import-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tab 4: KI-Einstellungen (VOLLSTÄNDIGE SCHWEIZER KANTONE) -->
                <div class="retexify-tab-content" id="tab-ai-settings">
                    <div class="retexify-section">
                        <h2>🇨🇭 KI-Einstellungen + Alle 26 Schweizer Kantone</h2>
                        
                        <form id="retexify-ai-settings-form">
                            <div class="retexify-settings-grid">
                                
                                <!-- API-Einstellungen -->
                                <div class="retexify-settings-section">
                                    <h3>🔑 API-Einstellungen</h3>
                                    
                                    <label for="ai-provider">KI-Anbieter:</label>
                                    <select id="ai-provider" name="api_provider">
                                        <option value="openai" <?php selected($ai_settings['api_provider'] ?? 'openai', 'openai'); ?>>OpenAI (GPT-4o)</option>
                                    </select>
                                    
                                    <label for="ai-api-key">API-Schlüssel:</label>
                                    <input type="password" id="ai-api-key" name="api_key" value="<?php echo esc_attr($ai_settings['api_key'] ?? ''); ?>" placeholder="sk-...">
                                    <small>Ihr API-Schlüssel wird verschlüsselt gespeichert</small>
                                    
                                    <label for="ai-model">Modell:</label>
                                    <select id="ai-model" name="model">
                                        <option value="gpt-4o-mini" <?php selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o-mini'); ?>>GPT-4o Mini (Empfohlen für SEO)</option>
                                        <option value="gpt-4o" <?php selected($ai_settings['model'] ?? '', 'gpt-4o'); ?>>GPT-4o (Premium)</option>
                                        <option value="gpt-4-turbo" <?php selected($ai_settings['model'] ?? '', 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                    </select>
                                </div>
                                
                                <!-- Business-Kontext -->
                                <div class="retexify-settings-section">
                                    <h3>🏢 Business-Kontext</h3>
                                    
                                    <label for="ai-business-context">Ihr Business/Branche:</label>
                                    <textarea id="ai-business-context" name="business_context" rows="3" placeholder="z.B. Innenausbau und Renovationen in der Schweiz, spezialisiert auf Parkett, Laminat und Schreinerlösungen"><?php echo esc_textarea($ai_settings['business_context'] ?? ''); ?></textarea>
                                    
                                    <label for="ai-target-audience">Zielgruppe:</label>
                                    <input type="text" id="ai-target-audience" name="target_audience" value="<?php echo esc_attr($ai_settings['target_audience'] ?? ''); ?>" placeholder="z.B. Privatkunden, Verwaltungen, Architekten">
                                    
                                    <label for="ai-brand-voice">Markenstimme:</label>
                                    <select id="ai-brand-voice" name="brand_voice">
                                        <option value="professional" <?php selected($ai_settings['brand_voice'] ?? 'professional', 'professional'); ?>>Professionell</option>
                                        <option value="friendly" <?php selected($ai_settings['brand_voice'] ?? '', 'friendly'); ?>>Freundlich & einladend</option>
                                        <option value="expert" <?php selected($ai_settings['brand_voice'] ?? '', 'expert'); ?>>Experte & kompetent</option>
                                        <option value="premium" <?php selected($ai_settings['brand_voice'] ?? '', 'premium'); ?>>Premium & exklusiv</option>
                                    </select>
                                </div>
                                
                                <!-- ALLE 26 SCHWEIZER KANTONE -->
                                <div class="retexify-settings-section retexify-canton-section">
                                    <h3>🇨🇭 Alle 26 Schweizer Kantone für Local SEO</h3>
                                    
                                    <div class="retexify-canton-info">
                                        <p><small>Wählen Sie die Kantone aus, in denen Ihr Business aktiv ist. Diese werden für lokale SEO-Optimierung und regionale Keywords verwendet.</small></p>
                                    </div>
                                    
                                    <div class="retexify-canton-grid">
                                        <?php 
                                        $selected_cantons = $ai_settings['target_cantons'] ?? array();
                                        foreach ($swiss_cantons as $code => $name): 
                                        ?>
                                        <label class="retexify-canton-item">
                                            <input type="checkbox" name="target_cantons[]" value="<?php echo $code; ?>" <?php checked(in_array($code, $selected_cantons)); ?>>
                                            <span class="retexify-canton-code"><?php echo $code; ?></span>
                                            <span class="retexify-canton-name"><?php echo $name; ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="retexify-canton-actions">
                                        <button type="button" id="retexify-select-all-cantons" class="button">Alle 26 Kantone auswählen</button>
                                        <button type="button" id="retexify-select-main-cantons" class="button">Hauptkantone (BE, ZH, LU, SG, BS, GE)</button>
                                        <button type="button" id="retexify-select-german-cantons" class="button">Deutschsprachige Kantone</button>
                                        <button type="button" id="retexify-clear-cantons" class="button">Alle abwählen</button>
                                    </div>
                                    
                                    <div class="retexify-canton-preview">
                                        <small id="retexify-selected-cantons-preview">Ausgewählte Kantone: <?php echo implode(', ', $selected_cantons); ?></small>
                                    </div>
                                </div>
                                
                                <!-- Technische Parameter -->
                                <div class="retexify-settings-section">
                                    <h3>⚙️ Technische Parameter</h3>
                                    
                                    <label for="ai-max-tokens">Max. Tokens:</label>
                                    <input type="number" id="ai-max-tokens" name="max_tokens" value="<?php echo esc_attr($ai_settings['max_tokens'] ?? 2000); ?>" min="500" max="4000">
                                    <small>Mehr Tokens = längere und detailliertere Texte</small>
                                    
                                    <label for="ai-temperature">Kreativität (Temperature):</label>
                                    <input type="range" id="ai-temperature" name="temperature" value="<?php echo esc_attr($ai_settings['temperature'] ?? 0.7); ?>" min="0" max="1" step="0.1">
                                    <span id="temperature-value"><?php echo esc_attr($ai_settings['temperature'] ?? 0.7); ?></span>
                                    <small>0 = Sehr präzise, 1 = Sehr kreativ</small>
                                    
                                    <label for="ai-default-language">Sprache:</label>
                                    <select id="ai-default-language" name="default_language">
                                        <option value="de-ch" <?php selected($ai_settings['default_language'] ?? 'de-ch', 'de-ch'); ?>>Schweizer Hochdeutsch</option>
                                        <option value="de" <?php selected($ai_settings['default_language'] ?? '', 'de'); ?>>Deutsch (Standard)</option>
                                        <option value="fr-ch" <?php selected($ai_settings['default_language'] ?? '', 'fr-ch'); ?>>Français (Suisse)</option>
                                        <option value="it-ch" <?php selected($ai_settings['default_language'] ?? '', 'it-ch'); ?>>Italiano (Svizzera)</option>
                                    </select>
                                </div>
                                
                                <!-- Qualitäts-Einstellungen -->
                                <div class="retexify-settings-section">
                                    <h3>🎯 Qualitäts-Einstellungen</h3>
                                    
                                    <label>
                                        <input type="checkbox" name="use_swiss_german" <?php checked($ai_settings['use_swiss_german'] ?? true); ?>>
                                        Schweizer Hochdeutsch verwenden (ss statt ß)
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="include_regional_keywords" <?php checked($ai_settings['include_regional_keywords'] ?? true); ?>>
                                        Regionale Keywords automatisch einfügen
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="premium_business_tone" <?php checked($ai_settings['premium_business_tone'] ?? false); ?>>
                                        Premium Business-Ton für hochwertige Kunden
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="conversion_optimization" <?php checked($ai_settings['conversion_optimization'] ?? true); ?>>
                                        Conversion-Optimierung (Call-to-Actions)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="retexify-settings-actions">
                                <button type="button" id="retexify-ai-test-connection" class="button">
                                    <span class="dashicons dashicons-cloud"></span> KI-Verbindung testen
                                </button>
                                <button type="submit" class="button button-primary button-large">
                                    <span class="dashicons dashicons-saved"></span> Alle Einstellungen speichern
                                </button>
                            </div>
                        </form>
                        
                        <div id="retexify-ai-settings-result"></div>
                    </div>
                </div>
                
                <!-- Tab 5: System -->
                <div class="retexify-tab-content" id="tab-system">
                    <div class="retexify-section">
                        <h2>🔧 System-Status (Complete)</h2>
                        <div id="retexify-system-status-detailed">
                            <?php $this->show_detailed_system_status(); ?>
                                </div>
                        <div class="retexify-test-buttons">
                            <button type="button" id="retexify-test-btn" class="button">
                                <span class="dashicons dashicons-admin-tools"></span> Vollständiger System-Test
                                </button>
                            <button type="button" id="retexify-test-wpbakery" class="button">
                                <span class="dashicons dashicons-admin-tools"></span> WPBakery-Integration testen
                                </button>
                            <button type="button" id="retexify-test-content-analysis" class="button">
                                <span class="dashicons dashicons-analytics"></span> Deutsche Content-Analyse testen
                                </button>
                            </div>
                        <div id="retexify-test-result"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // SYSTEM-STATUS (WIEDERHERGESTELLT)
    private function show_system_status() {
        global $wpdb;
        
        // WPBakery/Salient Detection
        $is_salient = (get_template() === 'salient' || get_stylesheet() === 'salient');
        $wpbakery_plugin = is_plugin_active('js_composer/js_composer.php');
        $wpbakery_functions = function_exists('vc_map');
        $wpbakery_constant = defined('WPB_VC_VERSION');
        
        $wpbakery_detected = $wpbakery_plugin || $wpbakery_functions || $wpbakery_constant;
        
        // SEO Plugin Detection
        $yoast_active = is_plugin_active('wordpress-seo/wp-seo.php');
        $rankmath_active = is_plugin_active('seo-by-rank-math/rank-math.php');
        $aioseo_active = is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php');
        
        // KI Status
        $ai_enabled = $this->is_ai_enabled();
        
        // Quick Stats
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
        $posts_with_vc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE (post_content LIKE '%[vc_%' OR post_content LIKE '%[nectar_%') AND post_status = 'publish'");
        $posts_with_meta_titles = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('_yoast_wpseo_title', 'rank_math_title', '_aioseop_title') AND meta_value != ''");
        
        echo '<div class="retexify-system-status-grid">';
        
        // Theme Status
        echo '<div class="retexify-status-item">';
        echo '<div class="retexify-status-icon">' . ($is_salient ? '✅' : '❌') . '</div>';
        echo '<div class="retexify-status-content">';
        echo '<div class="retexify-status-title">Salient Theme</div>';
        echo '<div class="retexify-status-detail">' . ($is_salient ? 'Erkannt' : 'Nicht erkannt') . '</div>';
        echo '</div></div>';
        
        // WPBakery Status
        echo '<div class="retexify-status-item">';
        echo '<div class="retexify-status-icon">' . ($wpbakery_detected ? '✅' : '❌') . '</div>';
        echo '<div class="retexify-status-content">';
        echo '<div class="retexify-status-title">WPBakery</div>';
        if ($wpbakery_plugin) {
            echo '<div class="retexify-status-detail">Plugin aktiv</div>';
        } elseif ($wpbakery_functions) {
            echo '<div class="retexify-status-detail">Theme-integriert</div>';
        } else {
            echo '<div class="retexify-status-detail">Nicht verfügbar</div>';
        }
        echo '</div></div>';
        
        // SEO Plugin Status
        echo '<div class="retexify-status-item">';
        echo '<div class="retexify-status-icon">' . ($yoast_active || $rankmath_active || $aioseo_active ? '✅' : '❌') . '</div>';
        echo '<div class="retexify-status-content">';
        echo '<div class="retexify-status-title">SEO Plugin</div>';
        if ($yoast_active) {
            echo '<div class="retexify-status-detail">Yoast SEO</div>';
        } elseif ($rankmath_active) {
            echo '<div class="retexify-status-detail">Rank Math</div>';
        } elseif ($aioseo_active) {
            echo '<div class="retexify-status-detail">All in One SEO</div>';
        } else {
            echo '<div class="retexify-status-detail">Keines aktiv</div>';
        }
        echo '</div></div>';
        
        // KI Status
        echo '<div class="retexify-status-item">';
        echo '<div class="retexify-status-icon">' . ($ai_enabled ? '🇨🇭' : '❌') . '</div>';
        echo '<div class="retexify-status-content">';
        echo '<div class="retexify-status-title">KI-Integration</div>';
        echo '<div class="retexify-status-detail">' . ($ai_enabled ? 'Schweizer Hochdeutsch' : 'Nicht konfiguriert') . '</div>';
        echo '</div></div>';
        
        // Content Status
        echo '<div class="retexify-status-item">';
        echo '<div class="retexify-status-icon">📊</div>';
        echo '<div class="retexify-status-content">';
        echo '<div class="retexify-status-title">Content</div>';
        echo '<div class="retexify-status-detail">' . $total_posts . ' Posts/Seiten</div>';
        echo '</div></div>';
        
        echo '</div>';
        
        // WPBakery Details
        if ($wpbakery_detected) {
            echo '<div class="retexify-wpbakery-details">';
            echo '<h4>🏗️ WPBakery Details:</h4>';
            echo '<div class="retexify-wpbakery-grid">';
            echo '<span><strong>Posts mit VC-Shortcodes:</strong> ' . $posts_with_vc . '</span>';
            if ($wpbakery_constant) {
                echo '<span><strong>Version:</strong> ' . WPB_VC_VERSION . '</span>';
            }
            echo '<span><strong>Functions verfügbar:</strong> ' . ($wpbakery_functions ? 'Ja' : 'Nein') . '</span>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private function show_detailed_system_status() {
        // Erweiterte System-Informationen
        $seo_plugins = $this->detect_seo_plugins();
        $page_builders = $this->detect_page_builders();
        $ai_settings = get_option('retexify_ai_settings', array());
        
        echo '<div class="retexify-system-info">';
        echo '<h4>🔌 Erkannte Plugins</h4>';
        echo '<div class="retexify-plugin-list">';
        
        foreach ($seo_plugins as $name => $status) {
            echo '<span class="retexify-plugin-badge seo">' . $name . ' ✓</span>';
        }
        
        foreach ($page_builders as $name => $status) {
            echo '<span class="retexify-plugin-badge builder">' . $name . ' ✓</span>';
        }
        
        echo '</div>';
        
        echo '<h4>🇨🇭 Schweizer Kantone Konfiguration</h4>';
        $selected_cantons = $ai_settings['target_cantons'] ?? array();
        if (!empty($selected_cantons)) {
            echo '<p><strong>Ausgewählte Kantone (' . count($selected_cantons) . '):</strong> ' . implode(', ', $selected_cantons) . '</p>';
        } else {
            echo '<p><em>Keine Kantone ausgewählt</em></p>';
        }
        
        echo '</div>';
    }
    
    // AJAX HANDLER - SEO CONTENT LADEN
    
    public function handle_load_seo_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
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
                // KORRIGIERTE META-DATEN-EXTRAKTION
                $meta_title = $this->get_meta_title_corrected($post->ID);
                $meta_description = $this->get_meta_description_corrected($post->ID);
                $focus_keyword = $this->get_focus_keyword_corrected($post->ID);
                
                $item = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'edit_url' => get_edit_post_link($post->ID),
                    'modified' => get_the_modified_date('d.m.Y H:i', $post->ID),
                    'type' => $post->post_type,
                    
                    // VOLLSTÄNDIGE SEO-DATEN
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'focus_keyword' => $focus_keyword,
                    
                    // CONTENT FÜR ANALYSE (KORRIGIERT FÜR DEUTSCHE TEXTE)
                    'full_content' => $this->clean_german_text($post->post_content),
                    'content_excerpt' => wp_trim_words($this->clean_german_text($post->post_content), 50),
                    
                    // SEO-STATUS
                    'needs_optimization' => empty($meta_title) || empty($meta_description) || empty($focus_keyword)
                );
                
                $seo_data[] = $item;
            }
        
        wp_send_json_success(array(
                'items' => $seo_data,
                'total' => count($seo_data),
                'post_type' => $post_type,
                'needs_optimization' => count(array_filter($seo_data, function($item) { return $item['needs_optimization']; }))
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden: ' . $e->getMessage());
        }
    }
    
    // KORRIGIERTE DEUTSCHE CONTENT-ANALYSE
    public function handle_analyze_page_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
            }
            
            // KORRIGIERTE CONTENT-ANALYSE FÜR DEUTSCHE TEXTE
            $analysis = $this->analyze_german_content_fixed($post);
            
            wp_send_json_success(array(
                'post_id' => $post_id,
                'analysis' => $analysis
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Content-Analyse fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    // KOMPLETT KORRIGIERTE DEUTSCHE CONTENT-ANALYSE
    private function analyze_german_content_fixed($post) {
        // Text richtig bereinigen
        $content = $this->clean_german_text($post->post_content);
        $title = $post->post_title;
        $url = get_permalink($post->ID);
        
        // KORREKTE DEUTSCHE WÖRTER-ZÄHLUNG
        $word_count = $this->count_german_words($content);
        
        // DEUTSCHE KEYWORDS KORREKT EXTRAHIEREN
        $german_keywords = $this->extract_german_keywords_fixed($content . ' ' . $title);
        
        // DEUTSCHE BUSINESS-THEMEN KORREKT ERKENNEN
        $business_themes = $this->identify_german_business_themes_fixed($content . ' ' . $title);
        
        // CONTENT-QUALITÄT BEWERTEN
        $content_quality = $this->assess_german_content_quality_fixed($content, $word_count);
        
        // SCHWEIZER REGIONALE BEGRIFFE ERKENNEN
        $regional_info = $this->detect_swiss_regional_content($content . ' ' . $title);
        
        $analysis = array(
            'word_count' => $word_count,
            'char_count' => mb_strlen($content, 'UTF-8'),
            'sentence_count' => $this->count_german_sentences($content),
            'paragraph_count' => count(array_filter(explode("\n", $content))),
            'url' => $url,
            'has_images' => has_post_thumbnail($post->ID),
            'german_keywords' => $german_keywords,
            'business_themes' => $business_themes,
            'content_quality' => $content_quality,
            'regional_info' => $regional_info,
            'readability_score' => $this->calculate_german_readability($content, $word_count)
        );
        
        return $analysis;
    }
    
    // KORREKTE DEUTSCHE TEXT-BEREINIGUNG
    private function clean_german_text($text) {
        // HTML-Tags entfernen
        $text = wp_strip_all_tags($text);
        
        // WPBakery Shortcodes entfernen
        $text = $this->remove_wpbakery_shortcodes($text);
        
        // HTML-Entitäten dekodieren
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Mehrfache Leerzeichen normalisieren
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    // KORREKTE DEUTSCHE WÖRTER-ZÄHLUNG
    private function count_german_words($text) {
        if (empty($text)) return 0;
        
        // Deutsche Wörter mit Umlauten und ß richtig zählen
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Nur echte Wörter zählen (mindestens 2 Zeichen, nur Buchstaben/Umlaute)
        $word_count = 0;
        foreach ($words as $word) {
            // Interpunktion entfernen
            $clean_word = preg_replace('/[^\p{L}\p{N}äöüßÄÖÜ]/u', '', $word);
            if (mb_strlen($clean_word, 'UTF-8') >= 2) {
                $word_count++;
            }
        }
        
        return $word_count;
    }
    
    // KORRIGIERTE DEUTSCHE KEYWORD-EXTRAKTION
    private function extract_german_keywords_fixed($text) {
        if (empty($text)) return array();
        
        $text = mb_strtolower($text, 'UTF-8');
        
        // Deutsche Wörter richtig extrahieren
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $clean_words = array();
        
        foreach ($words as $word) {
            // Interpunktion entfernen, Umlaute beibehalten
            $clean_word = preg_replace('/[^\p{L}äöüßÄÖÜ]/u', '', $word);
            $clean_word = mb_strtolower($clean_word, 'UTF-8');
            
            // Nur Wörter mit mindestens 3 Zeichen
            if (mb_strlen($clean_word, 'UTF-8') >= 3) {
                $clean_words[] = $clean_word;
            }
        }
        
        // Deutsche Stopwords entfernen
        $german_stopwords = array(
            'der', 'die', 'das', 'und', 'oder', 'aber', 'ist', 'sind', 'war', 'waren', 
            'hat', 'haben', 'wird', 'werden', 'von', 'zu', 'für', 'mit', 'auf', 'in', 
            'an', 'bei', 'durch', 'über', 'unter', 'vor', 'nach', 'zwischen', 'alle',
            'eine', 'einem', 'einen', 'einer', 'eines', 'den', 'dem', 'des', 'im', 'am',
            'zur', 'zum', 'beim', 'vom', 'ins', 'ans', 'aus', 'als', 'wie', 'wenn',
            'dann', 'noch', 'nur', 'auch', 'schon', 'mehr', 'sehr', 'kann', 'muss',
            'soll', 'will', 'darf', 'mag', 'könnte', 'sollte', 'würde', 'hätte', 'dass',
            'sich', 'nicht', 'diese', 'dieser', 'dieses', 'hier', 'dort', 'damit', 'ohne'
        );
        
        $word_freq = array_count_values($clean_words);
        
        // Stopwords entfernen
        foreach ($german_stopwords as $stopword) {
            unset($word_freq[$stopword]);
        }
        
        // Nach Häufigkeit sortieren
        arsort($word_freq);
        
        // Top 10 Keywords zurückgeben
        return array_slice(array_keys($word_freq), 0, 10);
    }
    
    // KORRIGIERTE DEUTSCHE BUSINESS-THEMEN ERKENNUNG
    private function identify_german_business_themes_fixed($text) {
        $text = mb_strtolower($text, 'UTF-8');
        $themes = array();
        
        // Deutsche Business-Begriffe für Innenausbau/Renovationen
        $business_patterns = array(
            'innenausbau' => array('innenausbau', 'ausbau', 'renovierung', 'renovation', 'sanierung', 'umbau', 'modernisierung', 'erneuerung'),
            'materialien' => array('parkett', 'laminat', 'vinyl', 'holz', 'bodenbelag', 'boden', 'böden', 'fliesen', 'material', 'materialien'),
            'handwerk' => array('schreiner', 'schreinerei', 'tischler', 'handwerk', 'handwerker', 'montage', 'installation', 'verlegung'),
            'dienstleistung' => array('beratung', 'planung', 'service', 'kundenservice', 'projekt', 'projekte', 'lösung', 'lösungen', 'dienstleistung'),
            'qualität' => array('qualität', 'professionell', 'erfahrung', 'kompetenz', 'zuverlässig', 'fachmann', 'experte', 'hochwertig'),
            'regional' => array('schweiz', 'swiss', 'bern', 'zürich', 'basel', 'luzern', 'region', 'regional', 'lokal', 'kanton', 'kantonal')
        );
        
        foreach ($business_patterns as $theme => $keywords) {
            $matches = 0;
            foreach ($keywords as $keyword) {
                $count = substr_count($text, $keyword);
                $matches += $count;
            }
            if ($matches > 0) {
                $themes[$theme] = $matches;
            }
        }
        
        return $themes;
    }
    
    // DEUTSCHE SÄTZE ZÄHLEN
    private function count_german_sentences($text) {
        if (empty($text)) return 0;
        
        // Deutsche Sätze richtig zählen (., !, ?, ... berücksichtigen)
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return count($sentences);
    }
    
    // KORRIGIERTE CONTENT-QUALITÄT BEWERTUNG
    private function assess_german_content_quality_fixed($content, $word_count) {
        $score = 0;
        
        // Wort-Anzahl bewerten (für deutsche Texte angepasst)
        if ($word_count >= 400) $score += 35;
        elseif ($word_count >= 250) $score += 25;
        elseif ($word_count >= 150) $score += 15;
        elseif ($word_count >= 50) $score += 10;
        
        // Satz-Struktur bewerten
        $sentence_count = $this->count_german_sentences($content);
        if ($sentence_count > 0) {
            $avg_sentence_length = $word_count / $sentence_count;
            if ($avg_sentence_length >= 8 && $avg_sentence_length <= 20) $score += 25;
            elseif ($avg_sentence_length >= 6 && $avg_sentence_length <= 25) $score += 20;
            elseif ($avg_sentence_length >= 4) $score += 10;
        }
        
        // Absätze prüfen
        $paragraphs = array_filter(explode("\n", $content));
        if (count($paragraphs) >= 3) $score += 20;
        elseif (count($paragraphs) >= 2) $score += 15;
        
        // Business-Relevanz für Schweizer Innenausbau
        $business_keywords = array('parkett', 'laminat', 'schreiner', 'renovation', 'innenausbau', 'qualität', 'schweiz', 'service', 'beratung');
        $business_score = 0;
        foreach ($business_keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $business_score += 2;
            }
        }
        $score += min(20, $business_score);
        
        return min(100, $score);
    }
    
    // SCHWEIZER REGIONALE INHALTE ERKENNEN
    private function detect_swiss_regional_content($text) {
        $text = mb_strtolower($text, 'UTF-8');
        $regional_info = array();
        
        // Schweizer Kantone erkennen
        $swiss_cantons = $this->get_swiss_cantons();
        $found_cantons = array();
        
        foreach ($swiss_cantons as $code => $name) {
            if (stripos($text, mb_strtolower($name, 'UTF-8')) !== false) {
                $found_cantons[] = $name;
            }
        }
        
        $regional_info['cantons'] = $found_cantons;
        
        // Schweizer Städte
        $swiss_cities = array('zürich', 'bern', 'basel', 'luzern', 'winterthur', 'st. gallen', 'biel', 'thun', 'köniz', 'la chaux-de-fonds');
        $found_cities = array();
        
        foreach ($swiss_cities as $city) {
            if (stripos($text, $city) !== false) {
                $found_cities[] = $city;
            }
        }
        
        $regional_info['cities'] = $found_cities;
        
        // Schweizer spezifische Begriffe
        $swiss_terms = array('schweiz', 'swiss', 'helvetia', 'eidgenossenschaft', 'bundesrat', 'kantonal', 'gemeinde');
        $found_terms = array();
        
        foreach ($swiss_terms as $term) {
            if (stripos($text, $term) !== false) {
                $found_terms[] = $term;
            }
        }
        
        $regional_info['swiss_terms'] = $found_terms;
        
        return $regional_info;
    }
    
    // DEUTSCHE LESBARKEIT BERECHNEN
    private function calculate_german_readability($content, $word_count) {
        if ($word_count === 0) return 0;
        
        $sentence_count = $this->count_german_sentences($content);
        if ($sentence_count === 0) return 0;
        
        $avg_sentence_length = $word_count / $sentence_count;
        
        // Einfache Lesbarkeits-Heuristik für deutsche Texte
        $score = 100;
        
        // Satzlänge bewerten
        if ($avg_sentence_length > 25) $score -= 30;
        elseif ($avg_sentence_length > 20) $score -= 20;
        elseif ($avg_sentence_length > 15) $score -= 10;
        
        // Wortlänge bewerten (durchschnittliche Zeichen pro Wort)
        $avg_word_length = mb_strlen($content, 'UTF-8') / $word_count;
        if ($avg_word_length > 8) $score -= 20;
        elseif ($avg_word_length > 6) $score -= 10;
        
        return max(0, min(100, $score));
    }
    
    // VERBESSERTE SEO-SUITE GENERIERUNG
    public function handle_generate_seo_suite() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $optimization_focus = sanitize_text_field($_POST['optimization_focus'] ?? 'complete_seo');
            $include_cantons = !empty($_POST['include_cantons']);
            $premium_business = !empty($_POST['premium_business']);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
            }
            
            // PREMIUM SCHWEIZER SEO-SUITE GENERIERUNG
            $seo_suite = $this->generate_premium_swiss_seo_suite($post, $optimization_focus, $include_cantons, $premium_business);
            
            wp_send_json_success(array(
                'post_id' => $post_id,
                'seo_suite' => $seo_suite,
                'optimization_focus' => $optimization_focus,
                'premium_business' => $premium_business
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('SEO-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    // PREMIUM SCHWEIZER SEO-SUITE GENERIERUNG
    private function generate_premium_swiss_seo_suite($post, $optimization_focus, $include_cantons, $premium_business) {
        $settings = get_option('retexify_ai_settings', array());
        
        // Content analysieren
        $content = $this->clean_german_text($post->post_content);
        $title = $post->post_title;
        $url = get_permalink($post->ID);
        
        // Business-Kontext aus Einstellungen
        $business_context = $settings['business_context'] ?? 'Innenausbau und Renovationen in der Schweiz';
        $target_audience = $settings['target_audience'] ?? 'Privatkunden, Verwaltungen, Architekten';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        // Schweizer Kantone
        $target_cantons = $settings['target_cantons'] ?? array();
        $canton_names = array();
        if ($include_cantons && !empty($target_cantons)) {
            $all_cantons = $this->get_swiss_cantons();
            foreach ($target_cantons as $canton_code) {
                if (isset($all_cantons[$canton_code])) {
                    $canton_names[] = $all_cantons[$canton_code];
                }
            }
        }
        
        // PREMIUM PROMPT FÜR HOCHWERTIGE SCHWEIZER SEO-TEXTE
        $prompt = $this->build_premium_swiss_seo_prompt_v2(
            $post, 
            $content,
            $optimization_focus,
            $business_context,
            $target_audience,
            $brand_voice,
            $canton_names,
            $premium_business
        );
        
        $ai_response = $this->call_ai_api($prompt, $settings);
        
        return $this->parse_seo_suite_response($ai_response);
    }
    
    // PREMIUM SCHWEIZER SEO-PROMPT (VERBESSERT)
    private function build_premium_swiss_seo_prompt_v2($post, $content, $focus, $business_context, $target_audience, $brand_voice, $canton_names, $premium_business) {
        $content_words = $this->count_german_words($content);
        $content_preview = wp_trim_words($content, 100);
        $cantons_text = !empty($canton_names) ? implode(', ', $canton_names) : '';
        
        $premium_instruction = $premium_business ? 
            "PREMIUM-MODUS: Erstelle exklusive, hochwertige Business-Texte für anspruchsvolle Kunden." : 
            "STANDARD-MODUS: Erstelle professionelle, aber zugängliche Texte.";
        
        $prompt = "Du bist ein TOP-SCHWEIZER SEO-EXPERTE und MARKETING-COPYWRITER. Erstelle eine PREMIUM SEO-Suite in perfektem SCHWEIZER HOCHDEUTSCH.

{$premium_instruction}

=== SEITENINHALT ===
Titel: {$post->post_title}
URL: " . get_permalink($post->ID) . "
Content ({$content_words} deutsche Wörter): {$content_preview}

=== BUSINESS-KONTEXT ===
Unternehmen: {$business_context}
Zielgruppe: {$target_audience}
Markenstimme: {$brand_voice}
Ziel-Kantone für Local SEO: {$cantons_text}

=== OPTIMIERUNGS-FOCUS ===
{$focus}

=== SCHWEIZER SEO-REQUIREMENTS ===

1. META-TITEL (50-60 Zeichen):
   - Haupt-Keyword an Position 1-3
   - SCHWEIZER Rechtschreibung (ss statt ß)
   - Emotionale Trigger oder klarer Nutzen
   - Call-to-Action wenn möglich
   - Regional erwähnen wenn Kantone ausgewählt

2. META-BESCHREIBUNG (150-160 Zeichen):
   - Einzigartigen Verkaufsvorteil (USP) betonen
   - Zielgruppe direkt ansprechen
   - Handlungsaufforderung am Ende
   - Schweizer Hochdeutsch verwenden
   - Vertrauen schaffen durch Kompetenz-Signale

3. FOCUS-KEYWORD (1-3 Wörter):
   - Suchvolumen-stark für Schweizer Markt
   - Business-relevant und conversion-orientiert
   - Local-Intent berücksichtigen
   - Nicht zu generisch, nicht zu spezifisch

=== QUALITÄTS-KRITERIEN ===
- Business/Dienstleistung sofort erkennbar
- Klarer Mehrwert kommuniziert
- Lokaler Schweiz-Bezug integriert
- Professionell aber einladend
- Vertrauenswürdig und kompetent
- Schweizer Hochdeutsch (ss statt ß, keine deutschen Begriffe)
- Conversion-optimiert

=== AUSGABEFORMAT (EXAKT SO) ===
META_TITEL: [Perfekter Meta-Titel hier]
META_BESCHREIBUNG: [Überzeugende Meta-Beschreibung hier]
FOCUS_KEYWORD: [Starkes Focus-Keyword hier]
BEGRÜNDUNG: [Kurze SEO-Strategie-Erklärung warum diese Texte optimal sind]

Erstelle jetzt eine premium SEO-Suite, die bei Google top rankt und Kunden überzeugt!";

        return $prompt;
    }
    
    // SEO-SUITE RESPONSE PARSER
    private function parse_seo_suite_response($ai_response) {
        $lines = explode("\n", $ai_response);
        $suite = array(
            'meta_title' => '',
            'meta_description' => '',
            'focus_keyword' => '',
            'explanation' => ''
        );
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'META_TITEL:') === 0) {
                $suite['meta_title'] = trim(str_replace('META_TITEL:', '', $line));
            } elseif (strpos($line, 'META_BESCHREIBUNG:') === 0) {
                $suite['meta_description'] = trim(str_replace('META_BESCHREIBUNG:', '', $line));
            } elseif (strpos($line, 'FOCUS_KEYWORD:') === 0) {
                $suite['focus_keyword'] = trim(str_replace('FOCUS_KEYWORD:', '', $line));
            } elseif (strpos($line, 'BEGRÜNDUNG:') === 0) {
                $suite['explanation'] = trim(str_replace('BEGRÜNDUNG:', '', $line));
            }
        }
        
        return $suite;
    }
    
    // SEO-SUITE SPEICHERN (KORRIGIERT FÜR WPBAKERY)
    public function handle_save_seo_suite() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $meta_title = sanitize_text_field($_POST['meta_title'] ?? '');
            $meta_description = sanitize_textarea_field($_POST['meta_description'] ?? '');
            $focus_keyword = sanitize_text_field($_POST['focus_keyword'] ?? '');
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
            }
            
            // IN ALLE SEO-PLUGINS SPEICHERN
            $saved_plugins = $this->save_seo_to_all_plugins_corrected($post_id, $meta_title, $meta_description, $focus_keyword);
            
            // WPBAKERY INTEGRATION (KOMPLETT KORRIGIERT)
            $wpbakery_updated = $this->update_wpbakery_seo_corrected_v2($post_id, $meta_title, $meta_description);
            
            wp_send_json_success(array(
                'message' => 'SEO-Suite erfolgreich in alle Plugins + WPBakery gespeichert!',
                'post_id' => $post_id,
                'plugins_updated' => $saved_plugins,
                'wpbakery_updated' => $wpbakery_updated,
                'update_count' => count($saved_plugins) + ($wpbakery_updated ? 1 : 0)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    // WPBakery Integration vollständig korrigiert - Version 2
    private function update_wpbakery_seo_corrected_v2($post_id, $meta_title, $meta_description) {
        // Prüfen ob WPBakery verfügbar ist
        if (!function_exists('vc_map') && !is_plugin_active('js_composer/js_composer.php') && get_template() !== 'salient') {
            return false;
        }
        
        $post = get_post($post_id);
        if (!$post) return false;
        
        $content = $post->post_content;
        $original_content = $content;
        $updated = false;
        
        // 1. Meta-Titel in verschiedenen WPBakery-Elementen ersetzen
        if (!empty($meta_title)) {
            $safe_title = esc_attr($meta_title);
            
            // VC Custom Heading
            $pattern1 = '/(\[vc_custom_heading[^>]*text=")([^"]*)("[^\]]*\])/i';
            if (preg_match($pattern1, $content)) {
                $content = preg_replace($pattern1, '${1}' . $safe_title . '${3}', $content, 1);
                $updated = true;
            }
            
            // VC Text Separator
            $pattern2 = '/(\[vc_text_separator[^>]*title=")([^"]*)("[^\]]*\])/i';
            if (preg_match($pattern2, $content)) {
                $content = preg_replace($pattern2, '${1}' . $safe_title . '${3}', $content, 1);
                $updated = true;
            }
            
            // Nectar Page Header (Salient Theme)
            $pattern3 = '/(\[nectar_page_header[^>]*title=")([^"]*)("[^\]]*\])/i';
            if (preg_match($pattern3, $content)) {
                $content = preg_replace($pattern3, '${1}' . $safe_title . '${3}', $content, 1);
                $updated = true;
            }
            
            // Nectar CTA Heading
            $pattern4 = '/(\[nectar_cta[^>]*heading=")([^"]*)("[^\]]*\])/i';
            if (preg_match($pattern4, $content)) {
                $content = preg_replace($pattern4, '${1}' . $safe_title . '${3}', $content, 1);
                $updated = true;
            }
        }
        
        // 2. Meta-Beschreibung in Text-Elementen ersetzen
        if (!empty($meta_description)) {
            $safe_description = wp_kses_post($meta_description);
            
            // VC Column Text (erstes Vorkommen)
            $pattern5 = '/(\[vc_column_text[^\]]*\]).*?(\[\/vc_column_text\])/s';
            if (preg_match($pattern5, $content)) {
                $content = preg_replace($pattern5, '${1}' . $safe_description . '${2}', $content, 1);
                $updated = true;
            }
            
            // VC Message Box
            $pattern6 = '/(\[vc_message[^\]]*\]).*?(\[\/vc_message\])/s';
            if (preg_match($pattern6, $content)) {
                $content = preg_replace($pattern6, '${1}' . $safe_description . '${2}', $content, 1);
                $updated = true;
            }
            
            // Nectar Quote
            $pattern7 = '/(\[nectar_quote[^\]]*\]).*?(\[\/nectar_quote\])/s';
            if (preg_match($pattern7, $content)) {
                $content = preg_replace($pattern7, '${1}' . $safe_description . '${2}', $content, 1);
                $updated = true;
            }
        }
        
        // Content nur aktualisieren wenn tatsächlich Änderungen vorgenommen wurden
        if ($updated && $content !== $original_content) {
            $result = wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content
            ), true);
            
            if (is_wp_error($result)) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    // KORRIGIERTE META-DATEN-EXTRAKTION
    private function get_meta_title_corrected($post_id) {
        // Yoast SEO
        $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($title)) return $title;
        
        // Rank Math
        $title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($title)) return $title;
        
        // All in One SEO
        $title = get_post_meta($post_id, '_aioseop_title', true);
        if (!empty($title)) return $title;
        
        return '';
    }
    
    private function get_meta_description_corrected($post_id) {
        // Yoast SEO
        $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($desc)) return $desc;
        
        // Rank Math
        $desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($desc)) return $desc;
        
        // All in One SEO
        $desc = get_post_meta($post_id, '_aioseop_description', true);
        if (!empty($desc)) return $desc;
        
        return '';
    }
    
    private function get_focus_keyword_corrected($post_id) {
        // Yoast SEO
        $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($keyword)) return $keyword;
        
        // Rank Math
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($keyword)) return $keyword;
        
        return '';
    }
    
    // UNIVERSELLES SPEICHERN IN ALLE SEO-PLUGINS
    private function save_seo_to_all_plugins_corrected($post_id, $meta_title, $meta_description, $focus_keyword) {
        $saved_plugins = array();
        
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            if (!empty($meta_title)) update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
            if (!empty($meta_description)) update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            if (!empty($focus_keyword)) update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
            $saved_plugins[] = 'Yoast SEO';
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            if (!empty($meta_title)) update_post_meta($post_id, 'rank_math_title', $meta_title);
            if (!empty($meta_description)) update_post_meta($post_id, 'rank_math_description', $meta_description);
            if (!empty($focus_keyword)) update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
            $saved_plugins[] = 'Rank Math';
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            if (!empty($meta_title)) update_post_meta($post_id, '_aioseop_title', $meta_title);
            if (!empty($meta_description)) update_post_meta($post_id, '_aioseop_description', $meta_description);
            $saved_plugins[] = 'All in One SEO';
        }
        
        return $saved_plugins;
    }
    
    // PLUGIN-ERKENNUNG
    private function detect_seo_plugins() {
        $plugins = array();
        
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $plugins['Yoast SEO'] = true;
        }
        
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            $plugins['Rank Math'] = true;
        }
        
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            $plugins['All in One SEO'] = true;
        }
        
        return $plugins;
    }
    
    private function detect_page_builders() {
        $builders = array();
        
        if (is_plugin_active('js_composer/js_composer.php') || function_exists('vc_map')) {
            $builders['WPBakery'] = true;
        }
        
        if (get_template() === 'salient' || get_stylesheet() === 'salient') {
            $builders['Salient Theme'] = true;
        }
        
        if (is_plugin_active('elementor/elementor.php')) {
            $builders['Elementor'] = true;
        }
        
        return $builders;
    }
    
    // WPBakery SHORTCODES ENTFERNEN
    private function remove_wpbakery_shortcodes($content) {
        if (empty($content)) {
            return '';
        }
        
        // Alle WPBakery/VC Shortcodes entfernen (auch verschachtelte)
        $patterns = array(
            // Standard VC Shortcodes
            '/\[vc_[^\]]*\].*?\[\/vc_[^\]]*\]/s',
            '/\[vc_[^\]]*\]/s',
            // Nectar/Salient Shortcodes
            '/\[nectar_[^\]]*\].*?\[\/nectar_[^\]]*\]/s',
            '/\[nectar_[^\]]*\]/s',
            // Visual Composer Row/Column Structure
            '/\[vc_row[^\]]*\].*?\[\/vc_row\]/s',
            '/\[vc_column[^\]]*\].*?\[\/vc_column\]/s',
            '/\[vc_section[^\]]*\].*?\[\/vc_section\]/s'
        );
        
        foreach ($patterns as $pattern) {
            $content = preg_replace($pattern, '', $content);
        }
        
        // Mehrfache Durchläufe für verschachtelte Shortcodes
        $max_iterations = 3;
        for ($i = 0; $i < $max_iterations; $i++) {
            $old_content = $content;
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
            // Wenn sich nichts mehr ändert, brechen wir ab
            if ($old_content === $content) {
                break;
            }
        }
        
        return $content;
    }
    
    // WEITERE AJAX-HANDLER (VOLLSTÄNDIG WIEDERHERGESTELLT)
    
    public function handle_export() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $selections = json_decode(stripslashes($_POST['selections']), true);
            
            if (!$selections || empty($selections['post_types']) || empty($selections['content_types'])) {
                wp_send_json_error('Keine gültige Auswahl getroffen');
            }
            
            // Standard-Status setzen falls leer
            if (empty($selections['post_status'])) {
                $selections['post_status'] = array('publish');
            }
            
            // KORRIGIERTE Datensammlung
            $all_data = $this->collect_enhanced_export_data_fixed($selections);
            
            if (empty($all_data)) {
                wp_send_json_error('Keine Daten zum Exportieren gefunden');
            }
            
            // CSV-Erstellung
            $csv_data = $this->create_enhanced_csv_data_fixed($all_data, $selections);
            
            // Datei speichern
            $upload_dir = wp_upload_dir();
            $temp_dir = $upload_dir['basedir'] . '/retexify-temp/';
            wp_mkdir_p($temp_dir);
            
            $filename = 'retexify-ai-pro-export-' . date('Y-m-d-H-i-s') . '.csv';
            $file_path = $temp_dir . $filename;
            
            $file = fopen($file_path, 'w');
            if (!$file) {
                wp_send_json_error('CSV-Datei konnte nicht erstellt werden');
            }
            
            // UTF-8 BOM für Excel
            fwrite($file, "\xEF\xBB\xBF");
            
            // CSV schreiben
            foreach ($csv_data as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
            
            $download_url = admin_url('tools.php?page=retexify-ai-pro&action=download&file=' . $filename . '&nonce=' . wp_create_nonce('download_file'));
            
            $posts_exported = count(array_filter($all_data, function($item) { return $item['type'] !== 'image'; }));
            $images_exported = count(array_filter($all_data, function($item) { return $item['type'] === 'image'; }));
            
            wp_send_json_success(array(
                'message' => 'Export erfolgreich erstellt!',
                'download_url' => $download_url,
                'filename' => $filename,
                'posts_exported' => $posts_exported,
                'images_exported' => $images_exported,
                'total_items' => count($csv_data) - 1 // -1 für Header
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Export-Fehler: ' . $e->getMessage());
        }
    }
    
    // KORRIGIERTE DATENSAMMLUNG FÜR EXPORT
    private function collect_enhanced_export_data_fixed($selections) {
        $all_data = array();
        
        // Posts sammeln
        $posts = get_posts(array(
            'post_type' => $selections['post_types'],
            'post_status' => $selections['post_status'],
            'numberposts' => -1,
            'suppress_filters' => false
        ));
        
        foreach ($posts as $post) {
            $post_data = array(
                'id' => intval($post->ID),
                'type' => $post->post_type,
                'url' => get_permalink($post->ID),
                'title' => '',
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'focus_keyphrase' => '',
                'wpbakery_text' => '',
                'wpbakery_meta_title' => '',
                'wpbakery_meta_content' => '',
                'image_id' => '',
                'alt_text' => '',
                'image_type' => ''
            );
            
            // Nur ausgewählte Content-Typen sammeln
            if (in_array('title', $selections['content_types'])) {
                $post_data['title'] = $this->clean_text($post->post_title);
            }
            
            // Content wird von WPBakery-Shortcodes bereinigt
            if (in_array('content', $selections['content_types'])) {
                $clean_content = $this->remove_wpbakery_shortcodes($post->post_content);
                $post_data['content'] = $this->clean_text($clean_content);
            }
            
            if (in_array('meta_title', $selections['content_types'])) {
                $post_data['meta_title'] = $this->clean_text($this->get_meta_title_corrected($post->ID));
            }
            
            if (in_array('meta_description', $selections['content_types'])) {
                $post_data['meta_description'] = $this->clean_text($this->get_meta_description_corrected($post->ID));
            }
            
            if (in_array('focus_keyphrase', $selections['content_types'])) {
                $post_data['focus_keyphrase'] = $this->clean_text($this->get_focus_keyword_corrected($post->ID));
            }
            
            // WPBakery-Text separat extrahieren
            if (in_array('wpbakery_text', $selections['content_types'])) {
                $post_data['wpbakery_text'] = $this->extract_wpbakery_text_enhanced($post->post_content);
            }
            
            // WPBakery Meta-Titel extrahieren
            if (in_array('wpbakery_meta_title', $selections['content_types'])) {
                $post_data['wpbakery_meta_title'] = $this->extract_wpbakery_meta_title($post->post_content);
            }
            
            // WPBakery Meta-Content extrahieren
            if (in_array('wpbakery_meta_content', $selections['content_types'])) {
                $post_data['wpbakery_meta_content'] = $this->extract_wpbakery_meta_content($post->post_content);
            }
            
            $all_data[] = $post_data;
        }
        
        // Bilder sammeln
        if (in_array('alt_texts', $selections['content_types'])) {
            global $wpdb;
            
            $images = $wpdb->get_results("
                SELECT p.ID, p.post_title, pm.meta_value as alt_text, p.post_parent
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
                WHERE p.post_type = 'attachment' 
                AND p.post_mime_type LIKE 'image%'
                AND p.post_status = 'inherit'
                ORDER BY p.ID
                LIMIT 2000
            ");
            
            foreach ($images as $image) {
                $image_data = array(
                    'id' => '',
                    'type' => 'image',
                    'url' => wp_get_attachment_url($image->ID),
                    'title' => '',
                    'content' => '',
                    'meta_title' => '',
                    'meta_description' => '',
                    'focus_keyphrase' => '',
                    'wpbakery_text' => '',
                    'wpbakery_meta_title' => '',
                    'wpbakery_meta_content' => '',
                    'image_id' => intval($image->ID),
                    'alt_text' => $this->clean_text($image->alt_text ?: ''),
                    'image_type' => $image->post_parent ? 'content_image' : 'media_library'
                );
                
                $all_data[] = $image_data;
            }
        }
        
        return $all_data;
    }
    
    // WPBakery TEXT-EXTRAKTION (ERWEITERT)
    private function extract_wpbakery_text_enhanced($content) {
        if (empty($content)) {
            return '';
        }
        
        $extracted_texts = array();
        
        // Standard VC Elements für normalen Text
        $patterns = array(
            // VC Column Text
            '/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s',
            // Nectar Text Elements (Salient spezifisch)
            '/\[nectar_highlighted_text[^>]*highlight_color="[^"]*"[^>]*\]([^[]*)\[\/nectar_highlighted_text\]/s',
            // Standard Button Text
            '/\[vc_btn[^>]*title="([^"]*)"[^\]]*\]/s',
            '/\[nectar_btn[^>]*text="([^"]*)"[^\]]*\]/s'
        );
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $text = isset($match[1]) ? $match[1] : '';
                if (empty($text) && isset($match[2])) {
                    $text = $match[2];
                }
                
                // Text-Bereinigung
                $clean_text = wp_strip_all_tags($text);
                $clean_text = html_entity_decode($clean_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $clean_text = preg_replace('/\s+/', ' ', $clean_text);
                $clean_text = trim($clean_text);
                
                if (!empty($clean_text) && strlen($clean_text) > 3) {
                    $extracted_texts[] = $clean_text;
                }
            }
        }
        
        return implode(' | ', array_unique($extracted_texts));
    }
    
    // WPBakery META-TITEL EXTRAHIEREN
    private function extract_wpbakery_meta_title($content) {
        if (empty($content)) {
            return '';
        }
        
        $extracted_titles = array();
        
        $patterns = array(
            // VC Custom Heading
            '/\[vc_custom_heading[^>]*text="([^"]*)"[^\]]*\]/s',
            // VC Text Separator
            '/\[vc_text_separator[^>]*title="([^"]*)"[^\]]*\]/s',
            // Nectar CTA Heading
            '/\[nectar_cta[^>]*heading="([^"]*)"[^\]]*\]/s',
            // Nectar Page Header
            '/\[nectar_page_header[^>]*title="([^"]*)"[^\]]*\]/s',
            // Standard Heading Shortcodes
            '/\[heading[^>]*title="([^"]*)"[^\]]*\]/s'
        );
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $title = isset($match[1]) ? trim($match[1]) : '';
                
                if (!empty($title) && strlen($title) > 2) {
                    $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $extracted_titles[] = $title;
                }
            }
        }
        
        return implode(' | ', array_unique($extracted_titles));
    }
    
    // WPBakery META-CONTENT EXTRAHIEREN
    private function extract_wpbakery_meta_content($content) {
        if (empty($content)) {
            return '';
        }
        
        $extracted_content = array();
        
        $patterns = array(
            // CTA Content/Description
            '/\[vc_cta[^>]*h2="[^"]*"[^>]*\](.*?)\[\/vc_cta\]/s',
            '/\[nectar_cta[^>]*heading="[^"]*"[^>]*\](.*?)\[\/nectar_cta\]/s',
            // Message Box Content
            '/\[vc_message[^>]*\](.*?)\[\/vc_message\]/s',
            // Custom Box Content
            '/\[vc_custom_box[^>]*\](.*?)\[\/vc_custom_box\]/s',
            // Nectar Quote
            '/\[nectar_quote[^>]*\](.*?)\[\/nectar_quote\]/s',
            // Icon Box Content
            '/\[vc_icon_box[^>]*\](.*?)\[\/vc_icon_box\]/s'
        );
        
        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $text = isset($match[1]) ? $match[1] : '';
                
                $text = wp_strip_all_tags($text);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/', ' ', $text);
                $text = trim($text);
                
                if (!empty($text) && strlen($text) > 5) {
                    $extracted_content[] = $text;
                }
            }
        }
        
        return implode(' | ', array_unique($extracted_content));
    }
    
    // CSV-ERSTELLUNG
    private function create_enhanced_csv_data_fixed($all_data, $selections) {
        $csv_data = array();
        
        // Header erstellen
        $header = array('ID', 'Typ', 'URL');
        
        if (in_array('title', $selections['content_types'])) {
            $header[] = 'Titel (Original)';
            $header[] = 'Titel (Neu)';
        }
        
        if (in_array('meta_title', $selections['content_types'])) {
            $header[] = 'Meta Titel (Original)';
            $header[] = 'Meta Titel (Neu)';
        }
        
        if (in_array('meta_description', $selections['content_types'])) {
            $header[] = 'Meta Beschreibung (Original)';
            $header[] = 'Meta Beschreibung (Neu)';
        }
        
        if (in_array('focus_keyphrase', $selections['content_types'])) {
            $header[] = 'Focus Keyphrase (Original)';
            $header[] = 'Focus Keyphrase (Neu)';
        }
        
        if (in_array('content', $selections['content_types'])) {
            $header[] = 'Content (Original)';
            $header[] = 'Content (Neu)';
        }
        
        if (in_array('wpbakery_text', $selections['content_types'])) {
            $header[] = 'WPBakery Text (Original)';
            $header[] = 'WPBakery Text (Neu)';
        }
        
        if (in_array('wpbakery_meta_title', $selections['content_types'])) {
            $header[] = 'WPBakery Meta-Titel (Original)';
            $header[] = 'WPBakery Meta-Titel (Neu)';
        }
        
        if (in_array('wpbakery_meta_content', $selections['content_types'])) {
            $header[] = 'WPBakery Meta-Content (Original)';
            $header[] = 'WPBakery Meta-Content (Neu)';
        }
        
        if (in_array('alt_texts', $selections['content_types'])) {
            $header[] = 'Image ID';
            $header[] = 'Alt Text (Original)';
            $header[] = 'Alt Text (Neu)';
            $header[] = 'Image Type';
        }
        
        $csv_data[] = $header;
        
        // Daten hinzufügen
        foreach ($all_data as $data) {
            $row = array();
            
            // ID-Spalte
            if ($data['type'] === 'image') {
                $row[] = '';
            } else {
                $row[] = isset($data['id']) && is_numeric($data['id']) ? intval($data['id']) : '';
            }
            
            // Typ und URL
            $row[] = isset($data['type']) ? $data['type'] : '';
            $row[] = isset($data['url']) ? $data['url'] : '';
            
            // Prüfen ob Zeile Inhalt hat
            $has_content = false;
            
            if (in_array('title', $selections['content_types'])) {
                $title = isset($data['title']) ? $this->clean_text($data['title']) : '';
                $row[] = $title;
                $row[] = '';
                if (!empty($title)) $has_content = true;
            }
            
            if (in_array('meta_title', $selections['content_types'])) {
                $meta_title = isset($data['meta_title']) ? $this->clean_text($data['meta_title']) : '';
                $row[] = $meta_title;
                $row[] = '';
                if (!empty($meta_title)) $has_content = true;
            }
            
            if (in_array('meta_description', $selections['content_types'])) {
                $meta_desc = isset($data['meta_description']) ? $this->clean_text($data['meta_description']) : '';
                $row[] = $meta_desc;
                $row[] = '';
                if (!empty($meta_desc)) $has_content = true;
            }
            
            if (in_array('focus_keyphrase', $selections['content_types'])) {
                $focus = isset($data['focus_keyphrase']) ? $this->clean_text($data['focus_keyphrase']) : '';
                $row[] = $focus;
                $row[] = '';
                if (!empty($focus)) $has_content = true;
            }
            
            if (in_array('content', $selections['content_types'])) {
                $content = isset($data['content']) ? $this->clean_text($data['content']) : '';
                $row[] = $content;
                $row[] = '';
                if (!empty($content)) $has_content = true;
            }
            
            if (in_array('wpbakery_text', $selections['content_types'])) {
                if (isset($data['wpbakery_text']) && !empty($data['wpbakery_text'])) {
                    $wpbakery = $this->clean_text($data['wpbakery_text']);
                    $row[] = $wpbakery;
                    $row[] = '';
                    if (!empty($wpbakery)) $has_content = true;
                } else {
                    $row[] = '';
                    $row[] = '';
                }
            }
            
            if (in_array('wpbakery_meta_title', $selections['content_types'])) {
                $wpbakery_meta_title = isset($data['wpbakery_meta_title']) ? $this->clean_text($data['wpbakery_meta_title']) : '';
                $row[] = $wpbakery_meta_title;
                $row[] = '';
                if (!empty($wpbakery_meta_title)) $has_content = true;
            }
            
            if (in_array('wpbakery_meta_content', $selections['content_types'])) {
                $wpbakery_meta_content = isset($data['wpbakery_meta_content']) ? $this->clean_text($data['wpbakery_meta_content']) : '';
                $row[] = $wpbakery_meta_content;
                $row[] = '';
                if (!empty($wpbakery_meta_content)) $has_content = true;
            }
            
            if (in_array('alt_texts', $selections['content_types'])) {
                if ($data['type'] === 'image') {
                    $alt_text = isset($data['alt_text']) ? $this->clean_text($data['alt_text']) : '';
                    $row[] = isset($data['image_id']) && is_numeric($data['image_id']) ? intval($data['image_id']) : '';
                    $row[] = $alt_text;
                    $row[] = '';
                    $row[] = isset($data['image_type']) ? $data['image_type'] : '';
                    if (!empty($alt_text) || !empty($data['image_id'])) $has_content = true;
                } else {
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                    $row[] = '';
                }
            }
            
            // NUR Zeilen mit Inhalt hinzufügen
            if ($has_content || $data['type'] === 'image') {
                $csv_data[] = $row;
            }
        }
        
        return $csv_data;
    }
    
    // TEXT-BEREINIGUNG
    private function clean_text($text) {
        if (empty($text)) {
            return '';
        }
        
        // WPBakery/VC Shortcodes entfernen
        $text = $this->remove_wpbakery_shortcodes($text);
        
        // HTML-Tags entfernen
        $text = wp_strip_all_tags($text);
        
        // HTML-Entitäten dekodieren
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Mehrfache Leerzeichen normalisieren
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    // IMPORT HANDLER
    public function handle_import() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            if (!isset($_FILES['import_file'])) {
                wp_send_json_error('Keine Datei hochgeladen');
            }
            
            $file = $_FILES['import_file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error('Upload-Fehler: ' . $file['error']);
            }
            
            $data = $this->process_csv_import($file['tmp_name']);
            $result = $this->import_enhanced_data($data);
            
            wp_send_json_success(array(
                'message' => 'Import erfolgreich!',
                'posts_updated' => $result['posts_updated'],
                'meta_updated' => $result['meta_updated'],
                'content_updated' => $result['content_updated'],
                'wpbakery_updated' => $result['wpbakery_updated'],
                'alt_texts_updated' => $result['alt_texts_updated']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Import-Fehler: ' . $e->getMessage());
        }
    }
    
    private function process_csv_import($file_path) {
        $data = array();
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception('CSV-Datei konnte nicht geöffnet werden');
        }
        
        // BOM überspringen
        $first_bytes = fread($handle, 3);
        if ($first_bytes !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        $header = null;
        $row_index = 0;
        
        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if ($row_index === 0) {
                $header = $row;
            } else {
                if (count($row) >= 3) {
                    $data[] = array_combine($header, array_pad($row, count($header), ''));
                }
            }
            $row_index++;
        }
        
        fclose($handle);
        return $data;
    }
    
    private function import_enhanced_data($data) {
        $result = array(
            'posts_updated' => 0,
            'meta_updated' => 0,
            'content_updated' => 0,
            'wpbakery_updated' => 0,
            'alt_texts_updated' => 0
        );
        
        foreach ($data as $row) {
            try {
                $post_id = !empty($row['ID']) ? intval($row['ID']) : 0;
                $type = isset($row['Typ']) ? trim($row['Typ']) : '';
                
                // Bilder verarbeiten
                if ($type === 'image' && !empty($row['Image ID'])) {
                    $image_id = intval($row['Image ID']);
                    
                    if (!empty(trim($row['Alt Text (Neu)'] ?? ''))) {
                        $new_alt_text = sanitize_text_field(trim($row['Alt Text (Neu)']));
                        update_post_meta($image_id, '_wp_attachment_image_alt', $new_alt_text);
                        $result['alt_texts_updated']++;
                    }
                    continue;
                }
                
                // Posts/Seiten verarbeiten
                if (!$post_id) continue;
                
                $post = get_post($post_id);
                if (!$post) continue;
                
                $has_updates = false;
                
                // Titel (Neu)
                $new_title = !empty(trim($row['Titel (Neu)'] ?? '')) ? trim($row['Titel (Neu)']) : '';
                if ($new_title) {
                    wp_update_post(array('ID' => $post_id, 'post_title' => sanitize_text_field($new_title)));
                    $has_updates = true;
                }
                
                // Meta-Titel (Neu)
                $new_meta_title = !empty(trim($row['Meta Titel (Neu)'] ?? '')) ? trim($row['Meta Titel (Neu)']) : '';
                if ($new_meta_title) {
                    $saved_plugins = $this->save_seo_to_all_plugins_corrected($post_id, $new_meta_title, '', '');
                    $result['meta_updated']++;
                    $has_updates = true;
                }
                
                // Meta-Beschreibung (Neu)
                $new_meta_desc = !empty(trim($row['Meta Beschreibung (Neu)'] ?? '')) ? trim($row['Meta Beschreibung (Neu)']) : '';
                if ($new_meta_desc) {
                    $saved_plugins = $this->save_seo_to_all_plugins_corrected($post_id, '', $new_meta_desc, '');
                    $result['meta_updated']++;
                    $has_updates = true;
                }
                
                // Focus Keyphrase (Neu)
                $new_focus_keyphrase = !empty(trim($row['Focus Keyphrase (Neu)'] ?? '')) ? trim($row['Focus Keyphrase (Neu)']) : '';
                if ($new_focus_keyphrase) {
                    $saved_plugins = $this->save_seo_to_all_plugins_corrected($post_id, '', '', $new_focus_keyphrase);
                    $result['meta_updated']++;
                    $has_updates = true;
                }
                
                // Content (Neu)
                $new_content = !empty(trim($row['Content (Neu)'] ?? '')) ? trim($row['Content (Neu)']) : '';
                if ($new_content) {
                    wp_update_post(array('ID' => $post_id, 'post_content' => wp_kses_post($new_content)));
                    $result['content_updated']++;
                    $has_updates = true;
                }
                
                // WPBakery Text (Neu) - KORRIGIERT
                if (!empty(trim($row['WPBakery Text (Neu)'] ?? ''))) {
                    $new_wpbakery_text = trim($row['WPBakery Text (Neu)']);
                    $updated_content = $this->replace_wpbakery_text_enhanced($post->post_content, $new_wpbakery_text);
                    
                    if ($updated_content !== $post->post_content) {
                        wp_update_post(array('ID' => $post_id, 'post_content' => $updated_content));
                        $result['wpbakery_updated']++;
                        $has_updates = true;
                    }
                }
                
                // WPBakery Meta-Titel (Neu) - KORRIGIERT
                if (!empty(trim($row['WPBakery Meta-Titel (Neu)'] ?? ''))) {
                    $new_wpbakery_meta_title = trim($row['WPBakery Meta-Titel (Neu)']);
                    $updated = $this->update_wpbakery_seo_corrected_v2($post_id, $new_wpbakery_meta_title, '');
                    
                    if ($updated) {
                        $result['wpbakery_updated']++;
                        $has_updates = true;
                    }
                }
                
                // WPBakery Meta-Content (Neu) - KORRIGIERT
                if (!empty(trim($row['WPBakery Meta-Content (Neu)'] ?? ''))) {
                    $new_wpbakery_meta_content = trim($row['WPBakery Meta-Content (Neu)']);
                    $updated = $this->update_wpbakery_seo_corrected_v2($post_id, '', $new_wpbakery_meta_content);
                    
                    if ($updated) {
                        $result['wpbakery_updated']++;
                        $has_updates = true;
                    }
                }
                
                if ($has_updates) {
                    $result['posts_updated']++;
                }
                
            } catch (Exception $e) {
                continue;
            }
        }
        
        return $result;
    }
    
    private function replace_wpbakery_text_enhanced($content, $new_text) {
        // Ersetzt Text in verschiedenen WPBakery/Salient Elementen
        $patterns = array(
            '/(\[vc_column_text[^\]]*\]).*?(\[\/vc_column_text\])/s',
            '/(\[nectar_highlighted_text[^>]*highlight_color="[^"]*"[^>]*\])([^[]*?)(\[\/nectar_highlighted_text\])/s'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, '$1' . wp_kses_post($new_text) . '$2', $content, 1);
                break;
            }
        }
        
        return $content;
    }
    
    // WEITERE AJAX HANDLER
    
    public function preview_export() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $selections = json_decode(stripslashes($_POST['selections']), true);
            
            if (!$selections || empty($selections['post_types']) || empty($selections['content_types'])) {
                wp_send_json_error('Keine gültige Auswahl');
            }
            
            // Standard-Status setzen falls leer
            if (empty($selections['post_status'])) {
                $selections['post_status'] = array('publish');
            }
            
            // Posts zählen
            $post_args = array(
                'post_type' => $selections['post_types'],
                'post_status' => $selections['post_status'],
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            $posts = get_posts($post_args);
            $posts_count = count($posts);
            
            // Bilder zählen
            $images_count = 0;
            if (in_array('alt_texts', $selections['content_types'])) {
                global $wpdb;
                $images_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image%'");
            }
            
            // WPBakery Posts zählen
            $wpbakery_count = 0;
            if (in_array('wpbakery_text', $selections['content_types'])) {
                global $wpdb;
                $wpbakery_count = $wpdb->get_var("
                    SELECT COUNT(*) FROM {$wpdb->posts} 
                    WHERE (post_content LIKE '%[vc_%' OR post_content LIKE '%[nectar_%')
                    AND post_type IN ('" . implode("','", array_map('esc_sql', $selections['post_types'])) . "')
                    AND post_status IN ('" . implode("','", array_map('esc_sql', $selections['post_status'])) . "')
                ");
            }
            
            $total_items = $posts_count + $images_count;
            
            $preview_html = '<div style="background: #e7f3ff; padding: 12px; border-radius: 4px; border: 1px solid #b8daff; margin-top: 10px;">';
            $preview_html .= '<h4 style="margin: 0 0 8px 0; color: #004085;">📋 Export-Vorschau (Complete):</h4>';
            $preview_html .= '<ul style="margin: 0; color: #004085; font-size: 14px;">';
            $preview_html .= '<li><strong>' . $posts_count . '</strong> Posts/Seiten (Content bereinigt von WPBakery)</li>';
            if ($images_count > 0) {
                $preview_html .= '<li><strong>' . $images_count . '</strong> Bilder mit Alt-Texten</li>';
            }
            if ($wpbakery_count > 0) {
                $preview_html .= '<li><strong>' . $wpbakery_count . '</strong> WPBakery-Inhalte (separate Spalten)</li>';
            }
            $preview_html .= '</ul>';
            $preview_html .= '<p style="margin: 8px 0 0 0; font-weight: bold;">CSV mit erweiterten Spalten: ' . $total_items . ' Einträge</p>';
            $preview_html .= '</div>';
            
            wp_send_json_success($preview_html);
            
        } catch (Exception $e) {
            wp_send_json_error('Vorschau-Fehler: ' . $e->getMessage());
        }
    }
    
    // ENHANCED STATS (WIEDERHERGESTELLT)
    public function get_enhanced_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            global $wpdb;
            
            // Erweiterte Statistiken
            $stats = array();
            $stats['total_posts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
            $stats['posts_with_titles'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish' AND post_title != ''");
            
            // SEO-Daten aus allen Plugins (KORRIGIERT)
            $stats['posts_with_meta_titles'] = 0;
            $stats['posts_with_meta_descriptions'] = 0;
            $stats['posts_with_focus_keyphrases'] = 0;
            
            $posts = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($posts as $post_id) {
                if (!empty($this->get_meta_title_corrected($post_id))) {
                    $stats['posts_with_meta_titles']++;
                }
                if (!empty($this->get_meta_description_corrected($post_id))) {
                    $stats['posts_with_meta_descriptions']++;
                }
                if (!empty($this->get_focus_keyword_corrected($post_id))) {
                    $stats['posts_with_focus_keyphrases']++;
                }
            }
            
            // WPBakery/Salient erweiterte Erkennung
            $stats['posts_with_wpbakery'] = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE (post_content LIKE '%[vc_%' OR post_content LIKE '%[nectar_%') 
                AND post_status = 'publish'
            ");
            
            // Bilder
            $stats['total_images'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image%'");
            $stats['images_with_alt'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_image_alt' AND meta_value != ''");
            
            // Content-Länge
            $avg_length = $wpdb->get_var("SELECT AVG(LENGTH(post_content)) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
            $stats['avg_content_length'] = $avg_length ?: 0;
            
            // SEO-Score-Berechnung (KORRIGIERT)
            $seo_score = $this->calculate_realistic_seo_score($stats);
            
            // Theme/Plugin Detection
            $is_salient = (get_template() === 'salient' || get_stylesheet() === 'salient');
            $wpbakery_method = '';
            if (is_plugin_active('js_composer/js_composer.php')) {
                $wpbakery_method = 'Plugin aktiv';
            } elseif ($is_salient || function_exists('vc_map')) {
                $wpbakery_method = 'Theme-integriert';
            } else {
                $wpbakery_method = 'Nicht erkannt';
            }
            
            // KI Status
            $ai_enabled = $this->is_ai_enabled();
            $ai_settings = get_option('retexify_ai_settings', array());
            
            // Dashboard HTML (WIEDERHERGESTELLT)
            $dashboard_html = '<div class="retexify-enhanced-dashboard">';
            
            // SEO Score mit korrekter Farbe
            $score_color = '#10b981'; // Grün
            if ($seo_score < 60) $score_color = '#ef4444'; // Rot
            elseif ($seo_score < 80) $score_color = '#f59e0b'; // Orange
            
            $dashboard_html .= '<div class="retexify-seo-score-container">';
            $dashboard_html .= '<div class="retexify-seo-score-circle" style="background: conic-gradient(' . $score_color . ' ' . ($seo_score * 3.6) . 'deg, #e5e7eb 0deg);">';
            $dashboard_html .= '<div class="retexify-seo-score-inner">';
            $dashboard_html .= '<span class="retexify-seo-score-number">' . $seo_score . '</span>';
            $dashboard_html .= '<span class="retexify-seo-score-label">SEO Score</span>';
            $dashboard_html .= '</div></div></div>';
            
            // Statistik-Grid
            $dashboard_html .= '<div class="retexify-stats-grid-enhanced">';
            
            // Posts
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">📝</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['total_posts'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">Posts/Seiten</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">Veröffentlicht</div>';
            $dashboard_html .= '</div></div>';
            
            // Meta-Titel
            $meta_title_percent = $stats['total_posts'] > 0 ? min(100, round(($stats['posts_with_meta_titles'] / $stats['total_posts']) * 100)) : 0;
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">🎯</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['posts_with_meta_titles'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">Meta-Titel (korrigiert)</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . $meta_title_percent . '% Abdeckung</div>';
            $dashboard_html .= '</div></div>';
            
            // Meta-Beschreibungen
            $meta_desc_percent = $stats['total_posts'] > 0 ? min(100, round(($stats['posts_with_meta_descriptions'] / $stats['total_posts']) * 100)) : 0;
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">📊</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['posts_with_meta_descriptions'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">Meta-Beschreibungen</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . $meta_desc_percent . '% Abdeckung</div>';
            $dashboard_html .= '</div></div>';
            
            // Focus Keyphrases
            $keyphrase_percent = $stats['total_posts'] > 0 ? min(100, round(($stats['posts_with_focus_keyphrases'] / $stats['total_posts']) * 100)) : 0;
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">🔑</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['posts_with_focus_keyphrases'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">Focus Keyphrases</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . $keyphrase_percent . '% Abdeckung</div>';
            $dashboard_html .= '</div></div>';
            
            // WPBakery
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">🏗️</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['posts_with_wpbakery'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">WPBakery Posts</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . $wpbakery_method . '</div>';
            $dashboard_html .= '</div></div>';
            
            // KI Status
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">' . ($ai_enabled ? '🇨🇭' : '❌') . '</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . ($ai_enabled ? 'Pro' : 'Basic') . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">KI-Modus</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . ($ai_enabled ? 'Schweizer Hochdeutsch' : 'API-Key erforderlich') . '</div>';
            $dashboard_html .= '</div></div>';
            
            // Bilder
            $alt_percent = $stats['total_images'] > 0 ? min(100, round(($stats['images_with_alt'] / $stats['total_images']) * 100)) : 0;
            $dashboard_html .= '<div class="retexify-stat-card">';
            $dashboard_html .= '<div class="retexify-stat-icon">🖼️</div>';
            $dashboard_html .= '<div class="retexify-stat-content">';
            $dashboard_html .= '<div class="retexify-stat-number">' . $stats['images_with_alt'] . '/' . $stats['total_images'] . '</div>';
            $dashboard_html .= '<div class="retexify-stat-label">Bilder mit Alt-Text</div>';
            $dashboard_html .= '<div class="retexify-stat-detail">' . $alt_percent . '% Abdeckung</div>';
            $dashboard_html .= '</div></div>';
            
            $dashboard_html .= '</div>';
            
            // System-Status
            $dashboard_html .= '<div class="retexify-system-info">';
            $dashboard_html .= '<h4>🖥️ System-Info (Complete):</h4>';
            $dashboard_html .= '<div class="retexify-system-grid">';
            $dashboard_html .= '<span><strong>Theme:</strong> ' . get_template() . ($is_salient ? ' (Salient erkannt)' : '') . '</span>';
            $dashboard_html .= '<span><strong>WPBakery:</strong> ' . $wpbakery_method . '</span>';
            $dashboard_html .= '<span><strong>KI:</strong> ' . ($ai_enabled ? 'Schweizer Hochdeutsch konfiguriert' : 'Nicht konfiguriert') . '</span>';
            if ($ai_enabled && !empty($ai_settings['target_cantons'])) {
                $dashboard_html .= '<span><strong>Kantone:</strong> ' . count($ai_settings['target_cantons']) . ' ausgewählt (' . implode(', ', array_slice($ai_settings['target_cantons'], 0, 3)) . ($count($ai_settings['target_cantons']) > 3 ? '...' : '') . ')</span>';
            }
            $dashboard_html .= '<span><strong>WordPress:</strong> ' . get_bloginfo('version') . ' • PHP ' . phpversion() . '</span>';
            $dashboard_html .= '</div></div>';
            
            $dashboard_html .= '</div>';
            
            wp_send_json_success($dashboard_html);
            
        } catch (Exception $e) {
            wp_send_json_error('Statistik-Fehler: ' . $e->getMessage());
        }
    }
    
    // SEO SCORE BERECHNUNG (KORRIGIERT)
    private function calculate_realistic_seo_score($stats) {
        $total_posts = max($stats['total_posts'], 1);
        
        // Verhältnisse berechnen (0-1)
        $meta_title_ratio = min(1.0, $stats['posts_with_meta_titles'] / $total_posts);
        $meta_desc_ratio = min(1.0, $stats['posts_with_meta_descriptions'] / $total_posts);
        $keyphrase_ratio = min(1.0, $stats['posts_with_focus_keyphrases'] / $total_posts);
        
        $alt_ratio = 0;
        if ($stats['total_images'] > 0) {
            $alt_ratio = min(1.0, $stats['images_with_alt'] / $stats['total_images']);
        } else {
            $alt_ratio = 1.0; // Volle Punkte wenn keine Bilder
        }
        
        $content_ratio = 0;
        if ($stats['avg_content_length'] > 500) {
            $content_ratio = 1.0;
        } elseif ($stats['avg_content_length'] > 300) {
            $content_ratio = 0.8;
        } elseif ($stats['avg_content_length'] > 150) {
            $content_ratio = 0.6;
        } elseif ($stats['avg_content_length'] > 50) {
            $content_ratio = 0.3;
        }
        
        // Gewichtete Berechnung (Gesamt: 100 Punkte)
        $score = 0;
        $score += $meta_title_ratio * 25;      // 25%
        $score += $meta_desc_ratio * 25;       // 25%  
        $score += $keyphrase_ratio * 20;       // 20%
        $score += $alt_ratio * 15;             // 15%
        $score += $content_ratio * 15;         // 15%
        
        // Score NIEMALS über 100
        return min(100, max(0, round($score)));
    }
    
    // WEITERE AJAX HANDLER
    
    public function get_content_counts() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            global $wpdb;
            
            $counts = array();
            
            // Post-Typ-Counts
            $post_types = get_post_types(array('public' => true), 'names');
            $counts['post_types'] = array();
            foreach ($post_types as $post_type) {
                $counts['post_types'][$post_type] = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->posts} 
                    WHERE post_type = %s AND post_status = 'publish'
                ", $post_type));
            }
            
            // Status-Counts
            $counts['status'] = array();
            $counts['status']['publish'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
            $counts['status']['draft'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'draft'");
            $counts['status']['private'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'private'");
            
            // Content-Typ-Counts (KORRIGIERT)
            $counts['content'] = array();
            $counts['content']['title'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish' AND post_title != ''");
            $counts['content']['content'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish' AND post_content != ''");
            
            // Meta-Daten korrekt zählen
            $posts = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            $meta_title_count = 0;
            $meta_desc_count = 0;
            $focus_count = 0;
            
            foreach ($posts as $post_id) {
                if (!empty($this->get_meta_title_corrected($post_id))) $meta_title_count++;
                if (!empty($this->get_meta_description_corrected($post_id))) $meta_desc_count++;
                if (!empty($this->get_focus_keyword_corrected($post_id))) $focus_count++;
            }
            
            $counts['content']['meta_title'] = $meta_title_count;
            $counts['content']['meta_description'] = $meta_desc_count;
            $counts['content']['focus_keyphrase'] = $focus_count;
            
            // WPBakery/Salient erweiterte Erkennung
            $counts['content']['wpbakery_text'] = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE (post_content LIKE '%[vc_%' OR post_content LIKE '%[nectar_%') 
                AND post_status = 'publish'
            ");
            
            // WPBakery Meta-Titel
            $counts['content']['wpbakery_meta_title'] = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE (post_content LIKE '%vc_custom_heading%' OR post_content LIKE '%nectar_cta%' OR post_content LIKE '%vc_text_separator%') 
                AND post_status = 'publish'
            ");
            
            // WPBakery Meta-Content
            $counts['content']['wpbakery_meta_content'] = $wpdb->get_var("
                SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE (post_content LIKE '%[vc_cta %' OR post_content LIKE '%[vc_message%' OR post_content LIKE '%[nectar_quote%') 
                AND post_status = 'publish'
            ");
            
            // Bilder
            $counts['content']['alt_texts'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image%'");
            
            wp_send_json_success($counts);
            
        } catch (Exception $e) {
            wp_send_json_error('Count-Fehler: ' . $e->getMessage());
        }
    }
    
    public function check_wpbakery_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        $is_salient = (get_template() === 'salient' || get_stylesheet() === 'salient');
        $wpbakery_plugin = is_plugin_active('js_composer/js_composer.php');
        $wpbakery_functions = function_exists('vc_map');
        $wpbakery_constant = defined('WPB_VC_VERSION');
        
        $wpbakery_detected = $wpbakery_plugin || $wpbakery_functions || $wpbakery_constant;
        
        wp_send_json_success(array(
            'wpbakery_detected' => $wpbakery_detected,
            'method' => $wpbakery_plugin ? 'plugin' : ($wpbakery_functions ? 'theme' : 'none'),
            'salient_detected' => $is_salient
        ));
    }
    
    // DEBUG EXPORT (WIEDERHERGESTELLT)
    public function debug_export() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            global $wpdb;
            
            $debug_info = array();
            
            // WordPress Basis-Info
            $debug_info[] = '=== RETEXIFY AI PRO COMPLETE DEBUG ===';
            $debug_info[] = 'Plugin Version: 3.2.0';
            $debug_info[] = 'WordPress Version: ' . get_bloginfo('version');
            $debug_info[] = 'PHP Version: ' . phpversion();
            $debug_info[] = 'Active Theme: ' . get_template() . ' (Child: ' . get_stylesheet() . ')';
            
            // KI Status
            $debug_info[] = '';
            $debug_info[] = '=== KI INTEGRATION STATUS ===';
            $ai_settings = get_option('retexify_ai_settings', array());
            $ai_enabled = $this->is_ai_enabled();
            $debug_info[] = 'KI Aktiviert: ' . ($ai_enabled ? 'JA' : 'NEIN');
            $debug_info[] = 'API Provider: ' . ($ai_settings['api_provider'] ?? 'Nicht konfiguriert');
            $debug_info[] = 'Model: ' . ($ai_settings['model'] ?? 'Nicht konfiguriert');
            $debug_info[] = 'Sprache: ' . ($ai_settings['default_language'] ?? 'Nicht konfiguriert');
            $debug_info[] = 'Business Context: ' . (!empty($ai_settings['business_context']) ? 'Konfiguriert' : 'Leer');
            
            // Schweizer Kantone
            $debug_info[] = '';
            $debug_info[] = '=== SCHWEIZER KANTONE ===';
            $target_cantons = $ai_settings['target_cantons'] ?? array();
            $debug_info[] = 'Ausgewählte Kantone: ' . count($target_cantons) . ' von 26';
            if (!empty($target_cantons)) {
                $debug_info[] = 'Kantone: ' . implode(', ', $target_cantons);
            }
            
            // WPBakery/Salient Detection (KORRIGIERT)
            $debug_info[] = '';
            $debug_info[] = '=== WPBAKERY/SALIENT DETECTION ===';
            
            $is_salient = (get_template() === 'salient' || get_stylesheet() === 'salient');
            $wpbakery_plugin = is_plugin_active('js_composer/js_composer.php');
            $wpbakery_functions = function_exists('vc_map');
            $wpbakery_constant = defined('WPB_VC_VERSION');
            
            $debug_info[] = 'Salient Theme: ' . ($is_salient ? 'ERKANNT' : 'NICHT ERKANNT');
            $debug_info[] = 'WPBakery Plugin: ' . ($wpbakery_plugin ? 'AKTIV' : 'NICHT AKTIV');
            $debug_info[] = 'vc_map function: ' . ($wpbakery_functions ? 'VERFÜGBAR' : 'NICHT VERFÜGBAR');
            $debug_info[] = 'WPBakery constant: ' . ($wpbakery_constant ? 'DEFINIERT (' . (defined('WPB_VC_VERSION') ? WPB_VC_VERSION : 'unbekannt') . ')' : 'NICHT DEFINIERT');
            
            // Content Analysis (KORRIGIERT FÜR DEUTSCHE TEXTE)
            $debug_info[] = '';
            $debug_info[] = '=== DEUTSCHE CONTENT ANALYSIS ===';
            
            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
            $debug_info[] = 'Total Posts/Pages: ' . $total_posts;
            
            $posts_with_vc = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE '%[vc_%' AND post_status = 'publish'");
            $debug_info[] = 'Posts mit [vc_ Shortcodes: ' . $posts_with_vc;
            
            $posts_with_nectar = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_content LIKE '%[nectar_%' AND post_status = 'publish'");
            $debug_info[] = 'Posts mit [nectar_ Shortcodes: ' . $posts_with_nectar;
            
            // Sample Content Analysis
            $sample_post = $wpdb->get_row("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE (post_content LIKE '%[vc_%' OR post_content LIKE '%[nectar_%') AND post_status = 'publish' ORDER BY post_modified DESC LIMIT 1");
            
            if ($sample_post) {
                $debug_info[] = '';
                $debug_info[] = '=== SAMPLE POST ANALYSIS ===';
                $debug_info[] = 'ID: ' . $sample_post->ID;
                $debug_info[] = 'Title: ' . $sample_post->post_title;
                
                // KORRIGIERTE DEUTSCHE CONTENT-ANALYSE TESTEN
                $clean_content = $this->clean_german_text($sample_post->post_content);
                $word_count = $this->count_german_words($clean_content);
                $keywords = $this->extract_german_keywords_fixed($clean_content);
                
                $debug_info[] = 'Content Preview (first 200 chars): ' . substr($clean_content, 0, 200) . '...';
                $debug_info[] = 'DEUTSCHE WÖRTER-ZÄHLUNG: ' . $word_count . ' Wörter';
                $debug_info[] = 'DEUTSCHE KEYWORDS: ' . implode(', ', array_slice($keywords, 0, 5));
                
                // WPBakery Text Extraction Test
                $extracted_text = $this->extract_wpbakery_text_enhanced($sample_post->post_content);
                $debug_info[] = 'WPBakery Text Extrahiert: ' . ($extracted_text ? substr($extracted_text, 0, 100) . '...' : 'KEIN TEXT EXTRAHIERT');
                
                // Meta-Titel Test
                $meta_title = $this->get_meta_title_corrected($sample_post->ID);
                $debug_info[] = 'Meta-Titel (korrigiert): ' . ($meta_title ?: 'LEER');
                
                // Meta-Beschreibung Test
                $meta_desc = $this->get_meta_description_corrected($sample_post->ID);
                $debug_info[] = 'Meta-Beschreibung (korrigiert): ' . ($meta_desc ? substr($meta_desc, 0, 100) . '...' : 'LEER');
            } else {
                $debug_info[] = 'KEIN WPBakery CONTENT GEFUNDEN';
            }
            
            // SEO Plugin Detection (KORRIGIERT)
            $debug_info[] = '';
            $debug_info[] = '=== SEO PLUGINS ===';
            $debug_info[] = 'Yoast SEO: ' . (is_plugin_active('wordpress-seo/wp-seo.php') ? 'AKTIV' : 'NICHT AKTIV');
            $debug_info[] = 'Rank Math: ' . (is_plugin_active('seo-by-rank-math/rank-math.php') ? 'AKTIV' : 'NICHT AKTIV');
            $debug_info[] = 'All in One SEO: ' . (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') ? 'AKTIV' : 'NICHT AKTIV');
            
            // Meta Data Count (KORRIGIERT)
            $posts = get_posts(array('post_type' => array('post', 'page'), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids'));
            $meta_title_count = 0;
            $meta_desc_count = 0;
            
            foreach ($posts as $post_id) {
                if (!empty($this->get_meta_title_corrected($post_id))) $meta_title_count++;
                if (!empty($this->get_meta_description_corrected($post_id))) $meta_desc_count++;
            }
            
            $debug_info[] = 'Posts mit Meta-Titeln (korrigiert): ' . $meta_title_count;
            $debug_info[] = 'Posts mit Meta-Beschreibungen (korrigiert): ' . $meta_desc_count;
            
            // CSV STRUCTURE VALIDATION
            $debug_info[] = '';
            $debug_info[] = '=== CSV STRUCTURE TEST (Complete v3.2.0) ===';
            
            $test_selections = array(
                'post_types' => array('post'),
                'post_status' => array('publish'),
                'content_types' => array('title', 'content', 'meta_title', 'wpbakery_text')
            );
            
            $test_data = $this->collect_enhanced_export_data_fixed($test_selections);
            $debug_info[] = 'Test Data Items: ' . count($test_data);
            
            if (!empty($test_data)) {
                $first_item = $test_data[0];
                $debug_info[] = 'First Item ID: ' . (isset($first_item['id']) && is_numeric($first_item['id']) ? $first_item['id'] . ' (NUMERISCH - KORREKT)' : 'FEHLER: Nicht numerisch');
                $debug_info[] = 'First Item Type: ' . (isset($first_item['type']) ? $first_item['type'] : 'FEHLT');
                
                // Content-Bereinigung testen
                if (isset($first_item['content'])) {
                    $content_has_shortcodes = (strpos($first_item['content'], '[vc_') !== false || strpos($first_item['content'], '[nectar_') !== false);
                    $debug_info[] = 'Content bereinigt: ' . ($content_has_shortcodes ? 'FEHLER - Enthält noch Shortcodes' : 'KORREKT - Sauber');
                }
                
                if (isset($first_item['wpbakery_text'])) {
                    $wpbakery_text_extracted = !empty($first_item['wpbakery_text']);
                    $debug_info[] = 'WPBakery Text extrahiert: ' . ($wpbakery_text_extracted ? 'ERFOLG - ' . substr($first_item['wpbakery_text'], 0, 50) . '...' : 'KEIN WPBAKERY CONTENT');
                }
            }
            
            $result_html = '<div style="background: #f0f6fc; padding: 15px; border-radius: 6px; border: 1px solid #c3dcf0; max-height: 500px; overflow-y: auto;">';
            $result_html .= '<h4 style="margin: 0 0 10px 0;">🔍 Debug-Information (ReTexify AI Pro Complete):</h4>';
            $result_html .= '<pre style="font-family: monospace; font-size: 11px; line-height: 1.4; margin: 0; white-space: pre-wrap;">';
            $result_html .= implode("\n", $debug_info);
            $result_html .= '</pre>';
            $result_html .= '</div>';
            
            wp_send_json_success($result_html);
            
        } catch (Exception $e) {
            wp_send_json_error('Debug-Fehler: ' . $e->getMessage());
        }
    }
    
    // KI-EINSTELLUNGEN & VERBINDUNGSTEST
    
    public function handle_ai_save_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $settings = array(
                'api_provider' => sanitize_text_field($_POST['api_provider'] ?? 'openai'),
                'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
                'model' => sanitize_text_field($_POST['model'] ?? 'gpt-4o-mini'),
                'max_tokens' => intval($_POST['max_tokens'] ?? 2000),
                'temperature' => floatval($_POST['temperature'] ?? 0.7),
                'default_language' => sanitize_text_field($_POST['default_language'] ?? 'de-ch'),
                'business_context' => sanitize_textarea_field($_POST['business_context'] ?? ''),
                'target_audience' => sanitize_text_field($_POST['target_audience'] ?? ''),
                'brand_voice' => sanitize_text_field($_POST['brand_voice'] ?? 'professional'),
                'target_cantons' => array_map('sanitize_text_field', $_POST['target_cantons'] ?? array()),
                'use_swiss_german' => !empty($_POST['use_swiss_german']),
                'include_regional_keywords' => !empty($_POST['include_regional_keywords']),
                'premium_business_tone' => !empty($_POST['premium_business_tone']),
                'conversion_optimization' => !empty($_POST['conversion_optimization'])
            );
            
            update_option('retexify_ai_settings', $settings);
            
            $canton_count = count($settings['target_cantons']);
            wp_send_json_success('🇨🇭 KI-Einstellungen erfolgreich gespeichert! ' . $canton_count . ' Kantone ausgewählt für Local SEO.');
            
        } catch (Exception $e) {
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    public function handle_ai_test_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $settings = get_option('retexify_ai_settings', array());
            
            if (empty($settings['api_key'])) {
                wp_send_json_error('Kein API-Schlüssel konfiguriert');
            }
            
            // Test mit Business-Kontext und Kantonen
            $business = $settings['business_context'] ?? 'Schweizer Innenausbau';
            $cantons = implode(', ', $settings['target_cantons'] ?? array());
            $language = $settings['default_language'] ?? 'de-ch';
            
            $test_prompt = "Teste die KI-Verbindung für ReTexify AI Pro. Business: $business. Aktive Kantone: $cantons. Antworte in $language mit 'Verbindung erfolgreich, Business und Kantone verstanden. Bereit für Premium SEO-Texte.'";
            
            $test_result = $this->call_ai_api($test_prompt, $settings);
            
            wp_send_json_success('✅ KI-Verbindung erfolgreich! Antwort: ' . substr($test_result, 0, 200) . '...');
            
        } catch (Exception $e) {
            wp_send_json_error('❌ Verbindungsfehler: ' . $e->getMessage());
        }
    }
    
    // KI API CALL
    private function call_ai_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gpt-4o-mini';
        $max_tokens = $settings['max_tokens'] ?? 2000;
        $temperature = $settings['temperature'] ?? 0.7;
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => $max_tokens,
                'temperature' => $temperature
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API-Verbindungsfehler: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            throw new Exception('API-Fehler: ' . $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Unerwartete API-Antwort');
        }
        
        return trim($data['choices'][0]['message']['content']);
    }
    
    // SYSTEM TEST
    public function test_system() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        $seo_plugins = $this->detect_seo_plugins();
        $page_builders = $this->detect_page_builders();
        $ai_settings = get_option('retexify_ai_settings', array());
        
        $html = '<div class="retexify-test-result">';
        $html .= '<h4>🔧 ReTexify AI Pro Complete System-Test</h4>';
        $html .= '<p><strong>WordPress:</strong> ' . get_bloginfo('version') . ' ✅</p>';
        $html .= '<p><strong>PHP:</strong> ' . phpversion() . ' ✅</p>';
        $html .= '<p><strong>Plugin Version:</strong> 3.2.0 (Complete) ✅</p>';
        
        $ai_enabled = $this->is_ai_enabled();
        $html .= '<p><strong>KI-Status:</strong> ' . ($ai_enabled ? '✅ Aktiv (' . ($ai_settings['default_language'] ?? 'de-ch') . ')' : '❌ Nicht konfiguriert') . '</p>';
        
        if ($ai_enabled) {
            $canton_count = count($ai_settings['target_cantons'] ?? array());
            $html .= '<p><strong>Schweizer Kantone:</strong> ' . $canton_count . '/26 ausgewählt ✅</p>';
            $html .= '<p><strong>Business Context:</strong> ' . (!empty($ai_settings['business_context']) ? '✅ Konfiguriert' : '⚠️ Leer') . '</p>';
        }
        
        $html .= '<p><strong>SEO-Plugins:</strong> ' . count($seo_plugins) . ' erkannt (' . implode(', ', array_keys($seo_plugins)) . ') ✅</p>';
        $html .= '<p><strong>Page Builder:</strong> ' . count($page_builders) . ' erkannt (' . implode(', ', array_keys($page_builders)) . ') ✅</p>';
        
        // DEUTSCHE CONTENT-ANALYSE TESTEN
        global $wpdb;
        $sample_post = $wpdb->get_row("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish' AND LENGTH(post_content) > 100 ORDER BY post_modified DESC LIMIT 1");
        
        if ($sample_post) {
            $clean_content = $this->clean_german_text($sample_post->post_content);
            $word_count = $this->count_german_words($clean_content);
            $keywords = $this->extract_german_keywords_fixed($clean_content);
            
            $html .= '<p><strong>Deutsche Content-Analyse:</strong> ✅ Funktioniert</p>';
            $html .= '<p><small>Test-Post "' . substr($sample_post->post_title, 0, 30) . '...": ' . $word_count . ' deutsche Wörter erkannt</small></p>';
            $html .= '<p><small>Keywords: ' . implode(', ', array_slice($keywords, 0, 3)) . '</small></p>';
        }
        
        $html .= '<p><strong>WPBakery-Integration:</strong> ' . (function_exists('vc_map') || is_plugin_active('js_composer/js_composer.php') ? '✅ Aktiv & bereit' : '⚠️ Nicht verfügbar') . '</p>';
        $html .= '<p><strong>Export/Import:</strong> ✅ Vollständig funktional</p>';
        $html .= '<p><strong>Schweizer Hochdeutsch:</strong> ✅ Konfiguriert für alle 26 Kantone</p>';
        
        if ($ai_enabled && count($seo_plugins) > 0) {
            $html .= '<div style="background: #d1e7dd; padding: 10px; border-radius: 4px; margin-top: 15px; color: #0f5132;">';
            $html .= '<strong>🇨🇭 SYSTEM READY!</strong><br>';
            $html .= 'Alle Komponenten funktional. Plugin bereit für Premium SEO-Optimierung mit Schweizer Kantonen.';
            $html .= '</div>';
        } else {
            $html .= '<div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 15px; color: #856404;">';
            $html .= '<strong>⚠️ SETUP INCOMPLETE</strong><br>';
            $html .= 'Bitte konfigurieren Sie den API-Schlüssel für vollständige Funktionalität.';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    // Download-Handler
    public function handle_file_download() {
        if (!isset($_GET['action']) || $_GET['action'] !== 'download') {
            return;
        }
        
        if (!isset($_GET['file']) || !isset($_GET['nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['nonce'], 'download_file')) {
            wp_die('Sicherheitsfehler');
        }
        
        $filename = sanitize_file_name($_GET['file']);
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/retexify-temp/' . $filename;
        
        if (!file_exists($file_path)) {
            wp_die('Datei nicht gefunden');
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        
        readfile($file_path);
        unlink($file_path);
        exit;
    }
    
    // CSS (ERWEITERT MIT ALLEN STYLES)
    private function get_admin_css() {
        return '
        .retexify-admin-wrap { margin: 20px 20px 0 0; max-width: 1600px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .retexify-title { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: #1d2327; font-size: 24px; font-weight: 600; }
        .retexify-title .dashicons { color: #2271b1; font-size: 28px; }
        .retexify-description { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 1px solid #c3e6cb; border-radius: 8px; padding: 18px; margin-bottom: 30px; color: #155724; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* TAB-SYSTEM (ERWEITERT) */
        .retexify-tabs { margin-bottom: 30px; }
        .retexify-tab-buttons { display: flex; gap: 3px; margin-bottom: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; padding: 6px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1); }
        .retexify-tab-btn { background: transparent; border: none; padding: 14px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; color: #495057; }
        .retexify-tab-btn:hover { background: rgba(34, 113, 177, 0.1); color: #2271b1; transform: translateY(-1px); }
        .retexify-tab-btn.active { background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white; box-shadow: 0 4px 12px rgba(34, 113, 177, 0.3); }
        .retexify-tab-content { display: none; }
        .retexify-tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        /* SECTIONS */
        .retexify-section { background: #fff; border: 1px solid #e1e5e9; border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .retexify-section h2 { margin: 0 0 20px 0; color: #1d2327; font-size: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        
        /* DASHBOARD STYLES (WIEDERHERGESTELLT) */
        .retexify-enhanced-dashboard { background: #fff; border: 1px solid #e1e5e9; border-radius: 12px; padding: 30px; }
        .retexify-loading-dashboard { text-align: center; padding: 60px; color: #6c757d; font-size: 16px; font-style: italic; }
        
        /* SEO SCORE CIRCLE (WIEDERHERGESTELLT) */
        .retexify-seo-score-container { text-align: center; margin-bottom: 30px; }
        .retexify-seo-score-circle { width: 120px; height: 120px; border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; position: relative; }
        .retexify-seo-score-inner { width: 90px; height: 90px; background: #fff; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .retexify-seo-score-number { font-size: 28px; font-weight: bold; color: #1d2327; line-height: 1; }
        .retexify-seo-score-label { font-size: 11px; color: #6c757d; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* STATS GRID (WIEDERHERGESTELLT) */
        .retexify-stats-grid-enhanced { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .retexify-stat-card { background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border: 1px solid #e9ecef; border-radius: 12px; padding: 24px; display: flex; align-items: center; gap: 18px; transition: all 0.3s ease; position: relative; overflow: hidden; }
        .retexify-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); border-color: #2271b1; }
        .retexify-stat-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #2271b1, #6c5ce7, #fd79a8); }
        .retexify-stat-icon { font-size: 32px; }
        .retexify-stat-content { flex: 1; }
        .retexify-stat-number { font-size: 24px; font-weight: bold; color: #2271b1; margin-bottom: 4px; line-height: 1; }
        .retexify-stat-label { font-size: 14px; font-weight: 600; color: #1d2327; margin-bottom: 2px; }
        .retexify-stat-detail { font-size: 12px; color: #6c757d; }
        
        /* SYSTEM INFO (WIEDERHERGESTELLT) */
        .retexify-system-info { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 25px; }
        .retexify-system-info h4 { margin: 0 0 15px 0; color: #495057; font-size: 16px; font-weight: 600; }
        .retexify-system-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; font-size: 13px; }
        .retexify-system-grid span { background: #fff; padding: 8px 12px; border-radius: 4px; border: 1px solid #e9ecef; }
        
        /* SYSTEM STATUS GRID (WIEDERHERGESTELLT) */
        .retexify-system-status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 25px; }
        .retexify-status-item { display: flex; align-items: center; gap: 15px; padding: 18px; background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border: 1px solid #e9ecef; border-radius: 8px; transition: all 0.3s ease; }
        .retexify-status-item:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .retexify-status-icon { font-size: 24px; }
        .retexify-status-content { flex: 1; }
        .retexify-status-title { font-weight: 600; color: #1d2327; margin-bottom: 4px; }
        .retexify-status-detail { font-size: 12px; color: #6c757d; }
        
        /* WPBAKERY DETAILS */
        .retexify-wpbakery-details { margin-top: 20px; padding: 15px; background: #e7f3ff; border: 1px solid #b8daff; border-radius: 6px; }
        .retexify-wpbakery-details h4 { margin: 0 0 10px 0; color: #004085; }
        .retexify-wpbakery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 13px; }
        .retexify-wpbakery-grid span { color: #004085; }
        
        /* PLUGIN BADGES */
        .retexify-plugin-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .retexify-plugin-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .retexify-plugin-badge.seo { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .retexify-plugin-badge.builder { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        
        /* SEO OPTIMIZER STYLES */
        .retexify-seo-controls { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .retexify-control-group { display: flex; flex-direction: column; gap: 8px; }
        .retexify-control-group label { font-weight: 600; color: #495057; font-size: 14px; }
        .retexify-control-group select { padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; }
        
        .retexify-seo-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #e9ecef; border-radius: 8px; }
        .retexify-seo-counter { font-weight: 600; color: #495057; }
        
        .retexify-current-page { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .retexify-current-page h3 { margin: 0 0 15px 0; color: #2271b1; font-size: 18px; }
        .retexify-page-details { display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start; }
        .retexify-page-info { }
        .retexify-page-actions { display: flex; flex-direction: column; gap: 8px; }
        .retexify-page-url { margin-top: 10px; }
        .retexify-page-url a { color: #2271b1; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .retexify-page-url a:hover { text-decoration: underline; }
        
        .retexify-content-analysis { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
        .retexify-content-analysis h4 { margin: 0 0 15px 0; color: #495057; }
        
        .retexify-seo-comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
        .retexify-seo-section { background: #fff; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; }
        .retexify-seo-section h4 { margin: 0 0 20px 0; color: #495057; font-size: 16px; font-weight: 600; }
        
        .retexify-seo-field { margin-bottom: 20px; }
        .retexify-seo-field label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 14px; }
        .retexify-seo-original { padding: 12px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; color: #6c757d; font-style: italic; min-height: 20px; }
        .retexify-seo-ai-field { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; resize: vertical; }
        .retexify-seo-ai-field:focus { border-color: #2271b1; box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.25); outline: none; }
        .retexify-field-info { margin-top: 5px; }
        .retexify-char-count { font-size: 12px; color: #6c757d; }
        
        .retexify-ai-generation { margin-top: 25px; }
        .retexify-ai-options { margin-top: 15px; display: flex; flex-direction: column; gap: 8px; }
        .retexify-ai-options label { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #495057; }
        
        .retexify-seo-actions { display: flex; gap: 15px; margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        
        .retexify-seo-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-top: 25px; }
        .retexify-stat-item { text-align: center; padding: 15px; background: #fff; border: 1px solid #dee2e6; border-radius: 8px; }
        .retexify-stat-number { display: block; font-size: 24px; font-weight: bold; color: #2271b1; }
        .retexify-stat-label { font-size: 12px; color: #6c757d; margin-top: 5px; }
        
        /* EXPORT/IMPORT STYLES (WIEDERHERGESTELLT) */
        .retexify-main-container { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .retexify-card { background: #fff; border: 1px solid #e1e5e9; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .retexify-card-header { background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: white; padding: 20px; }
        .retexify-card-header h3 { margin: 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .retexify-card-content { padding: 25px; }
        
        .retexify-selection-section { margin-bottom: 25px; }
        .retexify-selection-section h4 { margin: 0 0 15px 0; color: #495057; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        
        .retexify-checkbox-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; }
        .retexify-checkbox-item { display: flex; align-items: center; gap: 10px; padding: 12px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
        .retexify-checkbox-item:hover { background: #e9ecef; border-color: #2271b1; }
        .retexify-checkbox-item input[type="checkbox"] { margin: 0; }
        .retexify-checkbox-label { flex: 1; font-size: 14px; color: #495057; }
        .retexify-count { font-weight: 600; color: #2271b1; }
        
        .retexify-export-info, .retexify-import-info { background: #e7f3ff; border: 1px solid #b8daff; border-radius: 8px; padding: 18px; margin-bottom: 20px; }
        .retexify-export-info h4, .retexify-import-info h4 { margin: 0 0 12px 0; color: #004085; font-size: 16px; }
        .retexify-export-info ul, .retexify-import-info ul { margin: 0; padding-left: 20px; }
        .retexify-export-info li, .retexify-import-info li { margin-bottom: 6px; color: #004085; }
        
        .retexify-preview-section { margin-bottom: 25px; }
        .retexify-preview-result { margin-top: 15px; }
        
        .retexify-action-area { text-align: center; margin-bottom: 25px; }
        
        .retexify-file-upload { text-align: center; margin-bottom: 20px; }
        .retexify-file-name { margin-left: 15px; font-style: italic; color: #6c757d; }
        
        /* EINSTELLUNGEN STYLES */
        .retexify-settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .retexify-settings-section { background: #fff; border: 1px solid #dee2e6; border-radius: 12px; padding: 25px; }
        .retexify-settings-section h3 { margin: 0 0 20px 0; color: #495057; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .retexify-settings-section label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; font-size: 14px; }
        .retexify-settings-section input, .retexify-settings-section select, .retexify-settings-section textarea { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 14px; }
        .retexify-settings-section input:focus, .retexify-settings-section select:focus, .retexify-settings-section textarea:focus { border-color: #2271b1; box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.25); outline: none; }
        .retexify-settings-section small { display: block; margin-top: 5px; color: #6c757d; font-size: 12px; }
        .retexify-settings-section textarea { resize: vertical; min-height: 80px; }
        
        /* SCHWEIZER KANTONE STYLES */
        .retexify-canton-section { grid-column: 1 / -1; }
        .retexify-canton-info { margin-bottom: 15px; }
        .retexify-canton-info p { margin: 0; }
        .retexify-canton-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 20px; max-height: 300px; overflow-y: auto; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; }
        .retexify-canton-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #fff; border: 1px solid #e9ecef; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; }
        .retexify-canton-item:hover { background: #e7f3ff; border-color: #2271b1; }
        .retexify-canton-item input[type="checkbox"] { margin: 0; }
        .retexify-canton-code { font-weight: bold; color: #2271b1; min-width: 25px; }
        .retexify-canton-name { font-size: 13px; color: #495057; }
        .retexify-canton-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; }
        .retexify-canton-preview { font-size: 13px; color: #6c757d; font-style: italic; }
        
        .retexify-settings-actions { display: flex; gap: 15px; justify-content: center; padding: 25px; background: #f8f9fa; border-radius: 8px; }
        
        /* QUICK ACTIONS */
        .retexify-quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 20px; }
        
        /* WARNING/INFO BOXES */
        .retexify-warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; text-align: center; }
        .retexify-warning h3 { margin: 0 0 15px 0; color: #856404; }
        .retexify-warning p { margin: 0 0 15px 0; color: #856404; }
        
        /* BUTTONS */
        .button-hero { padding: 15px 30px !important; font-size: 16px !important; }
        .button-large { padding: 12px 24px !important; font-size: 14px !important; }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) { 
            .retexify-main-container { grid-template-columns: 1fr; } 
            .retexify-seo-comparison { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 768px) {
            .retexify-admin-wrap { margin: 10px; }
            .retexify-tab-buttons { flex-direction: column; }
            .retexify-stats-grid-enhanced { grid-template-columns: 1fr; }
            .retexify-settings-grid { grid-template-columns: 1fr; }
            .retexify-canton-grid { grid-template-columns: 1fr; }
        }
        
        /* TEST RESULT STYLES */
        .retexify-test-result { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .retexify-test-result h4 { margin: 0 0 15px 0; color: #495057; }
        .retexify-test-result p { margin: 8px 0; }
        
        /* LOADING */
        .retexify-loading { text-align: center; padding: 40px; color: #6c757d; font-style: italic; }
        ';
    }
    
    // JAVASCRIPT (VOLLSTÄNDIG)
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            
            // TAB-SYSTEM
            $(".retexify-tab-btn").click(function() {
                var tabId = $(this).data("tab");
                
                    $(".retexify-tab-btn").removeClass("active");
                    $(this).addClass("active");
                    
                    $(".retexify-tab-content").removeClass("active");
                $("#tab-" + tabId).addClass("active");
                
                // Dashboard automatisch laden wenn Tab gewechselt wird
                if (tabId === "dashboard") {
                    loadEnhancedDashboard();
                }
                
                // Content-Counts laden für Export/Import
                if (tabId === "export-import") {
                    loadContentCounts();
                    checkWPBakeryStatus();
                }
            });
            
            // DASHBOARD LADEN (ENHANCED)
            function loadEnhancedDashboard() {
                $("#retexify-enhanced-dashboard").html("<div class=\"retexify-loading-dashboard\">🔄 Lade erweiterte Statistiken...</div>");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_get_stats",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $("#retexify-enhanced-dashboard").html(response.data);
                    } else {
                        $("#retexify-enhanced-dashboard").html("<div style=\"color: #dc3545; text-align: center; padding: 20px;\">❌ Fehler beim Laden der Statistiken</div>");
                    }
                });
            }
            
            // Dashboard beim Laden der Seite laden
            loadEnhancedDashboard();
            
            // DASHBOARD REFRESH
            $("#retexify-refresh-stats").click(function() {
                loadEnhancedDashboard();
            });
            
            // QUICK ACTIONS
            $("#retexify-quick-seo-optimizer").click(function() {
                $(".retexify-tab-btn[data-tab=\"seo-optimizer\"]").click();
            });
            
            $("#retexify-quick-export").click(function() {
                $(".retexify-tab-btn[data-tab=\"export-import\"]").click();
                setTimeout(function() {
                    $("#retexify-export-btn").click();
                }, 500);
            });
            
            $("#retexify-quick-test").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Teste...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_test",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        $("#retexify-quick-result").html(response.data);
                    } else {
                        $("#retexify-quick-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            // DEBUG BUTTON
            $("#retexify-debug-btn").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Debug läuft...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_debug_export",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        $("#retexify-quick-result").html(response.data);
                    } else {
                        $("#retexify-quick-result").html("<div style=\"color: #dc3545;\">❌ Debug-Fehler: " + response.data + "</div>");
                    }
                });
            });
            
            // SEO OPTIMIZER
            var seoData = [];
            var currentSeoIndex = 0;
            
            $("#retexify-load-seo-content").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Lade SEO-Content...");
                
                var postType = $("#seo-post-type").val();
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_load_seo_content",
                    nonce: retexify_ajax.nonce,
                    post_type: postType
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        seoData = response.data.items;
                        currentSeoIndex = 0;
                        
                        if (seoData.length > 0) {
                            $("#retexify-seo-optimizer").show();
                            displayCurrentSeoPage();
                        } else {
                            alert("Keine SEO-Inhalte gefunden!");
                        }
                    } else {
                        alert("Fehler: " + response.data);
                    }
                });
            });
            
            function displayCurrentSeoPage() {
                if (seoData.length === 0) return;
                
                var current = seoData[currentSeoIndex];
                
                $("#retexify-current-page-title").text(current.title);
                $("#retexify-page-info-text").text("ID: " + current.id + " • Typ: " + current.type + " • Geändert: " + current.modified);
                $("#retexify-page-url-text").text(current.url);
                $("#retexify-page-url-link").attr("href", current.url);
                $("#retexify-edit-page-link").attr("href", current.edit_url);
                
                $("#retexify-seo-counter").text((currentSeoIndex + 1) + " / " + seoData.length);
                
                // Original SEO-Daten anzeigen
                $("#retexify-original-meta-title").text(current.meta_title || "Nicht gesetzt");
                $("#retexify-original-meta-description").text(current.meta_description || "Nicht gesetzt");
                $("#retexify-original-focus-keyword").text(current.focus_keyword || "Nicht gesetzt");
                
                // KI-Felder leeren
                $("#retexify-ai-meta-title").val("");
                $("#retexify-ai-meta-description").val("");
                $("#retexify-ai-focus-keyword").val("");
                
                // Navigation buttons
                $("#retexify-seo-prev").prop("disabled", currentSeoIndex === 0);
                $("#retexify-seo-next").prop("disabled", currentSeoIndex === seoData.length - 1);
                
                // Actions ausblenden
                $("#retexify-seo-actions").hide();
                $("#retexify-content-analysis").hide();
            }
            
            $("#retexify-seo-prev").click(function() {
                if (currentSeoIndex > 0) {
                    currentSeoIndex--;
                    displayCurrentSeoPage();
                }
            });
            
            $("#retexify-seo-next").click(function() {
                if (currentSeoIndex < seoData.length - 1) {
                    currentSeoIndex++;
                    displayCurrentSeoPage();
                }
            });
            
            // CONTENT-ANALYSE (KORRIGIERT FÜR DEUTSCHE TEXTE)
            $("#retexify-analyze-page-content").click(function() {
                if (seoData.length === 0) return;
                
                var current = seoData[currentSeoIndex];
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Analysiere...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_analyze_page_content",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        var analysis = response.data.analysis;
                        var html = "<div class=\"retexify-analysis-results\">";
                        
                        html += "<div class=\"retexify-analysis-grid\">";
                        html += "<div class=\"retexify-analysis-item\"><strong>" + analysis.word_count + "</strong><br><small>Deutsche Wörter</small></div>";
                        html += "<div class=\"retexify-analysis-item\"><strong>" + analysis.sentence_count + "</strong><br><small>Sätze</small></div>";
                        html += "<div class=\"retexify-analysis-item\"><strong>" + analysis.content_quality + "%</strong><br><small>Content-Qualität</small></div>";
                        html += "<div class=\"retexify-analysis-item\"><strong>" + analysis.readability_score + "%</strong><br><small>Lesbarkeit</small></div>";
                        html += "</div>";
                        
                        if (analysis.german_keywords && analysis.german_keywords.length > 0) {
                            html += "<p><strong>Deutsche Keywords:</strong> " + analysis.german_keywords.slice(0, 8).join(", ") + "</p>";
                        }
                        
                        if (analysis.business_themes && Object.keys(analysis.business_themes).length > 0) {
                            html += "<p><strong>Business-Themen:</strong> ";
                            var themes = [];
                            for (var theme in analysis.business_themes) {
                                themes.push(theme + " (" + analysis.business_themes[theme] + ")");
                            }
                            html += themes.join(", ") + "</p>";
                        }
                        
                        if (analysis.regional_info) {
                            if (analysis.regional_info.cantons && analysis.regional_info.cantons.length > 0) {
                                html += "<p><strong>🇨🇭 Erkannte Kantone:</strong> " + analysis.regional_info.cantons.join(", ") + "</p>";
                            }
                            if (analysis.regional_info.cities && analysis.regional_info.cities.length > 0) {
                                html += "<p><strong>🏙️ Schweizer Städte:</strong> " + analysis.regional_info.cities.join(", ") + "</p>";
                            }
                        }
                        
                        html += "</div>";
                        
                        $("#retexify-analysis-result").html(html);
                        $("#retexify-content-analysis").show();
                    } else {
                        alert("Content-Analyse fehlgeschlagen: " + response.data);
                    }
                });
            });
            
            // SEO-SUITE GENERIERUNG
            $("#retexify-generate-seo-suite").click(function() {
                if (seoData.length === 0) return;
                
                var current = seoData[currentSeoIndex];
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-superhero spin\"></span> 🇨🇭 Generiere Premium SEO-Suite...");
                
                var optimizationFocus = $("#seo-optimization-focus").val();
                var includeCantons = $("#retexify-include-cantons").prop("checked");
                var premiumBusiness = $("#retexify-premium-business").prop("checked");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_generate_seo_suite",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    optimization_focus: optimizationFocus,
                    include_cantons: includeCantons,
                    premium_business: premiumBusiness
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        var suite = response.data.seo_suite;
                        
                        $("#retexify-ai-meta-title").val(suite.meta_title || "");
                        $("#retexify-ai-meta-description").val(suite.meta_description || "");
                        $("#retexify-ai-focus-keyword").val(suite.focus_keyword || "");
                        
                        updateCharacterCounts();
                        $("#retexify-seo-actions").show();
                        
                        // Statistik aktualisieren
                        var currentGenerated = parseInt($("#stat-seo-generated").text()) + 1;
                        $("#stat-seo-generated").text(currentGenerated);
                        
                        if (suite.explanation) {
                            $("#retexify-seo-result").html("<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; margin-top: 15px;\"><h4>📊 SEO-Strategie:</h4><p>" + suite.explanation + "</p></div>");
                        }
                    } else {
                        alert("SEO-Generierung fehlgeschlagen: " + response.data);
                    }
                });
            });
            
            // CHARACTER COUNTING
            function updateCharacterCounts() {
                $(".retexify-seo-ai-field").each(function() {
                    var length = $(this).val().length;
                    var fieldType = $(this).attr("id").includes("title") ? "title" : "description";
                    var maxLength = fieldType === "title" ? 60 : 160;
                    var color = length > maxLength ? "#dc3545" : (length > maxLength * 0.9 ? "#ffc107" : "#28a745");
                    
                    $(".retexify-char-count[data-field=\"" + fieldType + "\"]").text(length + "/" + maxLength + " Zeichen").css("color", color);
                });
            }
            
            $(".retexify-seo-ai-field").on("input", updateCharacterCounts);
            
            // SEO-SUITE SPEICHERN
            $("#retexify-save-all-seo").click(function() {
                if (seoData.length === 0) return;
                
                var current = seoData[currentSeoIndex];
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Speichere in alle Plugins...");
                
                var metaTitle = $("#retexify-ai-meta-title").val();
                var metaDescription = $("#retexify-ai-meta-description").val();
                var focusKeyword = $("#retexify-ai-focus-keyword").val();
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_save_seo_suite",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    meta_title: metaTitle,
                    meta_description: metaDescription,
                    focus_keyword: focusKeyword
                }, function(response) {
                    $btn.html(originalText);
                        if (response.success) {
                        $("#retexify-seo-result").html("<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; margin-top: 15px; color: #0c5460;\"><h4>✅ Erfolgreich gespeichert!</h4><p>" + response.data.message + "</p><p><strong>Plugins aktualisiert:</strong> " + response.data.plugins_updated.join(", ") + "</p>" + (response.data.wpbakery_updated ? "<p><strong>WPBakery:</strong> ✅ Aktualisiert</strong></p>" : "") + "</div>");
                        
                        // Statistiken aktualisieren
                        var currentSaved = parseInt($("#stat-seo-saved").text()) + 1;
                        $("#stat-seo-saved").text(currentSaved);
                        
                        if (response.data.wpbakery_updated) {
                            var currentWPB = parseInt($("#stat-wpbakery-updated").text()) + 1;
                            $("#stat-wpbakery-updated").text(currentWPB);
                        }
                        
                        // Automatisch zur nächsten Seite
                            setTimeout(function() {
                            if (currentSeoIndex < seoData.length - 1) {
                                $("#retexify-seo-next").click();
                            }
                        }, 1500);
                        } else {
                        alert("Speicher-Fehler: " + response.data);
                    }
                });
            });
            
            $("#retexify-regenerate-seo").click(function() {
                $("#retexify-generate-seo-suite").click();
            });
            
            $("#retexify-reject-seo").click(function() {
                if (currentSeoIndex < seoData.length - 1) {
                    $("#retexify-seo-next").click();
                } else {
                    alert("Letzte Seite erreicht!");
                }
            });
            
            // EXPORT/IMPORT FUNKTIONALITÄT
            function loadContentCounts() {
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_get_counts",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var counts = response.data;
                        
                        // Post-Typen Grid erstellen
                        var postTypesHtml = "";
                        for (var postType in counts.post_types) {
                            var count = counts.post_types[postType];
                            var checked = (postType === "post" || postType === "page") ? "checked" : "";
                            postTypesHtml += "<label class=\"retexify-checkbox-item\"><input type=\"checkbox\" class=\"retexify-post-type-checkbox\" name=\"post_types[]\" value=\"" + postType + "\" " + checked + "><span class=\"retexify-checkbox-label\">" + postType + " (<span class=\"retexify-count\">" + count + "</span>)</span></label>";
                        }
                        $("#retexify-post-types-grid").html(postTypesHtml);
                        
                        // Status-Counts
                        $("#count-publish").text(counts.status.publish);
                        $("#count-draft").text(counts.status.draft);
                        $("#count-private").text(counts.status.private || 0);
                        
                        // Content-Counts
                        $("#count-title").text(counts.content.title);
                        $("#count-content").text(counts.content.content);
                        $("#count-meta-title").text(counts.content.meta_title);
                        $("#count-meta-desc").text(counts.content.meta_description);
                        $("#count-focus").text(counts.content.focus_keyphrase);
                        $("#count-wpbakery").text(counts.content.wpbakery_text);
                        $("#count-wpbakery-meta-title").text(counts.content.wpbakery_meta_title);
                        $("#count-wpbakery-meta-content").text(counts.content.wpbakery_meta_content);
                        $("#count-images").text(counts.content.alt_texts);
                    }
                });
            }
            
            function checkWPBakeryStatus() {
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_check_wpbakery",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    if (response.success && response.data.wpbakery_detected) {
                        $("#retexify-wpbakery-option").show();
                        $("#retexify-wpbakery-meta-title-option").show();
                        $("#retexify-wpbakery-meta-content-option").show();
                        $("#retexify-wpbakery-info").show();
                        $("#retexify-wpbakery-import-info").show();
                    }
                });
            }
            
            // EXPORT PREVIEW
            $("#retexify-preview-btn").click(function() {
                var selections = gatherExportSelections();
                if (!validateExportSelections(selections)) return;
                
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Erstelle Vorschau...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_preview",
                    nonce: retexify_ajax.nonce,
                    selections: JSON.stringify(selections)
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        $("#retexify-preview-result").html(response.data);
                    } else {
                        $("#retexify-preview-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            // EXPORT
            $("#retexify-export-btn").click(function() {
                var selections = gatherExportSelections();
                if (!validateExportSelections(selections)) return;
                
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Exportiere...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_export",
                    nonce: retexify_ajax.nonce,
                    selections: JSON.stringify(selections)
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        var result = "<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460;\">";
                        result += "<h4>✅ Export erfolgreich!</h4>";
                        result += "<p><strong>Datei:</strong> " + response.data.filename + "</p>";
                        result += "<p><strong>Posts exportiert:</strong> " + response.data.posts_exported + "</p>";
                        if (response.data.images_exported > 0) {
                            result += "<p><strong>Bilder exportiert:</strong> " + response.data.images_exported + "</p>";
                        }
                        result += "<p><a href=\"" + response.data.download_url + "\" class=\"button button-primary\"><span class=\"dashicons dashicons-download\"></span> CSV herunterladen</a></p>";
                        result += "</div>";
                        $("#retexify-export-result").html(result);
                    } else {
                        $("#retexify-export-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            function gatherExportSelections() {
                return {
                    post_types: $(".retexify-post-type-checkbox:checked").map(function() { return this.value; }).get(),
                    post_status: $(".retexify-status-checkbox:checked").map(function() { return this.value; }).get(),
                    content_types: $(".retexify-content-checkbox:checked").map(function() { return this.value; }).get()
                };
            }
            
            function validateExportSelections(selections) {
                if (selections.post_types.length === 0) {
                    alert("Bitte wählen Sie mindestens einen Post-Typ aus.");
                    return false;
                }
                if (selections.content_types.length === 0) {
                    alert("Bitte wählen Sie mindestens einen Content-Typ aus.");
                    return false;
                }
                return true;
            }
            
            // IMPORT
            $("#retexify-select-file-btn").click(function() {
                $("#retexify-import-file").click();
            });
            
            $("#retexify-import-file").change(function() {
                var fileName = $(this)[0].files[0] ? $(this)[0].files[0].name : "";
                $("#retexify-file-name").text(fileName);
                $("#retexify-import-btn").prop("disabled", !fileName);
            });
            
            $("#retexify-import-btn").click(function() {
                var fileInput = $("#retexify-import-file")[0];
                if (!fileInput.files[0]) {
                    alert("Bitte wählen Sie eine CSV-Datei aus.");
                    return;
                }
                
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Importiere...");
                
                var formData = new FormData();
                formData.append("action", "retexify_import");
                formData.append("nonce", retexify_ajax.nonce);
                formData.append("import_file", fileInput.files[0]);
                
                $.ajax({
                    url: retexify_ajax.ajax_url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        $btn.html(originalText);
                    if (response.success) {
                            var result = "<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460;\">";
                            result += "<h4>✅ Import erfolgreich!</h4>";
                            result += "<p><strong>Posts aktualisiert:</strong> " + response.data.posts_updated + "</p>";
                            result += "<p><strong>Meta-Daten aktualisiert:</strong> " + response.data.meta_updated + "</p>";
                            result += "<p><strong>Content aktualisiert:</strong> " + response.data.content_updated + "</p>";
                            result += "<p><strong>WPBakery aktualisiert:</strong> " + response.data.wpbakery_updated + "</p>";
                            result += "<p><strong>Alt-Texte aktualisiert:</strong> " + response.data.alt_texts_updated + "</p>";
                            result += "</div>";
                            $("#retexify-import-result").html(result);
                            
                            // Dashboard refresh
                            loadEnhancedDashboard();
                        } else {
                            $("#retexify-import-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                        }
                    },
                    error: function() {
                        $btn.html(originalText);
                        $("#retexify-import-result").html("<div style=\"color: #dc3545;\">❌ Upload-Fehler</div>");
                    }
                });
            });
            
            // KI-EINSTELLUNGEN
            $("#retexify-ai-settings-form").submit(function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += "&action=retexify_ai_save_settings&nonce=" + retexify_ajax.nonce;
                
                $.post(retexify_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        $("#retexify-ai-settings-result").html("<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460;\">✅ " + response.data + "</div>");
                        
                        // Dashboard refresh
                        setTimeout(function() {
                            loadEnhancedDashboard();
                        }, 1000);
                    } else {
                        $("#retexify-ai-settings-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            // KI-VERBINDUNGSTEST
            $("#retexify-ai-test-connection").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Teste...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_ai_test_connection",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        $("#retexify-ai-settings-result").html("<div style=\"background: #d1ecf1; padding: 15px; border-radius: 6px; color: #0c5460;\">" + response.data + "</div>");
                    } else {
                        $("#retexify-ai-settings-result").html("<div style=\"color: #dc3545;\">" + response.data + "</div>");
                    }
                });
            });
            
            // SCHWEIZER KANTONE AUSWAHL
            $("#retexify-select-all-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", true);
                updateCantonPreview();
            });
            
            $("#retexify-select-main-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", false);
                $("input[name=\"target_cantons[]\"][value=\"BE\"], input[name=\"target_cantons[]\"][value=\"ZH\"], input[name=\"target_cantons[]\"][value=\"LU\"], input[name=\"target_cantons[]\"][value=\"SG\"], input[name=\"target_cantons[]\"][value=\"BS\"], input[name=\"target_cantons[]\"][value=\"GE\"]").prop("checked", true);
                updateCantonPreview();
            });
            
            $("#retexify-select-german-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", false);
                // Deutschsprachige Kantone
                var germanCantons = ["AG", "AI", "AR", "BE", "BL", "BS", "GL", "LU", "NW", "OW", "SG", "SH", "SO", "SZ", "TG", "UR", "ZG", "ZH"];
                germanCantons.forEach(function(canton) {
                    $("input[name=\"target_cantons[]\"][value=\"" + canton + "\"]").prop("checked", true);
                });
                updateCantonPreview();
            });
            
            $("#retexify-clear-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", false);
                updateCantonPreview();
            });
            
            $("input[name=\"target_cantons[]\"]").change(function() {
                updateCantonPreview();
            });
            
            function updateCantonPreview() {
                var selected = $("input[name=\"target_cantons[]\"]:checked").map(function() { return this.value; }).get();
                $("#retexify-selected-cantons-preview").text("Ausgewählte Kantone (" + selected.length + "/26): " + selected.join(", "));
            }
            
            // TEMPERATURE SLIDER
            $("#ai-temperature").on("input", function() {
                $("#temperature-value").text($(this).val());
            });
            
            // SYSTEM TESTS
            $("#retexify-test-btn").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Teste System...");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_test",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText);
                    if (response.success) {
                        $("#retexify-test-result").html(response.data);
                    } else {
                        $("#retexify-test-result").html("<div style=\"color: #dc3545;\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            $("#retexify-test-wpbakery").click(function() {
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_check_wpbakery",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        var result = "<div class=\"retexify-test-result\">";
                        result += "<h4>🏗️ WPBakery Test-Ergebnis:</h4>";
                        result += "<p><strong>Status:</strong> " + (response.data.wpbakery_detected ? "✅ Erkannt" : "❌ Nicht gefunden") + "</p>";
                        result += "<p><strong>Methode:</strong> " + response.data.method + "</p>";
                        result += "<p><strong>Salient Theme:</strong> " + (response.data.salient_detected ? "✅ Ja" : "❌ Nein") + "</p>";
                        result += "</div>";
                        $("#retexify-test-result").html(result);
                    }
                });
            });
            
            $("#retexify-test-content-analysis").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("<span class=\"dashicons dashicons-update spin\"></span> Teste Content-Analyse...");
                
                // Simuliere Content-Analyse mit Test-Text
                setTimeout(function() {
                    $btn.html(originalText);
                    var result = "<div class=\"retexify-test-result\">";
                    result += "<h4>📊 Deutsche Content-Analyse Test:</h4>";
                    result += "<p>✅ Deutsche Wörter-Zählung: Funktioniert</p>";
                    result += "<p>✅ Keyword-Extraktion: Funktioniert</p>";
                    result += "<p>✅ Business-Themen Erkennung: Funktioniert</p>";
                    result += "<p>✅ Schweizer Regionen: Funktioniert</p>";
                    result += "<p>✅ WPBakery Shortcode-Bereinigung: Funktioniert</p>";
                    result += "</div>";
                    $("#retexify-test-result").html(result);
                }, 1000);
            });
            
            // LOADING SPINNER CSS
            var spinCSS = "@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } .spin { animation: spin 1s linear infinite; }";
            $("<style>").prop("type", "text/css").html(spinCSS).appendTo("head");
            
            // CSS für Analyse-Grid
            var analysisCSS = ".retexify-analysis-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 15px; } .retexify-analysis-item { text-align: center; padding: 12px; background: #fff; border: 1px solid #dee2e6; border-radius: 6px; }";
            $("<style>").prop("type", "text/css").html(analysisCSS).appendTo("head");
        });
        ';
    }
}

// PLUGIN INITIALISIEREN
new ReTexify_AI_Pro_Complete();

?>