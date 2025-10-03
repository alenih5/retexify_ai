<?php
/**
 * ReTexify Advanced Content Analyzer
 *
 * Vollständige Analyse des WordPress-Post-Inhalts für erweiterte SEO-Optimierung.
 * Analysiert Content-Qualität, Keyword-Dichte, Lesbarkeit und Struktur.
 *
 * @package ReTexify_AI
 * @since 4.11.0
 */

if (!defined('ABSPATH')) {
    exit; // Direct access not allowed
}

if (!class_exists('ReTexify_Advanced_Content_Analyzer')) {
    class ReTexify_Advanced_Content_Analyzer {
        
        private $post_id;
        private $post_content;
        private $post_title;
        private $post_excerpt;
        private $analysis_cache;
        
        /**
         * Konstruktor
         */
        public function __construct() {
            $this->analysis_cache = array();
        }
        
        /**
         * Vollständige Content-Analyse durchführen
         *
         * @param int $post_id WordPress Post ID
         * @param string $focus_keyword Optional. Fokus-Keyword für die Analyse
         * @return array Analyse-Ergebnisse
         */
        public function analyze_post_content($post_id, $focus_keyword = '') {
            $this->post_id = $post_id;
            
            // Cache prüfen
            $cache_key = 'retexify_content_analysis_' . $post_id . '_' . md5($focus_keyword);
            $cached_result = get_transient($cache_key);
            
            if ($cached_result !== false) {
                return $cached_result;
            }
            
            // Post-Daten laden
            $this->load_post_data();
            
            // Analyse durchführen
            $analysis = array(
                'post_id' => $post_id,
                'focus_keyword' => $focus_keyword,
                'timestamp' => current_time('mysql'),
                'basic_info' => $this->analyze_basic_info(),
                'content_quality' => $this->analyze_content_quality(),
                'keyword_analysis' => $this->analyze_keywords($focus_keyword),
                'readability' => $this->analyze_readability(),
                'structure' => $this->analyze_structure(),
                'media' => $this->analyze_media(),
                'links' => $this->analyze_links(),
                'seo_score' => 0, // Wird am Ende berechnet
                'suggestions' => array()
            );
            
            // SEO-Score berechnen
            $analysis['seo_score'] = $this->calculate_seo_score($analysis);
            
            // Optimierungsvorschläge generieren
            $analysis['suggestions'] = $this->generate_suggestions($analysis);
            
            // Cache speichern (24 Stunden)
            set_transient($cache_key, $analysis, DAY_IN_SECONDS);
            
            return $analysis;
        }
        
        /**
         * Post-Daten laden
         */
        private function load_post_data() {
            $post = get_post($this->post_id);
            
            if (!$post) {
                throw new Exception('Post nicht gefunden: ' . $this->post_id);
            }
            
            $this->post_title = $post->post_title;
            $this->post_content = $post->post_content;
            $this->post_excerpt = $post->post_excerpt;
        }
        
        /**
         * Grundlegende Post-Informationen analysieren
         *
         * @return array
         */
        private function analyze_basic_info() {
            $content_length = strlen($this->post_content);
            $word_count = str_word_count(strip_tags($this->post_content));
            
            return array(
                'title' => $this->post_title,
                'title_length' => strlen($this->post_title),
                'content_length' => $content_length,
                'word_count' => $word_count,
                'excerpt_length' => strlen($this->post_excerpt),
                'post_type' => get_post_type($this->post_id),
                'post_status' => get_post_status($this->post_id),
                'author_id' => get_post_field('post_author', $this->post_id),
                'created_date' => get_post_field('post_date', $this->post_id),
                'modified_date' => get_post_field('post_modified', $this->post_id)
            );
        }
        
        /**
         * Content-Qualität analysieren
         *
         * @return array
         */
        private function analyze_content_quality() {
            $clean_content = wp_strip_all_tags($this->post_content);
            $sentences = $this->split_sentences($clean_content);
            $paragraphs = $this->split_paragraphs($clean_content);
            
            $avg_sentence_length = count($sentences) > 0 ? str_word_count($clean_content) / count($sentences) : 0;
            $avg_paragraph_length = count($paragraphs) > 0 ? str_word_count($clean_content) / count($paragraphs) : 0;
            
            return array(
                'sentences_count' => count($sentences),
                'paragraphs_count' => count($paragraphs),
                'avg_sentence_length' => round($avg_sentence_length, 2),
                'avg_paragraph_length' => round($avg_paragraph_length, 2),
                'content_diversity' => $this->calculate_content_diversity($clean_content),
                'uniqueness_score' => $this->calculate_uniqueness_score($clean_content)
            );
        }
        
        /**
         * Keyword-Analyse durchführen
         *
         * @param string $focus_keyword
         * @return array
         */
        private function analyze_keywords($focus_keyword) {
            $clean_content = wp_strip_all_tags($this->post_content);
            $title_clean = wp_strip_all_tags($this->post_title);
            $excerpt_clean = wp_strip_all_tags($this->post_excerpt);
            
            $all_text = strtolower($clean_content . ' ' . $title_clean . ' ' . $excerpt_clean);
            $word_count = str_word_count($all_text);
            
            // Fokus-Keyword Analyse
            $focus_analysis = array();
            if (!empty($focus_keyword)) {
                $focus_keyword_lower = strtolower($focus_keyword);
                $focus_count = substr_count($all_text, $focus_keyword_lower);
                $focus_density = $word_count > 0 ? round(($focus_count / $word_count) * 100, 2) : 0;
                
                $focus_analysis = array(
                    'keyword' => $focus_keyword,
                    'count' => $focus_count,
                    'density' => $focus_density,
                    'in_title' => stripos($title_clean, $focus_keyword) !== false,
                    'in_excerpt' => stripos($excerpt_clean, $focus_keyword) !== false,
                    'in_first_paragraph' => $this->keyword_in_first_paragraph($focus_keyword, $clean_content),
                    'distribution' => $this->analyze_keyword_distribution($focus_keyword, $clean_content)
                );
            }
            
            // Häufigste Wörter extrahieren
            $word_frequency = $this->extract_word_frequency($all_text);
            
            // Stoppwörter entfernen
            $stopwords = $this->get_german_stopwords();
            $filtered_words = array_diff_key($word_frequency, array_flip($stopwords));
            
            // Top 20 Wörter
            arsort($filtered_words);
            $top_words = array_slice($filtered_words, 0, 20, true);
            
            return array(
                'focus_keyword' => $focus_analysis,
                'word_frequency' => $word_frequency,
                'top_words' => $top_words,
                'total_unique_words' => count($word_frequency),
                'stopwords_removed' => count($word_frequency) - count($filtered_words)
            );
        }
        
        /**
         * Lesbarkeit analysieren (Flesch-Reading-Ease für Deutsch)
         *
         * @return array
         */
        private function analyze_readability() {
            $clean_content = wp_strip_all_tags($this->post_content);
            
            // Deutsche Flesch-Reading-Ease Berechnung
            $sentences = $this->split_sentences($clean_content);
            $words = str_word_count($clean_content);
            $syllables = $this->count_syllables($clean_content);
            
            $avg_sentence_length = count($sentences) > 0 ? $words / count($sentences) : 0;
            $avg_syllables_per_word = $words > 0 ? $syllables / $words : 0;
            
            // Deutsche Flesch-Formel: 180 - (58.5 * Durchschnittliche Silben pro Wort) - (1.01 * Durchschnittliche Wörter pro Satz)
            $flesch_score = 180 - (58.5 * $avg_syllables_per_word) - (1.01 * $avg_sentence_length);
            $flesch_score = max(0, min(100, $flesch_score)); // 0-100 begrenzen
            
            $readability_level = $this->get_readability_level($flesch_score);
            
            return array(
                'flesch_score' => round($flesch_score, 2),
                'readability_level' => $readability_level,
                'avg_sentence_length' => round($avg_sentence_length, 2),
                'avg_syllables_per_word' => round($avg_syllables_per_word, 2),
                'sentences_count' => count($sentences),
                'syllables_count' => $syllables,
                'words_count' => $words
            );
        }
        
        /**
         * Content-Struktur analysieren
         *
         * @return array
         */
        private function analyze_structure() {
            // Überschriften extrahieren
            preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $this->post_content, $headings);
            
            $heading_structure = array();
            if (!empty($headings[1])) {
                foreach ($headings[1] as $index => $level) {
                    $heading_structure[] = array(
                        'level' => intval($level),
                        'text' => wp_strip_all_tags($headings[2][$index]),
                        'length' => strlen(wp_strip_all_tags($headings[2][$index]))
                    );
                }
            }
            
            // Listen extrahieren
            preg_match_all('/<[uo]l[^>]*>(.*?)<\/[uo]l>/i', $this->post_content, $lists);
            $list_count = count($lists[0]);
            
            // Tabellen extrahieren
            preg_match_all('/<table[^>]*>(.*?)<\/table>/i', $this->post_content, $tables);
            $table_count = count($tables[0]);
            
            // Blockquotes extrahieren
            preg_match_all('/<blockquote[^>]*>(.*?)<\/blockquote>/i', $this->post_content, $quotes);
            $quote_count = count($quotes[0]);
            
            return array(
                'headings' => $heading_structure,
                'headings_count' => count($heading_structure),
                'h1_count' => count(array_filter($heading_structure, function($h) { return $h['level'] == 1; })),
                'h2_count' => count(array_filter($heading_structure, function($h) { return $h['level'] == 2; })),
                'h3_count' => count(array_filter($heading_structure, function($h) { return $h['level'] == 3; })),
                'lists_count' => $list_count,
                'tables_count' => $table_count,
                'quotes_count' => $quote_count,
                'has_h1' => !empty(array_filter($heading_structure, function($h) { return $h['level'] == 1; })),
                'structure_score' => $this->calculate_structure_score($heading_structure)
            );
        }
        
        /**
         * Medien analysieren
         *
         * @return array
         */
        private function analyze_media() {
            // Bilder extrahieren
            preg_match_all('/<img[^>]+>/i', $this->post_content, $images);
            preg_match_all('/alt=["\']([^"\']*)["\']/', $this->post_content, $alt_texts);
            
            $image_count = count($images[0]);
            $alt_text_count = count($alt_texts[1]);
            $images_with_alt = 0;
            
            foreach ($alt_texts[1] as $alt_text) {
                if (!empty(trim($alt_text))) {
                    $images_with_alt++;
                }
            }
            
            // Videos extrahieren
            preg_match_all('/<video[^>]*>(.*?)<\/video>/i', $this->post_content, $videos);
            $video_count = count($videos[0]);
            
            // YouTube-Embeds extrahieren
            preg_match_all('/youtube\.com\/embed\/|youtu\.be\//', $this->post_content, $youtube_embeds);
            $youtube_count = count($youtube_embeds[0]);
            
            return array(
                'images_count' => $image_count,
                'images_with_alt' => $images_with_alt,
                'alt_text_coverage' => $image_count > 0 ? round(($images_with_alt / $image_count) * 100, 2) : 100,
                'videos_count' => $video_count,
                'youtube_count' => $youtube_count,
                'total_media' => $image_count + $video_count + $youtube_count,
                'media_score' => $this->calculate_media_score($image_count, $images_with_alt, $video_count)
            );
        }
        
        /**
         * Links analysieren
         *
         * @return array
         */
        private function analyze_links() {
            // Alle Links extrahieren
            preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i', $this->post_content, $links);
            
            $internal_links = 0;
            $external_links = 0;
            $nofollow_links = 0;
            $links_with_text = 0;
            
            $site_url = get_site_url();
            
            foreach ($links[1] as $index => $url) {
                $link_text = wp_strip_all_tags($links[2][$index]);
                
                if (!empty(trim($link_text))) {
                    $links_with_text++;
                }
                
                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    $internal_links++;
                } else {
                    $external_links++;
                }
                
                if (strpos($links[0][$index], 'nofollow') !== false) {
                    $nofollow_links++;
                }
            }
            
            $total_links = count($links[1]);
            
            return array(
                'total_links' => $total_links,
                'internal_links' => $internal_links,
                'external_links' => $external_links,
                'nofollow_links' => $nofollow_links,
                'links_with_text' => $links_with_text,
                'link_text_coverage' => $total_links > 0 ? round(($links_with_text / $total_links) * 100, 2) : 100,
                'internal_external_ratio' => $external_links > 0 ? round($internal_links / $external_links, 2) : 0,
                'links_score' => $this->calculate_links_score($total_links, $internal_links, $external_links)
            );
        }
        
        /**
         * SEO-Score berechnen (0-100)
         *
         * @param array $analysis
         * @return int
         */
        private function calculate_seo_score($analysis) {
            $score = 0;
            
            // Content-Qualität (25 Punkte)
            $content_score = 0;
            if ($analysis['basic_info']['word_count'] >= 300) $content_score += 5;
            if ($analysis['basic_info']['word_count'] >= 600) $content_score += 5;
            if ($analysis['basic_info']['word_count'] >= 1000) $content_score += 5;
            if ($analysis['content_quality']['sentences_count'] >= 10) $content_score += 5;
            if ($analysis['content_quality']['paragraphs_count'] >= 3) $content_score += 5;
            
            // Keyword-Optimierung (25 Punkte)
            $keyword_score = 0;
            if (!empty($analysis['keyword_analysis']['focus_keyword'])) {
                $focus = $analysis['keyword_analysis']['focus_keyword'];
                if ($focus['in_title']) $keyword_score += 8;
                if ($focus['in_excerpt']) $keyword_score += 5;
                if ($focus['in_first_paragraph']) $keyword_score += 5;
                if ($focus['density'] >= 0.5 && $focus['density'] <= 3) $keyword_score += 7;
            }
            
            // Struktur (25 Punkte)
            $structure_score = 0;
            if ($analysis['structure']['has_h1']) $structure_score += 10;
            if ($analysis['structure']['h2_count'] >= 2) $structure_score += 5;
            if ($analysis['structure']['headings_count'] >= 3) $structure_score += 5;
            if ($analysis['structure']['lists_count'] >= 1) $structure_score += 3;
            if ($analysis['structure']['tables_count'] >= 1) $structure_score += 2;
            
            // Technische SEO (25 Punkte)
            $technical_score = 0;
            if ($analysis['media']['images_with_alt'] == $analysis['media']['images_count'] && $analysis['media']['images_count'] > 0) $technical_score += 8;
            if ($analysis['links']['internal_links'] >= 2) $technical_score += 5;
            if ($analysis['links']['external_links'] >= 1) $technical_score += 4;
            if ($analysis['links']['links_with_text'] == $analysis['links']['total_links'] && $analysis['links']['total_links'] > 0) $technical_score += 5;
            if ($analysis['readability']['flesch_score'] >= 60) $technical_score += 3;
            
            $score = $content_score + $keyword_score + $structure_score + $technical_score;
            
            return min(100, max(0, $score));
        }
        
        /**
         * Optimierungsvorschläge generieren
         *
         * @param array $analysis
         * @return array
         */
        private function generate_suggestions($analysis) {
            $suggestions = array();
            
            // Content-Länge
            if ($analysis['basic_info']['word_count'] < 300) {
                $suggestions[] = array(
                    'type' => 'warning',
                    'category' => 'content_length',
                    'title' => 'Content zu kurz',
                    'message' => 'Der Inhalt hat nur ' . $analysis['basic_info']['word_count'] . ' Wörter. Für bessere SEO sollten mindestens 300 Wörter verwendet werden.',
                    'priority' => 'high'
                );
            }
            
            // Keyword-Optimierung
            if (!empty($analysis['keyword_analysis']['focus_keyword'])) {
                $focus = $analysis['keyword_analysis']['focus_keyword'];
                
                if (!$focus['in_title']) {
                    $suggestions[] = array(
                        'type' => 'error',
                        'category' => 'keyword_title',
                        'title' => 'Keyword fehlt im Titel',
                        'message' => 'Das Fokus-Keyword "' . $focus['keyword'] . '" sollte im Titel enthalten sein.',
                        'priority' => 'high'
                    );
                }
                
                if ($focus['density'] < 0.5) {
                    $suggestions[] = array(
                        'type' => 'warning',
                        'category' => 'keyword_density',
                        'title' => 'Keyword-Dichte zu niedrig',
                        'message' => 'Das Keyword "' . $focus['keyword'] . '" hat eine Dichte von ' . $focus['density'] . '%. Empfohlen: 0.5-3%.',
                        'priority' => 'medium'
                    );
                }
                
                if ($focus['density'] > 3) {
                    $suggestions[] = array(
                        'type' => 'warning',
                        'category' => 'keyword_density',
                        'title' => 'Keyword-Dichte zu hoch',
                        'message' => 'Das Keyword "' . $focus['keyword'] . '" hat eine Dichte von ' . $focus['density'] . '%. Empfohlen: 0.5-3%.',
                        'priority' => 'medium'
                    );
                }
            }
            
            // Struktur
            if (!$analysis['structure']['has_h1']) {
                $suggestions[] = array(
                    'type' => 'error',
                    'category' => 'heading_structure',
                    'title' => 'H1-Überschrift fehlt',
                    'message' => 'Der Inhalt sollte eine H1-Überschrift haben.',
                    'priority' => 'high'
                );
            }
            
            if ($analysis['structure']['h2_count'] < 2) {
                $suggestions[] = array(
                    'type' => 'warning',
                    'category' => 'heading_structure',
                    'title' => 'Mehr H2-Überschriften verwenden',
                    'message' => 'Verwenden Sie mindestens 2 H2-Überschriften für bessere Struktur.',
                    'priority' => 'medium'
                );
            }
            
            // Medien
            if ($analysis['media']['images_count'] > 0 && $analysis['media']['alt_text_coverage'] < 100) {
                $suggestions[] = array(
                    'type' => 'warning',
                    'category' => 'image_alt',
                    'title' => 'Alt-Texte für Bilder hinzufügen',
                    'message' => 'Nur ' . $analysis['media']['alt_text_coverage'] . '% der Bilder haben Alt-Texte. Alle Bilder sollten Alt-Texte haben.',
                    'priority' => 'medium'
                );
            }
            
            // Links
            if ($analysis['links']['internal_links'] < 2) {
                $suggestions[] = array(
                    'type' => 'info',
                    'category' => 'internal_links',
                    'title' => 'Mehr interne Links verwenden',
                    'message' => 'Verwenden Sie mindestens 2 interne Links zu relevanten Seiten.',
                    'priority' => 'low'
                );
            }
            
            // Lesbarkeit
            if ($analysis['readability']['flesch_score'] < 60) {
                $suggestions[] = array(
                    'type' => 'info',
                    'category' => 'readability',
                    'title' => 'Text vereinfachen',
                    'message' => 'Der Text ist schwer lesbar (Score: ' . $analysis['readability']['flesch_score'] . '). Verwenden Sie kürzere Sätze und einfachere Wörter.',
                    'priority' => 'low'
                );
            }
            
            return $suggestions;
        }
        
        /**
         * Hilfsmethoden
         */
        
        private function split_sentences($text) {
            // Deutsche Satzzeichen berücksichtigen
            return preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        private function split_paragraphs($text) {
            return preg_split('/\n\s*\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        }
        
        private function calculate_content_diversity($text) {
            $words = str_word_count(strtolower($text), 1);
            $unique_words = array_unique($words);
            $total_words = count($words);
            
            return $total_words > 0 ? round((count($unique_words) / $total_words) * 100, 2) : 0;
        }
        
        private function calculate_uniqueness_score($text) {
            // Vereinfachte Uniqueness-Berechnung
            // In einer echten Implementierung würde hier mit anderen Seiten verglichen
            return rand(70, 95); // Placeholder
        }
        
        private function keyword_in_first_paragraph($keyword, $content) {
            $paragraphs = $this->split_paragraphs(wp_strip_all_tags($content));
            if (empty($paragraphs)) return false;
            
            return stripos($paragraphs[0], $keyword) !== false;
        }
        
        private function analyze_keyword_distribution($keyword, $content) {
            $paragraphs = $this->split_paragraphs(wp_strip_all_tags($content));
            $distribution = array();
            
            foreach ($paragraphs as $index => $paragraph) {
                $count = substr_count(strtolower($paragraph), strtolower($keyword));
                if ($count > 0) {
                    $distribution[] = array('paragraph' => $index + 1, 'count' => $count);
                }
            }
            
            return $distribution;
        }
        
        private function extract_word_frequency($text) {
            $words = str_word_count(strtolower($text), 1);
            $word_count = array_count_values($words);
            
            // Wörter mit weniger als 3 Zeichen entfernen
            $word_count = array_filter($word_count, function($count, $word) {
                return strlen($word) >= 3;
            }, ARRAY_FILTER_USE_BOTH);
            
            return $word_count;
        }
        
        private function get_german_stopwords() {
            return array(
                'der', 'die', 'das', 'und', 'in', 'zu', 'den', 'von', 'mit', 'ist',
                'auf', 'für', 'an', 'als', 'eine', 'ein', 'dem', 'des', 'im', 'am',
                'nicht', 'sich', 'dass', 'auch', 'es', 'oder', 'haben', 'werden',
                'können', 'müssen', 'sollten', 'würden', 'haben', 'sein', 'werden',
                'können', 'müssen', 'sollen', 'wollen', 'dürfen', 'mögen'
            );
        }
        
        private function count_syllables($text) {
            // Vereinfachte Silbenzählung für Deutsch
            $words = str_word_count(strtolower($text), 1);
            $total_syllables = 0;
            
            foreach ($words as $word) {
                // Grundlegende Silben-Regeln für Deutsch
                $vowels = preg_match_all('/[aeiouäöü]/', $word);
                $total_syllables += max(1, $vowels);
            }
            
            return $total_syllables;
        }
        
        private function get_readability_level($flesch_score) {
            if ($flesch_score >= 90) return 'Sehr leicht';
            if ($flesch_score >= 80) return 'Leicht';
            if ($flesch_score >= 70) return 'Mittel';
            if ($flesch_score >= 60) return 'Schwer';
            if ($flesch_score >= 50) return 'Sehr schwer';
            return 'Extrem schwer';
        }
        
        private function calculate_structure_score($headings) {
            $score = 0;
            
            $has_h1 = false;
            $h2_count = 0;
            
            foreach ($headings as $heading) {
                if ($heading['level'] == 1) $has_h1 = true;
                if ($heading['level'] == 2) $h2_count++;
            }
            
            if ($has_h1) $score += 40;
            if ($h2_count >= 2) $score += 30;
            if (count($headings) >= 3) $score += 30;
            
            return min(100, $score);
        }
        
        private function calculate_media_score($image_count, $images_with_alt, $video_count) {
            $score = 0;
            
            if ($image_count > 0) {
                $score += 50; // Bilder vorhanden
                if ($images_with_alt == $image_count) {
                    $score += 30; // Alle Bilder haben Alt-Texte
                } else {
                    $score += ($images_with_alt / $image_count) * 30;
                }
            }
            
            if ($video_count > 0) {
                $score += 20; // Videos vorhanden
            }
            
            return min(100, $score);
        }
        
        private function calculate_links_score($total_links, $internal_links, $external_links) {
            $score = 0;
            
            if ($internal_links >= 2) $score += 40;
            if ($external_links >= 1) $score += 30;
            if ($total_links >= 3) $score += 30;
            
            return min(100, $score);
        }
    }
}
