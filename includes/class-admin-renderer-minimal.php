<?php
/**
 * ReTexify Admin Renderer - Minimal Version
 * 
 * Fallback-Admin-Interface falls die Hauptversion nicht lädt
 * 
 * @package ReTexify_AI
 * @since 4.10.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Admin_Renderer_Minimal {
    
    private $ai_engine;
    private $export_import_manager;
    
    public function __construct($ai_engine = null, $export_import_manager = null) {
        $this->ai_engine = $ai_engine;
        $this->export_import_manager = $export_import_manager;
    }
    
    public function render_admin_page() {
        $ai_settings = get_option('retexify_ai_settings', array());
        $api_keys = get_option('retexify_api_keys', array());
        $ai_enabled = !empty($api_keys[$ai_settings['api_provider'] ?? 'openai']);
        
        ?>
        <div class="wrap">
            <h1>🇨🇭 ReTexify AI - Universeller SEO-Optimizer</h1>
            <p class="description">Version <?php echo RETEXIFY_VERSION ?? '4.11.0'; ?> | Minimal Interface</p>
            
            <div class="notice notice-info">
                <p><strong>Info:</strong> Das Plugin läuft im Minimal-Modus. Alle Kernfunktionen sind verfügbar.</p>
            </div>
            
            <div class="retexify-minimal-dashboard">
                
                <!-- Status Karte -->
                <div class="postbox" style="margin: 20px 0;">
                    <div class="postbox-header">
                        <h2 class="hndle">🔧 System-Status</h2>
                    </div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th>KI-Status:</th>
                                <td><?php echo $ai_enabled ? '✅ Aktiv' : '❌ Nicht konfiguriert'; ?></td>
                            </tr>
                            <tr>
                                <th>Provider:</th>
                                <td><?php echo ucfirst($ai_settings['api_provider'] ?? 'openai'); ?></td>
                            </tr>
                            <tr>
                                <th>WordPress:</th>
                                <td><?php echo get_bloginfo('version'); ?></td>
                            </tr>
                            <tr>
                                <th>PHP:</th>
                                <td><?php echo PHP_VERSION; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- KI-Einstellungen -->
                <div class="postbox" style="margin: 20px 0;">
                    <div class="postbox-header">
                        <h2 class="hndle">⚙️ KI-Einstellungen</h2>
                    </div>
                    <div class="inside">
                        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                            <?php wp_nonce_field('retexify_nonce', 'nonce'); ?>
                            <input type="hidden" name="action" value="retexify_save_api_key">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Provider:</th>
                                    <td>
                                        <select name="provider">
                                            <option value="openai" <?php selected($ai_settings['api_provider'] ?? 'openai', 'openai'); ?>>OpenAI (GPT-4o)</option>
                                            <option value="anthropic" <?php selected($ai_settings['api_provider'] ?? '', 'anthropic'); ?>>Anthropic Claude</option>
                                            <option value="gemini" <?php selected($ai_settings['api_provider'] ?? '', 'gemini'); ?>>Google Gemini</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">API-Schlüssel:</th>
                                    <td>
                                        <input type="password" name="api_key" class="regular-text" 
                                               placeholder="Geben Sie Ihren API-Schlüssel ein"
                                               value="<?php echo !empty($api_keys[$ai_settings['api_provider'] ?? 'openai']) ? '••••••••••••' : ''; ?>">
                                        <p class="description">Ihr API-Schlüssel wird sicher gespeichert.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">Modell:</th>
                                    <td>
                                        <select name="model">
                                            <option value="gpt-4o" <?php selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o'); ?>>GPT-4o</option>
                                            <option value="gpt-4o-mini" <?php selected($ai_settings['model'] ?? 'gpt-4o-mini', 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                            <option value="claude-3-5-sonnet" <?php selected($ai_settings['model'] ?? '', 'claude-3-5-sonnet'); ?>>Claude 3.5 Sonnet</option>
                                            <option value="gemini-1.5-pro" <?php selected($ai_settings['model'] ?? '', 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <input type="submit" class="button-primary" value="Einstellungen speichern">
                            </p>
                        </form>
                    </div>
                </div>
                
                <!-- SEO-Test -->
                <div class="postbox" style="margin: 20px 0;">
                    <div class="postbox-header">
                        <h2 class="hndle">🚀 SEO-Test</h2>
                    </div>
                    <div class="inside">
                        <?php if ($ai_enabled): ?>
                            <p>✅ KI ist konfiguriert und bereit für SEO-Generierung.</p>
                            <p>
                                <button type="button" class="button" onclick="testSEO()">API-Verbindung testen</button>
                                <span id="test-result"></span>
                            </p>
                        <?php else: ?>
                            <p>❌ Bitte konfigurieren Sie zuerst einen API-Schlüssel.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Support -->
                <div class="postbox" style="margin: 20px 0;">
                    <div class="postbox-header">
                        <h2 class="hndle">🆘 Support</h2>
                    </div>
                    <div class="inside">
                        <p><strong>Bei Problemen:</strong></p>
                        <ul>
                            <li>Überprüfen Sie die WordPress Debug-Logs</li>
                            <li>Stellen Sie sicher, dass alle Plugin-Dateien korrekt hochgeladen wurden</li>
                            <li>Kontaktieren Sie den Support mit den Debug-Informationen</li>
                        </ul>
                        
                        <h4>Debug-Informationen:</h4>
                        <textarea readonly style="width: 100%; height: 100px; font-family: monospace;">
Plugin-Version: <?php echo RETEXIFY_VERSION ?? 'Unknown'; ?>

WordPress-Version: <?php echo get_bloginfo('version'); ?>
PHP-Version: <?php echo PHP_VERSION; ?>

Verfügbare Klassen:
<?php 
$classes = get_declared_classes();
$retexify_classes = array_filter($classes, function($class) {
    return strpos($class, 'ReTexify') === 0;
});
echo implode(', ', $retexify_classes);
?>

Plugin-Pfad: <?php echo RETEXIFY_PLUGIN_PATH ?? 'Unknown'; ?>
                        </textarea>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
        .retexify-minimal-dashboard .postbox {
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        .retexify-minimal-dashboard .postbox-header {
            background: #f1f1f1;
            padding: 10px 15px;
            border-bottom: 1px solid #ccd0d4;
        }
        .retexify-minimal-dashboard .postbox-header .hndle {
            margin: 0;
            font-size: 14px;
        }
        .retexify-minimal-dashboard .inside {
            padding: 15px;
        }
        </style>
        
        <script>
        function testSEO() {
            const resultSpan = document.getElementById('test-result');
            resultSpan.innerHTML = '⏳ Teste...';
            
            jQuery.post(ajaxurl, {
                action: 'retexify_test_api_connection',
                nonce: '<?php echo wp_create_nonce('retexify_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    resultSpan.innerHTML = '✅ ' + response.data;
                } else {
                    resultSpan.innerHTML = '❌ ' + response.data;
                }
            }).fail(function() {
                resultSpan.innerHTML = '❌ AJAX-Fehler';
            });
        }
        </script>
        <?php
    }
}
