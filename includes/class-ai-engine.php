<?php
/**
 * ReTexify AI Engine - Multi-Provider Support
 * 
 * Vollständige Unterstützung für OpenAI, Anthropic Claude UND Google Gemini
 * Mit allen neuesten Modellen (Stand Dezember 2024)
 * 
 * @package ReTexify_AI_Pro
 * @since 3.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class ReTexify_AI_Engine {
    
    /**
     * Unterstützte KI-Provider mit neuesten Updates
     */
    private $supported_providers = array(
        'openai' => 'OpenAI (GPT-4o, GPT-4o Mini, o1)',
        'anthropic' => 'Anthropic Claude (3.5 Sonnet, Haiku, Opus)',
        'gemini' => 'Google Gemini (Pro, Flash, Ultra)'
    );
    
    /**
     * Neueste Modelle für jeden Provider (Stand Dezember 2024)
     */
    private $default_models = array(
        'openai' => array(
            'gpt-4o-mini' => 'GPT-4o Mini (Empfohlen - Günstig & Schnell)',
            'gpt-4o' => 'GPT-4o (Premium - Beste Balance)',
            'o1-mini' => 'o1-Mini (Reasoning - Komplexe Aufgaben)',
            'o1-preview' => 'o1-Preview (Advanced Reasoning)',
            'gpt-4-turbo' => 'GPT-4 Turbo (Bewährt)',
            'gpt-4' => 'GPT-4 (Klassisch)',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Budget)'
        ),
        'anthropic' => array(
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Empfohlen - Beste Balance)',
            'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku (Neu - Schnell & Günstig)',
            'claude-3-opus-20240229' => 'Claude 3 Opus (Premium - Beste Qualität)',
            'claude-3-sonnet-20240229' => 'Claude 3 Sonnet (Ausgewogen)',
            'claude-3-haiku-20240307' => 'Claude 3 Haiku (Budget)'
        ),
        'gemini' => array(
            'gemini-1.5-pro-latest' => 'Gemini 1.5 Pro (Empfohlen - Beste Qualität)',
            'gemini-1.5-flash-latest' => 'Gemini 1.5 Flash (Schnell & Günstig)',
            'gemini-1.5-flash-8b-latest' => 'Gemini 1.5 Flash-8B (Ultra-Schnell)',
            'gemini-1.0-pro-latest' => 'Gemini 1.0 Pro (Bewährt)',
            'gemini-exp-1206' => 'Gemini Experimental (Beta - Neueste Features)'
        )
    );
    
    /**
     * Cost estimates for each model (per SEO Suite generation)
     * Stand: Dezember 2024
     */
    private $cost_estimates = array(
        'openai' => array(
            'gpt-4o-mini' => ['perRequest' => '0.001', 'speed' => '⚡ Sehr schnell', 'quality' => '⭐⭐⭐⭐'],
            'gpt-4o' => ['perRequest' => '0.015', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐⭐'],
            'o1-mini' => ['perRequest' => '0.018', 'speed' => '🔄 Mittel', 'quality' => '⭐⭐⭐⭐⭐'],
            'o1-preview' => ['perRequest' => '0.09', 'speed' => '⏳ Langsam', 'quality' => '⭐⭐⭐⭐⭐'],
            'gpt-4-turbo' => ['perRequest' => '0.04', 'speed' => '⚡ Mittel', 'quality' => '⭐⭐⭐⭐⭐'],
            'gpt-4' => ['perRequest' => '0.12', 'speed' => '⏳ Langsam', 'quality' => '⭐⭐⭐⭐⭐'],
            'gpt-3.5-turbo' => ['perRequest' => '0.002', 'speed' => '⚡ Sehr schnell', 'quality' => '⭐⭐⭐']
        ),
        'anthropic' => array(
            'claude-3-5-sonnet-20241022' => ['perRequest' => '0.009', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐⭐'],
            'claude-3-5-haiku-20241022' => ['perRequest' => '0.003', 'speed' => '⚡ Sehr schnell', 'quality' => '⭐⭐⭐⭐'],
            'claude-3-opus-20240229' => ['perRequest' => '0.045', 'speed' => '⏳ Langsam', 'quality' => '⭐⭐⭐⭐⭐'],
            'claude-3-sonnet-20240229' => ['perRequest' => '0.009', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐⭐'],
            'claude-3-haiku-20240307' => ['perRequest' => '0.0008', 'speed' => '⚡ Sehr schnell', 'quality' => '⭐⭐⭐⭐']
        ),
        'gemini' => array(
            'gemini-1.5-pro-latest' => ['perRequest' => '0.003', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐⭐'],
            'gemini-1.5-flash-latest' => ['perRequest' => '0.0002', 'speed' => '⚡ Sehr schnell', 'quality' => '⭐⭐⭐⭐'],
            'gemini-1.5-flash-8b-latest' => ['perRequest' => '0.0001', 'speed' => '⚡ Ultra-schnell', 'quality' => '⭐⭐⭐'],
            'gemini-1.0-pro-latest' => ['perRequest' => '0.001', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐'],
            'gemini-exp-1206' => ['perRequest' => '0.001', 'speed' => '⚡ Schnell', 'quality' => '⭐⭐⭐⭐']
        )
    );
    
    /**
     * Optimierungsfokus-Prompts
     */
    private $optimization_focus_prompts = array(
        'complete_seo' => 'Vollständige SEO-Optimierung für maximale Sichtbarkeit in Suchmaschinen.',
        'local_seo_swiss' => 'Schweizer Local SEO mit Fokus auf regionale Begriffe und Kantone.',
        'conversion' => 'Conversion-optimiert für höhere Klickraten und bessere Verkäufe.',
        'readability' => 'Lesbarkeit und Verständlichkeit für eine breitere Zielgruppe.',
        'branding' => 'Markenaufbau und Vertrauensbildung bei der Zielgruppe.',
        'ecommerce' => 'E-Commerce optimiert für Online-Shops und Produktverkauf.',
        'b2b' => 'B2B und Professional Services für Geschäftskunden.',
        'news_blog' => 'News und Blog-Content für aktuelle Themen und Engagement.'
    );
    
    /**
     * Schweizer Kantone für Local SEO
     */
    private $swiss_cantons = array(
        'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden',
        'BE' => 'Bern', 'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt',
        'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus', 'GR' => 'Graubünden',
        'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
        'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn',
        'SZ' => 'Schwyz', 'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri',
        'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
    );
    
    /**
     * Konstruktor
     */
    public function __construct() {
        // Eventuell spätere Initialisierung
    }
    
    /**
     * Verfügbare Provider abrufen
     */
    public function get_available_providers() {
        return $this->supported_providers;
    }
    
    /**
     * Modelle für Provider abrufen
     */
    public function get_models_for_provider($provider) {
        return $this->default_models[$provider] ?? array();
    }
    
    /**
     * Cost estimates abrufen
     */
    public function get_cost_estimates() {
        return $this->cost_estimates;
    }
    
    /**
     * KI-Einstellungen validieren
     * 
     * @param array $settings KI-Einstellungen
     * @return array Validierte Einstellungen
     */
    public function validate_settings($settings) {
        $validated = array();
        
        // API Provider validieren
        $provider = $settings['api_provider'] ?? 'openai';
        $validated['api_provider'] = array_key_exists($provider, $this->supported_providers) ? $provider : 'openai';
        
        // API Key validieren
        $validated['api_key'] = sanitize_text_field($settings['api_key'] ?? '');
        
        // Model validieren basierend auf Provider
        $available_models = array_keys($this->default_models[$validated['api_provider']] ?? array());
        $model = $settings['model'] ?? '';
        
        if (!empty($available_models)) {
            $validated['model'] = in_array($model, $available_models) ? $model : $available_models[0];
        } else {
            // Fallbacks für jeden Provider
            $fallbacks = array(
                'openai' => 'gpt-4o-mini',
                'anthropic' => 'claude-3-5-sonnet-20241022',
                'gemini' => 'gemini-1.5-flash-latest'
            );
            $validated['model'] = $fallbacks[$validated['api_provider']] ?? 'gpt-4o-mini';
        }
        
        // Parameter validieren
        $validated['max_tokens'] = intval($settings['max_tokens'] ?? 2000);
        $validated['temperature'] = floatval($settings['temperature'] ?? 0.7);
        $validated['default_language'] = sanitize_text_field($settings['default_language'] ?? 'de-ch');
        
        // Business-Context validieren
        $validated['business_context'] = sanitize_textarea_field($settings['business_context'] ?? 'Schweizer Unternehmen');
        $validated['target_audience'] = sanitize_text_field($settings['target_audience'] ?? 'Schweizer Kunden');
        $validated['brand_voice'] = sanitize_text_field($settings['brand_voice'] ?? 'professional');
        
        // Kantone validieren
        $target_cantons = $settings['target_cantons'] ?? array();
        if (is_array($target_cantons)) {
            $validated['target_cantons'] = array_filter($target_cantons, function($canton) {
                return array_key_exists($canton, $this->swiss_cantons);
            });
        } else {
            $validated['target_cantons'] = array('BE', 'ZH', 'LU', 'SG');
        }
        
        $validated['use_swiss_german'] = true;
        
        return $validated;
    }
    
    /**
     * KI-Verbindung testen mit besserer Validierung
     * 
     * @param array $settings KI-Einstellungen
     * @return array Test-Ergebnis
     */
    public function test_connection($settings) {
        try {
            if (empty($settings['api_key'])) {
                throw new Exception('Kein API-Schlüssel konfiguriert');
            }
            
            // Provider-spezifische API-Key Validierung
            $provider = $settings['api_provider'] ?? 'openai';
            $api_key = $settings['api_key'];
            
            $this->validate_api_key_format($provider, $api_key);
            
            $test_prompt = "Teste die KI-Verbindung für ReTexify AI Pro. Antworte kurz mit: 'Verbindung erfolgreich!'";
            
            $result = $this->call_ai_api($test_prompt, $settings);
            
            return array(
                'success' => true,
                'message' => '✅ KI-Verbindung erfolgreich! Provider: ' . $this->supported_providers[$provider] . ' | Model: ' . $settings['model'] . ' | Antwort: ' . $result,
                'provider' => $provider,
                'model' => $settings['model']
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '❌ Verbindungsfehler: ' . $e->getMessage(),
                'provider' => $settings['api_provider'] ?? 'unknown',
                'model' => $settings['model'] ?? 'unknown'
            );
        }
    }
    
    /**
     * API-Key Format validieren
     * 
     * @param string $provider Provider
     * @param string $api_key API Key
     * @throws Exception Wenn Format ungültig
     */
    private function validate_api_key_format($provider, $api_key) {
        $patterns = array(
            'openai' => '/^sk-/',
            'anthropic' => '/^sk-ant-/',
            'gemini' => '/^AIza/'
        );
        
        if (isset($patterns[$provider]) && !preg_match($patterns[$provider], $api_key)) {
            $format_examples = array(
                'openai' => 'sk-proj-... oder sk-...',
                'anthropic' => 'sk-ant-api03-...',
                'gemini' => 'AIzaSy...'
            );
            
            throw new Exception('Ungültiges API-Key Format für ' . ucfirst($provider) . '. Erwartet: ' . $format_examples[$provider]);
        }
    }
    
    /**
     * Einzelnes SEO-Element generieren mit Optimierungsfokus
     * 
     * @param WP_Post $post WordPress Post
     * @param string $seo_type Art des SEO-Elements (meta_title, meta_description, focus_keyword)
     * @param array $settings KI-Einstellungen
     * @param bool $include_cantons Kantone berücksichtigen
     * @param bool $premium_tone Premium Business-Ton verwenden
     * @return string Generierter Content
     */
    public function generate_single_seo_item($post, $seo_type, $settings, $include_cantons = true, $premium_tone = false) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        // Business-Kontext aufbauen
        $business_context = $this->build_business_context($settings);
        
        // Kantone-Text aufbauen
        $canton_text = $this->build_canton_context($settings, $include_cantons);
        
        // Ton-Anweisungen aufbauen
        $tone_instruction = $this->build_tone_instruction($settings, $premium_tone);
        
        // Optimierungsfokus hinzufügen
        $optimization_focus = $this->build_optimization_focus($settings);
        
        // Prompt für spezifischen SEO-Typ generieren
        $prompt = $this->generate_seo_prompt($seo_type, $title, $content, $business_context, $canton_text, $tone_instruction, $optimization_focus);
        
        if (!$prompt) {
            throw new Exception('Unbekannter SEO-Typ: ' . $seo_type);
        }
        
        return $this->call_ai_api($prompt, $settings);
    }
    
    /**
     * Komplette SEO-Suite generieren mit Optimierungsfokus
     * 
     * @param WP_Post $post WordPress Post
     * @param array $settings KI-Einstellungen
     * @param bool $include_cantons Kantone berücksichtigen
     * @param bool $premium_tone Premium Business-Ton verwenden
     * @return array Komplette SEO-Suite
     */
    public function generate_complete_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        // Business-Kontext aufbauen
        $business_context = $this->build_business_context($settings);
        
        // Kantone-Text aufbauen
        $canton_text = $this->build_canton_context($settings, $include_cantons);
        
        // Ton-Anweisungen aufbauen
        $tone_instruction = $this->build_tone_instruction($settings, $premium_tone);
        
        // Optimierungsfokus hinzufügen
        $optimization_focus = $this->build_optimization_focus($settings);
        
        $prompt = "Erstelle eine komplette SEO-Suite in perfektem Schweizer Hochdeutsch für diese Seite:

=== SEITENINHALT ===
Titel: {$title}
Content: " . substr($content, 0, 800) . "

=== BUSINESS-KONTEXT ===
{$business_context}
{$canton_text}

=== OPTIMIERUNGSFOKUS ===
{$optimization_focus}

=== TON-ANWEISUNGEN ===
{$tone_instruction}

=== TECHNISCHE ANFORDERUNGEN ===
- Schweizer Rechtschreibung verwenden (ss statt ß)
- Regional relevant für die Schweiz
- Conversion-optimiert
- Suchmaschinenfreundlich
- Für das Focus-Keyword: Verwende kommerzielle Begriffe mit Suchvolumen. Vermeide übertriebene Marketing-Sprache.

=== AUSGABEFORMAT (EXAKT SO) ===
META_TITEL: [Meta-Titel 50-60 Zeichen]
META_BESCHREIBUNG: [Meta-Beschreibung 150-160 Zeichen]
FOCUS_KEYWORD: [Starkes Focus-Keyword 1-3 Wörter]

Erstelle jetzt eine optimierte SEO-Suite:";
        
        $ai_response = $this->call_ai_api($prompt, $settings);
        
        return $this->parse_seo_suite_response($ai_response);
    }
    
    // PRIVATE HELPER METHODEN
    
    /**
     * Business-Kontext aus Einstellungen aufbauen
     * 
     * @param array $settings KI-Einstellungen
     * @return string Business-Kontext
     */
    private function build_business_context($settings) {
        $business_context = $settings['business_context'] ?? 'Schweizer Unternehmen';
        $target_audience = $settings['target_audience'] ?? 'Schweizer Kunden';
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        return "Unternehmen: {$business_context}
Zielgruppe: {$target_audience}
Markenstimme: {$brand_voice}";
    }
    
    /**
     * Kantone-Kontext aufbauen
     * 
     * @param array $settings KI-Einstellungen
     * @param bool $include_cantons Kantone berücksichtigen
     * @return string Kantone-Kontext
     */
    private function build_canton_context($settings, $include_cantons) {
        if (!$include_cantons || empty($settings['target_cantons'])) {
            return '';
        }
        
        $selected_canton_names = array();
        foreach ($settings['target_cantons'] as $code) {
            if (isset($this->swiss_cantons[$code])) {
                $selected_canton_names[] = $this->swiss_cantons[$code];
            }
        }
        
        if (empty($selected_canton_names)) {
            return '';
        }
        
        return "Ziel-Kantone: " . implode(', ', $selected_canton_names) . "
Berücksichtige diese Kantone für lokale SEO-Optimierung.";
    }
    
    /**
     * Optimierungsfokus aufbauen
     * 
     * @param array $settings KI-Einstellungen
     * @return string Optimierungsfokus
     */
    private function build_optimization_focus($settings) {
        $focus = $settings['optimization_focus'] ?? 'complete_seo';
        
        if (isset($this->optimization_focus_prompts[$focus])) {
            return "OPTIMIERUNGSFOKUS: " . $this->optimization_focus_prompts[$focus];
        }
        
        return "OPTIMIERUNGSFOKUS: " . $this->optimization_focus_prompts['complete_seo'];
    }
    
    /**
     * Ton-Anweisungen aufbauen
     * 
     * @param array $settings KI-Einstellungen
     * @param bool $premium_tone Premium-Ton verwenden
     * @return string Ton-Anweisungen
     */
    private function build_tone_instruction($settings, $premium_tone) {
        $brand_voice = $settings['brand_voice'] ?? 'professional';
        
        if ($premium_tone) {
            return 'PREMIUM BUSINESS-MODUS: 
- Erstelle exklusive, hochwertige Business-Texte für anspruchsvolle Kunden
- Verwende einen professionellen, vertrauensvollen Ton
- Betone Qualität, Expertise und Schweizer Präzision
- Zielgruppe: Entscheidungsträger und Premium-Kunden';
        }
        
        $tone_instructions = array(
            'professional' => 'PROFESSIONELLER MODUS: Sachlich, kompetent und vertrauenswürdig',
            'friendly' => 'FREUNDLICHER MODUS: Warm, einladend und zugänglich, aber trotzdem professionell',
            'expert' => 'EXPERTEN-MODUS: Fachlich versiert, kompetent und autoritativ',
            'premium' => 'PREMIUM-MODUS: Hochwertig, exklusiv und für anspruchsvolle Kunden',
            'casual' => 'LOCKERER MODUS: Modern, jung und nahbar, aber seriös'
        );
        
        return $tone_instructions[$brand_voice] ?? $tone_instructions['professional'];
    }
    
    /**
     * SEO-Prompt für spezifischen Typ generieren
     * 
     * @param string $seo_type SEO-Typ
     * @param string $title Titel
     * @param string $content Content
     * @param string $business_context Business-Kontext
     * @param string $canton_text Kantone-Text
     * @param string $tone_instruction Ton-Anweisungen
     * @param string $optimization_focus Optimierungsfokus
     * @return string|false Generierter Prompt oder false
     */
    private function generate_seo_prompt($seo_type, $title, $content, $business_context, $canton_text, $tone_instruction, $optimization_focus) {
        $prompts = array(
            'meta_title' => "Erstelle einen perfekten Meta-Titel (50-60 Zeichen) in Schweizer Hochdeutsch für diese Seite:

Titel: {$title}
Content: " . substr($content, 0, 300) . "

=== BUSINESS-KONTEXT ===
{$business_context}
{$canton_text}

=== OPTIMIERUNGSFOKUS ===
{$optimization_focus}

=== TON-ANWEISUNGEN ===
{$tone_instruction}

Antworte nur mit dem Meta-Titel, nichts anderes:",

            'meta_description' => "Erstelle eine überzeugende Meta-Beschreibung (150-160 Zeichen) in Schweizer Hochdeutsch für diese Seite:

Titel: {$title}
Content: " . substr($content, 0, 500) . "

=== BUSINESS-KONTEXT ===
{$business_context}
{$canton_text}

=== OPTIMIERUNGSFOKUS ===
{$optimization_focus}

=== TON-ANWEISUNGEN ===
{$tone_instruction}

Antworte nur mit der Meta-Beschreibung, nichts anderes:",

            'focus_keyword' => "Erstelle ein starkes, kommerzielles Focus-Keyword (1-3 Wörter), das ein potentieller Kunde in der Schweiz bei Google suchen würde.

Titel: {$title}
Content: " . substr($content, 0, 300) . "

=== BUSINESS-KONTEXT ===
{$business_context}
{$canton_text}

=== OPTIMIERUNGSFOKUS ===
{$optimization_focus}

Anforderungen an das Keyword:
- Hohes Suchvolumen & kommerzieller Such-Intent.
- Vermeide übertriebene Marketing-Begriffe. Konzentriere dich auf die tatsächliche Dienstleistung.
- Lokal relevant: Baue, wenn sinnvoll, einen der Ziel-Kantone ein.
- {$tone_instruction}

Antworte nur mit dem Keyword, nichts anderes:"
        );
        
        return $prompts[$seo_type] ?? false;
    }
    
    /**
     * SEO-Suite Response parsen
     * 
     * @param string $ai_response KI-Antwort
     * @return array Geparste SEO-Suite
     */
    private function parse_seo_suite_response($ai_response) {
        $lines = explode("\n", $ai_response);
        $suite = array(
            'meta_title' => '',
            'meta_description' => '',
            'focus_keyword' => ''
        );
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'META_TITEL:') === 0) {
                $suite['meta_title'] = trim(str_replace('META_TITEL:', '', $line));
            } elseif (strpos($line, 'META_BESCHREIBUNG:') === 0) {
                $suite['meta_description'] = trim(str_replace('META_BESCHREIBUNG:', '', $line));
            } elseif (strpos($line, 'FOCUS_KEYWORD:') === 0) {
                $suite['focus_keyword'] = trim(str_replace('FOCUS_KEYWORD:', '', $line));
            }
        }
        
        return $suite;
    }
    
    /**
     * KI-API aufrufen
     * 
     * @param string $prompt Prompt für die KI
     * @param array $settings KI-Einstellungen
     * @return string KI-Antwort
     * @throws Exception Bei API-Fehlern
     */
    private function call_ai_api($prompt, $settings) {
        $provider = $settings['api_provider'] ?? 'openai';
        
        switch ($provider) {
            case 'openai':
                return $this->call_openai_api($prompt, $settings);
            case 'anthropic':
                return $this->call_anthropic_api($prompt, $settings);
            case 'gemini':
                return $this->call_gemini_api($prompt, $settings);
            default:
                throw new Exception('Nicht unterstützter KI-Provider: ' . $provider);
        }
    }
    
    /**
     * OpenAI API aufrufen
     * 
     * @param string $prompt Prompt
     * @param array $settings Einstellungen
     * @return string Antwort
     * @throws Exception Bei API-Fehlern
     */
    private function call_openai_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gpt-4o-mini';
        $max_tokens = $settings['max_tokens'] ?? 2000;
        $temperature = $settings['temperature'] ?? 0.7;
        
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'user', 'content' => $prompt)
                ),
                'max_tokens' => $max_tokens,
                'temperature' => $temperature
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('OpenAI API-Verbindungsfehler: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            throw new Exception('OpenAI API-Fehler: ' . $data['error']['message']);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception('Unerwartete OpenAI API-Antwort');
        }
        
        return trim($data['choices'][0]['message']['content']);
    }
    
    /**
     * Anthropic API aufrufen (Claude)
     * 
     * @param string $prompt Prompt
     * @param array $settings Einstellungen
     * @return string Antwort
     * @throws Exception Bei API-Fehlern
     */
    private function call_anthropic_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'claude-3-5-sonnet-20241022';
        $max_tokens = $settings['max_tokens'] ?? 2000;
        $temperature = $settings['temperature'] ?? 0.7;
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                )
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Anthropic API-Verbindungsfehler: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? 'Unbekannter Anthropic Fehler';
            throw new Exception('Anthropic API-Fehler: ' . $error_message);
        }
        
        if (!isset($data['content'][0]['text'])) {
            throw new Exception('Unerwartete Anthropic API-Antwort: ' . json_encode($data));
        }
        
        return trim($data['content'][0]['text']);
    }
    
    /**
     * Google Gemini API aufrufen (NEU!)
     * 
     * @param string $prompt Prompt
     * @param array $settings Einstellungen
     * @return string Antwort
     * @throws Exception Bei API-Fehlern
     */
    private function call_gemini_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gemini-1.5-flash-latest';
        $temperature = $settings['temperature'] ?? 0.7;
        
        // Gemini API Endpoint
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => $temperature,
                    'maxOutputTokens' => $settings['max_tokens'] ?? 2000,
                    'topP' => 0.8,
                    'topK' => 10
                )
            )),
            'timeout' => 90
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('Gemini API-Verbindungsfehler: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            $error_message = $data['error']['message'] ?? 'Unbekannter Gemini Fehler';
            throw new Exception('Gemini API-Fehler: ' . $error_message);
        }
        
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Unerwartete Gemini API-Antwort: ' . json_encode($data));
        }
        
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }
    
    /**
     * Token-Anzahl schätzen (für alle Provider)
     * 
     * @param string $text Text
     * @return int Geschätzte Token-Anzahl
     */
    public function estimate_tokens($text) {
        // Grobe Schätzung: ~4 Zeichen pro Token für deutsche Texte
        return ceil(strlen($text) / 4);
    }
    
    /**
     * Kosten für Request schätzen (aktualisierte Preise Dezember 2024)
     * 
     * @param string $prompt Prompt
     * @param array $settings Einstellungen
     * @return array Kosten-Schätzung
     */
    public function estimate_cost($prompt, $settings) {
        $provider = $settings['api_provider'] ?? 'openai';
        $model = $settings['model'] ?? 'gpt-4o-mini';
        
        $input_tokens = $this->estimate_tokens($prompt);
        $output_tokens = $settings['max_tokens'] ?? 2000;
        
        // Aktuelle Preise (Stand Dezember 2024, in USD per 1M Token)
        $pricing = array(
            'openai' => array(
                'gpt-4o-mini' => array('input' => 0.15, 'output' => 0.60),
                'gpt-4o' => array('input' => 2.50, 'output' => 10.00),
                'o1-mini' => array('input' => 3.00, 'output' => 12.00),
                'o1-preview' => array('input' => 15.00, 'output' => 60.00),
                'gpt-4-turbo' => array('input' => 10.00, 'output' => 30.00),
                'gpt-4' => array('input' => 30.00, 'output' => 60.00),
                'gpt-3.5-turbo' => array('input' => 0.50, 'output' => 1.50)
            ),
            'anthropic' => array(
                'claude-3-5-sonnet-20241022' => array('input' => 3.00, 'output' => 15.00),
                'claude-3-5-haiku-20241022' => array('input' => 1.00, 'output' => 5.00),
                'claude-3-opus-20240229' => array('input' => 15.00, 'output' => 75.00),
                'claude-3-sonnet-20240229' => array('input' => 3.00, 'output' => 15.00),
                'claude-3-haiku-20240307' => array('input' => 0.25, 'output' => 1.25)
            ),
            'gemini' => array(
                'gemini-1.5-pro-latest' => array('input' => 1.25, 'output' => 5.00),
                'gemini-1.5-flash-latest' => array('input' => 0.075, 'output' => 0.30),
                'gemini-1.5-flash-8b-latest' => array('input' => 0.0375, 'output' => 0.15),
                'gemini-1.0-pro-latest' => array('input' => 0.50, 'output' => 1.50),
                'gemini-exp-1206' => array('input' => 0.50, 'output' => 1.50)
            )
        );
        
        if (!isset($pricing[$provider][$model])) {
            return array('error' => 'Kosten-Schätzung für dieses Modell nicht verfügbar');
        }
        
        $model_pricing = $pricing[$provider][$model];
        
        $input_cost = ($input_tokens / 1000000) * $model_pricing['input'];
        $output_cost = ($output_tokens / 1000000) * $model_pricing['output'];
        $total_cost = $input_cost + $output_cost;
        
        return array(
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $input_tokens + $output_tokens,
            'input_cost_usd' => round($input_cost, 6),
            'output_cost_usd' => round($output_cost, 6),
            'total_cost_usd' => round($total_cost, 6),
            'model' => $model,
            'provider' => $provider
        );
    }
}

// Globale Instanz bereitstellen
if (!function_exists('retexify_get_ai_engine')) {
    function retexify_get_ai_engine() {
        static $instance = null;
        if (null === $instance) {
            $instance = new ReTexify_AI_Engine();
        }
        return $instance;
    }
}