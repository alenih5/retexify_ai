<?php
/**
 * Intelligenter API-Rate-Limiter für ReTexify AI
 * 
 * Diese Datei MUSS in includes/ gespeichert werden als:
 * includes/class-api-rate-limiter.php
 * 
 * Verhindert API-Überlastung und spart Kosten!
 * 
 * @package ReTexify_AI
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_API_Rate_Limiter {
    
    /**
     * Rate-Limits pro Provider (Anfragen pro Minute)
     */
    private $limits = array(
        'openai' => array(
            'requests_per_minute' => 60,
            'tokens_per_minute' => 90000,
            'requests_per_day' => 10000
        ),
        'anthropic' => array(
            'requests_per_minute' => 50,
            'tokens_per_minute' => 100000,
            'requests_per_day' => 10000
        ),
        'gemini' => array(
            'requests_per_minute' => 60,
            'tokens_per_minute' => 32000,
            'requests_per_day' => 1500
        )
    );
    
    /**
     * Prüfen ob API-Call erlaubt ist
     * 
     * @param string $provider Provider-Name (openai, anthropic, gemini)
     * @param int $estimated_tokens Geschätzte Token-Anzahl
     * @return array ['allowed' => bool, 'reason' => string, 'retry_after' => int]
     */
    public function can_make_request($provider, $estimated_tokens = 0) {
        // Provider valide?
        if (!isset($this->limits[$provider])) {
            return array(
                'allowed' => false,
                'reason' => 'Unbekannter Provider: ' . $provider,
                'retry_after' => 0
            );
        }
        
        $limits = $this->limits[$provider];
        
        // PRÜFUNG 1: Anfragen pro Minute
        $rpm_check = $this->check_requests_per_minute($provider, $limits['requests_per_minute']);
        if (!$rpm_check['allowed']) {
            return $rpm_check;
        }
        
        // PRÜFUNG 2: Token pro Minute (wenn Token angegeben)
        if ($estimated_tokens > 0) {
            $tpm_check = $this->check_tokens_per_minute($provider, $estimated_tokens, $limits['tokens_per_minute']);
            if (!$tpm_check['allowed']) {
                return $tpm_check;
            }
        }
        
        // PRÜFUNG 3: Anfragen pro Tag
        $rpd_check = $this->check_requests_per_day($provider, $limits['requests_per_day']);
        if (!$rpd_check['allowed']) {
            return $rpd_check;
        }
        
        // PRÜFUNG 4: Aktive API-Fehler (429 Too Many Requests)
        $error_check = $this->check_active_errors($provider);
        if (!$error_check['allowed']) {
            return $error_check;
        }
        
        // Alles OK!
        return array(
            'allowed' => true,
            'reason' => 'OK',
            'retry_after' => 0
        );
    }
    
    /**
     * API-Call registrieren (NACH erfolgreichem Call)
     * 
     * @param string $provider Provider-Name
     * @param int $tokens_used Verwendete Token
     * @param bool $was_successful War der Call erfolgreich?
     * @param int $http_status HTTP-Statuscode
     */
    public function register_request($provider, $tokens_used = 0, $was_successful = true, $http_status = 200) {
        // Anfragen-Zähler erhöhen
        $this->increment_request_counter($provider, 'minute');
        $this->increment_request_counter($provider, 'day');
        
        // Token-Zähler erhöhen
        if ($tokens_used > 0) {
            $this->increment_token_counter($provider, $tokens_used);
        }
        
        // Bei Fehler: Cooldown setzen
        if (!$was_successful) {
            $this->handle_api_error($provider, $http_status);
        }
        
        // Statistik speichern
        $this->save_statistics($provider, $tokens_used, $was_successful);
    }
    
    /**
     * Anfragen pro Minute prüfen
     */
    private function check_requests_per_minute($provider, $limit) {
        $key = "retexify_rpm_{$provider}";
        $current = (int) get_transient($key);
        
        if ($current >= $limit) {
            return array(
                'allowed' => false,
                'reason' => sprintf(
                    'Rate-Limit erreicht: %d/%d Anfragen pro Minute',
                    $current,
                    $limit
                ),
                'retry_after' => 60
            );
        }
        
        return array('allowed' => true);
    }
    
    /**
     * Token pro Minute prüfen
     */
    private function check_tokens_per_minute($provider, $estimated_tokens, $limit) {
        $key = "retexify_tpm_{$provider}";
        $current = (int) get_transient($key);
        
        if (($current + $estimated_tokens) >= $limit) {
            return array(
                'allowed' => false,
                'reason' => sprintf(
                    'Token-Limit erreicht: %d/%d Token pro Minute',
                    $current,
                    $limit
                ),
                'retry_after' => 60
            );
        }
        
        return array('allowed' => true);
    }
    
    /**
     * Anfragen pro Tag prüfen
     */
    private function check_requests_per_day($provider, $limit) {
        $key = "retexify_rpd_{$provider}";
        $current = (int) get_transient($key);
        
        if ($current >= $limit) {
            // Berechne Zeit bis Mitternacht
            $now = current_time('timestamp');
            $midnight = strtotime('tomorrow', $now);
            $retry_after = $midnight - $now;
            
            return array(
                'allowed' => false,
                'reason' => sprintf(
                    'Tages-Limit erreicht: %d/%d Anfragen heute',
                    $current,
                    $limit
                ),
                'retry_after' => $retry_after
            );
        }
        
        return array('allowed' => true);
    }
    
    /**
     * Prüfen ob aktive API-Fehler vorliegen
     */
    private function check_active_errors($provider) {
        $key = "retexify_api_error_{$provider}";
        $error_data = get_transient($key);
        
        if ($error_data !== false) {
            return array(
                'allowed' => false,
                'reason' => sprintf(
                    'API-Fehler aktiv: %s (HTTP %d)',
                    $error_data['message'],
                    $error_data['http_status']
                ),
                'retry_after' => $error_data['retry_after']
            );
        }
        
        return array('allowed' => true);
    }
    
    /**
     * Anfragen-Zähler erhöhen
     */
    private function increment_request_counter($provider, $period) {
        $key = "retexify_r{$period[0]}_{$provider}"; // rpm oder rpd
        $current = (int) get_transient($key);
        
        $expiry = ($period === 'minute') ? 60 : 86400; // 60 Sek oder 24 Std
        
        set_transient($key, $current + 1, $expiry);
    }
    
    /**
     * Token-Zähler erhöhen
     */
    private function increment_token_counter($provider, $tokens) {
        $key = "retexify_tpm_{$provider}";
        $current = (int) get_transient($key);
        
        set_transient($key, $current + $tokens, 60);
    }
    
    /**
     * API-Fehler behandeln
     */
    private function handle_api_error($provider, $http_status) {
        $key = "retexify_api_error_{$provider}";
        
        // Retry-Zeit basierend auf Status
        $retry_after = 60; // Standard: 1 Minute
        
        switch ($http_status) {
            case 429: // Too Many Requests
                $retry_after = 300; // 5 Minuten
                $message = 'Zu viele Anfragen';
                break;
                
            case 500: // Internal Server Error
            case 502: // Bad Gateway
            case 503: // Service Unavailable
                $retry_after = 120; // 2 Minuten
                $message = 'Server-Fehler';
                break;
                
            case 401: // Unauthorized
            case 403: // Forbidden
                $retry_after = 600; // 10 Minuten
                $message = 'Authentifizierungs-Fehler';
                break;
                
            default:
                $message = 'Unbekannter Fehler';
        }
        
        $error_data = array(
            'message' => $message,
            'http_status' => $http_status,
            'retry_after' => $retry_after,
            'timestamp' => current_time('timestamp')
        );
        
        set_transient($key, $error_data, $retry_after);
        
        error_log(sprintf(
            'ReTexify: API-Fehler bei %s (HTTP %d). Retry in %d Sekunden.',
            $provider,
            $http_status,
            $retry_after
        ));
    }
    
    /**
     * Statistiken speichern
     */
    private function save_statistics($provider, $tokens_used, $was_successful) {
        $stats_key = 'retexify_api_stats';
        $stats = get_option($stats_key, array());
        
        if (!isset($stats[$provider])) {
            $stats[$provider] = array(
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_tokens' => 0,
                'last_request' => null
            );
        }
        
        $stats[$provider]['total_requests']++;
        
        if ($was_successful) {
            $stats[$provider]['successful_requests']++;
        } else {
            $stats[$provider]['failed_requests']++;
        }
        
        $stats[$provider]['total_tokens'] += $tokens_used;
        $stats[$provider]['last_request'] = current_time('mysql');
        
        update_option($stats_key, $stats, false);
    }
    
    /**
     * Statistiken abrufen
     * 
     * @return array Alle Provider-Statistiken
     */
    public function get_statistics() {
        return get_option('retexify_api_stats', array());
    }
    
    /**
     * Statistiken für einen Provider abrufen
     * 
     * @param string $provider Provider-Name
     * @return array Provider-Statistiken
     */
    public function get_provider_statistics($provider) {
        $stats = $this->get_statistics();
        return isset($stats[$provider]) ? $stats[$provider] : null;
    }
    
    /**
     * Alle Zähler zurücksetzen (für Testing)
     */
    public function reset_all_limits() {
        $providers = array('openai', 'anthropic', 'gemini');
        
        foreach ($providers as $provider) {
            // Anfragen-Zähler
            delete_transient("retexify_rpm_{$provider}");
            delete_transient("retexify_rpd_{$provider}");
            delete_transient("retexify_tpm_{$provider}");
            
            // Fehler-Status
            delete_transient("retexify_api_error_{$provider}");
        }
        
        error_log('ReTexify: Alle Rate-Limits zurückgesetzt');
    }
    
    /**
     * Rate-Limit-Status für UI abrufen
     * 
     * @param string $provider Provider-Name
     * @return array Status-Informationen
     */
    public function get_limit_status($provider) {
        if (!isset($this->limits[$provider])) {
            return null;
        }
        
        $limits = $this->limits[$provider];
        
        // Aktuelle Werte
        $rpm_current = (int) get_transient("retexify_rpm_{$provider}");
        $rpd_current = (int) get_transient("retexify_rpd_{$provider}");
        $tpm_current = (int) get_transient("retexify_tpm_{$provider}");
        
        // Fehler-Status
        $error = get_transient("retexify_api_error_{$provider}");
        
        return array(
            'provider' => $provider,
            'requests_per_minute' => array(
                'current' => $rpm_current,
                'limit' => $limits['requests_per_minute'],
                'percentage' => round(($rpm_current / $limits['requests_per_minute']) * 100, 1)
            ),
            'requests_per_day' => array(
                'current' => $rpd_current,
                'limit' => $limits['requests_per_day'],
                'percentage' => round(($rpd_current / $limits['requests_per_day']) * 100, 1)
            ),
            'tokens_per_minute' => array(
                'current' => $tpm_current,
                'limit' => $limits['tokens_per_minute'],
                'percentage' => round(($tpm_current / $limits['tokens_per_minute']) * 100, 1)
            ),
            'has_error' => ($error !== false),
            'error_details' => $error
        );
    }
}

// Globale Instanz
global $retexify_rate_limiter;
$retexify_rate_limiter = new ReTexify_API_Rate_Limiter();
