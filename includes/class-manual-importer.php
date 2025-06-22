<?php
/**
 * ReTexify Manual Importer Class
 *
 * Handles the manual data import from a CSV file.
 *
 * @package    ReTexify
 * @author     Imponi
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Retexify_Manual_Importer')) {
    class Retexify_Manual_Importer {

        /**
         * Processes the uploaded CSV file for import.
         *
         * @param string $file_path Path to the uploaded CSV file.
         * @return array Result of the import process.
         */
        public function import_csv($file_path) {
            if (!file_exists($file_path) || !is_readable($file_path)) {
                return new WP_Error('file_error', 'Die Importdatei konnte nicht gelesen werden.');
            }

            $handle = fopen($file_path, 'r');
            if ($handle === false) {
                return new WP_Error('file_open_error', 'Die Importdatei konnte nicht geöffnet werden.');
            }

            // Skip header row
            $headers = fgetcsv($handle);
            $processed_rows = 0;
            $updated_posts = 0;
            $created_posts = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                $post_id = intval($data['ID']);
                
                $post_data = array(
                    'post_title'   => wp_strip_all_tags($data['Titel']),
                    'post_content' => $data['Content'],
                    'post_type'    => $data['Post Type'],
                    'post_name'    => $data['Slug'],
                    'post_status'  => 'publish',
                );

                if ($post_id > 0 && get_post($post_id)) {
                    // Update existing post
                    $post_data['ID'] = $post_id;
                    wp_update_post($post_data);
                    $updated_posts++;
                } else {
                    // Create new post
                    $post_id = wp_insert_post($post_data);
                    $created_posts++;
                }

                if ($post_id > 0) {
                    // Update meta
                    update_post_meta($post_id, '_yoast_wpseo_title', sanitize_text_field($data['Meta-Titel']));
                    update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($data['Meta-Beschreibung']));
                    update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($data['Yoast Focus Keyphrase']));
                    update_post_meta($post_id, '_wpb_shortcodes_custom_css', wp_kses_post($data['WPBakery Elements']));

                    // Import featured image
                    if (!empty($data['Featured Image URL'])) {
                        $this->set_featured_image_from_url($post_id, $data['Featured Image URL'], $data['Image Alt Text']);
                    }
                }
                $processed_rows++;
            }

            fclose($handle);
            unlink($file_path); // Clean up uploaded file

            $message = sprintf(
                '%d Zeilen verarbeitet. %d Beiträge aktualisiert, %d Beiträge neu erstellt.',
                $processed_rows, $updated_posts, $created_posts
            );

            return array('success' => true, 'message' => $message);
        }

        private function set_featured_image_from_url($post_id, $image_url, $alt_text = '') {
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Check if image already exists
            $image_name = basename($image_url);
            $existing_attachment_id = $this->get_attachment_id_by_filename($image_name);
            if ($existing_attachment_id) {
                set_post_thumbnail($post_id, $existing_attachment_id);
                return $existing_attachment_id;
            }

            $tmp = download_url($image_url);
            if (is_wp_error($tmp)) {
                return false;
            }

            $file_array = array(
                'name' => $image_name,
                'tmp_name' => $tmp
            );
            
            $attachment_id = media_handle_sideload($file_array, $post_id, $alt_text);

            if (is_wp_error($attachment_id)) {
                @unlink($file_array['tmp_name']);
                return false;
            }

            set_post_thumbnail($post_id, $attachment_id);
            if (!empty($alt_text)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
            }

            return $attachment_id;
        }

        private function get_attachment_id_by_filename($filename) {
            global $wpdb;
            $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid RLIKE %s;", $filename));
            return !empty($attachment) ? $attachment[0] : null;
        }
    }
} 