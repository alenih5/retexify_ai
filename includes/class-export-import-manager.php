<?php
/**
 * ReTexify Export/Import Manager
 * 
 * Exportiert und importiert SEO-Daten, WPBakery-Inhalte und Alt-Texte
 * Unterstützt alle gängigen SEO-Plugins und Page Builder
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.6
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Export_Import_Manager {
    
    /**
     * Unterstützte SEO-Plugins
     */
    private $seo_plugins = array(
        'yoast' => array(
            'name' => 'Yoast SEO',
            'file' => 'wordpress-seo/wp-seo.php',
            'meta_title' => '_yoast_wpseo_title',
            'meta_description' => '_yoast_wpseo_metadesc',
            'focus_keyword' => '_yoast_wpseo_focuskw'
        ),
        'rankmath' => array(
            'name' => 'Rank Math',
            'file' => 'seo-by-rank-math/rank-math.php',
            'meta_title' => 'rank_math_title',
            'meta_description' => 'rank_math_description',
            'focus_keyword' => 'rank_math_focus_keyword'
        ),
        'aioseo' => array(
            'name' => 'All in One SEO',
            'file' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
            'meta_title' => '_aioseop_title',
            'meta_description' => '_aioseop_description',
            'focus_keyword' => null
        ),
        'seopress' => array(
            'name' => 'SEOPress',
            'file' => 'wp-seopress/seopress.php',
            'meta_title' => '_seopress_titles_title',
            'meta_description' => '_seopress_titles_desc',
            'focus_keyword' => null
        )
    );
    
    /**
     * Unterstützte Page Builder
     */
    private $page_builders = array(
        'wpbakery' => array(
            'name' => 'WPBakery Page Builder',
            'file' => 'js_composer/js_composer.php'
        ),
        'salient' => array(
            'name' => 'Salient Theme',
            'function' => 'nectar_theme_setup'
        )
    );
    
    /**
     * Cache für Performance
     */
    private $cache = array();
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Cache initialisieren
        $this->cache = array();
    }
    
    /**
     * Export-Statistiken abrufen
     * 
     * @return array Statistiken für Export-Interface
     */
    public function get_export_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Posts und Seiten zählen
        $post_counts = $wpdb->get_results("
            SELECT post_type, post_status, COUNT(*) as count 
            FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            GROUP BY post_type, post_status
        ");
        
        $stats['posts'] = 0;
        $stats['pages'] = 0;
        $stats['published'] = 0;
        $stats['drafts'] = 0;
        $stats['private'] = 0;
        
        foreach ($post_counts as $count) {
            if ($count->post_type === 'post') {
                $stats['posts'] += $count->count;
            } elseif ($count->post_type === 'page') {
                $stats['pages'] += $count->count;
            }
            
            if ($count->post_status === 'publish') {
                $stats['published'] += $count->count;
            } elseif ($count->post_status === 'draft') {
                $stats['drafts'] += $count->count;
            } elseif ($count->post_status === 'private') {
                $stats['private'] += $count->count;
            }
        }
        
        // SEO-Daten zählen
        $stats['meta_titles'] = $this->count_seo_data('meta_title');
        $stats['meta_descriptions'] = $this->count_seo_data('meta_description');
        $stats['focus_keywords'] = $this->count_seo_data('focus_keyword');
        
        // Content und WPBakery zählen
        $stats['content'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_content != ''
        ");
        
        $stats['titles'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_title != ''
        ");
        
        // WPBakery-Daten zählen
        $stats['wpbakery'] = $this->count_wpbakery_content();
        
        // Alt-Texte zählen
        $stats['alt_texts'] = $this->count_alt_texts();
        
        return $this->format_export_stats_html($stats);
    }
    
    /**
     * SEO-Daten für spezifischen Typ zählen
     * 
     * @param string $type SEO-Datentyp
     * @return int Anzahl
     */
    private function count_seo_data($type) {
        global $wpdb;
        
        $meta_keys = array();
        
        foreach ($this->seo_plugins as $plugin) {
            if (!empty($plugin[$type])) {
                $meta_keys[] = "'" . $plugin[$type] . "'";
            }
        }
        
        if (empty($meta_keys)) {
            return 0;
        }
        
        $meta_keys_in = implode(',', $meta_keys);
        
        return $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type IN ('post', 'page')
            AND pm.meta_key IN ({$meta_keys_in})
            AND pm.meta_value != ''
        ");
    }
    
    /**
     * WPBakery-Content zählen
     * 
     * @return int Anzahl Posts mit WPBakery-Content
     */
    private function count_wpbakery_content() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type IN ('post', 'page') 
            AND post_content LIKE '%[vc_%'
        ");
    }
    
    /**
     * Alt-Texte zählen
     * 
     * @return int Anzahl Bilder mit Alt-Texten
     */
    private function count_alt_texts() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_wp_attachment_image_alt' 
            AND meta_value != ''
        ");
    }
    
    /**
     * Export-Statistiken als HTML formatieren
     * 
     * @param array $stats Statistiken
     * @return string HTML
     */
    private function format_export_stats_html($stats) {
        $html = '<div class="retexify-export-stats-grid">';
        
        $html .= '<div class="retexify-stat-card">';
        $html .= '<h4>📊 Gesamt-Übersicht</h4>';
        $html .= '<div class="retexify-stat-row"><span>Posts:</span> <strong>' . $stats['posts'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Seiten:</span> <strong>' . $stats['pages'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Veröffentlicht:</span> <strong>' . $stats['published'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Entwürfe:</span> <strong>' . $stats['drafts'] . '</strong></div>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-stat-card">';
        $html .= '<h4>🎯 SEO-Daten</h4>';
        $html .= '<div class="retexify-stat-row"><span>Meta-Titel:</span> <strong>' . $stats['meta_titles'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Meta-Beschreibungen:</span> <strong>' . $stats['meta_descriptions'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Focus Keywords:</span> <strong>' . $stats['focus_keywords'] . '</strong></div>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-stat-card">';
        $html .= '<h4>📄 Content</h4>';
        $html .= '<div class="retexify-stat-row"><span>Titel:</span> <strong>' . $stats['titles'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Content:</span> <strong>' . $stats['content'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>WPBakery:</span> <strong>' . $stats['wpbakery'] . '</strong></div>';
        $html .= '<div class="retexify-stat-row"><span>Alt-Texte:</span> <strong>' . $stats['alt_texts'] . '</strong></div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // JavaScript zum Aktualisieren der Zahlen
        $html .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#posts-count").text("' . $stats['posts'] . '");
            $("#pages-count").text("' . $stats['pages'] . '");
            $("#published-count").text("' . $stats['published'] . '");
            $("#drafts-count").text("' . $stats['drafts'] . '");
            $("#private-count").text("' . $stats['private'] . '");
            $("#titles-count").text("' . $stats['titles'] . '");
            $("#content-count").text("' . $stats['content'] . '");
            $("#meta-titles-count").text("' . $stats['meta_titles'] . '");
            $("#meta-descriptions-count").text("' . $stats['meta_descriptions'] . '");
            $("#focus-keywords-count").text("' . $stats['focus_keywords'] . '");
            $("#wpbakery-count").text("' . $stats['wpbakery'] . '");
            $("#alt-texts-count").text("' . $stats['alt_texts'] . '");
        });
        </script>';
        
        return $html;
    }
    
    /**
     * Export-Vorschau generieren
     * 
     * @param array $options Export-Optionen
     * @return array Vorschau-Daten
     */
    public function generate_export_preview($options) {
        $post_types = $options['post_types'] ?? array('post', 'page');
        $post_status = $options['post_status'] ?? array('publish');
        $fields = $options['fields'] ?? array();
        
        if (empty($post_types) || empty($fields)) {
            throw new Exception('Bitte wählen Sie mindestens einen Post-Typ und ein Feld aus');
        }
        
        // Beispiel-Posts abrufen (max. 5 für Vorschau)
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => $post_status,
            'numberposts' => 5,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));
        
        $preview_data = array();
        
        foreach ($posts as $post) {
            $row = array('ID' => $post->ID);
            
            foreach ($fields as $field) {
                switch ($field) {
                    case 'post_title':
                        $row['Titel (Original)'] = wp_trim_words($post->post_title, 8);
                        $row['Titel (Neu)'] = '';
                        break;
                        
                    case 'post_content':
                        $content = $this->clean_content($post->post_content);
                        $row['Content (Original)'] = wp_trim_words($content, 15);
                        $row['Content (Neu)'] = '';
                        break;
                        
                    case 'meta_title':
                        $current = $this->get_seo_meta($post->ID, 'meta_title');
                        $row['Meta-Titel (Original)'] = wp_trim_words($current, 8);
                        $row['Meta-Titel (Neu)'] = '';
                        break;
                        
                    case 'meta_description':
                        $current = $this->get_seo_meta($post->ID, 'meta_description');
                        $row['Meta-Beschreibung (Original)'] = wp_trim_words($current, 15);
                        $row['Meta-Beschreibung (Neu)'] = '';
                        break;
                        
                    case 'focus_keyword':
                        $current = $this->get_seo_meta($post->ID, 'focus_keyword');
                        $row['Focus Keyphrase (Original)'] = $current;
                        $row['Focus Keyphrase (Neu)'] = '';
                        break;
                        
                    case 'wpbakery_text':
                        $wpbakery = $this->extract_wpbakery_text($post->post_content);
                        $row['WPBakery Text (Original)'] = wp_trim_words($wpbakery, 15);
                        $row['WPBakery Text (Neu)'] = '';
                        break;
                }
            }
            
            $preview_data[] = $row;
        }
        
        return array(
            'preview' => $preview_data,
            'total_posts' => count($posts),
            'message' => 'Vorschau zeigt ' . count($posts) . ' von ' . count(get_posts(array('post_type' => $post_types, 'post_status' => $post_status, 'numberposts' => -1))) . ' Posts'
        );
    }
    
    /**
     * Export durchführen
     * 
     * @param array $options Export-Optionen
     * @return array Download-Informationen
     */
    public function perform_export($options) {
        $post_types = $options['post_types'] ?? array('post', 'page');
        $post_status = $options['post_status'] ?? array('publish');
        $fields = $options['fields'] ?? array();
        
        if (empty($post_types) || empty($fields)) {
            throw new Exception('Bitte wählen Sie mindestens einen Post-Typ und ein Feld aus');
        }
        
        // Alle passenden Posts abrufen
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => $post_status,
            'numberposts' => -1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));
        
        if (empty($posts)) {
            throw new Exception('Keine Posts gefunden, die den Kriterien entsprechen');
        }
        
        // CSV-Header erstellen
        $csv_header = array('ID');
        $field_mapping = array();
        
        foreach ($fields as $field) {
            switch ($field) {
                case 'post_title':
                    $csv_header[] = 'Titel (Original)';
                    $csv_header[] = 'Titel (Neu)';
                    $field_mapping['post_title'] = array('original' => 'Titel (Original)', 'new' => 'Titel (Neu)');
                    break;
                    
                case 'post_content':
                    $csv_header[] = 'Content (Original)';
                    $csv_header[] = 'Content (Neu)';
                    $field_mapping['post_content'] = array('original' => 'Content (Original)', 'new' => 'Content (Neu)');
                    break;
                    
                case 'meta_title':
                    $csv_header[] = 'Meta-Titel (Original)';
                    $csv_header[] = 'Meta-Titel (Neu)';
                    $field_mapping['meta_title'] = array('original' => 'Meta-Titel (Original)', 'new' => 'Meta-Titel (Neu)');
                    break;
                    
                case 'meta_description':
                    $csv_header[] = 'Meta-Beschreibung (Original)';
                    $csv_header[] = 'Meta-Beschreibung (Neu)';
                    $field_mapping['meta_description'] = array('original' => 'Meta-Beschreibung (Original)', 'new' => 'Meta-Beschreibung (Neu)');
                    break;
                    
                case 'focus_keyword':
                    $csv_header[] = 'Focus Keyphrase (Original)';
                    $csv_header[] = 'Focus Keyphrase (Neu)';
                    $field_mapping['focus_keyword'] = array('original' => 'Focus Keyphrase (Original)', 'new' => 'Focus Keyphrase (Neu)');
                    break;
                    
                case 'wpbakery_text':
                    $csv_header[] = 'WPBakery Text (Original)';
                    $csv_header[] = 'WPBakery Text (Neu)';
                    $field_mapping['wpbakery_text'] = array('original' => 'WPBakery Text (Original)', 'new' => 'WPBakery Text (Neu)');
                    break;
                    
                case 'wpbakery_meta_title':
                    $csv_header[] = 'WPBakery Meta-Titel (Original)';
                    $csv_header[] = 'WPBakery Meta-Titel (Neu)';
                    $field_mapping['wpbakery_meta_title'] = array('original' => 'WPBakery Meta-Titel (Original)', 'new' => 'WPBakery Meta-Titel (Neu)');
                    break;
                    
                case 'wpbakery_meta_content':
                    $csv_header[] = 'WPBakery Meta-Content (Original)';
                    $csv_header[] = 'WPBakery Meta-Content (Neu)';
                    $field_mapping['wpbakery_meta_content'] = array('original' => 'WPBakery Meta-Content (Original)', 'new' => 'WPBakery Meta-Content (Neu)');
                    break;
                    
                case 'alt_texts':
                    $csv_header[] = 'Alt-Texte (Original)';
                    $csv_header[] = 'Alt-Texte (Neu)';
                    $field_mapping['alt_texts'] = array('original' => 'Alt-Texte (Original)', 'new' => 'Alt-Texte (Neu)');
                    break;
            }
        }
        
        // CSV-Daten erstellen
        $csv_data = array();
        $csv_data[] = $csv_header;
        
        foreach ($posts as $post) {
            $row = array($post->ID);
            
            foreach ($fields as $field) {
                switch ($field) {
                    case 'post_title':
                        $row[] = $post->post_title;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'post_content':
                        $content = $this->clean_content($post->post_content);
                        $row[] = $content;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'meta_title':
                        $current = $this->get_seo_meta($post->ID, 'meta_title');
                        $row[] = $current;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'meta_description':
                        $current = $this->get_seo_meta($post->ID, 'meta_description');
                        $row[] = $current;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'focus_keyword':
                        $current = $this->get_seo_meta($post->ID, 'focus_keyword');
                        $row[] = $current;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'wpbakery_text':
                        $wpbakery = $this->extract_wpbakery_text($post->post_content);
                        $row[] = $wpbakery;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'wpbakery_meta_title':
                        $meta_title = $this->extract_wpbakery_meta($post->post_content, 'title');
                        $row[] = $meta_title;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'wpbakery_meta_content':
                        $meta_content = $this->extract_wpbakery_meta($post->post_content, 'content');
                        $row[] = $meta_content;
                        $row[] = ''; // Neue Spalte leer
                        break;
                        
                    case 'alt_texts':
                        $alt_texts = $this->get_alt_texts($post->ID);
                        $row[] = implode(' | ', $alt_texts);
                        $row[] = ''; // Neue Spalte leer
                        break;
                }
            }
            
            $csv_data[] = $row;
        }
        
        // CSV-Datei erstellen
        $upload_dir = wp_upload_dir();
        $filename = 'retexify-export-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($filepath, 'w');
        if (!$file) {
            throw new Exception('Kann CSV-Datei nicht erstellen');
        }
        
        // UTF-8 BOM für Excel
        fwrite($file, "\xEF\xBB\xBF");
        
        foreach ($csv_data as $row) {
            fputcsv($file, $row, ';'); // Semikolon für deutsche Excel-Version
        }
        
        fclose($file);
        
        $download_url = $upload_dir['url'] . '/' . $filename;
        
        return array(
            'download_url' => $download_url,
            'filename' => $filename,
            'total_posts' => count($posts),
            'total_fields' => count($fields),
            'file_size' => size_format(filesize($filepath)),
            'message' => 'Export erfolgreich! ' . count($posts) . ' Posts mit ' . count($fields) . ' Feldern exportiert.'
        );
    }
    
    /**
     * Import durchführen
     * 
     * @param string $filepath Pfad zur CSV-Datei
     * @return array Import-Ergebnisse
     */
    public function perform_import($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception('CSV-Datei nicht gefunden');
        }
        
        // CSV-Datei einlesen
        $csv_data = array();
        $file = fopen($filepath, 'r');
        
        if (!$file) {
            throw new Exception('Kann CSV-Datei nicht öffnen');
        }
        
        // UTF-8 BOM überspringen falls vorhanden
        $bom = fread($file, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($file);
        }
        
        while (($row = fgetcsv($file, 0, ';')) !== FALSE) {
            $csv_data[] = $row;
        }
        
        fclose($file);
        
        if (empty($csv_data) || count($csv_data) < 2) {
            throw new Exception('CSV-Datei ist leer oder ungültig');
        }
        
        $header = array_map('trim', $csv_data[0]);
        $data_rows = array_slice($csv_data, 1);
        
        // ID-Spalte finden
        $id_column = array_search('ID', $header);
        if ($id_column === false) {
            throw new Exception('ID-Spalte nicht gefunden');
        }
        
        // Importierbare Spalten identifizieren (nur "Neu"-Spalten)
        $import_fields = array();
        foreach ($header as $index => $column_name) {
            if (strpos($column_name, '(Neu)') !== false) {
                $import_fields[$index] = $column_name;
            }
        }
        
        if (empty($import_fields)) {
            throw new Exception('Keine importierbaren Spalten gefunden (suche nach "(Neu)"-Spalten)');
        }
        
        $results = array(
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array(),
            'details' => array()
        );
        
        foreach ($data_rows as $row_index => $row) {
            $results['processed']++;
            
            $post_id = intval($row[$id_column] ?? 0);
            
            if (!$post_id) {
                $results['skipped']++;
                $results['errors'][] = "Zeile " . ($row_index + 2) . ": Ungültige Post-ID";
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                $results['skipped']++;
                $results['errors'][] = "Zeile " . ($row_index + 2) . ": Post mit ID {$post_id} nicht gefunden";
                continue;
            }
            
            $updated_fields = array();
            
            foreach ($import_fields as $column_index => $column_name) {
                $new_value = trim($row[$column_index] ?? '');
                
                if (empty($new_value)) {
                    continue; // Leere Felder überspringen
                }
                
                try {
                    if ($this->import_field($post_id, $column_name, $new_value)) {
                        $updated_fields[] = $column_name;
                    }
                } catch (Exception $e) {
                    $results['errors'][] = "Zeile " . ($row_index + 2) . ", Feld '{$column_name}': " . $e->getMessage();
                }
            }
            
            if (!empty($updated_fields)) {
                $results['updated']++;
                $results['details'][] = "Post {$post_id}: " . implode(', ', $updated_fields);
            } else {
                $results['skipped']++;
            }
        }
        
        return array(
            'success' => true,
            'results' => $results,
            'message' => "Import abgeschlossen! {$results['updated']} Posts aktualisiert, {$results['skipped']} übersprungen, {$results['processed']} verarbeitet."
        );
    }
    
    /**
     * Einzelnes Feld importieren
     * 
     * @param int $post_id Post-ID
     * @param string $field_name Feldname
     * @param string $value Neuer Wert
     * @return bool Erfolgreich aktualisiert
     */
    private function import_field($post_id, $field_name, $value) {
        switch ($field_name) {
            case 'Titel (Neu)':
                return $this->update_post_title($post_id, $value);
                
            case 'Content (Neu)':
                return $this->update_post_content($post_id, $value);
                
            case 'Meta-Titel (Neu)':
                return $this->update_seo_meta($post_id, 'meta_title', $value);
                
            case 'Meta-Beschreibung (Neu)':
                return $this->update_seo_meta($post_id, 'meta_description', $value);
                
            case 'Focus Keyphrase (Neu)':
                return $this->update_seo_meta($post_id, 'focus_keyword', $value);
                
            case 'WPBakery Text (Neu)':
                return $this->update_wpbakery_text($post_id, $value);
                
            case 'WPBakery Meta-Titel (Neu)':
                return $this->update_wpbakery_meta($post_id, 'title', $value);
                
            case 'WPBakery Meta-Content (Neu)':
                return $this->update_wpbakery_meta($post_id, 'content', $value);
                
            case 'Alt-Texte (Neu)':
                return $this->update_alt_texts($post_id, $value);
                
            default:
                throw new Exception("Unbekanntes Feld: {$field_name}");
        }
    }
    
    /**
     * Post-Titel aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $new_title Neuer Titel
     * @return bool Erfolg
     */
    private function update_post_title($post_id, $new_title) {
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_title' => $new_title
        ));
        
        return !is_wp_error($result);
    }
    
    /**
     * Post-Content aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $new_content Neuer Content
     * @return bool Erfolg
     */
    private function update_post_content($post_id, $new_content) {
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content
        ));
        
        return !is_wp_error($result);
    }
    
    /**
     * SEO-Meta-Daten aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $type Meta-Typ
     * @param string $value Neuer Wert
     * @return bool Erfolg
     */
    private function update_seo_meta($post_id, $type, $value) {
        $updated = false;
        
        foreach ($this->seo_plugins as $plugin) {
            if (empty($plugin[$type]) || !is_plugin_active($plugin['file'])) {
                continue;
            }
            
            update_post_meta($post_id, $plugin[$type], $value);
            $updated = true;
        }
        
        return $updated;
    }
    
    /**
     * WPBakery-Text aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $new_text Neuer Text
     * @return bool Erfolg
     */
    private function update_wpbakery_text($post_id, $new_text) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        
        // WPBakery-Text-Elemente ersetzen
        $content = preg_replace_callback(
            '/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s',
            function($matches) use ($new_text) {
                static $counter = 0;
                if ($counter === 0) {
                    $counter++;
                    return str_replace($matches[1], $new_text, $matches[0]);
                }
                return $matches[0];
            },
            $content
        );
        
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));
        
        return !is_wp_error($result);
    }
    
    /**
     * WPBakery-Meta-Daten aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $type Meta-Typ (title/content)
     * @param string $value Neuer Wert
     * @return bool Erfolg
     */
    private function update_wpbakery_meta($post_id, $type, $value) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        
        if ($type === 'title') {
            // Meta-Titel in WPBakery-Elementen ersetzen
            $content = preg_replace(
                '/(\[vc_[^\]]*title=")[^"]*("[^\]]*\])/i',
                '${1}' . esc_attr($value) . '${2}',
                $content
            );
        } elseif ($type === 'content') {
            // Meta-Content in WPBakery-Elementen ersetzen
            $content = preg_replace(
                '/(\[vc_[^\]]*content=")[^"]*("[^\]]*\])/i',
                '${1}' . esc_attr($value) . '${2}',
                $content
            );
        }
        
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content
        ));
        
        return !is_wp_error($result);
    }
    
    /**
     * Alt-Texte aktualisieren
     * 
     * @param int $post_id Post-ID
     * @param string $alt_texts Alt-Texte (getrennt durch |)
     * @return bool Erfolg
     */
    private function update_alt_texts($post_id, $alt_texts) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        // Bilder im Post finden
        $image_ids = $this->get_post_image_ids($post_id);
        $new_alt_texts = array_map('trim', explode('|', $alt_texts));
        
        $updated = 0;
        foreach ($image_ids as $index => $image_id) {
            if (isset($new_alt_texts[$index]) && !empty($new_alt_texts[$index])) {
                update_post_meta($image_id, '_wp_attachment_image_alt', $new_alt_texts[$index]);
                $updated++;
            }
        }
        
        return $updated > 0;
    }
    
    /**
     * SEO-Meta-Daten abrufen
     * 
     * @param int $post_id Post-ID
     * @param string $type Meta-Typ
     * @return string Meta-Wert
     */
    private function get_seo_meta($post_id, $type) {
        foreach ($this->seo_plugins as $plugin) {
            if (empty($plugin[$type]) || !is_plugin_active($plugin['file'])) {
                continue;
            }
            
            $value = get_post_meta($post_id, $plugin[$type], true);
            if (!empty($value)) {
                return $value;
            }
        }
        
        return '';
    }
    
    /**
     * WPBakery-Text extrahieren
     * 
     * @param string $content Post-Content
     * @return string Extrahierter Text
     */
    private function extract_wpbakery_text($content) {
        $text_parts = array();
        
        // vc_column_text Shortcodes finden
        preg_match_all('/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s', $content, $matches);
        
        foreach ($matches[1] as $text) {
            $clean_text = strip_tags($text);
            $clean_text = html_entity_decode($clean_text);
            $clean_text = trim($clean_text);
            
            if (!empty($clean_text)) {
                $text_parts[] = $clean_text;
            }
        }
        
        return implode(' ', $text_parts);
    }
    
    /**
     * WPBakery-Meta extrahieren
     * 
     * @param string $content Post-Content
     * @param string $type Meta-Typ (title/content)
     * @return string Extrahierte Meta-Daten
     */
    private function extract_wpbakery_meta($content, $type) {
        $meta_parts = array();
        
        if ($type === 'title') {
            // Titel aus WPBakery-Shortcodes extrahieren
            preg_match_all('/\[vc_[^\]]*title="([^"]*)"[^\]]*\]/i', $content, $matches);
            $meta_parts = $matches[1];
        } elseif ($type === 'content') {
            // Content aus WPBakery-Shortcodes extrahieren
            preg_match_all('/\[vc_[^\]]*content="([^"]*)"[^\]]*\]/i', $content, $matches);
            $meta_parts = $matches[1];
        }
        
        // Leere Werte entfernen
        $meta_parts = array_filter($meta_parts, function($value) {
            return !empty(trim($value));
        });
        
        return implode(' | ', $meta_parts);
    }
    
    /**
     * Alt-Texte für Post abrufen
     * 
     * @param int $post_id Post-ID
     * @return array Alt-Texte
     */
    private function get_alt_texts($post_id) {
        $image_ids = $this->get_post_image_ids($post_id);
        $alt_texts = array();
        
        foreach ($image_ids as $image_id) {
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            if (!empty($alt_text)) {
                $alt_texts[] = $alt_text;
            }
        }
        
        return $alt_texts;
    }
    
    /**
     * Bild-IDs für Post abrufen
     * 
     * @param int $post_id Post-ID
     * @return array Bild-IDs
     */
    private function get_post_image_ids($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        
        $image_ids = array();
        
        // Featured Image
        $featured_image = get_post_thumbnail_id($post_id);
        if ($featured_image) {
            $image_ids[] = $featured_image;
        }
        
        // Bilder im Content
        preg_match_all('/wp-image-(\d+)/i', $post->post_content, $matches);
        if (!empty($matches[1])) {
            $image_ids = array_merge($image_ids, array_map('intval', $matches[1]));
        }
        
        // Galerie-Bilder
        preg_match_all('/\[gallery[^\]]*ids="([^"]*)"[^\]]*\]/i', $post->post_content, $gallery_matches);
        foreach ($gallery_matches[1] as $gallery_ids) {
            $ids = array_map('intval', explode(',', $gallery_ids));
            $image_ids = array_merge($image_ids, $ids);
        }
        
        return array_unique(array_filter($image_ids));
    }
    
    /**
     * Content bereinigen
     * 
     * @param string $content Roher Content
     * @return string Bereinigter Content
     */
    private function clean_content($content) {
        // Shortcodes entfernen
        $content = strip_shortcodes($content);
        
        // HTML-Tags entfernen
        $content = wp_strip_all_tags($content);
        
        // HTML-Entitäten dekodieren
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Mehrfache Leerzeichen normalisieren
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
    
    /**
     * System-Status abrufen
     * 
     * @return array System-Status
     */
    public function get_system_status() {
        $upload_dir = wp_upload_dir();
        
        $status = array(
            'upload_dir_writable' => is_writable($upload_dir['path']),
            'max_upload_size' => wp_max_upload_size(),
            'memory_limit' => ini_get('memory_limit'),
            'seo_plugins' => array(),
            'page_builders' => array()
        );
        
        // SEO-Plugins erkennen
        foreach ($this->seo_plugins as $plugin) {
            if (is_plugin_active($plugin['file'])) {
                $status['seo_plugins'][] = $plugin['name'];
            }
        }
        
        // Page Builder erkennen
        foreach ($this->page_builders as $builder) {
            if (isset($builder['file']) && is_plugin_active($builder['file'])) {
                $status['page_builders'][] = $builder['name'];
            } elseif (isset($builder['function']) && function_exists($builder['function'])) {
                $status['page_builders'][] = $builder['name'];
            }
        }
        
        return $status;
    }
}

// Globale Instanz bereitstellen
if (!function_exists('retexify_get_export_import_manager')) {
    function retexify_get_export_import_manager() {
        static $instance = null;
        if (null === $instance) {
            $instance = new ReTexify_Export_Import_Manager();
        }
        return $instance;
    }
}