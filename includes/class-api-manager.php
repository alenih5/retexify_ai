<?php
/**
 * ReTexify API Manager - Vollständige Implementierung
 * 
 * API-Verwaltung für kostenlose Services mit robustem Error-Handling
 * Rate-Limiting, Caching und respektvolle API-Nutzung
 * 
 * @package ReTexify_AI_Pro
 * @version 3.7.0
 * @author Imponi
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_API_Manager {
    
    /**
     * Cache für API-Antworten (in-memory für Session)
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
    private static $user_agent = 'ReTexify-AI-Plugin/3.7.0 (WordPress SEO Tool; +https://imponi.ch)';
    
    /**
     * Maximale Cache-Zeit (Sekunden)
     */
    private static $cache_duration = 3600; // 1 Stunde
    
    /**
     * Maximale Cache-Einträge
     */
    private static $max_cache_entries = 50;
    
    /**
     * Debug-Modus aktiviert
     */
    private static $debug_mode = false;
    
    /**
     * Hauptfunktion für sichere API-Aufrufe mit Fallback-System
     *
     * @param string $url API-Endpunkt URL
     * @param array $params GET-Parameter
     * @param array $options Zusätzliche Optionen
     * @return array|string|false API-Antwort oder false bei Fehler
     */
    public static function make_api_call($url, $params = array(), $options = array()) {
        try {
            // Debug-Log falls aktiviert
            if (self::$debug_mode) {
                error_log("ReTexify API Call: {$url} with params: " . json_encode($params));
            }
            
            // Cache-Key generieren
            $cache_key = self::generate_cache_key($url, $params);
            
            // Cache prüfen
            $cached_result = self::get_cached($cache_key);
            if ($cached_result !== false) {
                if (self::$debug_mode) {
                    error_log("ReTexify API: Using cached result for {$url}");
                }
                return $cached_result;
            }
            
            // Rate-Limiting respektieren
            self::respect_rate_limit();
            
            // URL mit Parametern zusammenbauen
            $request_url = self::build_request_url($url, $params);
            
            // HTTP-Request ausführen
            $response = self::execute_http_request($request_url, $options);
            
            if ($response === false) {
                if (self::$debug_mode) {
                    error_log("ReTexify API: Request failed for {$url}");
                }
                return false;
            }
            
            // Erfolgreiche Antwort cachen
            self::set_cache($cache_key, $response);
            
            if (self::$debug_mode) {
                error_log("ReTexify API: Successful response from {$url}");
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log('ReTexify API Manager Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Google Suggest API-Aufruf für Keyword-Autocomplete
     *
     * @param string $keyword Suchbegriff
     * @param string $language Sprache (default: 'de')
     * @return array Suggest-Resultate
     */
    public static function google_suggest($keyword, $language = 'de') {
        if (empty($keyword) || strlen($keyword) < 2) {
            return array();
        }
        
        $url = 'http://suggestqueries.google.com/complete/search';
        $params = array(
            'client' => 'chrome',
            'q' => $keyword,
            'hl' => $language
        );
        
        $response = self::make_api_call($url, $params, array(
            'timeout' => 5 // Kürzerer Timeout für Google Suggest
        ));
        
        if ($response && is_array($response) && isset($response[1])) {
            // Google Suggest gibt Array zurück: [query, [suggestions], ...]
            $suggestions = array_slice($response[1], 0, 10);
            
            // Leere oder zu kurze Suggestions filtern
            $filtered_suggestions = array_filter($suggestions, function($suggestion) {
                return !empty($suggestion) && strlen($suggestion) > 2;
            });
            
            return array_values($filtered_suggestions);
        }
        
        return array();
    }
    
    /**
     * Wikipedia API-Aufruf für verwandte Begriffe und Artikel
     *
     * @param string $term Suchbegriff
     * @param string $language Sprache (default: 'de')
     * @return array Wikipedia-Daten
     */
    public static function wikipedia_search($term, $language = 'de') {
        if (empty($term) || strlen($term) < 2) {
            return array();
        }
        
        // OpenSearch API für bessere und zuverlässigere Ergebnisse
        $url = "https://{$language}.wikipedia.org/w/api.php";
        $params = array(
            'action' => 'opensearch',
            'search' => $term,
            'limit' => 15,
            'format' => 'json',
            'namespace' => 0 // Nur Hauptnamespace
        );
        
        $response = self::make_api_call($url, $params, array(
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => self::$user_agent
            )
        ));
        
        if ($response && is_array($response) && isset($response[1])) {
            $related_terms = array_slice($response[1], 0, 15);
            
            // Leere oder zu kurze Begriffe filtern
            $filtered_terms = array_filter($related_terms, function($term) {
                return !empty($term) && strlen($term) > 2 && strlen($term) < 100;
            });
            
            return array_values($filtered_terms);
        }
        
        return array();
    }
    
    /**
     * Alternative Wikipedia API-Methode für Seiten-Extracts
     *
     * @param string $term Suchbegriff
     * @param string $language Sprache (default: 'de')
     * @return array Seitenauszüge
     */
    public static function wikipedia_extracts($term, $language = 'de') {
        if (empty($term)) {
            return array();
        }
        
        $url = "https://{$language}.wikipedia.org/w/api.php";
        $params = array(
            'action' => 'query',
            'format' => 'json',
            'titles' => $term,
            'prop' => 'extracts',
            'exintro' => true,
            'explaintext' => true,
            'exsectionformat' => 'plain',
            'exchars' => 500
        );
        
        $response = self::make_api_call($url, $params);
        
        $extracts = array();
        if ($response && isset($response['query']['pages'])) {
            foreach ($response['query']['pages'] as $page) {
                if (isset($page['extract']) && !empty($page['extract'])) {
                    $extracts[] = substr($page['extract'], 0, 300);
                }
            }
        }
        
        return $extracts;
    }
    
    /**
     * Wiktionary API-Aufruf für Synonyme und Definitionen
     *
     * @param string $word Wort
     * @param string $language Sprache (default: 'de')
     * @return array Synonyme und Definitionen
     */
    public static function wiktionary_search($word, $language = 'de') {
        if (empty($word) || strlen($word) < 2) {
            return array();
        }
        
        // Parse API für bessere Ergebnisse
        $url = "https://{$language}.wiktionary.org/w/api.php";
        $params = array(
            'action' => 'parse',
            'page' => $word,
            'format' => 'json',
            'prop' => 'wikitext',
            'section' => 0
        );
        
        $response = self::make_api_call($url, $params);
        
        $synonyms = array();
        if ($response && isset($response['parse']['wikitext']['*'])) {
            $wikitext = $response['parse']['wikitext']['*'];
            
            // Einfache Synonym-Extraktion aus Wikitext
            if (preg_match_all('/(?:Synonyme?|Synonym|ähnlich|gleichbedeutend):?\s*\[\[([^\]]+)\]\]/i', $wikitext, $matches)) {
                foreach ($matches[1] as $match) {
                    $clean_match = trim(explode('|', $match)[0]); // Pipe-separated Links berücksichtigen
                    if (strlen($clean_match) > 2 && strlen($clean_match) < 50) {
                        $synonyms[] = $clean_match;
                    }
                }
            }
            
            // Alternative Muster für Synonyme
            if (empty($synonyms) && preg_match_all('/\[\[([^\]]+)\]\]/i', $wikitext, $matches)) {
                foreach (array_slice($matches[1], 0, 8) as $match) {
                    $clean_match = trim(explode('|', $match)[0]);
                    if (strlen($clean_match) > 2 && strlen($clean_match) < 50) {
                        $synonyms[] = $clean_match;
                    }
                }
            }
        }
        
        return array_slice(array_unique($synonyms), 0, 8);
    }
    
    /**
     * OpenStreetMap Nominatim für Schweizer Ortsdaten und Local SEO
     *
     * @param string $location Ortsname
     * @param int $limit Maximale Anzahl Ergebnisse (default: 5)
     * @return array Ortsdaten
     */
    public static function osm_swiss_places($location, $limit = 5) {
        if (empty($location) || strlen($location) < 2) {
            return array();
        }
        
        $url = 'https://nominatim.openstreetmap.org/search';
        $params = array(
            'q' => $location,
            'format' => 'json',
            'countrycodes' => 'ch',
            'limit' => min($limit, 10),
            'addressdetails' => 1,
            'extratags' => 1
        );
        
        $response = self::make_api_call($url, $params, array(
            'headers' => array(
                'Accept' => 'application/json',
                'User-Agent' => self::$user_agent
            ),
            'timeout' => 6
        ));
        
        $places = array();
        if ($response && is_array($response)) {
            foreach ($response as $place) {
                if (isset($place['display_name']) && isset($place['address'])) {
                    $places[] = array(
                        'name' => $place['display_name'],
                        'canton' => $place['address']['state'] ?? '',
                        'city' => $place['address']['city'] ?? $place['address']['town'] ?? $place['address']['village'] ?? '',
                        'type' => $place['type'] ?? '',
                        'class' => $place['class'] ?? '',
                        'importance' => $place['importance'] ?? 0.5,
                        'lat' => $place['lat'] ?? '',
                        'lon' => $place['lon'] ?? ''
                    );
                }
            }
        }
        
        return $places;
    }
    
    /**
     * Schweizer Kantone-Datenbank für Local SEO
     * 
     * @return array Alle Schweizer Kantone mit Details
     */
    public static function get_swiss_cantons() {
        return array(
            'AG' => array('name' => 'Aargau', 'capital' => 'Aarau'),
            'AI' => array('name' => 'Appenzell Innerrhoden', 'capital' => 'Appenzell'),
            'AR' => array('name' => 'Appenzell Ausserrhoden', 'capital' => 'Herisau'),
            'BE' => array('name' => 'Bern', 'capital' => 'Bern'),
            'BL' => array('name' => 'Basel-Landschaft', 'capital' => 'Liestal'),
            'BS' => array('name' => 'Basel-Stadt', 'capital' => 'Basel'),
            'FR' => array('name' => 'Freiburg', 'capital' => 'Freiburg'),
            'GE' => array('name' => 'Genf', 'capital' => 'Genf'),
            'GL' => array('name' => 'Glarus', 'capital' => 'Glarus'),
            'GR' => array('name' => 'Graubünden', 'capital' => 'Chur'),
            'JU' => array('name' => 'Jura', 'capital' => 'Delsberg'),
            'LU' => array('name' => 'Luzern', 'capital' => 'Luzern'),
            'NE' => array('name' => 'Neuenburg', 'capital' => 'Neuenburg'),
            'NW' => array('name' => 'Nidwalden', 'capital' => 'Stans'),
            'OW' => array('name' => 'Obwalden', 'capital' => 'Sarnen'),
            'SG' => array('name' => 'St. Gallen', 'capital' => 'St. Gallen'),
            'SH' => array('name' => 'Schaffhausen', 'capital' => 'Schaffhausen'),
            'SO' => array('name' => 'Solothurn', 'capital' => 'Solothurn'),
            'SZ' => array('name' => 'Schwyz', 'capital' => 'Schwyz'),
            'TG' => array('name' => 'Thurgau', 'capital' => 'Frauenfeld'),
            'TI' => array('name' => 'Tessin', 'capital' => 'Bellinzona'),
            'UR' => array('name' => 'Uri', 'capital' => 'Altdorf'),
            'VD' => array('name' => 'Waadt', 'capital' => 'Lausanne'),
            'VS' => array('name' => 'Wallis', 'capital' => 'Sitten'),
            'ZG' => array('name' => 'Zug', 'capital' => 'Zug'),
            'ZH' => array('name' => 'Zürich', 'capital' => 'Zürich')
        );
    }
    
    /**
     * Cache-Key generieren basierend auf URL und Parametern
     *
     * @param string $url API-URL
     * @param array $params Parameter
     * @return string Cache-Key
     */
    private static function generate_cache_key($url, $params) {
        return 'retexify_api_' . md5($url . serialize($params));
    }
    
    /**
     * Gecachte Daten abrufen
     *
     * @param string $key Cache-Key
     * @return mixed|false Gecachte Daten oder false
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
     *
     * @param string $key Cache-Key
     * @param mixed $data Zu cachende Daten
     */
    private static function set_cache($key, $data) {
        self::$cache[$key] = array(
            'data' => $data,
            'timestamp' => time()
        );
        
        // Cache-Größe begrenzen
        if (count(self::$cache) > self::$max_cache_entries) {
            // Älteste Einträge entfernen
            $oldest_key = array_keys(self::$cache)[0];
            unset(self::$cache[$oldest_key]);
        }
    }
    
    /**
     * Rate-Limiting respektieren - verhindert API-Überlastung
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
     * Request-URL zusammenbauen mit Parametern
     *
     * @param string $url Basis-URL
     * @param array $params GET-Parameter
     * @return string Vollständige URL
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
     * HTTP-Request ausführen mit WordPress HTTP API
     *
     * @param string $url Request-URL
     * @param array $options Request-Optionen
     * @return array|string|false Response oder false bei Fehler
     */
    private static function execute_http_request($url, $options = array()) {
        $default_args = array(
            'timeout' => self::$timeout,
            'user-agent' => self::$user_agent,
            'headers' => array(
                'Accept' => 'application/json'
            ),
            'sslverify' => true,
            'redirection' => 3
        );
        
        // Optionen mergen
        $args = wp_parse_args($options, $default_args);
        
        // WordPress HTTP API verwenden
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('ReTexify API HTTP Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code < 200 || $status_code >= 300) {
            error_log("ReTexify API HTTP Status Error: {$status_code} for URL: {$url}");
            return false;
        }
        
        if (empty($body)) {
            error_log("ReTexify API Empty Response for URL: {$url}");
            return false;
        }
        
        // JSON dekodieren falls möglich
        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        
        // Als String zurückgeben falls kein valides JSON
        return $body;
    }
    
    /**
     * API-Status für alle Services testen
     * 
     * @return array Status aller APIs
     */
    public static function test_apis() {
        $status = array(
            'google_suggest' => false,
            'wikipedia' => false,
            'wiktionary' => false,
            'osm_nominatim' => false
        );
        
        try {
            // Google Suggest testen
            $google_test = self::google_suggest('test', 'de');
            $status['google_suggest'] = !empty($google_test);
            
            // Wikipedia testen  
            $wikipedia_test = self::wikipedia_search('Schweiz', 'de');
            $status['wikipedia'] = !empty($wikipedia_test);
            
            // Wiktionary testen
            $wiktionary_test = self::wiktionary_search('Haus', 'de');
            $status['wiktionary'] = !empty($wiktionary_test);
            
            // OSM testen
            $osm_test = self::osm_swiss_places('Zürich', 1);
            $status['osm_nominatim'] = !empty($osm_test);
            
        } catch (Exception $e) {
            error_log('ReTexify API Test Error: ' . $e->getMessage());
        }
        
        return $status;
    }
    
    /**
     * Cache-Statistiken abrufen
     * 
     * @return array Cache-Statistiken
     */
    public static function get_cache_stats() {
        $cache_size = 0;
        foreach (self::$cache as $entry) {
            $cache_size += strlen(serialize($entry));
        }
        
        return array(
            'cache_entries' => count(self::$cache),
            'cache_size_bytes' => $cache_size,
            'cache_size_kb' => round($cache_size / 1024, 2),
            'last_request' => self::$last_request_time,
            'max_entries' => self::$max_cache_entries,
            'cache_duration' => self::$cache_duration
        );
    }
    
    /**
     * Cache komplett leeren
     */
    public static function clear_cache() {
        self::$cache = array();
        if (self::$debug_mode) {
            error_log('ReTexify API: Cache cleared');
        }
    }
    
    /**
     * Debug-Modus aktivieren/deaktivieren
     * 
     * @param bool $enabled Debug-Modus aktiviert
     */
    public static function set_debug_mode($enabled = true) {
        self::$debug_mode = (bool) $enabled;
    }
    
    /**
     * Rate-Limit anpassen
     * 
     * @param float $seconds Sekunden zwischen Requests
     */
    public static function set_rate_limit($seconds) {
        if ($seconds >= 0.1 && $seconds <= 10) {
            self::$rate_limit = (float) $seconds;
        }
    }
    
    /**
     * Timeout anpassen
     * 
     * @param int $seconds Timeout in Sekunden
     */
    public static function set_timeout($seconds) {
        if ($seconds >= 1 && $seconds <= 30) {
            self::$timeout = (int) $seconds;
        }
    }
    
    /**
     * Multi-API Keyword Research - kombiniert alle APIs
     * 
     * @param string $keyword Hauptkeyword
     * @param string $language Sprache
     * @return array Kombinierte Ergebnisse aller APIs
     */
    public static function multi_api_keyword_research($keyword, $language = 'de') {
        if (empty($keyword)) {
            return array();
        }
        
        $research_data = array(
            'google_suggestions' => array(),
            'wikipedia_related' => array(),
            'wiktionary_synonyms' => array(),
            'swiss_locations' => array(),
            'combined_keywords' => array()
        );
        
        try {
            // 1. Google Suggest
            $research_data['google_suggestions'] = self::google_suggest($keyword, $language);
            
            // 2. Wikipedia Related
            $research_data['wikipedia_related'] = self::wikipedia_search($keyword, $language);
            
            // 3. Wiktionary Synonyms
            $research_data['wiktionary_synonyms'] = self::wiktionary_search($keyword, $language);
            
            // 4. Swiss Locations (falls Ortsname erkannt)
            if (preg_match('/\b(schweiz|zürich|bern|basel|genf|luzern|st\.?\s?gallen|winterthur|lausanne|biel|thun|köniz|la chaux-de-fonds|fribourg|schaffhausen|vernier|chur|uster|sion|neuenburg|lancy|kriens|yverdon|steffisburg|oftringen|wohlen|renens|bulle|monthey|dietikon|riehen|carouge|weinfelden|aarau|rapperswil|davos|zermatt|interlaken|st\.\s?moritz|andermatt|saas-fee|grindelwald|wengen|mürren|verbier|crans-montana|villars|leysin|gstaad|engelberg)\b/i', $keyword)) {
                $research_data['swiss_locations'] = self::osm_swiss_places($keyword, 3);
            }
            
            // 5. Kombinierte Keywords erstellen
            $all_keywords = array_merge(
                $research_data['google_suggestions'],
                $research_data['wikipedia_related'],
                $research_data['wiktionary_synonyms']
            );
            
            // Duplikate entfernen und nach Relevanz sortieren
            $unique_keywords = array_unique($all_keywords);
            $research_data['combined_keywords'] = array_slice($unique_keywords, 0, 20);
            
        } catch (Exception $e) {
            error_log('ReTexify Multi-API Research Error: ' . $e->getMessage());
        }
        
        return $research_data;
    }
    
    /**
     * Test API-Verbindung (aus Hauptdatei verschoben)
     */
    public static function test_api($url) {
        if (empty($url)) {
            return false;
        }
        
        $response = self::make_api_call($url, array(), array(
            'timeout' => 5,
            'method' => 'HEAD' // Nur Header prüfen für schnellere Tests
        ));
        
        return $response !== false;
    }
    
    /**
     * Keyword-Suggestions abrufen (aus Hauptdatei verschoben)
     */
    public static function get_keyword_suggestions($keyword, $language = 'de') {
        if (empty($keyword) || strlen($keyword) < 2) {
            return array();
        }
        
        // Google Suggest verwenden
        $suggestions = self::google_suggest($keyword, $language);
        
        // Wikipedia verwandte Begriffe hinzufügen
        $wiki_terms = self::wikipedia_search($keyword, $language);
        
        // Kombinieren und Duplikate entfernen
        $all_suggestions = array_merge($suggestions, $wiki_terms);
        $unique_suggestions = array_unique($all_suggestions);
        
        // Nach Relevanz sortieren (kürzere Begriffe zuerst)
        usort($unique_suggestions, function($a, $b) {
            return strlen($a) - strlen($b);
        });
        
        return array_slice($unique_suggestions, 0, 15);
    }
    
    /**
     * Topic-Suggestions abrufen (aus Hauptdatei verschoben)
     */
    public static function get_topic_suggestions($topic) {
        if (empty($topic) || strlen($topic) < 2) {
            return array();
        }
        
        // Wikipedia verwandte Artikel
        $wiki_articles = self::wikipedia_search($topic, 'de');
        
        // Wiktionary verwandte Begriffe
        $wiki_terms = self::wiktionary_search($topic, 'de');
        
        // Schweizer Orte falls relevant
        $swiss_places = self::osm_swiss_places($topic, 3);
        
        // Kombinieren
        $all_topics = array_merge($wiki_articles, $wiki_terms, $swiss_places);
        $unique_topics = array_unique($all_topics);
        
        return array_slice($unique_topics, 0, 20);
    }
    
    /**
     * Schweizer Kantone abrufen (aus Hauptdatei verschoben)
     */
    public static function get_swiss_cantons_list() {
        return array(
            'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
            'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
            'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
            'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
            'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
            'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
            'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
        );
    }
}