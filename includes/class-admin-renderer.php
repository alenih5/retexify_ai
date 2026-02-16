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
                    <button class="retexify-tab-btn active" data-tab="dashboard">📊 Dashboard</button>
                    <button class="retexify-tab-btn" data-tab="seo-optimizer">🚀 SEO-Optimizer</button>
                    <button class="retexify-tab-btn" data-tab="media-seo">🖼️ Medien SEO</button>
                    <button class="retexify-tab-btn" data-tab="ai-settings">⚙️ KI-Einstellungen</button>
                    <?php if ($export_import_available): ?>
                    <button class="retexify-tab-btn" data-tab="export-import">📤 Export/Import</button>
                    <?php endif; ?>
                    <button class="retexify-tab-btn" data-tab="system">🔧 System</button>
                </div>
                <!-- Dashboard Tab -->
                <div class="retexify-tab-content active" id="tab-dashboard">
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
                <div class="retexify-tab-content" id="tab-seo-optimizer">
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
                            <!-- 🆕 BULK-FUNKTIONEN & FILTER -->
                            <div class="retexify-bulk-controls" style="margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                                <h3 style="margin: 0 0 15px 0; display: flex; align-items: center; gap: 8px; color: white;">⚡ Bulk-Funktionen & Filter</h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 15px;">
                                    <button id="retexify-filter-empty-btn" class="button button-secondary" style="height: 45px;">
                                        <span class="dashicons dashicons-filter"></span> Nur ohne SEO
                                    </button>
                                    <button id="retexify-bulk-pages-btn" class="button button-primary" style="height: 45px; background: #10b981;">
                                        <span class="dashicons dashicons-admin-page"></span> Alle Seiten
                                    </button>
                                    <button id="retexify-bulk-posts-btn" class="button button-primary" style="height: 45px; background: #3b82f6;">
                                        <span class="dashicons dashicons-admin-post"></span> Alle Beiträge
                                    </button>
                                    <button id="retexify-bulk-all-btn" class="button button-primary" style="height: 45px; background: #8b5cf6;">
                                        <span class="dashicons dashicons-grid-view"></span> ALLES
                                    </button>
                                </div>
                                
                                <label style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 10px; border-radius: 6px;">
                                    <input type="checkbox" id="retexify-only-empty-checkbox" checked>
                                    Nur Posts OHNE vorhandene SEO-Daten
                                </label>
                                
                                <div id="retexify-bulk-progress" style="display: none; margin-top: 15px; background: white; padding: 15px; border-radius: 8px; color: #333;">
                                    <div><strong>Fortschritt:</strong> <span id="retexify-bulk-current">0</span> / <span id="retexify-bulk-total">0</span></div>
                                    <div style="background: #e5e7eb; height: 24px; border-radius: 12px; margin-top: 8px; overflow: hidden;">
                                        <div id="retexify-bulk-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #10b981, #3b82f6); transition: width 0.3s;"></div>
                                    </div>
                                    <div id="retexify-bulk-status" style="margin-top: 8px; font-size: 13px; color: #6b7280;"></div>
                                </div>
                            </div>
                            
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
                                                        <span id="title-chars">0</span>/65 Zeichen
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
                                                        <span id="description-chars">0</span>/165 Zeichen
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
                <!-- 🖼️ Medien-SEO Tab -->
                <div class="retexify-tab-content" id="tab-media-seo">
                    <div class="retexify-card">
                        <div class="retexify-card-header">
                            <h2>🖼️ Medien-SEO Optimizer</h2>
                            <div class="retexify-header-badge">
                                <?php if ($ai_enabled): ?>
                                    🤖 KI-Bild-Optimierung aktiv
                                <?php else: ?>
                                    ⚠️ KI für Auto-Generierung konfigurieren
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="retexify-card-body">
                            <!-- Medien-Statistiken -->
                            <div id="retexify-media-stats" class="retexify-media-stats-grid">
                                <div class="retexify-loading">Lade Medien-Statistiken...</div>
                            </div>
                            
                            <!-- Filter-Bereich -->
                            <div class="retexify-media-filters" style="margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                                <h3 style="margin: 0 0 15px 0; color: white;">🔍 Bilder filtern & optimieren</h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-bottom: 15px;">
                                    <select id="retexify-media-filter-type" class="retexify-select" style="height: 42px; border-radius: 8px;">
                                        <option value="all">Alle Bilder</option>
                                        <option value="without_alt">Ohne Alt-Text</option>
                                        <option value="with_alt">Mit Alt-Text</option>
                                        <option value="without_title">Ohne richtigen Titel</option>
                                    </select>
                                    
                                    <select id="retexify-media-mime-type" class="retexify-select" style="height: 42px; border-radius: 8px;">
                                        <option value="">Alle Formate</option>
                                        <option value="image/jpeg">JPEG</option>
                                        <option value="image/png">PNG</option>
                                        <option value="image/webp">WebP</option>
                                        <option value="image/gif">GIF</option>
                                        <option value="image/svg+xml">SVG</option>
                                    </select>
                                    
                                    <input type="text" id="retexify-media-search" class="retexify-input" placeholder="🔍 Bilder suchen..." style="height: 42px; border-radius: 8px;">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">
                                    <button type="button" id="retexify-load-media" class="button button-secondary" style="height: 42px; border-radius: 8px;">
                                        📄 Bilder laden
                                    </button>
                                    <button type="button" id="retexify-bulk-generate-alt" class="button button-primary" style="height: 42px; background: #10b981; border-radius: 8px;" <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                        🤖 Alle Alt-Texte generieren
                                    </button>
                                    <button type="button" id="retexify-bulk-generate-all-site" class="button button-primary" style="height: 42px; background: #7c3aed; border-color: #7c3aed; border-radius: 8px; color: white;" <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                        🌐 ALLE Website-Bilder optimieren
                                    </button>
                                    <button type="button" id="retexify-export-media-csv" class="button button-secondary" style="height: 42px; border-radius: 8px;">
                                        📤 CSV Export
                                    </button>
                                    <button type="button" id="retexify-diagnose-media-btn" class="button" style="height: 42px; border-radius: 8px; background: #f59e0b; border-color: #f59e0b; color: white; font-weight: 600;">
                                        🔍 Diagnose starten
                                    </button>
                                </div>
                                
                                <!-- Bulk-Progress -->
                                <div id="retexify-media-bulk-progress" style="display: none; margin-top: 15px; background: white; padding: 15px; border-radius: 8px; color: #333;">
                                    <div><strong>Fortschritt:</strong> <span id="retexify-media-bulk-current">0</span> / <span id="retexify-media-bulk-total">0</span></div>
                                    <div style="background: #e5e7eb; height: 24px; border-radius: 12px; margin-top: 8px; overflow: hidden;">
                                        <div id="retexify-media-bulk-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #10b981, #3b82f6); transition: width 0.3s;"></div>
                                    </div>
                                    <div id="retexify-media-bulk-status" style="margin-top: 8px; font-size: 13px; color: #6b7280;"></div>
                                </div>
                            </div>
                            
                            <!-- Medien-Liste -->
                            <div id="retexify-media-list" style="display: none;">
                                <!-- Navigation -->
                                <div class="retexify-seo-navigation" id="retexify-media-navigation">
                                    <button type="button" id="retexify-media-prev" class="retexify-btn retexify-btn-secondary" disabled>← Vorheriges Bild</button>
                                    <span id="retexify-media-counter" class="retexify-counter">1 / 0</span>
                                    <button type="button" id="retexify-media-next" class="retexify-btn retexify-btn-secondary">Nächstes Bild →</button>
                                </div>
                                
                                <!-- Bild-Anzeige und Editor -->
                                <div class="retexify-media-editor-grid">
                                    <!-- Bild-Vorschau -->
                                    <div class="retexify-media-preview-card">
                                        <div id="retexify-media-image-preview" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                            <img id="retexify-media-preview-img" src="" alt="" style="max-width: 100%; max-height: 300px; border-radius: 4px; display: none;">
                                            <span id="retexify-media-preview-placeholder">Bild wird geladen...</span>
                                        </div>
                                        <div class="retexify-media-info" style="margin-top: 12px; padding: 12px; background: #f1f5f9; border-radius: 8px; font-size: 13px;">
                                            <div><strong>Dateiname:</strong> <span id="retexify-media-filename">-</span></div>
                                            <div><strong>Format:</strong> <span id="retexify-media-format">-</span></div>
                                            <div><strong>Dimensionen:</strong> <span id="retexify-media-dimensions">-</span></div>
                                            <div><strong>Verwendet auf:</strong> <span id="retexify-media-used-on">-</span></div>
                                            <div><strong>Seiten-Keywords:</strong> <span id="retexify-media-page-keywords" style="color: #667eea; font-weight: 600;">-</span></div>
                                        </div>
                                    </div>
                                    
                                    <!-- SEO-Editor -->
                                    <div class="retexify-media-seo-editor">
                                        <h4 style="margin: 0 0 15px 0;">📝 Bild-SEO Daten</h4>
                                        
                                        <!-- Aktueller Alt-Text -->
                                        <div class="retexify-seo-item" style="margin-bottom: 15px;">
                                            <label style="font-weight: 600; margin-bottom: 4px; display: block;">Alt-Text (aktuell):</label>
                                            <div id="retexify-media-current-alt" class="retexify-current-value" style="padding: 8px; background: #f8f9fa; border-radius: 6px; min-height: 36px; border: 1px solid #e9ecef;">Nicht gesetzt</div>
                                        </div>
                                        
                                        <!-- Neuer Alt-Text -->
                                        <div class="retexify-seo-item" style="margin-bottom: 15px;">
                                            <label for="retexify-media-new-alt" style="font-weight: 600; margin-bottom: 4px; display: block;">Alt-Text (neu):</label>
                                            <textarea id="retexify-media-new-alt" class="retexify-textarea" rows="2" placeholder="Beschreibender Alt-Text für das Bild..." style="width: 100%;"></textarea>
                                            <div class="retexify-input-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                                                <span class="retexify-char-counter"><span id="retexify-media-alt-chars">0</span>/125 Zeichen</span>
                                                <button type="button" id="retexify-generate-single-alt" class="retexify-btn retexify-btn-primary" style="font-size: 12px;" <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                                    🤖 Alt-Text generieren
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Bildtitel -->
                                        <div class="retexify-seo-item" style="margin-bottom: 15px;">
                                            <label for="retexify-media-new-title" style="font-weight: 600; margin-bottom: 4px; display: block;">Bildtitel:</label>
                                            <input type="text" id="retexify-media-new-title" class="retexify-input" placeholder="SEO-optimierter Bildtitel..." style="width: 100%;">
                                        </div>
                                        
                                        <!-- Bildunterschrift -->
                                        <div class="retexify-seo-item" style="margin-bottom: 15px;">
                                            <label for="retexify-media-new-caption" style="font-weight: 600; margin-bottom: 4px; display: block;">Bildunterschrift:</label>
                                            <textarea id="retexify-media-new-caption" class="retexify-textarea" rows="2" placeholder="Informativer Zusatztext..." style="width: 100%;"></textarea>
                                        </div>
                                        
                                        <!-- Aktions-Buttons -->
                                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                                            <button type="button" id="retexify-generate-all-media-seo" class="retexify-btn retexify-btn-primary" style="flex: 1;" <?php if (!$ai_enabled) echo 'disabled'; ?>>
                                                ✨ Alles generieren (KI)
                                            </button>
                                            <button type="button" id="retexify-save-media-seo" class="retexify-btn retexify-btn-success" style="flex: 1;">
                                                💾 Speichern
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- KI-Einstellungen Tab -->
                <div class="retexify-tab-content" id="tab-ai-settings">
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
                <?php if ($export_import_available): ?>
                <!-- Export/Import Tab -->
                <div class="retexify-tab-content" id="tab-export-import">
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
                <div class="retexify-tab-content" id="tab-system">
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