<?php
/**
 * ReTexify German Text Processor
 * 
 * Spezialisiert auf deutsche Textverarbeitung und -normalisierung
 * 
 * @package ReTexify_AI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_German_Text_Processor {
    
    /**
     * Deutsche Stop-Wörter
     */
    private static $german_stop_words = array(
        'der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines', 'einem', 'einen',
        'und', 'oder', 'aber', 'für', 'mit', 'von', 'aus', 'bei', 'seit', 'nach', 'zu', 'zur', 'zum',
        'in', 'an', 'auf', 'über', 'unter', 'hinter', 'neben', 'zwischen', 'durch', 'um', 'gegen',
        'ohne', 'bis', 'ab', 'von', 'aus', 'bei', 'mit', 'nach', 'seit', 'von', 'zu', 'durch',
        'für', 'um', 'gegen', 'ohne', 'wider', 'entlang', 'außer', 'neben', 'zwischen',
        'ist', 'sind', 'war', 'waren', 'wird', 'werden', 'hat', 'haben', 'hatte', 'hatten',
        'kann', 'können', 'muss', 'müssen', 'soll', 'sollen', 'will', 'wollen', 'mag', 'mögen',
        'darf', 'dürfen', 'möchte', 'möchten', 'sollte', 'sollten', 'könnte', 'könnten',
        'würde', 'würden', 'hätte', 'hätten', 'wäre', 'wären', 'wird', 'werden',
        'ich', 'du', 'er', 'sie', 'es', 'wir', 'ihr', 'sie', 'mich', 'dich', 'sich',
        'mir', 'dir', 'uns', 'euch', 'mein', 'dein', 'sein', 'ihr', 'unser', 'euer',
        'meine', 'deine', 'seine', 'ihre', 'unsere', 'eure', 'meinen', 'deinen', 'seinen',
        'ihren', 'unseren', 'euren', 'meinem', 'deinem', 'seinem', 'ihrem', 'unserem', 'eurem',
        'meines', 'deines', 'seines', 'ihres', 'unseres', 'eures', 'meiner', 'deiner', 'seiner',
        'ihrer', 'unserer', 'eurer', 'dieser', 'diese', 'dieses', 'diesen', 'diesem', 'dieser',
        'jener', 'jene', 'jenes', 'jenen', 'jenem', 'jener', 'welcher', 'welche', 'welches',
        'welchen', 'welchem', 'welcher', 'alle', 'alles', 'allen', 'allem', 'aller',
        'manche', 'manches', 'manchen', 'manchem', 'mancher', 'viele', 'vieles', 'vielen',
        'vielem', 'vieler', 'wenige', 'weniges', 'wenigen', 'wenigem', 'weniger',
        'einige', 'einiges', 'einigen', 'einigem', 'einiger', 'keine', 'keines', 'keinen',
        'keinem', 'keiner', 'nicht', 'nichts', 'niemand', 'niemandem', 'niemanden',
        'etwas', 'etwas', 'etwas', 'etwas', 'etwas', 'nichts', 'nichts', 'nichts',
        'alles', 'alles', 'alles', 'alles', 'alles', 'manches', 'manches', 'manches',
        'vieles', 'vieles', 'vieles', 'vieles', 'vieles', 'weniges', 'weniges', 'weniges',
        'einiges', 'einiges', 'einiges', 'einiges', 'einiges', 'keines', 'keines', 'keines'
    );
    
    /**
     * Text verarbeiten
     * 
     * @param string $content Roher Content
     * @return array Verarbeiteter Text mit verschiedenen Ansichten
     */
    public function process_text($content) {
        $processed = array(
            'raw' => $content,
            'normalized' => $this->normalize_german_text($content),
            'sentences' => $this->extract_sentences($content),
            'paragraphs' => $this->extract_paragraphs($content),
            'words' => $this->extract_meaningful_words($content),
            'word_count' => str_word_count($content),
            'character_count' => strlen($content)
        );
        
        return $processed;
    }
    
    /**
     * Deutschen Text normalisieren
     * 
     * @param string $text Roher Text
     * @return string Normalisierter Text
     */
    public function normalize_german_text($text) {
        // HTML-Tags entfernen
        $text = strip_tags($text);
        
        // Umlaute normalisieren
        $text = str_replace(array('ä', 'ö', 'ü', 'ß'), array('ae', 'oe', 'ue', 'ss'), $text);
        
        // Mehrfache Leerzeichen entfernen
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Zeilenumbrüche normalisieren
        $text = str_replace(array("\r\n", "\r", "\n"), ' ', $text);
        
        // Trim
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Sätze extrahieren
     * 
     * @param string $text Text
     * @return array Sätze
     */
    public function extract_sentences($text) {
        // Deutsche Satzzeichen berücksichtigen
        $sentences = preg_split('/[.!?]+/', $text);
        
        $clean_sentences = array();
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (!empty($sentence) && strlen($sentence) > 10) {
                $clean_sentences[] = $sentence;
            }
        }
        
        return $clean_sentences;
    }
    
    /**
     * Absätze extrahieren
     * 
     * @param string $text Text
     * @return array Absätze
     */
    public function extract_paragraphs($text) {
        // HTML-Paragraphs berücksichtigen
        $paragraphs = preg_split('/<\/?p[^>]*>/', $text);
        
        $clean_paragraphs = array();
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph) && strlen($paragraph) > 20) {
                $clean_paragraphs[] = $paragraph;
            }
        }
        
        return $clean_paragraphs;
    }
    
    /**
     * Bedeutungsvolle Wörter extrahieren
     * 
     * @param string $text Text
     * @return array Wörter
     */
    public function extract_meaningful_words($text) {
        // Text normalisieren
        $normalized = $this->normalize_german_text($text);
        
        // Wörter extrahieren
        $words = preg_split('/\s+/', $normalized);
        
        // Stop-Wörter entfernen und filtern
        $meaningful_words = array();
        foreach ($words as $word) {
            $word = strtolower(trim($word));
            
            // Nur Wörter mit mindestens 3 Zeichen
            if (strlen($word) >= 3) {
                // Stop-Wörter überspringen
                if (!in_array($word, self::$german_stop_words)) {
                    // Nur Buchstaben erlauben
                    if (preg_match('/^[a-zäöüß]+$/i', $word)) {
                        $meaningful_words[] = $word;
                    }
                }
            }
        }
        
        return $meaningful_words;
    }
    
    /**
     * Deutsche Silben zählen
     * 
     * @param string $word Wort
     * @return int Anzahl Silben
     */
    public function count_german_syllables($word) {
        $word = strtolower($word);
        
        // Vokale zählen
        $vowels = preg_match_all('/[aeiouäöü]/', $word);
        
        // Mindestens eine Silbe
        return max(1, $vowels);
    }
    
    /**
     * Deutsche Komposita erkennen
     * 
     * @param array $words Wörter
     * @return array Komposita
     */
    public function detect_german_compounds($words) {
        $compounds = array();
        
        foreach ($words as $word) {
            // Wörter mit mehreren Teilen (durch Großbuchstaben getrennt)
            if (preg_match('/[A-Z][a-z]+[A-Z]/', $word)) {
                $compounds[] = $word;
            }
            
            // Lange Wörter (wahrscheinlich Komposita)
            if (strlen($word) > 12) {
                $compounds[] = $word;
            }
        }
        
        return array_unique($compounds);
    }
    
    /**
     * Text-Komplexität bewerten
     * 
     * @param string $text Text
     * @return array Komplexitäts-Scores
     */
    public function assess_text_complexity($text) {
        $words = $this->extract_meaningful_words($text);
        $sentences = $this->extract_sentences($text);
        
        $complexity = array(
            'average_word_length' => 0,
            'average_sentence_length' => 0,
            'syllable_count' => 0,
            'complexity_score' => 0
        );
        
        if (!empty($words)) {
            $total_length = 0;
            $total_syllables = 0;
            
            foreach ($words as $word) {
                $total_length += strlen($word);
                $total_syllables += $this->count_german_syllables($word);
            }
            
            $complexity['average_word_length'] = $total_length / count($words);
            $complexity['syllable_count'] = $total_syllables;
        }
        
        if (!empty($sentences)) {
            $complexity['average_sentence_length'] = count($words) / count($sentences);
        }
        
        // Komplexitäts-Score berechnen
        $complexity['complexity_score'] = 
            ($complexity['average_word_length'] * 0.3) +
            ($complexity['average_sentence_length'] * 0.4) +
            (($complexity['syllable_count'] / count($words)) * 0.3);
        
        return $complexity;
    }
    
    // ===== ADVANCED SEO SCORE FEATURES - NEUE METHODEN =====
    
    /**
     * Erweiterte SEO-Score Berechnung
     * 
     * @param string $content Content zu analysieren
     * @param string $focus_keyword Fokus-Keyword
     * @param array $settings Plugin-Settings
     * @return array Detaillierter SEO-Score
     */
    public function calculate_advanced_seo_score($content, $focus_keyword = '', $settings = array()) {
        $seo_score = array(
            'overall_score' => 0,
            'keyword_optimization' => array('score' => 0, 'max' => 25, 'details' => array()),
            'content_quality' => array('score' => 0, 'max' => 25, 'details' => array()),
            'technical_seo' => array('score' => 0, 'max' => 25, 'details' => array()),
            'user_experience' => array('score' => 0, 'max' => 25, 'details' => array()),
            'recommendations' => array(),
            'timestamp' => current_time('mysql')
        );
        
        // 1. Keyword-Optimierung (25 Punkte)
        $seo_score['keyword_optimization'] = $this->analyze_keyword_optimization($content, $focus_keyword);
        
        // 2. Content-Qualität (25 Punkte)
        $seo_score['content_quality'] = $this->analyze_content_quality($content, $settings);
        
        // 3. Technische SEO (25 Punkte)
        $seo_score['technical_seo'] = $this->analyze_technical_seo($content, $settings);
        
        // 4. User Experience (25 Punkte)
        $seo_score['user_experience'] = $this->analyze_user_experience($content, $settings);
        
        // Gesamt-Score berechnen
        $seo_score['overall_score'] = 
            $seo_score['keyword_optimization']['score'] +
            $seo_score['content_quality']['score'] +
            $seo_score['technical_seo']['score'] +
            $seo_score['user_experience']['score'];
        
        // Empfehlungen generieren
        $seo_score['recommendations'] = $this->generate_seo_recommendations($seo_score, $focus_keyword);
        
        return $seo_score;
    }
    
    /**
     * Keyword-Optimierung analysieren
     * 
     * @param string $content
     * @param string $focus_keyword
     * @return array
     */
    private function analyze_keyword_optimization($content, $focus_keyword) {
        $analysis = array(
            'score' => 0,
            'max' => 25,
            'details' => array()
        );
        
        if (empty($focus_keyword)) {
            $analysis['details'][] = 'Kein Fokus-Keyword definiert';
            return $analysis;
        }
        
        $keyword_lower = strtolower($focus_keyword);
        $content_lower = strtolower($content);
        $word_count = str_word_count($content);
        
        // Keyword im Titel (8 Punkte)
        $title = get_the_title();
        if (!empty($title) && stripos($title, $focus_keyword) !== false) {
            $analysis['score'] += 8;
            $analysis['details'][] = 'Keyword im Titel vorhanden';
        } else {
            $analysis['details'][] = 'Keyword fehlt im Titel';
        }
        
        // Keyword in der Beschreibung (5 Punkte)
        $excerpt = get_the_excerpt();
        if (!empty($excerpt) && stripos($excerpt, $focus_keyword) !== false) {
            $analysis['score'] += 5;
            $analysis['details'][] = 'Keyword in der Beschreibung vorhanden';
        } else {
            $analysis['details'][] = 'Keyword fehlt in der Beschreibung';
        }
        
        // Keyword im ersten Absatz (5 Punkte)
        $first_paragraph = $this->get_first_paragraph($content);
        if (stripos($first_paragraph, $focus_keyword) !== false) {
            $analysis['score'] += 5;
            $analysis['details'][] = 'Keyword im ersten Absatz vorhanden';
        } else {
            $analysis['details'][] = 'Keyword fehlt im ersten Absatz';
        }
        
        // Keyword-Dichte (7 Punkte)
        $keyword_count = substr_count($content_lower, $keyword_lower);
        $keyword_density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;
        
        if ($keyword_density >= 0.5 && $keyword_density <= 3) {
            $analysis['score'] += 7;
            $analysis['details'][] = 'Optimale Keyword-Dichte: ' . round($keyword_density, 2) . '%';
        } elseif ($keyword_density < 0.5) {
            $analysis['details'][] = 'Keyword-Dichte zu niedrig: ' . round($keyword_density, 2) . '%';
        } else {
            $analysis['details'][] = 'Keyword-Dichte zu hoch: ' . round($keyword_density, 2) . '%';
        }
        
        return $analysis;
    }
    
    /**
     * Content-Qualität analysieren
     * 
     * @param string $content
     * @param array $settings
     * @return array
     */
    private function analyze_content_quality($content, $settings) {
        $analysis = array(
            'score' => 0,
            'max' => 25,
            'details' => array()
        );
        
        $word_count = str_word_count($content);
        
        // Content-Länge (10 Punkte)
        if ($word_count >= 300) {
            $analysis['score'] += 5;
            $analysis['details'][] = 'Mindestlänge erreicht (' . $word_count . ' Wörter)';
        }
        if ($word_count >= 600) {
            $analysis['score'] += 3;
            $analysis['details'][] = 'Gute Content-Länge (' . $word_count . ' Wörter)';
        }
        if ($word_count >= 1000) {
            $analysis['score'] += 2;
            $analysis['details'][] = 'Ausgezeichnete Content-Länge (' . $word_count . ' Wörter)';
        }
        
        // Überschriften-Struktur (8 Punkte)
        $heading_score = $this->analyze_heading_structure($content);
        $analysis['score'] += $heading_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $heading_score['details']);
        
        // Lesbarkeit (7 Punkte)
        $readability_score = $this->analyze_readability($content);
        $analysis['score'] += $readability_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $readability_score['details']);
        
        return $analysis;
    }
    
    /**
     * Technische SEO analysieren
     * 
     * @param string $content
     * @param array $settings
     * @return array
     */
    private function analyze_technical_seo($content, $settings) {
        $analysis = array(
            'score' => 0,
            'max' => 25,
            'details' => array()
        );
        
        // Bilder und Alt-Texte (8 Punkte)
        $image_score = $this->analyze_images($content);
        $analysis['score'] += $image_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $image_score['details']);
        
        // Interne Links (7 Punkte)
        $link_score = $this->analyze_internal_links($content);
        $analysis['score'] += $link_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $link_score['details']);
        
        // Meta-Informationen (10 Punkte)
        $meta_score = $this->analyze_meta_information();
        $analysis['score'] += $meta_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $meta_score['details']);
        
        return $analysis;
    }
    
    /**
     * User Experience analysieren
     * 
     * @param string $content
     * @param array $settings
     * @return array
     */
    private function analyze_user_experience($content, $settings) {
        $analysis = array(
            'score' => 0,
            'max' => 25,
            'details' => array()
        );
        
        // Content-Struktur (10 Punkte)
        $structure_score = $this->analyze_content_structure($content);
        $analysis['score'] += $structure_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $structure_score['details']);
        
        // Engagement-Faktoren (8 Punkte)
        $engagement_score = $this->analyze_engagement_factors($content);
        $analysis['score'] += $engagement_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $engagement_score['details']);
        
        // Mobile-Freundlichkeit (7 Punkte)
        $mobile_score = $this->analyze_mobile_friendliness($content);
        $analysis['score'] += $mobile_score['score'];
        $analysis['details'] = array_merge($analysis['details'], $mobile_score['details']);
        
        return $analysis;
    }
    
    // Hilfsmethoden für SEO-Score Berechnung
    private function analyze_heading_structure($content) {
        $score = 0;
        $details = array();
        
        preg_match_all('/<h1[^>]*>(.*?)<\/h1>/i', $content, $h1_matches);
        $h1_count = count($h1_matches[0]);
        
        if ($h1_count == 1) {
            $score += 4;
            $details[] = 'Eine H1-Überschrift vorhanden (optimal)';
        } elseif ($h1_count > 1) {
            $details[] = 'Mehrere H1-Überschriften (' . $h1_count . ') - nur eine empfohlen';
        } else {
            $details[] = 'Keine H1-Überschrift vorhanden';
        }
        
        preg_match_all('/<h2[^>]*>(.*?)<\/h2>/i', $content, $h2_matches);
        $h2_count = count($h2_matches[0]);
        
        if ($h2_count >= 2) {
            $score += 2;
            $details[] = 'Ausreichend H2-Überschriften (' . $h2_count . ')';
        } else {
            $details[] = 'Zu wenige H2-Überschriften (' . $h2_count . ')';
        }
        
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>/i', $content, $h3_matches);
        $h3_count = count($h3_matches[0]);
        
        if ($h3_count >= 1) {
            $score += 2;
            $details[] = 'H3-Überschriften für Struktur vorhanden (' . $h3_count . ')';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_readability($content) {
        $score = 0;
        $details = array();
        
        $clean_content = wp_strip_all_tags($content);
        $sentences = preg_split('/[.!?]+/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
        $words = str_word_count($clean_content);
        
        if (count($sentences) > 0) {
            $avg_sentence_length = $words / count($sentences);
            
            if ($avg_sentence_length <= 15) {
                $score += 4;
                $details[] = 'Kurze, verständliche Sätze (' . round($avg_sentence_length, 1) . ' Wörter/Satz)';
            } elseif ($avg_sentence_length <= 20) {
                $score += 2;
                $details[] = 'Mittlere Satzlänge (' . round($avg_sentence_length, 1) . ' Wörter/Satz)';
            } else {
                $details[] = 'Lange Sätze (' . round($avg_sentence_length, 1) . ' Wörter/Satz) - schwerer lesbar';
            }
        }
        
        $paragraphs = preg_split('/\n\s*\n/', $clean_content, -1, PREG_SPLIT_NO_EMPTY);
        if (count($paragraphs) > 0) {
            $avg_paragraph_length = $words / count($paragraphs);
            
            if ($avg_paragraph_length <= 100) {
                $score += 3;
                $details[] = 'Gute Absatz-Länge (' . round($avg_paragraph_length, 1) . ' Wörter/Absatz)';
            } else {
                $details[] = 'Lange Absätze (' . round($avg_paragraph_length, 1) . ' Wörter/Absatz)';
            }
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_images($content) {
        $score = 0;
        $details = array();
        
        preg_match_all('/<img[^>]+>/i', $content, $images);
        $image_count = count($images[0]);
        
        if ($image_count > 0) {
            $score += 2;
            
            preg_match_all('/alt=["\']([^"\']*)["\']/', $content, $alt_texts);
            $alt_count = count($alt_texts[1]);
            $images_with_alt = 0;
            
            foreach ($alt_texts[1] as $alt_text) {
                if (!empty(trim($alt_text))) {
                    $images_with_alt++;
                }
            }
            
            if ($images_with_alt == $image_count) {
                $score += 6;
                $details[] = 'Alle Bilder haben Alt-Texte (' . $image_count . ' Bilder)';
            } else {
                $score += ($images_with_alt / $image_count) * 6;
                $details[] = 'Nur ' . $images_with_alt . ' von ' . $image_count . ' Bildern haben Alt-Texte';
            }
        } else {
            $details[] = 'Keine Bilder vorhanden';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_internal_links($content) {
        $score = 0;
        $details = array();
        
        preg_match_all('/<a[^>]+href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/i', $content, $links);
        $total_links = count($links[1]);
        
        if ($total_links > 0) {
            $site_url = get_site_url();
            $internal_links = 0;
            $links_with_text = 0;
            
            foreach ($links[1] as $index => $url) {
                if (strpos($url, $site_url) === 0 || strpos($url, '/') === 0) {
                    $internal_links++;
                }
                
                $link_text = wp_strip_all_tags($links[2][$index]);
                if (!empty(trim($link_text))) {
                    $links_with_text++;
                }
            }
            
            if ($internal_links >= 2) {
                $score += 4;
                $details[] = 'Ausreichend interne Links (' . $internal_links . ')';
            } else {
                $details[] = 'Zu wenige interne Links (' . $internal_links . ')';
            }
            
            if ($links_with_text == $total_links) {
                $score += 3;
                $details[] = 'Alle Links haben aussagekräftigen Text';
            } else {
                $details[] = 'Nur ' . $links_with_text . ' von ' . $total_links . ' Links haben Text';
            }
        } else {
            $details[] = 'Keine Links vorhanden';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_meta_information() {
        $score = 0;
        $details = array();
        
        $title = get_the_title();
        $title_length = strlen($title);
        
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 4;
            $details[] = 'Optimale Titel-Länge (' . $title_length . ' Zeichen)';
        } else {
            $details[] = 'Titel-Länge nicht optimal (' . $title_length . ' Zeichen)';
        }
        
        $excerpt = get_the_excerpt();
        $desc_length = strlen($excerpt);
        
        if ($desc_length >= 140 && $desc_length <= 160) {
            $score += 3;
            $details[] = 'Optimale Beschreibungs-Länge (' . $desc_length . ' Zeichen)';
        } else {
            $details[] = 'Beschreibungs-Länge nicht optimal (' . $desc_length . ' Zeichen)';
        }
        
        $score += 3;
        $details[] = 'Meta-Informationen konfiguriert';
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_content_structure($content) {
        $score = 0;
        $details = array();
        
        preg_match_all('/<[uo]l[^>]*>(.*?)<\/[uo]l>/i', $content, $lists);
        if (count($lists[0]) > 0) {
            $score += 3;
            $details[] = 'Listen für bessere Struktur vorhanden (' . count($lists[0]) . ')';
        }
        
        preg_match_all('/<table[^>]*>(.*?)<\/table>/i', $content, $tables);
        if (count($tables[0]) > 0) {
            $score += 2;
            $details[] = 'Tabellen für Datenstruktur vorhanden';
        }
        
        preg_match_all('/<blockquote[^>]*>(.*?)<\/blockquote>/i', $content, $quotes);
        if (count($quotes[0]) > 0) {
            $score += 2;
            $details[] = 'Zitate für Hervorhebung vorhanden';
        }
        
        $paragraphs = preg_split('/\n\s*\n/', wp_strip_all_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        if (count($paragraphs) >= 3) {
            $score += 3;
            $details[] = 'Gute Absatz-Struktur (' . count($paragraphs) . ' Absätze)';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_engagement_factors($content) {
        $score = 0;
        $details = array();
        
        $cta_patterns = array(
            '/kontaktieren/i', '/kontakt/i', '/anrufen/i', '/bestellen/i',
            '/kaufen/i', '/mehr erfahren/i', '/jetzt/i', '/hier/i'
        );
        
        $cta_found = false;
        foreach ($cta_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $cta_found = true;
                break;
            }
        }
        
        if ($cta_found) {
            $score += 4;
            $details[] = 'Call-to-Action vorhanden';
        } else {
            $details[] = 'Kein Call-to-Action gefunden';
        }
        
        if (preg_match('/\?/', $content)) {
            $score += 2;
            $details[] = 'Fragen für Engagement vorhanden';
        }
        
        $emotional_words = array('fantastisch', 'erstaunlich', 'unglaublich', 'perfekt', 'ideal', 'beste');
        $emotional_found = false;
        foreach ($emotional_words as $word) {
            if (stripos($content, $word) !== false) {
                $emotional_found = true;
                break;
            }
        }
        
        if ($emotional_found) {
            $score += 2;
            $details[] = 'Emotionale Trigger verwendet';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function analyze_mobile_friendliness($content) {
        $score = 0;
        $details = array();
        
        if (strpos($content, 'responsive') !== false || strpos($content, 'max-width') !== false) {
            $score += 3;
            $details[] = 'Responsive Design-Elemente vorhanden';
        }
        
        $paragraphs = preg_split('/\n\s*\n/', wp_strip_all_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        $short_paragraphs = 0;
        
        foreach ($paragraphs as $paragraph) {
            if (str_word_count($paragraph) <= 50) {
                $short_paragraphs++;
            }
        }
        
        if ($short_paragraphs >= count($paragraphs) * 0.7) {
            $score += 4;
            $details[] = 'Mobile-freundliche Absatz-Längen';
        }
        
        return array('score' => $score, 'details' => $details);
    }
    
    private function generate_seo_recommendations($seo_score, $focus_keyword) {
        $recommendations = array();
        
        if ($seo_score['keyword_optimization']['score'] < 15) {
            $recommendations[] = array(
                'category' => 'keyword_optimization',
                'priority' => 'high',
                'title' => 'Keyword-Optimierung verbessern',
                'description' => 'Das Fokus-Keyword sollte im Titel, in der Beschreibung und im ersten Absatz vorkommen.',
                'action' => 'Keyword "' . $focus_keyword . '" strategisch platzieren'
            );
        }
        
        if ($seo_score['content_quality']['score'] < 15) {
            $recommendations[] = array(
                'category' => 'content_quality',
                'priority' => 'medium',
                'title' => 'Content-Qualität verbessern',
                'description' => 'Verwenden Sie mehr Überschriften, kürzere Sätze und strukturieren Sie den Content besser.',
                'action' => 'Content-Struktur und Lesbarkeit optimieren'
            );
        }
        
        if ($seo_score['technical_seo']['score'] < 15) {
            $recommendations[] = array(
                'category' => 'technical_seo',
                'priority' => 'medium',
                'title' => 'Technische SEO verbessern',
                'description' => 'Fügen Sie Alt-Texte zu Bildern hinzu und verbessern Sie die Meta-Informationen.',
                'action' => 'Bilder optimieren und Meta-Tags anpassen'
            );
        }
        
        if ($seo_score['user_experience']['score'] < 15) {
            $recommendations[] = array(
                'category' => 'user_experience',
                'priority' => 'low',
                'title' => 'User Experience verbessern',
                'description' => 'Fügen Sie Call-to-Actions hinzu und strukturieren Sie den Content für bessere Lesbarkeit.',
                'action' => 'Engagement-Faktoren und Mobile-Optimierung verbessern'
            );
        }
        
        return $recommendations;
    }
    
    private function get_first_paragraph($content) {
        $paragraphs = preg_split('/\n\s*\n/', wp_strip_all_tags($content), -1, PREG_SPLIT_NO_EMPTY);
        return !empty($paragraphs) ? $paragraphs[0] : '';
    }
} 