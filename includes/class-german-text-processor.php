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
} 