<?php
/**
 * ReTexify Content Classifier
 * 
 * Spezialisiert auf Content-Klassifizierung und -Qualitätsbewertung
 * 
 * @package ReTexify_AI
 * @since 4.23.0
 * @version 4.23.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Content_Classifier {
    
    /**
     * Content klassifizieren
     * 
     * @param array $processed_text Verarbeiteter Text
     * @param array $keyword_analysis Keyword-Analyse
     * @return array Content-Klassifizierung
     */
    public function classify_content($processed_text, $keyword_analysis) {
        $words = $processed_text['words'];
        $sentences = $processed_text['sentences'];
        
        $classification = array(
            'content_type' => $this->determine_content_type($words, $sentences),
            'search_intent' => $this->determine_search_intent($words, $keyword_analysis),
            'complexity_level' => $this->assess_complexity_level($processed_text),
            'readability_score' => $this->calculate_readability_score($sentences, $words),
            'engagement_score' => $this->calculate_engagement_score($processed_text),
            'technical_score' => $this->calculate_technical_score($words),
            'content_quality' => $this->assess_content_quality($processed_text, $keyword_analysis),
            'semantic_themes' => $this->extract_semantic_themes($keyword_analysis),
            'seasonal_context' => $this->detect_seasonal_context($processed_text['raw']),
            'competitive_hints' => $this->detect_competitive_hints($processed_text['raw'])
        );
        
        return $classification;
    }
    
    /**
     * Content-Typ bestimmen
     * 
     * @param array $words Wörter
     * @param array $sentences Sätze
     * @return string Content-Typ
     */
    private function determine_content_type($words, $sentences) {
        $type_indicators = array(
            'informational' => array('information', 'erklärung', 'beschreibung', 'überblick', 'guide', 'anleitung'),
            'commercial' => array('kaufen', 'preis', 'angebot', 'bestellen', 'kontakt', 'beratung'),
            'transactional' => array('bestellung', 'kauf', 'zahlung', 'versand', 'lieferung', 'service'),
            'navigational' => array('kontakt', 'impressum', 'agb', 'datenschutz', 'über uns', 'team')
        );
        
        $scores = array();
        foreach ($type_indicators as $type => $indicators) {
            $score = 0;
            foreach ($indicators as $indicator) {
                if (in_array($indicator, $words)) {
                    $score++;
                }
            }
            $scores[$type] = $score;
        }
        
        $max_score = max($scores);
        if ($max_score > 0) {
            return array_keys($scores, $max_score)[0];
        }
        
        return 'informational';
    }
    
    /**
     * Search Intent bestimmen
     * 
     * @param array $words Wörter
     * @param array $keyword_analysis Keyword-Analyse
     * @return string Search Intent
     */
    private function determine_search_intent($words, $keyword_analysis) {
        $intent_indicators = array(
            'informational' => array('was', 'wie', 'wann', 'wo', 'warum', 'information', 'erklärung'),
            'commercial' => array('beste', 'preis', 'kosten', 'günstig', 'billig', 'teuer', 'vergleich'),
            'transactional' => array('kaufen', 'bestellen', 'kauf', 'bestellung', 'zahlung', 'versand'),
            'navigational' => array('kontakt', 'adresse', 'telefon', 'email', 'öffnungszeiten')
        );
        
        $scores = array();
        foreach ($intent_indicators as $intent => $indicators) {
            $score = 0;
            foreach ($indicators as $indicator) {
                if (in_array($indicator, $words)) {
                    $score++;
                }
            }
            $scores[$intent] = $score;
        }
        
        $max_score = max($scores);
        if ($max_score > 0) {
            return array_keys($scores, $max_score)[0];
        }
        
        return 'informational';
    }
    
    /**
     * Komplexitäts-Level bewerten
     * 
     * @param array $processed_text Verarbeiteter Text
     * @return string Komplexitäts-Level
     */
    private function assess_complexity_level($processed_text) {
        $words = $processed_text['words'];
        $sentences = $processed_text['sentences'];
        
        $avg_word_length = 0;
        $avg_sentence_length = 0;
        
        if (!empty($words)) {
            $total_length = 0;
            foreach ($words as $word) {
                $total_length += strlen($word);
            }
            $avg_word_length = $total_length / count($words);
        }
        
        if (!empty($sentences)) {
            $avg_sentence_length = count($words) / count($sentences);
        }
        
        // Komplexitäts-Level bestimmen
        if ($avg_word_length > 8 || $avg_sentence_length > 25) {
            return 'expert';
        } elseif ($avg_word_length > 6 || $avg_sentence_length > 20) {
            return 'intermediate';
        } else {
            return 'beginner';
        }
    }
    
    /**
     * Lesbarkeits-Score berechnen
     * 
     * @param array $sentences Sätze
     * @param array $words Wörter
     * @return int Lesbarkeits-Score (0-100)
     */
    private function calculate_readability_score($sentences, $words) {
        if (empty($sentences) || empty($words)) {
            return 50;
        }
        
        $avg_sentence_length = count($words) / count($sentences);
        $avg_word_length = 0;
        
        if (!empty($words)) {
            $total_length = 0;
            foreach ($words as $word) {
                $total_length += strlen($word);
            }
            $avg_word_length = $total_length / count($words);
        }
        
        // Deutsche Lesbarkeitsformel (vereinfacht)
        $readability = 100 - ($avg_sentence_length * 2) - ($avg_word_length * 5);
        
        return max(0, min(100, round($readability)));
    }
    
    /**
     * Engagement-Score berechnen
     * 
     * @param array $processed_text Verarbeiteter Text
     * @return int Engagement-Score (0-100)
     */
    private function calculate_engagement_score($processed_text) {
        $text = $processed_text['raw'];
        $words = $processed_text['words'];
        
        $engagement = 50; // Basis-Score
        
        // Engagement-Indikatoren
        $engagement_indicators = array(
            'fragen' => preg_match_all('/\?/', $text),
            'ausrufe' => preg_match_all('/!/', $text),
            'anführungszeichen' => preg_match_all('/["\']/', $text),
            'zahlen' => preg_match_all('/\d+/', $text),
            'links' => preg_match_all('/http/', $text)
        );
        
        // Score basierend auf Indikatoren anpassen
        foreach ($engagement_indicators as $indicator => $count) {
            if ($count > 0) {
                $engagement += min(10, $count * 2);
            }
        }
        
        // Wortanzahl berücksichtigen
        if (count($words) > 500) {
            $engagement += 10;
        } elseif (count($words) < 100) {
            $engagement -= 20;
        }
        
        return max(0, min(100, round($engagement)));
    }
    
    /**
     * Technischen Score berechnen
     * 
     * @param array $words Wörter
     * @return int Technischer Score (0-100)
     */
    private function calculate_technical_score($words) {
        $technical_terms = array(
            'algorithmus', 'automatisierung', 'digitalisierung', 'optimierung', 'integration',
            'implementierung', 'entwicklung', 'programmierung', 'codierung', 'architektur',
            'infrastruktur', 'plattform', 'system', 'software', 'hardware', 'netzwerk',
            'datenbank', 'api', 'interface', 'protokoll', 'standard', 'framework', 'library'
        );
        
        $technical_count = 0;
        foreach ($words as $word) {
            if (in_array($word, $technical_terms)) {
                $technical_count++;
            }
        }
        
        $score = min(100, ($technical_count / count($words)) * 1000);
        return round($score);
    }
    
    /**
     * Content-Qualität bewerten
     * 
     * @param array $processed_text Verarbeiteter Text
     * @param array $keyword_analysis Keyword-Analyse
     * @return array Content-Qualität
     */
    private function assess_content_quality($processed_text, $keyword_analysis) {
        $words = $processed_text['words'];
        $sentences = $processed_text['sentences'];
        
        $quality = array(
            'overall_score' => 50,
            'word_count_score' => 0,
            'readability_score' => 0,
            'keyword_relevance_score' => 0,
            'structure_score' => 0,
            'recommendations' => array()
        );
        
        // Wortanzahl bewerten
        $word_count = count($words);
        if ($word_count >= 300) {
            $quality['word_count_score'] = 100;
        } elseif ($word_count >= 200) {
            $quality['word_count_score'] = 80;
        } elseif ($word_count >= 100) {
            $quality['word_count_score'] = 60;
        } else {
            $quality['word_count_score'] = 30;
            $quality['recommendations'][] = 'Content-Länge erhöhen (aktuell: ' . $word_count . ' Wörter)';
        }
        
        // Lesbarkeit bewerten
        $readability = $this->calculate_readability_score($sentences, $words);
        $quality['readability_score'] = $readability;
        
        if ($readability < 60) {
            $quality['recommendations'][] = 'Lesbarkeit verbessern (aktuell: ' . $readability . '/100)';
        }
        
        // Keyword-Relevanz bewerten
        if (!empty($keyword_analysis['primary_keywords'])) {
            $quality['keyword_relevance_score'] = min(100, count($keyword_analysis['primary_keywords']) * 10);
        }
        
        // Struktur bewerten
        if (count($sentences) >= 5) {
            $quality['structure_score'] = 100;
        } elseif (count($sentences) >= 3) {
            $quality['structure_score'] = 70;
        } else {
            $quality['structure_score'] = 40;
            $quality['recommendations'][] = 'Mehr Sätze für bessere Struktur';
        }
        
        // Gesamtscore berechnen
        $quality['overall_score'] = round(
            ($quality['word_count_score'] * 0.3) +
            ($quality['readability_score'] * 0.3) +
            ($quality['keyword_relevance_score'] * 0.2) +
            ($quality['structure_score'] * 0.2)
        );
        
        return $quality;
    }
    
    /**
     * Semantische Themen extrahieren
     * 
     * @param array $keyword_analysis Keyword-Analyse
     * @return array Semantische Themen
     */
    private function extract_semantic_themes($keyword_analysis) {
        $themes = array(
            'primary' => array(),
            'secondary' => array(),
            'semantic_clusters' => array()
        );
        
        // Primäre Themen aus Keywords
        if (!empty($keyword_analysis['primary_keywords'])) {
            $themes['primary'] = array_slice($keyword_analysis['primary_keywords'], 0, 5);
        }
        
        // Sekundäre Themen aus semantischen Keywords
        if (!empty($keyword_analysis['semantic_keywords'])) {
            $themes['secondary'] = array_slice($keyword_analysis['semantic_keywords'], 0, 10);
        }
        
        // Semantische Cluster erstellen
        $themes['semantic_clusters'] = array(
            'quality' => array('qualität', 'service', 'kompetenz'),
            'innovation' => array('innovation', 'modern', 'zukunft'),
            'trust' => array('vertrauen', 'sicherheit', 'zuverlässigkeit')
        );
        
        return $themes;
    }
    
    /**
     * Saisonalen Kontext erkennen
     * 
     * @param string $text Text
     * @return array Saisonaler Kontext
     */
    private function detect_seasonal_context($text) {
        $seasonal_indicators = array(
            'frühling' => array('frühling', 'frühlings', 'märz', 'april', 'mai', 'ostern', 'blühen'),
            'sommer' => array('sommer', 'sommers', 'juni', 'juli', 'august', 'urlaub', 'ferien', 'sonne'),
            'herbst' => array('herbst', 'herbsts', 'september', 'oktober', 'november', 'laub', 'ernte'),
            'winter' => array('winter', 'winters', 'dezember', 'januar', 'februar', 'weihnachten', 'schnee')
        );
        
        $detected_seasons = array();
        foreach ($seasonal_indicators as $season => $indicators) {
            foreach ($indicators as $indicator) {
                if (stripos($text, $indicator) !== false) {
                    $detected_seasons[] = $season;
                    break;
                }
            }
        }
        
        return array_unique($detected_seasons);
    }
    
    /**
     * Wettbewerbs-Hinweise erkennen
     * 
     * @param string $text Text
     * @return array Wettbewerbs-Hinweise
     */
    private function detect_competitive_hints($text) {
        $competitive_indicators = array(
            'vergleich', 'beste', 'alternativ', 'konkurrenz', 'wettbewerb',
            'anders', 'verschieden', 'unterschied', 'vorteil', 'nachteil'
        );
        
        $hints = array();
        foreach ($competitive_indicators as $indicator) {
            if (stripos($text, $indicator) !== false) {
                $hints[] = $indicator;
            }
        }
        
        return array_unique($hints);
    }
} 