<?php
/**
 * ReTexify Performance Optimizer
 * 
 * Optimiert Performance durch Caching, Datenbankoptimierung und API-Performance
 * 
 * @package ReTexify_AI
 * @since 4.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Performance_Optimizer {
    
    /**
     * Cache für häufig abgerufene Daten
     */
    private static $cache = array();
    
    /**
     * Cache-Dauer in Sekunden
     */
    private static $cache_duration = 1800; // 30 Minuten
    
    /**
     * Maximale Cache-Einträge
     */
    private static $max_cache_entries = 100;
    
    /**
     * Batch-Größe für Massenoperationen
     */
    private static $batch_size = 50;
    
    /**
     * Performance-Metriken
     */
    private static $metrics = array(
        'cache_hits' => 0,
        'cache_misses' => 0,
        'db_queries_saved' => 0,
        'api_calls_saved' => 0
    );
    
    /**
     * Initialisierung
     */
    public static function init() {
        // Cache-Bereinigung alle 24 Stunden
        if (!wp_next_scheduled('retexify_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'retexify_cache_cleanup');
        }
        
        add_action('retexify_cache_cleanup', array(__CLASS__, 'cleanup_cache'));
        
        // Performance-Metriken beim Shutdown speichern
        add_action('shutdown', array(__CLASS__, 'save_metrics'));
    }
    
    /**
     * Optimierte Option abrufen mit Caching
     * 
     * @param string $option_name Option-Name
     * @param mixed $default Standardwert
     * @return mixed Option-Wert
     */
    public static function get_option_cached($option_name, $default = false) {
        $cache_key = 'option_' . $option_name;
        
        // Cache prüfen
        $cached_value = self::get_cache($cache_key);
        if ($cached_value !== false) {
            self::$metrics['cache_hits']++;
            return $cached_value;
        }
        
        // Datenbank abfragen
        $value = get_option($option_name, $default);
        
        // Cache setzen
        self::set_cache($cache_key, $value);
        self::$metrics['cache_misses']++;
        self::$metrics['db_queries_saved']++;
        
        return $value;
    }
    
    /**
     * Optimierte Option speichern mit Cache-Invalidierung
     * 
     * @param string $option_name Option-Name
     * @param mixed $value Wert
     * @return bool Erfolg
     */
    public static function update_option_cached($option_name, $value) {
        $result = update_option($option_name, $value);
        
        if ($result) {
            // Cache invalidieren
            $cache_key = 'option_' . $option_name;
            self::delete_cache($cache_key);
        }
        
        return $result;
    }
    
    /**
     * Optimierte Post-Meta abrufen mit Caching
     * 
     * @param int $post_id Post-ID
     * @param string $meta_key Meta-Key
     * @param bool $single Einzelner Wert
     * @return mixed Meta-Wert
     */
    public static function get_post_meta_cached($post_id, $meta_key, $single = true) {
        $cache_key = "post_meta_{$post_id}_{$meta_key}";
        
        // Cache prüfen
        $cached_value = self::get_cache($cache_key);
        if ($cached_value !== false) {
            self::$metrics['cache_hits']++;
            return $cached_value;
        }
        
        // Datenbank abfragen
        $value = get_post_meta($post_id, $meta_key, $single);
        
        // Cache setzen
        self::set_cache($cache_key, $value);
        self::$metrics['cache_misses']++;
        self::$metrics['db_queries_saved']++;
        
        return $value;
    }
    
    /**
     * Optimierte Post-Meta speichern mit Cache-Invalidierung
     * 
     * @param int $post_id Post-ID
     * @param string $meta_key Meta-Key
     * @param mixed $meta_value Meta-Wert
     * @return int|bool Meta-ID oder false
     */
    public static function update_post_meta_cached($post_id, $meta_key, $meta_value) {
        $result = update_post_meta($post_id, $meta_key, $meta_value);
        
        if ($result) {
            // Cache invalidieren
            $cache_key = "post_meta_{$post_id}_{$meta_key}";
            self::delete_cache($cache_key);
        }
        
        return $result;
    }
    
    /**
     * Batch-Export mit optimierter Performance
     * 
     * @param array $post_types Post-Typen
     * @param array $status_types Status-Typen
     * @param array $content_types Content-Typen
     * @return array Export-Ergebnis
     */
    public static function batch_export_optimized($post_types = array('post', 'page'), $status_types = array('publish'), $content_types = array()) {
        $start_time = microtime(true);
        
        // Posts in Batches abrufen
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => $status_types,
            'numberposts' => -1,
            'fields' => 'ids' // Nur IDs für bessere Performance
        ));
        
        $total_posts = count($posts);
        $batches = array_chunk($posts, self::$batch_size);
        
        $export_data = array();
        $processed = 0;
        
        foreach ($batches as $batch) {
            // Batch verarbeiten
            $batch_data = self::process_export_batch($batch, $content_types);
            $export_data = array_merge($export_data, $batch_data);
            
            $processed += count($batch);
            
            // Memory-Limit prüfen
            if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB
                break;
            }
        }
        
        $end_time = microtime(true);
        $execution_time = round($end_time - $start_time, 2);
        
        return array(
            'success' => true,
            'data' => $export_data,
            'total_posts' => $total_posts,
            'processed' => $processed,
            'execution_time' => $execution_time,
            'memory_usage' => memory_get_usage(true)
        );
    }
    
    /**
     * Export-Batch verarbeiten
     * 
     * @param array $post_ids Post-IDs
     * @param array $content_types Content-Typen
     * @return array Batch-Daten
     */
    private static function process_export_batch($post_ids, $content_types) {
        $batch_data = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $row = array();
            
            // Basis-Daten
            $row[] = $post->ID;
            $row[] = $post->post_title;
            $row[] = $post->post_type;
            $row[] = $post->post_status;
            
            // Content-Typen verarbeiten
            foreach ($content_types as $content_type) {
                switch ($content_type) {
                    case 'title':
                        $row[] = $post->post_title;
                        break;
                    case 'post_content':
                        $row[] = wp_strip_all_tags($post->post_content);
                        break;
                    case 'yoast_meta_title':
                        $row[] = self::get_post_meta_cached($post_id, '_yoast_wpseo_title');
                        break;
                    case 'yoast_meta_description':
                        $row[] = self::get_post_meta_cached($post_id, '_yoast_wpseo_metadesc');
                        break;
                    case 'yoast_focus_keyword':
                        $row[] = self::get_post_meta_cached($post_id, '_yoast_wpseo_focuskw');
                        break;
                    default:
                        $row[] = '';
                }
            }
            
            $batch_data[] = $row;
        }
        
        return $batch_data;
    }
    
    /**
     * API-Cache für Keyword-Research
     * 
     * @param string $keyword Keyword
     * @param string $language Sprache
     * @return array|false Gecachte Ergebnisse oder false
     */
    public static function get_keyword_research_cached($keyword, $language = 'de') {
        $cache_key = "keyword_research_{$keyword}_{$language}";
        
        // Cache prüfen
        $cached_result = self::get_cache($cache_key);
        if ($cached_result !== false) {
            self::$metrics['cache_hits']++;
            self::$metrics['api_calls_saved']++;
            return $cached_result;
        }
        
        return false; // Kein Cache, API-Call nötig
    }
    
    /**
     * Keyword-Research-Ergebnisse cachen
     * 
     * @param string $keyword Keyword
     * @param string $language Sprache
     * @param array $results Ergebnisse
     */
    public static function cache_keyword_research($keyword, $language, $results) {
        $cache_key = "keyword_research_{$keyword}_{$language}";
        self::set_cache($cache_key, $results, 7200); // 2 Stunden Cache
    }
    
    /**
     * Cache abrufen
     * 
     * @param string $key Cache-Key
     * @return mixed|false Gecachte Daten oder false
     */
    private static function get_cache($key) {
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
     * Cache setzen
     * 
     * @param string $key Cache-Key
     * @param mixed $data Zu cachende Daten
     * @param int $duration Cache-Dauer (optional)
     */
    private static function set_cache($key, $data, $duration = null) {
        if ($duration === null) {
            $duration = self::$cache_duration;
        }
        
        self::$cache[$key] = array(
            'data' => $data,
            'timestamp' => time(),
            'duration' => $duration
        );
        
        // Cache-Größe begrenzen
        if (count(self::$cache) > self::$max_cache_entries) {
            // Älteste Einträge entfernen
            $oldest_key = array_keys(self::$cache)[0];
            unset(self::$cache[$oldest_key]);
        }
    }
    
    /**
     * Cache löschen
     * 
     * @param string $key Cache-Key
     */
    private static function delete_cache($key) {
        if (isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
        }
    }
    
    /**
     * Cache bereinigen
     */
    public static function cleanup_cache() {
        $current_time = time();
        $cleaned = 0;
        
        foreach (self::$cache as $key => $data) {
            if ($data['timestamp'] + $data['duration'] < $current_time) {
                unset(self::$cache[$key]);
                $cleaned++;
            }
        }
        
        error_log("ReTexify Performance: Cache bereinigt - {$cleaned} Einträge entfernt");
    }
    
    /**
     * Performance-Metriken abrufen
     * 
     * @return array Metriken
     */
    public static function get_metrics() {
        $cache_size = 0;
        foreach (self::$cache as $entry) {
            $cache_size += strlen(serialize($entry));
        }
        
        return array(
            'cache_hits' => self::$metrics['cache_hits'],
            'cache_misses' => self::$metrics['cache_misses'],
            'cache_hit_ratio' => self::$metrics['cache_hits'] > 0 ? 
                round((self::$metrics['cache_hits'] / (self::$metrics['cache_hits'] + self::$metrics['cache_misses'])) * 100, 2) : 0,
            'db_queries_saved' => self::$metrics['db_queries_saved'],
            'api_calls_saved' => self::$metrics['api_calls_saved'],
            'cache_entries' => count(self::$cache),
            'cache_size_kb' => round($cache_size / 1024, 2),
            'max_cache_entries' => self::$max_cache_entries,
            'cache_duration' => self::$cache_duration
        );
    }
    
    /**
     * Metriken speichern
     */
    public static function save_metrics() {
        $metrics = self::get_metrics();
        update_option('retexify_performance_metrics', $metrics);
    }
    
    /**
     * Performance-Optimierungen aktivieren
     */
    public static function enable_optimizations() {
        // Transients für bessere Performance
        if (!get_transient('retexify_performance_enabled')) {
            set_transient('retexify_performance_enabled', true, DAY_IN_SECONDS);
        }
        
        // Object Cache prüfen
        if (wp_using_ext_object_cache()) {
            error_log('ReTexify Performance: Object Cache verfügbar - Performance optimiert');
        }
        
        // Memory-Limit erhöhen falls nötig
        $current_limit = ini_get('memory_limit');
        $current_limit_bytes = wp_convert_hr_to_bytes($current_limit);
        
        if ($current_limit_bytes < 256 * 1024 * 1024) { // Weniger als 256MB
            @ini_set('memory_limit', '256M');
        }
    }
}

// Performance-Optimierungen initialisieren
ReTexify_Performance_Optimizer::init(); 