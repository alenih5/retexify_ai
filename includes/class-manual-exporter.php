<?php
/**
 * ReTexify Manual Exporter Class
 *
 * Handles the manual data export to CSV.
 *
 * @package    ReTexify
 * @author     Imponi
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Retexify_Manual_Exporter')) {
    class Retexify_Manual_Exporter {

        private $content_analyzer;

        public function __construct() {
            if (!class_exists('German_Content_Analyzer')) {
                require_once RETEXIFY_PLUGIN_PATH . 'includes/class-german-content-analyzer.php';
            }
            $this->content_analyzer = retexify_get_content_analyzer();
        }

        /**
         * Generates a CSV file with the selected post data based on options.
         *
         * @param array $options The export options.
         * @return array|WP_Error The result of the export operation.
         */
        public function generate_csv($options) {
            $args = array(
                'post_type' => $options['post_types'],
                'posts_per_page' => -1,
                'post_status' => $options['statuses'],
                'orderby' => 'ID',
                'order' => 'ASC',
            );
            $posts = get_posts($args);

            if (empty($posts)) {
                return new WP_Error('no_posts', 'Keine passenden Inhalte für den Export gefunden. Bitte prüfen Sie Ihre Auswahl.');
            }

            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/retexify-exports';
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
            }

            $filename = 'retexify-export-' . implode('-', $options['post_types']) . '-' . date('Y-m-d-His') . '.csv';
            $filepath = $export_dir . '/' . $filename;
            $file_url = $upload_dir['baseurl'] . '/retexify-exports/' . $filename;

            $file = fopen($filepath, 'w');
            if (!$file) {
                return new WP_Error('file_error', 'Die Exportdatei konnte nicht erstellt werden. Prüfen Sie die Berechtigungen des Upload-Ordners.');
            }

            // Set UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Dynamische Header
            $base_headers = ['ID', 'Post Type', 'Slug'];
            $original_headers = [];
            $new_headers = [];
            $field_map = $this->get_field_map();
            
            foreach ($options['content_fields'] as $field_key) {
                if (isset($field_map[$field_key])) {
                    $original_headers[] = $field_map[$field_key]['label'] . ' (Original)';
                    $new_headers[] = $field_map[$field_key]['label'] . ' (Neu)';
                }
            }
            
            $headers = array_merge($base_headers, $original_headers, $new_headers);
            fputcsv($file, $headers);

            foreach ($posts as $post) {
                // Base data
                $csv_row = [
                    $post->ID,
                    $post->post_type,
                    $post->post_name
                ];
                
                $original_data = [];
                $new_data = [];
                
                // Dynamic data based on selection
                foreach ($options['content_fields'] as $field_key) {
                     if (isset($field_map[$field_key])) {
                        $original_data[] = call_user_func($field_map[$field_key]['getter'], $post->ID, $post);
                        $new_data[] = ''; // Placeholder for new data
                     }
                }
                
                $final_row = array_merge($csv_row, $original_data, $new_data);
                fputcsv($file, $final_row);
            }

            fclose($file);

            return array(
                'success' => true,
                'file_url' => $file_url,
                'message' => count($posts) . ' Einträge erfolgreich nach ' . basename($filepath) . ' exportiert.'
            );
        }

        /**
         * Map of field keys to their properties and data retrieval methods.
         */
        private function get_field_map() {
            return [
                'title' => [
                    'label' => 'Titel',
                    'getter' => function($id, $post) { return $post->post_title; }
                ],
                'content' => [
                    'label' => 'Content',
                    'getter' => function($id, $post) { return $this->content_analyzer->clean_german_text($post->post_content); }
                ],
                'meta_title' => [
                    'label' => 'Meta-Titel',
                    'getter' => function($id) { return $this->get_meta_value($id, ['_yoast_wpseo_title', 'rank_math_title', '_aioseop_title', '_seopress_titles_title']); }
                ],
                'meta_description' => [
                    'label' => 'Meta-Beschreibung',
                    'getter' => function($id) { return $this->get_meta_value($id, ['_yoast_wpseo_metadesc', 'rank_math_description', '_aioseop_description', '_seopress_titles_desc']); }
                ],
                'focus_keyword' => [
                    'label' => 'Focus Keyphrase',
                    'getter' => function($id) { return $this->get_meta_value($id, ['_yoast_wpseo_focuskw', 'rank_math_focus_keyword']); }
                ],
                'featured_image' => [
                    'label' => 'Beitragsbild URL',
                    'getter' => function($id) { return get_the_post_thumbnail_url($id, 'full'); }
                ],
                'alt_text' => [
                    'label' => 'Bild Alt-Text',
                    'getter' => function($id) { 
                        $thumb_id = get_post_thumbnail_id($id);
                        return $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : ''; 
                    }
                ]
            ];
        }

        /**
         * Helper to get meta value from a list of possible keys.
         */
        private function get_meta_value($post_id, $keys) {
            foreach ($keys as $key) {
                $value = get_post_meta($post_id, $key, true);
                if (!empty($value)) {
                    return $value;
                }
            }
            return '';
        }
    }
} 