<?php
/**
 * ReTexify AI - System Status Handler (OPTIMIERTE VERSION)
 * Verwaltet alle System-Status-Abfragen zentral mit modernem Design
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_System_Status {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 🆕 NEUE METHODE: Moderne System-Status-Tests (die in retexify.php aufgerufen wird)
     */
    public function get_modern_status_tests() {
        // WordPress-Test
        $wp_version = get_bloginfo('version');
        $tests['wordpress'] = array(
            'name' => 'WordPress',
            'status' => version_compare($wp_version, '5.0', '>=') ? 'success' : 'warning',
            'message' => 'Version ' . $wp_version,
            'details' => version_compare($wp_version, '5.0', '>=') ? 'WordPress läuft einwandfrei' : 'WordPress-Version veraltet'
        );
        
        // PHP-Test
        $php_version = phpversion();
        $tests['php'] = array(
            'name' => 'PHP',
            'status' => version_compare($php_version, '7.4', '>=') ? 'success' : 'warning',
            'message' => 'Version ' . $php_version,
            'details' => version_compare($php_version, '7.4', '>=') ? 'PHP-Version kompatibel' : 'PHP-Version veraltet'
        );
        
        // cURL-Test
        $tests['curl'] = array(
            'name' => 'cURL',
            'status' => function_exists('curl_init') ? 'success' : 'error',
            'message' => function_exists('curl_init') ? 'Verfügbar' : 'Nicht verfügbar',
            'details' => function_exists('curl_init') ? 'cURL für API-Calls verfügbar' : 'cURL erforderlich für API-Calls'
        );
        
        // JSON-Test
        $tests['json'] = array(
            'name' => 'JSON',
            'status' => function_exists('json_encode') ? 'success' : 'error',
            'message' => function_exists('json_encode') ? 'Verfügbar' : 'Nicht verfügbar',
            'details' => function_exists('json_encode') ? 'JSON-Funktionen verfügbar' : 'JSON-Funktionen erforderlich'
        );
        
        // Memory-Limit-Test
        $memory_limit = ini_get('memory_limit');
        $memory_mb = intval($memory_limit);
        $tests['memory'] = array(
            'name' => 'Memory Limit',
            'status' => $memory_mb >= 128 ? 'success' : 'warning',
            'message' => $memory_limit,
            'details' => $memory_mb >= 128 ? 'Ausreichend Speicher' : 'Wenig Speicher verfügbar'
        );
        
        // Plugin-Version-Test
        $plugin_version = defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '4.23.0';
        $tests['plugin'] = array(
            'name' => 'Plugin Version',
            'status' => 'success',
            'message' => 'v' . $plugin_version,
            'details' => 'ReTexify AI aktiv und bereit'
        );
        
        // KI-API-Keys-Test
        $api_keys = get_option('retexify_api_keys', array());
        $has_api_keys = !empty($api_keys['openai']) || !empty($api_keys['anthropic']) || !empty($api_keys['gemini']);
        $tests['ai_keys'] = array(
            'name' => 'KI-API-Keys',
            'status' => $has_api_keys ? 'success' : 'warning',
            'message' => $has_api_keys ? 'Konfiguriert' : 'Nicht konfiguriert',
            'details' => $has_api_keys ? 'API-Schlüssel verfügbar' : 'API-Schlüssel in KI-Einstellungen hinzufügen'
        );
        
        // Export/Import-Test
        $upload_dir = wp_upload_dir();
        $retexify_dir = $upload_dir['basedir'] . '/retexify-ai/';
        $tests['export_import'] = array(
            'name' => 'Export/Import',
            'status' => is_writable($retexify_dir) ? 'success' : 'warning',
            'message' => is_writable($retexify_dir) ? 'Verfügbar' : 'Eingeschränkt',
            'details' => is_writable($retexify_dir) ? 'Upload-Verzeichnis beschreibbar' : 'Upload-Verzeichnis nicht beschreibbar'
        );
        
        return $tests;
    }
    
    /**
     * 🆕 RESEARCH-ENGINE STATUS (für Research-Tab)
     */
    public function get_research_status_tests() {
        $tests = array();
        
        // Google Suggest API
        $tests['google_suggest'] = array(
            'name' => 'Google Suggest',
            'status' => $this->test_google_suggest() ? 'success' : 'error',
            'message' => $this->test_google_suggest() ? 'Verfügbar' : 'Nicht erreichbar',
            'details' => 'Keyword-Vorschläge via Google'
        );
        
        // Wikipedia API
        $tests['wikipedia'] = array(
            'name' => 'Wikipedia',
            'status' => $this->test_wikipedia() ? 'success' : 'error',
            'message' => $this->test_wikipedia() ? 'Verfügbar' : 'Nicht erreichbar',
            'details' => 'Content-Informationen via Wikipedia'
        );
        
        // Wiktionary API
        $tests['wiktionary'] = array(
            'name' => 'Wiktionary',
            'status' => $this->test_wiktionary() ? 'success' : 'error',
            'message' => $this->test_wiktionary() ? 'Verfügbar' : 'Nicht erreichbar',
            'details' => 'Wortdefinitionen via Wiktionary'
        );
        
        // OpenStreetMap API
        $tests['openstreetmap'] = array(
            'name' => 'OpenStreetMap',
            'status' => $this->test_openstreetmap() ? 'success' : 'error',
            'message' => $this->test_openstreetmap() ? 'Verfügbar' : 'Nicht erreichbar',
            'details' => 'Geografische Daten für Local SEO'
        );
        
        return $tests;
    }
    
    /**
     * 🆕 NEUE HAUPTMETHODE: Einheitlicher moderner System-Status
     * Ersetzt beide alten Methoden und vereint alles in einem Design
     */
    public function generate_unified_modern_status_html($system_tests = null, $research_tests = null) {
        if (!$system_tests) {
            $system_tests = $this->get_modern_status_tests();
        }
        if (!$research_tests) {
            $research_tests = $this->get_research_status_tests();
        }
        
        // Status-Statistiken berechnen
        $total_tests = count($system_tests) + count($research_tests);
        $success_count = 0;
        $warning_count = 0;
        $error_count = 0;
        
        foreach ($system_tests as $test) {
            if ($test['status'] === 'success') $success_count++;
            elseif ($test['status'] === 'warning') $warning_count++;
            else $error_count++;
        }
        
        foreach ($research_tests as $test) {
            if ($test['status'] === 'success') $success_count++;
            elseif ($test['status'] === 'warning') $warning_count++;
            else $error_count++;
        }
        
        $overall_status = $error_count > 0 ? 'error' : ($warning_count > 0 ? 'warning' : 'success');
        
        ob_start();
        ?>
        <div class="retexify-modern-system-container">
            <!-- Header -->
            <div class="retexify-status-header">
                <div class="retexify-status-header-left">
                    <div class="retexify-status-icon">
                        🖥️
                    </div>
                    <div>
                        <h3 class="retexify-status-title">System-Status</h3>
                        <p class="retexify-status-subtitle">
                            <?php echo $total_tests; ?> Tests durchgeführt • 
                            <?php echo $success_count; ?> erfolgreich • 
                            <?php if ($warning_count > 0) echo $warning_count . ' Warnungen • '; ?>
                            <?php if ($error_count > 0) echo $error_count . ' Fehler'; ?>
                        </p>
                    </div>
                </div>
                <div class="retexify-status-header-right">
                    <button type="button" class="retexify-test-button" id="retexify-test-system-badge">
                        Tests erneuern
                    </button>
                </div>
            </div>
            
            <!-- System-Status Cards -->
            <div class="retexify-status-grid">
                <?php foreach ($system_tests as $key => $test): ?>
                    <div class="retexify-status-card status-<?php echo esc_attr($test['status']); ?>">
                        <div class="retexify-card-header">
                            <h4 class="retexify-card-title"><?php echo esc_html($test['name']); ?></h4>
                            <span class="retexify-card-status status-<?php echo esc_attr($test['status']); ?>">
                                <?php echo $this->get_status_text($test['status']); ?>
                            </span>
                        </div>
                        <div class="retexify-card-content">
                            <p class="retexify-card-message"><?php echo esc_html($test['message']); ?></p>
                            <?php if (!empty($test['details'])): ?>
                                <p class="retexify-card-details"><?php echo esc_html($test['details']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Research Engine Section -->
            <?php if (!empty($research_tests)): ?>
                <div class="retexify-research-section">
                    <div class="retexify-research-header">
                        <h4 class="retexify-research-title">
                            <span class="retexify-research-icon">🧠</span>
                            Intelligent Research Engine
                        </h4>
                        <button type="button" class="retexify-test-button" id="retexify-test-research-badge">
                            APIs testen
                        </button>
                    </div>
                    
                    <div class="retexify-research-grid">
                        <?php foreach ($research_tests as $key => $test): ?>
                            <div class="retexify-research-card">
                                <div class="retexify-research-card-header">
                                    <span class="retexify-research-card-title"><?php echo esc_html($test['name']); ?></span>
                                    <span class="retexify-research-card-status retexify-card-status status-<?php echo esc_attr($test['status']); ?>">
                                        <?php echo $this->get_status_text($test['status']); ?>
                                    </span>
                                </div>
                                <p class="retexify-research-card-message"><?php echo esc_html($test['message']); ?></p>
                                <?php if (!empty($test['details'])): ?>
                                    <p class="retexify-research-card-details"><?php echo esc_html($test['details']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Summary Statistics -->
            <div class="retexify-summary-section">
                <div class="retexify-summary-stat">
                    <div class="retexify-summary-number"><?php echo $total_tests; ?></div>
                    <div class="retexify-summary-label">Gesamt Tests</div>
                </div>
                <div class="retexify-summary-stat">
                    <div class="retexify-summary-number retexify-text-success"><?php echo $success_count; ?></div>
                    <div class="retexify-summary-label">Erfolgreich</div>
                </div>
                <?php if ($warning_count > 0): ?>
                    <div class="retexify-summary-stat">
                        <div class="retexify-summary-number retexify-text-warning"><?php echo $warning_count; ?></div>
                        <div class="retexify-summary-label">Warnungen</div>
                    </div>
                <?php endif; ?>
                <?php if ($error_count > 0): ?>
                    <div class="retexify-summary-stat">
                        <div class="retexify-summary-number retexify-text-error"><?php echo $error_count; ?></div>
                        <div class="retexify-summary-label">Fehler</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * 🆕 AKTUALISIERTE METHODE: Moderne System-Status HTML-Generierung
     * Ersetzt die alte generate_modern_system_status_html Methode
     */
    public static function generate_modern_system_status_html($tests) {
        $instance = self::get_instance();
        return $instance->generate_unified_modern_status_html($tests, null);
    }

    /**
     * 🆕 AKTUALISIERTE METHODE: Research-Status HTML-Generierung  
     * Ersetzt die alte generate_research_status_html Methode
     */
    public static function generate_research_status_html($tests) {
        $instance = self::get_instance();
        return $instance->generate_unified_modern_status_html(null, $tests);
    }

    /**
     * 🆕 HELPER-METHODEN für das moderne Design
     */
    private function get_status_emoji($status) {
        switch ($status) {
            case 'success': return '✅';
            case 'warning': return '⚠️';
            case 'error': return '🔴';
            case 'info': return 'ℹ️';
            default: return '❓';
        }
    }

    private function get_status_text($status) {
        switch ($status) {
            case 'success': return 'OK';
            case 'warning': return 'Warnung';
            case 'error': return 'Fehler';
            case 'info': return 'Info';
            default: return 'Unbekannt';
        }
    }

    // ========================================================================
    // 🔧 BESTEHENDE METHODEN (unverändert für Kompatibilität)
    // ========================================================================
    
    /**
     * Vollständigen System-Status abrufen (bestehende Methode)
     */
    public function get_system_status() {
        $status = array(
            'wordpress' => $this->get_wordpress_info(),
            'plugin' => $this->get_plugin_info(),
            'php' => $this->get_php_info(),
            'apis' => $this->test_api_connections(),
            'research' => $this->test_research_apis()
        );
        
        return $status;
    }
    
    /**
     * Research-APIs testen (bestehende Methode)
     */
    public function test_research_apis() {
        return array(
            'google_suggest' => $this->test_google_suggest(),
            'wikipedia' => $this->test_wikipedia(),
            'wiktionary' => $this->test_wiktionary(),
            'openstreetmap' => $this->test_openstreetmap()
        );
    }
    
    /**
     * WordPress-Informationen
     */
    private function get_wordpress_info() {
        global $wp_version;
        
        return array(
            'version' => $wp_version,
            'multisite' => is_multisite(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize')
        );
    }
    
    /**
     * Plugin-Informationen
     */
    private function get_plugin_info() {
        return array(
            'version' => defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '4.23.0',
            'path' => defined('RETEXIFY_PLUGIN_PATH') ? RETEXIFY_PLUGIN_PATH : plugin_dir_path(__FILE__),
            'url' => defined('RETEXIFY_PLUGIN_URL') ? RETEXIFY_PLUGIN_URL : plugin_dir_url(__FILE__),
            'active_since' => get_option('retexify_activation_time', 'Unbekannt')
        );
    }
    
    /**
     * PHP-Informationen
     */
    private function get_php_info() {
        return array(
            'version' => phpversion(),
            'curl_enabled' => function_exists('curl_init'),
            'json_enabled' => function_exists('json_encode'),
            'openssl_enabled' => extension_loaded('openssl'),
            'mbstring_enabled' => extension_loaded('mbstring')
        );
    }
    
    /**
     * KI-API-Verbindungen testen (schnell)
     */
    private function test_api_connections() {
        $api_keys = get_option('retexify_api_keys', array());
        $connections = array();
        
        // OpenAI testen
        if (!empty($api_keys['openai'])) {
            $connections['openai'] = $this->quick_test_openai($api_keys['openai']);
        } else {
            $connections['openai'] = false;
        }
        
        // Anthropic testen
        if (!empty($api_keys['anthropic'])) {
            $connections['anthropic'] = $this->quick_test_anthropic($api_keys['anthropic']);
        } else {
            $connections['anthropic'] = false;
        }
        
        // Gemini testen
        if (!empty($api_keys['gemini'])) {
            $connections['gemini'] = $this->quick_test_gemini($api_keys['gemini']);
        } else {
            $connections['gemini'] = false;
        }
        
        return $connections;
    }
    
    /**
     * Schnelle API-Tests (nur Ping, kein Content)
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
     * Research-API-Tests
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
     * 🗑️ LEGACY-METHODEN (für Kompatibilität beibehalten)
     */
    public function render_system_status_html($status_data) {
        // Alte Methode - jetzt an moderne Methode weiterleiten
        $tests = $this->get_modern_status_tests();
        return self::generate_modern_system_status_html($tests);
    }
    
    public static function generate_system_status_html($tests) {
        // Alte Methode - an moderne Methode weiterleiten
        return self::generate_modern_system_status_html($tests);
    }
}

/**
 * Helper-Funktion für globalen Zugriff
 */
function retexify_get_system_status() {
    return ReTexify_System_Status::get_instance();
} 