<?php
/**
 * ReTexify AI Pro - Direkte Textgenerierung (KORRIGIERT)
 * Neue Klasse für freie Textgenerierung ohne Post-Bindung
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_Direct_Text_Generator {
    
    /**
     * @var ReTexify_AI_Engine AI-Engine Instanz
     */
    private $ai_engine;
    
    /**
     * Text-Typen für direkte Generierung
     */
    private $text_types = array(
        'meta_title' => 'Meta-Titel (50-60 Zeichen)',
        'meta_description' => 'Meta-Beschreibung (150-160 Zeichen)',
        'product_description' => 'Produktbeschreibung',
        'blog_intro' => 'Blog-Einleitung',
        'company_description' => 'Unternehmensbeschreibung',
        'service_description' => 'Service-Beschreibung',
        'email_subject' => 'E-Mail-Betreff',
        'social_post' => 'Social Media Post',
        'ad_copy' => 'Werbetexte',
        'press_release' => 'Pressemitteilung',
        'landing_page' => 'Landing Page Text',
        'newsletter' => 'Newsletter-Content',
        'free_text' => 'Freier Text (Ihre Vorgabe)'
    );
    
    /**
     * Konstruktor
     */
    public function __construct($ai_engine = null) {
        $this->ai_engine = $ai_engine;
        
        // AJAX-Handler registrieren
        add_action('wp_ajax_retexify_generate_direct_text', array($this, 'ajax_generate_direct_text'));
        add_action('wp_ajax_retexify_get_text_types', array($this, 'ajax_get_text_types'));
    }
    
    /**
     * Direkte Textgenerierung (AJAX-Handler)
     */
    public function ajax_generate_direct_text() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $text_type = sanitize_text_field($_POST['text_type'] ?? 'free_text');
        
        if (empty($prompt)) {
            wp_send_json_error('Bitte geben Sie einen Text/Prompt ein');
            return;
        }
        
        try {
            $generated_text = $this->generate_text($prompt, $text_type);
            
            wp_send_json_success(array(
                'generated_text' => $generated_text,
                'text_type' => $text_type,
                'character_count' => strlen($generated_text),
                'word_count' => str_word_count($generated_text)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Textgenerierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Verfügbare Text-Typen abrufen (AJAX-Handler)
     */
    public function ajax_get_text_types() {
        if (!wp_verify_nonce($_POST['nonce'], 'retexify_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sicherheitsfehler');
            return;
        }
        
        wp_send_json_success($this->text_types);
    }
    
    /**
     * Text basierend auf Prompt und Typ generieren
     * 
     * @param string $prompt Benutzer-Prompt
     * @param string $text_type Typ des zu generierenden Texts
     * @return string Generierter Text
     */
    public function generate_text($prompt, $text_type) {
        if (!$this->ai_engine) {
            throw new Exception('AI-Engine nicht verfügbar');
        }
        
        // KI-Einstellungen laden
        $settings = get_option('retexify_ai_settings', array());
        
        // Typ-spezifischen Prompt erstellen
        $ai_prompt = $this->build_prompt($prompt, $text_type, $settings);
        
        try {
            $response = $this->ai_engine->call_ai_api($ai_prompt, $settings);
            return $this->post_process_text($response, $text_type);
            
        } catch (Exception $e) {
            throw new Exception('KI-Generierung fehlgeschlagen: ' . $e->getMessage());
        }
    }
    
    /**
     * Typ-spezifischen Prompt erstellen
     * 
     * @param string $user_prompt Benutzer-Prompt
     * @param string $text_type Text-Typ
     * @param array $settings KI-Einstellungen
     * @return string Vollständiger AI-Prompt
     */
    private function build_prompt($user_prompt, $text_type, $settings) {
        $business_context = $settings['business_context'] ?? 'Schweizer Unternehmen';
        $target_audience = $settings['target_audience'] ?? 'Schweizer Kunden';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        // Kantone-Kontext
        $canton_text = '';
        if (!empty($settings['target_cantons'])) {
            $canton_names = $this->get_canton_names($settings['target_cantons']);
            if (!empty($canton_names)) {
                $canton_text = "\nZiel-Kantone: " . implode(', ', $canton_names);
            }
        }
        
        // Basis-Prompt
        $base_prompt = "Du bist ein SCHWEIZER CONTENT-EXPERTE und erstellst hochwertige Texte in perfektem Schweizer Hochdeutsch.

=== BUSINESS-KONTEXT ===
Unternehmen: {$business_context}
Zielgruppe: {$target_audience}
Markenstimme: {$brand_voice}{$canton_text}

=== BENUTZER-ANFRAGE ===
{$user_prompt}

=== TEXT-TYP ===
{$this->text_types[$text_type]}

";
        
        // Typ-spezifische Anweisungen hinzufügen
        $specific_instructions = $this->get_type_specific_instructions($text_type);
        
        return $base_prompt . $specific_instructions;
    }
    
    /**
     * Typ-spezifische Anweisungen abrufen
     * 
     * @param string $text_type Text-Typ
     * @return string Spezifische Anweisungen
     */
    private function get_type_specific_instructions($text_type) {
        $instructions = array(
            'meta_title' => "=== ANFORDERUNGEN ===
- Exakt 50-60 Zeichen
- Enthält Haupt-Keyword
- Klick-optimiert
- Schweizer Rechtschreibung (ss statt ß)
- Keine übertriebene Marketing-Sprache

Erstelle nur den Meta-Titel ohne zusätzliche Erklärungen:",

            'meta_description' => "=== ANFORDERUNGEN ===
- Exakt 150-160 Zeichen
- Überzeugende Beschreibung
- Call-to-Action integriert
- Wichtige Keywords enthalten
- Schweizer Rechtschreibung (ss statt ß)

Erstelle nur die Meta-Beschreibung ohne zusätzliche Erklärungen:",

            'product_description' => "=== ANFORDERUNGEN ===
- Verkaufsorientiert und überzeugend
- Vorteile und Features klar hervorheben
- Emotionale Ansprache
- Vertrauen schaffen
- Schweizer Qualitätsstandards betonen

Erstelle eine professionelle Produktbeschreibung:",

            'blog_intro' => "=== ANFORDERUNGEN ===
- Fesselnde Einleitung (150-200 Wörter)
- Neugierig machen
- Problemstellung ansprechen
- Lesefluss optimiert
- Persönlicher Bezug

Erstelle eine mitreissende Blog-Einleitung:",

            'company_description' => "=== ANFORDERUNGEN ===
- Professionell und vertrauenswürdig
- Alleinstellungsmerkmale hervorheben
- Schweizer Werte betonen
- Zielgruppe direkt ansprechen
- Glaubwürdig und authentisch

Erstelle eine überzeugende Unternehmensbeschreibung:",

            'service_description' => "=== ANFORDERUNGEN ===
- Nutzen und Mehrwert klar kommunizieren
- Problemlösung fokussieren
- Expertise und Kompetenz zeigen
- Lokalen Bezug herstellen
- Handlungsaufforderung integrieren

Erstelle eine überzeugende Service-Beschreibung:",

            'email_subject' => "=== ANFORDERUNGEN ===
- 30-50 Zeichen optimal
- Hohe Öffnungsrate erzielen
- Neugierig aber seriös
- Kein Spam-Verdacht
- Personalisiert wirken

Erstelle 3 verschiedene E-Mail-Betreffs:",

            'social_post' => "=== ANFORDERUNGEN ===
- Plattform-optimiert (Facebook/LinkedIn)
- Engagement fördern
- Hashtags strategisch einsetzen
- Emotionale Verbindung
- Call-to-Action integrieren

Erstelle einen ansprechenden Social Media Post:",

            'ad_copy' => "=== ANFORDERUNGEN ===
- Hohe Conversion-Rate
- Klare Nutzenversprechen
- Verknappung und Dringlichkeit
- Zielgruppe präzise ansprechen
- Rechtlich korrekt

Erstelle einen wirkungsvollen Werbetext:",

            'press_release' => "=== ANFORDERUNGEN ===
- Journalistischer Stil
- Nachrichtenwert hervorheben
- Objektiv und faktenbezogen
- Schweizer Medienlandschaft beachten
- Kontaktdaten integrieren

Erstelle eine professionelle Pressemitteilung:",

            'landing_page' => "=== ANFORDERUNGEN ===
- Conversion-optimiert
- Klare Werteversprechen
- Vertrauen schaffen
- Bedenken ausräumen
- Starke Call-to-Actions

Erstelle einen überzeugenden Landing Page Text:",

            'newsletter' => "=== ANFORDERUNGEN ===
- Mehrwert für Abonnenten
- Persönlich und nahbar
- Aktuelle Themen aufgreifen
- Lesefreundlich strukturiert
- Newsletter-spezifische Ansprache

Erstelle einen wertvollen Newsletter-Content:",

            'free_text' => "=== ANFORDERUNGEN ===
- Folge den Benutzer-Vorgaben
- Schweizer Rechtschreibung verwenden
- Professionell und hochwertig
- Zielgruppe berücksichtigen
- Business-Kontext einbeziehen

Erstelle den gewünschten Text:"
        );
        
        return $instructions[$text_type] ?? $instructions['free_text'];
    }
    
    /**
     * Text nach der Generierung nachbearbeiten
     * 
     * @param string $text Generierter Text
     * @param string $text_type Text-Typ
     * @return string Nachbearbeiteter Text
     */
    private function post_process_text($text, $text_type) {
        // Basis-Bereinigung
        $text = trim($text);
        
        // BUGFIX: Korrekte Behandlung von speziellen Anführungszeichen
        // Deutsche Anführungszeichen ersetzen
        $text = str_replace(array(chr(226).chr(128).chr(158), chr(226).chr(128).chr(156), chr(226).chr(128).chr(157)), '"', $text);
        
        // Englische curly quotes ersetzen  
        $text = str_replace(array(chr(226).chr(128).chr(152), chr(226).chr(128).chr(153), chr(226).chr(128).chr(154)), "'", $text);
        
        // Alternative Methode mit HTML-Entities
        $text = str_replace(array('&ldquo;', '&rdquo;', '&lsquo;', '&rsquo;'), array('"', '"', "'", "'"), $text);
        
        // Typ-spezifische Nachbearbeitung
        switch ($text_type) {
            case 'meta_title':
                // Sicherstellen, dass Länge 50-60 Zeichen ist
                if (strlen($text) > 60) {
                    $text = substr($text, 0, 57) . '...';
                }
                break;
                
            case 'meta_description':
                // Sicherstellen, dass Länge 150-160 Zeichen ist
                if (strlen($text) > 160) {
                    $text = substr($text, 0, 157) . '...';
                }
                break;
                
            case 'email_subject':
                // Falls mehrere Betreff-Zeilen, alle zurückgeben
                break;
                
            default:
                // Standard-Nachbearbeitung
                break;
        }
        
        return $text;
    }
    
    /**
     * Kantone-Namen aus Codes abrufen
     * 
     * @param array $canton_codes Array mit Kantone-Codes
     * @return array Array mit Kantone-Namen
     */
    private function get_canton_names($canton_codes) {
        $swiss_cantons = array(
            'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
            'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
            'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
            'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
            'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
            'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
            'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
        );
        
        $canton_names = array();
        foreach ($canton_codes as $code) {
            if (isset($swiss_cantons[$code])) {
                $canton_names[] = $swiss_cantons[$code];
            }
        }
        
        return $canton_names;
    }
    
    /**
     * Verfügbare Text-Typen abrufen
     * 
     * @return array Text-Typen
     */
    public function get_text_types() {
        return $this->text_types;
    }
    
    /**
     * HTML für direkte Textgenerierung rendern
     * 
     * @return string HTML-Code
     */
    public function render_direct_text_interface() {
        $html = '<div class="retexify-direct-text-section">';
        $html .= '<h3>🤖 Direkte Textgenerierung</h3>';
        $html .= '<p class="retexify-description">';
        $html .= 'Generieren Sie professionelle Texte ohne Bindung an spezifische Beiträge oder Seiten.';
        $html .= '</p>';
        
        $html .= '<div class="retexify-form-group">';
        $html .= '<label for="retexify-direct-text-type">Text-Typ:</label>';
        $html .= '<select id="retexify-direct-text-type" class="retexify-select">';
        
        foreach ($this->text_types as $key => $label) {
            $html .= '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        
        $html .= '</select>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-form-group">';
        $html .= '<label for="retexify-direct-prompt">Ihr Text/Prompt:</label>';
        $html .= '<textarea id="retexify-direct-prompt" class="retexify-textarea" rows="4" ';
        $html .= 'placeholder="Beschreiben Sie, was Sie benötigen (z.B. Meta-Titel für Zahnarztpraxis in Zürich oder Produktbeschreibung für Bio-Honig aus dem Berner Oberland)"></textarea>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-form-group">';
        $html .= '<button type="button" class="retexify-btn retexify-btn-primary retexify-generate-direct-text">';
        $html .= '🤖 Text generieren';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-form-group">';
        $html .= '<label for="retexify-direct-result">Generierter Text:</label>';
        $html .= '<textarea id="retexify-direct-result" class="retexify-textarea" rows="6" ';
        $html .= 'placeholder="Hier erscheint Ihr generierter Text..." readonly></textarea>';
        $html .= '<div class="retexify-text-stats">';
        $html .= '<span id="retexify-direct-char-count">0 Zeichen</span> | ';
        $html .= '<span id="retexify-direct-word-count">0 Wörter</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="retexify-form-group">';
        $html .= '<button type="button" class="retexify-btn retexify-btn-secondary retexify-copy-direct-text">';
        $html .= '📋 Text kopieren';
        $html .= '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}