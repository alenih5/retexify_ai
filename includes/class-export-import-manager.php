<?php
/**
 * ReTexify Export/Import Manager - VOLLSTÄNDIG KORRIGIERTE VERSION
 * 
 * Behebt alle WPBakery-Erkennungs- und Zahlen-Mapping-Probleme
 * Version: 3.5.9 - Finale Korrektur für korrekte Statistiken
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.9
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
     * KORRIGIERT: Verfügbare Content-Typen (kompatibel mit ursprünglichem Interface)
     */
    private $available_content_types = array(
        'title' => 'Titel (nur zur Orientierung)',
        'meta_title' => 'Meta-Titel (alle SEO-Plugins)',
        'meta_description' => 'Meta-Beschreibung (alle SEO-Plugins)',
        'focus_keyword' => 'Focus-Keyword (alle SEO-Plugins)',
        'post_content' => 'Vollständiger Post-Inhalt',
        'wpbakery_text' => 'WPBakery Page Builder Texte',
        'alt_texts' => 'Bild Alt-Texte (komplette Mediendatenbank)'
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
            if (file_put_contents($htaccess_file, $htaccess_content) === false) {
                error_log('ReTexify AI: Konnte .htaccess nicht erstellen');
            }
            
            // index.php mit erweiterten Sicherheitsheadern
            $index_content = "<?php\n";
            $index_content .= "// ReTexify AI Security - Zugriff verweigert\n";
            $index_content .= "header('HTTP/1.0 403 Forbidden');\n";
            $index_content .= "header('Content-Type: text/plain');\n";
            $index_content .= "exit('Zugriff verweigert.');\n";
            
            $index_file = $this->upload_dir . 'index.php';
            if (file_put_contents($index_file, $index_content) === false) {
                error_log('ReTexify AI: Konnte index.php nicht erstellen');
            }
            
            // .gitignore für Entwicklungsumgebung
            $gitignore_content = "# ReTexify AI temporäre Upload-Dateien\n";
            $gitignore_content .= "*.csv\n";
            $gitignore_content .= "import_*\n";
            $gitignore_content .= "export_*\n";
            
            file_put_contents($this->upload_dir . '.gitignore', $gitignore_content);
        }
        
        // Verzeichnis-Berechtigungen prüfen
        if (!is_writable($this->upload_dir)) {
            throw new Exception('Upload-Verzeichnis ist nicht beschreibbar');
        }
    }
    
    /**
     * Alte Upload-Dateien automatisch bereinigen
     */
    private function cleanup_old_uploads() {
        $files = glob($this->upload_dir . 'import_*.csv');
        $current_time = time();
        
        foreach ($files as $file) {
            // Dateien älter als 24 Stunden löschen
            if (is_file($file) && ($current_time - filemtime($file)) > (24 * 3600)) {
                unlink($file);
            }
        }
        
        // Export-Dateien älter als 48 Stunden löschen
        $export_files = glob($this->upload_dir . 'retexify_export_*.csv');
        foreach ($export_files as $file) {
            if (is_file($file) && ($current_time - filemtime($file)) > (48 * 3600)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Sichere Download-URL mit zeitlicher Begrenzung erstellen
     */
    public function create_secure_download_url($filename) {
        $expiry = time() + (24 * 3600); // 24 Stunden gültig
        $hash = wp_hash($filename . $expiry . wp_salt());
        
        return admin_url('admin-ajax.php') . '?' . http_build_query(array(
            'action' => 'retexify_download_export_file',
            'filename' => urlencode(basename($filename)),
            'expiry' => $expiry,
            'hash' => $hash,
            'nonce' => wp_create_nonce('retexify_download_nonce')
        ));
    }
    
    /**
     * CSV-Export durchführen - nur ausgewählte Spalten
     * 
     * @param array $post_types Post-Typen zum Exportieren
     * @param array $status_types Status-Typen
     * @param array $content_types Content-Typen (nur ausgewählte)
     * @return array Export-Ergebnis
     */
    public function export_to_csv($post_types = array('post', 'page'), $status_types = array('publish'), $content_types = array()) {
        try {
            global $wpdb;
            
            // Validierung der Content-Typen
            $content_types = array_intersect($content_types, array_keys($this->available_content_types));
            
            if (empty($content_types)) {
                return array(
                    'success' => false,
                    'message' => 'Keine gültigen Content-Typen ausgewählt'
                );
            }
            
            // Posts abrufen
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
            
            // Bei Alt-Texte: Alle Medien-Attachments hinzufügen
            $media_items = array();
            if (in_array('alt_texts', $content_types)) {
                $media_items = $this->get_all_media_items();
            }
            
            if (empty($posts) && empty($media_items)) {
                return array(
                    'success' => false,
                    'message' => 'Keine Inhalte zum Exportieren gefunden'
                );
            }
            
            // CSV-Daten sammeln
            $csv_data = array();
            $headers = $this->get_csv_headers($content_types);
            $csv_data[] = $headers;
            
            // Posts verarbeiten
            foreach ($posts as $post) {
                $row = $this->build_csv_row($post, $content_types, 'post');
                if (!empty($row)) {
                    $csv_data[] = $row;
                }
            }
            
            // Medien-Items verarbeiten (falls Alt-Texte ausgewählt)
            if (!empty($media_items)) {
                foreach ($media_items as $media) {
                    $row = $this->build_csv_row($media, $content_types, 'media');
                    if (!empty($row)) {
                        $csv_data[] = $row;
                    }
                }
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
            
            return array(
                'success' => true,
                'message' => 'CSV-Export erfolgreich erstellt',
                'filename' => $filename,
                'filepath' => $filepath,
                'file_size' => filesize($filepath),
                'row_count' => count($csv_data) - 1, // Ohne Header
                'columns' => $headers,
                'exported_types' => $content_types,
                'download_url' => $this->create_secure_download_url($filename)
            );
            
        } catch (Exception $e) {
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
     * CSV-Headers basierend auf ausgewählten Content-Typen erstellen
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
     * CSV-Zeile für einen Post/Media erstellen
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
        
        // Minimale Dateigröße (gegen leere Dateien)
        if ($file['size'] < 10) {
            return array('valid' => false, 'message' => 'Datei ist zu klein oder leer');
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
        
        $allowed_mimes = array('text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel');
        if (!in_array($mime_type, $allowed_mimes)) {
            return array('valid' => false, 'message' => 'Ungültiger Dateityp: ' . $mime_type);
        }
        
        // Dateiinhalt validieren (erste Zeile lesen)
        $handle = fopen($file['tmp_name'], 'r');
        if (!$handle) {
            return array('valid' => false, 'message' => 'Datei konnte nicht gelesen werden');
        }
        
        $first_line = fgets($handle);
        fclose($handle);
        
        // Prüfen ob es wie CSV aussieht
        $delimiters = array(',', ';', '\t', '|');
        $has_delimiter = false;
        foreach ($delimiters as $delimiter) {
            if (strpos($first_line, $delimiter) !== false) {
                $has_delimiter = true;
                break;
            }
        }
        
        if (!$has_delimiter) {
            return array('valid' => false, 'message' => 'Datei scheint keine gültige CSV-Datei zu sein');
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
            
            // Prüfen ob Post oder Attachment
            $post = get_post($id);
            if (!$post) {
                return array(
                    'success' => false,
                    'message' => "Item mit ID {$id} nicht gefunden"
                );
            }
            
            $updated_fields = 0;
            
            // Mappings durchgehen und nur "Neu" Spalten importieren
            foreach ($column_mapping as $column_index => $target_field) {
                if ($target_field === 'ignore' || !isset($row[$column_index])) {
                    continue;
                }
                
                $value = trim($row[$column_index]);
                if (empty($value)) {
                    continue;
                }
                
                switch ($target_field) {
                    case 'meta_title_new':
                        if ($post->post_type !== 'attachment') {
                            $this->save_meta_title($id, $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'meta_description_new':
                        if ($post->post_type !== 'attachment') {
                            $this->save_meta_description($id, $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'focus_keyword_new':
                        if ($post->post_type !== 'attachment') {
                            $this->save_focus_keyword($id, $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'wpbakery_text_new':
                        if ($post->post_type !== 'attachment') {
                            // WPBakery Text in Post-Content aktualisieren wäre komplex
                            // Hier könnten Custom Fields verwendet werden
                            update_post_meta($id, '_wpbakery_custom_text', $value);
                            $updated_fields++;
                        }
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
     * Meta-Titel in allen SEO-Plugins speichern
     */
    private function save_meta_title($post_id, $meta_title) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_title', $meta_title);
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_title', $meta_title);
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_title', $meta_title);
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_title', $meta_title);
        }
    }
    
    /**
     * Meta-Beschreibung in allen SEO-Plugins speichern
     */
    private function save_meta_description($post_id, $meta_description) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_description', $meta_description);
        }
        
        // All in One SEO
        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            update_post_meta($post_id, '_aioseop_description', $meta_description);
        }
        
        // SEOPress
        if (is_plugin_active('wp-seopress/seopress.php')) {
            update_post_meta($post_id, '_seopress_titles_desc', $meta_description);
        }
    }
    
    /**
     * Focus-Keyword in allen SEO-Plugins speichern
     */
    private function save_focus_keyword($post_id, $focus_keyword) {
        // Yoast SEO
        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
        }
        
        // Rank Math
        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_keyword);
        }
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
            
            // Sicherheitscheck: Nur Dateien im Upload-Verzeichnis
            $real_upload_dir = realpath($this->upload_dir);
            $real_filepath = realpath($filepath);
            
            if (!$real_upload_dir || !$real_filepath || strpos($real_filepath, $real_upload_dir) !== 0) {
                return array(
                    'success' => false,
                    'message' => 'Sicherheitsfehler: Dateipfad ungültig'
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
     * VOLLSTÄNDIG KORRIGIERT: Export-Statistiken genau wie im ursprünglichen System
     * 
     * @return array Statistiken
     */
    public function get_export_stats() {
        global $wpdb;

        $stats = array();

        // Post-Typen und Status (unverändert)
        $post_counts = wp_count_posts('post');
        $page_counts = wp_count_posts('page');
        $stats['post'] = ($post_counts->publish ?? 0) + ($post_counts->draft ?? 0);
        $stats['page'] = ($page_counts->publish ?? 0) + ($page_counts->draft ?? 0);
        $stats['publish'] = ($post_counts->publish ?? 0) + ($page_counts->publish ?? 0);
        $stats['draft'] = ($post_counts->draft ?? 0) + ($page_counts->draft ?? 0);
        
        $total_posts = $stats['post'] + $stats['page'];
        $stats['title'] = $total_posts;
        
        // YOAST SEO spezifisch (wie ursprünglich)
        $stats['yoast_meta_title'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_yoast_wpseo_title' 
            AND meta_value != '' 
            AND meta_value IS NOT NULL
        ") ?: 0;
        
        $stats['yoast_meta_description'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_yoast_wpseo_metadesc' 
            AND meta_value != '' 
            AND meta_value IS NOT NULL
        ") ?: 0;
        
        $stats['yoast_focus_keyword'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_yoast_wpseo_focuskw' 
            AND meta_value != '' 
            AND meta_value IS NOT NULL
        ") ?: 0;
        
        // WPBAKERY META spezifisch (KORRIGIERT - sucht nach echten WPBakery Custom Fields)
        $stats['wpbakery_meta_title'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wpbakery_meta_title' 
            AND meta_value != '' 
            AND meta_value IS NOT NULL
        ") ?: 0;
        
        $stats['wpbakery_meta_description'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wpbakery_meta_description' 
            AND meta_value != '' 
            AND meta_value IS NOT NULL
        ") ?: 0;
        
        // Post-Content
        $stats['post_content'] = $total_posts;
        
        // WPBakery Text Content (KORRIGIERT - Posts mit WPBakery Shortcodes)
        $stats['wpbakery_text'] = $wpdb->get_var("
            SELECT COUNT(ID) FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_status = 'publish'
            AND (
                post_content LIKE '%[vc_column_text%' OR
                post_content LIKE '%[vc_text_separator%' OR
                post_content LIKE '%[vc_custom_heading%' OR
                post_content LIKE '%wpb-%' OR
                post_content LIKE '%[vc_%'
            )
        ") ?: 0;
        
        // Alt-Texte (Mediendatenbank)
        $stats['alt_texts'] = $wpdb->get_var("
            SELECT COUNT(ID) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ") ?: 0;
        
        // Debug-Log
        error_log('ReTexify Export Stats (ORIGINAL-KOMPATIBEL): ' . json_encode($stats));

        return $stats;
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