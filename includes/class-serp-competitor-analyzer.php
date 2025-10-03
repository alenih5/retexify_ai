<?php
/**
 * ReTexify SERP Competitor Analyzer
 *
 * Analysiert Top 10 Google-Suchergebnisse für ein Keyword und extrahiert
 * wertvolle SEO-Insights für bessere Content-Strategie.
 *
 * @package ReTexify_AI
 * @since 4.11.0
 */

if (!defined('ABSPATH')) {
    exit; // Direct access not allowed
}

if (!class_exists('ReTexify_Serp_Competitor_Analyzer')) {
    class ReTexify_Serp_Competitor_Analyzer {
        
        private $cache_duration;
        private $user_agent;
        private $rate_limit_delay;
        
        /**
         * Konstruktor
         */
        public function __construct() {
            $this->cache_duration = 7 * DAY_IN_SECONDS; // 7 Tage Cache
            $this->user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
            $this->rate_limit_delay = 2; // 2 Sekunden zwischen Requests
        }
        
        /**
         * Vollständige SERP-Analyse durchführen
         *
         * @param string $keyword Das zu analysierende Keyword
         * @param string $location Optional. Standort (z.B. 'CH', 'DE')
         * @param int $max_results Optional. Maximale Anzahl Ergebnisse (default: 10)
         * @return array SERP-Analyse-Ergebnisse
         */
        public function analyze_serp($keyword, $location = 'CH', $max_results = 10) {
            // Cache prüfen
            $cache_key = 'retexify_serp_' . md5($keyword . '_' . $location . '_' . $max_results);
            $cached_result = get_transient($cache_key);
            
            if ($cached_result !== false) {
                return $cached_result;
            }
            
            // SERP-Daten abrufen
            $serp_results = $this->fetch_serp_results($keyword, $location, $max_results);
            
            if (empty($serp_results)) {
                return array(
                    'keyword' => $keyword,
                    'location' => $location,
                    'error' => 'Keine SERP-Ergebnisse gefunden',
                    'timestamp' => current_time('mysql')
                );
            }
            
            // Analyse durchführen
            $analysis = array(
                'keyword' => $keyword,
                'location' => $location,
                'timestamp' => current_time('mysql'),
                'total_results' => count($serp_results),
                'results' => $serp_results,
                'meta_analysis' => $this->analyze_meta_tags($serp_results),
                'keyword_analysis' => $this->analyze_keyword_usage($serp_results, $keyword),
                'content_gaps' => $this->identify_content_gaps($serp_results),
                'serp_features' => $this->analyze_serp_features($serp_results),
                'competitor_insights' => $this->generate_competitor_insights($serp_results, $keyword),
                'recommendations' => array()
            );
            
            // Empfehlungen generieren
            $analysis['recommendations'] = $this->generate_recommendations($analysis);
            
            // Cache speichern
            set_transient($cache_key, $analysis, $this->cache_duration);
            
            return $analysis;
        }
        
        /**
         * SERP-Ergebnisse von Google abrufen
         *
         * @param string $keyword
         * @param string $location
         * @param int $max_results
         * @return array
         */
        private function fetch_serp_results($keyword, $location, $max_results) {
            $results = array();
            
            // Google Search URL erstellen
            $search_url = $this->build_google_search_url($keyword, $location);
            
            // Rate Limiting
            sleep($this->rate_limit_delay);
            
            // HTTP Request
            $response = wp_remote_get($search_url, array(
                'timeout' => 30,
                'user-agent' => $this->user_agent,
                'headers' => array(
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                    'Accept-Encoding' => 'gzip, deflate',
                    'Connection' => 'keep-alive',
                )
            ));
            
            if (is_wp_error($response)) {
                error_log('ReTexify SERP: HTTP Error - ' . $response->get_error_message());
                return array();
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                error_log('ReTexify SERP: Empty response body');
                return array();
            }
            
            // HTML parsen und Ergebnisse extrahieren
            $results = $this->parse_google_results($body, $max_results);
            
            return $results;
        }
        
        /**
         * Google Search URL erstellen
         *
         * @param string $keyword
         * @param string $location
         * @return string
         */
        private function build_google_search_url($keyword, $location) {
            $base_url = 'https://www.google.com/search';
            $params = array(
                'q' => urlencode($keyword),
                'num' => 20, // Mehr Ergebnisse für bessere Analyse
                'hl' => 'de', // Deutsche Sprache
                'lr' => 'lang_de',
                'safe' => 'off'
            );
            
            // Standort-spezifische Parameter
            if ($location === 'CH') {
                $params['gl'] = 'ch'; // Schweiz
            } elseif ($location === 'DE') {
                $params['gl'] = 'de'; // Deutschland
            } elseif ($location === 'AT') {
                $params['gl'] = 'at'; // Österreich
            }
            
            return $base_url . '?' . http_build_query($params);
        }
        
        /**
         * Google HTML-Ergebnisse parsen
         *
         * @param string $html
         * @param int $max_results
         * @return array
         */
        private function parse_google_results($html, $max_results) {
            $results = array();
            
            // DOMDocument verwenden für HTML-Parsing
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            libxml_clear_errors();
            
            $xpath = new DOMXPath($dom);
            
            // Google Organic Results (div.g)
            $result_nodes = $xpath->query('//div[@class="g"]');
            
            $count = 0;
            foreach ($result_nodes as $node) {
                if ($count >= $max_results) break;
                
                $result = $this->extract_result_data($node, $xpath);
                if (!empty($result['title']) && !empty($result['url'])) {
                    $results[] = $result;
                    $count++;
                }
            }
            
            return $results;
        }
        
        /**
         * Einzelnes Suchergebnis extrahieren
         *
         * @param DOMElement $node
         * @param DOMXPath $xpath
         * @return array
         */
        private function extract_result_data($node, $xpath) {
            $result = array(
                'position' => 0,
                'title' => '',
                'url' => '',
                'description' => '',
                'domain' => '',
                'meta_title' => '',
                'meta_description' => ''
            );
            
            // Titel extrahieren
            $title_node = $xpath->query('.//h3', $node)->item(0);
            if ($title_node) {
                $result['title'] = trim($title_node->textContent);
            }
            
            // URL extrahieren
            $link_node = $xpath->query('.//a[@href]', $node)->item(0);
            if ($link_node) {
                $href = $link_node->getAttribute('href');
                if (strpos($href, '/url?q=') === 0) {
                    $href = urldecode(substr($href, 7));
                    $href = explode('&', $href)[0];
                }
                $result['url'] = $href;
                $result['domain'] = parse_url($href, PHP_URL_HOST);
            }
            
            // Beschreibung extrahieren
            $desc_nodes = $xpath->query('.//span[@class="st"] | .//div[@class="VwiC3b"]', $node);
            if ($desc_nodes->length > 0) {
                $result['description'] = trim($desc_nodes->item(0)->textContent);
            }
            
            // Meta-Daten als Titel und Beschreibung verwenden (falls verfügbar)
            $result['meta_title'] = $result['title'];
            $result['meta_description'] = $result['description'];
            
            return $result;
        }
        
        /**
         * Meta-Tags der Konkurrenz analysieren
         *
         * @param array $results
         * @return array
         */
        private function analyze_meta_tags($results) {
            $meta_analysis = array(
                'title_lengths' => array(),
                'description_lengths' => array(),
                'avg_title_length' => 0,
                'avg_description_length' => 0,
                'title_patterns' => array(),
                'description_patterns' => array(),
                'common_words_titles' => array(),
                'common_words_descriptions' => array()
            );
            
            $all_titles = array();
            $all_descriptions = array();
            
            foreach ($results as $result) {
                if (!empty($result['title'])) {
                    $title_length = strlen($result['title']);
                    $meta_analysis['title_lengths'][] = $title_length;
                    $all_titles[] = $result['title'];
                }
                
                if (!empty($result['description'])) {
                    $desc_length = strlen($result['description']);
                    $meta_analysis['description_lengths'][] = $desc_length;
                    $all_descriptions[] = $result['description'];
                }
            }
            
            // Durchschnittliche Längen berechnen
            if (!empty($meta_analysis['title_lengths'])) {
                $meta_analysis['avg_title_length'] = round(array_sum($meta_analysis['title_lengths']) / count($meta_analysis['title_lengths']), 2);
            }
            
            if (!empty($meta_analysis['description_lengths'])) {
                $meta_analysis['avg_description_length'] = round(array_sum($meta_analysis['description_lengths']) / count($meta_analysis['description_lengths']), 2);
            }
            
            // Häufige Wörter in Titeln
            $meta_analysis['common_words_titles'] = $this->extract_common_words($all_titles);
            
            // Häufige Wörter in Beschreibungen
            $meta_analysis['common_words_descriptions'] = $this->extract_common_words($all_descriptions);
            
            return $meta_analysis;
        }
        
        /**
         * Keyword-Verwendung analysieren
         *
         * @param array $results
         * @param string $keyword
         * @return array
         */
        private function analyze_keyword_usage($results, $keyword) {
            $keyword_analysis = array(
                'keyword' => $keyword,
                'keyword_variations' => $this->generate_keyword_variations($keyword),
                'title_usage' => array(),
                'description_usage' => array(),
                'url_usage' => array(),
                'semantic_keywords' => array(),
                'lsi_keywords' => array()
            );
            
            $keyword_lower = strtolower($keyword);
            $keyword_words = explode(' ', $keyword_lower);
            
            foreach ($results as $index => $result) {
                $position = $index + 1;
                
                // Titel-Analyse
                $title_lower = strtolower($result['title'] ?? '');
                $title_keyword_count = substr_count($title_lower, $keyword_lower);
                $title_exact_match = strpos($title_lower, $keyword_lower) !== false;
                
                $keyword_analysis['title_usage'][] = array(
                    'position' => $position,
                    'title' => $result['title'],
                    'keyword_count' => $title_keyword_count,
                    'exact_match' => $title_exact_match,
                    'keyword_position' => $title_exact_match ? strpos($title_lower, $keyword_lower) : -1
                );
                
                // Beschreibung-Analyse
                $desc_lower = strtolower($result['description'] ?? '');
                $desc_keyword_count = substr_count($desc_lower, $keyword_lower);
                
                $keyword_analysis['description_usage'][] = array(
                    'position' => $position,
                    'description' => $result['description'],
                    'keyword_count' => $desc_keyword_count,
                    'has_keyword' => $desc_keyword_count > 0
                );
                
                // URL-Analyse
                $url_lower = strtolower($result['url'] ?? '');
                $url_has_keyword = false;
                foreach ($keyword_words as $word) {
                    if (strpos($url_lower, $word) !== false) {
                        $url_has_keyword = true;
                        break;
                    }
                }
                
                $keyword_analysis['url_usage'][] = array(
                    'position' => $position,
                    'url' => $result['url'],
                    'has_keyword' => $url_has_keyword
                );
            }
            
            // Semantische Keywords extrahieren
            $keyword_analysis['semantic_keywords'] = $this->extract_semantic_keywords($results, $keyword);
            
            // LSI Keywords generieren
            $keyword_analysis['lsi_keywords'] = $this->generate_lsi_keywords($results, $keyword);
            
            return $keyword_analysis;
        }
        
        /**
         * Content-Gaps identifizieren
         *
         * @param array $results
         * @return array
         */
        private function identify_content_gaps($results) {
            $gaps = array(
                'missing_topics' => array(),
                'content_opportunities' => array(),
                'uniqueness_angles' => array(),
                'featured_snippets' => array(),
                'question_based_content' => array()
            );
            
            // Häufige Themen in Top-Ergebnissen identifizieren
            $common_themes = $this->identify_common_themes($results);
            
            // Fehlende Themen identifizieren
            $gaps['missing_topics'] = $this->find_missing_topics($common_themes);
            
            // Content-Opportunities
            $gaps['content_opportunities'] = $this->identify_content_opportunities($results);
            
            // Einzigartige Ansätze
            $gaps['uniqueness_angles'] = $this->find_uniqueness_angles($results);
            
            return $gaps;
        }
        
        /**
         * SERP-Features analysieren
         *
         * @param array $results
         * @return array
         */
        private function analyze_serp_features($results) {
            $features = array(
                'featured_snippets' => array(),
                'knowledge_panel' => false,
                'local_pack' => array(),
                'images' => array(),
                'videos' => array(),
                'news' => array(),
                'shopping' => array(),
                'people_also_ask' => array(),
                'related_searches' => array()
            );
            
            // Vereinfachte SERP-Feature-Erkennung
            // In einer echten Implementierung würde hier der HTML-Code nach spezifischen Elementen durchsucht
            
            return $features;
        }
        
        /**
         * Competitor-Insights generieren
         *
         * @param array $results
         * @param string $keyword
         * @return array
         */
        private function generate_competitor_insights($results, $keyword) {
            $insights = array(
                'top_domains' => array(),
                'domain_authority_estimate' => array(),
                'content_length_insights' => array(),
                'backlink_opportunities' => array(),
                'competitor_strategies' => array()
            );
            
            // Top-Domains identifizieren
            $domain_count = array();
            foreach ($results as $result) {
                $domain = $result['domain'] ?? '';
                if (!empty($domain)) {
                    $domain_count[$domain] = ($domain_count[$domain] ?? 0) + 1;
                }
            }
            
            arsort($domain_count);
            $insights['top_domains'] = array_slice($domain_count, 0, 10, true);
            
            // Content-Längen-Insights
            $title_lengths = array();
            $desc_lengths = array();
            
            foreach ($results as $result) {
                if (!empty($result['title'])) {
                    $title_lengths[] = strlen($result['title']);
                }
                if (!empty($result['description'])) {
                    $desc_lengths[] = strlen($result['description']);
                }
            }
            
            $insights['content_length_insights'] = array(
                'avg_title_length' => !empty($title_lengths) ? round(array_sum($title_lengths) / count($title_lengths)) : 0,
                'avg_description_length' => !empty($desc_lengths) ? round(array_sum($desc_lengths) / count($desc_lengths)) : 0,
                'title_length_range' => array(
                    'min' => !empty($title_lengths) ? min($title_lengths) : 0,
                    'max' => !empty($title_lengths) ? max($title_lengths) : 0
                ),
                'description_length_range' => array(
                    'min' => !empty($desc_lengths) ? min($desc_lengths) : 0,
                    'max' => !empty($desc_lengths) ? max($desc_lengths) : 0
                )
            );
            
            return $insights;
        }
        
        /**
         * SEO-Empfehlungen generieren
         *
         * @param array $analysis
         * @return array
         */
        private function generate_recommendations($analysis) {
            $recommendations = array();
            
            // Meta-Titel-Empfehlungen
            if ($analysis['meta_analysis']['avg_title_length'] > 0) {
                $recommendations[] = array(
                    'type' => 'meta_title',
                    'priority' => 'high',
                    'title' => 'Meta-Titel optimieren',
                    'message' => 'Durchschnittliche Titel-Länge der Top-Ergebnisse: ' . $analysis['meta_analysis']['avg_title_length'] . ' Zeichen. Empfohlen: 50-60 Zeichen.',
                    'action' => 'Titel-Länge anpassen'
                );
            }
            
            // Meta-Beschreibung-Empfehlungen
            if ($analysis['meta_analysis']['avg_description_length'] > 0) {
                $recommendations[] = array(
                    'type' => 'meta_description',
                    'priority' => 'high',
                    'title' => 'Meta-Beschreibung optimieren',
                    'message' => 'Durchschnittliche Beschreibungs-Länge der Top-Ergebnisse: ' . $analysis['meta_analysis']['avg_description_length'] . ' Zeichen. Empfohlen: 150-160 Zeichen.',
                    'action' => 'Beschreibung-Länge anpassen'
                );
            }
            
            // Keyword-Empfehlungen
            if (!empty($analysis['keyword_analysis']['semantic_keywords'])) {
                $recommendations[] = array(
                    'type' => 'keywords',
                    'priority' => 'medium',
                    'title' => 'Semantische Keywords verwenden',
                    'message' => 'Verwenden Sie diese verwandten Keywords: ' . implode(', ', array_slice($analysis['keyword_analysis']['semantic_keywords'], 0, 5)),
                    'action' => 'Verwandte Keywords in Content integrieren'
                );
            }
            
            // Content-Gap-Empfehlungen
            if (!empty($analysis['content_gaps']['missing_topics'])) {
                $recommendations[] = array(
                    'type' => 'content_gaps',
                    'priority' => 'medium',
                    'title' => 'Content-Lücken füllen',
                    'message' => 'Diese Themen werden von der Konkurrenz abgedeckt: ' . implode(', ', array_slice($analysis['content_gaps']['missing_topics'], 0, 3)),
                    'action' => 'Fehlende Themen in Content aufgreifen'
                );
            }
            
            return $recommendations;
        }
        
        /**
         * Hilfsmethoden
         */
        
        private function extract_common_words($texts) {
            $all_words = array();
            
            foreach ($texts as $text) {
                $words = str_word_count(strtolower($text), 1);
                $all_words = array_merge($all_words, $words);
            }
            
            // Stoppwörter entfernen
            $stopwords = array('der', 'die', 'das', 'und', 'in', 'zu', 'den', 'von', 'mit', 'ist', 'auf', 'für', 'an', 'als', 'eine', 'ein', 'dem', 'des', 'im', 'am');
            $all_words = array_diff($all_words, $stopwords);
            
            // Wörter mit weniger als 3 Zeichen entfernen
            $all_words = array_filter($all_words, function($word) {
                return strlen($word) >= 3;
            });
            
            $word_count = array_count_values($all_words);
            arsort($word_count);
            
            return array_slice($word_count, 0, 10, true);
        }
        
        private function generate_keyword_variations($keyword) {
            $variations = array($keyword);
            
            // Einfache Variationen
            $words = explode(' ', $keyword);
            
            if (count($words) > 1) {
                // Wort-Reihenfolge ändern
                $variations[] = implode(' ', array_reverse($words));
                
                // Singular/Plural (vereinfacht)
                $variations[] = str_replace(array('e', 'en'), array('', ''), $keyword);
            }
            
            return array_unique($variations);
        }
        
        private function extract_semantic_keywords($results, $keyword) {
            $semantic_keywords = array();
            
            foreach ($results as $result) {
                $text = strtolower($result['title'] . ' ' . $result['description']);
                $words = str_word_count($text, 1);
                
                foreach ($words as $word) {
                    if (strlen($word) >= 4 && $word !== strtolower($keyword)) {
                        $semantic_keywords[] = $word;
                    }
                }
            }
            
            $semantic_count = array_count_values($semantic_keywords);
            arsort($semantic_count);
            
            return array_keys(array_slice($semantic_count, 0, 10, true));
        }
        
        private function generate_lsi_keywords($results, $keyword) {
            // Vereinfachte LSI-Keyword-Generierung
            // In einer echten Implementierung würde hier eine semantische Analyse durchgeführt
            
            $lsi_keywords = array();
            $keyword_words = explode(' ', strtolower($keyword));
            
            foreach ($results as $result) {
                $text = strtolower($result['title'] . ' ' . $result['description']);
                
                // Wörter finden, die häufig mit dem Keyword auftreten
                $words = str_word_count($text, 1);
                foreach ($words as $word) {
                    if (strlen($word) >= 4 && !in_array($word, $keyword_words)) {
                        $lsi_keywords[] = $word;
                    }
                }
            }
            
            $lsi_count = array_count_values($lsi_keywords);
            arsort($lsi_count);
            
            return array_keys(array_slice($lsi_count, 0, 8, true));
        }
        
        private function identify_common_themes($results) {
            $themes = array();
            
            foreach ($results as $result) {
                $text = strtolower($result['title'] . ' ' . $result['description']);
                
                // Einfache Theme-Erkennung basierend auf häufigen Wörtern
                $words = str_word_count($text, 1);
                $words = array_filter($words, function($word) {
                    return strlen($word) >= 4;
                });
                
                $themes = array_merge($themes, $words);
            }
            
            return array_count_values($themes);
        }
        
        private function find_missing_topics($common_themes) {
            // Vereinfachte Implementierung
            // In einer echten Anwendung würde hier mit dem eigenen Content verglichen
            return array_keys(array_slice($common_themes, 0, 5, true));
        }
        
        private function identify_content_opportunities($results) {
            $opportunities = array();
            
            foreach ($results as $result) {
                if (strpos(strtolower($result['description']), 'wie') !== false ||
                    strpos(strtolower($result['description']), 'was') !== false ||
                    strpos(strtolower($result['description']), 'warum') !== false) {
                    $opportunities[] = 'FAQ-Content möglich';
                }
            }
            
            return array_unique($opportunities);
        }
        
        private function find_uniqueness_angles($results) {
            $angles = array();
            
            // Einzigartige Ansätze identifizieren
            $angles[] = 'Lokaler Fokus verstärken';
            $angles[] = 'Persönliche Erfahrungen einbauen';
            $angles[] = 'Aktuelle Trends aufgreifen';
            $angles[] = 'Interaktive Elemente hinzufügen';
            
            return $angles;
        }
    }
}
