<?php
/**
 * ReTexify AI - System Status Handler
 * Verwaltet alle System-Status-Abfragen zentral
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
     * Vollständigen System-Status abrufen (optimiert)
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
            'version' => defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '3.7.0',
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
     * Research-APIs testen
     */
    private function test_research_apis() {
        return array(
            'google_suggest' => $this->test_google_suggest(),
            'wikipedia' => $this->test_wikipedia(),
            'wiktionary' => $this->test_wiktionary(),
            'openstreetmap' => $this->test_openstreetmap()
        );
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
     * System-Status als HTML rendern
     */
    public function render_system_status_html($status_data) {
        $html = '<div class="retexify-system-status-grid-modern">';
        // WordPress-Version
        if (isset($status_data['wordpress']['version'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">WordPress</div><div class="status-message">Version ' . esc_html($status_data['wordpress']['version']) . '</div></div>';
        }
        // Multisite
        if (isset($status_data['wordpress']['multisite'])) {
            $multi = $status_data['wordpress']['multisite'] ? 'Aktiviert' : 'Nein';
            $html .= '<div class="status-box status-ok"><div class="status-title">Multisite</div><div class="status-message">' . $multi . '</div></div>';
        }
        // Memory-Limit
        if (isset($status_data['wordpress']['memory_limit'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Memory-Limit</div><div class="status-message">' . esc_html($status_data['wordpress']['memory_limit']) . '</div></div>';
        }
        // Max Execution
        if (isset($status_data['wordpress']['max_execution_time'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Max Execution</div><div class="status-message">' . esc_html($status_data['wordpress']['max_execution_time']) . 's</div></div>';
        }
        // Upload-Limit
        if (isset($status_data['wordpress']['upload_max_filesize'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Upload-Limit</div><div class="status-message">' . esc_html($status_data['wordpress']['upload_max_filesize']) . '</div></div>';
        }
        // Plugin-Version
        if (isset($status_data['plugin']['version'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Plugin-Version</div><div class="status-message">' . esc_html($status_data['plugin']['version']) . '</div></div>';
        }
        // Plugin-Pfad
        if (isset($status_data['plugin']['path'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Plugin-Pfad</div><div class="status-message" style="font-size:0.92em;">' . esc_html($status_data['plugin']['path']) . '</div></div>';
        }
        // Plugin-URL
        if (isset($status_data['plugin']['url'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Plugin-URL</div><div class="status-message" style="font-size:0.92em;">' . esc_html($status_data['plugin']['url']) . '</div></div>';
        }
        // Aktiv seit
        if (isset($status_data['plugin']['active_since'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">Aktiv seit</div><div class="status-message">' . esc_html($status_data['plugin']['active_since']) . '</div></div>';
        }
        // PHP-Version
        if (isset($status_data['php']['version'])) {
            $html .= '<div class="status-box status-ok"><div class="status-title">PHP</div><div class="status-message">Version ' . esc_html($status_data['php']['version']) . '</div></div>';
        }
        // cURL
        if (isset($status_data['php']['curl_enabled'])) {
            $curl = $status_data['php']['curl_enabled'] ? 'Verfügbar' : 'Nicht verfügbar';
            $html .= '<div class="status-box ' . ($status_data['php']['curl_enabled'] ? 'status-ok' : 'status-error') . '"><div class="status-title">cURL</div><div class="status-message">' . $curl . '</div></div>';
        }
        // JSON
        if (isset($status_data['php']['json_enabled'])) {
            $json = $status_data['php']['json_enabled'] ? 'Verfügbar' : 'Nicht verfügbar';
            $html .= '<div class="status-box ' . ($status_data['php']['json_enabled'] ? 'status-ok' : 'status-error') . '"><div class="status-title">JSON</div><div class="status-message">' . $json . '</div></div>';
        }
        // OpenSSL
        if (isset($status_data['php']['openssl_enabled'])) {
            $openssl = $status_data['php']['openssl_enabled'] ? 'Verfügbar' : 'Nicht verfügbar';
            $html .= '<div class="status-box ' . ($status_data['php']['openssl_enabled'] ? 'status-ok' : 'status-error') . '"><div class="status-title">OpenSSL</div><div class="status-message">' . $openssl . '</div></div>';
        }
        // mbstring
        if (isset($status_data['php']['mbstring_enabled'])) {
            $mb = $status_data['php']['mbstring_enabled'] ? 'Verfügbar' : 'Nicht verfügbar';
            $html .= '<div class="status-box ' . ($status_data['php']['mbstring_enabled'] ? 'status-ok' : 'status-error') . '"><div class="status-title">mbstring</div><div class="status-message">' . $mb . '</div></div>';
        }
        // AI-Engine
        if (isset($status_data['ai_engine'])) {
            $ai = $status_data['ai_engine'];
            $html .= '<div class="status-box ' . ($ai ? 'status-ok' : 'status-error') . '"><div class="status-title">AI-Engine</div><div class="status-message">' . ($ai ? 'Verfügbar' : 'Nicht verfügbar') . '</div></div>';
        }
        // Content-Analyzer
        if (isset($status_data['content_analyzer'])) {
            $analyzer = $status_data['content_analyzer'];
            $html .= '<div class="status-box ' . ($analyzer ? 'status-ok' : 'status-error') . '"><div class="status-title">Content-Analyzer</div><div class="status-message">' . ($analyzer ? 'Verfügbar' : 'Nicht verfügbar') . '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Modernes Grid-Layout für System-Status (ausgelagert aus retexify.php)
     */
    public static function generate_system_status_html($tests) {
        $html = '<div class="retexify-system-status-grid-modern">';
        foreach ($tests as $key => $test) {
            $status_class = 'status-' . $test['status'];
            $icon = $test['status'] === 'success' ? '✅' : ($test['status'] === 'warning' ? '⚠️' : '❌');
            $html .= '<div class="status-box ' . $status_class . '">';
            $html .= '<div class="status-title">' . $icon . ' ' . $test['name'] . '</div>';
            $html .= '<div class="status-message">' . $test['message'] . '</div>';
            $html .= '<div class="status-details"><small>' . $test['details'] . '</small></div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

/**
 * Helper-Funktion für globalen Zugriff
 */
function retexify_get_system_status() {
    return ReTexify_System_Status::get_instance();
} 