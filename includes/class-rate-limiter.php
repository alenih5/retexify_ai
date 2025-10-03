<?php
/**
 * ReTexify Rate Limiter
 * 
 * Schutz vor Missbrauch von AJAX-Calls durch Rate-Limiting
 * 
 * @package ReTexify_AI
 * @since 4.10.0
 */

if (!defined('ABSPATH')) {
    exit; // Direkten Zugriff verhindern
}

class ReTexify_Rate_Limiter {
    
    /**
     * Standard-Limits für verschiedene Aktionen
     */
    private static $default_limits = array(
        'generate_seo' => array('max_calls' => 30, 'timeframe' => HOUR_IN_SECONDS),
        'test_api' => array('max_calls' => 60, 'timeframe' => HOUR_IN_SECONDS),
        'keyword_research' => array('max_calls' => 20, 'timeframe' => HOUR_IN_SECONDS),
        'export_data' => array('max_calls' => 10, 'timeframe' => HOUR_IN_SECONDS),
        'import_data' => array('max_calls' => 5, 'timeframe' => HOUR_IN_SECONDS),
        'system_test' => array('max_calls' => 100, 'timeframe' => HOUR_IN_SECONDS)
    );
    
    /**
     * Prüft ob ein User das Rate-Limit für eine bestimmte Aktion überschritten hat
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id   WordPress User-ID
     * @param string $action    Aktion die geprüft werden soll
     * @param int    $max_calls Maximale Anzahl Calls (optional, überschreibt Standard)
     * @param int    $timeframe Zeitfenster in Sekunden (optional, überschreibt Standard)
     * @return bool True wenn Limit nicht überschritten, false wenn überschritten
     */
    public static function check_limit($user_id, $action, $max_calls = null, $timeframe = null) {
        // Standard-Limits für Aktion abrufen
        $default_limit = self::$default_limits[$action] ?? array('max_calls' => 50, 'timeframe' => HOUR_IN_SECONDS);
        
        // Parameter verwenden oder Standard-Limits
        $max_calls = $max_calls ?? $default_limit['max_calls'];
        $timeframe = $timeframe ?? $default_limit['timeframe'];
        
        // Transient-Key generieren
        $transient_key = 'retexify_rate_' . $user_id . '_' . $action;
        
        // Aktuelle Anzahl Calls abrufen
        $calls = get_transient($transient_key);
        
        if ($calls === false) {
            // Erster Call - Transient setzen
            set_transient($transient_key, 1, $timeframe);
            return true;
        }
        
        if ($calls >= $max_calls) {
            // Limit erreicht
            self::log_rate_limit_hit($user_id, $action, $calls, $max_calls);
            return false;
        }
        
        // Call-Counter erhöhen
        set_transient($transient_key, $calls + 1, $timeframe);
        return true;
    }
    
    /**
     * Gibt die verbleibenden Calls für eine Aktion zurück
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id User-ID
     * @param string $action  Aktion
     * @return int Anzahl verbleibender Calls
     */
    public static function get_remaining_calls($user_id, $action) {
        $default_limit = self::$default_limits[$action] ?? array('max_calls' => 50);
        $max_calls = $default_limit['max_calls'];
        
        $transient_key = 'retexify_rate_' . $user_id . '_' . $action;
        $calls = get_transient($transient_key);
        
        if ($calls === false) {
            return $max_calls;
        }
        
        return max(0, $max_calls - $calls);
    }
    
    /**
     * Gibt das Zeitfenster bis zum Reset zurück
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id User-ID
     * @param string $action  Aktion
     * @return int Sekunden bis zum Reset
     */
    public static function get_reset_time($user_id, $action) {
        $transient_key = 'retexify_rate_' . $user_id . '_' . $action;
        
        // Transient-Timeout abrufen
        $timeout = get_option('_transient_timeout_' . $transient_key);
        
        if (!$timeout) {
            return 0;
        }
        
        return max(0, $timeout - time());
    }
    
    /**
     * Setzt das Rate-Limit für einen User zurück (Admin-Funktion)
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id User-ID
     * @param string $action  Aktion (optional, wenn leer werden alle Aktionen zurückgesetzt)
     * @return bool True bei Erfolg
     */
    public static function reset_limit($user_id, $action = null) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        if ($action) {
            // Spezifische Aktion zurücksetzen
            $transient_key = 'retexify_rate_' . $user_id . '_' . $action;
            delete_transient($transient_key);
        } else {
            // Alle Aktionen für User zurücksetzen
            global $wpdb;
            $pattern = 'retexify_rate_' . $user_id . '_%';
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            ));
        }
        
        return true;
    }
    
    /**
     * Gibt Rate-Limit-Status für alle Aktionen eines Users zurück
     * 
     * @since 4.10.0
     * 
     * @param int $user_id User-ID
     * @return array Rate-Limit-Status für alle Aktionen
     */
    public static function get_user_rate_status($user_id) {
        $status = array();
        
        foreach (self::$default_limits as $action => $limit) {
            $transient_key = 'retexify_rate_' . $user_id . '_' . $action;
            $calls = get_transient($transient_key);
            
            $status[$action] = array(
                'current_calls' => $calls ?: 0,
                'max_calls' => $limit['max_calls'],
                'remaining_calls' => self::get_remaining_calls($user_id, $action),
                'reset_time' => self::get_reset_time($user_id, $action),
                'timeframe' => $limit['timeframe'],
                'is_limited' => $calls >= $limit['max_calls']
            );
        }
        
        return $status;
    }
    
    /**
     * Loggt Rate-Limit-Überschreitungen
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id    User-ID
     * @param string $action     Aktion
     * @param int    $calls      Aktuelle Anzahl Calls
     * @param int    $max_calls  Maximale erlaubte Calls
     */
    private static function log_rate_limit_hit($user_id, $action, $calls, $max_calls) {
        $user = get_user_by('id', $user_id);
        $user_login = $user ? $user->user_login : 'Unknown';
        
        $log_message = sprintf(
            'ReTexify Rate Limit überschritten - User: %s (ID: %d), Aktion: %s, Calls: %d/%d',
            $user_login,
            $user_id,
            $action,
            $calls,
            $max_calls
        );
        
        error_log($log_message);
        
        // Optional: In eigene Log-Datei schreiben
        if (WP_DEBUG) {
            $log_file = WP_CONTENT_DIR . '/retexify-rate-limits.log';
            $log_entry = date('Y-m-d H:i:s') . ' - ' . $log_message . "\n";
            file_put_contents($log_file, $log_entry, FILE_APPEND);
        }
        
        // Optional: Admin-Benachrichtigung bei wiederholten Überschreitungen
        $violation_key = 'retexify_rate_violations_' . $user_id;
        $violations = get_transient($violation_key);
        $violations = $violations ?: 0;
        $violations++;
        
        if ($violations >= 5) {
            // Nach 5 Verletzungen Admin benachrichtigen
            self::notify_admin_rate_limit_abuse($user_id, $user_login, $violations);
            set_transient($violation_key, 0, DAY_IN_SECONDS); // Reset nach 24h
        } else {
            set_transient($violation_key, $violations, HOUR_IN_SECONDS);
        }
    }
    
    /**
     * Benachrichtigt Admin bei Rate-Limit-Missbrauch
     * 
     * @since 4.10.0
     * 
     * @param int    $user_id    User-ID
     * @param string $user_login Username
     * @param int    $violations Anzahl Verletzungen
     */
    private static function notify_admin_rate_limit_abuse($user_id, $user_login, $violations) {
        $admin_email = get_option('admin_email');
        
        if (!$admin_email) {
            return;
        }
        
        $subject = 'ReTexify AI: Rate-Limit-Missbrauch erkannt';
        $message = sprintf(
            "Ein User hat wiederholt das Rate-Limit überschritten:\n\n" .
            "User: %s (ID: %d)\n" .
            "Verletzungen: %d\n" .
            "Zeitpunkt: %s\n" .
            "Website: %s\n\n" .
            "Bitte überprüfen Sie die Logs für weitere Details.",
            $user_login,
            $user_id,
            $violations,
            current_time('mysql'),
            home_url()
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Validiert Rate-Limit-Parameter
     * 
     * @since 4.10.0
     * 
     * @param string $action    Aktion
     * @param int    $max_calls Maximale Calls
     * @param int    $timeframe Zeitfenster
     * @return bool True wenn Parameter gültig
     */
    public static function validate_parameters($action, $max_calls, $timeframe) {
        // Aktion validieren
        if (empty($action) || !is_string($action)) {
            return false;
        }
        
        // Max Calls validieren
        if (!is_numeric($max_calls) || $max_calls < 1 || $max_calls > 1000) {
            return false;
        }
        
        // Zeitfenster validieren
        if (!is_numeric($timeframe) || $timeframe < 60 || $timeframe > DAY_IN_SECONDS) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Gibt Rate-Limit-Konfiguration zurück
     * 
     * @since 4.10.0
     * 
     * @return array Standard-Limits
     */
    public static function get_default_limits() {
        return self::$default_limits;
    }
    
    /**
     * Setzt benutzerdefinierte Limits (Admin-Funktion)
     * 
     * @since 4.10.0
     * 
     * @param array $limits Neue Limits
     * @return bool True bei Erfolg
     */
    public static function set_custom_limits($limits) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        
        if (!is_array($limits)) {
            return false;
        }
        
        // Limits validieren
        foreach ($limits as $action => $limit) {
            if (!self::validate_parameters($action, $limit['max_calls'], $limit['timeframe'])) {
                return false;
            }
        }
        
        // In Option speichern
        update_option('retexify_custom_rate_limits', $limits);
        
        return true;
    }
    
    /**
     * Lädt benutzerdefinierte Limits
     * 
     * @since 4.10.0
     * 
     * @return array Benutzerdefinierte Limits oder Standard-Limits
     */
    public static function get_effective_limits() {
        $custom_limits = get_option('retexify_custom_rate_limits', array());
        
        if (empty($custom_limits)) {
            return self::$default_limits;
        }
        
        // Custom Limits mit Standard-Limits mergen
        return array_merge(self::$default_limits, $custom_limits);
    }
}
