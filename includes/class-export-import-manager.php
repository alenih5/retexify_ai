<?php
/**
 * ReTexify Export/Import Manager - VERBESSERTE VERSION
 * 
 * Verwaltet CSV-Export und -Import für SEO-Daten
 * FIXES: Vollständiger Alt-Text Export und WPBakery-Content-Extraktion
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
     * NEUE FUNKTION: Komplette Mediathek exportieren
     * 
     * @return array CSV-Daten für alle Medien
     */
    private function export_complete_media_library() {
        global $wpdb;
        
        // Alle Bilder aus der Mediathek abrufen
        $images = $wpdb->get_results("
            SELECT ID, post_title, post_name, post_date, post_modified, post_parent, guid
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            ORDER BY post_date DESC
        ");
        
        $csv_data = array();
        
        // Headers für Mediathek-Export
        $headers = array(
            'Bild-ID',
            'Dateiname',
            'Titel',
            'Alt-Text (Original)',
            'Alt-Text (Neu)',
            'Bildgröße',
            'Abmessungen',
            'Dateigröße (KB)',
            'Verwendet in Posts',
            'Featured Image von',
            'Upload-Datum',
            'Letzte Änderung',
            'Datei-URL',
            'Bearbeiten-URL'
        );
        $csv_data[] = $headers;
        
        foreach ($images as $image) {
            $image_id = $image->ID;
            
            // Alt-Text abrufen
            $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            
            // Bildmetadaten abrufen
            $metadata = wp_get_attachment_metadata($image_id);
            $file_size = '';
            $dimensions = '';
            
            if ($metadata) {
                if (isset($metadata['filesize'])) {
                    $file_size = round($metadata['filesize'] / 1024, 2); // KB
                } else {
                    $file_path = get_attached_file($image_id);
                    if ($file_path && file_exists($file_path)) {
                        $file_size = round(filesize($file_path) / 1024, 2);
                    }
                }
                
                if (isset($metadata['width']) && isset($metadata['height'])) {
                    $dimensions = $metadata['width'] . ' x ' . $metadata['height'];
                }
            }
            
            // Verwendung in Posts finden
            $used_in_posts = $this->find_image_usage($image_id);
            $used_in_posts_text = !empty($used_in_posts) ? implode(', ', $used_in_posts) : 'Nicht verwendet';
            
            // Featured Image Verwendung
            $featured_in = $wpdb->get_col($wpdb->prepare("
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_thumbnail_id' AND meta_value = %d
            ", $image_id));
            $featured_text = !empty($featured_in) ? 'Post-ID: ' . implode(', ', $featured_in) : 'Nein';
            
            // Dateigröße von WordPress abrufen falls nicht in Metadaten
            if (empty($file_size)) {
                $file_size = 'Unbekannt';
            } else {
                $file_size = $file_size . ' KB';
            }
            
            $row = array(
                $image_id,
                basename($image->guid),
                $image->post_title ?: 'Ohne Titel',
                $alt_text ?: '',
                '', // Leer für "Alt-Text (Neu)"
                wp_get_attachment_image_src($image_id, 'medium')[0] ? 'Medium verfügbar' : 'Nur Original',
                $dimensions ?: 'Unbekannt',
                $file_size,
                $used_in_posts_text,
                $featured_text,
                date('d.m.Y H:i', strtotime($image->post_date)),
                date('d.m.Y H:i', strtotime($image->post_modified)),
                wp_get_attachment_url($image_id),
                admin_url('post.php?post=' . $image_id . '&action=edit')
            );
            
            $csv_data[] = $row;
        }
        
        return $csv_data;
    }
    
    /**
     * Bildverwendung in Posts finden
     * 
     * @param int $image_id Bild-ID
     * @return array Post-IDs wo das Bild verwendet wird
     */
    private function find_image_usage($image_id) {
        global $wpdb;
        
        $used_in = array();
        
        // 1. Als angehängtes Bild
        if (get_post($image_id)->post_parent) {
            $used_in[] = 'Post-ID: ' . get_post($image_id)->post_parent;
        }
        
        // 2. Im Post-Content erwähnt
        $image_url = wp_get_attachment_url($image_id);
        $filename = basename($image_url);
        
        $posts_with_image = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title FROM {$wpdb->posts} 
            WHERE post_content LIKE %s 
            AND post_type IN ('post', 'page') 
            AND post_status = 'publish'
        ", '%' . $filename . '%'));
        
        foreach ($posts_with_image as $post) {
            $used_in[] = $post->post_title . ' (ID: ' . $post->ID . ')';
        }
        
        // 3. In WPBakery Shortcodes
        $wpbakery_posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title FROM {$wpdb->posts} 
            WHERE post_content LIKE %s 
            AND post_type IN ('post', 'page') 
            AND post_status = 'publish'
        ", '%image="' . $image_id . '"%'));
        
        foreach ($wpbakery_posts as $post) {
            $used_in[] = $post->post_title . ' (WPBakery - ID: ' . $post->ID . ')';
        }
        
        return array_unique($used_in);
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
     * @param bool $export_all_media Alle Medien exportieren (nicht nur verwendete)
     * @return array Export-Ergebnis
     */
    public function export_to_csv($post_types = array('post', 'page'), $status_types = array('publish'), $content_types = array(), $export_all_media = false) {
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
            
            // Wenn Alt-Texte exportiert werden sollen, prüfen ob alle Medien gewünscht sind
            if (in_array('alt_texts', $content_types) && $export_all_media) {
                // Komplette Mediathek exportieren
                $csv_data = $this->export_complete_media_library();
            } else {
                // Standard Export: Posts/Seiten mit ihren Medien
                $headers = $this->get_csv_headers($content_types);
                $csv_data[] = $headers;
                
                foreach ($posts as $post) {
                    $rows = $this->build_csv_row($post, $content_types);
                    foreach ($rows as $row) {
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
            'title' => array('Titel (Original)', 'Titel (Neu)'),
            'meta_title' => array('Meta-Titel (Original)', 'Meta-Titel (Neu)'),
            'meta_description' => array('Meta-Beschreibung (Original)', 'Meta-Beschreibung (Neu)'),
            'focus_keyword' => array('Focus-Keyword (Original)', 'Focus-Keyword (Neu)'),
            'post_content' => array('Vollständiger Inhalt (Original)', 'Vollständiger Inhalt (Neu)'),
            'wpbakery_text' => array('WPBakery Text-Module (Original)', 'WPBakery Text-Module (Neu)', 'WPBakery Titel-Module', 'WPBakery Button-Texte'),
            'alt_texts' => array('Alle Bild-IDs', 'Alle Bild-Dateinamen', 'Alle Alt-Texte (Original)', 'Alle Alt-Texte (Neu)', 'Bild-Quellen')
        );
        
        foreach ($content_types as $type) {
            if (isset($header_mapping[$type])) {
                $headers = array_merge($headers, $header_mapping[$type]);
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
        $row = array($post->ID, $post->post_type, $post->post_status);

        foreach ($content_types as $type) {
            switch ($type) {
                case 'title':
                    $row[] = get_the_title($post->ID);
                    $row[] = ''; // Leer für 'Neu'
                    break;
                case 'meta_title':
                    $row[] = $this->get_meta_title($post->ID);
                    $row[] = ''; // Leer für 'Neu'
                    break;
                case 'meta_description':
                    $row[] = $this->get_meta_description($post->ID);
                    $row[] = ''; // Leer für 'Neu'
                    break;
                case 'focus_keyword':
                    $row[] = $this->get_focus_keyword($post->ID);
                    $row[] = ''; // Leer für 'Neu'
                    break;
                case 'post_content':
                    $row[] = get_post_field('post_content', $post->ID);
                    $row[] = ''; // Leer für 'Neu'
                    break;
                case 'wpbakery_text':
                    $wpbakery_data = $this->get_complete_wpbakery_text($post->ID);
                    $row[] = $wpbakery_data['text_modules'];
                    $row[] = ''; // Leer für 'WPBakery Text-Module (Neu)'
                    $row[] = $wpbakery_data['title_modules'];
                    $row[] = $wpbakery_data['button_texts'];
                    break;
                case 'alt_texts':
                    $image_data = $this->get_complete_images_data($post->ID);
                    if (!empty($image_data)) {
                        $row[] = implode(' | ', array_column($image_data, 'id'));
                        $row[] = implode(' | ', array_column($image_data, 'filename'));
                        $row[] = implode(' | ', array_column($image_data, 'alt'));
                        $row[] = ''; // Leer für 'Alt-Texte (Neu)'
                        $row[] = implode(' | ', array_column($image_data, 'source'));
                    } else {
                        // Leere Zellen, wenn keine Bilder gefunden wurden
                        $row = array_merge($row, array('', '', '', '', ''));
                    }
                    break;
            }
        }
        
        $row[] = get_permalink($post->ID);
        $row[] = admin_url('post.php?post=' . $post->ID . '&action=edit');
        $row[] = $post->post_date;
        $row[] = $post->post_modified;
        
        return array($row); // Gibt ein Array von Zeilen zurück (hier nur eine)
    }
    
    /**
     * VERBESSERT: Alle WPBakery-Text-Inhalte extrahieren
     * 
     * @param int $post_id Post-ID
     * @return array WPBakery-Daten
     */
    private function get_complete_wpbakery_text($post_id) {
        $post_content = get_post_field('post_content', $post_id);
        $result = array(
            'text_modules' => '',
            'title_modules' => '',
            'button_texts' => ''
        );
        
        if (strpos($post_content, '[vc_') === false) {
            return $result;
        }
        
        // Text-Module extrahieren
        $text_modules = array();
        
        // vc_column_text Shortcodes
        preg_match_all('/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s', $post_content, $text_matches);
        if (!empty($text_matches[1])) {
            foreach ($text_matches[1] as $text) {
                $clean_text = wp_strip_all_tags($text);
                $clean_text = html_entity_decode($clean_text);
                $clean_text = trim($clean_text);
                if (!empty($clean_text)) {
                    $text_modules[] = $clean_text;
                }
            }
        }
        
        // vc_custom_heading und andere Titel-Module
        $title_modules = array();
        
        // Custom Headings
        preg_match_all('/\[vc_custom_heading[^\]]*text="([^"]*)"[^\]]*\]/s', $post_content, $heading_matches);
        if (!empty($heading_matches[1])) {
            foreach ($heading_matches[1] as $heading) {
                $clean_heading = html_entity_decode($heading);
                $clean_heading = strip_tags($clean_heading);
                $clean_heading = trim($clean_heading);
                if (!empty($clean_heading)) {
                    $title_modules[] = $clean_heading;
                }
            }
        }
        
        // vc_row_inner und andere verschachtelte Inhalte
        preg_match_all('/\[vc_row_inner[^\]]*\](.*?)\[\/vc_row_inner\]/s', $post_content, $inner_matches);
        if (!empty($inner_matches[1])) {
            foreach ($inner_matches[1] as $inner_content) {
                preg_match_all('/\[vc_column_text[^\]]*\](.*?)\[\/vc_column_text\]/s', $inner_content, $inner_text_matches);
                if (!empty($inner_text_matches[1])) {
                    foreach ($inner_text_matches[1] as $text) {
                        $clean_text = wp_strip_all_tags($text);
                        $clean_text = html_entity_decode($clean_text);
                        $clean_text = trim($clean_text);
                        if (!empty($clean_text)) {
                            $text_modules[] = $clean_text;
                        }
                    }
                }
            }
        }
        
        // Button-Texte extrahieren
        $button_texts = array();
        
        // vc_btn Shortcodes
        preg_match_all('/\[vc_btn[^\]]*title="([^"]*)"[^\]]*\]/s', $post_content, $btn_matches);
        if (!empty($btn_matches[1])) {
            foreach ($btn_matches[1] as $btn_text) {
                $clean_btn = html_entity_decode($btn_text);
                $clean_btn = strip_tags($clean_btn);
                $clean_btn = trim($clean_btn);
                if (!empty($clean_btn)) {
                    $button_texts[] = $clean_btn;
                }
            }
        }
        
        // CTA Module
        preg_match_all('/\[vc_cta[^\]]*h2="([^"]*)"[^\]]*\]/s', $post_content, $cta_matches);
        if (!empty($cta_matches[1])) {
            foreach ($cta_matches[1] as $cta_text) {
                $clean_cta = html_entity_decode($cta_text);
                $clean_cta = strip_tags($clean_cta);
                $clean_cta = trim($clean_cta);
                if (!empty($clean_cta)) {
                    $title_modules[] = $clean_cta;
                }
            }
        }
        
        $result['text_modules'] = implode(' | ', array_unique($text_modules));
        $result['title_modules'] = implode(' | ', array_unique($title_modules));
        $result['button_texts'] = implode(' | ', array_unique($button_texts));
        
        return $result;
    }
    
    /**
     * VERBESSERT: Alle Bilder und Alt-Texte erfassen
     * 
     * @param int $post_id Post-ID
     * @return array Vollständige Bild-Daten
     */
    private function get_complete_images_data($post_id) {
        $images = array();
        $image_ids = array();
        
        // 1. Alle direkt angehängten Bilder
        $attached_images = get_posts(array(
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
        ));
        
        foreach ($attached_images as $img) {
            $image_ids[$img->ID] = 'attached';
        }
        
        // 2. Beitragsbild (Featured Image)
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_ids[$thumbnail_id] = 'featured';
        }
        
        // 3. Bilder im Post-Content suchen
        $post_content = get_post_field('post_content', $post_id);
        
        // img-Tags im Content
        preg_match_all('/<img[^>]+src="([^"]+)"[^>]*>/i', $post_content, $img_matches);
        if (!empty($img_matches[1])) {
            foreach ($img_matches[1] as $img_url) {
                $attachment_id = attachment_url_to_postid($img_url);
                if ($attachment_id) {
                    $image_ids[$attachment_id] = 'content_img';
                }
            }
        }
        
        // wp:image Gutenberg Blöcke
        preg_match_all('/<!-- wp:image {"id":(\d+)/', $post_content, $gutenberg_matches);
        if (!empty($gutenberg_matches[1])) {
            foreach ($gutenberg_matches[1] as $img_id) {
                $image_ids[intval($img_id)] = 'gutenberg';
            }
        }
        
        // WPBakery vc_single_image Shortcodes
        preg_match_all('/\[vc_single_image[^\]]*image="(\d+)"[^\]]*\]/s', $post_content, $vc_img_matches);
        if (!empty($vc_img_matches[1])) {
            foreach ($vc_img_matches[1] as $img_id) {
                $image_ids[intval($img_id)] = 'wpbakery';
            }
        }
        
        // WPBakery vc_gallery Shortcodes
        preg_match_all('/\[vc_gallery[^\]]*images="([^"]+)"[^\]]*\]/s', $post_content, $vc_gallery_matches);
        if (!empty($vc_gallery_matches[1])) {
            foreach ($vc_gallery_matches[1] as $gallery_ids) {
                $ids = explode(',', $gallery_ids);
                foreach ($ids as $img_id) {
                    $img_id = intval(trim($img_id));
                    if ($img_id > 0) {
                        $image_ids[$img_id] = 'wpbakery_gallery';
                    }
                }
            }
        }
        
        // WPBakery vc_row mit background images
        preg_match_all('/\[vc_row[^\]]*bg_image="(\d+)"[^\]]*\]/s', $post_content, $bg_matches);
        if (!empty($bg_matches[1])) {
            foreach ($bg_matches[1] as $img_id) {
                $image_ids[intval($img_id)] = 'wpbakery_bg';
            }
        }
        
        // WordPress-eigene Gallery Shortcodes
        preg_match_all('/\[gallery[^\]]*ids="([^"]+)"[^\]]*\]/s', $post_content, $wp_gallery_matches);
        if (!empty($wp_gallery_matches[1])) {
            foreach ($wp_gallery_matches[1] as $gallery_ids) {
                $ids = explode(',', $gallery_ids);
                foreach ($ids as $img_id) {
                    $img_id = intval(trim($img_id));
                    if ($img_id > 0) {
                        $image_ids[$img_id] = 'wp_gallery';
                    }
                }
            }
        }
        
        // 4. Alle gefundenen Bilder verarbeiten
        foreach ($image_ids as $image_id => $source) {
            if ($image_id > 0 && get_post($image_id)) {
                $images[] = array(
                    'id' => $image_id,
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: '',
                    'filename' => basename(wp_get_attachment_url($image_id)) ?: '',
                    'source' => $source
                );
            }
        }
        
        // Duplikate entfernen basierend auf ID
        $unique_images = array();
        $seen_ids = array();
        
        foreach ($images as $image) {
            if (!in_array($image['id'], $seen_ids)) {
                $unique_images[] = $image;
                $seen_ids[] = $image['id'];
            }
        }
        
        return $unique_images;
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
     * VERBESSERTE Export-Statistiken abrufen
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
        $stats['content'] = $total_posts;

        // Meta-Daten für alle SEO-Plugins zählen
        $stats['meta_title'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('_yoast_wpseo_title', 'rank_math_title', '_aioseop_title', '_seopress_titles_title') AND meta_value != ''");
        $stats['meta_description'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('_yoast_wpseo_metadesc', 'rank_math_description', '_aioseop_description', '_seopress_titles_desc') AND meta_value != ''");
        $stats['focus_keyword'] = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ('_yoast_wpseo_focuskw', 'rank_math_focus_keyword') AND meta_value != ''");
        
        // VERBESSERTE WPBakery-Statistiken
        $stats['wpbakery'] = $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_content LIKE '%[vc_%' AND post_status IN ('publish', 'draft')");
        
        // KOMPLETTE MEDIATHEK-Statistiken
        $stats['alt_texts'] = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        ");
        
        // Zusätzliche Mediathek-Statistiken
        $stats['alt_texts_with_alt'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'attachment' 
            AND p.post_mime_type LIKE 'image/%'
            AND pm.meta_key = '_wp_attachment_image_alt'
            AND pm.meta_value != ''
        ");
        
        $stats['alt_texts_without_alt'] = $stats['alt_texts'] - $stats['alt_texts_with_alt'];
        
        // Verwendete vs. unbenutzte Bilder
        $stats['images_used'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT a.ID) 
            FROM {$wpdb->posts} a
            WHERE a.post_type = 'attachment' 
            AND a.post_mime_type LIKE 'image/%'
            AND (
                a.post_parent > 0 
                OR a.ID IN (SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id')
            )
        ");
        
        $stats['images_unused'] = $stats['alt_texts'] - $stats['images_used'];

        return $stats;
    }
    
    /**
     * Mediathek-spezifische Statistiken abrufen
     * 
     * @return array Detaillierte Mediathek-Statistiken
     */
    public function get_media_library_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Gesamt-Statistiken
        $stats['total_images'] = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'
        ");
        
        $stats['with_alt_text'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%'
            AND pm.meta_key = '_wp_attachment_image_alt' AND pm.meta_value != ''
        ");
        
        $stats['without_alt_text'] = $stats['total_images'] - $stats['with_alt_text'];
        
        $stats['used_in_posts'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT ID) FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' 
            AND post_parent > 0
        ");
        
        $stats['featured_images'] = $wpdb->get_var("
            SELECT COUNT(DISTINCT meta_value) FROM {$wpdb->postmeta} 
            WHERE meta_key = '_thumbnail_id'
        ");
        
        $stats['unused_images'] = $stats['total_images'] - $stats['used_in_posts'] - $stats['featured_images'];
        
        // Dateigröße-Statistiken
        $large_images = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'attachment' AND p.post_mime_type LIKE 'image/%'
            AND pm.meta_key = '_wp_attachment_metadata'
            AND pm.meta_value LIKE '%s:5:\"width\";i:%'
            AND CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(pm.meta_value, 'width\";i:', -1), ';', 1) AS UNSIGNED) > 2000
        ");
        
        $stats['large_images'] = $large_images ?: 0;
        
        return $stats;
    }
}
}