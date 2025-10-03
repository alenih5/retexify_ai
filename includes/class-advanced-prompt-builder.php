<?php
/**
 * ReTexify Advanced Prompt Builder
 *
 * Erstellt hochoptimierte KI-Prompts für SEO-Textgenerierung basierend auf
 * Content-Analyse, Keyword-Research und SERP-Konkurrenzanalyse.
 *
 * @package ReTexify_AI
 * @since 4.11.0
 */

if (!defined('ABSPATH')) {
    exit; // Direct access not allowed
}

if (!class_exists('ReTexify_Advanced_Prompt_Builder')) {
    class ReTexify_Advanced_Prompt_Builder {
        
        private $ai_engine;
        private $content_analyzer;
        private $serp_analyzer;
        private $keyword_research;
        
        /**
         * Konstruktor
         */
        public function __construct($ai_engine = null, $content_analyzer = null, $serp_analyzer = null, $keyword_research = null) {
            $this->ai_engine = $ai_engine;
            $this->content_analyzer = $content_analyzer;
            $this->serp_analyzer = $serp_analyzer;
            $this->keyword_research = $keyword_research;
        }
        
        /**
         * Hochoptimierten SEO-Prompt erstellen
         *
         * @param int $post_id WordPress Post ID
         * @param array $settings SEO-Einstellungen
         * @return array Prompt-Daten und generierte Meta-Texte
         */
        public function build_advanced_seo_prompt($post_id, $settings) {
            // Alle verfügbaren Daten sammeln
            $data = $this->collect_all_data($post_id, $settings);
            
            // Prompt erstellen
            $prompt = $this->create_seo_generation_prompt($data);
            
            // KI-Generierung durchführen
            $generated_content = $this->generate_seo_content($prompt, $data);
            
            return array(
                'prompt' => $prompt,
                'data' => $data,
                'generated_content' => $generated_content,
                'timestamp' => current_time('mysql'),
                'settings' => $settings
            );
        }
        
        /**
         * Alle verfügbaren Daten sammeln
         *
         * @param int $post_id
         * @param array $settings
         * @return array
         */
        private function collect_all_data($post_id, $settings) {
            $data = array(
                'post_id' => $post_id,
                'settings' => $settings,
                'timestamp' => current_time('mysql')
            );
            
            // 1. Business-Kontext laden
            $data['business_context'] = $this->load_business_context($settings);
            
            // 2. Content-Analyse durchführen
            if ($this->content_analyzer) {
                $focus_keyword = $settings['focus_keyword'] ?? '';
                $data['content_analysis'] = $this->content_analyzer->analyze_post_content($post_id, $focus_keyword);
            }
            
            // 3. Keyword-Research durchführen
            if ($this->keyword_research && !empty($settings['focus_keyword'])) {
                $data['keyword_research'] = $this->perform_keyword_research($settings['focus_keyword'], $settings);
            }
            
            // 4. SERP-Analyse durchführen
            if ($this->serp_analyzer && !empty($settings['focus_keyword'])) {
                $location = $settings['location'] ?? 'CH';
                $data['serp_analysis'] = $this->serp_analyzer->analyze_serp($settings['focus_keyword'], $location);
            }
            
            // 5. Lokale SEO-Daten
            $data['local_seo'] = $this->prepare_local_seo_data($settings);
            
            return $data;
        }
        
        /**
         * Business-Kontext aus Plugin-Einstellungen laden
         *
         * @param array $settings
         * @return array
         */
        private function load_business_context($settings) {
            $business_settings = get_option('retexify_business_settings', array());
            
            return array(
                'company_name' => $business_settings['company_name'] ?? 'Ihr Unternehmen',
                'industry' => $business_settings['industry'] ?? 'Allgemein',
                'target_audience' => $business_settings['target_audience'] ?? 'Privat',
                'brand_voice' => $business_settings['brand_voice'] ?? 'Professionell',
                'usps' => $business_settings['usps'] ?? array(),
                'company_description' => $business_settings['company_description'] ?? '',
                'contact_info' => $business_settings['contact_info'] ?? array(),
                'website_url' => get_site_url()
            );
        }
        
        /**
         * Keyword-Research durchführen
         *
         * @param string $keyword
         * @param array $settings
         * @return array
         */
        private function perform_keyword_research($keyword, $settings) {
            if (!$this->keyword_research) {
                return array();
            }
            
            $research_data = array(
                'main_keyword' => $keyword,
                'related_keywords' => array(),
                'lsi_keywords' => array(),
                'long_tail_keywords' => array(),
                'search_intent' => $this->classify_search_intent($keyword),
                'keyword_difficulty' => $this->estimate_keyword_difficulty($keyword),
                'search_volume_trend' => 'stable'
            );
            
            try {
                // Verwandte Keywords abrufen
                $related = $this->keyword_research->get_related_keywords($keyword, 10);
                if (!empty($related)) {
                    $research_data['related_keywords'] = array_slice($related, 0, 8);
                }
                
                // LSI Keywords generieren
                $lsi = $this->keyword_research->generate_lsi_keywords($keyword, $settings);
                if (!empty($lsi)) {
                    $research_data['lsi_keywords'] = array_slice($lsi, 0, 6);
                }
                
                // Long-Tail Keywords
                $long_tail = $this->keyword_research->generate_long_tail_keywords($keyword, 5);
                if (!empty($long_tail)) {
                    $research_data['long_tail_keywords'] = $long_tail;
                }
                
            } catch (Exception $e) {
                error_log('ReTexify Advanced Prompt: Keyword Research Error - ' . $e->getMessage());
            }
            
            return $research_data;
        }
        
        /**
         * Lokale SEO-Daten vorbereiten
         *
         * @param array $settings
         * @return array
         */
        private function prepare_local_seo_data($settings) {
            $selected_cantons = $settings['selected_cantons'] ?? array();
            $local_keywords = array();
            
            if (!empty($selected_cantons)) {
                foreach ($selected_cantons as $canton) {
                    $canton_name = $this->get_canton_name($canton);
                    if ($canton_name) {
                        $local_keywords[] = $canton_name;
                    }
                }
            }
            
            return array(
                'selected_cantons' => $selected_cantons,
                'canton_names' => array_filter(array_map(array($this, 'get_canton_name'), $selected_cantons)),
                'local_keywords' => $local_keywords,
                'location' => $settings['location'] ?? 'CH'
            );
        }
        
        /**
         * Hochoptimierten SEO-Generierungs-Prompt erstellen
         *
         * @param array $data
         * @return string
         */
        private function create_seo_generation_prompt($data) {
            $prompt = "Du bist ein SEO-Experte mit Spezialisierung auf deutschsprachige Märkte und Google-Optimierung.\n\n";
            
            // Business-Kontext
            $prompt .= "BUSINESS-KONTEXT:\n";
            $prompt .= "- Unternehmen: " . $data['business_context']['company_name'] . "\n";
            $prompt .= "- Branche: " . $data['business_context']['industry'] . "\n";
            $prompt .= "- Zielgruppe: " . $data['business_context']['target_audience'] . "\n";
            $prompt .= "- Markenstimme: " . $data['business_context']['brand_voice'] . "\n";
            
            if (!empty($data['business_context']['usps'])) {
                $prompt .= "- Unique Selling Propositions: " . implode(', ', $data['business_context']['usps']) . "\n";
            }
            
            $prompt .= "\n";
            
            // Content-Analyse
            if (!empty($data['content_analysis'])) {
                $content = $data['content_analysis'];
                $prompt .= "SEITENINHALT-ANALYSE:\n";
                $prompt .= "- Thema: " . ($content['basic_info']['title'] ?? 'Nicht verfügbar') . "\n";
                $prompt .= "- Content-Qualität: " . ($content['seo_score'] ?? 0) . "/100\n";
                $prompt .= "- Textlänge: " . ($content['basic_info']['word_count'] ?? 0) . " Wörter\n";
                
                if (!empty($content['content_quality'])) {
                    $prompt .= "- Sätze: " . ($content['content_quality']['sentences_count'] ?? 0) . "\n";
                    $prompt .= "- Absätze: " . ($content['content_quality']['paragraphs_count'] ?? 0) . "\n";
                }
                
                if (!empty($content['readability'])) {
                    $prompt .= "- Lesbarkeit: " . ($content['readability']['readability_level'] ?? 'Unbekannt') . "\n";
                }
                
                $prompt .= "\n";
            }
            
            // Keyword-Daten
            if (!empty($data['keyword_research'])) {
                $keywords = $data['keyword_research'];
                $prompt .= "KEYWORD-DATEN:\n";
                $prompt .= "- Haupt-Keyword: " . ($keywords['main_keyword'] ?? '') . "\n";
                $prompt .= "- Suchintention: " . ($keywords['search_intent'] ?? 'Informational') . "\n";
                
                if (!empty($keywords['related_keywords'])) {
                    $prompt .= "- Verwandte Keywords: " . implode(', ', array_slice($keywords['related_keywords'], 0, 5)) . "\n";
                }
                
                if (!empty($keywords['lsi_keywords'])) {
                    $prompt .= "- LSI-Keywords: " . implode(', ', array_slice($keywords['lsi_keywords'], 0, 4)) . "\n";
                }
                
                if (!empty($keywords['long_tail_keywords'])) {
                    $prompt .= "- Long-Tail-Varianten: " . implode(', ', array_slice($keywords['long_tail_keywords'], 0, 3)) . "\n";
                }
                
                $prompt .= "\n";
            }
            
            // SERP-Konkurrenz-Insights
            if (!empty($data['serp_analysis'])) {
                $serp = $data['serp_analysis'];
                $prompt .= "KONKURRENZ-INSIGHTS:\n";
                
                if (!empty($serp['meta_analysis'])) {
                    $prompt .= "- Durchschn. Titel-Länge: " . ($serp['meta_analysis']['avg_title_length'] ?? 0) . " Zeichen\n";
                    $prompt .= "- Durchschn. Beschreibungs-Länge: " . ($serp['meta_analysis']['avg_description_length'] ?? 0) . " Zeichen\n";
                }
                
                if (!empty($serp['keyword_analysis']['semantic_keywords'])) {
                    $prompt .= "- Top-Ranking-Keywords: " . implode(', ', array_slice($serp['keyword_analysis']['semantic_keywords'], 0, 4)) . "\n";
                }
                
                if (!empty($serp['content_gaps']['missing_topics'])) {
                    $prompt .= "- Content-Gaps: " . implode(', ', array_slice($serp['content_gaps']['missing_topics'], 0, 3)) . "\n";
                }
                
                $prompt .= "\n";
            }
            
            // Lokale SEO
            if (!empty($data['local_seo']['canton_names'])) {
                $prompt .= "LOCAL SEO:\n";
                $prompt .= "- Kantone: " . implode(', ', $data['local_seo']['canton_names']) . "\n";
                $prompt .= "- Lokale Keywords: " . implode(', ', $data['local_seo']['local_keywords']) . "\n";
                $prompt .= "\n";
            }
            
            // Prompt-Anweisungen
            $prompt .= "AUFGABE:\n";
            $prompt .= "Erstelle einen hochoptimierten Meta-Titel (max 58 Zeichen) und Meta-Beschreibung (140-155 Zeichen) für diese Seite.\n\n";
            
            $prompt .= "ANFORDERUNGEN:\n";
            $prompt .= "✅ Haupt-Keyword natürlich im Titel integrieren\n";
            $prompt .= "✅ Lokale Bezüge (Kantone) geschickt einbauen\n";
            $prompt .= "✅ Call-to-Action verwenden\n";
            $prompt .= "✅ Unique Selling Proposition hervorheben\n";
            $prompt .= "✅ Emotionale Trigger nutzen\n";
            $prompt .= "✅ Für Rich Snippets optimieren\n";
            $prompt .= "✅ Premium-Ton beibehalten\n";
            $prompt .= "✅ Suchintention berücksichtigen\n";
            $prompt .= "✅ LSI-Keywords natürlich einbauen\n\n";
            
            $prompt .= "Antworte im JSON-Format:\n";
            $prompt .= "{\n";
            $prompt .= '  "meta_title": "...",' . "\n";
            $prompt .= '  "meta_description": "...",' . "\n";
            $prompt .= '  "focus_keyword": "...",' . "\n";
            $prompt .= '  "reasoning": "Kurze Erklärung der SEO-Strategie",' . "\n";
            $prompt .= '  "local_optimization": "Wie lokale Bezüge eingebaut wurden",' . "\n";
            $prompt .= '  "cta_strategy": "Call-to-Action Strategie"' . "\n";
            $prompt .= "}\n";
            
            return $prompt;
        }
        
        /**
         * SEO-Content mit KI generieren
         *
         * @param string $prompt
         * @param array $data
         * @return array
         */
        private function generate_seo_content($prompt, $data) {
            if (!$this->ai_engine) {
                return array(
                    'error' => 'AI Engine nicht verfügbar',
                    'meta_title' => '',
                    'meta_description' => '',
                    'reasoning' => ''
                );
            }
            
            try {
                // AI-Request durchführen
                $response = $this->ai_engine->generate_text($prompt, array(
                    'max_tokens' => 500,
                    'temperature' => 0.7,
                    'top_p' => 0.9
                ));
                
                if (is_wp_error($response)) {
                    throw new Exception('AI Generation Error: ' . $response->get_error_message());
                }
                
                // JSON-Response parsen
                $json_data = $this->parse_ai_response($response);
                
                // Validierung und Bereinigung
                $json_data = $this->validate_and_clean_response($json_data, $data);
                
                return $json_data;
                
            } catch (Exception $e) {
                error_log('ReTexify Advanced Prompt: AI Generation Error - ' . $e->getMessage());
                
                // Fallback-Response
                return $this->generate_fallback_response($data);
            }
        }
        
        /**
         * AI-Response parsen
         *
         * @param string $response
         * @return array
         */
        private function parse_ai_response($response) {
            // JSON aus Response extrahieren
            $json_match = preg_match('/\{.*\}/s', $response, $matches);
            
            if ($json_match && !empty($matches[0])) {
                $json_data = json_decode($matches[0], true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
                    return $json_data;
                }
            }
            
            // Fallback: Einfaches Parsing
            return $this->simple_parse_response($response);
        }
        
        /**
         * Einfaches Response-Parsing als Fallback
         *
         * @param string $response
         * @return array
         */
        private function simple_parse_response($response) {
            $result = array(
                'meta_title' => '',
                'meta_description' => '',
                'focus_keyword' => '',
                'reasoning' => 'Automatisch generiert',
                'local_optimization' => '',
                'cta_strategy' => ''
            );
            
            // Titel extrahieren
            if (preg_match('/meta_title["\']?\s*:\s*["\']([^"\']+)["\']/', $response, $matches)) {
                $result['meta_title'] = trim($matches[1]);
            }
            
            // Beschreibung extrahieren
            if (preg_match('/meta_description["\']?\s*:\s*["\']([^"\']+)["\']/', $response, $matches)) {
                $result['meta_description'] = trim($matches[1]);
            }
            
            // Keyword extrahieren
            if (preg_match('/focus_keyword["\']?\s*:\s*["\']([^"\']+)["\']/', $response, $matches)) {
                $result['focus_keyword'] = trim($matches[1]);
            }
            
            // Reasoning extrahieren
            if (preg_match('/reasoning["\']?\s*:\s*["\']([^"\']+)["\']/', $response, $matches)) {
                $result['reasoning'] = trim($matches[1]);
            }
            
            return $result;
        }
        
        /**
         * Response validieren und bereinigen
         *
         * @param array $json_data
         * @param array $data
         * @return array
         */
        private function validate_and_clean_response($json_data, $data) {
            $result = array(
                'meta_title' => '',
                'meta_description' => '',
                'focus_keyword' => '',
                'reasoning' => '',
                'local_optimization' => '',
                'cta_strategy' => '',
                'validation' => array()
            );
            
            // Meta-Titel validieren
            $title = $json_data['meta_title'] ?? '';
            $result['meta_title'] = $this->clean_and_validate_title($title);
            $result['validation']['title_length'] = strlen($result['meta_title']);
            $result['validation']['title_valid'] = strlen($result['meta_title']) <= 60;
            
            // Meta-Beschreibung validieren
            $description = $json_data['meta_description'] ?? '';
            $result['meta_description'] = $this->clean_and_validate_description($description);
            $result['validation']['description_length'] = strlen($result['meta_description']);
            $result['validation']['description_valid'] = strlen($result['meta_description']) >= 140 && strlen($result['meta_description']) <= 160;
            
            // Focus-Keyword
            $result['focus_keyword'] = sanitize_text_field($json_data['focus_keyword'] ?? '');
            
            // Reasoning
            $result['reasoning'] = sanitize_text_field($json_data['reasoning'] ?? 'Automatisch generiert');
            
            // Lokale Optimierung
            $result['local_optimization'] = sanitize_text_field($json_data['local_optimization'] ?? '');
            
            // CTA-Strategie
            $result['cta_strategy'] = sanitize_text_field($json_data['cta_strategy'] ?? '');
            
            return $result;
        }
        
        /**
         * Fallback-Response generieren
         *
         * @param array $data
         * @return array
         */
        private function generate_fallback_response($data) {
            $focus_keyword = $data['settings']['focus_keyword'] ?? '';
            $title = $data['content_analysis']['basic_info']['title'] ?? 'SEO-optimierter Titel';
            
            // Einfacher Fallback-Titel
            $fallback_title = $title;
            if (!empty($focus_keyword) && stripos($title, $focus_keyword) === false) {
                $fallback_title = $focus_keyword . ' - ' . $title;
            }
            
            // Titel-Länge begrenzen
            if (strlen($fallback_title) > 58) {
                $fallback_title = substr($fallback_title, 0, 55) . '...';
            }
            
            // Fallback-Beschreibung
            $fallback_description = 'Entdecken Sie die besten Lösungen und Services. Professionelle Beratung und hochwertige Qualität. Kontaktieren Sie uns für weitere Informationen.';
            
            // Lokale Bezüge hinzufügen
            if (!empty($data['local_seo']['canton_names'])) {
                $cantons = implode(', ', array_slice($data['local_seo']['canton_names'], 0, 2));
                $fallback_description = 'Entdecken Sie die besten Lösungen in ' . $cantons . '. Professionelle Beratung und hochwertige Qualität. Kontaktieren Sie uns für weitere Informationen.';
            }
            
            return array(
                'meta_title' => $fallback_title,
                'meta_description' => $fallback_description,
                'focus_keyword' => $focus_keyword,
                'reasoning' => 'Fallback-Generierung aufgrund AI-Fehler',
                'local_optimization' => !empty($data['local_seo']['canton_names']) ? 'Lokale Kantone integriert' : '',
                'cta_strategy' => 'Kontakt-Aufruf verwendet',
                'validation' => array(
                    'title_length' => strlen($fallback_title),
                    'title_valid' => strlen($fallback_title) <= 60,
                    'description_length' => strlen($fallback_description),
                    'description_valid' => strlen($fallback_description) >= 140 && strlen($fallback_description) <= 160
                )
            );
        }
        
        /**
         * Titel bereinigen und validieren
         *
         * @param string $title
         * @return string
         */
        private function clean_and_validate_title($title) {
            // HTML-Tags entfernen
            $title = wp_strip_all_tags($title);
            
            // Mehrfache Leerzeichen entfernen
            $title = preg_replace('/\s+/', ' ', $title);
            
            // Trim
            $title = trim($title);
            
            // Länge begrenzen
            if (strlen($title) > 60) {
                $title = substr($title, 0, 57) . '...';
            }
            
            return $title;
        }
        
        /**
         * Beschreibung bereinigen und validieren
         *
         * @param string $description
         * @return string
         */
        private function clean_and_validate_description($description) {
            // HTML-Tags entfernen
            $description = wp_strip_all_tags($description);
            
            // Mehrfache Leerzeichen entfernen
            $description = preg_replace('/\s+/', ' ', $description);
            
            // Trim
            $description = trim($description);
            
            // Länge anpassen
            if (strlen($description) < 140) {
                $description .= ' Kontaktieren Sie uns für weitere Informationen.';
            } elseif (strlen($description) > 160) {
                $description = substr($description, 0, 157) . '...';
            }
            
            return $description;
        }
        
        /**
         * Suchintention klassifizieren
         *
         * @param string $keyword
         * @return string
         */
        private function classify_search_intent($keyword) {
            $keyword_lower = strtolower($keyword);
            
            // Transactional Keywords
            if (preg_match('/\b(kaufen|bestellen|preis|kosten|shop|store|online)\b/', $keyword_lower)) {
                return 'Transactional';
            }
            
            // Navigational Keywords
            if (preg_match('/\b(website|homepage|kontakt|impressum)\b/', $keyword_lower)) {
                return 'Navigational';
            }
            
            // Informational Keywords
            if (preg_match('/\b(was|wie|warum|wo|wann|wer|tutorial|anleitung|tipp)\b/', $keyword_lower)) {
                return 'Informational';
            }
            
            return 'Informational';
        }
        
        /**
         * Keyword-Schwierigkeit schätzen
         *
         * @param string $keyword
         * @return string
         */
        private function estimate_keyword_difficulty($keyword) {
            $word_count = str_word_count($keyword);
            
            if ($word_count == 1) {
                return 'Hoch';
            } elseif ($word_count == 2) {
                return 'Mittel';
            } else {
                return 'Niedrig';
            }
        }
        
        /**
         * Kantonsname abrufen
         *
         * @param string $canton_code
         * @return string|null
         */
        private function get_canton_name($canton_code) {
            $cantons = array(
                'AG' => 'Aargau',
                'AI' => 'Appenzell Innerrhoden',
                'AR' => 'Appenzell Ausserrhoden',
                'BE' => 'Bern',
                'BL' => 'Basel-Landschaft',
                'BS' => 'Basel-Stadt',
                'FR' => 'Freiburg',
                'GE' => 'Genf',
                'GL' => 'Glarus',
                'GR' => 'Graubünden',
                'JU' => 'Jura',
                'LU' => 'Luzern',
                'NE' => 'Neuenburg',
                'NW' => 'Nidwalden',
                'OW' => 'Obwalden',
                'SG' => 'St. Gallen',
                'SH' => 'Schaffhausen',
                'SO' => 'Solothurn',
                'SZ' => 'Schwyz',
                'TG' => 'Thurgau',
                'TI' => 'Tessin',
                'UR' => 'Uri',
                'VD' => 'Waadt',
                'VS' => 'Wallis',
                'ZG' => 'Zug',
                'ZH' => 'Zürich'
            );
            
            return $cantons[$canton_code] ?? null;
        }
    }
}
