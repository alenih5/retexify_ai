<?php
/**
 * ReTexify AI Pro - Erweiterte Admin-Interface Ergänzungen
 * Integration aller neuen Features und Bugfixes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Erweiterte AJAX-Handler für neue Funktionen
 * Diese Datei sollte in die Hauptdatei retexify.php integriert werden
 */

class ReTexify_Enhanced_Handlers {
    
    private $image_seo_manager;
    private $direct_text_generator;
    
    public function __construct($ai_engine) {
        // Neue Manager initialisieren
        if (file_exists(plugin_dir_path(__FILE__) . 'class-image-seo-manager.php')) {
            require_once plugin_dir_path(__FILE__) . 'class-image-seo-manager.php';
            $this->image_seo_manager = new ReTexify_Image_SEO_Manager($ai_engine);
        }
        
        if (file_exists(plugin_dir_path(__FILE__) . 'class-direct-text-generator.php')) {
            require_once plugin_dir_path(__FILE__) . 'class-direct-text-generator.php';
            $this->direct_text_generator = new ReTexify_Direct_Text_Generator($ai_engine);
        }
        
        // Neue AJAX-Handler registrieren
        $this->register_enhanced_ajax_handlers();
    }
    
    /**
     * Erweiterte AJAX-Handler registrieren
     */
    private function register_enhanced_ajax_handlers() {
        // Content-Analyse vor SEO-Generierung
        add_action('wp_ajax_retexify_analyze_content', array($this, 'ajax_analyze_content'));
        
        // Verbesserte Einstellungen-Speicherung (Kantone-Bugfix)
        add_action('wp_ajax_retexify_save_settings_enhanced', array($this, 'ajax_save_settings_enhanced'));
        
        // Beiträge/Seiten/Bilder Übersicht
        add_action('wp_ajax_retexify_get_content_overview', array($this, 'ajax_get_content_overview'));
        
        // Bulk-SEO-Generierung
        add_action('wp_ajax_retexify_bulk_generate_seo', array($this, 'ajax_bulk_generate_seo'));
    }
    
    /**
     * NEUER HANDLER: Content-Analyse vor SEO-Generierung
     */
    public function ajax_analyze_content() {
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
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception('Post nicht gefunden');
            }
            
            // Content-Analyse durchführen
            $analysis = $this->perform_content_analysis($post);
            
            wp_send_json_success($analysis);
            
        } catch (Exception $e) {
            wp_send_json_error('Content-Analyse fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * BUGFIX: Erweiterte Einstellungen-Speicherung mit korrekter Kantone-Behandlung
     */
    public function ajax_save_settings_enhanced() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        try {
            $settings = array();
            
            // Standard-Felder
            $settings['api_provider'] = sanitize_text_field($_POST['api_provider'] ?? 'openai');
            $settings['api_key'] = sanitize_text_field($_POST['api_key'] ?? '');
            $settings['model'] = sanitize_text_field($_POST['model'] ?? '');
            $settings['optimization_focus'] = sanitize_text_field($_POST['optimization_focus'] ?? 'complete_seo');
            $settings['business_context'] = sanitize_textarea_field($_POST['business_context'] ?? '');
            $settings['target_audience'] = sanitize_text_field($_POST['target_audience'] ?? '');
            $settings['brand_voice'] = sanitize_text_field($_POST['brand_voice'] ?? 'professional');
            
            // BUGFIX: Kantone korrekt behandeln
            $target_cantons = $_POST['target_cantons'] ?? array();
            
            if (is_string($target_cantons)) {
                // Falls als String übertragen, in Array umwandeln
                $target_cantons = array($target_cantons);
            }
            
            if (is_array($target_cantons)) {
                $settings['target_cantons'] = array_map('sanitize_text_field', $target_cantons);
            } else {
                $settings['target_cantons'] = array();
            }
            
            // Einstellungen speichern
            $success = update_option('retexify_ai_settings', $settings);
            
            if ($success) {
                wp_send_json_success(array(
                    'message' => 'Einstellungen erfolgreich gespeichert',
                    'cantons_count' => count($settings['target_cantons']),
                    'saved_settings' => $settings
                ));
            } else {
                wp_send_json_error('Einstellungen konnten nicht gespeichert werden');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Speichern: ' . $e->getMessage());
        }
    }
    
    /**
     * NEUE FUNKTION: Content-Übersicht (Beiträge, Seiten, Bilder)
     */
    public function ajax_get_content_overview() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $content_type = sanitize_text_field($_POST['content_type'] ?? 'all');
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        
        try {
            $overview = $this->get_content_overview($content_type, $page, $per_page);
            wp_send_json_success($overview);
            
        } catch (Exception $e) {
            wp_send_json_error('Content-Übersicht fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * NEUE FUNKTION: Bulk-SEO-Generierung für mehrere Posts
     */
    public function ajax_bulk_generate_seo() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $post_ids = $_POST['post_ids'] ?? array();
        $seo_elements = $_POST['seo_elements'] ?? array('meta_title', 'meta_description', 'focus_keyword');
        
        if (empty($post_ids) || !is_array($post_ids)) {
            wp_send_json_error('Keine Posts ausgewählt');
            return;
        }
        
        try {
            $results = $this->bulk_generate_seo($post_ids, $seo_elements);
            wp_send_json_success($results);
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Content-Analyse durchführen
     */
    private function perform_content_analysis($post) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        // Basis-Analyse
        $word_count = str_word_count($content);
        $char_count = strlen($content);
        $paragraph_count = substr_count($content, "\n\n") + 1;
        
        // Keyword-Dichte analysieren
        $keywords = $this->extract_keywords($content, $title);
        
        // Readability-Score (vereinfacht)
        $readability_score = $this->calculate_readability_score($content);
        
        // SEO-Potenzial bewerten
        $seo_potential = $this->assess_seo_potential($post, $content, $title);
        
        return array(
            'post_id' => $post->ID,
            'title' => $title,
            'word_count' => $word_count,
            'char_count' => $char_count,
            'paragraph_count' => $paragraph_count,
            'keywords' => $keywords,
            'readability_score' => $readability_score,
            'seo_potential' => $seo_potential,
            'analysis_timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Keywords aus Content extrahieren
     */
    private function extract_keywords($content, $title) {
        // Einfache Keyword-Extraktion
        $text = strtolower($content . ' ' . $title);
        
        // Deutsche Stopwords
        $stopwords = array(
            'der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines',
            'und', 'oder', 'aber', 'wenn', 'dann', 'also', 'noch', 'wie', 'was', 'wo',
            'wer', 'warum', 'wann', 'hier', 'da', 'dort', 'sich', 'sie', 'er', 'es',
            'ich', 'du', 'wir', 'ihr', 'mich', 'dich', 'uns', 'euch', 'ihm', 'ihr',
            'ist', 'sind', 'war', 'waren', 'hat', 'haben', 'wird', 'werden', 'kann',
            'könnte', 'soll', 'sollte', 'muss', 'müssen', 'darf', 'dürfen', 'mag',
            'für', 'von', 'zu', 'mit', 'nach', 'bei', 'über', 'unter', 'vor', 'hinter'
        );
        
        // Text in Wörter aufteilen
        $words = preg_split('/\s+/', $text);
        $word_frequency = array();
        
        foreach ($words as $word) {
            $word = trim(preg_replace('/[^\w\säöüß]/u', '', $word));
            
            if (strlen($word) >= 3 && !in_array($word, $stopwords)) {
                $word_frequency[$word] = ($word_frequency[$word] ?? 0) + 1;
            }
        }
        
        // Nach Häufigkeit sortieren
        arsort($word_frequency);
        
        return array_slice($word_frequency, 0, 10, true);
    }
    
    /**
     * Vereinfachter Readability-Score
     */
    private function calculate_readability_score($content) {
        $sentences = preg_split('/[.!?]+/', $content);
        $sentence_count = count(array_filter($sentences));
        $word_count = str_word_count($content);
        
        if ($sentence_count == 0) return 0;
        
        $avg_words_per_sentence = $word_count / $sentence_count;
        
        // Einfacher Score basierend auf Satzlänge
        if ($avg_words_per_sentence <= 15) {
            return 90; // Sehr gut lesbar
        } elseif ($avg_words_per_sentence <= 20) {
            return 75; // Gut lesbar
        } elseif ($avg_words_per_sentence <= 25) {
            return 60; // Durchschnittlich
        } else {
            return 40; // Schwer lesbar
        }
    }
    
    /**
     * SEO-Potenzial bewerten
     */
    private function assess_seo_potential($post, $content, $title) {
        $score = 0;
        $max_score = 100;
        $recommendations = array();
        
        // Titel-Länge prüfen
        $title_length = strlen($title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 20;
        } else {
            $recommendations[] = 'Titel-Länge optimieren (30-60 Zeichen)';
        }
        
        // Content-Länge prüfen
        $word_count = str_word_count($content);
        if ($word_count >= 300) {
            $score += 20;
        } else {
            $recommendations[] = 'Content verlängern (mind. 300 Wörter)';
        }
        
        // Meta-Beschreibung prüfen
        $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true) ?: 
                     get_post_meta($post->ID, '_aioseop_description', true) ?: 
                     get_post_meta($post->ID, 'meta_description', true);
        
        if (!empty($meta_desc)) {
            $score += 20;
        } else {
            $recommendations[] = 'Meta-Beschreibung hinzufügen';
        }
        
        // Focus-Keyword prüfen
        $focus_keyword = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true) ?: 
                         get_post_meta($post->ID, '_aioseop_keywords', true) ?: 
                         get_post_meta($post->ID, 'focus_keyword', true);
        
        if (!empty($focus_keyword)) {
            $score += 20;
        } else {
            $recommendations[] = 'Focus-Keyword definieren';
        }
        
        // Alt-Texte für Bilder prüfen
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_alt = get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true);
            if (!empty($thumbnail_alt)) {
                $score += 20;
            } else {
                $recommendations[] = 'Alt-Text für Featured Image hinzufügen';
            }
        }
        
        return array(
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => round(($score / $max_score) * 100),
            'recommendations' => $recommendations
        );
    }
    
    /**
     * Content-Übersicht erstellen
     */
    private function get_content_overview($content_type, $page, $per_page) {
        $args = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'publish'
        );
        
        switch ($content_type) {
            case 'posts':
                $args['post_type'] = 'post';
                break;
            case 'pages':
                $args['post_type'] = 'page';
                break;
            case 'images':
                return $this->get_images_overview($page, $per_page);
            default:
                $args['post_type'] = array('post', 'page');
        }
        
        $query = new WP_Query($args);
        $items = array();
        
        foreach ($query->posts as $post) {
            $seo_status = $this->get_post_seo_status($post->ID);
            
            $items[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'type' => $post->post_type,
                'status' => $post->post_status,
                'date' => $post->post_date,
                'word_count' => str_word_count(wp_strip_all_tags($post->post_content)),
                'seo_status' => $seo_status,
                'edit_url' => get_edit_post_link($post->ID)
            );
        }
        
        return array(
            'items' => $items,
            'total' => $query->found_posts,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page)
        );
    }
    
    /**
     * Bilder-Übersicht erstellen
     */
    private function get_images_overview($page, $per_page) {
        if (!$this->image_seo_manager) {
            throw new Exception('Bilder-SEO Manager nicht verfügbar');
        }
        
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => 'inherit'
        );
        
        $query = new WP_Query($args);
        $items = array();
        
        foreach ($query->posts as $attachment) {
            $alt_text = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
            $thumbnail_url = wp_get_attachment_thumb_url($attachment->ID);
            
            $items[] = array(
                'id' => $attachment->ID,
                'title' => $attachment->post_title,
                'filename' => basename(get_attached_file($attachment->ID)),
                'alt_text' => $alt_text,
                'has_alt' => !empty($alt_text),
                'thumbnail' => $thumbnail_url,
                'upload_date' => $attachment->post_date
            );
        }
        
        return array(
            'items' => $items,
            'total' => $query->found_posts,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($query->found_posts / $per_page)
        );
    }
    
    /**
     * SEO-Status eines Posts abrufen
     */
    private function get_post_seo_status($post_id) {
        $status = array(
            'has_meta_title' => false,
            'has_meta_description' => false,
            'has_focus_keyword' => false,
            'completion_percentage' => 0
        );
        
        // Meta-Titel prüfen
        $meta_title = get_post_meta($post_id, '_yoast_wpseo_title', true) ?: 
                      get_post_meta($post_id, '_aioseop_title', true) ?: 
                      get_post_meta($post_id, 'meta_title', true);
        
        if (!empty($meta_title)) {
            $status['has_meta_title'] = true;
        }
        
        // Meta-Beschreibung prüfen
        $meta_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true) ?: 
                     get_post_meta($post_id, '_aioseop_description', true) ?: 
                     get_post_meta($post_id, 'meta_description', true);
        
        if (!empty($meta_desc)) {
            $status['has_meta_description'] = true;
        }
        
        // Focus-Keyword prüfen
        $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true) ?: 
                         get_post_meta($post_id, '_aioseop_keywords', true) ?: 
                         get_post_meta($post_id, 'focus_keyword', true);
        
        if (!empty($focus_keyword)) {
            $status['has_focus_keyword'] = true;
        }
        
        // Vervollständigung berechnen
        $completed = array_sum(array(
            $status['has_meta_title'],
            $status['has_meta_description'],
            $status['has_focus_keyword']
        ));
        
        $status['completion_percentage'] = round(($completed / 3) * 100);
        
        return $status;
    }
    
    /**
     * Bulk-SEO-Generierung durchführen
     */
    private function bulk_generate_seo($post_ids, $seo_elements) {
        $results = array(
            'success_count' => 0,
            'error_count' => 0,
            'processed_posts' => array(),
            'errors' => array()
        );
        
        foreach ($post_ids as $post_id) {
            $post_id = intval($post_id);
            
            try {
                $post_results = $this->generate_seo_for_post($post_id, $seo_elements);
                
                $results['processed_posts'][] = array(
                    'post_id' => $post_id,
                    'title' => get_the_title($post_id),
                    'results' => $post_results
                );
                
                $results['success_count']++;
                
            } catch (Exception $e) {
                $results['errors'][] = array(
                    'post_id' => $post_id,
                    'title' => get_the_title($post_id),
                    'error' => $e->getMessage()
                );
                
                $results['error_count']++;
            }
        }
        
        return $results;
    }
    
    /**
     * SEO für einzelnen Post generieren
     */
    private function generate_seo_for_post($post_id, $seo_elements) {
        // Diese Methode würde die bestehende SEO-Generierung verwenden
        // Hier vereinfachte Implementierung
        
        $results = array();
        
        foreach ($seo_elements as $element) {
            // Hier würde die echte SEO-Generierung stattfinden
            $results[$element] = "Generiert für Post {$post_id}";
        }
        
        return $results;
    }
    
    /**
     * HTML für erweiterte Admin-Oberfläche rendern
     */
    public function render_enhanced_interface() {
        $html = '';
        
        // Content-Übersicht
        $html .= $this->render_content_overview();
        
        // Direkte Textgenerierung
        if ($this->direct_text_generator) {
            $html .= $this->direct_text_generator->render_direct_text_interface();
        }
        
        // Bulk-SEO-Generierung
        $html .= $this->render_bulk_seo_interface();
        
        return $html;
    }
    
    /**
     * Content-Übersicht HTML rendern
     */
    private function render_content_overview() {
        return '<div class="retexify-content-overview-section">';
        $html .= '<h3>📊 Content-Übersicht</h3>';
        $html .= '<div class="retexify-content-filter">';
        $html .= '<select id="retexify-content-type-filter">';
        $html .= '<option value="all">Alle Inhalte</option>';
        $html .= '<option value="posts">Beiträge</option>';
        $html .= '<option value="pages">Seiten</option>';
        $html .= '<option value="images">Bilder</option>';
        $html .= '</select>';
        $html .= '<button class="retexify-btn retexify-load-content-overview">Laden</button>';
        $html .= '</div>';
        $html .= '<div id="retexify-content-overview-results"></div>';
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Bulk-SEO HTML rendern
     */
    private function render_bulk_seo_interface() {
        $html = '<div class="retexify-bulk-seo-section">';
        $html .= '<h3>⚡ Bulk-SEO-Generierung</h3>';
        $html .= '<p class="retexify-description">';
        $html .= 'Generieren Sie SEO-Daten für mehrere Beiträge/Seiten gleichzeitig.';
        $html .= '</p>';
        $html .= '<div class="retexify-bulk-seo-controls">';
        $html .= '<div class="retexify-form-group">';
        $html .= '<label>SEO-Elemente:</label>';
        $html .= '<label><input type="checkbox" name="bulk_seo_elements[]" value="meta_title" checked> Meta-Titel</label>';
        $html .= '<label><input type="checkbox" name="bulk_seo_elements[]" value="meta_description" checked> Meta-Beschreibung</label>';
        $html .= '<label><input type="checkbox" name="bulk_seo_elements[]" value="focus_keyword" checked> Focus-Keyword</label>';
        $html .= '</div>';
        $html .= '<button class="retexify-btn retexify-btn-primary retexify-start-bulk-seo">';
        $html .= '⚡ Bulk-Generierung starten';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '<div id="retexify-bulk-seo-progress" style="display: none;"></div>';
        $html .= '<div id="retexify-bulk-seo-results"></div>';
        $html .= '</div>';
        
        return $html;
    }
} 