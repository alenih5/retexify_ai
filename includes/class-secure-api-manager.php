<?php
/**
 * Sichere API-Schlüssel-Verwaltung für ReTexify AI
 * 
 * Diese Datei MUSS in includes/ gespeichert werden als:
 * includes/class-secure-api-manager.php
 * 
 * @package ReTexify_AI
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Direkten Zugriff verhindern
}

class ReTexify_Secure_API_Manager {
    
    /**
     * Verschlüsselungsmethode
     */
    private $cipher = 'AES-256-CBC';
    
    /**
     * Verschlüsselungsschlüssel generieren
     */
    private function get_encryption_key() {
        // Nutzt WordPress Salt als Basis
        $key = wp_salt('auth') . wp_salt('secure_auth');
        return substr(hash('sha256', $key), 0, 32);
    }
    
    /**
     * Initialisierungsvektor generieren
     */
    private function get_iv() {
        $iv = wp_salt('logged_in') . wp_salt('nonce');
        return substr(hash('sha256', $iv), 0, 16);
    }
    
    /**
     * API-Schlüssel verschlüsselt speichern
     * 
     * @param string $provider Provider-Name (openai, anthropic, gemini)
     * @param string $api_key Der API-Schlüssel
     * @return bool Erfolg oder Fehler
     */
    public function save_api_key($provider, $api_key) {
        // Validierung
        if (empty($provider) || empty($api_key)) {
            return false;
        }
        
        // Verschlüsseln
        try {
            $encrypted = openssl_encrypt(
                $api_key,
                $this->cipher,
                $this->get_encryption_key(),
                0,
                $this->get_iv()
            );
            
            if ($encrypted === false) {
                error_log('ReTexify: Verschlüsselung fehlgeschlagen für ' . $provider);
                return false;
            }
            
            // In WordPress-Options speichern
            $option_name = 'retexify_api_key_' . sanitize_key($provider);
            update_option($option_name, $encrypted, false); // false = nicht autoload
            
            // Log für Debug
            error_log('ReTexify: API-Schlüssel für ' . $provider . ' erfolgreich gespeichert');
            
            return true;
            
        } catch (Exception $e) {
            error_log('ReTexify: Fehler beim Speichern: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * API-Schlüssel entschlüsselt abrufen
     * 
     * @param string $provider Provider-Name
     * @return string|false Der API-Schlüssel oder false bei Fehler
     */
    public function get_api_key($provider) {
        $option_name = 'retexify_api_key_' . sanitize_key($provider);
        $encrypted = get_option($option_name);
        
        if (empty($encrypted)) {
            return false;
        }
        
        try {
            $decrypted = openssl_decrypt(
                $encrypted,
                $this->cipher,
                $this->get_encryption_key(),
                0,
                $this->get_iv()
            );
            
            if ($decrypted === false) {
                error_log('ReTexify: Entschlüsselung fehlgeschlagen für ' . $provider);
                return false;
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log('ReTexify: Fehler beim Abrufen: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * API-Schlüssel löschen
     * 
     * @param string $provider Provider-Name
     * @return bool Erfolg
     */
    public function delete_api_key($provider) {
        $option_name = 'retexify_api_key_' . sanitize_key($provider);
        return delete_option($option_name);
    }
    
    /**
     * Alle API-Schlüssel löschen
     * 
     * @return void
     */
    public function delete_all_api_keys() {
        $providers = array('openai', 'anthropic', 'gemini');
        
        foreach ($providers as $provider) {
            $this->delete_api_key($provider);
        }
        
        error_log('ReTexify: Alle API-Schlüssel gelöscht');
    }
    
    /**
     * Prüfen ob API-Schlüssel vorhanden ist
     * 
     * @param string $provider Provider-Name
     * @return bool True wenn vorhanden
     */
    public function has_api_key($provider) {
        $key = $this->get_api_key($provider);
        return !empty($key);
    }
    
    /**
     * Validiere API-Schlüssel-Format
     * 
     * @param string $provider Provider-Name
     * @param string $api_key Der zu prüfende Schlüssel
     * @return bool True wenn Format OK
     */
    public function validate_api_key_format($provider, $api_key) {
        switch ($provider) {
            case 'openai':
                // OpenAI: sk-...
                return preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key);
                
            case 'anthropic':
                // Anthropic: sk-ant-...
                return preg_match('/^sk-ant-[a-zA-Z0-9_-]{95,}$/', $api_key);
                
            case 'gemini':
                // Google: AIza...
                return preg_match('/^AIza[a-zA-Z0-9_-]{35}$/', $api_key);
                
            default:
                return false;
        }
    }
    
    /**
     * API-Schlüssel maskiert anzeigen (für UI)
     * 
     * @param string $provider Provider-Name
     * @return string Maskierter Schlüssel oder "Nicht gesetzt"
     */
    public function get_masked_api_key($provider) {
        $key = $this->get_api_key($provider);
        
        if (empty($key)) {
            return 'Nicht gesetzt';
        }
        
        // Zeige nur erste 8 und letzte 4 Zeichen
        $length = strlen($key);
        if ($length < 12) {
            return str_repeat('*', $length);
        }
        
        return substr($key, 0, 8) . str_repeat('*', $length - 12) . substr($key, -4);
    }
    
    /**
     * Rate-Limiting-Status prüfen
     * 
     * @param string $provider Provider-Name
     * @return array Status-Informationen
     */
    public function check_rate_limit($provider) {
        $transient_name = 'retexify_rate_limit_' . sanitize_key($provider);
        $rate_data = get_transient($transient_name);
        
        if ($rate_data === false) {
            // Keine Limits aktiv
            return array(
                'limited' => false,
                'requests_remaining' => 100,
                'reset_time' => null
            );
        }
        
        return $rate_data;
    }
    
    /**
     * Rate-Limit setzen (nach API-Fehler)
     * 
     * @param string $provider Provider-Name
     * @param int $retry_after Sekunden bis Reset
     * @return void
     */
    public function set_rate_limit($provider, $retry_after = 60) {
        $transient_name = 'retexify_rate_limit_' . sanitize_key($provider);
        
        $rate_data = array(
            'limited' => true,
            'requests_remaining' => 0,
            'reset_time' => time() + $retry_after
        );
        
        set_transient($transient_name, $rate_data, $retry_after);
        
        error_log(sprintf(
            'ReTexify: Rate-Limit für %s aktiv. Reset in %d Sekunden.',
            $provider,
            $retry_after
        ));
    }
}

// Ende der Klasse
