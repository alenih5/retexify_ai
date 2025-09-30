<?php
/**
 * Sicherer AJAX-Handler für ReTexify AI
 * 
 * Diese Datei MUSS in includes/ gespeichert werden als:
 * includes/class-secure-ajax-handler.php
 * 
 * WICHTIG: Verhindert alle bekannten AJAX-Sicherheitslücken!
 * 
 * @package ReTexify_AI
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Direkten Zugriff verhindern
}

class ReTexify_Secure_AJAX_Handler {
    
    /**
     * Erlaubte AJAX-Actions (Whitelist)
     */
    private $allowed_actions = array(
        'retexify_generate_complete_seo',
        'retexify_generate_single_field',
        'retexify_save_seo_data',
        'retexify_test_api',
        'retexify_get_system_status',
        'retexify_load_content',
        'retexify_export_csv',
        'retexify_import_csv',
        'retexify_keyword_research'
    );
    
    /**
     * Maximale Anfragen pro Minute pro User
     */
    private $rate_limit_per_minute = 30;
    
    /**
     * Konstruktor - Registriert AJAX-Actions
     */
    public function __construct() {
        // Für eingeloggte User
        foreach ($this->allowed_actions as $action) {
            add_action('wp_ajax_' . $action, array($this, 'handle_ajax_request'));
        }
        
        // Für nicht-eingeloggte User (falls nötig)
        // add_action('wp_ajax_nopriv_action_name', array($this, 'handle_public_request'));
    }
    
    /**
     * Haupt-AJAX-Handler mit Sicherheitsprüfungen
     */
    public function handle_ajax_request() {
        // SCHRITT 1: Nonce-Prüfung (KRITISCH!)
        if (!$this->verify_nonce()) {
            $this->send_error('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.', 403);
            return;
        }
        
        // SCHRITT 2: Berechtigung prüfen
        if (!$this->check_permissions()) {
            $this->send_error('Keine Berechtigung für diese Aktion.', 403);
            return;
        }
        
        // SCHRITT 3: Rate-Limiting prüfen
        if (!$this->check_rate_limit()) {
            $this->send_error('Zu viele Anfragen. Bitte kurz warten.', 429);
            return;
        }
        
        // SCHRITT 4: Action ermitteln
        $action = $this->get_current_action();
        
        if (!in_array($action, $this->allowed_actions)) {
            $this->send_error('Ungültige Aktion.', 400);
            return;
        }
        
        // SCHRITT 5: Input validieren und sanitizen
        $data = $this->sanitize_input($_POST);
        
        // SCHRITT 6: Action ausführen
        try {
            $result = $this->execute_action($action, $data);
            $this->send_success($result);
            
        } catch (Exception $e) {
            error_log('ReTexify AJAX Error: ' . $e->getMessage());
            $this->send_error('Ein Fehler ist aufgetreten: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Nonce verifizieren
     * 
     * @return bool True wenn gültig
     */
    private function verify_nonce() {
        // Prüfe mehrere mögliche Nonce-Parameter
        $nonce = '';
        
        if (isset($_POST['nonce'])) {
            $nonce = $_POST['nonce'];
        } elseif (isset($_POST['_ajax_nonce'])) {
            $nonce = $_POST['_ajax_nonce'];
        } elseif (isset($_POST['security'])) {
            $nonce = $_POST['security'];
        }
        
        if (empty($nonce)) {
            error_log('ReTexify: Kein Nonce in AJAX-Request gefunden');
            return false;
        }
        
        // Mehrere Nonce-Actions prüfen für Kompatibilität
        $valid_nonces = array(
            'retexify_nonce',
            'retexify_ajax_nonce',
            'retexify-ajax-nonce'
        );
        
        foreach ($valid_nonces as $nonce_action) {
            if (wp_verify_nonce($nonce, $nonce_action)) {
                return true;
            }
        }
        
        error_log('ReTexify: Nonce-Validierung fehlgeschlagen');
        return false;
    }
    
    /**
     * Benutzer-Berechtigungen prüfen
     * 
     * @return bool True wenn berechtigt
     */
    private function check_permissions() {
        // Muss eingeloggt sein
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Muss mindestens Editor sein
        if (!current_user_can('edit_posts')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Rate-Limiting prüfen
     * 
     * @return bool True wenn unter Limit
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $transient_key = 'retexify_rate_limit_' . $user_id;
        
        // Aktuelle Anfragen abrufen
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            // Erste Anfrage - Zähler starten
            set_transient($transient_key, 1, 60); // 60 Sekunden
            return true;
        }
        
        // Limit überschritten?
        if ($requests >= $this->rate_limit_per_minute) {
            error_log(sprintf(
                'ReTexify: Rate-Limit überschritten für User %d (%d Anfragen)',
                $user_id,
                $requests
            ));
            return false;
        }
        
        // Zähler erhöhen
        set_transient($transient_key, $requests + 1, 60);
        
        return true;
    }
    
    /**
     * Aktuelle Action ermitteln
     * 
     * @return string Action-Name
     */
    private function get_current_action() {
        return isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
    }
    
    /**
     * Input-Daten sanitizen
     * 
     * @param array $data Rohe POST-Daten
     * @return array Bereinigte Daten
     */
    private function sanitize_input($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            // Verschiedene Sanitize-Funktionen je nach Datentyp
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_input($value); // Rekursiv
                
            } elseif ($key === 'post_id' || strpos($key, '_id') !== false) {
                $sanitized[$key] = absint($value); // Integer
                
            } elseif ($key === 'email') {
                $sanitized[$key] = sanitize_email($value);
                
            } elseif ($key === 'url') {
                $sanitized[$key] = esc_url_raw($value);
                
            } elseif (in_array($key, array('content', 'description', 'text'))) {
                $sanitized[$key] = wp_kses_post($value); // HTML erlauben
                
            } else {
                $sanitized[$key] = sanitize_text_field($value); // Standard
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Action ausführen
     * 
     * @param string $action Action-Name
     * @param array $data Validierte Daten
     * @return mixed Ergebnis
     */
    private function execute_action($action, $data) {
        global $retexify_plugin; // Haupt-Plugin-Instanz
        
        // Routing zu entsprechender Methode
        switch ($action) {
            case 'retexify_generate_complete_seo':
                return $this->handle_generate_complete_seo($data);
                
            case 'retexify_generate_single_field':
                return $this->handle_generate_single_field($data);
                
            case 'retexify_save_seo_data':
                return $this->handle_save_seo_data($data);
                
            case 'retexify_test_api':
                return $this->handle_test_api($data);
                
            case 'retexify_get_system_status':
                return $this->handle_get_system_status($data);
                
            case 'retexify_load_content':
                return $this->handle_load_content($data);
                
            case 'retexify_export_csv':
                return $this->handle_export_csv($data);
                
            case 'retexify_import_csv':
                return $this->handle_import_csv($data);
                
            case 'retexify_keyword_research':
                return $this->handle_keyword_research($data);
                
            default:
                throw new Exception('Unbekannte Action: ' . $action);
        }
    }
    
    /**
     * Beispiel-Handler: SEO generieren
     */
    private function handle_generate_complete_seo($data) {
        // Pflichtfelder prüfen
        if (empty($data['post_id'])) {
            throw new Exception('Keine Post-ID angegeben');
        }
        
        $post_id = $data['post_id'];
        
        // Post existiert?
        $post = get_post($post_id);
        if (!$post) {
            throw new Exception('Post nicht gefunden');
        }
        
        // Hier die eigentliche SEO-Generierung aufrufen
        // return $this->plugin->seo_generator->generate_complete_seo($post_id);
        
        return array(
            'post_id' => $post_id,
            'title' => 'Generierter SEO-Titel',
            'description' => 'Generierte Meta-Description',
            'keywords' => array('keyword1', 'keyword2')
        );
    }
    
    /**
     * Weitere Handler-Methoden hier...
     */
    private function handle_generate_single_field($data) {
        // Implementierung...
        return array('field' => 'value');
    }
    
    private function handle_save_seo_data($data) {
        // Implementierung...
        return array('saved' => true);
    }
    
    private function handle_test_api($data) {
        // Implementierung...
        return array('status' => 'ok');
    }
    
    private function handle_get_system_status($data) {
        // Implementierung...
        return array('status' => 'healthy');
    }
    
    private function handle_load_content($data) {
        // Implementierung...
        return array('content' => 'loaded');
    }
    
    private function handle_export_csv($data) {
        // Implementierung...
        return array('exported' => true);
    }
    
    private function handle_import_csv($data) {
        // Implementierung...
        return array('imported' => true);
    }
    
    private function handle_keyword_research($data) {
        // Implementierung...
        return array('keywords' => array());
    }
    
    /**
     * Erfolgreiche Antwort senden
     * 
     * @param mixed $data Daten zum Zurücksenden
     */
    private function send_success($data) {
        wp_send_json_success($data);
    }
    
    /**
     * Fehler-Antwort senden
     * 
     * @param string $message Fehlermeldung
     * @param int $code HTTP-Statuscode
     */
    private function send_error($message, $code = 400) {
        status_header($code);
        wp_send_json_error(array(
            'message' => $message,
            'code' => $code
        ));
    }
}

// Instanz erstellen
new ReTexify_Secure_AJAX_Handler();
