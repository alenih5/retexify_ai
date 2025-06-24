<?php
/**
 * ReTexify Export/Import Manager - Überarbeitet
 * 
 * Verwaltet CSV-Export und -Import für SEO-Daten
 * Neue Version: Nur ausgewählte Spalten, WPBakery Meta-Daten, komplette Mediendatenbank
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.8
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
     * Verfügbare Content-Typen (überarbeitet)
     */
    private $available_content_types = array(
        'title' => 'Titel (nur zur Orientierung)',
        'yoast_meta_title' => 'Yoast Meta-Titel',
        'yoast_meta_description' => 'Yoast Meta-Beschreibung', 
        'wpbakery_meta_title' => 'WPBakery Meta-Titel',
        'wpbakery_meta_description' => 'WPBakery Meta-Beschreibung',
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
                SELECT ID, post_title, post_type, post_status, post_date, post_modified
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
                'exported_types' => $content_types
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
            SELECT ID, post_title, post_type, post_status, post_date, post_modified, guid
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
                case 'yoast_meta_title':
                    $headers[] = 'Yoast Meta-Titel (Original)';
                    $headers[] = 'Yoast Meta-Titel (Neu)';
                    break;
                case 'yoast_meta_description':
                    $headers[] = 'Yoast Meta-Beschreibung (Original)';
                    $headers[] = 'Yoast Meta-Beschreibung (Neu)';
                    break;
                case 'wpbakery_meta_title':
                    $headers[] = 'WPBakery Meta-Titel (Original)';
                    $headers[] = 'WPBakery Meta-Titel (Neu)';
                    break;
                case 'wpbakery_meta_description':
                    $headers[] = 'WPBakery Meta-Beschreibung (Original)';
                    $headers[] = 'WPBakery Meta-Beschreibung (Neu)';
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
                    
                case 'yoast_meta_title':
                    if ($item_type === 'post') {
                        $row[] = $this->get_yoast_meta_title($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Yoast Meta-Titel
                        $row[] = '';
                    }
                    break;
                    
                case 'yoast_meta_description':
                    if ($item_type === 'post') {
                        $row[] = $this->get_yoast_meta_description($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine Yoast Meta-Beschreibung
                        $row[] = '';
                    }
                    break;
                    
                case 'wpbakery_meta_title':
                    if ($item_type === 'post') {
                        $row[] = $this->get_wpbakery_meta_title($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine WPBakery Meta-Titel
                        $row[] = '';
                    }
                    break;
                    
                case 'wpbakery_meta_description':
                    if ($item_type === 'post') {
                        $row[] = $this->get_wpbakery_meta_description($item->ID);
                        $row[] = ''; // Leer für 'Neu'
                    } else {
                        $row[] = ''; // Medien haben keine WPBakery Meta-Beschreibung
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
     * Yoast Meta-Titel abrufen (nur Yoast)
     */
    private function get_yoast_meta_title($post_id) {
        return get_post_meta($post_id, '_yoast_wpseo_title', true);
    }
    
    /**
     * Yoast Meta-Beschreibung abrufen (nur Yoast)
     */
    private function get_yoast_meta_description($post_id) {
        return get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    }
    
    /**
     * WPBakery Meta-Titel abrufen (Custom Field)
     */
    private function get_wpbakery_meta_title($post_id) {
        return get_post_meta($post_id, '_wpbakery_meta_title', true);
    }
    
    /**
     * WPBakery Meta-Beschreibung abrufen (Custom Field)
     */
    private function get_wpbakery_meta_description($post_id) {
        return get_post_meta($post_id, '_wpbakery_meta_description', true);
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
            
            // Spalten-Mapping-Optionen (nur für "Neu" Spalten)
            $mapping_options = array(
                'id' => 'ID',
                'yoast_meta_title_new' => 'Yoast Meta-Titel (Neu)',
                'yoast_meta_description_new' => 'Yoast Meta-Beschreibung (Neu)',
                'wpbakery_meta_title_new' => 'WPBakery Meta-Titel (Neu)',
                'wpbakery_meta_description_new' => 'WPBakery Meta-Beschreibung (Neu)',
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
                    'message' => 'ID nicht gefunden oder ungültig: ' . $id
                );
            }
            
            // Prüfen ob Post oder Attachment
            $post = get_post($id);
            if (!$post) {
                return array(
                    'success' => false,
                    'message' => 'Item mit ID nicht gefunden: ' . $id
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
                    case 'yoast_meta_title_new':
                        if ($post->post_type !== 'attachment') {
                            update_post_meta($id, '_yoast_wpseo_title', $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'yoast_meta_description_new':
                        if ($post->post_type !== 'attachment') {
                            update_post_meta($id, '_yoast_wpseo_metadesc', $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'wpbakery_meta_title_new':
                        if ($post->post_type !== 'attachment') {
                            update_post_meta($id, '_wpbakery_meta_title', $value);
                            $updated_fields++;
                        }
                        break;
                        
                    case 'wpbakery_meta_description_new':
                        if ($post->post_type !== 'attachment') {
                            update_post_meta($id, '_wpbakery_meta_description', $value);
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
                    'message' => 'Keine Felder aktualisiert für ID: ' . $id
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
     * Export-Statistiken abrufen (überarbeitet)
     * 
     * @return array Statistiken
     */
    public function get_export_stats() {
        global $wpdb;

        $stats = array();

        // Post-Typen und Status
        $post_counts = wp_count_posts('post');
        $page_counts = wp_count_posts('page');
        $stats['post'] = $post_counts->publish + $post_counts->draft;
        $stats['page'] = $page_counts->publish + $page_counts->draft;
        $stats['publish'] = $post_counts->publish + $page_counts->publish;
        $stats['draft'] = $post_counts->draft + $page_counts->draft;
        
        $total_posts = $stats['post'] + $stats['page'];
        $stats['title'] = $total_posts;

        // Yoast SEO Meta-Daten (nur Yoast)
        $stats['yoast_meta_title'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_title' AND meta_value != ''");
        $stats['yoast_meta_description'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_yoast_wpseo_metadesc' AND meta_value != ''");
        
        // WPBakery Meta-Daten (Custom Fields)
        $stats['wpbakery_meta_title'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wpbakery_meta_title' AND meta_value != ''");
        $stats['wpbakery_meta_description'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = '_wpbakery_meta_description' AND meta_value != ''");
        
        // Komplette Mediendatenbank
        $stats['alt_texts'] = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");

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