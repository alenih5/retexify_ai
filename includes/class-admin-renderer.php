<?php
if (!defined('ABSPATH')) exit;

class ReTexify_Admin_Renderer {
    private $ai_engine;
    private $export_import_manager;
    public function __construct($ai_engine = null, $export_import_manager = null) {
        $this->ai_engine = $ai_engine;
        $this->export_import_manager = $export_import_manager;
    }
    public function render_admin_page() {
        $ai_settings = get_option('retexify_ai_settings', array());
        $ai_enabled = $this->is_ai_enabled();
        $api_keys = $this->get_all_api_keys();
        $export_import_available = $this->export_import_manager !== null;
        $available_providers = array();
        if ($this->ai_engine && method_exists($this->ai_engine, 'get_available_providers')) {
            $available_providers = $this->ai_engine->get_available_providers();
        } else {
            $available_providers = array(
                'openai' => 'OpenAI (GPT-4o, GPT-4o Mini)',
                'anthropic' => 'Anthropic Claude (3.5 Sonnet, Haiku)',
                'gemini' => 'Google Gemini (Pro, Flash)'
            );
        }
        ?>
        <div class="retexify-light-wrap">
            <div class="retexify-header">
                <h1>🇨🇭 ReTexify AI - Universeller SEO-Optimizer</h1>
                <p class="retexify-subtitle">Intelligente SEO-Optimierung für alle Branchen • Multi-KI-Support (OpenAI, Claude, Gemini) • Version <?php echo RETEXIFY_VERSION; ?></p>
            </div>
            <div class="retexify-tabs">
                <div class="retexify-tab-nav">
                    <button class="retexify-tab-button active" data-tab="dashboard">📊 Dashboard</button>
                    <button class="retexify-tab-button" data-tab="seo-optimizer">🚀 SEO-Optimizer</button>
                    <button class="retexify-tab-button" data-tab="images-seo">🖼️ Bilder-SEO</button>
                    <button class="retexify-tab-button" data-tab="direct-text">🤖 Direkte Textgenerierung</button>
                    <button class="retexify-tab-button" data-tab="ai-settings">⚙️ KI-Einstellungen</button>
                    <?php if (
                        $export_import_available): ?>
                    <button class="retexify-tab-button" data-tab="export-import">📤 Export/Import</button>
                    <?php endif; ?>
                    <button class="retexify-tab-button" data-tab="system">🔧 System</button>
                </div>
                <!-- Dashboard Tab -->
                <div class="retexify-tab-content active" id="retexify-tab-dashboard">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>📊 Content-Dashboard</h2>
                            <div class="retexify-header-badge" id="retexify-refresh-stats-badge">
                                🔄 Aktualisieren
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="retexify-dashboard-content">
                                <div class="retexify-loading">Lade Dashboard...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- SEO-Optimizer Tab -->
                <div class="retexify-tab-content" id="retexify-tab-seo-optimizer">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🚀 Intelligenter SEO-Optimizer</h2>
                            <div class="retexify-header-badge">
                                <?php if ($ai_enabled): ?>
                                    🤖 Aktiv: <?php echo $available_providers[$ai_settings['api_provider']] ?? 'Unbekannt'; ?>
                                <?php else: ?>
                                    ⚠️ KI-Text-Generierung deaktiviert
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <!-- SEO Controls -->
                            <div class="retexify-seo-controls">
                                <div class="retexify-control-group">
                                    <label for="seo-post-type">Post-Typ wählen:</label>
                                    <select id="seo-post-type" class="retexify-select">
                                        <option value="post">Beiträge</option>
                                        <option value="page">Seiten</option>
                                    </select>
                                </div>
                                <button type="button" id="retexify-load-seo-content" class="retexify-btn retexify-btn-primary">
                                    📄 SEO-Content laden
                                </button>
                            </div>
                            <!-- SEO Content List -->
                            <div id="retexify-seo-content-list" style="display: none;">
                                <div class="retexify-seo-navigation">
                                    <button type="button" id="retexify-seo-prev" class="retexify-btn retexify-btn-secondary" disabled>
                                        ← Vorherige
                                    </button>
                                    <span id="retexify-seo-counter" class="retexify-counter">1 / 10</span>
                                    <button type="button" id="retexify-seo-next" class="retexify-btn retexify-btn-secondary">
                                        Nächste →
                                    </button>
                                </div>
                                <!-- Current Page Info -->
                                <div class="retexify-current-page-info">
                                    <h3 id="retexify-current-page-title">Seite wird geladen...</h3>
                                    <div class="retexify-page-meta">
                                        <p id="retexify-page-info">Seiten-Informationen...</p>
                                        <div class="retexify-page-actions">
                                            <a id="retexify-page-url" href="#" target="_blank" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                🔗 Seite anzeigen
                                            </a>
                                            <a id="retexify-edit-page" href="#" target="_blank" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                ✏️ Bearbeiten
                                            </a>
                                            <button type="button" id="retexify-show-content" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                                📄 Vollständigen Content anzeigen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Content Display -->
                                <div id="retexify-full-content" class="retexify-content-display" style="display: none;">
                                    <h4>📄 Vollständiger Seiteninhalt:</h4>
                                    <div id="retexify-content-text" class="retexify-content-box">
                                        Content wird geladen...
                                    </div>
                                    <div class="retexify-content-stats">
                                        <span id="retexify-word-count">0 Wörter</span> • 
                                        <span id="retexify-char-count">0 Zeichen</span>
                                    </div>
                                </div>
                                <!-- SEO Editing Area -->
                                <div class="retexify-seo-editor">
                                    <div class="retexify-seo-current">
                                        <h4>🔍 Aktuelle SEO-Daten:</h4>
                                        <div class="retexify-seo-grid">
                                            <div class="retexify-seo-item">
                                                <label>Meta-Titel (aktuell):</label>
                                                <div id="retexify-current-meta-title" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label>Meta-Beschreibung (aktuell):</label>
                                                <div id="retexify-current-meta-description" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label>Focus-Keyword (aktuell):</label>
                                                <div id="retexify-current-focus-keyword" class="retexify-current-value">
                                                    Nicht gesetzt
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="retexify-seo-new">
                                        <h4>✨ Neue SEO-Daten (KI-optimiert oder manuell):</h4>
                                        <div class="retexify-seo-grid">
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-meta-title">Meta-Titel (neu):</label>
                                                <input type="text" id="retexify-new-meta-title" class="retexify-input" placeholder="Neuer Meta-Titel...">
                                                <div class="retexify-input-footer">
                                                    <div class="retexify-char-counter">
                                                        <span id="title-chars">0</span>/60 Zeichen
                                                    </div>
                                                    <button type="button" class="retexify-generate-single" data-type="meta_title" <?php if (!$ai_enabled) echo 'disabled title=\'KI-Text-Generierung nur mit API-Key möglich\''; ?>>
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-meta-description">Meta-Beschreibung (neu):</label>
                                                <textarea id="retexify-new-meta-description" class="retexify-textarea" placeholder="Neue Meta-Beschreibung..."></textarea>
                                                <div class="retexify-input-footer">
                                                    <div class="retexify-char-counter">
                                                        <span id="description-chars">0</span>/160 Zeichen
                                                    </div>
                                                    <button type="button" class="retexify-generate-single" data-type="meta_description" <?php if (!$ai_enabled) echo 'disabled title=\'KI-Text-Generierung nur mit API-Key möglich\''; ?>>
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="retexify-seo-item">
                                                <label for="retexify-new-focus-keyword">Focus-Keyword (neu):</label>
                                                <input type="text" id="retexify-new-focus-keyword" class="retexify-input" placeholder="Neues Focus-Keyword...">
                                                <div class="retexify-input-footer keyword">
                                                    <button type="button" class="retexify-generate-single" data-type="focus_keyword" <?php if (!$ai_enabled) echo 'disabled title=\'KI-Text-Generierung nur mit API-Key möglich\''; ?>>
                                                        🤖 Meta-Text generieren
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Generation Options -->
                                        <div class="retexify-generation-options">
                                            <h5>🛠️ Generierungs-Optionen:</h5>
                                            <label class="retexify-checkbox">
                                                <input type="checkbox" id="retexify-include-cantons" checked <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                                Schweizer Kantone berücksichtigen
                                            </label>
                                            <label class="retexify-checkbox">
                                                <input type="checkbox" id="retexify-premium-tone" checked <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                                Premium Business-Ton verwenden
                                            </label>
                                        </div>
                                        <!-- Action Buttons -->
                                        <div class="retexify-seo-actions">
                                            <button type="button" id="retexify-generate-all-seo" class="retexify-btn retexify-btn-primary retexify-btn-large" <?php if (!$ai_enabled) echo 'disabled title=\'KI-Text-Generierung nur mit API-Key möglich\''; ?>>
                                                ✨ Alle Texte generieren
                                            </button>
                                            <button type="button" id="retexify-save-seo-texts" class="retexify-btn retexify-btn-success retexify-btn-large">
                                                💾 SEO-Daten speichern
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- KI-Einstellungen Tab -->
                <div class="retexify-tab-content" id="retexify-tab-ai-settings">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>⚙️ KI-Einstellungen</h2>
                            <div class="retexify-header-badge">
                                🤖 Multi-KI Support (OpenAI, Claude, Gemini)
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <form id="retexify-ai-settings-form">
                                <div class="retexify-settings-grid">
                                    <!-- API Settings -->
                                    <div class="retexify-settings-group retexify-settings-provider">
                                        <h3>🔑 KI-Provider & API-Einstellungen</h3>
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-provider">KI-Provider wählen:</label>
                                            <select id="ai-provider" name="api_provider" class="retexify-select">
                                                <?php foreach ($available_providers as $provider_key => $provider_name): ?>
                                                <option value="<?php echo esc_attr($provider_key); ?>" 
                                                        <?php selected($ai_settings['api_provider'] ?? 'openai', $provider_key); ?>>
                                                    <?php echo esc_html($provider_name); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small>Wählen Sie Ihren bevorzugten KI-Provider</small>
                                        </div>
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-api-key">API-Schlüssel:</label>
                                            <input type="password" id="ai-api-key" name="api_key" 
                                                   value="<?php echo esc_attr($api_keys[$ai_settings['api_provider'] ?? 'openai'] ?? ''); ?>" 
                                                   class="retexify-input" placeholder="Ihr API-Schlüssel...">
                                            <small id="api-key-help">
                                                OpenAI: Erhältlich auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a><br>
                                                Anthropic: Erhältlich auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a><br>
                                                Google: Erhältlich auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a>
                                            </small>
                                        </div>
                                        <div class="retexify-field retexify-field-short">
                                            <label for="ai-model">KI-Modell:</label>
                                            <select id="ai-model" name="model" class="retexify-select">
                                                <option value="gpt-4o-mini">GPT-4o Mini (Empfohlen)</option>
                                                <option value="gpt-4o">GPT-4o (Premium)</option>
                                                <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet</option>
                                                <option value="gemini-1.5-flash-latest">Gemini 1.5 Flash</option>
                                            </select>
                                            <small id="model-help">Das Modell bestimmt Qualität und Kosten der KI-Generierung</small>
                                        </div>
                                        <div class="retexify-provider-comparison">
                                            <h4 id="current-provider-title">📊 OpenAI GPT:</h4>
                                            <div id="current-provider-info" class="retexify-provider-card">
                                                <ul>
                                                    <li>Sehr günstig (GPT-4o Mini)</li>
                                                    <li>Bewährt für SEO</li>
                                                    <li>Schnell & zuverlässig</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Business Context -->
                                    <div class="retexify-settings-group retexify-settings-business">
                                        <h3>🏢 Business-Kontext</h3>
                                        <div class="retexify-field">
                                            <label for="ai-business-context">Ihr Business/Branche:</label>
                                            <textarea id="ai-business-context" name="business_context" 
                                                      class="retexify-textarea" rows="3"
                                                      placeholder="z.B. Online-Shop für Sportartikel, IT-Beratung für KMU, Restaurant in Zürich..."><?php echo esc_textarea($ai_settings['business_context'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="retexify-field">
                                            <label for="ai-target-audience">Zielgruppe:</label>
                                            <input type="text" id="ai-target-audience" name="target_audience" 
                                                   value="<?php echo esc_attr($ai_settings['target_audience'] ?? ''); ?>" 
                                                   class="retexify-input" 
                                                   placeholder="z.B. Geschäftskunden, Familien, Technik-Enthusiasten...">
                                        </div>
                                        <div class="retexify-field">
                                            <label for="ai-brand-voice">Markenstimme:</label>
                                            <select id="ai-brand-voice" name="brand_voice" class="retexify-select">
                                                <option value="professional" <?php selected($ai_settings['brand_voice'] ?? 'professional', 'professional'); ?>>
                                                    Professionell
                                                </option>
                                                <option value="friendly" <?php selected($ai_settings['brand_voice'] ?? '', 'friendly'); ?>>
                                                    Freundlich & einladend
                                                </option>
                                                <option value="expert" <?php selected($ai_settings['brand_voice'] ?? '', 'expert'); ?>>
                                                    Experte & kompetent
                                                </option>
                                                <option value="premium" <?php selected($ai_settings['brand_voice'] ?? '', 'premium'); ?>>
                                                    Premium & exklusiv
                                                </option>
                                                <option value="casual" <?php selected($ai_settings['brand_voice'] ?? '', 'casual'); ?>>
                                                    Locker & modern
                                                </option>
                                            </select>
                                        </div>
                                        <div class="retexify-field">
                                            <label for="seo-optimization-focus">Optimierungs-Fokus:</label>
                                            <select id="seo-optimization-focus" name="optimization_focus" class="retexify-select">
                                                <option value="complete_seo" <?php selected($ai_settings['optimization_focus'] ?? 'complete_seo', 'complete_seo'); ?>>Komplette SEO-Optimierung</option>
                                                <option value="local_seo_swiss" <?php selected($ai_settings['optimization_focus'] ?? '', 'local_seo_swiss'); ?>>Schweizer Local SEO</option>
                                                <option value="conversion" <?php selected($ai_settings['optimization_focus'] ?? '', 'conversion'); ?>>Conversion-optimiert</option>
                                                <option value="readability" <?php selected($ai_settings['optimization_focus'] ?? '', 'readability'); ?>>Lesbarkeit & Verständlichkeit</option>
                                                <option value="branding" <?php selected($ai_settings['optimization_focus'] ?? '', 'branding'); ?>>Markenaufbau & Trust</option>
                                                <option value="ecommerce" <?php selected($ai_settings['optimization_focus'] ?? '', 'ecommerce'); ?>>E-Commerce & Verkauf</option>
                                                <option value="b2b" <?php selected($ai_settings['optimization_focus'] ?? '', 'b2b'); ?>>B2B & Professional</option>
                                                <option value="news_blog" <?php selected($ai_settings['optimization_focus'] ?? '', 'news_blog'); ?>>News & Blog-Content</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Schweizer Kantone -->
                                    <div class="retexify-settings-group retexify-full-width">
                                        <h3>🇨🇭 Schweizer Kantone für Local SEO</h3>
                                        <p class="retexify-description">
                                            Wählen Sie die Kantone aus, in denen Ihr Business aktiv ist:
                                        </p>
                                        <div class="retexify-canton-grid">
                                            <?php 
                                            $swiss_cantons = $this->get_swiss_cantons();
                                            $selected_cantons = $ai_settings['target_cantons'] ?? array();
                                            foreach ($swiss_cantons as $code => $name): 
                                            ?>
                                            <label class="retexify-canton-item">
                                                <input type="checkbox" name="target_cantons[]" value="<?php echo $code; ?>" 
                                                       <?php checked(in_array($code, $selected_cantons)); ?>>
                                                <span class="retexify-canton-code"><?php echo $code; ?></span>
                                                <span class="retexify-canton-name"><?php echo $name; ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="retexify-canton-actions">
                                            <button type="button" id="retexify-select-all-cantons" class="retexify-btn retexify-btn-secondary">
                                                Alle auswählen
                                            </button>
                                            <button type="button" id="retexify-select-main-cantons" class="retexify-btn retexify-btn-secondary">
                                                Hauptkantone
                                            </button>
                                            <button type="button" id="retexify-clear-cantons" class="retexify-btn retexify-btn-secondary">
                                                Alle abwählen
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="retexify-settings-actions">
                                    <button type="button" id="retexify-ai-test-connection" class="retexify-btn retexify-btn-secondary">
                                        🔗 Verbindung testen
                                    </button>
                                    <button type="submit" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                        💾 Einstellungen speichern
                                    </button>
                                </div>
                            </form>
                            <div id="retexify-ai-settings-result"></div>
                        </div>
                    </div>
                </div>
                <!-- Bilder-SEO Tab -->
                <div class="retexify-tab-content" id="retexify-tab-images-seo">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🖼️ Intelligente Bilder-SEO</h2>
                            <div class="retexify-header-badge">
                                🤖 KI-generierte Alt-Texte & Bild-Beschreibungen
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div class="retexify-images-seo-section">
                                <h3>🖼️ Bilder-SEO für Ihre Website</h3>
                                <p>Optimieren Sie Ihre Bilder für Suchmaschinen mit KI-generierten Alt-Texten und Beschreibungen.</p>
                                
                                <div class="retexify-form-group">
                                    <label for="retexify-images-post-select">Post/Seite auswählen:</label>
                                    <select id="retexify-images-post-select" class="retexify-select">
                                        <option value="">-- Post/Seite auswählen --</option>
                                    </select>
                                </div>
                                
                                <button type="button" id="retexify-load-images-seo" class="retexify-btn retexify-btn-primary">
                                    🖼️ Bilder laden
                                </button>
                                
                                <div id="retexify-images-seo-interface" class="retexify-images-seo-interface" style="display: none;">
                                    <!-- Dynamisch per JavaScript gefüllt -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Direkte Textgenerierung Tab -->
                <div class="retexify-tab-content" id="retexify-tab-direct-text">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🤖 Direkte Textgenerierung</h2>
                            <div class="retexify-header-badge">
                                ✨ Freie Textgenerierung ohne Post-Bindung
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div class="retexify-direct-text-section">
                                <h3>🤖 KI-Textgenerierung</h3>
                                <p>Generieren Sie verschiedene Texttypen direkt mit KI - ohne an einen spezifischen Post gebunden zu sein.</p>
                                
                                <div class="retexify-form-group">
                                    <label for="retexify-direct-text-type">Text-Typ:</label>
                                    <select id="retexify-direct-text-type" class="retexify-select">
                                        <option value="meta_title">Meta-Titel</option>
                                        <option value="meta_description">Meta-Beschreibung</option>
                                        <option value="focus_keyword">Focus-Keyword</option>
                                        <option value="blog_post">Blog-Artikel</option>
                                        <option value="product_description">Produktbeschreibung</option>
                                        <option value="landing_page">Landing Page Text</option>
                                        <option value="email_newsletter">E-Mail Newsletter</option>
                                        <option value="social_media">Social Media Post</option>
                                    </select>
                                </div>
                                
                                <div class="retexify-form-group">
                                    <label for="retexify-direct-prompt">Prompt/Anweisung:</label>
                                    <textarea id="retexify-direct-prompt" class="retexify-textarea" rows="4" 
                                              placeholder="Beschreiben Sie, was Sie generieren möchten... z.B.: 'Erstelle einen Meta-Titel für eine Seite über Schweizer Schokolade'"></textarea>
                                </div>
                                
                                <button type="button" class="retexify-btn retexify-btn-primary retexify-generate-direct-text">
                                    🤖 Text generieren
                                </button>
                                
                                <div class="retexify-form-group">
                                    <label for="retexify-direct-result">Generierter Text:</label>
                                    <textarea id="retexify-direct-result" class="retexify-textarea" rows="6" 
                                              placeholder="Hier erscheint Ihr generierter Text..." readonly></textarea>
                                    <div class="retexify-text-stats">
                                        <span id="retexify-direct-char-count">0 Zeichen</span> | 
                                        <span id="retexify-direct-word-count">0 Wörter</span>
                                    </div>
                                </div>
                                
                                <div class="retexify-form-group">
                                    <button type="button" class="retexify-btn retexify-btn-secondary retexify-copy-direct-text">
                                        📋 Text kopieren
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($export_import_available): ?>
                <!-- Export/Import Tab -->
                <div class="retexify-tab-content" id="retexify-tab-export-import">
                    <div class="retexify-export-import-container">
                        <!-- Export Sektion -->
                        <div class="retexify-card">
                            <div class="retexify-card-header">
                                <h2>📤 Content Export</h2>
                                <div class="retexify-header-badge">CSV-Export für SEO-Daten</div>
                            </div>
                            <div class="retexify-card-body">
                                <div class="retexify-export-controls">
                                    <div class="retexify-export-options">
                                        <h4>📋 Export-Optionen:</h4>
                                        <!-- Post-Typen Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">🗂️ Post-Typen:</label>
                                            <div class="retexify-checkbox-grid">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_post_types[]" value="post" checked>
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Beiträge
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_post_types[]" value="page" checked>
                                                    <span class="retexify-checkbox-icon">📄</span>
                                                    Seiten
                                                </label>
                                            </div>
                                        </div>
                                        <!-- Status Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">🔘 Status:</label>
                                            <div class="retexify-checkbox-grid">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_status[]" value="publish" checked>
                                                    <span class="retexify-checkbox-icon">✅</span>
                                                    Veröffentlicht
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_status[]" value="draft">
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Entwürfe
                                                </label>
                                            </div>
                                        </div>
                                        <!-- Content-Typen Auswahl -->
                                        <div class="retexify-option-group">
                                            <label class="retexify-option-label">📋 Inhalte:</label>
                                            <div class="retexify-checkbox-grid" id="retexify-export-content-options">
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="title" checked>
                                                    <span class="retexify-checkbox-icon">🏷️</span>
                                                    Titel
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_meta_title" checked>
                                                    <span class="retexify-checkbox-icon">🎯</span>
                                                    Yoast Meta-Titel
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_meta_description" checked>
                                                    <span class="retexify-checkbox-icon">📝</span>
                                                    Yoast Meta-Beschreibung
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="yoast_focus_keyword" checked>
                                                    <span class="retexify-checkbox-icon">🔍</span>
                                                    Yoast Focus-Keyword
                                                </label>
                                                <label class="retexify-checkbox">
                                                    <input type="checkbox" name="export_content[]" value="alt_texts">
                                                    <span class="retexify-checkbox-icon">🖼️</span>
                                                    Alt-Texte
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="retexify-export-actions">
                                        <button type="button" id="retexify-preview-export" class="retexify-btn retexify-btn-secondary retexify-btn-large">
                                            👁️ Vorschau anzeigen
                                        </button>
                                        <button type="button" id="retexify-start-export" class="retexify-btn retexify-btn-primary retexify-btn-large">
                                            📤 CSV Export starten
                                        </button>
                                    </div>
                                </div>
                                <!-- Export Vorschau -->
                                <div id="retexify-export-preview" class="retexify-export-preview" style="display: none;">
                                    <!-- Dynamisch per JavaScript gefüllt -->
                                </div>
                            </div>
                        </div>
                        <!-- Import Sektion -->
                        <div class="retexify-card">
                            <div class="retexify-card-header">
                                <h2>📥 Content Import</h2>
                                <div class="retexify-header-badge">CSV-Import für SEO-Daten</div>
                            </div>
                            <div class="retexify-card-body">
                                <div class="retexify-import-controls">
                                    <!-- Upload Bereich -->
                                    <div class="retexify-upload-area" id="retexify-csv-upload-area">
                                        <div class="retexify-upload-icon">📁</div>
                                        <div class="retexify-upload-text">
                                            <h4>CSV-Datei hier ablegen oder klicken</h4>
                                            <p>Unterstützte Formate: .csv (max. <?php echo size_format(wp_max_upload_size()); ?>)</p>
                                        </div>
                                        <input type="file" id="retexify-csv-file-input" accept=".csv" style="display: none;">
                                    </div>
                                    <!-- Import Ergebnisse -->
                                    <div id="retexify-import-results" style="display: none;">
                                        <!-- Dynamisch per JavaScript gefüllt -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Hilfe-Text für Import -->
                        <div class="retexify-card" style="margin-top: 20px;">
                            <div class="retexify-card-header">
                                <h2>💡 Import-Anleitung</h2>
                                <div class="retexify-header-badge">Wie funktioniert der Import?</div>
                            </div>
                            <div class="retexify-card-body">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                        <h4 style="margin: 0 0 10px 0; color: #007bff;">📤 1. Exportieren</h4>
                                        <p style="margin: 0; font-size: 14px; color: #495057;">
                                            Exportieren Sie zuerst Ihre aktuellen SEO-Daten mit dem Export-Tool.
                                        </p>
                                    </div>
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                        <h4 style="margin: 0 0 10px 0; color: #28a745;">✏️ 2. Bearbeiten</h4>
                                        <p style="margin: 0; font-size: 14px; color: #495057;">
                                            Bearbeiten Sie die CSV-Datei und füllen Sie die "Neu"-Spalten mit Ihren gewünschten Inhalten.
                                        </p>
                                    </div>
                                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #e9ecef;">
                                        <h4 style="margin: 0 0 10px 0; color: #6f42c1;">📥 3. Importieren</h4>
                                        <p style="margin: 0; font-size: 14px; color: #495057;">
                                            Ziehen Sie die bearbeitete CSV-Datei in den Import-Bereich und starten Sie den Import.
                                        </p>
                                    </div>
                                </div>
                                <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-top: 20px;">
                                    <p style="margin: 0; font-size: 13px; color: #856404;">
                                        <strong>⚠️ Wichtig:</strong> Nur die "Neu"-Spalten werden beim Import übernommen. 
                                        Die "Original"-Spalten dienen zur Orientierung und werden nicht verändert.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- System Tab -->
                <div class="retexify-tab-content" id="retexify-tab-system">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🔧 System-Status & Research APIs</h2>
                            <div class="retexify-header-badge" id="retexify-test-system-badge">
                                🧪 System & APIs testen
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <div id="retexify-system-status">
                                <div class="retexify-loading">Lade System-Status...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    private function get_swiss_cantons() {
        return array(
            'AG' => 'Aargau', 'AI' => 'Appenzell Innerrhoden', 'AR' => 'Appenzell Ausserrhoden', 'BE' => 'Bern',
            'BL' => 'Basel-Landschaft', 'BS' => 'Basel-Stadt', 'FR' => 'Freiburg', 'GE' => 'Genf', 'GL' => 'Glarus',
            'GR' => 'Graubünden', 'JU' => 'Jura', 'LU' => 'Luzern', 'NE' => 'Neuenburg', 'NW' => 'Nidwalden',
            'OW' => 'Obwalden', 'SG' => 'St. Gallen', 'SH' => 'Schaffhausen', 'SO' => 'Solothurn', 'SZ' => 'Schwyz',
            'TG' => 'Thurgau', 'TI' => 'Tessin', 'UR' => 'Uri', 'VD' => 'Waadt', 'VS' => 'Wallis', 'ZG' => 'Zug', 'ZH' => 'Zürich'
        );
    }
    private function is_ai_enabled() {
        // Verwende die neue, sichere API-Schlüssel-Struktur
        $api_keys = get_option('retexify_api_keys', array());
        $ai_settings = get_option('retexify_ai_settings', array());
        $current_provider = $ai_settings['api_provider'] ?? 'openai';
        
        return !empty($api_keys[$current_provider]);
    }
    private function get_all_api_keys() {
        // Verwende die neue, sichere API-Schlüssel-Struktur
        return get_option('retexify_api_keys', array());
    }
} 