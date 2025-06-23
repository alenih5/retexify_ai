<?php
/**
 * ReTexify Export/Import Manager
 * 
 * Verwaltet CSV-Export und -Import für SEO-Daten
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.7
 */

if (!defined('ABSPATH')) {
    exit;
}

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
     * Konstruktor
     */
    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/retexify-imports/';
        $this->max_file_size = wp_max_upload_size();
        
        // Upload-Verzeichnis erstellen falls nicht vorhanden
        $this->ensure_upload_directory();
    }
    
    /**
     * Upload-Verzeichnis erstellen
     */
    private function ensure_upload_directory() {
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // .htaccess für Sicherheit
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($this->upload_dir . '.htaccess', $htaccess_content);
            
            // index.php für Sicherheit
            file_put_contents($this->upload_dir . 'index.php', '<?php // Silence is golden');
        }
    }
    
    /**
     * CSV-Export durchführen
     * 
     * @param array $post_types Post-Typen zum Exportieren
     * @param array $status_types Status-Typen
     * @param array $content_types Content-Typen
     * @return array Export-Ergebnis
     */
    public function export_to_csv($post_types = array('post', 'page'), $status_types = array('publish'), $content_types = array()) {
        try {
            global $wpdb;
            
            // Standard Content-Typen falls leer
            if (empty($content_types)) {
                $content_types = array('title', 'meta_title', 'meta_description', 'focus_keyword');
            }
            
            // Posts abrufen
            $post_types_sql = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
            $status_types_sql = "'" . implode("','", array_map('esc_sql', $status_types)) . "'";
            
            $posts = $wpdb->get_results("
                SELECT ID, post_title, post_content, post_type, post_status, post_date, post_modified
                FROM {$wpdb->posts} 
                WHERE post_type IN ({$post_types_sql}) 
                AND post_status IN ({$status_types_sql})
                ORDER BY post_modified DESC
                LIMIT 1000
            ");
            
            if (empty($posts)) {
                return array(
                    'success' => false,
                    'message' => 'Keine Posts zum Exportieren gefunden'
                );
            }
            
            // CSV-Daten sammeln
            $csv_data = array();
            $headers = $this->get_csv_headers($content_types);
            $csv_data[] = $headers;
            
            foreach ($posts as $post) {
                $row = $this->build_csv_row($post, $content_types);
                $csv_data[] = $row;
            }
            
            // CSV-Datei erstellen
            $filename = 'retexify_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = $this->upload_dir . $filename;
            
            $fp = fopen($filepath, 'w');
            if (!$fp) {
                return array(
                    'success' => false,
                    'message' => 'CSV-Datei konnte nicht erstellt werden'
                );
            }
            
            // UTF-8 BOM für Excel-Kompatibilität
            fwrite($fp, "\xEF\xBB\xBF");
            
            foreach ($csv_data as $row) {
                fputcsv($fp, $row, ';'); // Semikolon für deutsche Excel-Version
            }
            
            fclose($fp);
            
            // Download-URL erstellen
            $upload_url = wp_upload_dir()['baseurl'] . '/retexify-imports/';
            $download_url = $upload_url . $filename;
            
            return array(
                'success' => true,
                'message' => 'CSV-Export erfolgreich erstellt',
                'filename' => $filename,
                'filepath' => $filepath,
                'download_url' => $download_url,
                'file_size' => filesize($filepath),
                'row_count' => count($csv_data) - 1, // Ohne Header
                'columns' => $headers
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Export-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * CSV-Headers basierend auf Content-Typen erstellen
     * 
     * @param array $content_types Content-Typen
     * @return array Headers
     */
    private function get_csv_headers($content_types) {
        $headers = array('ID', 'Post-Typ', 'Status');
        
        $header_mapping = array(
            'title' => 'Titel',
            'content' => 'Content',
            'meta_title' => 'Meta-Titel',
            'meta_description' => 'Meta-Beschreibung',
            'focus_keyword' => 'Focus-Keyword',
            'wpbakery_text' => 'WPBakery Text',
            'wpbakery_meta_title' => 'WPBakery Meta-Titel',
            'wpbakery_meta_content' => 'WPBakery Meta-Content',
            'alt_text' => 'Alt-Texte'
        );
        
        foreach ($content_types as $type) {
            if (isset($header_mapping[$type])) {
                $headers[] = $header_mapping[$type];
            }
        }
        
        $headers[] = 'URL';
        $headers[] = 'Bearbeiten-URL';
        $headers[] = 'Erstellt';
        $headers[] = 'Geändert';
        
        return $headers;
    }
    
    /**
     * CSV-Zeile für einen Post erstellen
     * 
     * @param object $post WordPress Post
     * @param array $content_types Content-Typen
     * @return array CSV-Zeile
     */
    private function build_csv_row($post, $content_types) {
        $row = array(
            $post->ID,
            $post->post_type,
            $post->post_status
        );
        
        foreach ($content_types as $type) {
            switch ($type) {
                case 'title':
                    $row[] = $post->post_title;
                    break;
                    
                case 'content':
                    $row[] = wp_trim_words(wp_strip_all_tags($post->post_content), 50);
                    break;
                    
                case 'meta_title':
                    $row[] = $this->get_meta_title($post->ID);
                    break;
                    
                case 'meta_description':
                    $row[] = $this->get_meta_description($post->ID);
                    break;
                    
                case 'focus_keyword':
                    $row[] = $this->get_focus_keyword($post->ID);
                    break;
                    
                case 'wpbakery_text':
                    $row[] = $this->get_wpbakery_text($post->ID);
                    break;
                    
                case 'wpbakery_meta_title':
                    $row[] = get_post_meta($post->ID, '_wpb_vc_js_status', true);
                    break;
                    
                case 'wpbakery_meta_content':
                    $row[] = get_post_meta($post->ID, '_wpb_shortcodes_custom_css', true);
                    break;
                    
                case 'alt_text':
                    $row[] = $this->get_alt_texts($post->ID);
                    break;
                    
                default:
                    $row[] = '';
                    break;
            }
        }
        
        $row[] = get_permalink($post->ID);
        $row[] = admin_url('post.php?post=' . $post->ID . '&action=edit');
        $row[] = $post->post_date;
        $row[] = $post->post_modified;
        
        return $row;
    }
    
    /**
     * Meta-Titel abrufen
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
     * Meta-Beschreibung abrufen
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
     * Focus-Keyword abrufen
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
     * WPBakery Text-Elemente extrahieren
     */
    private function get_wpbakery_text($post_id) {
        $content = get_post_field('post_content', $post_id);
        
        // WPBakery Shortcodes nach Text durchsuchen
        $pattern = '/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s';
        preg_match_all($pattern, $content, $matches);
        
        if (!empty($matches[1])) {
            $text_blocks = array_map('wp_strip_all_tags', $matches[1]);
            return implode(' | ', array_filter($text_blocks));
        }
        
        return '';
    }
    
    /**
     * Alt-Texte von Bildern im Post abrufen
     */
    private function get_alt_texts($post_id) {
        $content = get_post_field('post_content', $post_id);
        
        // Bild-IDs aus Content extrahieren
        $pattern = '/wp-image-(\d+)/';
        preg_match_all($pattern, $content, $matches);
        
        $alt_texts = array();
        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_id) {
                $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                if (!empty($alt_text)) {
                    $alt_texts[] = $alt_text;
                }
            }
        }
        
        // Featured Image auch prüfen
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $featured_alt = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
            if (!empty($featured_alt)) {
                $alt_texts[] = 'Featured: ' . $featured_alt;
            }
        }
        
        return implode(' | ', $alt_texts);
    }
    
    /**
     * CSV-Upload verarbeiten
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
     * Upload-Datei validieren
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
        
        // Dateierweiterung prüfen
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $this->allowed_extensions)) {
            return array('valid' => false, 'message' => 'Nur CSV-Dateien erlaubt');
        }
        
        // MIME-Type prüfen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mimes = array('text/csv', 'text/plain', 'application/csv');
        if (!in_array($mime_type, $allowed_mimes)) {
            return array('valid' => false, 'message' => 'Ungültiger Dateityp');
        }
        
        return array('valid' => true, 'message' => 'Datei gültig');
    }
    
    /**
     * CSV-Vorschau erstellen
     * 
     * @param string $filepath Pfad zur CSV-Datei
     * @return array Vorschau-Daten
     */
    private function create_csv_preview($filepath) {
        $preview = array(
            'headers' => array(),
            'rows' => array(),
            'total_rows' => 0,
            'detected_delimiter' => ';'
        );
        
        if (!file_exists($filepath)) {
            return $preview;
        }
        
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return $preview;
        }
        
        // BOM entfernen falls vorhanden
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        
        // Delimiter erkennen
        $first_line = fgets($handle);
        rewind($handle);
        if ($bom === "\xEF\xBB\xBF") {
            fread($handle, 3); // BOM überspringen
        }
        
        $delimiter = $this->detect_csv_delimiter($first_line);
        $preview['detected_delimiter'] = $delimiter;
        
        // Headers lesen
        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers) {
            $preview['headers'] = array_map('trim', $headers);
        }
        
        // Erste 5 Zeilen als Vorschau
        $row_count = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false && $row_count < 5) {
            $preview['rows'][] = array_map('trim', $row);
            $row_count++;
        }
        
        // Gesamtanzahl Zeilen zählen
        while (fgetcsv($handle, 0, $delimiter) !== false) {
            $row_count++;
        }
        
        $preview['total_rows'] = $row_count;
        
        fclose($handle);
        
        return $preview;
    }
    
    /**
     * CSV-Delimiter erkennen
     * 
     * @param string $line Erste Zeile der CSV
     * @return string Erkannter Delimiter
     */
    private function detect_csv_delimiter($line) {
        $delimiters = array(';', ',', "\t", '|');
        $max_count = 0;
        $detected_delimiter = ';';
        
        foreach ($delimiters as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $max_count) {
                $max_count = $count;
                $detected_delimiter = $delimiter;
            }
        }
        
        return $detected_delimiter;
    }
    
    /**
     * Import-Vorschau abrufen
     * 
     * @param string $filename Dateiname
     * @return array Import-Vorschau
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
            
            // Spalten-Mapping-Optionen erstellen
            $mapping_options = array(
                'id' => 'ID',
                'title' => 'Titel',
                'meta_title' => 'Meta-Titel',
                'meta_description' => 'Meta-Beschreibung',
                'focus_keyword' => 'Focus-Keyword',
                'content' => 'Content',
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
     * CSV-Daten importieren
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
            
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $result = $this->import_single_row($row, $headers, $column_mapping);
                
                if ($result['success']) {
                    if ($result['action'] === 'updated') {
                        $updated++;
                    } else {
                        $imported++;
                    }
                } else {
                    $errors[] = $result['message'];
                }
                
                // Timeout vermeiden
                if (($imported + $updated) % 50 === 0) {
                    @set_time_limit(30);
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
                'errors' => array_slice($errors, 0, 10), // Nur erste 10 Fehler zeigen
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
     * Einzelne CSV-Zeile importieren
     * 
     * @param array $row CSV-Zeile
     * @param array $headers CSV-Headers
     * @param array $column_mapping Spalten-Zuordnung
     * @return array Import-Ergebnis für diese Zeile
     */
    private function import_single_row($row, $headers, $column_mapping) {
        try {
            // Post-ID finden
            $post_id = null;
            $id_column = array_search('id', $column_mapping);
            
            if ($id_column !== false && isset($row[$id_column])) {
                $post_id = intval($row[$id_column]);
            }
            
            if (!$post_id || !get_post($post_id)) {
                return array(
                    'success' => false,
                    'message' => 'Post-ID nicht gefunden oder ungültig: ' . $post_id
                );
            }
            
            $updated_fields = 0;
            
            // Mappings durchgehen und Daten aktualisieren
            foreach ($column_mapping as $column_index => $target_field) {
                if ($target_field === 'ignore' || !isset($row[$column_index])) {
                    continue;
                }
                
                $value = trim($row[$column_index]);
                if (empty($value)) {
                    continue;
                }
                
                switch ($target_field) {
                    case 'title':
                        wp_update_post(array(
                            'ID' => $post_id,
                            'post_title' => $value
                        ));
                        $updated_fields++;
                        break;
                        
                    case 'meta_title':
                        if ($this->update_meta_title($post_id, $value)) {
                            $updated_fields++;
                        }
                        break;
                        
                    case 'meta_description':
                        if ($this->update_meta_description($post_id, $value)) {
                            $updated_fields++;
                        }
                        break;
                        
                    case 'focus_keyword':
                        if ($this->update_focus_keyword($post_id, $value)) {
                            $updated_fields++;
                        }
                        break;
                        
                    case 'content':
                        wp_update_post(array(
                            'ID' => $post_id,
                            'post_content' => $value
                        ));
                        $updated_fields++;
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
                    'message' => 'Keine Felder aktualisiert für Post-ID: ' . $post_id
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
     * Meta-Titel aktualisieren
     */
    private function update_meta_title($post_id, $title) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $title);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_title', $title);
            return true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_title', $title);
            return true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_title', $title);
            return true;
        }
        
        return false;
    }
    
    /**
     * Meta-Beschreibung aktualisieren
     */
    private function update_meta_description($post_id, $description) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $description);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_description', $description);
            return true;
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_description', $description);
            return true;
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_desc', $description);
            return true;
        }
        
        return false;
    }
    
    /**
     * Focus-Keyword aktualisieren
     */
    private function update_focus_keyword($post_id, $keyword) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
            return true;
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $keyword);
            return true;
        }
        
        return false;
    }
    
    /**
     * Hochgeladene Datei löschen
     * 
     * @param string $filename Dateiname
     * @return array Lösch-Ergebnis
     */
    public function delete_uploaded_file($filename) {
        try {
            $filepath = $this->upload_dir . $filename;
            
            if (!file_exists($filepath)) {
                return array(
                    'success' => false,
                    'message' => 'Datei nicht gefunden'
                );
            }
            
            if (!unlink($filepath)) {
                return array(
                    'success' => false,
                    'message' => 'Datei konnte nicht gelöscht werden'
                );
            }
            
            return array(
                'success' => true,
                'message' => 'Datei erfolgreich gelöscht'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Lösch-Fehler: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Export-Statistiken abrufen
     * 
     * @return array Statistiken
     */
    public function get_export_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Post-Typen zählen
        $post_counts = $wpdb->get_results("
            SELECT post_type, post_status, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_status IN ('publish', 'draft', 'private')
            GROUP BY post_type, post_status
        ");
        
        foreach ($post_counts as $count) {
            $stats[$count->post_type . '_' . $count->post_status] = $count->count;
        }
        
        // Content-Typen zählen
        $meta_counts = $wpdb->get_results("
            SELECT 
                COUNT(CASE WHEN pm.meta_key IN ('_yoast_wpseo_title', 'rank_math_title', '_aioseop_title', '_seopress_titles_title') AND pm.meta_value != '' THEN 1 END) as meta_titles,
                COUNT(CASE WHEN pm.meta_key IN ('_yoast_wpseo_metadesc', 'rank_math_description', '_aioseop_description', '_seopress_titles_desc') AND pm.meta_value != '' THEN 1 END) as meta_descriptions,
                COUNT(CASE WHEN pm.meta_key IN ('_yoast_wpseo_focuskw', 'rank_math_focus_keyword') AND pm.meta_value != '' THEN 1 END) as focus_keywords
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('post', 'page') AND p.post_status IN ('publish', 'draft', 'private')
        ");
        
        if (!empty($meta_counts)) {
            $stats['meta_title_count'] = $meta_counts[0]->meta_titles;
            $stats['meta_desc_count'] = $meta_counts[0]->meta_descriptions;
            $stats['focus_keyword_count'] = $meta_counts[0]->focus_keywords;
        }
        
        // Titel und Content sind immer vorhanden
        $total_posts = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_status IN ('publish', 'draft', 'private')
        ");
        
        $stats['title_count'] = $total_posts;
        $stats['content_count'] = $total_posts;
        
        return $stats;
    }
}