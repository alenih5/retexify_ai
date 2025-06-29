<?php
/**
 * API Manager für kostenlose Services
 * Rate-Limiting, Error-Handling, Caching für ReTexify AI
 * 
 * @package ReTexify_AI_Pro
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_API_Manager {
    
    /**
     * Cache für API-Antworten (in-memory)
     */
    private static $cache = array();
    
    /**
     * Zeitstempel des letzten API-Aufrufs
     */
    private static $last_request_time = 0;
    
    /**
     * Rate-Limit zwischen API-Calls (Sekunden)
     */
    private static $rate_limit = 1;
    
    /**
     * Timeout für API-Requests (Sekunden)
     */
    private static $timeout = 8;
    
    /**
     * User-Agent für API-Calls
     */
    private static $user_agent = 'ReTexify-AI-Plugin/1.0 (+https://wordpress.org/)';
    
    /**
     * Maximale Cache-Zeit (Sekunden)
     */
    private static $cache_duration = 3600; // 1 Stunde
    
    /**
     * Hauptfunktion für sichere API-Aufrufe mit Fallback
     *
     * @param string $url API-Endpunkt URL
     * @param array $params GET-Parameter
     * @param array $options Zusätzliche Optionen
     * @return array|false API-Antwort oder false bei Fehler
     */
    public static function make_api_call($url, $params = array(), $options = array()) {
        try {
            // Cache-Key generieren
            $cache_key = self::generate_cache_key($url, $params);
            
            // Cache prüfen
            $cached_result = self::get_cached($cache_key);
            if ($cached_result !== false) {
                return $cached_result;
            }
            
            // Rate-Limiting respektieren
            self::respect_rate_limit();
            
            // URL mit Parametern zusammenbauen
            $request_url = self::build_request_url($url, $params);
            
            // HTTP-Request ausführen
            $response = self::execute_http_request($request_url, $options);
            
            if ($response === false) {
                return false;
            }
            
            // Erfolgreiche Antwort cachen
            self::set_cache($cache_key, $response);
            
            return $response;
            
        } catch (Exception $e) {
            error_log('ReTexify API Manager Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Google Suggest API-Aufruf
     *
     * @param string $keyword Suchbegriff
     * @param string $language Sprache (default: 'de')
     * @return array Suggest-Resultate
     */
    public static function google_suggest($keyword, $language = 'de') {
        if (empty($keyword)) {
            return array();
        }
        
        $url = 'http://suggestqueries.google.com/complete/search';
        $params = array(
            'client' => 'chrome',
            'q' => $keyword,
            'hl' => $language
        );
        
        $response = self::make_api_call($url, $params);
        
        if ($response && is_array($response) && isset($response[1])) {
            // Google Suggest gibt Array zurück: [query, [suggestions], ...]
            return array_slice($response[1], 0, 10); // Max 10 Vorschläge
        }
        
        return array();
    }
    
    /**
     * Wikipedia API-Aufruf für verwandte Begriffe
     *
     * @param string $term Suchbegriff
     * @param string $language Sprache (default: 'de')
     * @return array Wikipedia-Daten
     */
    public static function wikipedia_search($term, $language = 'de') {
        if (empty($term)) {
            return array();
        }
        
        $url = "https://{$language}.wikipedia.org/api/rest_v1/page/related/" . urlencode($term);
        
        $response = self::make_api_call($url, array(), array(
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        if ($response && isset($response['pages'])) {
            $related_terms = array();
            foreach ($response['pages'] as $page) {
                if (isset($page['title'])) {
                    $related_terms[] = $page['title'];
                }
            }
            return array_slice($related_terms, 0, 15); // Max 15 verwandte Begriffe
        }
        
        return array();
    }
    
    /**
     * Wiktionary API-Aufruf für Synonyme
     *
     * @param string $word Wort
     * @param string $language Sprache (default: 'de')
     * @return array Synonyme und Definitionen
     */
    public static function wiktionary_search($word, $language = 'de') {
        if (empty($word)) {
            return array();
        }
        
        $url = "https://{$language}.wiktionary.org/api/rest_v1/page/definition/" . urlencode($word);
        
        $response = self::make_api_call($url, array(), array(
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        $result = array();
        if ($response && isset($response[$language])) {
            foreach ($response[$language] as $definition) {
                if (isset($definition['definitions'])) {
                    foreach ($definition['definitions'] as $def) {
                        if (isset($def['definition'])) {
                            // Einfache Definition extrahieren (HTML-Tags entfernen)
                            $clean_def = strip_tags($def['definition']);
                            if (strlen($clean_def) > 10 && strlen($clean_def) < 200) {
                                $result[] = $clean_def;
                            }
                        }
                    }
                }
            }
        }
        
        return array_slice($result, 0, 5); // Max 5 Definitionen
    }
    
    /**
     * OpenStreetMap Nominatim für Schweizer Ortsdaten
     *
     * @param string $location Ortsname
     * @return array Ortsdaten
     */
    public static function osm_swiss_places($location) {
        if (empty($location)) {
            return array();
        }
        
        $url = 'https://nominatim.openstreetmap.org/search';
        $params = array(
            'q' => $location,
            'format' => 'json',
            'countrycodes' => 'ch',
            'limit' => 5,
            'addressdetails' => 1
        );
        
        $response = self::make_api_call($url, $params, array(
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));
        
        $places = array();
        if ($response && is_array($response)) {
            foreach ($response as $place) {
                if (isset($place['display_name']) && isset($place['address'])) {
                    $places[] = array(
                        'name' => $place['display_name'],
                        'canton' => $place['address']['state'] ?? '',
                        'city' => $place['address']['city'] ?? $place['address']['town'] ?? '',
                        'type' => $place['type'] ?? ''
                    );
                }
            }
        }
        
        return $places;
    }
    
    /**
     * Cache-Key generieren
     */
    private static function generate_cache_key($url, $params) {
        return 'retexify_api_' . md5($url . serialize($params));
    }
    
    /**
     * Gecachte Daten abrufen
     */
    private static function get_cached($key) {
        if (isset(self::$cache[$key])) {
            $cached_data = self::$cache[$key];
            
            // Cache-Gültigkeit prüfen
            if ($cached_data['timestamp'] + self::$cache_duration > time()) {
                return $cached_data['data'];
            } else {
                // Abgelaufenen Cache entfernen
                unset(self::$cache[$key]);
            }
        }
        
        return false;
    }
    
    /**
     * Daten in Cache speichern
     */
    private static function set_cache($key, $data) {
        self::$cache[$key] = array(
            'data' => $data,
            'timestamp' => time()
        );
        
        // Cache-Größe begrenzen (max 50 Einträge)
        if (count(self::$cache) > 50) {
            // Älteste Einträge entfernen
            $oldest_key = array_keys(self::$cache)[0];
            unset(self::$cache[$oldest_key]);
        }
    }
    
    /**
     * Rate-Limiting respektieren
     */
    private static function respect_rate_limit() {
        $current_time = microtime(true);
        $time_since_last = $current_time - self::$last_request_time;
        
        if ($time_since_last < self::$rate_limit) {
            $sleep_time = self::$rate_limit - $time_since_last;
            usleep($sleep_time * 1000000); // Microsekunden
        }
        
        self::$last_request_time = microtime(true);
    }
    
    /**
     * Request-URL zusammenbauen
     */
    private static function build_request_url($url, $params) {
        if (empty($params)) {
            return $url;
        }
        
        $query_string = http_build_query($params);
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        
        return $url . $separator . $query_string;
    }
    
    /**
     * HTTP-Request ausführen
     */
    private static function execute_http_request($url, $options = array()) {
        $default_args = array(
            'timeout' => self::$timeout,
            'user-agent' => self::$user_agent,
            'headers' => array(
                'Accept' => 'application/json'
            )
        );
        
        // Optionen mergen
        $args = wp_parse_args($options, $default_args);
        
        // WordPress HTTP API verwenden
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('ReTexify API Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log("ReTexify API Error: HTTP {$status_code} for URL: {$url}");
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // JSON-Response versuchen zu dekodieren
        $json_data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json_data;
        }
        
        // Fallback: Raw-Response zurückgeben
        return $body;
    }
    
    /**
     * Cache leeren (für Debugging)
     */
    public static function clear_cache() {
        self::$cache = array();
    }
    
    /**
     * API-Status prüfen
     */
    public static function test_apis() {
        $results = array();
        
        // Google Suggest testen
        $suggest_test = self::google_suggest('test', 'de');
        $results['google_suggest'] = !empty($suggest_test);
        
        // Wikipedia testen
        $wiki_test = self::wikipedia_search('Schweiz', 'de');
        $results['wikipedia'] = !empty($wiki_test);
        
        // Wiktionary testen
        $wikt_test = self::wiktionary_search('Haus', 'de');
        $results['wiktionary'] = !empty($wikt_test);
        
        // OSM testen
        $osm_test = self::osm_swiss_places('Bern');
        $results['openstreetmap'] = !empty($osm_test);
        
        return $results;
    }
}

/**
 * Helper-Funktion für globalen Zugriff
 */
function retexify_get_api_manager() {
    return new ReTexify_API_Manager();
}