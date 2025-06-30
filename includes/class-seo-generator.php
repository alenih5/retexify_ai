<?php
/**
 * ReTexify AI - SEO Generator Handler
 * Verwaltet alle SEO-Generierungs-Logik zentral
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_SEO_Generator {
    
    private $ai_engine;
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        if (function_exists('retexify_get_ai_engine')) {
            $this->ai_engine = retexify_get_ai_engine();
        }
    }
    
    /**
     * ALLE SEO-Texte parallel generieren (Hauptfunktion)
     */
    public function generate_complete_seo($post_id, $include_cantons = true, $premium_tone = false) {
        $start_time = microtime(true);
        
        try {
            $post = get_post($post_id);
            if (!$post) {
                throw new Exception('Post nicht gefunden');
            }
            
            // API-Einstellungen abrufen
            $settings = $this->get_ai_settings();
            if (empty($settings['api_key'])) {
                throw new Exception('Kein API-Schlüssel konfiguriert');
            }
            
            // Content vorbereiten
            $content = $this->prepare_content($post);
            
            // Alle drei SEO-Texte parallel generieren
            $results = array();
            
            if ($this->ai_engine && method_exists($this->ai_engine, 'generate_seo_suite')) {
                $suite = $this->ai_engine->generate_seo_suite(
                    $content,
                    $settings,
                    $include_cantons,
                    $premium_tone
                );
                
                $results = array(
                    'meta_title' => $suite['meta_title'] ?? '',
                    'meta_description' => $suite['meta_description'] ?? '',
                    'focus_keyword' => $suite['focus_keyword'] ?? ''
                );
            } else {
                // Fallback: Einzeln generieren
                $results['meta_title'] = $this->generate_single_seo_item($post, 'meta_title', $include_cantons, $premium_tone);
                $results['meta_description'] = $this->generate_single_seo_item($post, 'meta_description', $include_cantons, $premium_tone);
                $results['focus_keyword'] = $this->generate_single_seo_item($post, 'focus_keyword', $include_cantons, $premium_tone);
            }
            
            $end_time = microtime(true);
            $generation_time = round(($end_time - $start_time), 2);
            
            return array(
                'suite' => $results,
                'generation_time' => $generation_time,
                'tokens_used' => $this->estimate_tokens_used($content),
                'success' => true
            );
            
        } catch (Exception $e) {
            error_log('ReTexify SEO Generator Error: ' . $e->getMessage());
            return array(
                'error' => $e->getMessage(),
                'success' => false
            );
        }
    }
    
    /**
     * Einzelnes SEO-Item generieren
     */
    public function generate_single_seo_item($post, $seo_type, $include_cantons = true, $premium_tone = false) {
        try {
            $settings = $this->get_ai_settings();
            $content = $this->prepare_content($post);
            
            if ($this->ai_engine && method_exists($this->ai_engine, 'generate_single_seo_item')) {
                return $this->ai_engine->generate_single_seo_item(
                    $post,
                    $seo_type,
                    $settings,
                    $include_cantons,
                    $premium_tone
                );
            }
            
            return '';
            
        } catch (Exception $e) {
            error_log('ReTexify Single SEO Error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Content für KI-Analyse vorbereiten
     */
    private function prepare_content($post) {
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : wp_trim_words($content, 30);
        
        // Content für KI optimieren (begrenzen)
        $full_content = $title . "\n\n" . $excerpt . "\n\n" . $content;
        return substr($full_content, 0, 2000);
    }
    
    /**
     * AI-Einstellungen mit API-Keys abrufen
     */
    private function get_ai_settings() {
        $settings = get_option('retexify_ai_settings', array());
        $api_keys = get_option('retexify_api_keys', array());
        $current_provider = $settings['api_provider'] ?? 'openai';
        
        $settings['api_key'] = $api_keys[$current_provider] ?? '';
        
        return $settings;
    }
    
    /**
     * Token-Usage schätzen
     */
    private function estimate_tokens_used($content) {
        // Grobe Schätzung: 4 Zeichen = 1 Token
        return ceil(strlen($content) / 4);
    }
}

/**
 * Helper-Funktion für globalen Zugriff
 */
function retexify_get_seo_generator() {
    return ReTexify_SEO_Generator::get_instance();
} 