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

        /**
         * Generates a CSV file with the selected post data.
         *
         * @param string $post_type The post type to export.
         * @return array|WP_Error The result of the export operation.
         */
        public function generate_csv($post_type) {
            $args = array(
                'post_type' => $post_type,
                'posts_per_page' => -1,
                'post_status' => 'publish',
            );
            $posts = get_posts($args);

            if (empty($posts)) {
                return new WP_Error('no_posts', 'Keine Beiträge für den Export gefunden.');
            }

            $upload_dir = wp_upload_dir();
            $export_dir = $upload_dir['basedir'] . '/retexify-exports';
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
            }

            $filename = 'retexify-export-' . $post_type . '-' . time() . '.csv';
            $filepath = $export_dir . '/' . $filename;
            $file_url = $upload_dir['baseurl'] . '/retexify-exports/' . $filename;

            $file = fopen($filepath, 'w');

            // Set UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            $headers = array(
                'ID', 'Titel', 'Content', 'Meta-Titel', 'Meta-Beschreibung', 
                'Yoast Focus Keyphrase', 'Featured Image URL', 'Image Alt Text', 
                'WPBakery Elements', 'Post Type', 'Slug'
            );
            fputcsv($file, $headers);

            foreach ($posts as $post) {
                $row = array();
                $row[] = $post->ID;
                $row[] = $post->post_title;
                $row[] = $post->post_content;
                $row[] = get_post_meta($post->ID, '_yoast_wpseo_title', true);
                $row[] = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
                $row[] = get_post_meta($post->ID, '_yoast_wpseo_focuskw', true);
                
                $image_url = get_the_post_thumbnail_url($post->ID, 'full');
                $row[] = $image_url;
                $row[] = get_post_meta(get_post_thumbnail_id($post->ID), '_wp_attachment_image_alt', true);
                
                $row[] = get_post_meta($post->ID, '_wpb_shortcodes_custom_css', true);
                $row[] = $post->post_type;
                $row[] = $post->post_name;

                fputcsv($file, $row);
            }

            fclose($file);

            return array(
                'success' => true,
                'file_url' => $file_url,
                'message' => count($posts) . ' Beiträge erfolgreich exportiert.'
            );
        }
    }
} 