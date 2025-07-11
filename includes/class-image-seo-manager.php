<?php
/**
 * ReTexify AI Pro - Bilder-SEO Manager
 * Neue Klasse für intelligente Bilder-Optimierung
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Image_SEO_Manager {
    
    /**
     * @var ReTexify_AI_Engine AI-Engine Instanz
     */
    private $ai_engine;
    
    /**
     * Konstruktor
     */
    public function __construct($ai_engine = null) {
        $this->ai_engine = $ai_engine;
        
        // AJAX-Handler registrieren
        add_action('wp_ajax_retexify_load_image_seo', array($this, 'ajax_load_image_seo'));
        add_action('wp_ajax_retexify_generate_image_seo', array($this, 'ajax_generate_image_seo'));
        add_action('wp_ajax_retexify_save_image_seo_bulk', array($this, 'ajax_save_image_seo_bulk'));
    }
    
    /**
     * Bilder für einen Post laden (AJAX-Handler)
     */
    public function ajax_load_image_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Ungültige Post-ID');
            return;
        }
        
        try {
            $images = $this->get_post_images($post_id);
            
            wp_send_json_success(array(
                'images' => $images,
                'count' => count($images)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bilder laden fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Alt-Text für Bild generieren (AJAX-Handler)
     */
    public function ajax_generate_image_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $image_id = intval($_POST['image_id'] ?? 0);
        
        if (!$image_id) {
            wp_send_json_error('Ungültige Bild-ID');
            return;
        }
        
        try {
            $generated_seo = $this->generate_image_seo_data($image_id);
            
            wp_send_json_success($generated_seo);
            
        } catch (Exception $e) {
            wp_send_json_error('Bilder-SEO Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk-Speichern von Bilder-SEO Daten (AJAX-Handler)
     */
    public function ajax_save_image_seo_bulk() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $images_data = $_POST['images_data'] ?? array();
        
        if (empty($images_data) || !is_array($images_data)) {
            wp_send_json_error('Keine Bilder-Daten empfangen');
            return;
        }
        
        try {
            $saved_count = $this->save_images_seo_bulk($images_data);
            
            wp_send_json_success(array(
                'saved_count' => $saved_count,
                'message' => "{$saved_count} Bilder erfolgreich gespeichert"
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk-Speichern fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Alle Bilder eines Posts abrufen
     * 
     * @param int $post_id Post-ID
     * @return array Array mit Bild-Informationen
     */
    public function get_post_images($post_id) {
        $images = array();
        
        // Featured Image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id) {
            $image_data = $this->get_image_data($featured_image_id);
            if ($image_data) {
                $image_data['is_featured'] = true;
                $images[] = $image_data;
            }
        }
        
        // Bilder im Post-Content finden
        $post = get_post($post_id);
        if ($post) {
            $content_images = $this->extract_images_from_content($post->post_content);
            $images = array_merge($images, $content_images);
        }
        
        // Gallery-Bilder
        $gallery_images = $this->get_gallery_images($post_id);
        $images = array_merge($images, $gallery_images);
        
        // Duplikate entfernen (basierend auf ID)
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
     * Bild-Daten für eine Attachment-ID abrufen
     * 
     * @param int $attachment_id Attachment-ID
     * @return array|false Bild-Daten oder false
     */
    private function get_image_data($attachment_id) {
        $attachment = get_post($attachment_id);
        
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return false;
        }
        
        $image_url = wp_get_attachment_url($attachment_id);
        $thumbnail_url = wp_get_attachment_thumb_url($attachment_id);
        
        if (!$image_url) {
            return false;
        }
        
        // Alt-Text, Titel und Beschreibung abrufen
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $title = $attachment->post_title;
        $description = $attachment->post_content;
        
        return array(
            'id' => $attachment_id,
            'url' => $image_url,
            'thumbnail' => $thumbnail_url ?: $image_url,
            'alt' => $alt_text,
            'title' => $title,
            'description' => $description,
            'filename' => basename($image_url),
            'is_featured' => false
        );
    }
    
    /**
     * Bilder aus Post-Content extrahieren
     * 
     * @param string $content Post-Content
     * @return array Array mit Bild-Daten
     */
    private function extract_images_from_content($content) {
        $images = array();
        
        // wp-image-{id} Klassen finden
        preg_match_all('/wp-image-(\d+)/', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_id) {
                $image_data = $this->get_image_data(intval($image_id));
                if ($image_data) {
                    $images[] = $image_data;
                }
            }
        }
        
        // Attachment-URLs in img-Tags finden
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $img_matches);
        
        if (!empty($img_matches[1])) {
            foreach ($img_matches[1] as $img_url) {
                $attachment_id = attachment_url_to_postid($img_url);
                if ($attachment_id) {
                    $image_data = $this->get_image_data($attachment_id);
                    if ($image_data) {
                        $images[] = $image_data;
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Gallery-Bilder eines Posts abrufen
     * 
     * @param int $post_id Post-ID
     * @return array Array mit Bild-Daten
     */
    private function get_gallery_images($post_id) {
        $images = array();
        
        // Nach Gallery-Shortcodes suchen
        $post = get_post($post_id);
        if (!$post) {
            return $images;
        }
        
        $pattern = '/\[gallery[^\]]*ids=["\']([^"\']+)["\'][^\]]*\]/';
        preg_match_all($pattern, $post->post_content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $ids_string) {
                $ids = explode(',', $ids_string);
                foreach ($ids as $id) {
                    $id = intval(trim($id));
                    if ($id) {
                        $image_data = $this->get_image_data($id);
                        if ($image_data) {
                            $images[] = $image_data;
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * SEO-Daten für ein Bild mit KI generieren
     * 
     * @param int $image_id Bild-ID
     * @return array Generierte SEO-Daten
     */
    public function generate_image_seo_data($image_id) {
        if (!$this->ai_engine) {
            throw new Exception('AI-Engine nicht verfügbar');
        }
        
        $image_data = $this->get_image_data($image_id);
        if (!$image_data) {
            throw new Exception('Bild nicht gefunden');
        }
        
        // KI-Einstellungen laden
        $settings = get_option('retexify_ai_settings', array());
        
        // Kontext für die KI erstellen
        $filename = $image_data['filename'];
        $current_alt = $image_data['alt'];
        $current_title = $image_data['title'];
        
        $prompt = $this->build_image_seo_prompt($filename, $current_alt, $current_title, $settings);
        
        try {
            $ai_response = $this->ai_engine->call_ai_api($prompt, $settings);
            return $this->parse_image_seo_response($ai_response);
            
        } catch (Exception $e) {
            throw new Exception('KI-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Prompt für Bilder-SEO erstellen
     * 
     * @param string $filename Dateiname
     * @param string $current_alt Aktueller Alt-Text
     * @param string $current_title Aktueller Titel
     * @param array $settings KI-Einstellungen
     * @return string Prompt
     */
    private function build_image_seo_prompt($filename, $current_alt, $current_title, $settings) {
        $business_context = $settings['business_context'] ?? 'Schweizer Unternehmen';
        $target_cantons = $settings['target_cantons'] ?? array();
        
        $canton_text = '';
        if (!empty($target_cantons)) {
            $canton_names = array();
            $swiss_cantons = array(
                'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
                'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
                'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
                'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
                'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
                'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
                'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
            );
            
            foreach ($target_cantons as $code) {
                if (isset($swiss_cantons[$code])) {
                    $canton_names[] = $swiss_cantons[$code];
                }
            }
            
            if (!empty($canton_names)) {
                $canton_text = "\nZiel-Kantone: " . implode(', ', $canton_names);
            }
        }
        
        return "Du bist ein SCHWEIZER SEO-EXPERTE für Bilder-Optimierung. Erstelle optimale Alt-Texte, Titel und Beschreibungen für Bilder.

=== BILD-INFORMATIONEN ===
Dateiname: {$filename}
Aktueller Alt-Text: " . ($current_alt ?: 'Nicht vorhanden') . "
Aktueller Titel: " . ($current_title ?: 'Nicht vorhanden') . "

=== BUSINESS-KONTEXT ===
{$business_context}{$canton_text}

=== AUFGABE ===
Erstelle SEO-optimierte Bild-Informationen:

1. **ALT_TEXT** (50-100 Zeichen):
   - Beschreibt das Bild für Screenreader
   - Enthält relevante Keywords
   - Ist natürlich und lesbar
   - Berücksichtigt Schweizer Kontext

2. **TITEL** (30-60 Zeichen):
   - Kurzer, prägnanter Titel
   - SEO-optimiert
   - Benutzerfreundlich

3. **BESCHREIBUNG** (100-150 Zeichen):
   - Detailliertere Beschreibung
   - Kontext und Verwendungszweck
   - Lokaler Bezug

=== AUSGABEFORMAT (EXAKT SO) ===
ALT_TEXT: [Alt-Text hier]
TITEL: [Titel hier]
BESCHREIBUNG: [Beschreibung hier]

Erstelle jetzt die optimierten Bild-SEO-Daten:";
    }
    
    /**
     * KI-Response für Bilder-SEO parsen
     * 
     * @param string $response KI-Response
     * @return array Geparste SEO-Daten
     */
    private function parse_image_seo_response($response) {
        $result = array(
            'alt_text' => '',
            'title' => '',
            'description' => ''
        );
        
        // Alt-Text extrahieren
        if (preg_match('/ALT_TEXT:\s*(.+?)(?:\n|$)/i', $response, $matches)) {
            $result['alt_text'] = trim($matches[1]);
        }
        
        // Titel extrahieren
        if (preg_match('/TITEL:\s*(.+?)(?:\n|$)/i', $response, $matches)) {
            $result['title'] = trim($matches[1]);
        }
        
        // Beschreibung extrahieren
        if (preg_match('/BESCHREIBUNG:\s*(.+?)(?:\n|$)/i', $response, $matches)) {
            $result['description'] = trim($matches[1]);
        }
        
        return $result;
    }
    
    /**
     * Bulk-Speichern von Bilder-SEO Daten
     * 
     * @param array $images_data Array mit Bild-Daten
     * @return int Anzahl gespeicherter Bilder
     */
    public function save_images_seo_bulk($images_data) {
        $saved_count = 0;
        
        foreach ($images_data as $image_data) {
            $image_id = intval($image_data['id'] ?? 0);
            
            if (!$image_id) {
                continue;
            }
            
            $success = true;
            
            // Alt-Text speichern
            if (!empty($image_data['alt'])) {
                $success = $success && update_post_meta($image_id, '_wp_attachment_image_alt', sanitize_text_field($image_data['alt']));
            }
            
            // Titel speichern
            if (!empty($image_data['title'])) {
                $success = $success && wp_update_post(array(
                    'ID' => $image_id,
                    'post_title' => sanitize_text_field($image_data['title'])
                ));
            }
            
            // Beschreibung speichern
            if (!empty($image_data['description'])) {
                $success = $success && wp_update_post(array(
                    'ID' => $image_id,
                    'post_content' => sanitize_textarea_field($image_data['description'])
                ));
            }
            
            if ($success) {
                $saved_count++;
            }
        }
        
        return $saved_count;
    }
    
    /**
     * Statistiken für Bilder-SEO abrufen
     * 
     * @return array Statistiken
     */
    public function get_image_seo_stats() {
        global $wpdb;
        
        // Gesamt-Anzahl Bilder
        $total_images = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );
        
        // Bilder mit Alt-Text
        $images_with_alt = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'attachment' 
             AND p.post_mime_type LIKE 'image/%' 
             AND pm.meta_key = '_wp_attachment_image_alt' 
             AND pm.meta_value != ''"
        );
        
        // Bilder ohne Alt-Text
        $images_without_alt = $total_images - $images_with_alt;
        
        // Prozentsatz
        $alt_percentage = $total_images > 0 ? round(($images_with_alt / $total_images) * 100) : 0;
        
        return array(
            'total_images' => intval($total_images),
            'images_with_alt' => intval($images_with_alt),
            'images_without_alt' => intval($images_without_alt),
            'alt_percentage' => $alt_percentage
        );
    }
} 