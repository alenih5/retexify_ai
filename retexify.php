<?php
/**
 * Plugin Name: ReTexify AI Pro - Light & Fixed
 * Description: WordPress SEO-Plugin mit hellem Design und funktionierender KI-Integration
 * Version: 3.3.0
 * Author: Imponi
 * Text Domain: retexify_ai_pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_AI_Pro_Light {
    
    public function __construct() {
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
                'business_context' => 'Innenausbau und Renovationen in der Schweiz',
                'target_audience' => 'Privatkunden, Verwaltungen, Architekten',
                'brand_voice' => 'professional',
                'target_cantons' => array('BE', 'ZH', 'LU', 'SG'),
                'use_swiss_german' => true
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
        
        wp_add_inline_style('wp-admin', $this->get_light_css());
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
    
    public function admin_page() {
        $ai_settings = get_option('retexify_ai_settings', array());
        $ai_enabled = $this->is_ai_enabled();
        ?>
        <div class="retexify-light-wrap">
            <div class="retexify-header">
                <h1>🇨🇭 ReTexify AI Pro - Intelligente SEO-Optimierung</h1>
                <p class="retexify-subtitle">Schweizer SEO-Plugin mit KI-unterstützter Content-Optimierung • Helles, benutzerfreundliches Design</p>
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
                            <button type="button" id="retexify-refresh-stats" class="retexify-btn retexify-btn-secondary">
                                🔄 Aktualisieren
                            </button>
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
                                
                                <div class="retexify-control-group">
                                    <label for="seo-optimization-focus">Optimierungs-Fokus:</label>
                                    <select id="seo-optimization-focus" class="retexify-select">
                                        <option value="complete_seo">Komplette SEO-Optimierung</option>
                                        <option value="local_seo_swiss">Schweizer Local SEO</option>
                                        <option value="conversion">Conversion-optimiert</option>
                                        <option value="readability">Lesbarkeit</option>
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
                                            <a id="retexify-page-url" href="#" target="_blank" class="retexify-btn retexify-btn-link">
                                                🔗 Seite anzeigen
                                            </a>
                                            <a id="retexify-edit-page" href="#" target="_blank" class="retexify-btn retexify-btn-link">
                                                ✏️ Bearbeiten
                                            </a>
                                            <button type="button" id="retexify-show-content" class="retexify-btn retexify-btn-secondary">
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
                                                <div class="retexify-char-counter">
                                                    <span id="title-chars">0</span>/60 Zeichen
                                                </div>
                                                <button type="button" class="retexify-generate-single" data-type="meta_title">
                                                    🤖 Meta-Titel generieren
                                                </button>
                                            </div>
                                            
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-meta-description">Meta-Beschreibung (neu):</label>
                                                <textarea id="retexify-new-meta-description" class="retexify-textarea" placeholder="Neue Meta-Beschreibung..."></textarea>
                                                <div class="retexify-char-counter">
                                                    <span id="description-chars">0</span>/160 Zeichen
                                                </div>
                                                <button type="button" class="retexify-generate-single" data-type="meta_description">
                                                    🤖 Meta-Beschreibung generieren
                                                </button>
                                            </div>
                                            
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-focus-keyword">Focus-Keyword (neu):</label>
                                                <input type="text" id="retexify-new-focus-keyword" class="retexify-input" placeholder="Neues Focus-Keyword...">
                                                <button type="button" class="retexify-generate-single" data-type="focus_keyword">
                                                    🤖 Focus-Keyword generieren
                                                </button>
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
                                                🇨🇭 Komplette SEO-Suite generieren
                                            </button>
                                            <button type="button" id="retexify-save-seo-data" class="retexify-btn retexify-btn-success retexify-btn-large">
                                                💾 Alle Änderungen speichern
                                            </button>
                                            <button type="button" id="retexify-clear-seo-fields" class="retexify-btn retexify-btn-secondary">
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
                                <p>Bitte konfigurieren Sie zuerst Ihren OpenAI API-Schlüssel in den KI-Einstellungen.</p>
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
                        </div>
                        <div class="retexify-card-body">
                            <form id="retexify-ai-settings-form">
                                <div class="retexify-settings-grid">
                                    
                                    <!-- API Settings -->
                                    <div class="retexify-settings-group">
                                        <h3>🔑 API-Einstellungen</h3>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-api-key">OpenAI API-Schlüssel:</label>
                                            <input type="password" id="ai-api-key" name="api_key" 
                                                   value="<?php echo esc_attr($ai_settings['api_key'] ?? ''); ?>" 
                                                   class="retexify-input" placeholder="sk-...">
                                            <small>Ihr API-Schlüssel wird verschlüsselt gespeichert</small>
                                        </div>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-model">KI-Modell:</label>
                                            <select id="ai-model" name="model" class="retexify-select">
                                                <option value="gpt-4o-mini" <?php selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o-mini'); ?>>
                                                    GPT-4o Mini (Empfohlen)
                                                </option>
                                                <option value="gpt-4o" <?php selected($ai_settings['model'] ?? '', 'gpt-4o'); ?>>
                                                    GPT-4o (Premium)
                                                </option>
                                                <option value="gpt-4-turbo" <?php selected($ai_settings['model'] ?? '', 'gpt-4-turbo'); ?>>
                                                    GPT-4 Turbo
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <!-- Business Context -->
                                    <div class="retexify-settings-group">
                                        <h3>🏢 Business-Kontext</h3>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-business-context">Ihr Business/Branche:</label>
                                            <textarea id="ai-business-context" name="business_context" 
                                                      class="retexify-textarea" rows="3"
                                                      placeholder="z.B. Innenausbau und Renovationen in der Schweiz"><?php echo esc_textarea($ai_settings['business_context'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="retexify-field">
                                            <label for="ai-target-audience">Zielgruppe:</label>
                                            <input type="text" id="ai-target-audience" name="target_audience" 
                                                   value="<?php echo esc_attr($ai_settings['target_audience'] ?? ''); ?>" 
                                                   class="retexify-input" 
                                                   placeholder="z.B. Privatkunden, Verwaltungen, Architekten">
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
                            <button type="button" id="retexify-test-system" class="retexify-btn retexify-btn-secondary">
                                🧪 System testen
                            </button>
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
        <?php
    }
    
    // Schweizer Kantone
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
    
    // AJAX HANDLERS
    
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
                $meta_title = $this->get_meta_title($post->ID);
                $meta_description = $this->get_meta_description($post->ID);
                $focus_keyword = $this->get_focus_keyword($post->ID);
                
                $item = array(
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID),
                    'edit_url' => get_edit_post_link($post->ID),
                    'modified' => get_the_modified_date('d.m.Y H:i', $post->ID),
                    'type' => $post->post_type,
                    'meta_title' => $meta_title,
                    'meta_description' => $meta_description,
                    'focus_keyword' => $focus_keyword,
                    'content_preview' => wp_trim_words(strip_tags($post->post_content), 30),
                    'word_count' => str_word_count(strip_tags($post->post_content)),
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
    
    public function handle_get_page_content() {
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
            
            // Content bereinigen
            $content = $post->post_content;
            $content = wp_strip_all_tags($content);
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            $word_count = str_word_count($content);
            $char_count = strlen($content);
            
            wp_send_json_success(array(
                'content' => $content,
                'word_count' => $word_count,
                'char_count' => $char_count
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Laden des Contents: ' . $e->getMessage());
        }
    }
    
    public function handle_generate_seo_item() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $seo_type = sanitize_text_field($_POST['seo_type'] ?? '');
            $include_cantons = !empty($_POST['include_cantons']);
            $premium_tone = !empty($_POST['premium_tone']);
            
            if (!$post_id || !$seo_type) {
                wp_send_json_error('Ungültige Parameter');
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
            }
            
            $settings = get_option('retexify_ai_settings', array());
            $generated_content = $this->generate_single_seo_item($post, $seo_type, $settings, $include_cantons, $premium_tone);
            
            wp_send_json_success(array(
                'type' => $seo_type,
                'content' => $generated_content
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_generate_complete_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $post_id = intval($_POST['post_id'] ?? 0);
            $include_cantons = !empty($_POST['include_cantons']);
            $premium_tone = !empty($_POST['premium_tone']);
            
            if (!$post_id) {
                wp_send_json_error('Ungültige Post-ID');
            }
            
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error('Post nicht gefunden');
            }
            
            $settings = get_option('retexify_ai_settings', array());
            $seo_suite = $this->generate_complete_seo_suite($post, $settings, $include_cantons, $premium_tone);
            
            wp_send_json_success($seo_suite);
            
        } catch (Exception $e) {
            wp_send_json_error('Komplette SEO-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    public function handle_save_seo_data() {
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
            
            wp_send_json_success(array(
                'message' => 'SEO-Daten erfolgreich gespeichert!',
                'plugins_updated' => $saved_plugins
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Speicher-Fehler: ' . $e->getMessage());
        }
    }
    
    // KI-GENERATION METHODS
    
    private function generate_single_seo_item($post, $seo_type, $settings, $include_cantons, $premium_tone) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        $business_context = $settings['business_context'] ?? 'Schweizer Business';
        $target_audience = $settings['target_audience'] ?? 'Schweizer Kunden';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        $canton_text = '';
        if ($include_cantons && !empty($settings['target_cantons'])) {
            $cantons = $this->get_swiss_cantons();
            $selected_canton_names = array();
            foreach ($settings['target_cantons'] as $code) {
                if (isset($cantons[$code])) {
                    $selected_canton_names[] = $cantons[$code];
                }
            }
            $canton_text = 'Kantone: ' . implode(', ', $selected_canton_names);
        }
        
        $tone_instruction = $premium_tone ? 
            'Verwende einen premium, exklusiven Ton für anspruchsvolle Kunden.' : 
            'Verwende einen professionellen, aber zugänglichen Ton.';
        
        $prompts = array(
            'meta_title' => "Erstelle einen perfekten Meta-Titel (50-60 Zeichen) in Schweizer Hochdeutsch für diese Seite:

Titel: {$title}
Content: " . substr($content, 0, 300) . "
Business: {$business_context}
Zielgruppe: {$target_audience}
{$canton_text}
{$tone_instruction}

Antworte nur mit dem Meta-Titel, nichts anderes:",

            'meta_description' => "Erstelle eine überzeugende Meta-Beschreibung (150-160 Zeichen) in Schweizer Hochdeutsch für diese Seite:

Titel: {$title}
Content: " . substr($content, 0, 500) . "
Business: {$business_context}
Zielgruppe: {$target_audience}
{$canton_text}
{$tone_instruction}

Antworte nur mit der Meta-Beschreibung, nichts anderes:",

            'focus_keyword' => "Erstelle ein starkes Focus-Keyword (1-3 Wörter) für diese Seite:

Titel: {$title}
Content: " . substr($content, 0, 300) . "
Business: {$business_context}
{$canton_text}

Das Keyword sollte:
- Suchvolumen-stark für den Schweizer Markt sein
- Conversion-orientiert sein
- Lokal relevant sein

Antworte nur mit dem Keyword, nichts anderes:"
        );
        
        if (!isset($prompts[$seo_type])) {
            throw new Exception('Unbekannter SEO-Typ');
        }
        
        return $this->call_ai_api($prompts[$seo_type], $settings);
    }
    
    private function generate_complete_seo_suite($post, $settings, $include_cantons, $premium_tone) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        $business_context = $settings['business_context'] ?? 'Schweizer Business';
        $target_audience = $settings['target_audience'] ?? 'Schweizer Kunden';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        $canton_text = '';
        if ($include_cantons && !empty($settings['target_cantons'])) {
            $cantons = $this->get_swiss_cantons();
            $selected_canton_names = array();
            foreach ($settings['target_cantons'] as $code) {
                if (isset($cantons[$code])) {
                    $selected_canton_names[] = $cantons[$code];
                }
            }
            $canton_text = 'Ziel-Kantone: ' . implode(', ', $selected_canton_names);
        }
        
        $tone_instruction = $premium_tone ? 
            'PREMIUM-MODUS: Erstelle exklusive, hochwertige Business-Texte für anspruchsvolle Kunden.' : 
            'STANDARD-MODUS: Erstelle professionelle, aber zugängliche Texte.';
        
        $prompt = "Erstelle eine komplette SEO-Suite in perfektem Schweizer Hochdeutsch für diese Seite:

=== SEITENINHALT ===
Titel: {$title}
Content: " . substr($content, 0, 800) . "

=== BUSINESS-KONTEXT ===
Unternehmen: {$business_context}
Zielgruppe: {$target_audience}
Markenstimme: {$brand_voice}
{$canton_text}

=== ANWEISUNGEN ===
{$tone_instruction}

Schweizer Rechtschreibung verwenden (ss statt ß)
Regional relevant für die Schweiz
Conversion-optimiert

=== AUSGABEFORMAT (EXAKT SO) ===
META_TITEL: [Meta-Titel 50-60 Zeichen]
META_BESCHREIBUNG: [Meta-Beschreibung 150-160 Zeichen]
FOCUS_KEYWORD: [Starkes Focus-Keyword 1-3 Wörter]

Erstelle jetzt eine premium SEO-Suite:";
        
        $ai_response = $this->call_ai_api($prompt, $settings);
        
        return $this->parse_seo_suite_response($ai_response);
    }
    
    private function parse_seo_suite_response($ai_response) {
        $lines = explode("\n", $ai_response);
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
        
        return $suite;
    }
    
    // META-DATEN HELPER
    
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
        
        return '';
    }
    
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
        
        return '';
    }
    
    private function get_focus_keyword($post_id) {
        // Yoast SEO
        $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($keyword)) return $keyword;
        
        // Rank Math
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($keyword)) return $keyword;
        
        return '';
    }
    
    // WEITERE AJAX HANDLERS
    
    public function handle_ai_save_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            $settings = array(
                'api_provider' => 'openai',
                'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
                'model' => sanitize_text_field($_POST['model'] ?? 'gpt-4o-mini'),
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'default_language' => 'de-ch',
                'business_context' => sanitize_textarea_field($_POST['business_context'] ?? ''),
                'target_audience' => sanitize_text_field($_POST['target_audience'] ?? ''),
                'brand_voice' => sanitize_text_field($_POST['brand_voice'] ?? 'professional'),
                'target_cantons' => array_map('sanitize_text_field', $_POST['target_cantons'] ?? array()),
                'use_swiss_german' => true
            );
            
            update_option('retexify_ai_settings', $settings);
            
            wp_send_json_success('KI-Einstellungen erfolgreich gespeichert! ' . count($settings['target_cantons']) . ' Kantone ausgewählt.');
            
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
            
            $test_prompt = "Teste die KI-Verbindung für ReTexify AI Pro. Antworte mit: 'Verbindung erfolgreich! Bereit für Schweizer SEO-Optimierung.'";
            
            $test_result = $this->call_ai_api($test_prompt, $settings);
            
            wp_send_json_success('✅ KI-Verbindung erfolgreich! Antwort: ' . $test_result);
            
        } catch (Exception $e) {
            wp_send_json_error('Verbindungsfehler: ' . $e->getMessage());
        }
    }
    
    public function get_stats() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        try {
            global $wpdb;
            
            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post', 'page') AND post_status = 'publish'");
            
            $posts = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            
            $posts_with_meta_titles = 0;
            $posts_with_meta_descriptions = 0;
            $posts_with_focus_keywords = 0;
            
            foreach ($posts as $post_id) {
                if (!empty($this->get_meta_title($post_id))) $posts_with_meta_titles++;
                if (!empty($this->get_meta_description($post_id))) $posts_with_meta_descriptions++;
                if (!empty($this->get_focus_keyword($post_id))) $posts_with_focus_keywords++;
            }
            
            $ai_enabled = $this->is_ai_enabled();
            $ai_settings = get_option('retexify_ai_settings', array());
            
            $html = '<div class="retexify-stats-overview">';
            
            // SEO Score
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
            
            // Stats Grid
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
            
            // System Info
            $html .= '<div class="retexify-system-info">';
            $html .= '<h4>🖥️ System-Status:</h4>';
            $html .= '<div class="retexify-system-grid">';
            $html .= '<span><strong>WordPress:</strong> ' . get_bloginfo('version') . '</span>';
            $html .= '<span><strong>Theme:</strong> ' . get_template() . '</span>';
            $html .= '<span><strong>KI-Status:</strong> ' . ($ai_enabled ? '✅ Aktiv' : '❌ Nicht konfiguriert') . '</span>';
            if ($ai_enabled && !empty($ai_settings['target_cantons'])) {
                $html .= '<span><strong>Kantone:</strong> ' . count($ai_settings['target_cantons']) . ' ausgewählt</span>';
            }
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '</div>';
            
            wp_send_json_success($html);
            
        } catch (Exception $e) {
            wp_send_json_error('Statistik-Fehler: ' . $e->getMessage());
        }
    }
    
    public function test_system() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
        }
        
        $ai_enabled = $this->is_ai_enabled();
        $yoast_active = is_plugin_active('wordpress-seo/wp-seo.php');
        $rankmath_active = is_plugin_active('seo-by-rank-math/rank-math.php');
        $aioseo_active = is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php');
        
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
            $html .= '<strong>🇨🇭 SYSTEM READY!</strong> Alle Komponenten funktional. Plugin bereit für SEO-Optimierung.';
            $html .= '</div>';
        } else {
            $html .= '<div class="retexify-test-warning">';
            $html .= '<strong>⚠️ SETUP UNVOLLSTÄNDIG</strong> Bitte konfigurieren Sie die KI-Einstellungen.';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success($html);
    }
    
    // KI API CALL
    private function call_ai_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gpt-4o-mini';
        
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
                'max_tokens' => 2000,
                'temperature' => 0.7
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
    
    // HELLES CSS DESIGN
    private function get_light_css() {
        return '
        .retexify-light-wrap {
            max-width: 1400px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f9f9f9;
            min-height: 100vh;
            padding: 20px;
        }
        
        .retexify-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .retexify-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }
        
        .retexify-subtitle {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        /* TABS */
        .retexify-tabs {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .retexify-tab-nav {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        
        .retexify-tab-btn {
            flex: 1;
            padding: 18px 24px;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 600;
            color: #495057;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        
        .retexify-tab-btn:hover {
            background: #e9ecef;
            color: #007bff;
        }
        
        .retexify-tab-btn.active {
            background: white;
            color: #007bff;
            border-bottom-color: #007bff;
        }
        
        .retexify-tab-content {
            display: none;
            padding: 30px;
        }
        
        .retexify-tab-content.active {
            display: block;
        }
        
        /* CARDS */
        .retexify-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .retexify-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .retexify-card-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        
        .retexify-card-body {
            padding: 30px;
        }
        
        /* BUTTONS */
        .retexify-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1;
        }
        
        .retexify-btn-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .retexify-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        
        .retexify-btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .retexify-btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.3);
        }
        
        .retexify-btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
        }
        
        .retexify-btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        
        .retexify-btn-link {
            background: transparent;
            color: #007bff;
            text-decoration: underline;
        }
        
        .retexify-btn-large {
            padding: 16px 32px;
            font-size: 16px;
        }
        
        /* FORM ELEMENTS */
        .retexify-input,
        .retexify-select,
        .retexify-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }
        
        .retexify-input:focus,
        .retexify-select:focus,
        .retexify-textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        .retexify-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* SEO CONTROLS */
        .retexify-seo-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .retexify-control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .retexify-control-group label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        /* SEO NAVIGATION */
        .retexify-seo-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .retexify-counter {
            font-weight: 600;
            color: #495057;
            font-size: 16px;
        }
        
        /* CURRENT PAGE INFO */
        .retexify-current-page-info {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .retexify-current-page-info h3 {
            margin: 0 0 15px 0;
            color: #007bff;
            font-size: 20px;
            font-weight: 600;
        }
        
        .retexify-page-meta {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            align-items: start;
        }
        
        .retexify-page-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        /* CONTENT DISPLAY */
        .retexify-content-display {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .retexify-content-display h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        
        .retexify-content-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
            line-height: 1.6;
            color: #495057;
            margin-bottom: 15px;
        }
        
        .retexify-content-stats {
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* SEO EDITOR */
        .retexify-seo-editor {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 25px;
        }
        
        .retexify-seo-current,
        .retexify-seo-new {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
        }
        
        .retexify-seo-current h4,
        .retexify-seo-new h4 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        
        .retexify-seo-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .retexify-seo-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .retexify-seo-item label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .retexify-current-value {
            padding: 12px 16px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            color: #6c757d;
            font-style: italic;
            min-height: 20px;
        }
        
        .retexify-char-counter {
            font-size: 12px;
            color: #6c757d;
            text-align: right;
        }
        
        .retexify-generate-single {
            margin-top: 8px;
            align-self: flex-start;
        }
        
        /* GENERATION OPTIONS */
        .retexify-generation-options {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            grid-column: 1 / -1;
        }
        
        .retexify-generation-options h5 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 14px;
            font-weight: 600;
        }
        
        .retexify-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .retexify-checkbox input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        /* SEO ACTIONS */
        .retexify-seo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 12px;
            margin-top: 25px;
        }
        
        /* SETTINGS */
        .retexify-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .retexify-settings-group {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
        }
        
        .retexify-settings-group h3 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .retexify-field {
            margin-bottom: 20px;
        }
        
        .retexify-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 14px;
        }
        
        .retexify-field small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 12px;
        }
        
        .retexify-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        /* SCHWEIZER KANTONE */
        .retexify-full-width {
            grid-column: 1 / -1;
        }
        
        .retexify-canton-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .retexify-canton-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .retexify-canton-item:hover {
            background: #e7f3ff;
            border-color: #007bff;
        }
        
        .retexify-canton-item input[type="checkbox"] {
            margin: 0;
            width: auto;
        }
        
        .retexify-canton-code {
            font-weight: bold;
            color: #007bff;
            min-width: 25px;
        }
        
        .retexify-canton-name {
            font-size: 13px;
            color: #495057;
        }
        
        .retexify-canton-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .retexify-settings-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        /* DASHBOARD */
        .retexify-stats-overview {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .retexify-seo-score {
            text-align: center;
        }
        
        .retexify-score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
            color: white;
            box-shadow: 0 8px 32px rgba(40,167,69,0.3);
        }
        
        .retexify-score-number {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
        }
        
        .retexify-score-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .retexify-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .retexify-stat-item {
            text-align: center;
            padding: 20px;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .retexify-stat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        
        .retexify-stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        
        .retexify-stat-label {
            font-size: 14px;
            font-weight: 600;
            color: #495057;
        }
        
        .retexify-system-info {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
        }
        
        .retexify-system-info h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        
        .retexify-system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            font-size: 14px;
        }
        
        .retexify-system-grid span {
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        
        /* TEST RESULTS */
        .retexify-test-results {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 25px;
        }
        
        .retexify-test-results h4 {
            margin: 0 0 20px 0;
            color: #495057;
            font-size: 18px;
            font-weight: 600;
        }
        
        .retexify-test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .retexify-test-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        .retexify-test-item.success {
            background: #d1ecf1;
            border-color: #bee5eb;
        }
        
        .retexify-test-item.warning {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .retexify-test-icon {
            font-size: 20px;
        }
        
        .retexify-test-label {
            font-weight: 500;
            color: #495057;
        }
        
        .retexify-test-success {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: #0c5460;
        }
        
        .retexify-test-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            color: #856404;
        }
        
        /* LOADING & MESSAGES */
        .retexify-loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
        }
        
        .retexify-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            color: #856404;
        }
        
        .retexify-warning h3 {
            margin: 0 0 15px 0;
            color: #856404;
        }
        
        .retexify-warning p {
            margin: 0 0 20px 0;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .retexify-seo-editor {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .retexify-light-wrap {
                padding: 10px;
            }
            
            .retexify-header {
                padding: 20px;
            }
            
            .retexify-header h1 {
                font-size: 24px;
            }
            
            .retexify-tab-nav {
                flex-direction: column;
            }
            
            .retexify-seo-controls {
                grid-template-columns: 1fr;
            }
            
            .retexify-page-meta {
                grid-template-columns: 1fr;
            }
            
            .retexify-page-actions {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .retexify-settings-grid {
                grid-template-columns: 1fr;
            }
            
            .retexify-canton-grid {
                grid-template-columns: 1fr;
            }
            
            .retexify-seo-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .retexify-settings-actions {
                flex-direction: column;
            }
        }
        ';
    }
    
    // JAVASCRIPT 
    private function get_admin_js() {
        return '
        jQuery(document).ready(function($) {
            
            var seoData = [];
            var currentSeoIndex = 0;
            
            // TAB SYSTEM
            $(".retexify-tab-btn").click(function() {
                var tabId = $(this).data("tab");
                
                $(".retexify-tab-btn").removeClass("active");
                $(this).addClass("active");
                
                $(".retexify-tab-content").removeClass("active");
                $("#tab-" + tabId).addClass("active");
                
                // Dashboard laden wenn gewechselt wird
                if (tabId === "dashboard") {
                    loadDashboard();
                }
            });
            
            // Dashboard initial laden
            loadDashboard();
            
            function loadDashboard() {
                $("#retexify-dashboard-content").html("<div class=\"retexify-loading\">Lade Dashboard...</div>");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_get_stats",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $("#retexify-dashboard-content").html(response.data);
                    } else {
                        $("#retexify-dashboard-content").html("<div class=\"retexify-warning\">Fehler beim Laden der Statistiken</div>");
                    }
                });
            }
            
            // Dashboard refresh
            $("#retexify-refresh-stats").click(function() {
                loadDashboard();
            });
            
            // SEO CONTENT LADEN
            $("#retexify-load-seo-content").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("⏳ Lade SEO-Content...").prop("disabled", true);
                
                var postType = $("#seo-post-type").val();
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_load_seo_content",
                    nonce: retexify_ajax.nonce,
                    post_type: postType
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        seoData = response.data.items;
                        currentSeoIndex = 0;
                        
                        if (seoData.length > 0) {
                            $("#retexify-seo-content-list").show();
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
                $("#retexify-page-info").text("ID: " + current.id + " • Typ: " + current.type + " • " + current.word_count + " Wörter • Geändert: " + current.modified);
                $("#retexify-page-url").attr("href", current.url);
                $("#retexify-edit-page").attr("href", current.edit_url);
                
                $("#retexify-seo-counter").text((currentSeoIndex + 1) + " / " + seoData.length);
                
                // Aktuelle SEO-Daten anzeigen
                $("#retexify-current-meta-title").text(current.meta_title || "Nicht gesetzt");
                $("#retexify-current-meta-description").text(current.meta_description || "Nicht gesetzt");
                $("#retexify-current-focus-keyword").text(current.focus_keyword || "Nicht gesetzt");
                
                // Neue Felder leeren
                $("#retexify-new-meta-title").val("");
                $("#retexify-new-meta-description").val("");
                $("#retexify-new-focus-keyword").val("");
                
                updateCharCounters();
                
                // Navigation
                $("#retexify-seo-prev").prop("disabled", currentSeoIndex === 0);
                $("#retexify-seo-next").prop("disabled", currentSeoIndex === seoData.length - 1);
                
                // Content verstecken
                $("#retexify-full-content").hide();
            }
            
            // NAVIGATION
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
            
            // CONTENT ANZEIGEN
            $("#retexify-show-content").click(function() {
                if (seoData.length === 0) return;
                
                var current = seoData[currentSeoIndex];
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("⏳ Lade Content...").prop("disabled", true);
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_get_page_content",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        $("#retexify-content-text").text(response.data.content);
                        $("#retexify-word-count").text(response.data.word_count + " Wörter");
                        $("#retexify-char-count").text(response.data.char_count + " Zeichen");
                        $("#retexify-full-content").show();
                    } else {
                        alert("Fehler beim Laden des Contents: " + response.data);
                    }
                });
            });
            
            // CHARACTER COUNTER
            function updateCharCounters() {
                var titleLength = $("#retexify-new-meta-title").val().length;
                var descLength = $("#retexify-new-meta-description").val().length;
                
                $("#title-chars").text(titleLength);
                $("#description-chars").text(descLength);
                
                // Farben setzen
                $("#title-chars").css("color", titleLength > 60 ? "#dc3545" : titleLength > 54 ? "#ffc107" : "#28a745");
                $("#description-chars").css("color", descLength > 160 ? "#dc3545" : descLength > 150 ? "#ffc107" : "#28a745");
            }
            
            $("#retexify-new-meta-title, #retexify-new-meta-description").on("input", updateCharCounters);
            
            // EINZELNE SEO-ITEMS GENERIEREN
            $(".retexify-generate-single").click(function() {
                if (seoData.length === 0) {
                    alert("Bitte laden Sie zuerst SEO-Content.");
                    return;
                }
                
                var current = seoData[currentSeoIndex];
                var seoType = $(this).data("type");
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("🤖 Generiere...").prop("disabled", true);
                
                var includeCantons = $("#retexify-include-cantons").prop("checked");
                var premiumTone = $("#retexify-premium-tone").prop("checked");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_generate_seo_item",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    seo_type: seoType,
                    include_cantons: includeCantons,
                    premium_tone: premiumTone
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        var fieldId = "#retexify-new-" + seoType.replace("_", "-");
                        $(fieldId).val(response.data.content);
                        updateCharCounters();
                        
                        $("#retexify-seo-results").html("<div class=\"retexify-test-success\">✅ " + seoType.replace("_", " ") + " erfolgreich generiert!</div>");
                    } else {
                        alert("Generierung fehlgeschlagen: " + response.data);
                    }
                });
            });
            
            // KOMPLETTE SEO-SUITE GENERIEREN
            $("#retexify-generate-all-seo").click(function() {
                if (seoData.length === 0) {
                    alert("Bitte laden Sie zuerst SEO-Content.");
                    return;
                }
                
                var current = seoData[currentSeoIndex];
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("🇨🇭 Generiere komplette SEO-Suite...").prop("disabled", true);
                
                var includeCantons = $("#retexify-include-cantons").prop("checked");
                var premiumTone = $("#retexify-premium-tone").prop("checked");
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_generate_complete_seo",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    include_cantons: includeCantons,
                    premium_tone: premiumTone
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        var suite = response.data;
                        
                        $("#retexify-new-meta-title").val(suite.meta_title || "");
                        $("#retexify-new-meta-description").val(suite.meta_description || "");
                        $("#retexify-new-focus-keyword").val(suite.focus_keyword || "");
                        
                        updateCharCounters();
                        
                        $("#retexify-seo-results").html("<div class=\"retexify-test-success\">✅ Komplette SEO-Suite erfolgreich generiert!</div>");
                    } else {
                        alert("SEO-Suite Generierung fehlgeschlagen: " + response.data);
                    }
                });
            });
            
            // SEO-DATEN SPEICHERN
            $("#retexify-save-seo-data").click(function() {
                if (seoData.length === 0) {
                    alert("Bitte laden Sie zuerst SEO-Content.");
                    return;
                }
                
                var current = seoData[currentSeoIndex];
                var metaTitle = $("#retexify-new-meta-title").val();
                var metaDescription = $("#retexify-new-meta-description").val();
                var focusKeyword = $("#retexify-new-focus-keyword").val();
                
                if (!metaTitle && !metaDescription && !focusKeyword) {
                    alert("Bitte füllen Sie mindestens ein SEO-Feld aus.");
                    return;
                }
                
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("💾 Speichere...").prop("disabled", true);
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_save_seo_data",
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    meta_title: metaTitle,
                    meta_description: metaDescription,
                    focus_keyword: focusKeyword
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        $("#retexify-seo-results").html("<div class=\"retexify-test-success\">✅ " + response.data.message + "<br><strong>Plugins aktualisiert:</strong> " + response.data.plugins_updated.join(", ") + "</div>");
                        
                        // Aktuelle Daten aktualisieren
                        if (metaTitle) {
                            $("#retexify-current-meta-title").text(metaTitle);
                            seoData[currentSeoIndex].meta_title = metaTitle;
                        }
                        if (metaDescription) {
                            $("#retexify-current-meta-description").text(metaDescription);
                            seoData[currentSeoIndex].meta_description = metaDescription;
                        }
                        if (focusKeyword) {
                            $("#retexify-current-focus-keyword").text(focusKeyword);
                            seoData[currentSeoIndex].focus_keyword = focusKeyword;
                        }
                        
                        // Automatisch zur nächsten Seite nach 2 Sekunden
                        setTimeout(function() {
                            if (currentSeoIndex < seoData.length - 1) {
                                $("#retexify-seo-next").click();
                            }
                        }, 2000);
                    } else {
                        alert("Speicher-Fehler: " + response.data);
                    }
                });
            });
            
            // FELDER LEEREN
            $("#retexify-clear-seo-fields").click(function() {
                $("#retexify-new-meta-title").val("");
                $("#retexify-new-meta-description").val("");
                $("#retexify-new-focus-keyword").val("");
                updateCharCounters();
                $("#retexify-seo-results").html("");
            });
            
            // KI-EINSTELLUNGEN SPEICHERN
            $("#retexify-ai-settings-form").submit(function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += "&action=retexify_ai_save_settings&nonce=" + retexify_ajax.nonce;
                
                var $submitBtn = $(this).find("button[type=\"submit\"]");
                var originalText = $submitBtn.html();
                $submitBtn.html("💾 Speichere...").prop("disabled", true);
                
                $.post(retexify_ajax.ajax_url, formData, function(response) {
                    $submitBtn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        $("#retexify-ai-settings-result").html("<div class=\"retexify-test-success\">✅ " + response.data + "</div>");
                        
                        // Dashboard refresh nach erfolgreicher Speicherung
                        setTimeout(function() {
                            loadDashboard();
                        }, 1000);
                    } else {
                        $("#retexify-ai-settings-result").html("<div class=\"retexify-test-warning\">❌ " + response.data + "</div>");
                    }
                });
            });
            
            // KI-VERBINDUNGSTEST
            $("#retexify-ai-test-connection").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("🔗 Teste...").prop("disabled", true);
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_ai_test_connection",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        $("#retexify-ai-settings-result").html("<div class=\"retexify-test-success\">" + response.data + "</div>");
                    } else {
                        $("#retexify-ai-settings-result").html("<div class=\"retexify-test-warning\">" + response.data + "</div>");
                    }
                });
            });
            
            // SCHWEIZER KANTONE AUSWAHL
            $("#retexify-select-all-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", true);
            });
            
            $("#retexify-select-main-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", false);
                // Hauptkantone: BE, ZH, LU, SG, BS, GE
                $("input[name=\"target_cantons[]\"][value=\"BE\"], input[name=\"target_cantons[]\"][value=\"ZH\"], input[name=\"target_cantons[]\"][value=\"LU\"], input[name=\"target_cantons[]\"][value=\"SG\"], input[name=\"target_cantons[]\"][value=\"BS\"], input[name=\"target_cantons[]\"][value=\"GE\"]").prop("checked", true);
            });
            
            $("#retexify-clear-cantons").click(function() {
                $("input[name=\"target_cantons[]\"]").prop("checked", false);
            });
            
            // SYSTEM-TEST
            $("#retexify-test-system").click(function() {
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html("🧪 Teste System...").prop("disabled", true);
                
                $.post(retexify_ajax.ajax_url, {
                    action: "retexify_test",
                    nonce: retexify_ajax.nonce
                }, function(response) {
                    $btn.html(originalText).prop("disabled", false);
                    if (response.success) {
                        $("#retexify-system-status").html(response.data);
                    } else {
                        $("#retexify-system-status").html("<div class=\"retexify-warning\">❌ System-Test fehlgeschlagen: " + response.data + "</div>");
                    }
                });
            });
            
            // SYSTEM-STATUS INITIAL LADEN
            $("#retexify-test-system").click();
            
            // KEYBOARD SHORTCUTS
            $(document).keydown(function(e) {
                // Nur wenn nicht in einem Input-Feld
                if ($(e.target).is("input, textarea")) return;
                
                // Pfeiltasten für Navigation
                if (e.which === 37) { // Links
                    $("#retexify-seo-prev").click();
                    e.preventDefault();
                } else if (e.which === 39) { // Rechts
                    $("#retexify-seo-next").click();
                    e.preventDefault();
                }
                
                // G für komplette Generierung
                if (e.which === 71 && $("#retexify-seo-content-list").is(":visible")) {
                    $("#retexify-generate-all-seo").click();
                    e.preventDefault();
                }
                
                // S für Speichern
                if (e.which === 83 && $("#retexify-seo-content-list").is(":visible")) {
                    $("#retexify-save-seo-data").click();
                    e.preventDefault();
                }
            });
            
            // TOOLTIPS (falls vorhanden)
            if ($.fn.tooltip) {
                $("[title]").tooltip();
            }
            
            // SUCCESS ANIMATION
            function showSuccessMessage(message) {
                var $msg = $("<div>").addClass("retexify-success-popup")
                    .html("✅ " + message)
                    .css({
                        position: "fixed",
                        top: "20px",
                        right: "20px",
                        background: "#d1ecf1",
                        color: "#0c5460",
                        padding: "15px 25px",
                        borderRadius: "8px",
                        border: "1px solid #bee5eb",
                        zIndex: 9999,
                        boxShadow: "0 4px 12px rgba(0,0,0,0.15)"
                    });
                
                $("body").append($msg);
                
                setTimeout(function() {
                    $msg.fadeOut(function() {
                        $msg.remove();
                    });
                }, 3000);
            }
            
            // SMOOTH SCROLLING
            $("a[href^=\"#\"]").click(function(e) {
                e.preventDefault();
                var target = $($(this).attr("href"));
                if (target.length) {
                    $("html, body").animate({
                        scrollTop: target.offset().top - 20
                    }, 500);
                }
            });
            
            console.log("ReTexify AI Pro - Light Version erfolgreich geladen!");
        });
        ';
    }
}

// Plugin initialisieren
new ReTexify_AI_Pro_Light();

?>