<?php
/**
 * Plugin Name: ReTexify AI - Universal SEO Optimizer
 * Plugin URI: https://imponi.ch/
 * Description: Universelles WordPress SEO-Plugin mit KI-Integration für alle Branchen.
 * Version: 4.23.0
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
        define('RETEXIFY_VERSION', '4.23.0');
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
    
    // Sicherheits-Klassen (NEU)
    'includes/class-rate-limiter.php',
    'includes/class-error-handler.php',
    
    // Admin-Renderer (mit Fallback)
    'includes/class-admin-renderer-minimal.php',
    
    // Intelligente Features
    'includes/class-api-manager.php',
    'includes/class-intelligent-keyword-research.php',
    'includes/class_retexify_config.php',
    
    // ⚡ NEU: Advanced SEO Features
    'includes/class-advanced-content-analyzer.php',
    'includes/class-serp-competitor-analyzer.php',
    'includes/class-advanced-prompt-builder.php',
    'includes/class-german-text-processor.php'
);

foreach ($required_files as $file) {
    $file_path = RETEXIFY_PLUGIN_PATH . $file;
    if (file_exists($file_path)) {
        try {
        require_once $file_path;
        } catch (Exception $e) {
            error_log('ReTexify AI: Error loading file ' . $file . ': ' . $e->getMessage());
        }
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
        
        // Admin-Renderer initialisieren mit Fallback
        $admin_renderer_initialized = false;
        
        // Zuerst versuchen, die Haupt-Admin-Renderer-Klasse zu laden
        if (class_exists('ReTexify_Admin_Renderer')) {
            try {
        $this->admin_renderer = new ReTexify_Admin_Renderer($this->ai_engine, $this->export_import_manager);
                error_log('ReTexify AI: Haupt-Admin-Renderer erfolgreich initialisiert');
                $admin_renderer_initialized = true;
            } catch (Exception $e) {
                error_log('ReTexify AI: Fehler bei Haupt-Admin-Renderer: ' . $e->getMessage());
            }
        }
        
        // Falls Haupt-Admin-Renderer nicht funktioniert, Minimal-Version verwenden
        if (!$admin_renderer_initialized && class_exists('ReTexify_Admin_Renderer_Minimal')) {
            try {
                $this->admin_renderer = new ReTexify_Admin_Renderer_Minimal($this->ai_engine, $this->export_import_manager);
                error_log('ReTexify AI: Minimal-Admin-Renderer erfolgreich initialisiert');
                $admin_renderer_initialized = true;
            } catch (Exception $e) {
                error_log('ReTexify AI: Fehler bei Minimal-Admin-Renderer: ' . $e->getMessage());
            }
        }
        
        // Falls auch Minimal-Version nicht funktioniert, Inline-Fallback verwenden
        if (!$admin_renderer_initialized) {
            $this->admin_renderer = $this->create_inline_admin_renderer();
            error_log('ReTexify AI: Inline-Admin-Renderer als letzter Fallback initialisiert');
        }
        
        // Falls gar nichts funktioniert
        if (!$admin_renderer_initialized) {
            error_log('ReTexify AI: Kein Admin-Renderer konnte initialisiert werden!');
            $this->admin_renderer = null;
        }
    }
    
    // ========================================================================
    // 🔧 INITIALISIERUNG
    // ========================================================================
    
    /**
     * ✅ NEUE HELPER-METHODE: AJAX-Request validieren
     * Reduziert Code-Duplikation in allen AJAX-Handlern
     * 
     * @since 4.10.0
     * 
     * @param string $action AJAX-Action für Rate-Limiting
     * @return bool True wenn Request gültig, false wenn abgebrochen
     */
    private function validate_ajax_request($action = null) {
        // 1. Nonce prüfen
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'retexify_nonce')) {
            ReTexify_Error_Handler::log_security_error('nonce_verification_failed', 'AJAX Nonce verification failed', array(
                'action' => $action,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_error('Sicherheitsfehler - Ungültiger Request');
            return false;
        }
        
        // 2. Capability prüfen
        if (!current_user_can('manage_options')) {
            ReTexify_Error_Handler::log_security_error('insufficient_permissions', 'User lacks manage_options capability', array(
                'action' => $action,
                'user_id' => get_current_user_id(),
                'user_capabilities' => get_userdata(get_current_user_id())->allcaps ?? array()
            ));
            
            wp_send_json_error('Keine Berechtigung');
            return false;
        }
        
        // 3. Rate-Limiting prüfen (falls Action angegeben)
        if ($action && !ReTexify_Rate_Limiter::check_limit(get_current_user_id(), $action)) {
            $remaining_time = ReTexify_Rate_Limiter::get_reset_time(get_current_user_id(), $action);
            $minutes = ceil($remaining_time / 60);
            
            ReTexify_Error_Handler::log_error(
                ReTexify_Error_Handler::CONTEXT_SECURITY,
                'Rate limit exceeded',
                array(
                    'action' => $action,
                    'user_id' => get_current_user_id(),
                    'remaining_time' => $remaining_time
                ),
                ReTexify_Error_Handler::LEVEL_WARNING
            );
            
            wp_send_json_error("Rate-Limit erreicht. Bitte warten Sie {$minutes} Minuten.");
            return false;
        }
        
        return true;
    }
    
    /**
     * Erstellt einen Inline-Admin-Renderer als letzter Fallback
     * 
     * @since 4.10.3
     * 
     * @return object Einfacher Admin-Renderer-Objekt
     */
    private function create_inline_admin_renderer() {
        return (object) array(
            'render_admin_page' => function() {
                $ai_settings = get_option('retexify_ai_settings', array());
                $api_keys = get_option('retexify_api_keys', array());
                $ai_enabled = !empty($api_keys[$ai_settings['api_provider'] ?? 'openai']);
                
                echo '<div class="wrap">';
                echo '<h1>🇨🇭 ReTexify AI - Universeller SEO-Optimizer</h1>';
                echo '<p class="description">Version ' . (RETEXIFY_VERSION ?? '4.10.3') . ' | Inline-Fallback-Modus</p>';
                
                echo '<div class="notice notice-warning">';
                echo '<p><strong>Hinweis:</strong> Das Plugin läuft im Inline-Fallback-Modus. Alle Kernfunktionen sind verfügbar.</p>';
                echo '</div>';
                
                echo '<div class="postbox" style="margin: 20px 0;">';
                echo '<div class="postbox-header"><h2 class="hndle">🔧 System-Status</h2></div>';
                echo '<div class="inside">';
                echo '<table class="form-table">';
                echo '<tr><th>KI-Status:</th><td>' . ($ai_enabled ? '✅ Aktiv' : '❌ Nicht konfiguriert') . '</td></tr>';
                echo '<tr><th>Provider:</th><td>' . ucfirst($ai_settings['api_provider'] ?? 'openai') . '</td></tr>';
                echo '<tr><th>WordPress:</th><td>' . get_bloginfo('version') . '</td></tr>';
                echo '<tr><th>PHP:</th><td>' . PHP_VERSION . '</td></tr>';
                echo '</table>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="postbox" style="margin: 20px 0;">';
                echo '<div class="postbox-header"><h2 class="hndle">⚙️ KI-Einstellungen</h2></div>';
                echo '<div class="inside">';
                echo '<form method="post" action="' . admin_url('admin-ajax.php') . '">';
                wp_nonce_field('retexify_nonce', 'nonce');
                echo '<input type="hidden" name="action" value="retexify_save_api_key">';
                
                echo '<table class="form-table">';
                echo '<tr><th scope="row">Provider:</th><td>';
                echo '<select name="provider">';
                echo '<option value="openai"' . selected($ai_settings['api_provider'] ?? 'openai', 'openai', false) . '>OpenAI (GPT-4o)</option>';
                echo '<option value="anthropic"' . selected($ai_settings['api_provider'] ?? '', 'anthropic', false) . '>Anthropic Claude</option>';
                echo '<option value="gemini"' . selected($ai_settings['api_provider'] ?? '', 'gemini', false) . '>Google Gemini</option>';
                echo '</select>';
                echo '</td></tr>';
                
                echo '<tr><th scope="row">API-Schlüssel:</th><td>';
                echo '<input type="password" name="api_key" class="regular-text" placeholder="Geben Sie Ihren API-Schlüssel ein" value="' . (!empty($api_keys[$ai_settings['api_provider'] ?? 'openai']) ? '••••••••••••' : '') . '">';
                echo '<p class="description">Ihr API-Schlüssel wird sicher gespeichert.</p>';
                echo '</td></tr>';
                
                echo '<tr><th scope="row">Modell:</th><td>';
                echo '<select name="model">';
                echo '<option value="gpt-4o"' . selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o', false) . '>GPT-4o</option>';
                echo '<option value="gpt-4o-mini"' . selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o-mini', false) . '>GPT-4o Mini</option>';
                echo '<option value="claude-3-5-sonnet"' . selected($ai_settings['model'] ?? '', 'claude-3-5-sonnet', false) . '>Claude 3.5 Sonnet</option>';
                echo '<option value="gemini-1.5-pro"' . selected($ai_settings['model'] ?? '', 'gemini-1.5-pro', false) . '>Gemini 1.5 Pro</option>';
                echo '</select>';
                echo '</td></tr>';
                echo '</table>';
                
                echo '<p class="submit"><input type="submit" class="button-primary" value="Einstellungen speichern"></p>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="postbox" style="margin: 20px 0;">';
                echo '<div class="postbox-header"><h2 class="hndle">🚀 SEO-Test</h2></div>';
                echo '<div class="inside">';
                if ($ai_enabled) {
                    echo '<p>✅ KI ist konfiguriert und bereit für SEO-Generierung.</p>';
                    echo '<p><button type="button" class="button" onclick="testAPI()">API-Verbindung testen</button> <span id="test-result"></span></p>';
                } else {
                    echo '<p>❌ Bitte konfigurieren Sie zuerst einen API-Schlüssel.</p>';
                }
                echo '</div>';
                echo '</div>';
                
                echo '</div>';
                
                echo '<style>
                .postbox { border: 1px solid #ccd0d4; border-radius: 4px; margin: 20px 0; }
                .postbox-header { background: #f1f1f1; padding: 10px 15px; border-bottom: 1px solid #ccd0d4; }
                .postbox-header .hndle { margin: 0; font-size: 14px; }
                .inside { padding: 15px; }
                </style>';
                
                echo '<script>
                function testAPI() {
                    const resultSpan = document.getElementById("test-result");
                    resultSpan.innerHTML = "⏳ Teste...";
                    
                    jQuery.post(ajaxurl, {
                        action: "retexify_test_api_connection",
                        nonce: "' . wp_create_nonce('retexify_nonce') . '"
                    }, function(response) {
                        if (response.success) {
                            resultSpan.innerHTML = "✅ " + response.data;
                        } else {
                            resultSpan.innerHTML = "❌ " + response.data;
                        }
                    }).fail(function() {
                        resultSpan.innerHTML = "❌ AJAX-Fehler";
                    });
                }
                </script>';
            }
        );
    }
    
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
            
            // ⚡ NEU: Advanced SEO Features
            'retexify_advanced_content_analysis' => 'ajax_advanced_content_analysis',
            'retexify_serp_competitor_analysis' => 'ajax_serp_competitor_analysis',
            'retexify_generate_advanced_seo' => 'ajax_generate_advanced_seo',
            
            // 🆕 Filter & Bulk-Generierung
            'retexify_get_posts_without_seo' => 'ajax_get_posts_without_seo',
            'retexify_bulk_generate_seo' => 'ajax_bulk_generate_seo',
            
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
        
        // ✅ NEUE SICHERHEITS-FEATURES: Error-Handler-Datenbank erstellen
        if (class_exists('ReTexify_Error_Handler')) {
            ReTexify_Error_Handler::create_error_table();
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
        if ($this->admin_renderer) {
        $this->admin_renderer->render_admin_page();
        } else {
            echo '<div class="wrap">';
            echo '<h1>🇨🇭 ReTexify AI - Plugin Fehler</h1>';
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Fehler:</strong> Das Plugin konnte nicht korrekt geladen werden. ';
            echo 'Bitte überprüfen Sie die Fehler-Logs oder kontaktieren Sie den Support.';
            echo '</p></div>';
            
            // Debug-Informationen anzeigen
            echo '<div class="postbox">';
            echo '<div class="postbox-header"><h2 class="hndle">🔍 Debug-Informationen</h2></div>';
            echo '<div class="inside">';
            echo '<h4>Verfügbare ReTexify-Klassen:</h4>';
            $classes = get_declared_classes();
            $retexify_classes = array_filter($classes, function($class) {
                return strpos($class, 'ReTexify') === 0;
            });
            
            if (!empty($retexify_classes)) {
                echo '<ul>';
                foreach ($retexify_classes as $class) {
                    echo '<li>✅ ' . esc_html($class) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>❌ Keine ReTexify-Klassen gefunden!</p>';
            }
            
            echo '<h4>Plugin-Informationen:</h4>';
            echo '<ul>';
            echo '<li><strong>Version:</strong> ' . (RETEXIFY_VERSION ?? 'Unknown') . '</li>';
            echo '<li><strong>Plugin-Pfad:</strong> ' . (RETEXIFY_PLUGIN_PATH ?? 'Unknown') . '</li>';
            echo '<li><strong>WordPress-Version:</strong> ' . get_bloginfo('version') . '</li>';
            echo '<li><strong>PHP-Version:</strong> ' . PHP_VERSION . '</li>';
            echo '</ul>';
            
            echo '<h4>Fehlerbehebung:</h4>';
            echo '<ol>';
            echo '<li>Überprüfen Sie die WordPress Debug-Logs in <code>wp-content/debug.log</code></li>';
            echo '<li>Stellen Sie sicher, dass alle Plugin-Dateien korrekt hochgeladen wurden</li>';
            echo '<li>Deaktivieren Sie andere Plugins temporär um Konflikte zu testen</li>';
            echo '<li>Kontaktieren Sie den Support mit diesen Debug-Informationen</li>';
            echo '</ol>';
            
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
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
        // ✅ NEUE SICHERE VALIDIERUNG mit Rate-Limiting
        if (!$this->validate_ajax_request('generate_seo')) {
            return; // validate_ajax_request sendet bereits Response
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
            
            // ⚡ NEU: Prüfe ob Advanced Features aktiviert sind
            $use_advanced = !empty($_POST['use_advanced']) || get_option('retexify_use_advanced_features', false);
            $advanced_data = null;
            
            if ($use_advanced) {
                // ⚡ NEU: Advanced Analysis durchführen
                $keyword = sanitize_text_field($_POST['keyword'] ?? '');
                
                if (!empty($keyword)) {
                    try {
                        // Advanced Content Analyzer verwenden
                        if (class_exists('ReTexify_Advanced_Content_Analyzer')) {
                            $analyzer = new ReTexify_Advanced_Content_Analyzer();
                            $content_analysis = $analyzer->analyze_post_content($post_id, $keyword);
                            
                            // Keyword Research hinzufügen
                            if (class_exists('ReTexify_Intelligent_Keyword_Research')) {
                                $keyword_research = ReTexify_Intelligent_Keyword_Research::get_related_keywords($keyword, 10);
                                $lsi_keywords = ReTexify_Intelligent_Keyword_Research::generate_lsi_keywords($keyword);
                                
                                $advanced_data = array(
                                    'content_analysis' => $content_analysis,
                                    'keyword_research' => array(
                                        'related_keywords' => $keyword_research,
                                        'lsi_keywords' => $lsi_keywords,
                                        'search_intent' => ReTexify_Intelligent_Keyword_Research::classify_search_intent($keyword),
                                        'difficulty' => ReTexify_Intelligent_Keyword_Research::estimate_keyword_difficulty($keyword)
                                    ),
                                    'post_id' => $post_id,
                                    'keyword' => $keyword
                                );
                                
                                error_log('ReTexify: Advanced analysis completed, using enhanced generation');
                            }
                        }
                    } catch (Exception $e) {
                        error_log('ReTexify: Advanced analysis failed, falling back to standard: ' . $e->getMessage());
                        $advanced_data = null;
                    }
                }
            }
            
            // ⚠️ HAUPTVERBESSERUNG: Intelligente Keyword-Research verwenden
            if ($advanced_data) {
                // ⚡ NEU: Advanced SEO Generation mit erweiterten Daten
                $results = $this->generate_advanced_seo_suite($post, $settings, $include_cantons, $premium_tone, $advanced_data);
            } else {
                // ⚠️ BESTEHENDER CODE: Standard intelligente Generierung
            $results = $this->generate_intelligent_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            
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
            // ✅ NEUE ERROR-HANDLING mit zentralem Error-Handler
            ReTexify_Error_Handler::log_ajax_error(
                'retexify_generate_complete_seo',
                'Intelligente SEO-Generierung fehlgeschlagen',
                array(
                    'post_id' => $post_id ?? null,
                    'error_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ),
                ReTexify_Error_Handler::LEVEL_ERROR
            );
            
            wp_send_json_error('Ein Fehler ist bei der SEO-Generierung aufgetreten');
        }
        
        wp_die(); // Sicherheitshalber
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
        // ✅ NEUE SICHERE VALIDIERUNG mit Rate-Limiting
        if (!$this->validate_ajax_request('test_api')) {
            return; // validate_ajax_request sendet bereits Response
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
                    // ✅ API-Fehler loggen
                    ReTexify_Error_Handler::log_api_error(
                        $current_provider,
                        'test_connection',
                        $test_result['message'],
                        $test_result['http_code'] ?? null,
                        $test_result['response'] ?? null
                    );
                    
                    wp_send_json_error($test_result['message']);
                }
            } else {
                wp_send_json_error('KI-Engine nicht verfügbar');
            }
            
        } catch (Exception $e) {
            // ✅ NEUE ERROR-HANDLING mit zentralem Error-Handler
            ReTexify_Error_Handler::log_ajax_error(
                'retexify_test_api_connection',
                'API-Verbindungstest fehlgeschlagen',
                array(
                    'provider' => $current_provider ?? 'unknown',
                    'error_message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ),
                ReTexify_Error_Handler::LEVEL_ERROR
            );
            
            wp_send_json_error('Verbindungsfehler: ' . $e->getMessage());
        }
        
        wp_die(); // Sicherheitshalber
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
            error_log('ReTexify: Premium prompt generated, length: ' . (is_array($premium_prompt) ? print_r($premium_prompt, true) : $premium_prompt));
            $seo_suite_prompt = $this->build_intelligent_seo_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone);
            error_log('ReTexify: Calling AI with intelligent prompt...');
            $ai_response = $this->ai_engine->call_ai_api($seo_suite_prompt, $settings);
            if (empty($ai_response)) {
                error_log('ReTexify: AI API returned empty response');
                return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            $parsed_suite = $this->parse_intelligent_seo_response($ai_response, $analysis);
                
                // 🆕 SEO-Validierung: Semantische Fehler prüfen
                $parsed_suite = $this->validate_seo_semantics($parsed_suite, $post);
                
            error_log('ReTexify: Intelligent SEO suite generated successfully');
            return $parsed_suite;
        } catch (Exception $e) {
            error_log('ReTexify: Exception in intelligent SEO generation: ' . $e->getMessage());
            return $this->generate_simple_seo_suite($post, $settings, $include_cantons, $premium_tone);
        }
    }

    // ========================================================================
    // 🆕 CONTENT-AWARENESS: SEITEN-TYP ERKENNUNG
    // ========================================================================

    /**
     * Analysiert den Seiten-Kontext und verhindert semantische Fehler
     * 
     * @param WP_Post $post WordPress Post
     * @param array $settings Settings
     * @return array Context mit Seiten-Typ und Anweisungen
     */
    private function analyze_page_context($post, $settings) {
        $title = strtolower($post->post_title);
        $content = strtolower(wp_strip_all_tags($post->post_content));
        
        // LEGAL/ADMINISTRATIVE SEITEN erkennen
        $legal_keywords = array(
            'datenschutz', 'impressum', 'agb', 'nutzungsbedingungen', 
            'widerruf', 'rechtlich', 'haftung', 'disclaimer', 'cookies',
            'terms', 'privacy', 'legal', 'allgemeine geschäftsbedingungen'
        );
        
        $is_legal_page = false;
        foreach ($legal_keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $is_legal_page = true;
                break;
            }
        }
        
        // KONTAKT/INFO SEITEN erkennen
        $info_keywords = array('kontakt', 'über uns', 'team', 'karriere', 'jobs', 'about');
        $is_info_page = false;
        foreach ($info_keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $is_info_page = true;
                break;
            }
        }
        
        // Context zurückgeben
        if ($is_legal_page) {
            return array(
                'page_type' => 'legal',
                'seo_strategy' => 'informational',
                'use_business_context' => false,
                'use_cantons' => false,
                'instructions' => '🚨 KRITISCH: Dies ist eine RECHTLICHE Seite (Datenschutz/Impressum/AGB). 

ZWINGEND BEACHTEN:
- KEINE Produkt- oder Service-Erwähnungen
- KEINE Marketing-Sprache oder Verkaufsförderung
- KEINE Kantone-Erwähnungen
- KEINE Call-to-Action zum Kaufen
- NUR sachliche, informative Meta-Texte
- Fokus auf Transparenz und Information

Beispiel RICHTIG: "Datenschutzerklärung - Transparenter Umgang mit Ihren Daten"
Beispiel FALSCH: "Datenschutzerklärung für Küchenlösungen in Bern"'
            );
        } elseif ($is_info_page) {
            return array(
                'page_type' => 'info',
                'seo_strategy' => 'branding',
                'use_business_context' => true,
                'use_cantons' => true,
                'instructions' => 'Info-Seite: Fokussiere auf Unternehmen und Team.'
            );
        } else {
            return array(
                'page_type' => 'commercial',
                'seo_strategy' => 'conversion',
                'use_business_context' => true,
                'use_cantons' => true,
                'instructions' => 'Kommerzielle Seite: Erstelle verkaufsorientierte Meta-Texte mit Call-to-Action.'
            );
        }
    }

    /**
     * 🇨🇭 SCHWEIZER KANTONE: Codes zu ausgeschriebenen Namen konvertieren
     * 
     * Diese Funktion konvertiert ALLE 26 Schweizer Kantone-Codes
     * 
     * @param array $canton_codes Array mit Kantone-Codes (z.B. ['BE', 'SO', 'ZH'])
     * @return array Array mit ausgeschriebenen Namen (z.B. ['Bern', 'Solothurn', 'Zürich'])
     */
    private function get_canton_names($canton_codes) {
        // Validierung
        if (empty($canton_codes)) {
            return array();
        }
        
        // Sicherstellen dass es ein Array ist
        if (!is_array($canton_codes)) {
            $canton_codes = array($canton_codes);
        }
        
        // 🇨🇭 VOLLSTÄNDIGE KANTONE-MAP - ALLE 26 SCHWEIZER KANTONE
        $canton_map = array(
            // A-B
            'AG' => 'Aargau',
            'AI' => 'Appenzell Innerrhoden',
            'AR' => 'Appenzell Ausserrhoden',
            'BE' => 'Bern',
            'BL' => 'Basel-Landschaft',
            'BS' => 'Basel-Stadt',
            
            // F-G
            'FR' => 'Freiburg',
            'GE' => 'Genf',
            'GL' => 'Glarus',
            'GR' => 'Graubünden',
            
            // J-L
            'JU' => 'Jura',
            'LU' => 'Luzern',
            
            // N-O
            'NE' => 'Neuenburg',
            'NW' => 'Nidwalden',
            'OW' => 'Obwalden',
            
            // S
            'SG' => 'St. Gallen',
            'SH' => 'Schaffhausen',
            'SO' => 'Solothurn',
            'SZ' => 'Schwyz',
            
            // T-U
            'TG' => 'Thurgau',
            'TI' => 'Tessin',
            'UR' => 'Uri',
            
            // V-Z
            'VD' => 'Waadt',
            'VS' => 'Wallis',
            'ZG' => 'Zug',
            'ZH' => 'Zürich'
        );
        
        // Codes zu Namen konvertieren
        $canton_names = array();
        foreach ($canton_codes as $code) {
            // Code normalisieren (Grossbuchstaben)
            $code = strtoupper(trim($code));
            
            // Prüfen ob Kanton existiert
            if (isset($canton_map[$code])) {
                $canton_names[] = $canton_map[$code];
            } else {
                // Unbekannter Code - trotzdem loggen
                error_log("ReTexify: Unbekannter Kantone-Code: {$code}");
            }
        }
        
        return $canton_names;
    }

    /**
     * Validiert generierte SEO-Texte auf semantische Fehler
     * 
     * @param array $seo_suite Generierte SEO-Texte
     * @param WP_Post $post Original Post
     * @return array Validierte Texte
     */
    private function validate_seo_semantics($seo_suite, $post) {
        // 1. Textlängen-Optimierung
        $seo_suite = $this->optimize_text_lengths($seo_suite);
        
        // 2. Kantone-Validierung
        $canton_errors = $this->validate_canton_abbreviations($seo_suite);
        
        if (!empty($canton_errors)) {
            error_log("ReTexify: Kantone-Fehler erkannt - Regeneriere...");
            error_log("Fehler: " . implode(", ", $canton_errors));
        }
        
        // 3. Seiten-Typ-Validierung
        $page_context = $this->analyze_page_context($post, array());
        
        // Wenn Legal-Seite: Prüfe auf Business-Wörter
        if ($page_context['page_type'] === 'legal') {
            $full_text = strtolower(
                $seo_suite['meta_title'] . ' ' . 
                $seo_suite['meta_description'] . ' ' . 
                ($seo_suite['focus_keyword'] ?? '')
            );
            
            // Verbotene Wörter für Legal-Seiten
            $forbidden_business_words = array(
                'küche', 'produkt', 'service', 'angebot', 'kaufen', 
                'bestellen', 'lösung', 'dienstleistung', 'beratung'
            );
            
            foreach ($forbidden_business_words as $word) {
                if (strpos($full_text, $word) !== false) {
                    error_log("ReTexify: Semantischer Fehler erkannt auf Legal-Seite - '{$word}' gefunden!");
                    
                    // Verwende Safe Defaults
                    return $this->generate_safe_legal_seo($post);
                }
            }
        }
        
        return $seo_suite;
    }

    /**
     * Extrahiert Haupt-Keywords aus Titel und Content
     * 
     * @param string $title Post-Titel
     * @param string $content Post-Content
     * @return array Extrahierte Keywords
     */
    private function extract_content_keywords($title, $content) {
        $keywords = array();
        
        // Haupt-Keywords aus Titel extrahieren
        $title_words = explode(' ', strtolower($title));
        foreach ($title_words as $word) {
            $word = trim($word, '.,!?;:-');
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        // Content nach Hauptthemen durchsuchen
        $content_lower = strtolower($content);
        
        // 🚀 UNIVERSELLE PRODUKTKATEGORIEN FÜR ALLE BRANCHEN
        $product_categories = array(
            // 🏠 BAU & IMMOBILIEN
            'immobilien' => array('haus', 'häuser', 'wohnung', 'wohnungen', 'immobilie', 'immobilien', 'eigentum', 'miete', 'kauf', 'verkauf'),
            'bau' => array('bau', 'bauen', 'renovierung', 'sanierung', 'umbau', 'bauplanung', 'architekt', 'baustelle'),
            'dach' => array('dach', 'dächer', 'dachdecker', 'ziegel', 'dachziegel', 'dachfenster', 'dachboden'),
            'heizung' => array('heizung', 'heizungen', 'wärme', 'heizkörper', 'kessel', 'wärmepumpe', 'solar'),
            'sanitär' => array('sanitär', 'bad', 'badezimmer', 'toilette', 'dusche', 'badewanne', 'waschbecken'),
            
            // 🚗 AUTOMOTIVE
            'auto' => array('auto', 'autos', 'fahrzeug', 'fahrzeuge', 'pkw', 'limousine', 'kombi', 'suv'),
            'motorrad' => array('motorrad', 'motorräder', 'motorroller', 'scooter', 'chopper', 'enduro'),
            'reifen' => array('reifen', 'pneus', 'winterreifen', 'sommerreifen', 'ganzjahresreifen'),
            'werkstatt' => array('werkstatt', 'werkstätten', 'reparatur', 'wartung', 'service', 'inspektion'),
            'tuning' => array('tuning', 'tunen', 'modifikation', 'umbau', 'sportauspuff', 'felgen'),
            
            // 💻 TECHNOLOGIE & IT
            'software' => array('software', 'app', 'anwendung', 'programm', 'system', 'lösung'),
            'hardware' => array('hardware', 'computer', 'laptop', 'pc', 'server', 'netzwerk'),
            'webdesign' => array('webdesign', 'website', 'homepage', 'internet', 'online', 'web'),
            'smartphone' => array('smartphone', 'handy', 'iphone', 'android', 'telefon', 'mobil'),
            'gaming' => array('gaming', 'spiel', 'spiele', 'gamer', 'konsole', 'pc-spiele'),
            
            // 🏥 GESUNDHEIT & MEDIZIN
            'arzt' => array('arzt', 'ärztin', 'praxis', 'behandlung', 'diagnose', 'therapie'),
            'zahnarzt' => array('zahnarzt', 'zahnärztin', 'zähne', 'zahnbehandlung', 'implantat', 'prothese'),
            'apotheke' => array('apotheke', 'medikament', 'arznei', 'rezept', 'heilmittel'),
            'krankenhaus' => array('krankenhaus', 'klinik', 'station', 'operation', 'pflege'),
            'fitness' => array('fitness', 'training', 'sport', 'gym', 'studio', 'workout'),
            
            // 🎓 BILDUNG & AUSBILDUNG
            'schule' => array('schule', 'schulen', 'grundschule', 'realschule', 'gymnasium', 'lehrer'),
            'universität' => array('universität', 'uni', 'hochschule', 'studium', 'student', 'professor'),
            'kurs' => array('kurs', 'kurse', 'seminar', 'schulung', 'fortbildung', 'weiterbildung'),
            'sprachkurs' => array('sprachkurs', 'sprache', 'englisch', 'deutsch', 'französisch', 'italienisch'),
            'musikschule' => array('musikschule', 'musik', 'instrument', 'klavier', 'gitarre', 'geige'),
            
            // 🍽️ GASTRONOMIE
            'restaurant' => array('restaurant', 'gaststätte', 'lokal', 'küche', 'menü', 'speisekarte'),
            'café' => array('café', 'kaffee', 'kuchen', 'torte', 'frühstück', 'brunch'),
            'catering' => array('catering', 'bewirtung', 'verpflegung', 'party', 'event', 'feier'),
            'bäckerei' => array('bäckerei', 'bäcker', 'brot', 'brötchen', 'kuchen', 'gebäck'),
            'metzgerei' => array('metzgerei', 'metzger', 'fleisch', 'wurst', 'schinken', 'salami'),
            
            // 👕 MODE & BEAUTY
            'mode' => array('mode', 'kleidung', 'bekleidung', 'fashion', 'outfit', 'stil'),
            'schuhe' => array('schuhe', 'schuh', 'stiefel', 'sneaker', 'sandalen', 'pumps'),
            'kosmetik' => array('kosmetik', 'makeup', 'schminke', 'creme', 'pflege', 'beauty'),
            'frisör' => array('frisör', 'friseur', 'haar', 'haarschnitt', 'färben', 'styling'),
            'parfüm' => array('parfüm', 'duft', 'eau', 'parfum', 'spray', 'creme'),
            
            // 🎨 KUNST & KULTUR
            'kunst' => array('kunst', 'künstler', 'gemälde', 'skulptur', 'galerie', 'ausstellung'),
            'musik' => array('musik', 'konzert', 'band', 'musiker', 'live', 'konzertsaal'),
            'theater' => array('theater', 'bühne', 'schauspiel', 'aufführung', 'premiere', 'drama'),
            'museum' => array('museum', 'ausstellung', 'exponat', 'sammlung', 'geschichte'),
            'buch' => array('buch', 'bücher', 'buchhandlung', 'roman', 'literatur', 'lesen'),
            
            // 🏭 INDUSTRIE & HANDWERK
            'maschine' => array('maschine', 'maschinen', 'produktion', 'fertigung', 'industrie'),
            'werkzeug' => array('werkzeug', 'werkzeuge', 'bohrer', 'säge', 'hammer', 'schrauber'),
            'elektrik' => array('elektrik', 'elektriker', 'strom', 'elektrisch', 'installation'),
            'maler' => array('maler', 'streichen', 'farbe', 'anstrich', 'tapete', 'renovierung'),
            'schreiner' => array('schreiner', 'tischler', 'möbel', 'holz', 'massiv', 'einrichtung'),
            
            // 🏠 KÜCHE & HAUSHALT (Original erweitert)
            'küche' => array('küche', 'küchen', 'kochen', 'küchenplanung', 'kücheneinrichtung', 'küchenmöbel'),
            'griffe' => array('griff', 'griffe', 'handgriff', 'türgriff', 'küchengriff', 'küchengriffe', 'schrankgriff', 'schrankgriffe'),
            'neolith' => array('neolith', 'keramik', 'arbeitsplatte', 'arbeitsplatten', 'neolith-keramik', 'keramik-platte'),
            'backofen' => array('backofen', 'backöfen', 'einbau-backofen', 'herd', 'herde', 'kochfeld'),
            'spüle' => array('spüle', 'spülen', 'küchenspüle', 'waschbecken', 'spülbecken'),
            'kühlschrank' => array('kühlschrank', 'kühlschränke', 'gefrierschrank', 'kühl-gefrier', 'side-by-side'),
            'geschirrspüler' => array('geschirrspüler', 'spülmaschine', 'spüler', 'einbau-spüler'),
            'waschmaschine' => array('waschmaschine', 'trockner', 'waschen', 'wäsche', 'laundry'),
            
            // 🏠 HAUS & GARTEN
            'garten' => array('garten', 'gärten', 'gartengestaltung', 'landschaftsbau', 'pflanzen'),
            'pool' => array('pool', 'schwimmbad', 'whirlpool', 'sauna', 'wellness'),
            'terrasse' => array('terrasse', 'balkon', 'dachterrasse', 'belag', 'fliesen'),
            'garage' => array('garage', 'carport', 'einfahrt', 'auffahrt', 'parkplatz'),
            'keller' => array('keller', 'kellergeschoss', 'weinkeller', 'lagerraum'),
            
            // 🎯 DIENSTLEISTUNGEN
            'beratung' => array('beratung', 'consulting', 'berater', 'coaching', 'mentoring'),
            'recht' => array('recht', 'anwalt', 'rechtanwalt', 'notar', 'gericht', 'rechtsschutz'),
            'versicherung' => array('versicherung', 'versicherungen', 'police', 'beitrag', 'schaden'),
            'steuer' => array('steuer', 'steuerberater', 'buchhaltung', 'finanzen', 'abrechnung'),
            'reise' => array('reise', 'reisen', 'urlaub', 'ferien', 'hotel', 'flug'),
            
            // 🏪 EINZELHANDEL & VERKAUF
            'shop' => array('shop', 'laden', 'geschäft', 'verkauf', 'handel', 'retail'),
            'online-shop' => array('online-shop', 'e-commerce', 'internet-shop', 'web-shop', 'online-handel'),
            'großhandel' => array('großhandel', 'großhändler', 'wholesale', 'import', 'export'),
            'markt' => array('markt', 'supermarkt', 'discount', 'discounter', 'lebensmittel'),
            'tankstelle' => array('tankstelle', 'benzin', 'diesel', 'kraftstoff', 'tanken')
        );
        
        $detected_category = null;
        $max_matches = 0;
        
        foreach ($product_categories as $category => $keywords_array) {
            $matches = 0;
            foreach ($keywords_array as $keyword) {
                if (strpos($content_lower, $keyword) !== false || strpos(strtolower($title), $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > $max_matches) {
                $max_matches = $matches;
                $detected_category = $category;
            }
        }
        
        return array(
            'main_keywords' => $keywords,
            'detected_category' => $detected_category,
            'confidence' => $max_matches
        );
    }

    /**
     * Generiert sichere SEO-Texte für Legal-Seiten
     * 
     * @param WP_Post $post WordPress Post
     * @return array Safe SEO-Texte
     */
    private function generate_safe_legal_seo($post) {
        $title = $post->post_title;
        $site_name = get_bloginfo('name');
        
        return array(
            'meta_title' => $title . ' - ' . $site_name,
            'meta_description' => 'Informationen zu ' . $title . ' auf ' . $site_name . '. Erfahren Sie mehr über unsere rechtlichen Bestimmungen.',
            'focus_keyword' => strtolower(explode(' ', $title)[0])
        );
    }

    /**
     * Prüft ob SEO-Texte Kantone-Abkürzungen enthalten (FEHLER!)
     * 
     * @param array $seo_suite Generierte SEO-Texte
     * @return array Fehler-Array oder leeres Array
     */
    private function validate_canton_abbreviations($seo_suite) {
        // Alle 26 Kantone-Codes
        $canton_codes = array(
            'AG', 'AI', 'AR', 'BE', 'BL', 'BS', 'FR', 'GE', 'GL', 'GR',
            'JU', 'LU', 'NE', 'NW', 'OW', 'SG', 'SH', 'SO', 'SZ', 'TG',
            'TI', 'UR', 'VD', 'VS', 'ZG', 'ZH'
        );
        
        $errors = array();
        $full_text = strtoupper(
            ($seo_suite['meta_title'] ?? '') . ' ' . 
            ($seo_suite['meta_description'] ?? '') . ' ' . 
            ($seo_suite['focus_keyword'] ?? '')
        );
        
        // Prüfe auf jede Abkürzung
        foreach ($canton_codes as $code) {
            // Regex: Kanton-Code als ganzes Wort (mit Wortgrenzen)
            if (preg_match('/\b' . $code . '\b/', $full_text)) {
                $errors[] = "Kantone-Abkürzung gefunden: '{$code}' - MUSS ausgeschrieben werden!";
                error_log("ReTexify FEHLER: Kantone-Abkürzung '{$code}' in SEO-Text gefunden!");
            }
        }
        
        return $errors;
    }

    /**
     * Optimiert Textlängen für maximale SEO-Wirkung
     * 
     * @param array $seo_suite SEO-Texte
     * @return array Optimierte Texte
     */
    private function optimize_text_lengths($seo_suite) {
        // Meta-Titel: NUR kürzen wenn wirklich zu lang (>65 Zeichen)
        if (isset($seo_suite['meta_title'])) {
            $title = $seo_suite['meta_title'];
            $len = mb_strlen($title);
            
            // Nur kürzen wenn deutlich zu lang (mehr als 65 Zeichen)
            if ($len > 65) {
                // Intelligentes Kürzen: Suche nach dem letzten Leerzeichen vor Position 60
                $cut_pos = 60;
                while ($cut_pos > 40 && mb_substr($title, $cut_pos, 1) !== ' ') {
                    $cut_pos--;
                }
                $seo_suite['meta_title'] = mb_substr($title, 0, $cut_pos);
                error_log("ReTexify: Meta-Titel gekürzt von {$len} auf {$cut_pos} Zeichen");
            }
        }
        
        // Meta-Beschreibung: NUR kürzen wenn wirklich zu lang (>165 Zeichen)
        if (isset($seo_suite['meta_description'])) {
            $desc = $seo_suite['meta_description'];
            $len = mb_strlen($desc);
            
            // Nur kürzen wenn deutlich zu lang (mehr als 165 Zeichen)
            if ($len > 165) {
                // Intelligentes Kürzen: Suche nach dem letzten Leerzeichen vor Position 160
                $cut_pos = 160;
                while ($cut_pos > 120 && mb_substr($desc, $cut_pos, 1) !== ' ') {
                    $cut_pos--;
                }
                $seo_suite['meta_description'] = mb_substr($desc, 0, $cut_pos);
                error_log("ReTexify: Meta-Beschreibung gekürzt von {$len} auf {$cut_pos} Zeichen");
            }
        }
        
        return $seo_suite;
    }

    // ========================================================================
    // 🆕 AJAX HANDLER: POSTS OHNE SEO-DATEN FILTERN
    // ========================================================================

    /**
     * AJAX: Posts ohne SEO-Daten abrufen
     */
    public function ajax_get_posts_without_seo() {
        check_ajax_referer('retexify_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        try {
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'any');
            
            // Query für Posts OHNE SEO-Daten
            $args = array(
                'post_type' => $post_type === 'any' ? array('post', 'page', 'attachment') : $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => '_yoast_wpseo_title',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => '_yoast_wpseo_title',
                        'value' => '',
                        'compare' => '='
                    )
                )
            );
            
            $posts = get_posts($args);
            
            $result = array(
                'total' => count($posts),
                'posts' => array_map(function($post) {
                    return array(
                        'ID' => $post->ID,
                        'title' => $post->post_title,
                        'type' => $post->post_type,
                        'status' => $post->post_status
                    );
                }, $posts)
            );
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    // ========================================================================
    // 🆕 AJAX HANDLER: BULK-GENERIERUNG
    // ========================================================================

    /**
     * AJAX: Bulk SEO-Generierung für mehrere Posts
     */
    public function ajax_bulk_generate_seo() {
        check_ajax_referer('retexify_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        try {
            $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
            $only_empty = isset($_POST['only_empty']) ? (bool)$_POST['only_empty'] : false;
            
            if (empty($post_ids)) {
                throw new Exception('Keine Posts ausgewählt');
            }
            
            $results = array(
                'success' => 0,
                'failed' => 0,
                'skipped' => 0,
                'details' => array()
            );
            
            $settings = $this->get_ai_settings();
            
            foreach ($post_ids as $post_id) {
                $post = get_post($post_id);
                if (!$post) {
                    $results['failed']++;
                    continue;
                }
                
                // Wenn only_empty: Prüfe ob bereits SEO-Daten vorhanden
                if ($only_empty) {
                    $existing_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
                    if (!empty($existing_title)) {
                        $results['skipped']++;
                        continue;
                    }
                }
                
                try {
                    // SEO generieren
                    $seo_data = $this->generate_intelligent_seo_suite($post, $settings, true, false);
                    
                    // Validieren
                    $seo_data = $this->validate_seo_semantics($seo_data, $post);
                    
                    // Speichern (Yoast SEO)
                    update_post_meta($post_id, '_yoast_wpseo_title', $seo_data['meta_title']);
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_data['meta_description']);
                    update_post_meta($post_id, '_yoast_wpseo_focuskw', $seo_data['focus_keyword']);
                    
                    $results['success']++;
                    $results['details'][] = array(
                        'post_id' => $post_id,
                        'title' => $post->post_title,
                        'status' => 'success'
                    );
                    
                    // Rate-Limiting: 2 Sekunden Pause zwischen Requests
                    sleep(2);
                    
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['details'][] = array(
                        'post_id' => $post_id,
                        'title' => $post->post_title,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    );
                }
            }
            
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function build_intelligent_seo_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone) {
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        
        // 🆕 VERBESSERTE Content-Analyse
        $page_context = $this->analyze_page_context($post, $settings);
        $content_keywords = $this->extract_content_keywords($title, $content);
        
        // 🚨 DEBUG: Content-Analyse Logging
        error_log('ReTexify Main Content-Analyse DEBUG:');
        error_log('- Titel: ' . $title);
        error_log('- Erkannte Kategorie: ' . ($content_keywords['detected_category'] ?? 'KEINE'));
        error_log('- Haupt-Keywords: ' . implode(', ', $content_keywords['main_keywords'] ?? []));
        error_log('- Vertrauen: ' . ($content_keywords['confidence'] ?? 0));
        error_log('- Seiten-Typ: ' . ($page_context['page_type'] ?? 'unbekannt'));
        
        // Business-Kontext NUR verwenden wenn NICHT Legal-Seite
        $business_text = '';
        $canton_text = '';
        
        if ($page_context['use_business_context']) {
            $business_context = !empty($settings['business_context']) 
                ? $settings['business_context'] 
                : 'Schweizer Unternehmen';
            $business_text = "\n=== BUSINESS-KONTEXT ===\n{$business_context}";
        }
        
        if ($page_context['use_cantons'] && !empty($settings['target_cantons'])) {
            // ✅ FIX: Kantone-Namen statt Abkürzungen verwenden!
            $canton_names = $this->get_canton_names($settings['target_cantons']);
            if (!empty($canton_names)) {
                $canton_text = "\nZiel-Kantone: " . implode(', ', $canton_names);
            }
        }
        $tone_instruction = $premium_tone ? 'Verwende einen premium, professionellen Business-Ton' : 'Verwende einen freundlichen, professionellen Ton';
        $primary_keywords = !empty($analysis['primary_keywords']) ? implode(', ', array_slice($analysis['primary_keywords'], 0, 5)) : '';
        $focus_keyword_suggestion = !empty($analysis['keyword_strategy']['focus_keyword']) ? $analysis['keyword_strategy']['focus_keyword'] : '';
        $long_tail_keywords = !empty($analysis['long_tail_keywords']) ? implode(', ', array_slice($analysis['long_tail_keywords'], 0, 3)) : '';
        $semantic_themes = !empty($analysis['semantic_themes']) ? implode(', ', array_slice($analysis['semantic_themes'], 0, 3)) : '';
        $prompt = "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst hochwertige SEO-Texte basierend auf Content-Analyse.

=== CONTENT-INFORMATIONEN ===
Titel: {$title}
Content: " . substr($content, 0, 1000) . "

=== CONTENT-ANALYSE ===
Erkannte Kategorie: " . ($content_keywords['detected_category'] ?? 'unbekannt') . "
Haupt-Keywords: " . implode(', ', $content_keywords['main_keywords']) . "
Vertrauen: " . ($content_keywords['confidence'] ?? 0) . "

=== SEITEN-TYP-ANALYSE ===
Seiten-Typ: " . $page_context['page_type'] . "
SEO-Strategie: " . $page_context['seo_strategy'] . "

🚨 KRITISCHE ANWEISUNG:
" . $page_context['instructions'] . "

🚨 CONTENT-REGEL:
Verwende NUR Keywords aus der erkannten Kategorie!
- Falls Kategorie 'griffe' → NUR Griffe-Keywords verwenden!
- Falls Kategorie 'neolith' → NUR Neolith-Keywords verwenden!
- Falls Kategorie 'küche' → NUR Küchen-Keywords verwenden!
NIEMALS falsche Produktkategorien mischen!

" . $business_text . "
" . $canton_text . "

=== KEYWORD-ANFORDERUNGEN ===
Focus-Keyword MUSS:
- PRODUKT/SERVICE-spezifisch sein (KEINE Adjektive wie 'pflegeleicht', 'hochwertig')
- Suchvolumen haben (Was googelt der Kunde?)
- Zum Seiten-Typ passen

❌ SCHLECHTE Keywords: pflegeleicht, modern, hochwertig
✅ GUTE Keywords: Neolith Keramik, Küchenplanung Bern, Keramik Arbeitsplatte

=== KANTONE-REGEL ===
ZWINGEND: Kantone IMMER ausgeschrieben!
❌ FALSCH: BE, SO, ZH
✅ RICHTIG: Bern, Solothurn, Zürich

=== AUFGABE ===
Erstelle basierend auf der obigen ANALYSE eine SEO-Suite:

🎯 TEXTLÄNGEN-ANFORDERUNGEN (KRITISCH):

1. **META_TITEL**:
   - OPTIMAL: 55-65 Zeichen (NICHT genau 60!)
   - Nutze den verfügbaren Platz intelligent!
   - Focus-Keyword intelligent integriert
   - WICHTIG: Vollständige Sätze, keine Abkürzungen!

2. **META_BESCHREIBUNG**:
   - OPTIMAL: 150-165 Zeichen (NICHT genau 160!)
   - Fülle den Platz intelligent aus!
   - Klarer Call-to-Action
   - WICHTIG: Vollständige Sätze, Kantone IMMER AUSGESCHRIEBEN!
   - NIEMALS: Bern und S..... → IMMER: Bern und Solothurn

3. **FOCUS_KEYWORD** (1-4 Wörter):
   - PRODUKT/SERVICE-spezifische Begriffe
   - Vermeide generische Adjektive
   - Hohes kommerzielles Suchvolumen

=== ANTWORT-FORMAT (exakt so) ===
META_TITEL: [dein optimierter Meta-Titel]
META_BESCHREIBUNG: [deine optimierte Meta-Beschreibung]
FOCUS_KEYWORD: [dein optimiertes Focus-Keyword]

🚨 KRITISCHE SEO-REGELN (ZWINGEND):
1. Kantone IMMER ausgeschrieben (NIEMALS Abkürzungen wie BE, SO)
2. Focus-Keyword muss PRODUKT/SERVICE sein (KEINE Adjektive wie \"pflegeleicht\")
3. Keywords müssen Suchvolumen haben (Denke: \"Was googelt der Kunde?\")
4. Meta-Beschreibung MUSS Call-to-Action enthalten

Wichtig: Antworte NUR mit den drei Zeilen im angegebenen Format, nichts anderes!";
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
    
    /**
     * ⚡ NEU: Advanced SEO Generation mit erweiterten Analyse-Daten
     */
    private function generate_advanced_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false, $advanced_data = null) {
        error_log('ReTexify: Starting ADVANCED SEO generation with enhanced analysis data');
        
        try {
            // Advanced Prompt Builder verwenden
            if (!class_exists('ReTexify_Advanced_Prompt_Builder')) {
                error_log('ReTexify: Advanced Prompt Builder not available, falling back to intelligent generation');
                return $this->generate_intelligent_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            
            $prompt_builder = new ReTexify_Advanced_Prompt_Builder(
                $this->ai_engine,
                new ReTexify_Advanced_Content_Analyzer(),
                new ReTexify_Serp_Competitor_Analyzer(),
                new ReTexify_Intelligent_Keyword_Research()
            );
            
            // Erweiterte Settings für Advanced Generation
            $advanced_settings = array_merge($settings, array(
                'focus_keyword' => $advanced_data['keyword'] ?? '',
                'selected_cantons' => get_option('retexify_target_cantons', array()),
                'location' => 'CH',
                'include_cantons' => $include_cantons,
                'premium_tone' => $premium_tone
            ));
            
            // Advanced SEO mit allen verfügbaren Daten generieren
            $advanced_result = $prompt_builder->build_advanced_seo_prompt($post->ID, $advanced_settings);
            
            if (!empty($advanced_result['generated_content'])) {
                $generated = $advanced_result['generated_content'];
                
                $results = array(
                    'meta_title' => $generated['meta_title'] ?? '',
                    'meta_description' => $generated['meta_description'] ?? '',
                    'focus_keyword' => $generated['focus_keyword'] ?? $advanced_data['keyword'] ?? '',
                    'advanced_used' => true,
                    'research_mode' => 'advanced',
                    'analysis_data' => $advanced_data,
                    'reasoning' => $generated['reasoning'] ?? '',
                    'local_optimization' => $generated['local_optimization'] ?? '',
                    'cta_strategy' => $generated['cta_strategy'] ?? '',
                    'seo_score' => $advanced_data['content_analysis']['seo_score'] ?? 0,
                    'validation' => $generated['validation'] ?? array()
                );
                
                error_log('ReTexify: Advanced SEO generation completed successfully');
                return $results;
            } else {
                error_log('ReTexify: Advanced generation returned empty results, falling back');
                return $this->generate_intelligent_seo_suite($post, $settings, $include_cantons, $premium_tone);
            }
            
        } catch (Exception $e) {
            error_log('ReTexify: Exception in advanced SEO generation: ' . $e->getMessage());
            return $this->generate_intelligent_seo_suite($post, $settings, $include_cantons, $premium_tone);
        }
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
    
    // ===== ⚡ ADVANCED SEO FEATURES - NEUE AJAX-HANDLER =====
    
    /**
     * AJAX Handler für Advanced Content Analysis
     * Führt vor der SEO-Generierung eine umfassende Analyse durch
     */
    public function ajax_advanced_content_analysis() {
        // ✅ SICHERE VALIDIERUNG mit Rate-Limiting
        if (!$this->validate_ajax_request('advanced_analysis')) {
            return; // validate_ajax_request sendet bereits Response
        }
        
        try {
            $post_id = intval($_POST['post_id']);
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            
            // Prüfe ob Post existiert
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(['message' => 'Post nicht gefunden']);
                return;
            }
            
            // Advanced Content Analyzer initialisieren
            if (!class_exists('ReTexify_Advanced_Content_Analyzer')) {
                wp_send_json_error(['message' => 'Advanced Content Analyzer nicht verfügbar']);
                return;
            }
            
            $analyzer = new ReTexify_Advanced_Content_Analyzer();
            
            // Vollständige Analyse durchführen
            $analysis_result = $analyzer->analyze_post_content($post_id, $keyword);
            
            // Keyword Research hinzufügen
            if (!empty($keyword) && class_exists('ReTexify_Intelligent_Keyword_Research')) {
                $keyword_research = ReTexify_Intelligent_Keyword_Research::get_related_keywords($keyword, 10);
                $lsi_keywords = ReTexify_Intelligent_Keyword_Research::generate_lsi_keywords($keyword);
                $long_tail_keywords = ReTexify_Intelligent_Keyword_Research::generate_long_tail_keywords($keyword, 8);
                
                $analysis_result['keyword_research'] = array(
                    'related_keywords' => $keyword_research,
                    'lsi_keywords' => $lsi_keywords,
                    'long_tail_keywords' => $long_tail_keywords,
                    'search_intent' => ReTexify_Intelligent_Keyword_Research::classify_search_intent($keyword),
                    'difficulty' => ReTexify_Intelligent_Keyword_Research::estimate_keyword_difficulty($keyword)
                );
            }
            
            wp_send_json_success($analysis_result);
            
        } catch (Exception $e) {
            // ✅ ERROR-HANDLING mit zentralem Error-Handler
            ReTexify_Error_Handler::log_ajax_error(
                'retexify_advanced_content_analysis',
                'Advanced Content Analysis fehlgeschlagen',
                array(
                    'post_id' => $post_id ?? null,
                    'keyword' => $keyword ?? null,
                    'error' => $e->getMessage()
                ),
                ReTexify_Error_Handler::LEVEL_ERROR
            );
            
            wp_send_json_error([
                'message' => 'Analyse fehlgeschlagen: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * AJAX Handler für SERP Competitor Analysis
     */
    public function ajax_serp_competitor_analysis() {
        // ✅ SICHERE VALIDIERUNG mit Rate-Limiting
        if (!$this->validate_ajax_request('serp_analysis')) {
            return;
        }
        
        try {
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            $location = sanitize_text_field($_POST['location'] ?? 'CH');
            
            if (empty($keyword)) {
                wp_send_json_error(['message' => 'Keyword erforderlich']);
                return;
            }
            
            // SERP Competitor Analyzer initialisieren
            if (!class_exists('ReTexify_Serp_Competitor_Analyzer')) {
                wp_send_json_error(['message' => 'SERP Competitor Analyzer nicht verfügbar']);
                return;
            }
            
            $analyzer = new ReTexify_Serp_Competitor_Analyzer();
            
            // SERP-Analyse durchführen
            $serp_result = $analyzer->analyze_serp($keyword, $location, 10);
            
            wp_send_json_success($serp_result);
            
        } catch (Exception $e) {
            ReTexify_Error_Handler::log_ajax_error(
                'retexify_serp_competitor_analysis',
                'SERP Competitor Analysis fehlgeschlagen',
                array(
                    'keyword' => $keyword ?? null,
                    'location' => $location ?? null,
                    'error' => $e->getMessage()
                ),
                ReTexify_Error_Handler::LEVEL_ERROR
            );
            
            wp_send_json_error([
                'message' => 'SERP-Analyse fehlgeschlagen: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * AJAX Handler für Advanced SEO Generation
     */
    public function ajax_generate_advanced_seo() {
        // ✅ SICHERE VALIDIERUNG mit Rate-Limiting
        if (!$this->validate_ajax_request('generate_advanced_seo')) {
            return;
        }
        
        try {
            $post_id = intval($_POST['post_id']);
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            
            // Prüfe ob Post existiert
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(['message' => 'Post nicht gefunden']);
                return;
            }
            
            // Advanced Prompt Builder initialisieren
            if (!class_exists('ReTexify_Advanced_Prompt_Builder')) {
                wp_send_json_error(['message' => 'Advanced Prompt Builder nicht verfügbar']);
                return;
            }
            
            // Plugin-Einstellungen laden
            $settings = get_option('retexify_ai_settings', array());
            $settings['focus_keyword'] = $keyword;
            $settings['selected_cantons'] = get_option('retexify_target_cantons', array());
            $settings['location'] = 'CH';
            
            $prompt_builder = new ReTexify_Advanced_Prompt_Builder(
                $this->ai_engine,
                new ReTexify_Advanced_Content_Analyzer(),
                new ReTexify_Serp_Competitor_Analyzer(),
                new ReTexify_Intelligent_Keyword_Research()
            );
            
            // Advanced SEO mit allen verfügbaren Daten generieren
            $advanced_result = $prompt_builder->build_advanced_seo_prompt($post_id, $settings);
            
            wp_send_json_success($advanced_result);
            
        } catch (Exception $e) {
            ReTexify_Error_Handler::log_ajax_error(
                'retexify_generate_advanced_seo',
                'Advanced SEO Generation fehlgeschlagen',
                array(
                    'post_id' => $post_id ?? null,
                    'keyword' => $keyword ?? null,
                    'error' => $e->getMessage()
                ),
                ReTexify_Error_Handler::LEVEL_ERROR
            );
            
            wp_send_json_error([
                'message' => 'Advanced SEO-Generierung fehlgeschlagen: ' . $e->getMessage()
            ]);
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
