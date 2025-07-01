<?php
/**
 * ReTexify Export/Import Manager - VOLLSTÄNDIG KORRIGIERTE VERSION
 * 
 * Behebt das Yoast/WPBakery Vermischungsproblem komplett
 * Version: 3.6.0 - Finale Korrektur für separate Content-Types
 * 
 * @package ReTexify_AI_Pro
 * @since 3.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('ReTexify_Export_Import_Manager')) {
class ReTexify_Export_Import_Manager {
    
    /**
     * Upload-Verzeichnis für temporäre Dateien
     */
    private $upload_dir;
    
    /**
     * Maximale Dateigröße für Uploads (in Bytes)
     */
    private $max_file_size;
    
    /**
     * Erlaubte Dateierweiterungen
     */
    private $allowed_extensions = array('csv');
    
    /**
     * KORRIGIERT: Verfügbare Content-Typen
     */
    private $available_content_types = array(
        'title' => 'Titel',
        'yoast_meta_title' => 'Yoast Meta-Titel',
        'yoast_meta_description' => 'Yoast Meta-Beschreibung',
        'yoast_focus_keyword' => 'Yoast Focus-Keyword',
        'wpbakery_meta_title' => 'WPBakery Meta-Titel',
        'wpbakery_meta_description' => 'WPBakery Meta-Beschreibung',
        'wpbakery_text' => 'WPBakery Text',
        'post_content' => 'Post-Inhalt',
        'alt_texts' => 'Alt-Texte',
        // Rückwärtskompatibilität
        'meta_title' => 'Meta-Titel (Alle)',
        'meta_description' => 'Meta-Beschreibung (Alle)',
        'focus_keyword' => 'Focus-Keyword (Alle)'
    );
    
    /**
     * Konstruktor
     */
    public function __construct() {
        $upload_dir_info = wp_upload_dir();
        $this->upload_dir = $upload_dir_info['basedir'] . '/retexify-imports/';
        $this->max_file_size = wp_max_upload_size();
        
        // Upload-Verzeichnis erstellen falls nicht vorhanden
        $this->ensure_upload_directory();
        
        // Alte Dateien bereinigen
        $this->cleanup_old_uploads();
    }
    
    /**
     * Upload-Verzeichnis sicher erstellen
     */
    private function ensure_upload_directory() {
        if (!file_exists($this->upload_dir)) {
            // Verzeichnis mit restriktiven Berechtigungen erstellen
            if (!wp_mkdir_p($this->upload_dir)) {
                throw new Exception('Upload-Verzeichnis konnte nicht erstellt werden');
            }
            
            // Berechtigungen setzen (nur für Owner lesbar/schreibbar)
            chmod($this->upload_dir, 0755);
            
            // .htaccess für Apache-Schutz
            $htaccess_content = "# ReTexify AI Security\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<Files ~ \"\\.(csv)$\">\n";
            $htaccess_content .= "    Order allow,deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            $htaccess_file = $this->upload_dir . '.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Index.php für zusätzlichen Schutz
            $index_content = "<?php\n// Silence is golden.\n";
            $index_file = $this->upload_dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, $index_content);
            }
        }
    }
    
    /**
     * Alte Upload-Dateien bereinigen (älter als 24 Stunden)
     */
    private function cleanup_old_uploads() {
        if (!is_dir($this->upload_dir)) {
            return;
        }
        
        $files = glob($this->upload_dir . '*.csv');
        $current_time = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $file_age = $current_time - filemtime($file);
                // Dateien älter als 24 Stunden löschen
                if ($file_age > 86400) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Export-Statistiken abrufen - KORRIGIERT
     * 
     * @return array Statistiken
     */
    public function get_export_stats() {
        global $wpdb;
        $stats = array();

        // Beiträge
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status IN ('publish', 'draft', 'private')") ?: 0;
        $total_pages = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status IN ('publish', 'draft', 'private')") ?: 0;
        $stats['posts'] = array('total' => (int)$total_posts);
        $stats['pages'] = array('total' => (int)$total_pages);

        // Titel (alle Posts und Seiten)
        $stats['title'] = (int)$total_posts + (int)$total_pages;

        // Yoast Meta-Titel
        $stats['yoast_meta_title'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_title' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;
        // Yoast Meta-Beschreibung
        $stats['yoast_meta_description'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;
        // Yoast Focus-Keyword
        $stats['yoast_focus_keyword'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_focuskw' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;

        // WPBakery Meta-Titel
        $stats['wpbakery_meta_title'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wpbakery_meta_title' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;
        // WPBakery Meta-Beschreibung
        $stats['wpbakery_meta_description'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wpbakery_meta_description' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;
        // WPBakery Focus-Keyword
        $stats['wpbakery_focus_keyword'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wpbakery_focus_keyword' AND meta_value != '' AND meta_value IS NOT NULL") ?: 0;

        // Alt-Texte (Bilder)
        $total_images = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'") ?: 0;
        $stats['images'] = array('total' => (int)$total_images);
        $images_with_alt = $wpdb->get_var("SELECT COUNT(p.ID) FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%' AND m.meta_key = '_wp_attachment_image_alt' AND m.meta_value != ''") ?: 0;
        $stats['images']['with_alt'] = (int)$images_with_alt;
        $stats['alt_texts'] = $total_images;

        return $stats;
    }
    
    /**
     * CSV-Export durchführen - KORRIGIERT
     * 
     * @param array $post_types Post-Typen
     * @param array $status_types Status-Typen  
     * @param array $content_types Content-Typen
     * @return array Export-Ergebnis
     */
    public function export_to_csv($post_types = array('post', 'page'), $status_types = array('publish'), $content_types = array()) {
        try {
            global $wpdb;
            
            // ✅ KORRIGIERT: Standard-Content-Types setzen falls leer
            if (empty($content_types)) {
                $content_types = array('title', 'post_content', 'yoast_meta_title', 'yoast_meta_description', 'yoast_focus_keyword');
            }
            
            // Parameter validieren
            if (empty($post_types) || empty($status_types)) {
                return array(
                    'success' => false,
                    'message' => 'Ungültige Parameter für Export'
                );
            }
            
            // SQL-Injection Schutz
            $post_types_sql = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
            $status_types_sql = "'" . implode("','", array_map('esc_sql', $status_types)) . "'";
            
            $posts = $wpdb->get_results("
                SELECT ID, post_title, post_type, post_status, post_date, post_modified, post_content
                FROM {$wpdb->posts} 
                WHERE post_type IN ({$post_types_sql}) 
                AND post_status IN ({$status_types_sql})
                ORDER BY post_modified DESC
                LIMIT 2000
            ");
            
            if (empty($posts)) {
                return array(
                    'success' => false,
                    'message' => 'Keine Posts/Pages für Export gefunden'
                );
            }
            
            // CSV-Datei erstellen
            $filename = 'retexify_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = $this->upload_dir . $filename;
            
            $file_handle = fopen($filepath, 'w');
            if (!$file_handle) {
                return array(
                    'success' => false,
                    'message' => 'CSV-Datei konnte nicht erstellt werden'
                );
            }
            
            // BOM für UTF-8 hinzufügen
            fwrite($file_handle, "\xEF\xBB\xBF");
            
            // CSV-Header schreiben
            $headers = $this->get_csv_headers($content_types);
            fputcsv($file_handle, $headers, ';');
            
            $row_count = 0;
            
            // Posts exportieren
            foreach ($posts as $post) {
                $row = $this->build_csv_row($post, $content_types, 'post');
                if ($row) {
                    fputcsv($file_handle, $row, ';');
                    $row_count++;
                }
            }
            
            // Bilder exportieren falls gewünscht
            if (in_array('alt_texts', $content_types)) {
                $media_items = $this->get_all_media_items();
                foreach ($media_items as $media) {
                    $row = $this->build_csv_row($media, array('alt_texts'), 'media');
                    if ($row) {
                        fputcsv($file_handle, $row, ';');
                        $row_count++;
                    }
                }
            }
            
            fclose($file_handle);
            
            $file_size = filesize($filepath);
            
            return array(
                'success' => true,
                'filename' => $filepath,
                'file_size' => $file_size,
                'row_count' => $row_count,
                'message' => "Export erfolgreich: {$row_count} Zeilen exportiert"
            );
            
        } catch (Exception $e) {
            error_log('ReTexify Export Error: ' . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'Export-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Alle Medien-Items abrufen (komplette Mediendatenbank)
     * 
     * @return array Medien-Items
     */
    private function get_all_media_items() {
        global $wpdb;
        
        $media_items = $wpdb->get_results("
            SELECT ID, post_title, post_type, post_status, post_date, post_modified, guid, post_content
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            ORDER BY post_date DESC
            LIMIT 5000
        ");
        
        return $media_items;
    }
    
    /**
     * CSV-Headers basierend auf ausgewählten Content-Typen erstellen - KORRIGIERT
     * 
     * @param array $content_types Ausgewählte Content-Typen
     * @return array Headers
     */
    private function get_csv_headers($content_types) {
        $headers = array('ID', 'Typ', 'Status');
        
        foreach ($content_types as $type) {
            switch ($type) {
                case 'title':
                    $headers[] = 'Titel'; // Nur Original, kein "Neu"
                    break;
                    
                // KORRIGIERT: Separate Yoast Content-Types
                case 'yoast_meta_title':
                    $headers[] = 'Yoast Meta-Titel (Original)';
                    $headers[] = 'Yoast Meta-Titel (Neu)';
                    break;
                case 'yoast_meta_description':
                    $headers[] = 'Yoast Meta-Beschreibung (Original)';
                    $headers[] = 'Yoast Meta-Beschreibung (Neu)';
                    break;
                case 'yoast_focus_keyword':
                    $headers[] = 'Yoast Focus-Keyword (Original)';
                    $headers[] = 'Yoast Focus-Keyword (Neu)';
                    break;
                    
                // KORRIGIERT: Separate WPBakery Content-Types  
                case 'wpbakery_meta_title':
                    $headers[] = 'WPBakery Meta-Titel (Original)';
                    $headers[] = 'WPBakery Meta-Titel (Neu)';
                    break;
                case 'wpbakery_meta_description':
                    $headers[] = 'WPBakery Meta-Beschreibung (Original)';
                    $headers[] = 'WPBakery Meta-Beschreibung (Neu)';
                    break;
                    
                // Generische Meta-Felder (für Rückwärtskompatibilität)
                case 'meta_title':
                    $headers[] = 'Meta-Titel (Original)';
                    $headers[] = 'Meta-Titel (Neu)';
                    break;
                case 'meta_description':
                    $headers[] = 'Meta-Beschreibung (Original)';
                    $headers[] = 'Meta-Beschreibung (Neu)';
                    break;
                case 'focus_keyword':
                    $headers[] = 'Focus-Keyword (Original)';
                    $headers[] = 'Focus-Keyword (Neu)';
                    break;
                    
                case 'post_content':
                    $headers[] = 'Post-Inhalt';
                    break;
                case 'wpbakery_text':
                    $headers[] = 'WPBakery Text (Original)';
                    $headers[] = 'WPBakery Text (Neu)';
                    break;
                case 'alt_texts':
                    $headers[] = 'Dateiname';
                    $headers[] = 'Alt-Text (Original)';
                    $headers[] = 'Alt-Text (Neu)';
                    break;
            }
        }
        
        $headers[] = 'URL';
        $headers[] = 'Erstellt';
        $headers[] = 'Geändert';
        
        return $headers;
    }
    
    /**
     * CSV-Zeile für einen Post/Media erstellen - KORRIGIERT
     * 
     * @param object $item WordPress Post oder Media
     * @param array $content_types Content-Typen
     * @param string $item_type 'post' oder 'media'
     * @return array CSV-Zeile
     */
    private function build_csv_row($item, $content_types, $item_type = 'post') {
        $row = array($item->ID, $item->post_type, $item->post_status);
        
        foreach ($content_types as $type) {
            switch ($type) {
                case 'title':
                    if ($item_type === 'media') {
                        $row[] = basename($item->guid); // Dateiname für Medien
                    } else {
                        $row[] = get_the_title($item->ID);
                    }
                    break;
                    
                // KORRIGIERT: Spezifische Yoast-Funktionen
                case 'yoast_meta_title':
                    if ($item_type === 'post') {
                        $row[] = $this->get_yoast_meta_title($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Meta-Titel
                        $row[] = '';
                    }
                    break;
                    
                case 'yoast_meta_description':
                    if ($item_type === 'post') {
                        $row[] = $this->get_yoast_meta_description($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Meta-Beschreibung
                        $row[] = '';
                    }
                    break;
                    
                case 'yoast_focus_keyword':
                    if ($item_type === 'post') {
                        $row[] = $this->get_yoast_focus_keyword($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Focus Keywords
                        $row[] = '';
                    }
                    break;
                    
                // KORRIGIERT: Spezifische WPBakery-Funktionen
                case 'wpbakery_meta_title':
                    if ($item_type === 'post') {
                        $row[] = $this->get_wpbakery_meta_title($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine WPBakery Meta
                        $row[] = '';
                    }
                    break;
                    
                case 'wpbakery_meta_description':
                    if ($item_type === 'post') {
                        $row[] = $this->get_wpbakery_meta_description($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine WPBakery Meta
                        $row[] = '';
                    }
                    break;
                    
                // Generische Meta-Felder (alle SEO-Plugins)
                case 'meta_title':
                    if ($item_type === 'post') {
                        $row[] = $this->get_meta_title($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Meta-Titel
                        $row[] = '';
                    }
                    break;
                    
                case 'meta_description':
                    if ($item_type === 'post') {
                        $row[] = $this->get_meta_description($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Meta-Beschreibung
                        $row[] = '';
                    }
                    break;
                    
                case 'focus_keyword':
                    if ($item_type === 'post') {
                        $row[] = $this->get_focus_keyword($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Focus Keywords
                        $row[] = '';
                    }
                    break;
                    
                case 'post_content':
                    if ($item_type === 'post') {
                        $row[] = wp_strip_all_tags($item->post_content);
                    } else {
                        $row[] = ''; // Medien haben keinen Post-Content
                    }
                    break;
                    
                case 'wpbakery_text':
                    if ($item_type === 'post') {
                        $row[] = $this->extract_wpbakery_text($item->post_content);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine WPBakery-Texte
                        $row[] = '';
                    }
                    break;
                    
                case 'alt_texts':
                    if ($item_type === 'media') {
                        $row[] = basename($item->guid); // Dateiname
                        $row[] = get_post_meta($item->ID, '_wp_attachment_image_alt', true); // Original Alt-Text
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        // Für Posts: leer lassen oder überspringen
                        $row[] = '';
                        $row[] = '';
                        $row[] = '';
                    }
                    break;
            }
        }
        
        // URL und Daten
        if ($item_type === 'media') {
            $row[] = $item->guid; // Medien-URL
        } else {
            $row[] = get_permalink($item->ID);
        }
        $row[] = $item->post_date;
        $row[] = $item->post_modified;
        
        return $row;
    }
    
    /**
     * NEUE FUNKTIONEN: Spezifische Yoast-Meta-Daten abrufen
     */
    private function get_yoast_meta_title($post_id) {
        return get_post_meta($post_id, '_yoast_wpseo_title', true);
    }
    
    private function get_yoast_meta_description($post_id) {
        return get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    }
    
    private function get_yoast_focus_keyword($post_id) {
        return get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
    }
    
    /**
     * NEUE FUNKTIONEN: Spezifische WPBakery-Meta-Daten abrufen
     */
    private function get_wpbakery_meta_title($post_id) {
        return get_post_meta($post_id, '_wpbakery_meta_title', true);
    }
    
    private function get_wpbakery_meta_description($post_id) {
        return get_post_meta($post_id, '_wpbakery_meta_description', true);
    }
    
    /**
     * Meta-Titel abrufen (alle SEO-Plugins)
     */
    private function get_meta_title($post_id) {
        // Yoast SEO
        $title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        if (!empty($title)) return $title;
        
        // Rank Math
        $title = get_post_meta($post_id, 'rank_math_title', true);
        if (!empty($title)) return $title;
        
        // All in One SEO
        $title = get_post_meta($post_id, '_aioseop_title', true);
        if (!empty($title)) return $title;
        
        // SEOPress
        $title = get_post_meta($post_id, '_seopress_titles_title', true);
        if (!empty($title)) return $title;
        
        return '';
    }
    
    /**
     * Meta-Beschreibung abrufen (alle SEO-Plugins)
     */
    private function get_meta_description($post_id) {
        // Yoast SEO
        $desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        if (!empty($desc)) return $desc;
        
        // Rank Math
        $desc = get_post_meta($post_id, 'rank_math_description', true);
        if (!empty($desc)) return $desc;
        
        // All in One SEO
        $desc = get_post_meta($post_id, '_aioseop_description', true);
        if (!empty($desc)) return $desc;
        
        // SEOPress
        $desc = get_post_meta($post_id, '_seopress_titles_desc', true);
        if (!empty($desc)) return $desc;
        
        return '';
    }
    
    /**
     * Focus-Keyword abrufen (alle SEO-Plugins)
     */
    private function get_focus_keyword($post_id) {
        // Yoast SEO
        $keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($keyword)) return $keyword;
        
        // Rank Math
        $keyword = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($keyword)) return $keyword;
        
        return '';
    }
    
    /**
     * WPBakery Page Builder Text extrahieren
     */
    private function extract_wpbakery_text($content) {
        // WPBakery Shortcodes nach Texten durchsuchen
        $text_content = '';
        
        // [vc_column_text] Shortcode
        if (preg_match_all('/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $text_content .= wp_strip_all_tags($match) . ' ';
            }
        }
        
        // [vc_text_separator] Shortcode
        if (preg_match_all('/\[vc_text_separator[^\]]*title="([^"]*)"[^\]]*\]/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $text_content .= $match . ' ';
            }
        }
        
        // [vc_custom_heading] Shortcode
        if (preg_match_all('/\[vc_custom_heading[^\]]*text="([^"]*)"[^\]]*\]/s', $content, $matches)) {
            foreach ($matches[1] as $match) {
                $text_content .= $match . ' ';
            }
        }
        
        return trim($text_content);
    }
    
    /**
     * Sicheren Download-URL erstellen
     * 
     * @param string $filename Dateiname
     * @return string Download-URL
     */
    private function create_secure_download_url($filename) {
        $nonce = wp_create_nonce('retexify_download_nonce');
        return admin_url('admin-ajax.php?action=retexify_download_export_file&filename=' . urlencode($filename) . '&nonce=' . $nonce);
    }
    
    /**
     * CSV-Upload verarbeiten mit verbesserter Sicherheit
     * 
     * @param array $file $_FILES Array-Element
     * @return array Upload-Ergebnis
     */
    public function handle_csv_upload($file) {
        try {
            // Datei-Validierung
            $validation = $this->validate_upload($file);
            if (!$validation['valid']) {
                return array(
                    'success' => false,
                    'message' => $validation['message']
                );
            }
            
            // Eindeutigen Dateinamen generieren
            $filename = 'import_' . uniqid() . '_' . sanitize_file_name($file['name']);
            $filepath = $this->upload_dir . $filename;
            
            // Datei verschieben
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return array(
                    'success' => false,
                    'message' => 'Datei konnte nicht gespeichert werden'
                );
            }
            
            // Dateiberechtigungen setzen
            chmod($filepath, 0644);
            
            // CSV-Vorschau erstellen
            $preview = $this->create_csv_preview($filepath);
            
            return array(
                'success' => true,
                'message' => 'CSV-Datei erfolgreich hochgeladen',
                'filename' => $filename,
                'filepath' => $filepath,
                'file_size' => filesize($filepath),
                'preview' => $preview
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Upload-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Upload-Datei validieren mit erweiterten Sicherheitsprüfungen
     * 
     * @param array $file $_FILES Array-Element
     * @return array Validierungs-Ergebnis
     */
    private function validate_upload($file) {
        // Basis-Validierung
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return array('valid' => false, 'message' => 'Keine Datei hochgeladen');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array('valid' => false, 'message' => 'Upload-Fehler: ' . $file['error']);
        }
        
        // Dateigröße prüfen
        if ($file['size'] > $this->max_file_size) {
            return array('valid' => false, 'message' => 'Datei zu groß. Maximum: ' . size_format($this->max_file_size));
        }
        
        // Dateiname validieren
        $filename = sanitize_file_name($file['name']);
        if (empty($filename)) {
            return array('valid' => false, 'message' => 'Ungültiger Dateiname');
        }
        
        // Dateierweiterung prüfen
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowed_extensions)) {
            return array('valid' => false, 'message' => 'Nur CSV-Dateien erlaubt');
        }
        
        // MIME-Type prüfen (zusätzliche Sicherheit)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mime_types = array('text/csv', 'text/plain', 'application/csv');
        if (!in_array($mime_type, $allowed_mime_types)) {
            return array('valid' => false, 'message' => 'Ungültiger Dateityp: ' . $mime_type);
        }
        
        return array('valid' => true);
    }
    
    /**
     * CSV-Vorschau erstellen
     * 
     * @param string $filepath Dateipfad
     * @return array Vorschau-Daten
     */
    private function create_csv_preview($filepath) {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new Exception('Datei konnte nicht gelesen werden');
        }
        
        // BOM überspringen
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // Delimiter erkennen
        $first_line = fgets($handle);
        $delimiter = $this->detect_csv_delimiter($first_line);
        
        // Datei zurückspulen
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3);
        }
        
        // Headers lesen
        $headers = fgetcsv($handle, 0, $delimiter);
        
        // Erste 5 Datenzeilen lesen
        $rows = array();
        $row_count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $row_count < 5) {
            $rows[] = $row;
            $row_count++;
        }
        
        // Gesamtanzahl Zeilen zählen
        $total_rows = 1; // Header
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $total_rows++;
        }
        
        fclose($handle);
        
        return array(
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => $total_rows,
            'detected_delimiter' => $delimiter
        );
    }
    
    /**
     * CSV-Delimiter automatisch erkennen
     * 
     * @param string $line Erste Zeile der CSV
     * @return string Erkannter Delimiter
     */
    private function detect_csv_delimiter($line) {
        $delimiters = array(',', ';', "\t", '|');
        $delimiter_counts = array();
        
        foreach ($delimiters as $delimiter) {
            $delimiter_counts[$delimiter] = substr_count($line, $delimiter);
        }
        
        return array_search(max($delimiter_counts), $delimiter_counts);
    }
    
    /**
     * Import-Vorschau abrufen
     * 
     * @param string $filename Dateiname
     * @return array Vorschau-Ergebnis
     */
    public function get_import_preview($filename) {
        try {
            $filepath = $this->upload_dir . $filename;
            
            if (!file_exists($filepath)) {
                return array(
                    'success' => false,
                    'message' => 'Datei nicht gefunden'
                );
            }
            
            $preview = $this->create_csv_preview($filepath);
            
            // Spalten-Mapping-Optionen (nur für "Neu" Spalten)
            $mapping_options = array(
                'id' => 'ID',
                'meta_title_new' => 'Meta-Titel (Neu)',
                'meta_description_new' => 'Meta-Beschreibung (Neu)',
                'focus_keyword_new' => 'Focus-Keyword (Neu)',
                'wpbakery_text_new' => 'WPBakery Text (Neu)',
                'alt_text_new' => 'Alt-Text (Neu)',
                'ignore' => '--- Ignorieren ---'
            );
            
            return array(
                'success' => true,
                'preview' => $preview,
                'mapping_options' => $mapping_options,
                'file_info' => array(
                    'name' => $filename,
                    'size' => filesize($filepath),
                    'modified' => date('d.m.Y H:i:s', filemtime($filepath))
                )
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Vorschau-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * CSV-Daten importieren (nur "Neu" Spalten)
     * 
     * @param string $filename Dateiname
     * @param array $column_mapping Spalten-Zuordnung
     * @return array Import-Ergebnis
     */
    public function import_csv_data($filename, $column_mapping = array()) {
        try {
            $filepath = $this->upload_dir . $filename;
            
            if (!file_exists($filepath)) {
                return array(
                    'success' => false,
                    'message' => 'Import-Datei nicht gefunden'
                );
            }
            
            $handle = fopen($filepath, 'r');
            if (!$handle) {
                return array(
                    'success' => false,
                    'message' => 'Datei konnte nicht geöffnet werden'
                );
            }
            
            // BOM überspringen
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            $delimiter = $this->detect_csv_delimiter(fgets($handle));
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }
            
            // Headers überspringen
            $headers = fgetcsv($handle, 0, $delimiter);
            
            $imported = 0;
            $updated = 0;
            $errors = array();
            $processed = 0;
            
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $processed++;
                $result = $this->import_single_row($row, $headers, $column_mapping);
                
                if ($result['success']) {
                    if ($result['action'] === 'updated') {
                        $updated++;
                    } else {
                        $imported++;
                    }
                } else {
                    $errors[] = "Zeile {$processed}: " . $result['message'];
                }
                
                // Timeout vermeiden
                if ($processed % 50 === 0) {
                    @set_time_limit(30);
                }
                
                // Maximale Anzahl Fehler begrenzen
                if (count($errors) > 100) {
                    $errors[] = "... weitere Fehler unterdrückt (über 100 Fehler)";
                    break;
                }
            }
            
            fclose($handle);
            
            // Temporäre Datei löschen
            $this->delete_uploaded_file($filename);
            
            return array(
                'success' => true,
                'message' => 'Import erfolgreich abgeschlossen',
                'imported' => $imported,
                'updated' => $updated,
                'total_processed' => $processed,
                'errors' => array_slice($errors, 0, 20), // Nur erste 20 Fehler zeigen
                'total_errors' => count($errors)
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Import-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Einzelne CSV-Zeile importieren (nur "Neu" Spalten)
     * 
     * @param array $row CSV-Zeile
     * @param array $headers CSV-Headers
     * @param array $column_mapping Spalten-Zuordnung
     * @return array Import-Ergebnis für diese Zeile
     */
    private function import_single_row($row, $headers, $column_mapping) {
        try {
            // ID finden
            $id = null;
            $id_column = array_search('id', $column_mapping);
            if ($id_column !== false && isset($row[$id_column])) {
                $id = intval($row[$id_column]);
            }
            if (!$id) {
                return array(
                    'success' => false,
                    'message' => 'ID nicht gefunden oder ungültig: ' . ($row[0] ?? 'N/A')
                );
            }
            $post = get_post($id);
            if (!$post) {
                return array(
                    'success' => false,
                    'message' => "Post mit ID {$id} nicht gefunden"
                );
            }
            $updated_fields = 0;
            // Nur "Neu"-Spalten übernehmen
            foreach ($column_mapping as $column_index => $field_type) {
                if (!isset($row[$column_index]) || $field_type === 'ignore' || strpos($field_type, '_new') === false) {
                    continue;
                }
                $value = trim($row[$column_index]);
                if (empty($value)) {
                    continue;
                }
                switch ($field_type) {
                    case 'meta_title_new':
                        update_post_meta($id, '_yoast_wpseo_title', $value);
                        $updated_fields++;
                        break;
                    case 'meta_description_new':
                        update_post_meta($id, '_yoast_wpseo_metadesc', $value);
                        $updated_fields++;
                        break;
                    case 'focus_keyword_new':
                        update_post_meta($id, '_yoast_wpseo_focuskw', $value);
                        $updated_fields++;
                        break;
                    case 'wpbakery_text_new':
                        update_post_meta($id, '_wpbakery_custom_text', $value);
                        $updated_fields++;
                        break;
                    case 'alt_text_new':
                        if ($post->post_type === 'attachment') {
                            update_post_meta($id, '_wp_attachment_image_alt', $value);
                            $updated_fields++;
                        }
                        break;
                }
            }
            if ($updated_fields > 0) {
                return array(
                    'success' => true,
                    'action' => 'updated',
                    'updated_fields' => $updated_fields
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Keine Felder aktualisiert für ID: {$id}"
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Zeilen-Import-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Hochgeladene Datei löschen
     * 
     * @param string $filename Dateiname
     * @return bool Erfolgreich gelöscht
     */
/**
 * Hochgeladene Datei löschen - KORRIGIERTE VERSION
 * 
 * @param string $filename Dateiname
 * @return array Ergebnis mit success und message
 */
public function delete_uploaded_file($filename) {
    try {
        // Sicherheitsprüfungen
        if (empty($filename)) {
            return array(
                'success' => false,
                'message' => 'Kein Dateiname angegeben'
            );
        }
        
        // Dateiname bereinigen
        $filename = sanitize_file_name($filename);
        
        // Vollständigen Dateipfad erstellen
        $filepath = $this->upload_dir . $filename;
        
        // Prüfen ob Datei existiert
        if (!file_exists($filepath)) {
            return array(
                'success' => false,
                'message' => 'Datei nicht gefunden: ' . $filename
            );
        }
        
        // Sicherheitsprüfung: Datei muss im Upload-Verzeichnis sein
        $real_filepath = realpath($filepath);
        $real_upload_dir = realpath($this->upload_dir);
        
        if ($real_filepath === false || $real_upload_dir === false) {
            return array(
                'success' => false,
                'message' => 'Ungültiger Dateipfad'
            );
        }
        
        if (strpos($real_filepath, $real_upload_dir) !== 0) {
            return array(
                'success' => false,
                'message' => 'Sicherheitsfehler: Datei außerhalb des erlaubten Verzeichnisses'
            );
        }
        
        // Datei löschen
        if (unlink($filepath)) {
            // Erfolgreich gelöscht
            return array(
                'success' => true,
                'message' => 'Datei erfolgreich entfernt: ' . $filename
            );
        } else {
            // Löschen fehlgeschlagen
            return array(
                'success' => false,
                'message' => 'Datei konnte nicht gelöscht werden: ' . $filename
            );
        }
        
    } catch (Exception $e) {
        // Fehler abfangen
        return array(
            'success' => false,
            'message' => 'Löschfehler: ' . $e->getMessage()
        );
    }
}
    
    /**
     * Verfügbare Content-Typen abrufen
     * 
     * @return array Content-Typen
     */
    public function get_available_content_types() {
        return $this->available_content_types;
    }
}
}