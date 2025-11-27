<?php
/**
 * ReTexify Error Handler
 * 
 * Zentrale Fehlerbehandlung und Logging für das Plugin
 * 
 * @package ReTexify_AI
 * @since 4.23.0
 * @version 4.23.0
 */

if (!defined('ABSPATH')) {
    exit; // Direkten Zugriff verhindern
}

class ReTexify_Error_Handler {
    
    /**
     * Fehler-Level-Konstanten
     */
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';
    
    /**
     * Fehler-Kontexte
     */
    const CONTEXT_AJAX = 'ajax';
    const CONTEXT_AI_ENGINE = 'ai_engine';
    const CONTEXT_API = 'api';
    const CONTEXT_DATABASE = 'database';
    const CONTEXT_FILE = 'file';
    const CONTEXT_SECURITY = 'security';
    const CONTEXT_VALIDATION = 'validation';
    const CONTEXT_GENERAL = 'general';
    
    /**
     * Temporäre Fehler für aktuelle Session
     */
    private static $session_errors = array();
    
    /**
     * Loggt einen Fehler mit Kontext-Informationen
     * 
     * @since 4.10.0
     * 
     * @param string $context   Fehler-Kontext (CONTEXT_* Konstanten)
     * @param string $message   Fehler-Nachricht
     * @param mixed  $data      Zusätzliche Daten (optional)
     * @param string $level     Fehler-Level (LEVEL_* Konstanten)
     * @param bool   $user_friendly Soll eine benutzerfreundliche Nachricht generiert werden?
     * @return string Benutzerfreundliche Fehler-ID
     */
    public static function log_error($context, $message, $data = null, $level = self::LEVEL_ERROR, $user_friendly = true) {
        // Fehler-ID generieren
        $error_id = self::generate_error_id();
        
        // Fehler-Objekt erstellen
        $error = array(
            'id' => $error_id,
            'time' => current_time('mysql'),
            'timestamp' => time(),
            'context' => $context,
            'level' => $level,
            'message' => $message,
            'data' => $data,
            'user_id' => get_current_user_id(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'ip_address' => self::get_client_ip(),
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'N/A',
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => RETEXIFY_VERSION ?? 'Unknown'
        );
        
        // In Session-Array speichern
        self::$session_errors[] = $error;
        
        // In WordPress Error-Log schreiben
        self::write_to_wordpress_log($error);
        
        // In eigene Log-Datei schreiben (optional)
        if (WP_DEBUG || $level === self::LEVEL_ERROR) {
            self::write_to_custom_log($error);
        }
        
        // In Datenbank speichern (optional, für Admin-Interface)
        if (self::should_store_in_database($level)) {
            self::save_to_database($error);
        }
        
        // Bei kritischen Fehlern Admin benachrichtigen
        if ($level === self::LEVEL_ERROR && $context === self::CONTEXT_SECURITY) {
            self::notify_admin_critical_error($error);
        }
        
        return $error_id;
    }
    
    /**
     * Loggt einen AJAX-Fehler mit spezieller Behandlung
     * 
     * @since 4.10.0
     * 
     * @param string $action    AJAX-Action
     * @param string $message   Fehler-Nachricht
     * @param mixed  $data      Zusätzliche Daten
     * @param string $level     Fehler-Level
     * @return string Fehler-ID
     */
    public static function log_ajax_error($action, $message, $data = null, $level = self::LEVEL_ERROR) {
        $context_message = "AJAX Error in Action: {$action}";
        
        return self::log_error(
            self::CONTEXT_AJAX,
            $context_message . ' - ' . $message,
            array_merge($data ?: array(), array('ajax_action' => $action)),
            $level
        );
    }
    
    /**
     * Loggt einen API-Fehler
     * 
     * @since 4.10.0
     * 
     * @param string $provider  API-Provider (openai, anthropic, gemini)
     * @param string $endpoint  API-Endpoint
     * @param string $message   Fehler-Nachricht
     * @param int    $http_code HTTP-Status-Code
     * @param mixed  $response  API-Response
     * @return string Fehler-ID
     */
    public static function log_api_error($provider, $endpoint, $message, $http_code = null, $response = null) {
        $context_message = "API Error - Provider: {$provider}, Endpoint: {$endpoint}";
        if ($http_code) {
            $context_message .= ", HTTP Code: {$http_code}";
        }
        
        $data = array(
            'provider' => $provider,
            'endpoint' => $endpoint,
            'http_code' => $http_code,
            'response' => $response
        );
        
        return self::log_error(
            self::CONTEXT_API,
            $context_message . ' - ' . $message,
            $data,
            self::LEVEL_ERROR
        );
    }
    
    /**
     * Loggt einen Sicherheitsfehler
     * 
     * @since 4.10.0
     * 
     * @param string $type      Art des Sicherheitsfehlers
     * @param string $message   Fehler-Nachricht
     * @param mixed  $data      Zusätzliche Daten
     * @return string Fehler-ID
     */
    public static function log_security_error($type, $message, $data = null) {
        $context_message = "Security Error - Type: {$type}";
        
        return self::log_error(
            self::CONTEXT_SECURITY,
            $context_message . ' - ' . $message,
            $data,
            self::LEVEL_ERROR,
            false // Keine benutzerfreundliche Nachricht bei Sicherheitsfehlern
        );
    }
    
    /**
     * Gibt die neuesten Fehler zurück
     * 
     * @since 4.10.0
     * 
     * @param int    $limit     Anzahl Fehler
     * @param string $context   Optional: Nur Fehler aus bestimmten Kontext
     * @param string $level     Optional: Nur Fehler mit bestimmten Level
     * @return array Array von Fehlern
     */
    public static function get_recent_errors($limit = 10, $context = null, $level = null) {
        // Erst aus Datenbank laden
        $db_errors = self::get_errors_from_database($limit, $context, $level);
        
        // Dann Session-Fehler hinzufügen
        $session_errors = self::$session_errors;
        
        // Filter anwenden
        if ($context) {
            $session_errors = array_filter($session_errors, function($error) use ($context) {
                return $error['context'] === $context;
            });
        }
        
        if ($level) {
            $session_errors = array_filter($session_errors, function($error) use ($level) {
                return $error['level'] === $level;
            });
        }
        
        // Zusammenführen und sortieren
        $all_errors = array_merge($db_errors, $session_errors);
        
        // Nach Zeit sortieren (neueste zuerst)
        usort($all_errors, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        // Limit anwenden
        return array_slice($all_errors, 0, $limit);
    }
    
    /**
     * Gibt eine benutzerfreundliche Fehlermeldung zurück
     * 
     * @since 4.10.0
     * 
     * @param string $error_id Fehler-ID
     * @param string $context  Fehler-Kontext
     * @return string Benutzerfreundliche Nachricht
     */
    public static function get_user_friendly_message($error_id, $context = null) {
        $messages = array(
            self::CONTEXT_AJAX => 'Ein Fehler ist bei der Verarbeitung aufgetreten. Bitte versuchen Sie es erneut.',
            self::CONTEXT_AI_ENGINE => 'Die KI-Verarbeitung konnte nicht abgeschlossen werden. Bitte überprüfen Sie Ihre API-Einstellungen.',
            self::CONTEXT_API => 'Ein Verbindungsfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung.',
            self::CONTEXT_DATABASE => 'Ein Datenbankfehler ist aufgetreten. Bitte kontaktieren Sie den Administrator.',
            self::CONTEXT_FILE => 'Ein Dateizugriffsfehler ist aufgetreten. Bitte überprüfen Sie die Dateiberechtigungen.',
            self::CONTEXT_SECURITY => 'Ein Sicherheitsfehler ist aufgetreten. Der Zugriff wurde verweigert.',
            self::CONTEXT_VALIDATION => 'Die eingegebenen Daten sind ungültig. Bitte überprüfen Sie Ihre Eingaben.',
            self::CONTEXT_GENERAL => 'Ein unerwarteter Fehler ist aufgetreten. Bitte versuchen Sie es erneut.'
        );
        
        if ($context && isset($messages[$context])) {
            return $messages[$context];
        }
        
        return $messages[self::CONTEXT_GENERAL];
    }
    
    /**
     * Bereinigt alte Fehler aus der Datenbank
     * 
     * @since 4.10.0
     * 
     * @param int $days Fehler älter als X Tage löschen
     * @return int Anzahl gelöschter Fehler
     */
    public static function cleanup_old_errors($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'retexify_errors';
        
        // Prüfen ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return 0;
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE time < %s",
            $cutoff_date
        ));
        
        if ($deleted > 0) {
            error_log("ReTexify: {$deleted} alte Fehler aus Datenbank bereinigt");
        }
        
        return $deleted;
    }
    
    /**
     * Erstellt die Fehler-Datenbank-Tabelle
     * 
     * @since 4.10.0
     */
    public static function create_error_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'retexify_errors';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id varchar(32) NOT NULL,
            time datetime NOT NULL,
            timestamp int(11) NOT NULL,
            context varchar(50) NOT NULL,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            data longtext,
            user_id bigint(20),
            user_agent text,
            ip_address varchar(45),
            url text,
            referer text,
            wordpress_version varchar(20),
            plugin_version varchar(20),
            PRIMARY KEY (id),
            KEY time (time),
            KEY context (context),
            KEY level (level),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Generiert eine eindeutige Fehler-ID
     * 
     * @since 4.10.0
     * 
     * @return string Eindeutige Fehler-ID
     */
    private static function generate_error_id() {
        return 'err_' . substr(md5(uniqid('retexify_error', true)), 0, 16);
    }
    
    /**
     * Schreibt Fehler in WordPress Log
     * 
     * @since 4.10.0
     * 
     * @param array $error Fehler-Objekt
     */
    private static function write_to_wordpress_log($error) {
        $log_message = sprintf(
            '[ReTexify %s] %s: %s',
            strtoupper($error['level']),
            $error['context'],
            $error['message']
        );
        
        if ($error['data']) {
            $log_message .= ' | Data: ' . json_encode($error['data']);
        }
        
        error_log($log_message);
    }
    
    /**
     * Schreibt Fehler in eigene Log-Datei
     * 
     * @since 4.10.0
     * 
     * @param array $error Fehler-Objekt
     */
    private static function write_to_custom_log($error) {
        $log_file = WP_CONTENT_DIR . '/retexify-errors.log';
        $log_entry = json_encode($error) . "\n";
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Speichert Fehler in Datenbank
     * 
     * @since 4.10.0
     * 
     * @param array $error Fehler-Objekt
     */
    private static function save_to_database($error) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'retexify_errors';
        
        // Prüfen ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::create_error_table();
        }
        
        $wpdb->insert(
            $table_name,
            array(
                'id' => $error['id'],
                'time' => $error['time'],
                'timestamp' => $error['timestamp'],
                'context' => $error['context'],
                'level' => $error['level'],
                'message' => $error['message'],
                'data' => $error['data'] ? json_encode($error['data']) : null,
                'user_id' => $error['user_id'],
                'user_agent' => $error['user_agent'],
                'ip_address' => $error['ip_address'],
                'url' => $error['url'],
                'referer' => $error['referer'],
                'wordpress_version' => $error['wordpress_version'],
                'plugin_version' => $error['plugin_version']
            ),
            array(
                '%s', '%s', '%d', '%s', '%s', '%s', '%s', 
                '%d', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
    }
    
    /**
     * Lädt Fehler aus Datenbank
     * 
     * @since 4.10.0
     * 
     * @param int    $limit   Anzahl Fehler
     * @param string $context Optional: Kontext-Filter
     * @param string $level   Optional: Level-Filter
     * @return array Fehler-Array
     */
    private static function get_errors_from_database($limit, $context = null, $level = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'retexify_errors';
        
        // Prüfen ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        $where_conditions = array();
        $where_values = array();
        
        if ($context) {
            $where_conditions[] = 'context = %s';
            $where_values[] = $context;
        }
        
        if ($level) {
            $where_conditions[] = 'level = %s';
            $where_values[] = $level;
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT * FROM {$table_name} {$where_clause} ORDER BY time DESC LIMIT %d";
        $where_values[] = $limit;
        
        $results = $wpdb->get_results($wpdb->prepare($sql, $where_values), ARRAY_A);
        
        // JSON-Daten dekodieren
        foreach ($results as &$result) {
            if ($result['data']) {
                $result['data'] = json_decode($result['data'], true);
            }
        }
        
        return $results;
    }
    
    /**
     * Bestimmt ob Fehler in Datenbank gespeichert werden soll
     * 
     * @since 4.10.0
     * 
     * @param string $level Fehler-Level
     * @return bool True wenn gespeichert werden soll
     */
    private static function should_store_in_database($level) {
        // Nur Error- und Warning-Level in DB speichern
        return in_array($level, array(self::LEVEL_ERROR, self::LEVEL_WARNING));
    }
    
    /**
     * Benachrichtigt Admin bei kritischen Fehlern
     * 
     * @since 4.10.0
     * 
     * @param array $error Fehler-Objekt
     */
    private static function notify_admin_critical_error($error) {
        $admin_email = get_option('admin_email');
        
        if (!$admin_email) {
            return;
        }
        
        $subject = 'ReTexify AI: Kritischer Sicherheitsfehler';
        $message = sprintf(
            "Ein kritischer Sicherheitsfehler ist aufgetreten:\n\n" .
            "Fehler-ID: %s\n" .
            "Kontext: %s\n" .
            "Nachricht: %s\n" .
            "User-ID: %s\n" .
            "IP-Adresse: %s\n" .
            "URL: %s\n" .
            "Zeitpunkt: %s\n\n" .
            "Bitte überprüfen Sie die Logs für weitere Details.",
            $error['id'],
            $error['context'],
            $error['message'],
            $error['user_id'],
            $error['ip_address'],
            $error['url'],
            $error['time']
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Holt die Client-IP-Adresse
     * 
     * @since 4.10.0
     * 
     * @return string IP-Adresse
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Gibt Fehler-Statistiken zurück
     * 
     * @since 4.10.0
     * 
     * @param int $days Anzahl Tage für Statistik
     * @return array Fehler-Statistiken
     */
    public static function get_error_statistics($days = 7) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'retexify_errors';
        
        // Prüfen ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array();
        }
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Fehler nach Level
        $by_level = $wpdb->get_results($wpdb->prepare(
            "SELECT level, COUNT(*) as count FROM {$table_name} WHERE time >= %s GROUP BY level",
            $cutoff_date
        ), ARRAY_A);
        
        // Fehler nach Kontext
        $by_context = $wpdb->get_results($wpdb->prepare(
            "SELECT context, COUNT(*) as count FROM {$table_name} WHERE time >= %s GROUP BY context",
            $cutoff_date
        ), ARRAY_A);
        
        // Tägliche Fehler
        $daily = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(time) as date, COUNT(*) as count FROM {$table_name} WHERE time >= %s GROUP BY DATE(time) ORDER BY date DESC",
            $cutoff_date
        ), ARRAY_A);
        
        return array(
            'by_level' => $by_level,
            'by_context' => $by_context,
            'daily' => $daily,
            'total_errors' => array_sum(array_column($by_level, 'count')),
            'period_days' => $days
        );
    }
}
