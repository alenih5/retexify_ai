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
        $plugin_version = defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '4.3.0';
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
     * 🆕 MODERNE HTML-GENERIERUNG (kompatibel mit neuem CSS)
     */
    public static function generate_modern_system_status_html($tests) {
        $html = '<div class="retexify-system-status-container">';
        $html .= '<div class="retexify-status-header">';
        $html .= '<h3>System-Status</h3>';
        $html .= '<button class="retexify-test-button" onclick="loadSystemStatus(); return false;">System testen</button>';
        $html .= '</div>';
        $html .= '<div class="retexify-status-grid">';
        foreach ($tests as $key => $test) {
            $status_class = 'retexify-status-badge ' . $test['status'];
            $badge_text = strtoupper($test['status'] === 'success' ? 'OK' : ($test['status'] === 'warning' ? 'WARNUNG' : 'FEHLER'));
            $html .= '<div class="retexify-status-card">';
            $html .= '<div class="retexify-card-title">' . esc_html($test['name']) . '</div>';
            $html .= '<div class="retexify-card-value">' . esc_html($test['message']) .
                ' <span class="' . $status_class . '">' . $badge_text . '</span></div>';
            if (!empty($test['details'])) {
                $html .= '<div class="retexify-card-details">' . esc_html($test['details']) . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $success_count = count(array_filter($tests, function($test) { return $test['status'] === 'success'; }));
        $warning_count = count(array_filter($tests, function($test) { return $test['status'] === 'warning'; }));
        $error_count = count(array_filter($tests, function($test) { return $test['status'] === 'error'; }));
        $html .= '<div class="retexify-summary-stats">';
        $html .= '<div class="retexify-stat-item"><span class="retexify-stat-number">' . $success_count . '</span><span class="retexify-stat-label">Erfolgreich</span></div>';
        $html .= '<div class="retexify-stat-item"><span class="retexify-stat-number">' . $warning_count . '</span><span class="retexify-stat-label">Warnungen</span></div>';
        $html .= '<div class="retexify-stat-item"><span class="retexify-stat-number">' . $error_count . '</span><span class="retexify-stat-label">Fehler</span></div>';
        $html .= '<div class="retexify-stat-item"><span class="retexify-stat-number">' . count($tests) . '</span><span class="retexify-stat-label">Tests Total</span></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
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
     * 🆕 RESEARCH-ENGINE HTML (modernes Design)
     */
    public static function generate_research_status_html($tests) {
        $html = '<div class="retexify-research-section">';
        $html .= '<div class="retexify-research-header">';
        $html .= '<h4>Intelligent Research Engine</h4>';
        $html .= '<button class="retexify-test-button" onclick="loadResearchStatus(); return false;">APIs testen</button>';
        $html .= '</div>';
        $html .= '<div class="retexify-research-grid">';
        foreach ($tests as $key => $test) {
            $status_class = 'retexify-research-badge ' . $test['status'];
            $badge_text = strtoupper($test['status'] === 'success' ? 'OK' : ($test['status'] === 'warning' ? 'WARNUNG' : 'FEHLER'));
            $html .= '<div class="retexify-research-card">';
            $html .= '<div class="retexify-research-title">' . esc_html($test['name']) . '</div>';
            $html .= '<div class="retexify-research-value">' . esc_html($test['message']) .
                ' <span class="' . $status_class . '">' . $badge_text . '</span></div>';
            if (!empty($test['details'])) {
                $html .= '<div class="retexify-research-details">' . esc_html($test['details']) . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Helper: Status-Icons
     */
    private static function get_status_icon($status) {
        switch ($status) {
            case 'success': return '✅';
            case 'warning': return '⚠️';
            case 'error': return '❌';
            case 'info': return 'ℹ️';
            default: return '❓';
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
            'version' => defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '4.3.0',
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