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
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic Claude',
        'gemini' => 'Google Gemini'
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
            $filtered = array_filter($target_cantons, function($canton) {
                return array_key_exists($canton, $this->swiss_cantons);
            });
            $validated['target_cantons'] = array_values($filtered);
        } else {
            $validated['target_cantons'] = array();
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
                'message' => 'KI-Verbindung erfolgreich! Provider: ' . $this->supported_providers[$provider],
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
        
        // 🆕 CONTENT-ANALYSE für alle Methoden
        $content_keywords = $this->extract_content_keywords($title, $content);
        $page_context = $this->analyze_page_context($post, $settings);
        
        // 🚨 DEBUG: Content-Analyse Logging
        error_log('ReTexify Content-Analyse DEBUG:');
        error_log('- Titel: ' . $title);
        error_log('- Erkannte Kategorie: ' . ($content_keywords['detected_category'] ?? 'KEINE'));
        error_log('- Haupt-Keywords: ' . implode(', ', $content_keywords['main_keywords'] ?? []));
        error_log('- Vertrauen: ' . ($content_keywords['confidence'] ?? 0));
        error_log('- Seiten-Typ: ' . ($page_context['page_type'] ?? 'unbekannt'));
        
        // Business-Kontext aufbauen
        $business_context = $this->build_business_context($settings);
        
        // Kantone-Text aufbauen
        $canton_text = $this->build_canton_context($settings, $include_cantons);
        
        // Ton-Anweisungen aufbauen
        $tone_instruction = $this->build_tone_instruction($settings, $premium_tone);
        
        // Optimierungsfokus hinzufügen
        $optimization_focus = $this->build_optimization_focus($settings);
        
        // Prompt für spezifischen SEO-Typ generieren mit Content-Analyse
        $prompt = $this->generate_seo_prompt_with_analysis($seo_type, $title, $content, $business_context, $canton_text, $tone_instruction, $optimization_focus, $content_keywords, $page_context);
        
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
        
        // 🆕 CONTENT-ANALYSE für alle Methoden
        $content_keywords = $this->extract_content_keywords($title, $content);
        $page_context = $this->analyze_page_context($post, $settings);
        
        // 🚨 DEBUG: Content-Analyse Logging
        error_log('ReTexify Complete SEO Content-Analyse DEBUG:');
        error_log('- Titel: ' . $title);
        error_log('- Erkannte Kategorie: ' . ($content_keywords['detected_category'] ?? 'KEINE'));
        error_log('- Haupt-Keywords: ' . implode(', ', $content_keywords['main_keywords'] ?? []));
        error_log('- Vertrauen: ' . ($content_keywords['confidence'] ?? 0));
        error_log('- Seiten-Typ: ' . ($page_context['page_type'] ?? 'unbekannt'));
        
        // Business-Kontext aufbauen
        $business_context = $this->build_business_context($settings);
        
        // Kantone-Text aufbauen
        $canton_text = $this->build_canton_context($settings, $include_cantons);
        
        // Ton-Anweisungen aufbauen
        $tone_instruction = $this->build_tone_instruction($settings, $premium_tone);
        
        // Optimierungsfokus hinzufügen
        $optimization_focus = $this->build_optimization_focus($settings);
        
        // 🎲 VARIATION: Zufällige Variationen für unterschiedliche Texte
        $variations = array(
            "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst hochwertige SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-SPEZIALIST und erstellst optimale SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-PROFI und erstellst professionelle SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-BERATER und erstellst strategische SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-OPTIMIERER und erstellst effektive SEO-Texte basierend auf Content-Analyse."
        );
        $random_variation = $variations[array_rand($variations)];
        
        $prompt = $random_variation . "

=== CONTENT-INFORMATIONEN ===
Titel: {$title}
Content: " . substr($content, 0, 800) . "

=== CONTENT-ANALYSE ===
Erkannte Kategorie: " . ($content_keywords['detected_category'] ?? 'unbekannt') . "
Haupt-Keywords: " . implode(', ', $content_keywords['main_keywords']) . "
Vertrauen: " . ($content_keywords['confidence'] ?? 0) . "

=== SEITEN-TYP-ANALYSE ===
Seiten-Typ: " . $page_context['page_type'] . "
SEO-Strategie: " . $page_context['seo_strategy'] . "

🚨 KRITISCHE CONTENT-REGEL:
PRIORITÄT: 1. CONTENT → 2. BRANCHE → 3. FIRMA

Verwende NUR Keywords aus der erkannten Kategorie!
- Falls Kategorie 'immobilien' → NUR Immobilien-Keywords verwenden!
- Falls Kategorie 'auto' → NUR Auto-Keywords verwenden!
- Falls Kategorie 'software' → NUR Software-Keywords verwenden!
- Falls Kategorie 'arzt' → NUR Arzt-Keywords verwenden!
- Falls Kategorie 'restaurant' → NUR Restaurant-Keywords verwenden!
- Falls Kategorie 'mode' → NUR Mode-Keywords verwenden!
- Falls Kategorie 'griffe' → NUR Griffe-Keywords verwenden!
- Falls Kategorie 'neolith' → NUR Neolith-Keywords verwenden!
- Falls Kategorie 'küche' → NUR Küchen-Keywords verwenden!
- Falls Kategorie 'backofen' → NUR Backofen-Keywords verwenden!
- Falls Kategorie 'spüle' → NUR Spüle-Keywords verwenden!

❌ VERBOTEN: Falsche Produktkategorien mischen!
❌ NIEMALS: Auto-Seite mit Restaurant-Keywords!
❌ NIEMALS: Software-Seite mit Mode-Keywords!
❌ NIEMALS: Arzt-Seite mit Auto-Keywords!

✅ ERLAUBT: Content-spezifische Keywords + Branche + Firma

{$canton_text}

{$tone_instruction}
{$optimization_focus}

🎯 TEXTLÄNGEN-ANFORDERUNGEN (KRITISCH):

1. **META_TITEL**:
   - OPTIMAL: 55-65 Zeichen (NICHT genau 60!)
   - Nutze den verfügbaren Platz intelligent!
   - Focus-Keyword intelligent integriert
   - WICHTIG: Vollständige Sätze, keine Abkürzungen!
   - Verwende NUR Keywords aus der erkannten Kategorie!

2. **META_BESCHREIBUNG**:
   - OPTIMAL: 150-165 Zeichen (NICHT genau 160!)
   - Fülle den verfügbaren Platz intelligent aus!
   - Klarer Call-to-Action
   - WICHTIG: Vollständige Sätze, Kantone IMMER AUSGESCHRIEBEN!
   - NIEMALS: Bern und S..... → IMMER: Bern und Solothurn
   - Verwende NUR Keywords aus der erkannten Kategorie!

3. **FOCUS_KEYWORD** (1-4 Wörter):
   - PRODUKT/SERVICE-spezifische Begriffe
   - Vermeide generische Adjektive
   - Hohes kommerzielles Suchvolumen
   - Verwende NUR Keywords aus der erkannten Kategorie!

=== ANTWORT-FORMAT (exakt so) ===
META_TITEL: [dein optimierter Meta-Titel]
META_BESCHREIBUNG: [deine optimierte Meta-Beschreibung]
FOCUS_KEYWORD: [dein optimiertes Focus-Keyword]";
        
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
    /**
     * Erstellt spezifischen Prompt für SEO-Typ mit Content-Analyse
     */
    private function generate_seo_prompt_with_analysis($seo_type, $title, $content, $business_context, $canton_text, $tone_instruction, $optimization_focus, $content_keywords, $page_context) {
        // 🎲 VARIATION: Zufällige Variationen für unterschiedliche Texte
        $variations = array(
            "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst hochwertige SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-SPEZIALIST und erstellst optimale SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-PROFI und erstellst professionelle SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-BERATER und erstellst strategische SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-OPTIMIERER und erstellst effektive SEO-Texte basierend auf Content-Analyse."
        );
        $random_variation = $variations[array_rand($variations)];
        
        $base_prompt = $random_variation . "

=== CONTENT-INFORMATIONEN ===
Titel: {$title}
Content: " . substr($content, 0, 800) . "

=== CONTENT-ANALYSE ===
Erkannte Kategorie: " . ($content_keywords['detected_category'] ?? 'unbekannt') . "
Haupt-Keywords: " . implode(', ', $content_keywords['main_keywords']) . "
Vertrauen: " . ($content_keywords['confidence'] ?? 0) . "

=== SEITEN-TYP-ANALYSE ===
Seiten-Typ: " . $page_context['page_type'] . "
SEO-Strategie: " . $page_context['seo_strategy'] . "

🚨 KRITISCHE CONTENT-REGEL:
PRIORITÄT: 1. CONTENT → 2. BRANCHE → 3. FIRMA

Verwende NUR Keywords aus der erkannten Kategorie!
- Falls Kategorie 'immobilien' → NUR Immobilien-Keywords verwenden!
- Falls Kategorie 'auto' → NUR Auto-Keywords verwenden!
- Falls Kategorie 'software' → NUR Software-Keywords verwenden!
- Falls Kategorie 'arzt' → NUR Arzt-Keywords verwenden!
- Falls Kategorie 'restaurant' → NUR Restaurant-Keywords verwenden!
- Falls Kategorie 'mode' → NUR Mode-Keywords verwenden!
- Falls Kategorie 'griffe' → NUR Griffe-Keywords verwenden!
- Falls Kategorie 'neolith' → NUR Neolith-Keywords verwenden!
- Falls Kategorie 'küche' → NUR Küchen-Keywords verwenden!
- Falls Kategorie 'backofen' → NUR Backofen-Keywords verwenden!
- Falls Kategorie 'spüle' → NUR Spüle-Keywords verwenden!

❌ VERBOTEN: Falsche Produktkategorien mischen!
❌ NIEMALS: Auto-Seite mit Restaurant-Keywords!
❌ NIEMALS: Software-Seite mit Mode-Keywords!
❌ NIEMALS: Arzt-Seite mit Auto-Keywords!

✅ ERLAUBT: Content-spezifische Keywords + Branche + Firma

{$canton_text}

{$tone_instruction}
{$optimization_focus}

";

        switch ($seo_type) {
            case 'meta_title':
                return $base_prompt . "🎯 META-TITEL ANFORDERUNGEN:
- OPTIMAL: 55-65 Zeichen (NICHT genau 60!)
- Nutze den verfügbaren Platz intelligent!
- Focus-Keyword intelligent integriert
- WICHTIG: Vollständige Sätze, keine Abkürzungen!
- Verwende NUR Keywords aus der erkannten Kategorie!

Antworte NUR mit dem Meta-Titel, nichts anderes!";

            case 'meta_description':
                return $base_prompt . "🎯 META-BESCHREIBUNG ANFORDERUNGEN:
- OPTIMAL: 150-165 Zeichen (NICHT genau 160!)
- Fülle den verfügbaren Platz intelligent aus!
- Klarer Call-to-Action
- WICHTIG: Vollständige Sätze, Kantone IMMER AUSGESCHRIEBEN!
- NIEMALS: Bern und S..... → IMMER: Bern und Solothurn
- Verwende NUR Keywords aus der erkannten Kategorie!

Antworte NUR mit der Meta-Beschreibung, nichts anderes!";

            case 'focus_keyword':
                return $base_prompt . "🎯 FOCUS-KEYWORD ANFORDERUNGEN:
- 1-4 Wörter
- PRODUKT/SERVICE-spezifische Begriffe
- Vermeide generische Adjektive
- Hohes kommerzielles Suchvolumen
- Verwende NUR Keywords aus der erkannten Kategorie!

Antworte NUR mit dem Focus-Keyword, nichts anderes!";

            default:
                return false;
        }
    }

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
        
        // 🎯 TEXTLÄNGEN-OPTIMIERUNG für bessere Ausnutzung
        $suite = $this->optimize_text_lengths($suite);
        
        return $suite;
    }
    
    /**
     * Textlängen für SEO optimieren
     */
    private function optimize_text_lengths($seo_suite) {
        // Meta-Titel: 55-65 Zeichen optimal nutzen
        if (isset($seo_suite['meta_title'])) {
            $len = mb_strlen($seo_suite['meta_title']);
            if ($len > 65) {
                // Intelligentes Kürzen: Letztes Leerzeichen vor 65 Zeichen finden
                $target = mb_substr($seo_suite['meta_title'], 0, 65);
                $lastSpace = mb_strrpos($target, ' ');
                if ($lastSpace > 50) {
                    $seo_suite['meta_title'] = mb_substr($seo_suite['meta_title'], 0, $lastSpace);
                } else {
                    $seo_suite['meta_title'] = $target;
                }
            }
        }
        
        // Meta-Beschreibung: 150-165 Zeichen optimal nutzen
        if (isset($seo_suite['meta_description'])) {
            $len = mb_strlen($seo_suite['meta_description']);
            if ($len > 165) {
                // Intelligentes Kürzen: Letztes Leerzeichen vor 165 Zeichen finden
                $target = mb_substr($seo_suite['meta_description'], 0, 165);
                $lastSpace = mb_strrpos($target, ' ');
                if ($lastSpace > 140) {
                    $seo_suite['meta_description'] = mb_substr($seo_suite['meta_description'], 0, $lastSpace);
                } else {
                    $seo_suite['meta_description'] = $target;
                }
            }
        }
        
        return $seo_suite;
    }
    
    /**
     * ⚠️ NEUE HAUPTMETHODE: Direkter AI-API Call
     * Diese Methode wird vom Backend für intelligente Prompts verwendet
     */
    public function call_ai_api($prompt, $settings) {
        $provider = $settings['api_provider'] ?? 'openai';
        $api_key = $settings['api_key'] ?? '';
        if (empty($api_key)) {
            throw new Exception('Kein API-Schlüssel für ' . $provider . ' verfügbar');
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ReTexify AI: Calling ' . $provider . ' API with prompt length: ' . strlen($prompt));
        }
        switch ($provider) {
            case 'openai':
                return $this->call_openai_api($prompt, $settings);
            case 'anthropic':
                return $this->call_anthropic_api($prompt, $settings);
            case 'gemini':
                return $this->call_gemini_api($prompt, $settings);
            default:
                throw new Exception('Unbekannter API-Provider: ' . $provider);
        }
    }

    /**
     * ⚠️ KORRIGIERTE OpenAI API-Methode
     */
    private function call_openai_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gpt-4o-mini';
        $max_tokens = intval($settings['max_tokens'] ?? 1000);
        $temperature = floatval($settings['temperature'] ?? 0.7);
        
        $data = array(
            'model' => $model,
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Du bist ein professioneller SEO-Experte für den Schweizer Markt. Antworte präzise und befolge alle Anweisungen exakt.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        );
        
        // ⚠️ HAUPTFIX: Korrekte Header-Formation
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,  // ← KRITISCH: Bearer-Format
                'Content-Type' => 'application/json',
                'User-Agent' => 'ReTexify-AI/1.0'
            ),
            'body' => wp_json_encode($data),
            'data_format' => 'body'  // ⚠️ Wichtig für korrekte Übertragung
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('OpenAI API Fehler: ' . $response->get_error_message());
        }
        
        $http_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('ReTexify OpenAI: HTTP Code: ' . $http_code);
        error_log('ReTexify OpenAI: Response Body: ' . substr($body, 0, 200));
        
        if ($http_code !== 200) {
            throw new Exception('OpenAI API HTTP Fehler: ' . $http_code . ' - ' . $body);
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('OpenAI API JSON-Fehler: ' . json_last_error_msg());
        }
        
        if (isset($decoded['error'])) {
            throw new Exception('OpenAI API Fehler: ' . $decoded['error']['message']);
        }
        
        if (empty($decoded['choices'][0]['message']['content'])) {
            throw new Exception('OpenAI API: Leere Antwort erhalten');
        }
        
        $content = trim($decoded['choices'][0]['message']['content']);
        error_log('ReTexify OpenAI: Response length: ' . strlen($content));
        
        return $content;
    }

    /**
     * ⚠️ VERBESSERTE Anthropic API-Methode
     */
    private function call_anthropic_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'claude-3-5-sonnet-20241022';
        $max_tokens = intval($settings['max_tokens'] ?? 1000);
        $temperature = floatval($settings['temperature'] ?? 0.7);
        
        // ✅ FIX: Assoziatives Array für wp_remote_post Headers
        $headers = array(
            'x-api-key'        => $api_key,
            'Content-Type'     => 'application/json',
            'anthropic-version' => '2023-06-01'
        );
        
        $data = array(
            'model' => $model,
            'max_tokens' => $max_tokens,
            'temperature' => $temperature,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            )
        );
        
        $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'data_format' => 'body'
        ));
        if (is_wp_error($response)) {
            throw new Exception('Anthropic API Fehler: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        if (empty($decoded['content'][0]['text'])) {
            $error_msg = $decoded['error']['message'] ?? 'Unbekannter Anthropic Fehler';
            throw new Exception('Anthropic API Fehler: ' . $error_msg);
        }
        $content = trim($decoded['content'][0]['text']);
        error_log('ReTexify Anthropic: Response length: ' . strlen($content));
        return $content;
    }

    /**
     * ⚠️ VERBESSERTE Gemini API-Methode
     */
    private function call_gemini_api($prompt, $settings) {
        $api_key = $settings['api_key'];
        $model = $settings['model'] ?? 'gemini-1.5-flash';
        $max_tokens = intval($settings['max_tokens'] ?? 1000);
        $temperature = floatval($settings['temperature'] ?? 0.7);
        $headers = array(
            'Content-Type: application/json'
        );
        $data = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $prompt)
                    )
                )
            ),
            'generationConfig' => array(
                'temperature' => $temperature,
                'maxOutputTokens' => $max_tokens,
                'topP' => 1.0,
                'topK' => 32
            )
        );
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key;
        $response = wp_remote_post($url, array(
            'timeout' => 60,
            'headers' => $headers,
            'body' => wp_json_encode($data)
        ));
        if (is_wp_error($response)) {
            throw new Exception('Gemini API Fehler: ' . $response->get_error_message());
        }
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        if (empty($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            $error_msg = $decoded['error']['message'] ?? 'Unbekannter Gemini Fehler';
            throw new Exception('Gemini API Fehler: ' . $error_msg);
        }
        $content = trim($decoded['candidates'][0]['content']['parts'][0]['text']);
        error_log('ReTexify Gemini: Response length: ' . strlen($content));
        return $content;
    }

    /**
     * ⚠️ VERBESSERTE Methode: Intelligente SEO-Suite generieren
     * Diese Methode ersetzt/ergänzt die bestehende generate_complete_seo_suite Methode
     */
    public function generate_intelligent_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        // ⚠️ NEUE LOGIK: Intelligente Keyword-Research verwenden
        if (class_exists('ReTexify_Intelligent_Keyword_Research')) {
            error_log('ReTexify AI Engine: Using intelligent keyword research');
            $analysis_settings = array_merge($settings, array(
                'include_cantons' => $include_cantons,
                'premium_tone' => $premium_tone,
                'business_context' => $settings['business_context'] ?? '',
                'target_audience' => $settings['target_audience'] ?? 'Schweizer KMU',
                'brand_voice' => $premium_tone ? 'premium' : ($settings['brand_voice'] ?? 'professional'),
                'target_cantons' => $settings['target_cantons'] ?? array(),
                'optimization_focus' => 'complete_seo'
            ));
            try {
                $analysis = ReTexify_Intelligent_Keyword_Research::analyze_content($content, $analysis_settings);
                if (!empty($analysis) && !empty($analysis['keyword_strategy'])) {
                    $premium_prompt = ReTexify_Intelligent_Keyword_Research::create_premium_seo_prompt($content, $analysis_settings);
                    if (!empty($premium_prompt)) {
                        $intelligent_prompt = $this->build_intelligent_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone);
                        $ai_response = $this->call_ai_api($intelligent_prompt, $settings);
                        $suite = $this->parse_intelligent_suite_response($ai_response, $analysis);
                        error_log('ReTexify AI Engine: Intelligent SEO suite generated successfully');
                        return $suite;
                    }
                }
            } catch (Exception $e) {
                error_log('ReTexify AI Engine: Intelligent generation failed: ' . $e->getMessage());
            }
        }
        // Fallback zur normalen Generierung
        error_log('ReTexify AI Engine: Using fallback generation');
        return $this->generate_standard_seo_suite($post, $settings, $include_cantons, $premium_tone);
    }

    /**
     * ⚠️ NEUE HILFSMETHODE: Intelligenten Suite-Prompt erstellen
     */
    private function build_intelligent_suite_prompt($post, $analysis, $premium_prompt, $settings, $include_cantons, $premium_tone) {
        $title = $post->post_title;
        $content = wp_strip_all_tags($post->post_content);
        $business_context = !empty($settings['business_context']) ? $settings['business_context'] : 'Schweizer Unternehmen';
        $canton_text = '';
        if ($include_cantons && !empty($settings['target_cantons'])) {
            $cantons = is_array($settings['target_cantons']) ? implode(', ', $settings['target_cantons']) : $settings['target_cantons'];
            $canton_text = "Ziel-Kantone: {$cantons}";
        }
        $tone_instruction = $premium_tone ? 'Verwende einen premium, professionellen Business-Ton' : 'Verwende einen freundlichen, professionellen Ton';
        $primary_keywords = !empty($analysis['primary_keywords']) ? implode(', ', array_slice($analysis['primary_keywords'], 0, 5)) : '';
        $focus_keyword_suggestion = !empty($analysis['keyword_strategy']['focus_keyword']) ? $analysis['keyword_strategy']['focus_keyword'] : '';
        $long_tail_keywords = !empty($analysis['long_tail_keywords']) ? implode(', ', array_slice($analysis['long_tail_keywords'], 0, 3)) : '';
        $semantic_themes = !empty($analysis['semantic_themes']) ? implode(', ', array_slice($analysis['semantic_themes'], 0, 3)) : '';
        $prompt = "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst eine komplette, hochwertige SEO-Suite basierend auf einer detaillierten Content-Analyse.\n\n=== CONTENT-INFORMATIONEN ===\nTitel: {$title}\nContent: " . substr($content, 0, 1000) . "\n\n=== INTELLIGENTE ANALYSE-ERGEBNISSE ===\nPrimäre Keywords: {$primary_keywords}\nEmpfohlenes Focus-Keyword: {$focus_keyword_suggestion}\nLong-Tail Keywords: {$long_tail_keywords}\nSemantische Themen: {$semantic_themes}\nContent-Qualität: " . ($analysis['content_quality']['overall_score'] ?? 'N/A') . "/100\nReadability-Score: " . ($analysis['readability_score'] ?? 'N/A') . "/100\n\n=== BUSINESS-KONTEXT ===\n{$business_context}\n{$canton_text}\n\n=== OPTIMIERUNGS-ANWEISUNGEN ===\n{$tone_instruction}\n\n=== PREMIUM-PROMPT ===\n{$premium_prompt}\n\n=== AUFGABE ===\nErstelle basierend auf der INTELLIGENTEN ANALYSE eine komplette SEO-Suite:\n\n1. **META_TITEL** (55-60 Zeichen):\n   - Nutze das empfohlene Focus-Keyword\n   - Berücksichtige semantische Themen\n   - Optimiert für Schweizer Suchverhalten\n   - Hohe Click-Through-Rate\n\n2. **META_BESCHREIBUNG** (150-155 Zeichen):\n   - Integriere primäre Keywords natürlich\n   - Nutze Long-Tail Keywords\n   - Klarer Call-to-Action\n   - Lokaler Bezug\n\n3. **FOCUS_KEYWORD** (1-3 Wörter):\n   - Basierend auf Keyword-Analyse\n   - Hohes Suchvolumen Schweiz\n   - Kommerzieller Intent\n   - Perfekt zum Content\n\nANTWORT-FORMAT:\nMETA_TITEL: [Meta-Titel hier]\nMETA_BESCHREIBUNG: [Meta-Beschreibung hier]\nFOCUS_KEYWORD: [Focus-Keyword hier]\n\nAntworte NUR mit den drei Zeilen im Format, nichts anderes!";
        return $prompt;
    }

    /**
     * ⚠️ NEUE HILFSMETHODE: Intelligente Suite-Response parsen
     */
    private function parse_intelligent_suite_response($ai_response, $analysis = null) {
        $lines = explode("\n", trim($ai_response));
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
        if (empty($suite['meta_title']) || empty($suite['meta_description']) || empty($suite['focus_keyword'])) {
            $clean_lines = array_filter(array_map('trim', $lines));
            $clean_lines = array_values($clean_lines);
            if (count($clean_lines) >= 3) {
                if (empty($suite['meta_title'])) $suite['meta_title'] = $clean_lines[0];
                if (empty($suite['meta_description'])) $suite['meta_description'] = $clean_lines[1];
                if (empty($suite['focus_keyword'])) $suite['focus_keyword'] = $clean_lines[2];
            }
        }
        if ($analysis) {
            $suite['analysis_data'] = array(
                'primary_keywords' => $analysis['primary_keywords'] ?? array(),
                'long_tail_keywords' => $analysis['long_tail_keywords'] ?? array(),
                'content_quality_score' => $analysis['content_quality']['overall_score'] ?? 0,
                'readability_score' => $analysis['readability_score'] ?? 0,
                'semantic_themes' => $analysis['semantic_themes'] ?? array(),
                'processing_time' => $analysis['processing_time'] ?? 0,
                'keyword_strategy_confidence' => $analysis['keyword_strategy']['strategy_confidence'] ?? 0
            );
        }
        $suite['research_mode'] = 'intelligent';
        $suite['analysis_used'] = true;
        $suite['generation_timestamp'] = current_time('mysql');
        return $suite;
    }

    /**
     * ⚠️ FALLBACK-METHODE: Standard SEO-Suite mit Content-Analyse
     */
    private function generate_standard_seo_suite($post, $settings, $include_cantons = true, $premium_tone = false) {
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        
        // 🆕 CONTENT-ANALYSE für alle Methoden
        $content_keywords = $this->extract_content_keywords($title, $content);
        $page_context = $this->analyze_page_context($post, $settings);
        
        $business_context = $this->build_business_context($settings);
        $canton_text = $this->build_canton_context($settings, $include_cantons);
        $tone_instruction = $this->build_tone_instruction($settings, $premium_tone);
        $optimization_focus = $this->build_optimization_focus($settings);
        
        // 🎲 VARIATION: Zufällige Variationen für unterschiedliche Texte
        $variations = array(
            "Du bist ein SCHWEIZER SEO-EXPERTE und erstellst hochwertige SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-SPEZIALIST und erstellst optimale SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-PROFI und erstellst professionelle SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-BERATER und erstellst strategische SEO-Texte basierend auf Content-Analyse.",
            "Du bist ein SCHWEIZER SEO-OPTIMIERER und erstellst effektive SEO-Texte basierend auf Content-Analyse."
        );
        $random_variation = $variations[array_rand($variations)];
        
        $prompt = $random_variation . "

=== CONTENT-INFORMATIONEN ===
Titel: {$title}
Content: " . substr($content, 0, 800) . "

=== CONTENT-ANALYSE ===
Erkannte Kategorie: " . ($content_keywords['detected_category'] ?? 'unbekannt') . "
Haupt-Keywords: " . implode(', ', $content_keywords['main_keywords']) . "
Vertrauen: " . ($content_keywords['confidence'] ?? 0) . "

=== SEITEN-TYP-ANALYSE ===
Seiten-Typ: " . $page_context['page_type'] . "
SEO-Strategie: " . $page_context['seo_strategy'] . "

🚨 KRITISCHE CONTENT-REGEL:
PRIORITÄT: 1. CONTENT → 2. BRANCHE → 3. FIRMA

Verwende NUR Keywords aus der erkannten Kategorie!
- Falls Kategorie 'immobilien' → NUR Immobilien-Keywords verwenden!
- Falls Kategorie 'auto' → NUR Auto-Keywords verwenden!
- Falls Kategorie 'software' → NUR Software-Keywords verwenden!
- Falls Kategorie 'arzt' → NUR Arzt-Keywords verwenden!
- Falls Kategorie 'restaurant' → NUR Restaurant-Keywords verwenden!
- Falls Kategorie 'mode' → NUR Mode-Keywords verwenden!
- Falls Kategorie 'griffe' → NUR Griffe-Keywords verwenden!
- Falls Kategorie 'neolith' → NUR Neolith-Keywords verwenden!
- Falls Kategorie 'küche' → NUR Küchen-Keywords verwenden!
- Falls Kategorie 'backofen' → NUR Backofen-Keywords verwenden!
- Falls Kategorie 'spüle' → NUR Spüle-Keywords verwenden!

❌ VERBOTEN: Falsche Produktkategorien mischen!
❌ NIEMALS: Auto-Seite mit Restaurant-Keywords!
❌ NIEMALS: Software-Seite mit Mode-Keywords!
❌ NIEMALS: Arzt-Seite mit Auto-Keywords!

✅ ERLAUBT: Content-spezifische Keywords + Branche + Firma

{$canton_text}

{$tone_instruction}
{$optimization_focus}

🎯 TEXTLÄNGEN-ANFORDERUNGEN (KRITISCH):

1. **META_TITEL**:
   - OPTIMAL: 55-65 Zeichen (NICHT genau 60!)
   - Nutze den verfügbaren Platz intelligent!
   - Focus-Keyword intelligent integriert
   - WICHTIG: Vollständige Sätze, keine Abkürzungen!

2. **META_BESCHREIBUNG**:
   - OPTIMAL: 150-165 Zeichen (NICHT genau 160!)
   - Fülle den Platz intelligent aus!
   - Klarer Call-to-Action
   - WICHTIG: Vollständige Sätze, Kantone IMMER AUSGESCHRIEBEN!
   - NIEMALS: Bern und S..... → IMMER: Bern und Solothurn

3. **FOCUS_KEYWORD** (1-4 Wörter):
   - PRODUKT/SERVICE-spezifische Begriffe
   - Vermeide generische Adjektive
   - Hohes kommerzielles Suchvolumen

=== ANTWORT-FORMAT (exakt so) ===
META_TITEL: [dein optimierter Meta-Titel]
META_BESCHREIBUNG: [deine optimierte Meta-Beschreibung]
FOCUS_KEYWORD: [dein optimiertes Focus-Keyword]";
        
        $ai_response = $this->call_ai_api($prompt, $settings);
        $suite = $this->parse_intelligent_suite_response($ai_response);
        $suite['research_mode'] = 'standard';
        $suite['analysis_used'] = false;
        $suite['content_analysis'] = $content_keywords;
        return $suite;
    }
    
    /**
     * Extrahiert Haupt-Keywords aus Titel und Content
     * 
     * @param string $title Post-Titel
     * @param string $content Post-Content
     * @return array Extrahierte Keywords
     */
    private function extract_content_keywords($title, $content) {
        $keywords = array();
        
        // Haupt-Keywords aus Titel extrahieren
        $title_words = explode(' ', strtolower($title));
        foreach ($title_words as $word) {
            $word = trim($word, '.,!?;:-');
            if (strlen($word) > 3) {
                $keywords[] = $word;
            }
        }
        
        // Content nach Hauptthemen durchsuchen
        $content_lower = strtolower($content);
        
        // 🚀 UNIVERSELLE PRODUKTKATEGORIEN FÜR ALLE BRANCHEN
        $product_categories = array(
            // 🏠 BAU & IMMOBILIEN
            'immobilien' => array('haus', 'häuser', 'wohnung', 'wohnungen', 'immobilie', 'immobilien', 'eigentum', 'miete', 'kauf', 'verkauf'),
            'bau' => array('bau', 'bauen', 'renovierung', 'sanierung', 'umbau', 'bauplanung', 'architekt', 'baustelle'),
            'dach' => array('dach', 'dächer', 'dachdecker', 'ziegel', 'dachziegel', 'dachfenster', 'dachboden'),
            'heizung' => array('heizung', 'heizungen', 'wärme', 'heizkörper', 'kessel', 'wärmepumpe', 'solar'),
            'sanitär' => array('sanitär', 'bad', 'badezimmer', 'toilette', 'dusche', 'badewanne', 'waschbecken'),
            
            // 🚗 AUTOMOTIVE
            'auto' => array('auto', 'autos', 'fahrzeug', 'fahrzeuge', 'pkw', 'limousine', 'kombi', 'suv'),
            'motorrad' => array('motorrad', 'motorräder', 'motorroller', 'scooter', 'chopper', 'enduro'),
            'reifen' => array('reifen', 'pneus', 'winterreifen', 'sommerreifen', 'ganzjahresreifen'),
            'werkstatt' => array('werkstatt', 'werkstätten', 'reparatur', 'wartung', 'service', 'inspektion'),
            'tuning' => array('tuning', 'tunen', 'modifikation', 'umbau', 'sportauspuff', 'felgen'),
            
            // 💻 TECHNOLOGIE & IT
            'software' => array('software', 'app', 'anwendung', 'programm', 'system', 'lösung'),
            'hardware' => array('hardware', 'computer', 'laptop', 'pc', 'server', 'netzwerk'),
            'webdesign' => array('webdesign', 'website', 'homepage', 'internet', 'online', 'web'),
            'smartphone' => array('smartphone', 'handy', 'iphone', 'android', 'telefon', 'mobil'),
            'gaming' => array('gaming', 'spiel', 'spiele', 'gamer', 'konsole', 'pc-spiele'),
            
            // 🏥 GESUNDHEIT & MEDIZIN
            'arzt' => array('arzt', 'ärztin', 'praxis', 'behandlung', 'diagnose', 'therapie'),
            'zahnarzt' => array('zahnarzt', 'zahnärztin', 'zähne', 'zahnbehandlung', 'implantat', 'prothese'),
            'apotheke' => array('apotheke', 'medikament', 'arznei', 'rezept', 'heilmittel'),
            'krankenhaus' => array('krankenhaus', 'klinik', 'station', 'operation', 'pflege'),
            'fitness' => array('fitness', 'training', 'sport', 'gym', 'studio', 'workout'),
            
            // 🎓 BILDUNG & AUSBILDUNG
            'schule' => array('schule', 'schulen', 'grundschule', 'realschule', 'gymnasium', 'lehrer'),
            'universität' => array('universität', 'uni', 'hochschule', 'studium', 'student', 'professor'),
            'kurs' => array('kurs', 'kurse', 'seminar', 'schulung', 'fortbildung', 'weiterbildung'),
            'sprachkurs' => array('sprachkurs', 'sprache', 'englisch', 'deutsch', 'französisch', 'italienisch'),
            'musikschule' => array('musikschule', 'musik', 'instrument', 'klavier', 'gitarre', 'geige'),
            
            // 🍽️ GASTRONOMIE
            'restaurant' => array('restaurant', 'gaststätte', 'lokal', 'küche', 'menü', 'speisekarte'),
            'café' => array('café', 'kaffee', 'kuchen', 'torte', 'frühstück', 'brunch'),
            'catering' => array('catering', 'bewirtung', 'verpflegung', 'party', 'event', 'feier'),
            'bäckerei' => array('bäckerei', 'bäcker', 'brot', 'brötchen', 'kuchen', 'gebäck'),
            'metzgerei' => array('metzgerei', 'metzger', 'fleisch', 'wurst', 'schinken', 'salami'),
            
            // 👕 MODE & BEAUTY
            'mode' => array('mode', 'kleidung', 'bekleidung', 'fashion', 'outfit', 'stil'),
            'schuhe' => array('schuhe', 'schuh', 'stiefel', 'sneaker', 'sandalen', 'pumps'),
            'kosmetik' => array('kosmetik', 'makeup', 'schminke', 'creme', 'pflege', 'beauty'),
            'frisör' => array('frisör', 'friseur', 'haar', 'haarschnitt', 'färben', 'styling'),
            'parfüm' => array('parfüm', 'duft', 'eau', 'parfum', 'spray', 'creme'),
            
            // 🎨 KUNST & KULTUR
            'kunst' => array('kunst', 'künstler', 'gemälde', 'skulptur', 'galerie', 'ausstellung'),
            'musik' => array('musik', 'konzert', 'band', 'musiker', 'live', 'konzertsaal'),
            'theater' => array('theater', 'bühne', 'schauspiel', 'aufführung', 'premiere', 'drama'),
            'museum' => array('museum', 'ausstellung', 'exponat', 'sammlung', 'geschichte'),
            'buch' => array('buch', 'bücher', 'buchhandlung', 'roman', 'literatur', 'lesen'),
            
            // 🏭 INDUSTRIE & HANDWERK
            'maschine' => array('maschine', 'maschinen', 'produktion', 'fertigung', 'industrie'),
            'werkzeug' => array('werkzeug', 'werkzeuge', 'bohrer', 'säge', 'hammer', 'schrauber'),
            'elektrik' => array('elektrik', 'elektriker', 'strom', 'elektrisch', 'installation'),
            'maler' => array('maler', 'streichen', 'farbe', 'anstrich', 'tapete', 'renovierung'),
            'schreiner' => array('schreiner', 'tischler', 'möbel', 'holz', 'massiv', 'einrichtung'),
            
            // 🏠 KÜCHE & HAUSHALT (Original erweitert)
            'küche' => array('küche', 'küchen', 'kochen', 'küchenplanung', 'kücheneinrichtung', 'küchenmöbel'),
            'griffe' => array('griff', 'griffe', 'handgriff', 'türgriff', 'küchengriff', 'küchengriffe', 'schrankgriff', 'schrankgriffe'),
            'neolith' => array('neolith', 'keramik', 'arbeitsplatte', 'arbeitsplatten', 'neolith-keramik', 'keramik-platte'),
            'backofen' => array('backofen', 'backöfen', 'einbau-backofen', 'herd', 'herde', 'kochfeld'),
            'spüle' => array('spüle', 'spülen', 'küchenspüle', 'waschbecken', 'spülbecken'),
            'kühlschrank' => array('kühlschrank', 'kühlschränke', 'gefrierschrank', 'kühl-gefrier', 'side-by-side'),
            'geschirrspüler' => array('geschirrspüler', 'spülmaschine', 'spüler', 'einbau-spüler'),
            'waschmaschine' => array('waschmaschine', 'trockner', 'waschen', 'wäsche', 'laundry'),
            
            // 🏠 HAUS & GARTEN
            'garten' => array('garten', 'gärten', 'gartengestaltung', 'landschaftsbau', 'pflanzen'),
            'pool' => array('pool', 'schwimmbad', 'whirlpool', 'sauna', 'wellness'),
            'terrasse' => array('terrasse', 'balkon', 'dachterrasse', 'belag', 'fliesen'),
            'garage' => array('garage', 'carport', 'einfahrt', 'auffahrt', 'parkplatz'),
            'keller' => array('keller', 'kellergeschoss', 'weinkeller', 'lagerraum'),
            
            // 🎯 DIENSTLEISTUNGEN
            'beratung' => array('beratung', 'consulting', 'berater', 'coaching', 'mentoring'),
            'recht' => array('recht', 'anwalt', 'rechtanwalt', 'notar', 'gericht', 'rechtsschutz'),
            'versicherung' => array('versicherung', 'versicherungen', 'police', 'beitrag', 'schaden'),
            'steuer' => array('steuer', 'steuerberater', 'buchhaltung', 'finanzen', 'abrechnung'),
            'reise' => array('reise', 'reisen', 'urlaub', 'ferien', 'hotel', 'flug'),
            
            // 🏪 EINZELHANDEL & VERKAUF
            'shop' => array('shop', 'laden', 'geschäft', 'verkauf', 'handel', 'retail'),
            'online-shop' => array('online-shop', 'e-commerce', 'internet-shop', 'web-shop', 'online-handel'),
            'großhandel' => array('großhandel', 'großhändler', 'wholesale', 'import', 'export'),
            'markt' => array('markt', 'supermarkt', 'discount', 'discounter', 'lebensmittel'),
            'tankstelle' => array('tankstelle', 'benzin', 'diesel', 'kraftstoff', 'tanken')
        );
        
        $detected_category = null;
        $max_matches = 0;
        
        foreach ($product_categories as $category => $keywords_array) {
            $matches = 0;
            foreach ($keywords_array as $keyword) {
                if (strpos($content_lower, $keyword) !== false || strpos(strtolower($title), $keyword) !== false) {
                    $matches++;
                }
            }
            if ($matches > $max_matches) {
                $max_matches = $matches;
                $detected_category = $category;
            }
        }
        
        return array(
            'main_keywords' => $keywords,
            'detected_category' => $detected_category,
            'confidence' => $max_matches
        );
    }

    /**
     * Analysiert Seiten-Kontext (Legal vs Commercial)
     * 
     * @param WP_Post $post WordPress Post
     * @param array $settings Settings
     * @return array Context mit Seiten-Typ und Anweisungen
     */
    private function analyze_page_context($post, $settings) {
        $title = strtolower($post->post_title);
        $content = strtolower(wp_strip_all_tags($post->post_content));
        
        // Legal/Administrative Seiten erkennen
        $legal_keywords = array(
            'datenschutz', 'impressum', 'agb', 'nutzungsbedingungen',
            'widerruf', 'rechtlich', 'haftung', 'disclaimer', 'cookies',
            'terms', 'privacy', 'legal', 'geschäftsbedingungen'
        );
        
        $is_legal = false;
        foreach ($legal_keywords as $keyword) {
            if (strpos($title, $keyword) !== false) {
                $is_legal = true;
                break;
            }
        }
        
        if ($is_legal) {
            return array(
                'page_type' => 'legal',
                'seo_strategy' => 'informational',
                'use_business_context' => false,
                'use_cantons' => false,
                'instructions' => '🚨 RECHTLICHE SEITE: Erstelle sachliche, informative Meta-Texte OHNE Marketing, OHNE Produkte, OHNE Kantone!'
            );
        }
        
        return array(
            'page_type' => 'commercial',
            'seo_strategy' => 'conversion',
            'use_business_context' => true,
            'use_cantons' => true,
            'instructions' => 'Kommerzielle Seite: Verkaufsorientierte Texte mit Call-to-Action.'
        );
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
    
    /**
     * Quick Provider-Test (aus Hauptdatei verschoben)
     */
    public function quick_test_provider($provider, $api_key) {
        try {
            $test_settings = array(
                'api_provider' => $provider,
                'api_key' => $api_key,
                'model' => $this->get_default_model_for_provider($provider)
            );
            
            $result = $this->test_connection($test_settings);
            
            return array(
                'status' => $result['success'] ? 'success' : 'error',
                'message' => $result['message']
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Test fehlgeschlagen: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Default-Model für Provider abrufen
     */
    private function get_default_model_for_provider($provider) {
        $defaults = array(
            'openai' => 'gpt-4o-mini',
            'anthropic' => 'claude-3-5-sonnet-20241022',
            'gemini' => 'gemini-1.5-flash-latest'
        );
        
        return $defaults[$provider] ?? 'gpt-4o-mini';
    }
    
    /**
     * OpenAI Quick-Test (aus Hauptdatei verschoben)
     */
    public function quick_test_openai($api_key) {
        try {
            $test_settings = array(
                'api_provider' => 'openai',
                'api_key' => $api_key,
                'model' => 'gpt-4o-mini'
            );
            
            $test_prompt = "Teste OpenAI-Verbindung. Antworte mit: 'OpenAI funktioniert!'";
            $response = $this->call_ai_api($test_prompt, $test_settings);
            
            return array(
                'status' => 'success',
                'message' => 'OpenAI: ' . $response
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'OpenAI Test fehlgeschlagen: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Anthropic Quick-Test (aus Hauptdatei verschoben)
     */
    public function quick_test_anthropic($api_key) {
        try {
            $test_settings = array(
                'api_provider' => 'anthropic',
                'api_key' => $api_key,
                'model' => 'claude-3-5-sonnet-20241022'
            );
            
            $test_prompt = "Teste Anthropic-Verbindung. Antworte mit: 'Anthropic funktioniert!'";
            $response = $this->call_ai_api($test_prompt, $test_settings);
            
            return array(
                'status' => 'success',
                'message' => 'Anthropic: ' . $response
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Anthropic Test fehlgeschlagen: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Gemini Quick-Test (aus Hauptdatei verschoben)
     */
    public function quick_test_gemini($api_key) {
        try {
            $test_settings = array(
                'api_provider' => 'gemini',
                'api_key' => $api_key,
                'model' => 'gemini-1.5-flash-latest'
            );
            
            $test_prompt = "Teste Gemini-Verbindung. Antworte mit: 'Gemini funktioniert!'";
            $response = $this->call_ai_api($test_prompt, $test_settings);
            
            return array(
                'status' => 'success',
                'message' => 'Gemini: ' . $response
            );
            
        } catch (Exception $e) {
            return array(
                'status' => 'error',
                'message' => 'Gemini Test fehlgeschlagen: ' . $e->getMessage()
            );
        }
    }

    // ========================================================================
    // 🖼️ NEU: INTELLIGENTE BILD-SEO GENERIERUNG
    // ========================================================================
    
    /**
     * Generiert intelligente Alt-Texte und Titel für Bilder
     * Analysiert den Kontext der Seite, Dateinamen, und Keywords
     * 
     * @param array $image_data Bild-Informationen (ID, filename, title, page_context etc.)
     * @param array $settings KI-Einstellungen
     * @return array Generierte SEO-Daten (alt_text, title, caption, description)
     */
    public function generate_image_seo($image_data, $settings) {
        $filename = $image_data['filename'] ?? '';
        $current_title = $image_data['title'] ?? '';
        $current_alt = $image_data['current_alt'] ?? '';
        $page_title = $image_data['page_title'] ?? '';
        $page_content = $image_data['page_content'] ?? '';
        $page_keywords = $image_data['page_keywords'] ?? '';
        $image_caption = $image_data['caption'] ?? '';
        
        // Business-Kontext aufbauen
        $business_context = $this->build_business_context($settings);
        $canton_text = $this->build_canton_context($settings, !empty($settings['target_cantons']));
        
        // Dateinamen analysieren für Kontext-Hinweise
        $filename_clean = pathinfo($filename, PATHINFO_FILENAME);
        $filename_words = str_replace(array('-', '_', '.'), ' ', $filename_clean);
        
        // ✅ NEU v4.25.0: Erweiterte Content-Analyse (bis zu 500 Wörter statt 100)
        $content_excerpt = '';
        if (!empty($page_content)) {
            $content_excerpt = wp_trim_words(wp_strip_all_tags($page_content), 500);
        }
        
        // ✅ NEU v4.25.0: Intelligente Kategorie-Erkennung aus dem Dateinamen
        $filename_hints = $this->analyze_filename_for_seo($filename_clean);
        
        // ✅ NEU v4.25.0: Kontext-Qualität bewerten
        $context_quality = 'hoch';
        if (empty($page_title) && empty($page_content)) {
            $context_quality = 'niedrig';
        } elseif (empty($page_content)) {
            $context_quality = 'mittel';
        }
        
        $prompt = "Du bist ein SCHWEIZER SEO-EXPERTE für Bild-Optimierung und Barrierefreiheit.

=== BILD-INFORMATIONEN ===
Dateiname: {$filename}
Dateiname-Analyse: {$filename_words}
Aktueller Titel: {$current_title}
Aktuelle Bildbeschriftung: {$image_caption}
Aktueller Alt-Text: {$current_alt}
Dateiname-Hinweise: {$filename_hints}

=== SEITEN-KONTEXT (Kontext-Qualität: {$context_quality}) ===
Seiten-Titel: {$page_title}
Seiten-Keywords: {$page_keywords}
Seiten-Content (Auszug): {$content_excerpt}

=== BUSINESS-KONTEXT ===
{$business_context}
{$canton_text}

=== INTELLIGENTE ANALYSE-ANWEISUNGEN ===
1. LESE den gesamten Seiten-Kontext gründlich durch
2. VERSTEHE das Thema der Seite und den Zusammenhang zum Bild
3. ANALYSIERE den Dateinamen auf inhaltliche Hinweise (z.B. 'kuche-modern-weiss' = 'Moderne weisse Küche')
4. KOMBINIERE Seiten-Keywords mit dem Bild-Kontext
5. Wenn der Seiten-Kontext eine bestimmte Dienstleistung/Produkt beschreibt, beziehe den Alt-Text DARAUF
6. VERMEIDE generische Texte wie 'Bild' oder 'Foto' - sei SPEZIFISCH!
7. Verwende lokale Schweizer Bezüge wo sinnvoll

=== AUFGABE ===
Erstelle SEO-optimierte Texte für dieses Bild:

1. **ALT_TEXT**: Beschreibender Alt-Text (80-125 Zeichen)
   - Beschreibe WAS auf dem Bild zu sehen ist, basierend auf Dateiname + Seitenkontext
   - Integriere das wichtigste Keyword aus dem Seiten-Kontext
   - Natürlich lesbar, KEIN Keyword-Stuffing
   - Für Barrierefreiheit UND Google Image Search optimiert
   - Wenn Kontext-Qualität 'niedrig': Nutze den Dateinamen als Hauptquelle

2. **BILD_TITEL**: SEO-optimierter Bildtitel (40-70 Zeichen)
   - Keyword-reich aber natürlich
   - Inhaltlich passend zum Seitenthema

3. **CAPTION**: Bildunterschrift (50-120 Zeichen)
   - Informativer Zusatztext mit ergänzenden Keywords
   - Kann Standort/Kanton enthalten wenn relevant

=== ANTWORT-FORMAT (exakt so, NUR diese 3 Zeilen!) ===
ALT_TEXT: [dein optimierter Alt-Text]
BILD_TITEL: [dein optimierter Bildtitel]
CAPTION: [deine optimierte Bildunterschrift]";
        
        $ai_response = $this->call_ai_api($prompt, $settings);
        
        return $this->parse_image_seo_response($ai_response);
    }
    
    /**
     * ✅ NEU v4.25.0: Analysiert Dateinamen für SEO-Hinweise
     * 
     * @param string $filename_clean Bereinigter Dateiname ohne Extension
     * @return string Analyse-Ergebnis als Text
     */
    private function analyze_filename_for_seo($filename_clean) {
        if (empty($filename_clean)) {
            return 'Keine Hinweise verfügbar';
        }
        
        // Trennzeichen durch Leerzeichen ersetzen
        $words = preg_split('/[-_\s.]+/', strtolower($filename_clean));
        $words = array_filter($words, function($w) {
            return strlen($w) > 2 && !in_array($w, array('img', 'bild', 'foto', 'photo', 'image', 'pic', 'dsc', 'screenshot', 'screen'));
        });
        
        if (empty($words)) {
            return 'Generischer Dateiname ohne inhaltliche Hinweise';
        }
        
        // Zahlen und IDs entfernen
        $meaningful_words = array_filter($words, function($w) {
            return !preg_match('/^\d+$/', $w) && strlen($w) > 2;
        });
        
        if (empty($meaningful_words)) {
            return 'Nur technische Bezeichnungen im Dateinamen';
        }
        
        return 'Erkannte Begriffe: ' . implode(', ', array_values($meaningful_words));
    }
    
    /**
     * Parst die AI-Antwort für Bild-SEO
     */
    private function parse_image_seo_response($ai_response) {
        $lines = explode("\n", trim($ai_response));
        $result = array(
            'alt_text' => '',
            'title' => '',
            'caption' => ''
        );
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'ALT_TEXT:') === 0) {
                $result['alt_text'] = trim(str_replace('ALT_TEXT:', '', $line));
            } elseif (strpos($line, 'BILD_TITEL:') === 0) {
                $result['title'] = trim(str_replace('BILD_TITEL:', '', $line));
            } elseif (strpos($line, 'CAPTION:') === 0) {
                $result['caption'] = trim(str_replace('CAPTION:', '', $line));
            }
        }
        
        // Fallback: Wenn Parsing fehlschlägt, versuche zeilenbasiert
        if (empty($result['alt_text']) && !empty($lines)) {
            $clean_lines = array_filter(array_map('trim', $lines));
            $clean_lines = array_values($clean_lines);
            if (count($clean_lines) >= 1) $result['alt_text'] = $clean_lines[0];
            if (count($clean_lines) >= 2) $result['title'] = $clean_lines[1];
            if (count($clean_lines) >= 3) $result['caption'] = $clean_lines[2];
        }
        
        return $result;
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