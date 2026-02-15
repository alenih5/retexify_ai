<?php
/**
 * ReTexify Media SEO Manager
 * 
 * Intelligente Bild- und Medien-SEO-Verwaltung
 * Bilder filtern, analysieren, Alt-Texte generieren, exportieren
 * 
 * @package ReTexify_AI_Pro
 * @since 4.24.0
 * @version 4.24.0
 * @author Imponi
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Media_SEO_Manager {
    
    /**
     * Unterstützte Bild-MIME-Types
     */
    private static $supported_types = array(
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    );
    
    /**
     * Medien laden mit Filtern
     * 
     * @param array $filters Filter-Optionen
     * @return array Gefilterte Medien-Daten
     */
    public static function get_media($filters = array()) {
        global $wpdb;
        
        $defaults = array(
            'filter_type'   => 'all',          // all, without_alt, with_alt, without_title
            'mime_type'     => '',              // image/jpeg, image/png, etc.
            'attached_to'   => '',              // post_id oder 'unattached'
            'search'        => '',              // Suchbegriff
            'per_page'      => 50,
            'page'          => 1,
            'orderby'       => 'date',
            'order'         => 'DESC'
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        // Basis-Query für Bilder
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => !empty($filters['mime_type']) ? $filters['mime_type'] : 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => intval($filters['per_page']),
            'paged'          => intval($filters['page']),
            'orderby'        => sanitize_key($filters['orderby']),
            'order'          => $filters['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        // Suchfilter
        if (!empty($filters['search'])) {
            $args['s'] = sanitize_text_field($filters['search']);
        }
        
        // Anhang-Filter
        if (!empty($filters['attached_to'])) {
            if ($filters['attached_to'] === 'unattached') {
                $args['post_parent'] = 0;
            } else {
                $args['post_parent'] = intval($filters['attached_to']);
            }
        }
        
        // Meta-Filter für Alt-Text
        if ($filters['filter_type'] === 'without_alt') {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => '',
                    'compare' => '='
                )
            );
        } elseif ($filters['filter_type'] === 'with_alt') {
            $args['meta_query'] = array(
                array(
                    'key'     => '_wp_attachment_image_alt',
                    'value'   => '',
                    'compare' => '!='
                )
            );
        }
        
        $query = new WP_Query($args);
        $media_items = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                $media_items[] = self::get_media_item_data($attachment_id);
            }
            wp_reset_postdata();
        }
        
        // Zusätzlicher Filter: Ohne Titel (muss post-query gefiltert werden)
        if ($filters['filter_type'] === 'without_title') {
            $media_items = array_filter($media_items, function($item) {
                $filename = pathinfo($item['filename'], PATHINFO_FILENAME);
                $title_is_filename = (sanitize_title($item['title']) === sanitize_title($filename));
                return empty($item['title']) || $title_is_filename;
            });
            $media_items = array_values($media_items);
        }
        
        return array(
            'items'       => $media_items,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => intval($filters['page']),
            'per_page'    => intval($filters['per_page'])
        );
    }
    
    /**
     * Einzelne Medien-Item-Daten abrufen
     * 
     * @param int $attachment_id Attachment-ID
     * @return array Medien-Daten
     */
    public static function get_media_item_data($attachment_id) {
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return array();
        }
        
        $metadata = wp_get_attachment_metadata($attachment_id);
        $file_url = wp_get_attachment_url($attachment_id);
        $thumb_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        
        // Ermittle die Seite(n), auf der das Bild verwendet wird
        $parent_info = self::get_image_usage_context($attachment_id);
        
        return array(
            'id'              => $attachment_id,
            'title'           => $attachment->post_title,
            'filename'        => basename(get_attached_file($attachment_id) ?: ''),
            'alt_text'        => $alt_text ?: '',
            'caption'         => $attachment->post_excerpt ?: '',
            'description'     => $attachment->post_content ?: '',
            'url'             => $file_url,
            'thumbnail'       => $thumb_url ?: $file_url,
            'mime_type'       => $attachment->post_mime_type,
            'date'            => get_the_date('d.m.Y', $attachment_id),
            'filesize'        => isset($metadata['filesize']) ? size_format($metadata['filesize']) : '',
            'dimensions'      => isset($metadata['width']) ? $metadata['width'] . 'x' . $metadata['height'] : '',
            'parent_id'       => $attachment->post_parent,
            'parent_title'    => $parent_info['title'],
            'parent_url'      => $parent_info['url'],
            'parent_content'  => $parent_info['content_excerpt'],
            'parent_keywords' => $parent_info['keywords'],
            'usage_count'     => $parent_info['usage_count'],
            'has_alt'         => !empty($alt_text),
            'needs_optimization' => empty($alt_text) || strlen($alt_text) < 10
        );
    }
    
    /**
     * Ermittelt den Kontext, in dem ein Bild verwendet wird
     * 
     * @param int $attachment_id Attachment-ID
     * @return array Nutzungs-Kontext
     */
    public static function get_image_usage_context($attachment_id) {
        global $wpdb;
        $attachment = get_post($attachment_id);
        
        $result = array(
            'title'           => '',
            'url'             => '',
            'content_excerpt' => '',
            'keywords'        => '',
            'usage_count'     => 0,
            'pages'           => array()
        );
        
        // 1. Direkte Eltern-Seite prüfen
        if ($attachment && $attachment->post_parent > 0) {
            $parent = get_post($attachment->post_parent);
            if ($parent) {
                $result['title'] = $parent->post_title;
                $result['url'] = get_permalink($parent->ID);
                $result['content_excerpt'] = wp_trim_words(wp_strip_all_tags($parent->post_content), 80);
                
                // Keywords aus Yoast/RankMath holen
                $keywords = get_post_meta($parent->ID, '_yoast_wpseo_focuskw', true);
                if (empty($keywords)) {
                    $keywords = get_post_meta($parent->ID, 'rank_math_focus_keyword', true);
                }
                $result['keywords'] = $keywords ?: '';
            }
        }
        
        // 2. Suche nach Bild-Verwendung in Post-Content
        $file_url = wp_get_attachment_url($attachment_id);
        if ($file_url) {
            $filename = basename($file_url);
            $usage_posts = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_title, post_content FROM {$wpdb->posts} 
                 WHERE post_status = 'publish' 
                 AND (post_type = 'post' OR post_type = 'page')
                 AND post_content LIKE %s
                 LIMIT 5",
                '%' . $wpdb->esc_like($filename) . '%'
            ));
            
            $result['usage_count'] = count($usage_posts);
            
            foreach ($usage_posts as $usage_post) {
                $result['pages'][] = array(
                    'id'    => $usage_post->ID,
                    'title' => $usage_post->post_title
                );
                
                // Falls kein Eltern-Kontext, verwende den ersten Fund
                if (empty($result['title'])) {
                    $result['title'] = $usage_post->post_title;
                    $result['url'] = get_permalink($usage_post->ID);
                    $result['content_excerpt'] = wp_trim_words(wp_strip_all_tags($usage_post->post_content), 80);
                    
                    $keywords = get_post_meta($usage_post->ID, '_yoast_wpseo_focuskw', true);
                    if (empty($keywords)) {
                        $keywords = get_post_meta($usage_post->ID, 'rank_math_focus_keyword', true);
                    }
                    $result['keywords'] = $keywords ?: '';
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Medien-Statistiken abrufen
     * 
     * @return array Statistiken
     */
    public static function get_media_stats() {
        global $wpdb;
        
        $total_images = $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'"
        );
        
        $with_alt = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'attachment' 
             AND p.post_mime_type LIKE 'image/%' 
             AND pm.meta_key = '_wp_attachment_image_alt' 
             AND pm.meta_value <> ''"
        );
        
        $without_alt = $total_images - $with_alt;
        
        $unattached = $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type LIKE 'image/%' 
             AND post_parent = 0"
        );
        
        // Bilder nach MIME-Type
        $by_type = $wpdb->get_results(
            "SELECT post_mime_type, COUNT(*) as count FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' 
             GROUP BY post_mime_type ORDER BY count DESC"
        );
        
        $type_breakdown = array();
        foreach ($by_type as $type) {
            $type_breakdown[str_replace('image/', '', $type->post_mime_type)] = intval($type->count);
        }
        
        $optimization_percentage = $total_images > 0 
            ? round(($with_alt / $total_images) * 100, 1) 
            : 0;
        
        return array(
            'total_images'      => intval($total_images),
            'with_alt'          => intval($with_alt),
            'without_alt'       => intval($without_alt),
            'unattached'        => intval($unattached),
            'type_breakdown'    => $type_breakdown,
            'optimization_pct'  => $optimization_percentage
        );
    }
    
    /**
     * Bild-SEO-Daten speichern
     * 
     * @param int    $attachment_id Attachment-ID
     * @param array  $seo_data SEO-Daten (alt_text, title, caption, description)
     * @return array Ergebnis
     */
    public static function save_image_seo($attachment_id, $seo_data) {
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return array('success' => false, 'message' => 'Bild nicht gefunden');
        }
        
        $saved = 0;
        
        // Alt-Text speichern
        if (isset($seo_data['alt_text'])) {
            update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($seo_data['alt_text']));
            $saved++;
        }
        
        // Titel, Caption, Description als Post-Update
        $update_data = array('ID' => $attachment_id);
        $should_update = false;
        
        if (isset($seo_data['title']) && !empty($seo_data['title'])) {
            $update_data['post_title'] = sanitize_text_field($seo_data['title']);
            $should_update = true;
            $saved++;
        }
        
        if (isset($seo_data['caption'])) {
            $update_data['post_excerpt'] = sanitize_textarea_field($seo_data['caption']);
            $should_update = true;
            $saved++;
        }
        
        if (isset($seo_data['description'])) {
            $update_data['post_content'] = sanitize_textarea_field($seo_data['description']);
            $should_update = true;
            $saved++;
        }
        
        if ($should_update) {
            wp_update_post($update_data);
        }
        
        return array(
            'success'     => true,
            'saved_count' => $saved,
            'message'     => $saved . ' Bild-SEO-Elemente gespeichert'
        );
    }
    
    /**
     * Medien als CSV exportieren
     * 
     * @param array $filters Filter für den Export
     * @return array Export-Ergebnis mit Dateiname
     */
    public static function export_media_csv($filters = array()) {
        $filters['per_page'] = 9999;
        $media_data = self::get_media($filters);
        
        if (empty($media_data['items'])) {
            return array('success' => false, 'message' => 'Keine Medien zum Exportieren gefunden');
        }
        
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/retexify-ai/';
        
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'retexify-media-export-' . date('Y-m-d-His') . '.csv';
        $filepath = $export_dir . $filename;
        
        $fp = fopen($filepath, 'w');
        if (!$fp) {
            return array('success' => false, 'message' => 'Konnte Export-Datei nicht erstellen');
        }
        
        // BOM für Excel UTF-8
        fwrite($fp, "\xEF\xBB\xBF");
        
        // CSV-Header
        fputcsv($fp, array(
            'ID',
            'Dateiname',
            'Titel',
            'Alt-Text (Aktuell)',
            'Alt-Text (Neu)',
            'Bildunterschrift (Aktuell)',
            'Bildunterschrift (Neu)',
            'Beschreibung',
            'MIME-Typ',
            'Dimensionen',
            'Verwendet auf Seite',
            'Seiten-Keywords',
            'URL',
            'Optimiert'
        ), ';');
        
        // Daten schreiben
        foreach ($media_data['items'] as $item) {
            fputcsv($fp, array(
                $item['id'],
                $item['filename'],
                $item['title'],
                $item['alt_text'],
                '', // Neu-Spalte für Bearbeitung
                $item['caption'],
                '', // Neu-Spalte für Bearbeitung
                $item['description'],
                $item['mime_type'],
                $item['dimensions'],
                $item['parent_title'],
                $item['parent_keywords'],
                $item['url'],
                $item['has_alt'] ? 'Ja' : 'Nein'
            ), ';');
        }
        
        fclose($fp);
        
        return array(
            'success'   => true,
            'filename'  => $filename,
            'filepath'  => $filepath,
            'file_size' => size_format(filesize($filepath)),
            'row_count' => count($media_data['items']),
            'message'   => count($media_data['items']) . ' Medien exportiert'
        );
    }
}
