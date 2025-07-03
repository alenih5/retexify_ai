<?php
/**
 * ReTexify Keyword Analyzer
 * 
 * Spezialisiert auf Keyword-Extraktion und -Analyse
 * 
 * @package ReTexify_AI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Keyword_Analyzer {
    
    /**
     * Deutsche Power-Wörter
     */
    private static $german_power_words = array(
        'beste', 'professionelle', 'hochwertige', 'moderne', 'innovative', 'effiziente',
        'zuverlässige', 'schnelle', 'sichere', 'günstige', 'preiswerte', 'qualitative',
        'erfahrene', 'kompetente', 'seriöse', 'vertrauensvolle', 'bewährte', 'getestete',
        'zertifizierte', 'lizenzierte', 'akkreditierte', 'anerkannte', 'empfohlene',
        'beliebte', 'gefragte', 'trendige', 'moderne', 'zeitgemäße', 'zukunftsorientierte'
    );
    
    /**
     * Technische Begriffe
     */
    private static $technical_terms = array(
        'algorithmus', 'automatisierung', 'digitalisierung', 'optimierung', 'integration',
        'implementierung', 'entwicklung', 'programmierung', 'codierung', 'architektur',
        'infrastruktur', 'plattform', 'system', 'software', 'hardware', 'netzwerk',
        'datenbank', 'api', 'interface', 'protokoll', 'standard', 'framework', 'library'
    );
    
    /**
     * Keywords analysieren
     * 
     * @param array $processed_text Verarbeiteter Text
     * @return array Keyword-Analyse
     */
    public function analyze_keywords($processed_text) {
        $words = $processed_text['words'];
        $sentences = $processed_text['sentences'];
        
        $analysis = array(
            'primary_keywords' => $this->extract_primary_keywords($words),
            'long_tail_keywords' => $this->extract_long_tail_keywords($words, $sentences),
            'semantic_keywords' => $this->extract_semantic_keywords($words),
            'technical_keywords' => $this->extract_technical_keywords($words),
            'power_words' => $this->extract_power_words($words),
            'compound_keywords' => $this->detect_compound_keywords($words),
            'keyword_density' => $this->calculate_keyword_density($words),
            'keyword_scores' => $this->calculate_keyword_scores($words),
            'ngram_phrases' => $this->extract_ngram_phrases($sentences),
            'main_topic' => $this->determine_main_topic($words, $sentences)
        );
        
        return $analysis;
    }
    
    /**
     * Primäre Keywords extrahieren
     * 
     * @param array $words Wörter
     * @return array Primäre Keywords
     */
    private function extract_primary_keywords($words) {
        $word_frequency = array_count_values($words);
        arsort($word_frequency);
        
        $primary_keywords = array();
        $count = 0;
        
        foreach ($word_frequency as $word => $frequency) {
            if ($count >= 10) break; // Maximal 10 primäre Keywords
            
            if ($frequency >= 2 && strlen($word) >= 4) {
                $primary_keywords[] = $word;
                $count++;
            }
        }
        
        return $primary_keywords;
    }
    
    /**
     * Long-Tail Keywords extrahieren
     * 
     * @param array $words Wörter
     * @param array $sentences Sätze
     * @return array Long-Tail Keywords
     */
    private function extract_long_tail_keywords($words, $sentences) {
        $long_tail = array();
        
        // 3-4 Wort Phrasen aus Sätzen extrahieren
        foreach ($sentences as $sentence) {
            $sentence_words = preg_split('/\s+/', strtolower($sentence));
            
            for ($i = 0; $i <= count($sentence_words) - 3; $i++) {
                $phrase = implode(' ', array_slice($sentence_words, $i, 3));
                if (strlen($phrase) >= 15 && strlen($phrase) <= 60) {
                    $long_tail[] = $phrase;
                }
            }
            
            // 4-Wort Phrasen
            for ($i = 0; $i <= count($sentence_words) - 4; $i++) {
                $phrase = implode(' ', array_slice($sentence_words, $i, 4));
                if (strlen($phrase) >= 20 && strlen($phrase) <= 80) {
                    $long_tail[] = $phrase;
                }
            }
        }
        
        return array_unique(array_slice($long_tail, 0, 20));
    }
    
    /**
     * Semantische Keywords extrahieren
     * 
     * @param array $words Wörter
     * @return array Semantische Keywords
     */
    private function extract_semantic_keywords($words) {
        $semantic_keywords = array();
        
        // Wörter mit semantischer Bedeutung
        $semantic_patterns = array(
            'qualität', 'service', 'beratung', 'lösung', 'kompetenz', 'erfahrung',
            'sicherheit', 'vertrauen', 'zuverlässigkeit', 'profession', 'expertise',
            'innovation', 'effizienz', 'optimierung', 'entwicklung', 'zukunft'
        );
        
        foreach ($words as $word) {
            if (in_array($word, $semantic_patterns)) {
                $semantic_keywords[] = $word;
            }
        }
        
        return array_unique($semantic_keywords);
    }
    
    /**
     * Technische Keywords extrahieren
     * 
     * @param array $words Wörter
     * @return array Technische Keywords
     */
    private function extract_technical_keywords($words) {
        $technical_keywords = array();
        
        foreach ($words as $word) {
            if (in_array($word, self::$technical_terms)) {
                $technical_keywords[] = $word;
            }
        }
        
        return array_unique($technical_keywords);
    }
    
    /**
     * Power-Wörter extrahieren
     * 
     * @param array $words Wörter
     * @return array Power-Wörter
     */
    private function extract_power_words($words) {
        $power_words = array();
        
        foreach ($words as $word) {
            if (in_array($word, self::$german_power_words)) {
                $power_words[] = $word;
            }
        }
        
        return array_unique($power_words);
    }
    
    /**
     * Komposita-Keywords erkennen
     * 
     * @param array $words Wörter
     * @return array Komposita
     */
    private function detect_compound_keywords($words) {
        $compounds = array();
        
        foreach ($words as $word) {
            // Lange Wörter (wahrscheinlich Komposita)
            if (strlen($word) > 10) {
                $compounds[] = $word;
            }
            
            // Wörter mit typischen deutschen Komposita-Mustern
            if (preg_match('/[a-z]+[a-z]{3,}[a-z]+/', $word)) {
                $compounds[] = $word;
            }
        }
        
        return array_unique($compounds);
    }
    
    /**
     * Keyword-Dichte berechnen
     * 
     * @param array $words Wörter
     * @return array Keyword-Dichte
     */
    private function calculate_keyword_density($words) {
        $word_frequency = array_count_values($words);
        $total_words = count($words);
        
        $density = array();
        foreach ($word_frequency as $word => $frequency) {
            if ($frequency >= 2) {
                $density[$word] = round(($frequency / $total_words) * 100, 2);
            }
        }
        
        arsort($density);
        return array_slice($density, 0, 20);
    }
    
    /**
     * Keyword-Scores berechnen
     * 
     * @param array $words Wörter
     * @return array Keyword-Scores
     */
    private function calculate_keyword_scores($words) {
        $word_frequency = array_count_values($words);
        $total_words = count($words);
        
        $scores = array();
        foreach ($word_frequency as $word => $frequency) {
            $score = 0;
            
            // Häufigkeit
            $score += ($frequency / $total_words) * 50;
            
            // Länge (längere Wörter = höherer Score)
            $score += min(strlen($word) * 2, 20);
            
            // Power-Wort Bonus
            if (in_array($word, self::$german_power_words)) {
                $score += 15;
            }
            
            // Technischer Begriff Bonus
            if (in_array($word, self::$technical_terms)) {
                $score += 10;
            }
            
            $scores[$word] = round($score, 2);
        }
        
        arsort($scores);
        return array_slice($scores, 0, 30);
    }
    
    /**
     * N-Gram Phrasen extrahieren
     * 
     * @param array $sentences Sätze
     * @return array N-Gram Phrasen
     */
    private function extract_ngram_phrases($sentences) {
        $ngrams = array();
        
        foreach ($sentences as $sentence) {
            $words = preg_split('/\s+/', strtolower($sentence));
            
            // Bigrams (2-Wort Phrasen)
            for ($i = 0; $i < count($words) - 1; $i++) {
                $bigram = $words[$i] . ' ' . $words[$i + 1];
                if (strlen($bigram) >= 6) {
                    $ngrams[] = $bigram;
                }
            }
            
            // Trigrams (3-Wort Phrasen)
            for ($i = 0; $i < count($words) - 2; $i++) {
                $trigram = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                if (strlen($trigram) >= 10) {
                    $ngrams[] = $trigram;
                }
            }
        }
        
        return array_unique(array_slice($ngrams, 0, 50));
    }
    
    /**
     * Hauptthema bestimmen
     * 
     * @param array $words Wörter
     * @param array $sentences Sätze
     * @return string Hauptthema
     */
    private function determine_main_topic($words, $sentences) {
        $word_frequency = array_count_values($words);
        arsort($word_frequency);
        
        // Häufigstes Wort als Hauptthema
        $main_topic = array_keys($word_frequency)[0] ?? 'content';
        
        // Fallback: Erste Sätze analysieren
        if (empty($main_topic) || strlen($main_topic) < 3) {
            foreach ($sentences as $sentence) {
                $sentence_words = preg_split('/\s+/', strtolower($sentence));
                foreach ($sentence_words as $word) {
                    if (strlen($word) >= 4 && !in_array($word, array('diese', 'diese', 'diese'))) {
                        $main_topic = $word;
                        break 2;
                    }
                }
            }
        }
        
        return ucfirst($main_topic);
    }
} 