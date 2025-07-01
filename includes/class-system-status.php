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
        
        // KI-APIs
        if (isset($status_data['apis'])) {
            $apis = $status_data['apis'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🤖 KI-Provider</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            
            foreach ($apis as $provider => $status) {
                $status_class = $status ? 'status-ok' : 'status-error';
                $status_text = $status ? '✅ Verbunden' : '❌ Nicht verfügbar';
                $provider_name = ucfirst($provider);
                
                $html .= '<span class="' . $status_class . '"><strong>' . $provider_name . ':</strong> <span>' . $status_text . '</span></span>';
            }
            
            $html .= '</div></div>';
        }
        
        // Research-APIs
        if (isset($status_data['research'])) {
            $research = $status_data['research'];
            $html .= '<div class="retexify-system-info-modern">';
            $html .= '<h4>🔍 Research-APIs</h4>';
            $html .= '<div class="retexify-system-grid-modern">';
            
            $api_names = array(
                'google_suggest' => 'Google Suggest',
                'wikipedia' => 'Wikipedia',
                'wiktionary' => 'Wiktionary',
                'openstreetmap' => 'OpenStreetMap'
            );
            
            foreach ($research as $api => $status) {
                $status_class = $status ? 'status-ok' : 'status-error';
                $status_text = $status ? '✅ Aktiv' : '❌ Offline';
                $api_name = $api_names[$api] ?? ucfirst($api);
                
                $html .= '<span class="' . $status_class . '"><strong>' . $api_name . ':</strong> <span>' . $status_text . '</span></span>';
            }
            
            $html .= '</div></div>';
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