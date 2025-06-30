<?php
/**
 * ReTexify AI Pro - KORRIGIERTE System Status Klasse
 * Version: 3.7.1 - Bug Fixes für AJAX-Handler und Performance
 * FIXES: AJAX-Handler-Registrierung, Timeout-Optimierung, HTML-Rendering
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_System_Status {
    
    /**
     * Konstruktor - AJAX-Handler registrieren
     */
    public function __construct() {
        // KRITISCHER FIX: AJAX-Handler für beide Benutzertypen registrieren
        add_action('wp_ajax_retexify_test_system', array($this, 'ajax_test_system'));
        add_action('wp_ajax_nopriv_retexify_test_system', array($this, 'ajax_test_system'));
        
        add_action('wp_ajax_retexify_test_research_apis', array($this, 'ajax_test_research_apis'));
        add_action('wp_ajax_nopriv_retexify_test_research_apis', array($this, 'ajax_test_research_apis'));
        
        add_action('wp_ajax_retexify_test_api_services', array($this, 'ajax_test_research_apis')); // Alias
        add_action('wp_ajax_nopriv_retexify_test_api_services', array($this, 'ajax_test_research_apis')); // Alias
    }
    
    /**
     * AJAX-Handler für System-Test - OPTIMIERT
     */
    public function ajax_test_system() {
        // Security Check
        if (!check_ajax_referer('retexify_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Permission Check
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        try {
            // Performance Start
            $start_time = microtime(true);
            
            // System-Status sammeln
            $system_data = $this->get_system_status();
            
            // HTML-Output generieren
            $html_output = $this->render_system_status_html($system_data);
            
            // Performance Ende
            $duration = round((microtime(true) - $start_time), 2);
            
            // Debug-Log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ReTexify System-Test abgeschlossen in {$duration}s");
            }
            
            wp_send_json_success($html_output);
            
        } catch (Exception $e) {
            error_log('ReTexify System-Test Fehler: ' . $e->getMessage());
            wp_send_json_error('System-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX-Handler für Research-APIs Test - OPTIMIERT
     */
    public function ajax_test_research_apis() {
        // Security Check
        if (!check_ajax_referer('retexify_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Permission Check
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        try {
            // Performance Start
            $start_time = microtime(true);
            
            // Research-APIs testen
            $research_data = $this->test_research_apis();
            
            // HTML-Output generieren
            $html_output = $this->render_research_status_html($research_data);
            
            // Performance Ende
            $duration = round((microtime(true) - $start_time), 2);
            
            // Debug-Log
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("ReTexify Research-Test abgeschlossen in {$duration}s");
            }
            
            wp_send_json_success($html_output);
            
        } catch (Exception $e) {
            error_log('ReTexify Research-Test Fehler: ' . $e->getMessage());
            wp_send_json_error('Research-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * System-Status sammeln - OPTIMIERT
     */
    private function get_system_status() {
        $start_time = microtime(true);
        
        // WordPress System Info
        $wordpress_info = array(
            'version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'curl_enabled' => function_exists('curl_init'),
            'json_enabled' => function_exists('json_encode')
        );
        
        // Plugin Info
        $plugin_info = array(
            'version' => defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '3.7.1',
            'active_since' => get_option('retexify_activation_time', 'Unbekannt'),
            'php_extensions' => $this->check_php_extensions(),
            'wp_requirements' => $this->check_wp_requirements()
        );
        
        // API-Keys testen (ohne Keys preiszugeben)
        $api_status = $this->test_api_keys();
        
        // Ausführungszeit
        $execution_time = round((microtime(true) - $start_time), 3);
        
        return array(
            'wordpress' => $wordpress_info,
            'plugin' => $plugin_info,
            'api_status' => $api_status,
            'execution_time' => $execution_time,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * PHP Extensions prüfen
     */
    private function check_php_extensions() {
        $required_extensions = array('curl', 'json', 'mbstring', 'openssl');
        $extension_status = array();
        
        foreach ($required_extensions as $ext) {
            $extension_status[$ext] = extension_loaded($ext);
        }
        
        return $extension_status;
    }
    
    /**
     * WordPress Anforderungen prüfen
     */
    private function check_wp_requirements() {
        global $wp_version;
        
        return array(
            'wp_version_ok' => version_compare($wp_version, '5.0', '>='),
            'php_version_ok' => version_compare(PHP_VERSION, '7.4', '>='),
            'memory_ok' => $this->convert_to_bytes(ini_get('memory_limit')) >= 268435456, // 256MB
            'curl_ok' => function_exists('curl_init'),
            'ssl_ok' => function_exists('openssl_get_cert_locations')
        );
    }
    
    /**
     * Memory-String zu Bytes konvertieren
     */
    private function convert_to_bytes($size_str) {
        $size_str = trim($size_str);
        $last = strtolower(substr($size_str, -1));
        $size = (int) $size_str;
        
        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }
        
        return $size;
    }
    
    /**
     * API-Keys testen (optimiert mit Timeouts)
     */
    private function test_api_keys() {
        $api_keys = get_option('retexify_api_keys', array());
        $api_status = array();
        
        // OpenAI Test
        if (!empty($api_keys['openai'])) {
            $api_status['openai'] = $this->quick_test_openai($api_keys['openai']);
        } else {
            $api_status['openai'] = false;
        }
        
        // Anthropic Test
        if (!empty($api_keys['anthropic'])) {
            $api_status['anthropic'] = $this->quick_test_anthropic($api_keys['anthropic']);
        } else {
            $api_status['anthropic'] = false;
        }
        
        // Gemini Test
        if (!empty($api_keys['gemini'])) {
            $api_status['gemini'] = $this->quick_test_gemini($api_keys['gemini']);
        } else {
            $api_status['gemini'] = false;
        }
        
        return $api_status;
    }
    
    /**
     * Schneller OpenAI API Test (3 Sekunden Timeout)
     */
    private function quick_test_openai($api_key) {
        $response = wp_remote_get('https://api.openai.com/v1/models', array(
            'timeout' => 3,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'User-Agent' => 'ReTexify-AI-Plugin'
            )
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Schneller Anthropic API Test (3 Sekunden Timeout)
     */
    private function quick_test_anthropic($api_key) {
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 3,
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
        return !is_wp_error($response) && ($code === 200 || $code === 400); // 400 = OK (invalide Message aber Key OK)
    }
    
    /**
     * Schneller Gemini API Test (3 Sekunden Timeout)
     */
    private function quick_test_gemini($api_key) {
        $response = wp_remote_get('https://generativelanguage.googleapis.com/v1beta/models?key=' . $api_key, array(
            'timeout' => 3
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Research-APIs testen (optimiert)
     */
    private function test_research_apis() {
        return array(
            'Google Suggest' => $this->test_google_suggest(),
            'Wikipedia' => $this->test_wikipedia(),
            'Wiktionary' => $this->test_wiktionary(),
            'OpenStreetMap' => $this->test_openstreetmap()
        );
    }
    
    /**
     * Google Suggest API Test
     */
    private function test_google_suggest() {
        $response = wp_remote_get('https://suggestqueries.google.com/complete/search?client=firefox&q=test', array(
            'timeout' => 3,
            'user-agent' => 'Mozilla/5.0 (compatible; ReTexify-AI)'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Wikipedia API Test
     */
    private function test_wikipedia() {
        $response = wp_remote_get('https://de.wikipedia.org/api/rest_v1/page/summary/Test', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Wiktionary API Test
     */
    private function test_wiktionary() {
        $response = wp_remote_get('https://de.wiktionary.org/api/rest_v1/page/summary/test', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * OpenStreetMap API Test
     */
    private function test_openstreetmap() {
        $response = wp_remote_get('https://nominatim.openstreetmap.org/search?q=Bern&format=json&limit=1', array(
            'timeout' => 3,
            'user-agent' => 'ReTexify-AI-Plugin/1.0'
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * System-Status als HTML rendern - KORRIGIERT
     */
    private function render_system_status_html($status_data) {
        $html = '<div class="retexify-system-status-modern">';
        
        // WordPress-Info
        if (isset($status_data['wordpress'])) {
            $wp = $status_data['wordpress'];
            $html .= '<div class="retexify-system-info-section">';
            $html .= '<h4>🌐 WordPress System</h4>';
            $html .= '<div class="retexify-system-grid">';
            
            $html .= $this->render_status_item('Version', $wp['version'], true);
            $html .= $this->render_status_item('PHP Version', $wp['php_version'], version_compare($wp['php_version'], '7.4', '>='));
            $html .= $this->render_status_item('Memory Limit', $wp['memory_limit'], $this->convert_to_bytes($wp['memory_limit']) >= 268435456);
            $html .= $this->render_status_item('Max Execution', $wp['max_execution_time'] . 's', (int)$wp['max_execution_time'] >= 30);
            $html .= $this->render_status_item('Upload Max', $wp['upload_max_filesize'], true);
            $html .= $this->render_status_item('cURL', $wp['curl_enabled'] ? 'Aktiv' : 'Inaktiv', $wp['curl_enabled']);
            $html .= $this->render_status_item('JSON', $wp['json_enabled'] ? 'Aktiv' : 'Inaktiv', $wp['json_enabled']);
            
            $html .= '</div></div>';
        }
        
        // Plugin-Info
        if (isset($status_data['plugin'])) {
            $plugin = $status_data['plugin'];
            $html .= '<div class="retexify-system-info-section">';
            $html .= '<h4>🔌 Plugin Status</h4>';
            $html .= '<div class="retexify-system-grid">';
            
            $html .= $this->render_status_item('Version', $plugin['version'], true);
            $html .= $this->render_status_item('Aktiv seit', date('d.m.Y H:i', strtotime($plugin['active_since'])), true);
            
            // PHP Extensions
            if (isset($plugin['php_extensions'])) {
                foreach ($plugin['php_extensions'] as $ext => $status) {
                    $html .= $this->render_status_item(strtoupper($ext), $status ? 'Aktiv' : 'Fehlt', $status);
                }
            }
            
            $html .= '</div></div>';
        }
        
        // API-Status
        if (isset($status_data['api_status'])) {
            $api = $status_data['api_status'];
            $html .= '<div class="retexify-system-info-section">';
            $html .= '<h4>🤖 API-Verbindungen</h4>';
            $html .= '<div class="retexify-system-grid">';
            
            $html .= $this->render_status_item('OpenAI', $api['openai'] ? 'Verbunden' : 'Nicht konfiguriert', $api['openai']);
            $html .= $this->render_status_item('Anthropic', $api['anthropic'] ? 'Verbunden' : 'Nicht konfiguriert', $api['anthropic']);
            $html .= $this->render_status_item('Gemini', $api['gemini'] ? 'Verbunden' : 'Nicht konfiguriert', $api['gemini']);
            
            $html .= '</div></div>';
        }
        
        // Performance-Info
        if (isset($status_data['execution_time'])) {
            $html .= '<div class="retexify-system-info-section">';
            $html .= '<h4>⏱️ Performance</h4>';
            $html .= '<div class="retexify-system-grid">';
            $html .= $this->render_status_item('Test-Dauer', $status_data['execution_time'] . 's', $status_data['execution_time'] < 5);
            $html .= $this->render_status_item('Zeitstempel', date('d.m.Y H:i:s', strtotime($status_data['timestamp'])), true);
            $html .= '</div></div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Research-Status als HTML rendern - KORRIGIERT
     */
    private function render_research_status_html($research_data) {
        $html = '<div class="retexify-research-status-modern">';
        $html .= '<h4>🧠 Research Engine Status</h4>';
        $html .= '<div class="retexify-research-grid">';
        
        foreach ($research_data as $api_name => $status) {
            $status_class = $status ? 'status-ok' : 'status-error';
            $status_icon = $status ? '✅' : '❌';
            $status_text = $status ? 'Online' : 'Offline';
            
            $html .= '<div class="research-status-item ' . $status_class . '">';
            $html .= '<span class="research-api-name">' . esc_html($api_name) . '</span>';
            $html .= '<span class="research-api-status">' . $status_icon . ' ' . $status_text . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Test-Button hinzufügen
        $html .= '<div class="retexify-research-actions">';
        $html .= '<button id="test-research-apis" class="retexify-btn retexify-btn-secondary">🔄 APIs erneut testen</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Status-Item HTML generieren
     */
    private function render_status_item($label, $value, $is_ok) {
        $status_class = $is_ok ? 'status-ok' : 'status-error';
        $status_icon = $is_ok ? '✅' : '❌';
        
        return sprintf(
            '<div class="status-item %s"><span class="status-label">%s:</span> <span class="status-value">%s %s</span></div>',
            $status_class,
            esc_html($label),
            $status_icon,
            esc_html($value)
        );
    }
}

// Instanz erstellen (falls noch nicht vorhanden)
if (!function_exists('retexify_get_system_status')) {
    function retexify_get_system_status() {
        static $instance = null;
        if ($instance === null) {
            $instance = new ReTexify_System_Status();
        }
        return $instance;
    }
    
    // Automatisch initialisieren
    retexify_get_system_status();
} 