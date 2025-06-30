/**
 * ReTexify AI Pro - FIXED Universal Admin JavaScript with Multi-KI Support
 * Version: 3.5.1 - Robust Event Delegation, API-Key Management & Debug Support
 * FIXES: Connection Test, API-Key Storage, Provider Comparison
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify AI Pro JavaScript startet...');
    console.log('📊 AJAX URL:', retexify_ajax.ajax_url);
    console.log('🔑 Nonce:', retexify_ajax.nonce);
    
    var seoData = [];
    var currentSeoIndex = 0;
    
    // FIXED: MULTI-KI PROVIDER MANAGEMENT mit separater API-Key Speicherung
    var currentProvider = '';
    var apiKeys = {}; // Separate API-Keys für jeden Provider
    var providerModels = {};
    
    // ==== SYSTEM-TAB LOADING FIXES ====
    var systemStatusLoaded = false; // Flag um doppeltes Laden zu verhindern
    
    // TAB SYSTEM mit robuster Event-Delegation
    initializeTabs();
    
    // DASHBOARD INITIAL LADEN
    loadDashboard();
    
    function initializeTabs() {
        console.log('🔧 Initialisiere Tab-System...');
        
        // Event-Delegation für Tab-Buttons (funktioniert auch nach DOM-Updates)
        $(document).on('click', '.retexify-tab-btn', function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            console.log('📋 Tab geklickt:', tabId);
            
            // Alle Tabs deaktivieren
            $('.retexify-tab-btn').removeClass('active');
            $('.retexify-tab-content').removeClass('active');
            
            // Aktiven Tab setzen
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // Spezielle Tab-Aktionen
            if (tabId === 'dashboard') {
                loadDashboard();
            } else if (tabId === 'ai-settings') {
                setTimeout(initializeMultiAI, 100);
            } else if (tabId === 'system') {
                // FIXED: Sofortiges Laden des System-Status
                loadSystemStatusOnce();
            }
        });
        
        console.log('✅ Tab-System initialisiert');
    }
    
    /**
     * NEUE FUNKTION: Sofortiges Laden des System-Status
     * Behebt das Problem des nicht geladenen ersten Status
     */
    function loadSystemStatusImmediate() {
        console.log('🔧 Lade System-Status sofort...');
        
        // Verhindere doppeltes Laden
        if (systemStatusLoaded) {
            console.log('ℹ️ System-Status bereits geladen');
            return;
        }
        
        var $statusContainer = $('#retexify-system-status');
        
        // Sofortiger Loading-Indikator mit korrekten CSS-Klassen
        $statusContainer.html(`
            <div class="retexify-loading-wrapper">
                <div class="retexify-spinner"></div>
                <div class="retexify-loading-text">🔧 Prüfe System-Status...</div>
            </div>
        `);
        
        // System-Test AJAX-Aufruf
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_test_system',
                nonce: retexify_ajax.nonce
            },
            timeout: 15000, // 15 Sekunden Timeout
            success: function(response) {
                console.log('🔧 System-Status Response:', response);
                
                if (response.success) {
                    // Erfolgreiche Antwort mit korrekten CSS-Klassen rendern
                    $statusContainer.html(formatSystemStatus(response.data));
                    systemStatusLoaded = true;
                    showNotification('✅ System-Status erfolgreich geladen', 'success');
                } else {
                    // Fehler-Anzeige mit korrekten CSS-Klassen
                    $statusContainer.html(`
                        <div class="retexify-status-error">
                            <div class="status-error-icon">❌</div>
                            <div class="status-error-text">
                                <strong>System-Test fehlgeschlagen</strong><br>
                                ${response.data || 'Unbekannter Fehler'}
                            </div>
                        </div>
                    `);
                    showNotification('❌ System-Test fehlgeschlagen', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ System-Status AJAX Fehler:', status, error);
                console.error('Response Text:', xhr.responseText);
                
                $statusContainer.html(`
                    <div class="retexify-status-error">
                        <div class="status-error-icon">🔌</div>
                        <div class="status-error-text">
                            <strong>Verbindungsfehler</strong><br>
                            Konnte System-Status nicht laden: ${error}
                        </div>
                    </div>
                `);
                showNotification('❌ Verbindungsfehler beim System-Test', 'error');
            }
        });
    }
    
    /**
     * NEUE FUNKTION: System-Status formatieren mit korrekten CSS-Klassen
     * Behebt CSS-Rendering-Probleme
     */
    function formatSystemStatus(statusData) {
        if (typeof statusData === 'string') {
            // Falls statusData bereits HTML ist
            return `<div class="retexify-system-status-content">${statusData}</div>`;
        }
        
        // Falls statusData ein Object ist, formatiere es
        let html = '<div class="retexify-system-status-content">';
        
        if (statusData.wordpress) {
            html += `
                <div class="status-section">
                    <h3 class="status-section-title">WordPress</h3>
                    <div class="status-item">
                        <span class="status-label">Version:</span>
                        <span class="status-value">${statusData.wordpress.version}</span>
                    </div>
                </div>
            `;
        }
        
        if (statusData.plugin) {
            html += `
                <div class="status-section">
                    <h3 class="status-section-title">Plugin</h3>
                    <div class="status-item">
                        <span class="status-label">Version:</span>
                        <span class="status-value">${statusData.plugin.version}</span>
                    </div>
                </div>
            `;
        }
        
        if (statusData.apis) {
            html += `
                <div class="status-section">
                    <h3 class="status-section-title">APIs</h3>
            `;
            
            Object.keys(statusData.apis).forEach(function(apiName) {
                const apiStatus = statusData.apis[apiName];
                const statusClass = apiStatus ? 'status-ok' : 'status-error';
                const statusIcon = apiStatus ? '✅' : '❌';
                const statusText = apiStatus ? 'Aktiv' : 'Offline';
                
                html += `
                    <div class="status-item">
                        <span class="status-label">${apiName}:</span>
                        <span class="status-indicator ${statusClass}">
                            ${statusIcon} ${statusText}
                        </span>
                    </div>
                `;
            });
            
            html += '</div>';
        }
        
        html += '</div>';
        
        return html;
    }
    
    function loadDashboard() {
        console.log('📊 Lade Dashboard...');
        
        $('#retexify-dashboard-content').html('<div class="retexify-loading">📊 Lade Dashboard...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_stats',
                nonce: retexify_ajax.nonce
            },
            timeout: 30000,
            success: function(response) {
                console.log('📊 Dashboard Response:', response);
                if (response.success) {
                    $('#retexify-dashboard-content').html(response.data);
                    showNotification('✅ Dashboard geladen', 'success');
                } else {
                    $('#retexify-dashboard-content').html('<div class="retexify-warning">❌ Fehler: ' + (response.data || 'Unbekannter Fehler') + '</div>');
                    showNotification('❌ Dashboard-Fehler: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Fehler beim Dashboard-Laden:', status, error);
                console.error('Response Text:', xhr.responseText);
                $('#retexify-dashboard-content').html('<div class="retexify-warning">❌ Verbindungsfehler: ' + error + '</div>');
                showNotification('❌ Dashboard-Verbindungsfehler', 'error');
            }
        });
    }
    
    // DASHBOARD REFRESH mit Event-Delegation
    $(document).on('click', '#retexify-refresh-stats-badge', function(e) {
        e.preventDefault();
        console.log('🔄 Dashboard Refresh ausgelöst');
        
        var $badge = $(this);
        var originalText = $badge.html();
        $badge.html('🔄 Aktualisiere...');
        
        loadDashboard();
        
        setTimeout(function() {
            $badge.html(originalText);
        }, 2000);
    });
    
    // ==== SEO OPTIMIZER FUNKTIONEN mit Event-Delegation ====
    
    // SEO CONTENT LADEN
    $(document).on('click', '#retexify-load-seo-content', function(e) {
        e.preventDefault();
        console.log('📄 SEO Content laden ausgelöst');
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('⏳ Lade SEO-Content...').prop('disabled', true);
        
        var postType = $('#seo-post-type').val() || 'post';
        console.log('📄 Post-Typ:', postType);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_load_seo_content',
                nonce: retexify_ajax.nonce,
                post_type: postType
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('📄 SEO Content Response:', response);
                
                if (response.success && response.data.items) {
                    seoData = response.data.items;
                    currentSeoIndex = 0;
                    
                    if (seoData.length > 0) {
                        $('#retexify-seo-content-list').show();
                        displayCurrentSeoPage();
                        showNotification('✅ ' + seoData.length + ' ' + postType + ' geladen!', 'success');
                    } else {
                        showNotification('❌ Keine SEO-Inhalte gefunden!', 'warning');
                    }
                } else {
                    showNotification('❌ Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim SEO Content laden:', status, error);
                showNotification('❌ Verbindungsfehler beim Laden', 'error');
            }
        });
    });
    
    function displayCurrentSeoPage() {
        if (seoData.length === 0) {
            console.log('⚠️ Keine SEO-Daten vorhanden');
            return;
        }
        
        var current = seoData[currentSeoIndex];
        console.log('📄 Zeige SEO-Seite:', current.title);
        
        // Seitentitel und Info
        $('#retexify-current-page-title').text(current.title);
        $('#retexify-page-info').html(
            'ID: ' + current.id + ' • ' +
            'Typ: ' + current.type + ' • ' +
            'Geändert: ' + current.modified
        );
        
        // Links korrekt setzen
        $('#retexify-page-url').attr('href', current.url);
        $('#retexify-edit-page').attr('href', current.edit_url);
        
        // Counter aktualisieren
        $('#retexify-seo-counter').text((currentSeoIndex + 1) + ' / ' + seoData.length);
        
        // Aktuelle SEO-Daten anzeigen
        $('#retexify-current-meta-title').text(current.meta_title || 'Nicht gesetzt');
        $('#retexify-current-meta-description').text(current.meta_description || 'Nicht gesetzt');
        $('#retexify-current-focus-keyword').text(current.focus_keyword || 'Nicht gesetzt');
        
        // Neue Felder leeren
        $('#retexify-new-meta-title').val('');
        $('#retexify-new-meta-description').val('');
        $('#retexify-new-focus-keyword').val('');
        
        updateCharCounters();
        
        // Navigation
        $('#retexify-seo-prev').prop('disabled', currentSeoIndex === 0);
        $('#retexify-seo-next').prop('disabled', currentSeoIndex === seoData.length - 1);
        
        // Content verstecken
        $('#retexify-full-content').hide();
        
        // Ergebnis-Bereich leeren
        $('#retexify-seo-results').html('');
    }
    
    // KORRIGIERT: EINZELNES SEO-ITEM GENERIEREN mit Event-Delegation
    $(document).on('click', '.retexify-generate-single', function(e) {
        e.preventDefault();
        
        if (seoData.length === 0) {
            showNotification('❌ Keine SEO-Daten geladen', 'warning');
            return;
        }
        
        var current = seoData[currentSeoIndex];
        var $btn = $(this);
        var originalText = $btn.html();
        var seoType = $btn.data('type');
        
        $btn.html('🤖 Generiere...').prop('disabled', true);
        
        var includeCantons = $('#retexify-include-cantons').is(':checked');
        var premiumTone = $('#retexify-premium-tone').is(':checked');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_generate_seo_item',
                nonce: retexify_ajax.nonce,
                post_id: current.id,
                seo_type: seoType,
                include_cantons: includeCantons,
                premium_tone: premiumTone
            },
            timeout: 60000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                if (response.success) {
                    var content = response.data.content;
                    var type = response.data.type;
                    
                    if (type === 'meta_title') {
                        $('#retexify-new-meta-title').val(content);
                    } else if (type === 'meta_description') {
                        $('#retexify-new-meta-description').val(content);
                    } else if (type === 'focus_keyword') {
                        $('#retexify-new-focus-keyword').val(content);
                    }
                    
                    updateCharCounters();
                    showNotification('✅ ' + seoType + ' generiert!', 'success');
                } else {
                    showNotification('❌ Fehler: ' + response.data, 'error');
                }
            },
            error: function() {
                $btn.html(originalText).prop('disabled', false);
                showNotification('❌ Verbindungsfehler bei der Generierung', 'error');
            }
        });
    });
    
    // NAVIGATION mit Event-Delegation
    $(document).on('click', '#retexify-seo-prev', function(e) {
        e.preventDefault();
        if (currentSeoIndex > 0) {
            currentSeoIndex--;
            displayCurrentSeoPage();
            showNotification('← Vorherige Seite', 'success');
        }
    });
    
    $(document).on('click', '#retexify-seo-next', function(e) {
        e.preventDefault();
        if (currentSeoIndex < seoData.length - 1) {
            currentSeoIndex++;
            displayCurrentSeoPage();
            showNotification('→ Nächste Seite', 'success');
        }
    });
    
    // CONTENT ANZEIGEN mit Event-Delegation
    $(document).on('click', '#retexify-show-content', function(e) {
        e.preventDefault();
        
        if (seoData.length === 0) {
            showNotification('❌ Keine SEO-Daten geladen', 'warning');
            return;
        }
        
        var current = seoData[currentSeoIndex];
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('⏳ Lade Content...').prop('disabled', true);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_page_content',
                nonce: retexify_ajax.nonce,
                post_id: current.id
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                if (response.success) {
                    $('#retexify-content-text').text(response.data.content);
                    $('#retexify-word-count').text(response.data.word_count + ' Wörter');
                    $('#retexify-char-count').text(response.data.char_count + ' Zeichen');
                    $('#retexify-full-content').slideDown(300);
                    showNotification('📄 Content geladen', 'success');
                } else {
                    showNotification('❌ Fehler beim Laden des Contents: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Content laden:', status, error);
                showNotification('❌ Verbindungsfehler beim Content laden', 'error');
            }
        });
    });
    
    // CHARACTER COUNTER mit Event-Delegation
    function updateCharCounters() {
        var titleLength = $('#retexify-new-meta-title').val().length;
        var descLength = $('#retexify-new-meta-description').val().length;
        
        $('#title-chars').text(titleLength);
        $('#description-chars').text(descLength);
        
        // Farben setzen basierend auf optimalen Längen
        $('#title-chars').css('color', 
            titleLength > 60 ? '#dc3545' : 
            titleLength > 54 ? '#ffc107' : 
            titleLength > 0 ? '#28a745' : '#6c757d'
        );
        
        $('#description-chars').css('color', 
            descLength > 160 ? '#dc3545' : 
            descLength > 150 ? '#ffc107' : 
            descLength > 0 ? '#28a745' : '#6c757d'
        );
    }
    
    $(document).on('input', '#retexify-new-meta-title, #retexify-new-meta-description', updateCharCounters);
    
    // FELDER LEEREN mit Event-Delegation
    $(document).on('click', '#retexify-clear-seo-fields', function(e) {
        e.preventDefault();
        $('#retexify-new-meta-title').val('');
        $('#retexify-new-meta-description').val('');
        $('#retexify-new-focus-keyword').val('');
        updateCharCounters();
        $('#retexify-seo-results').html('');
        showNotification('🗑️ Felder geleert', 'success');
    });
    
    // ==== FIXED: MULTI-KI PROVIDER MANAGEMENT ====
    
    function initializeMultiAI() {
        console.log('🤖 Multi-KI System wird initialisiert...');
        
        // Laden der verfügbaren API-Keys
        loadApiKeys();
        
        currentProvider = $('#ai-provider').val() || 'openai';
        console.log('🤖 Aktueller Provider:', currentProvider);
        
        // Provider Info setzen
        updateProviderInfo(currentProvider);
        updateApiKeyHelp(currentProvider);
        updateProviderComparison(currentProvider);
        
        console.log('✅ Multi-KI System initialisiert');
    }
    
    // FIXED: API-Keys für alle Provider laden
    function loadApiKeys() {
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_api_keys',
                nonce: retexify_ajax.nonce
            },
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    apiKeys = response.data;
                    console.log('🔑 API-Keys geladen:', apiKeys);
                    
                    // Aktuellen API-Key anzeigen
                    var currentProvider = $('#ai-provider').val();
                    $('#ai-api-key').val(apiKeys[currentProvider] || '');
                    
                    updateConnectionStatus(currentProvider);
                } else {
                    console.error('❌ Fehler beim Laden der API-Keys:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Fehler beim Laden der API-Keys:', status, error);
            }
        });
    }
    
    // FIXED: API-Key speichern
    function saveApiKey(provider, apiKey) {
        return $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_save_api_key',
                nonce: retexify_ajax.nonce,
                provider: provider,
                api_key: apiKey
            },
            timeout: 15000
        });
    }
    
    // FIXED: Connection Status anzeigen
    function updateConnectionStatus(provider) {
        var hasApiKey = apiKeys[provider] && apiKeys[provider].length > 0;
        var $status = $('.retexify-connection-status');
        
        if ($status.length === 0) {
            // Status-Indikator erstellen falls nicht vorhanden
            $status = $('<span class="retexify-connection-status"></span>');
            $('.retexify-ai-provider-info').append($status);
        }
        
        if (hasApiKey) {
            $status.removeClass('disconnected testing').addClass('connected').text('🟢 Verbunden');
        } else {
            $status.removeClass('connected testing').addClass('disconnected').text('🔴 Nicht konfiguriert');
        }
    }
    
    // Provider Wechsel mit Event-Delegation
    $(document).on('change', '#ai-provider', function() {
        var newProvider = $(this).val();
        var oldProvider = currentProvider;
        
        console.log('🔄 Provider Wechsel:', oldProvider, '->', newProvider);
        
        if (newProvider !== oldProvider) {
            // FIXED: API-Key des alten Providers speichern vor Wechsel
            var currentApiKey = $('#ai-api-key').val();
            if (currentApiKey && oldProvider) {
                apiKeys[oldProvider] = currentApiKey;
                saveApiKey(oldProvider, currentApiKey).done(function() {
                    console.log('💾 API-Key für', oldProvider, 'gespeichert');
                });
            }
            
            switchProvider(newProvider, oldProvider);
        }
    });
    
    function switchProvider(newProvider, oldProvider) {
        currentProvider = newProvider;
        
        // Animation starten
        $('.retexify-settings-group').addClass('retexify-provider-transition');
        
        // Provider Info aktualisieren
        updateProviderInfo(newProvider);
        
        // Modelle laden
        loadModelsForProvider(newProvider);
        
        // API Key Hilfe aktualisieren
        updateApiKeyHelp(newProvider);
        
        // FIXED: Provider-Vergleich nur für aktuellen Provider
        updateProviderComparison(newProvider);
        
        // FIXED: API-Key für neuen Provider laden
        $('#ai-api-key').val(apiKeys[newProvider] || '');
        
        // Connection Status aktualisieren
        updateConnectionStatus(newProvider);
        
        // Animation beenden
        setTimeout(function() {
            $('.retexify-settings-group').removeClass('retexify-provider-transition').addClass('loaded');
            showProviderSwitchNotification(newProvider, oldProvider);
        }, 300);
    }
    
    function updateProviderInfo(provider) {
        var providerNames = {
            'openai': 'OpenAI (GPT-4, GPT-4o, etc.)',
            'anthropic': 'Anthropic Claude (3.5 Sonnet, Haiku, Opus)',
            'gemini': 'Google Gemini (Pro, Flash, etc.)'
        };
        
        $('.retexify-ai-provider-info span').first().text('🤖 Aktiv: ' + (providerNames[provider] || 'Unbekannt'));
    }
    
    function loadModelsForProvider(provider) {
        var $modelSelect = $('#ai-model');
        $modelSelect.html('<option value="">⏳ Lade Modelle...</option>').prop('disabled', true);
        
        // Modelle für Provider laden
        setTimeout(function() {
            var models = getDefaultModels(provider);
            
            $modelSelect.empty().prop('disabled', false);
            
            $.each(models, function(modelKey, modelName) {
                $modelSelect.append('<option value="' + modelKey + '">' + modelName + '</option>');
            });
            
            // Erstes Modell als Standard auswählen
            if ($modelSelect.find('option').length > 0) {
                $modelSelect.find('option:first').prop('selected', true);
                updateCostEstimation(provider, $modelSelect.val());
            }
            
            showNotification('🔄 Modelle für ' + provider + ' geladen', 'success');
        }, 500);
    }
    
    function getDefaultModels(provider) {
        var models = {
            'openai': {
                'gpt-4o-mini': 'GPT-4o Mini (Empfohlen - Günstig & Schnell)',
                'gpt-4o': 'GPT-4o (Premium - Beste Qualität)',
                'o1-mini': 'o1 Mini (Reasoning - Sehr smart)',
                'o1-preview': 'o1 Preview (Reasoning - Ultra smart)',
                'gpt-4-turbo': 'GPT-4 Turbo (Ausgewogen)',
                'gpt-4': 'GPT-4 (Klassisch)',
                'gpt-3.5-turbo': 'GPT-3.5 Turbo (Günstig)'
            },
            'anthropic': {
                'claude-3-5-sonnet-20241022': 'Claude 3.5 Sonnet (Empfohlen - Beste Balance)',
                'claude-3-5-haiku-20241022': 'Claude 3.5 Haiku (Neu - Schnell & Günstig)',
                'claude-3-opus-20240229': 'Claude 3 Opus (Premium - Beste Qualität)',
                'claude-3-sonnet-20240229': 'Claude 3 Sonnet (Ausgewogen)',
                'claude-3-haiku-20240307': 'Claude 3 Haiku (Schnell & Günstig)'
            },
            'gemini': {
                'gemini-1.5-pro-latest': 'Gemini 1.5 Pro (Empfohlen - Beste Qualität)',
                'gemini-1.5-flash-latest': 'Gemini 1.5 Flash (Schnell & Günstig)',
                'gemini-1.5-flash-8b-latest': 'Gemini 1.5 Flash 8B (Ultra-schnell)',
                'gemini-1.0-pro-latest': 'Gemini 1.0 Pro (Klassisch)',
                'gemini-exp-1206': 'Gemini Experimental (Neueste Features)'
            }
        };
        
        return models[provider] || {};
    }
    
    function updateApiKeyHelp(provider) {
        var helpTexts = {
            'openai': 'OpenAI: Erhältlich auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a><br>Format: sk-...',
            'anthropic': 'Anthropic: Erhältlich auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a><br>Format: sk-ant-...',
            'gemini': 'Google: Erhältlich auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a><br>Format: AIza...'
        };
        
        var placeholders = {
            'openai': 'sk-proj-...',
            'anthropic': 'sk-ant-api03-...',
            'gemini': 'AIzaSy...'
        };
        
        $('#api-key-help').html(helpTexts[provider] || 'API-Schlüssel eingeben');
        $('#ai-api-key').attr('placeholder', placeholders[provider] || 'API-Schlüssel...');
    }
    
    // FIXED: Provider-Vergleich nur für aktuellen Provider
    function updateProviderComparison(provider) {
        var providerInfo = {
            'openai': {
                title: '📊 OpenAI GPT:',
                features: [
                    '✅ Sehr günstig (GPT-4o Mini)',
                    '✅ Bewährt für SEO',
                    '✅ Schnell & zuverlässig',
                    '✅ Große Modellauswahl',
                    '✅ Reasoning-Modelle (o1)'
                ]
            },
            'anthropic': {
                title: '📊 Anthropic Claude:',
                features: [
                    '✅ Ausgezeichnete Textqualität',
                    '✅ Sehr präzise Anweisungen',
                    '✅ Ethisch ausgerichtet',
                    '✅ Lange Kontexte möglich',
                    '✅ Neueste Modelle (3.5 Haiku)'
                ]
            },
            'gemini': {
                title: '📊 Google Gemini:',
                features: [
                    '✅ Innovative Technologie',
                    '✅ Multimodal capabilities',
                    '✅ Sehr kostengünstig',
                    '✅ Schnelle Performance',
                    '✅ Experimentelle Features'
                ]
            }
        };
        
        var info = providerInfo[provider];
        if (info) {
            $('#current-provider-title').text(info.title);
            
            var featuresHtml = '<ul>';
            info.features.forEach(function(feature) {
                featuresHtml += '<li>' + feature + '</li>';
            });
            featuresHtml += '</ul>';
            
            $('#current-provider-info').html(featuresHtml);
        }
    }
    
    function updateCostEstimation(provider, model) {
        // Entferne vorherige Kostenschätzung
        $('.retexify-cost-estimation').remove();
        
        if (!model) return;
        
        var costs = getCostEstimation(provider, model);
        
        if (costs) {
            var costHtml = `
                <div class="retexify-cost-estimation">
                    <h5>💰 Kostenschätzung pro Request:</h5>
                    <div class="retexify-cost-grid">
                        <div class="retexify-cost-item">
                            <span class="retexify-cost-value">$${costs.perRequest}</span>
                            <span class="retexify-cost-label">Pro SEO-Suite</span>
                        </div>
                        <div class="retexify-cost-item">
                            <span class="retexify-cost-value">${costs.speed}</span>
                            <span class="retexify-cost-label">Geschwindigkeit</span>
                        </div>
                        <div class="retexify-cost-item">
                            <span class="retexify-cost-value">${costs.quality}</span>
                            <span class="retexify-cost-label">Qualität</span>
                        </div>
                        <div class="retexify-cost-item">
                            <span class="retexify-cost-value">$${costs.per1000}</span>
                            <span class="retexify-cost-label">Per 1000 Seiten</span>
                        </div>
                    </div>
                </div>
            `;
            
            $('#ai-model').closest('.retexify-field').after(costHtml);
        }
    }
    
    function getCostEstimation(provider, model) {
        var estimates = {
            'openai': {
                'gpt-4o-mini': { perRequest: '0.001', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐', per1000: '1.00' },
                'gpt-4o': { perRequest: '0.015', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐', per1000: '15.00' },
                'o1-mini': { perRequest: '0.018', speed: '🔄 Mittel', quality: '⭐⭐⭐⭐⭐', per1000: '18.00' },
                'o1-preview': { perRequest: '0.09', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐', per1000: '90.00' },
                'gpt-4-turbo': { perRequest: '0.04', speed: '⚡ Mittel', quality: '⭐⭐⭐⭐⭐', per1000: '40.00' },
                'gpt-4': { perRequest: '0.12', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐', per1000: '120.00' },
                'gpt-3.5-turbo': { perRequest: '0.002', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐', per1000: '2.00' }
            },
            'anthropic': {
                'claude-3-5-sonnet-20241022': { perRequest: '0.009', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐', per1000: '9.00' },
                'claude-3-5-haiku-20241022': { perRequest: '0.003', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐', per1000: '3.00' },
                'claude-3-opus-20240229': { perRequest: '0.045', speed: '⏳ Langsam', quality: '⭐⭐⭐⭐⭐', per1000: '45.00' },
                'claude-3-sonnet-20240229': { perRequest: '0.009', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐', per1000: '9.00' },
                'claude-3-haiku-20240307': { perRequest: '0.0008', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐', per1000: '0.80' }
            },
            'gemini': {
                'gemini-1.5-pro-latest': { perRequest: '0.003', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐⭐', per1000: '3.00' },
                'gemini-1.5-flash-latest': { perRequest: '0.0002', speed: '⚡ Sehr schnell', quality: '⭐⭐⭐⭐', per1000: '0.20' },
                'gemini-1.5-flash-8b-latest': { perRequest: '0.0001', speed: '⚡ Ultra-schnell', quality: '⭐⭐⭐', per1000: '0.10' },
                'gemini-1.0-pro-latest': { perRequest: '0.001', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐', per1000: '1.00' },
                'gemini-exp-1206': { perRequest: '0.001', speed: '⚡ Schnell', quality: '⭐⭐⭐⭐', per1000: '1.00' }
            }
        };
        
        return estimates[provider] && estimates[provider][model] ? estimates[provider][model] : null;
    }
    
    // Model Wechsel Handler mit Event-Delegation
    $(document).on('change', '#ai-model', function() {
        var provider = $('#ai-provider').val();
        var model = $(this).val();
        
        if (provider && model) {
            updateCostEstimation(provider, model);
            showNotification('📊 Kostenschätzung aktualisiert', 'success');
        }
    });
    
    // FIXED: API-Key Speichern beim Tippen
    $(document).on('input', '#ai-api-key', function() {
        var provider = $('#ai-provider').val();
        var apiKey = $(this).val();
        
        // Lokale Kopie aktualisieren
        apiKeys[provider] = apiKey;
        
        // Visual Feedback
        validateApiKeyFormat(provider, apiKey);
        
        // Auto-Save nach 2 Sekunden Pause
        clearTimeout(window.apiKeySaveTimeout);
        window.apiKeySaveTimeout = setTimeout(function() {
            if (apiKey.length > 10) { // Nur speichern wenn sinnvolle Länge
                saveApiKey(provider, apiKey).done(function() {
                    console.log('💾 Auto-Save API-Key für', provider);
                    updateConnectionStatus(provider);
                });
            }
        }, 2000);
    });
    
    // FIXED: API-Key Format Validierung
    function validateApiKeyFormat(provider, apiKey) {
        var $input = $('#ai-api-key');
        var $error = $('.retexify-api-key-error');
        
        if (!apiKey) {
            $input.removeClass('retexify-api-key-valid retexify-api-key-invalid');
            $error.remove();
            return;
        }
        
        var patterns = {
            'openai': /^sk-/,
            'anthropic': /^sk-ant-/,
            'gemini': /^AIza/
        };
        
        var isValid = patterns[provider] ? patterns[provider].test(apiKey) : apiKey.length > 10;
        
        if (isValid) {
            $input.removeClass('retexify-api-key-invalid').addClass('retexify-api-key-valid');
            $error.remove();
        } else {
            $input.removeClass('retexify-api-key-valid').addClass('retexify-api-key-invalid');
            
            if ($error.length === 0) {
                var errorMessages = {
                    'openai': 'OpenAI API-Schlüssel müssen mit "sk-" beginnen',
                    'anthropic': 'Anthropic API-Schlüssel müssen mit "sk-ant-" beginnen',
                    'gemini': 'Google API-Schlüssel müssen mit "AIza" beginnen'
                };
                
                $input.after('<div class="retexify-api-key-error">' + (errorMessages[provider] || 'Ungültiges API-Key Format') + '</div>');
            }
        }
    }
    
    function showProviderSwitchNotification(newProvider, oldProvider) {
        var providerNames = {
            'openai': 'OpenAI',
            'anthropic': 'Anthropic Claude',
            'gemini': 'Google Gemini'
        };
        
        var message = `🔄 Gewechselt zu ${providerNames[newProvider] || newProvider}`;
        if (oldProvider) {
            message += ` (von ${providerNames[oldProvider] || oldProvider})`;
        }
        
        showNotification(message, 'success');
    }
    
    // ==== FIXED: KI-EINSTELLUNGEN mit Event-Delegation ====
    
    // FIXED: CONNECTION TEST mit besserer Validierung
    $(document).on('click', '#retexify-ai-test-connection', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.html();
        var provider = $('#ai-provider').val();
        var apiKey = $('#ai-api-key').val();
        
        // FIXED: Lokale Validierung VOR dem Test
        if (!apiKey || apiKey.length < 10) {
            showNotification('❌ Bitte geben Sie zuerst einen gültigen API-Schlüssel ein', 'error');
            return;
        }
        
        // FIXED: Provider-spezifische Format-Validierung
        var patterns = {
            'openai': /^sk-/,
            'anthropic': /^sk-ant-/,
            'gemini': /^AIza/
        };
        
        if (patterns[provider] && !patterns[provider].test(apiKey)) {
            var errorMessages = {
                'openai': '❌ OpenAI API-Schlüssel müssen mit "sk-" beginnen',
                'anthropic': '❌ Anthropic API-Schlüssel müssen mit "sk-ant-" beginnen',
                'gemini': '❌ Google API-Schlüssel müssen mit "AIza" beginnen'
            };
            showNotification(errorMessages[provider] || '❌ Ungültiges API-Key Format', 'error');
            return;
        }
        
        $btn.html('<span class="retexify-ai-loading">🔗 Teste Verbindung...</span>').prop('disabled', true);
        
        // Status auf "Testing" setzen
        $('.retexify-connection-status').removeClass('connected disconnected').addClass('testing').text('🟡 Teste...');
        
        var testMessages = {
            'openai': '🔗 Teste OpenAI Verbindung...',
            'anthropic': '🔗 Teste Claude Verbindung...',
            'gemini': '🔗 Teste Gemini Verbindung...'
        };
        
        showNotification(testMessages[provider] || '🔗 Teste KI-Verbindung...', 'success');
        
        // FIXED: API-Key vor Test speichern
        apiKeys[provider] = apiKey;
        saveApiKey(provider, apiKey);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_ai_test_connection',
                nonce: retexify_ajax.nonce
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    $('#retexify-ai-settings-result').html(
                        '<div class="retexify-test-success">' + response.data + '</div>'
                    );
                    
                    var successMessages = {
                        'openai': '✅ OpenAI Verbindung erfolgreich!',
                        'anthropic': '✅ Claude Verbindung erfolgreich!',
                        'gemini': '✅ Gemini Verbindung erfolgreich!'
                    };
                    
                    showNotification(successMessages[provider] || '✅ KI-Verbindung erfolgreich!', 'success');
                    updateConnectionStatus(provider);
                } else {
                    $('#retexify-ai-settings-result').html(
                        '<div class="retexify-test-warning">' + (response.data || 'Unbekannter Fehler') + '</div>'
                    );
                    showNotification('❌ Verbindung fehlgeschlagen: ' + (response.data || 'Unbekannt'), 'error');
                    $('.retexify-connection-status').removeClass('connected testing').addClass('disconnected').text('🔴 Fehler');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Connection Test:', status, error);
                showNotification('❌ Verbindungsfehler beim Test', 'error');
                $('.retexify-connection-status').removeClass('connected testing').addClass('disconnected').text('🔴 Fehler');
            }
        });
    });
    
    // FORM SUBMISSION mit Event-Delegation
    $(document).on('submit', '#retexify-ai-settings-form', function(e) {
        e.preventDefault();
        
        var provider = $('#ai-provider').val();
        var apiKey = $('#ai-api-key').val();
        var model = $('#ai-model').val();
        
        // FIXED: Lokale Validierung vor dem Speichern
        if (!validateProviderSettings(provider, apiKey, model)) {
            return false;
        }
        
        var formData = $(this).serialize();
        formData += '&action=retexify_ai_save_settings&nonce=' + retexify_ajax.nonce;
        
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        $submitBtn.html('💾 Speichere...').prop('disabled', true);
        
        // API-Key vor Speichern aktualisieren
        apiKeys[provider] = apiKey;
        saveApiKey(provider, apiKey);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            timeout: 30000,
            success: function(response) {
                $submitBtn.html(originalText).prop('disabled', false);
                if (response.success) {
                    $('#retexify-ai-settings-result').html(
                        '<div class="retexify-test-success">✅ ' + response.data + '</div>'
                    );
                    
                    showNotification('⚙️ ' + provider + ' Einstellungen gespeichert!', 'success');
                    updateConnectionStatus(provider);
                    
                    // Dashboard refresh nach erfolgreicher Speicherung
                    setTimeout(loadDashboard, 1000);
                } else {
                    $('#retexify-ai-settings-result').html(
                        '<div class="retexify-test-warning">❌ ' + (response.data || 'Unbekannter Fehler') + '</div>'
                    );
                    showNotification('❌ Speichern fehlgeschlagen: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $submitBtn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Speichern:', status, error);
                showNotification('❌ Verbindungsfehler beim Speichern', 'error');
            }
        });
    });
    
    function validateProviderSettings(provider, apiKey, model) {
        if (!provider) {
            showNotification('❌ Bitte wählen Sie einen KI-Provider', 'error');
            return false;
        }
        
        if (!apiKey) {
            showNotification('❌ Bitte geben Sie einen API-Schlüssel ein', 'error');
            $('#ai-api-key').focus();
            return false;
        }
        
        // Provider-spezifische API Key Validierung
        var apiKeyPatterns = {
            'openai': /^sk-/,
            'anthropic': /^sk-ant-/,
            'gemini': /^AIza/
        };
        
        if (apiKeyPatterns[provider] && !apiKeyPatterns[provider].test(apiKey)) {
            var errorMessages = {
                'openai': '❌ OpenAI API-Schlüssel müssen mit "sk-" beginnen',
                'anthropic': '❌ Anthropic API-Schlüssel müssen mit "sk-ant-" beginnen',
                'gemini': '❌ Google API-Schlüssel müssen mit "AIza" beginnen'
            };
            
            showNotification(errorMessages[provider], 'error');
            $('#ai-api-key').focus();
            return false;
        }
        
        if (!model) {
            showNotification('❌ Bitte wählen Sie ein Modell', 'error');
            $('#ai-model').focus();
            return false;
        }
        
        return true;
    }
    
    // SCHWEIZER KANTONE AUSWAHL mit Event-Delegation
    $(document).on('click', '#retexify-select-all-cantons', function(e) {
        e.preventDefault();
        $('input[name="target_cantons[]"]').prop('checked', true);
        showNotification('🇨🇭 Alle Kantone ausgewählt', 'success');
    });
    
    $(document).on('click', '#retexify-select-main-cantons', function(e) {
        e.preventDefault();
        $('input[name="target_cantons[]"]').prop('checked', false);
        // Hauptkantone: BE, ZH, LU, SG, BS, GE
        var mainCantons = ['BE', 'ZH', 'LU', 'SG', 'BS', 'GE'];
        mainCantons.forEach(function(canton) {
            $('input[name="target_cantons[]"][value="' + canton + '"]').prop('checked', true);
        });
        showNotification('🏙️ Hauptkantone ausgewählt', 'success');
    });
    
    $(document).on('click', '#retexify-clear-cantons', function(e) {
        e.preventDefault();
        $('input[name="target_cantons[]"]').prop('checked', false);
        showNotification('🗑️ Alle Kantone abgewählt', 'success');
    });
    
    // ==== SYSTEM-TEST mit Event-Delegation ====
    
    $(document).on('click', '#retexify-test-system-badge', function(e) {
        e.preventDefault();
        console.log('🧪 Manueller System-Test ausgelöst');
        
        var $badge = $(this);
        var originalText = $badge.html();
        
        // Button-Zustand ändern
        $badge.html('🔄 Teste...').addClass('testing').prop('disabled', true);
        
        // Flag zurücksetzen für erneuten Test
        systemStatusLoaded = false;
        
        // System-Status erneut laden
        loadSystemStatusOnce();
        
        // Button nach 5 Sekunden wieder aktivieren
        setTimeout(function() {
            $badge.html(originalText).removeClass('testing').prop('disabled', false);
        }, 5000);
    });
    
    // ==== HILFSFUNKTIONEN ====
    
    function showNotification(message, type) {
        var bgColor = '#28a745';
        var textColor = 'white';
        var icon = '✅';
        
        if (type === 'warning') {
            bgColor = '#ffc107';
            textColor = '#1d2327';
            icon = '⚠️';
        } else if (type === 'error') {
            bgColor = '#dc3545';
            textColor = 'white';
            icon = '❌';
        }
        
        var $notification = $('<div>')
            .addClass('retexify-notification')
            .addClass(type)
            .html(icon + ' ' + message)
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'background': bgColor,
                'color': textColor,
                'padding': '12px 20px',
                'border-radius': '6px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
                'z-index': '9999',
                'max-width': '400px',
                'font-size': '14px',
                'font-weight': '600',
                'border': '1px solid rgba(255,255,255,0.2)'
            });
        
        $('body').append($notification);
        
        // Slide-in Animation
        $notification.css('transform', 'translateX(100%)').animate({
            transform: 'translateX(0)'
        }, 300);
        
        // Auto-remove nach 4 Sekunden
        setTimeout(function() {
            $notification.animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 300, function() {
                $(this).remove();
            });
        }, 4000);
        
        // Click to dismiss
        $notification.click(function() {
            $(this).animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 200, function() {
                $(this).remove();
            });
        });
    }
    
    // BENACHRICHTIGUNG WENN SEITE VERLASSEN WÄHREND EINES PROZESSES
    window.addEventListener('beforeunload', function(e) {
        if ($('.retexify-btn:disabled').length > 0) {
            var message = 'Eine KI-Operation läuft noch. Möchten Sie die Seite wirklich verlassen?';
            e.returnValue = message;
            return message;
        }
    });
    
    // DEBUG-INFORMATIONEN
    if (typeof retexify_ajax !== 'undefined' && retexify_ajax.debug) {
        console.log('🔧 ReTexify AI Pro Debug-Modus aktiviert');
        console.log('📊 AJAX URL:', retexify_ajax.ajax_url);
        console.log('🔑 Nonce:', retexify_ajax.nonce);
        console.log('🤖 KI aktiviert:', retexify_ajax.ai_enabled);
    }
    
    console.log('✅ ReTexify AI Pro JavaScript vollständig geladen!');
    console.log('🚀 Multi-KI System mit OpenAI, Anthropic Claude & Google Gemini bereit');
    
    // Willkommens-Nachricht (nur beim ersten Laden)
    if (!sessionStorage.getItem('retexify_welcome_shown')) {
        setTimeout(function() {
            showNotification('🇨🇭 ReTexify AI Pro bereit für universelle SEO-Optimierung!', 'success');
            sessionStorage.setItem('retexify_welcome_shown', 'true');
        }, 1000);
    }

    // === ReTexify Admin Backend Initialisierung ===

    jQuery(document).ready(function($) {
        // Tabs umschalten
        $(document).on('click', '.retexify-tab-btn', function() {
            var tab = $(this).data('tab');
            $('.retexify-tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.retexify-tab-content').removeClass('active');
            $('#tab-' + tab).addClass('active');
        });

        // === SEO-Buttons ===
        $(document).off('click', '#retexify-generate-all-seo').on('click', '#retexify-generate-all-seo', function(e) {
            e.preventDefault();
            if (typeof seoData === 'undefined' || seoData.length === 0) {
                showNotification('❌ Keine SEO-Daten geladen. Bitte laden Sie zuerst SEO-Content.', 'warning');
                return;
            }
            var current = seoData[currentSeoIndex];
            if (!current || !current.id) {
                showNotification('❌ Aktuelle Seite ungültig', 'error');
                return;
            }
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('⏳ Generiere alle SEO-Texte...').prop('disabled', true);
            var includeCantons = $('#retexify-include-cantons').is(':checked');
            var premiumTone = $('#retexify-premium-tone').is(':checked');
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_generate_complete_seo',
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    include_cantons: includeCantons,
                    premium_tone: premiumTone
                },
                timeout: 120000,
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        var data = response.data;
                        var metaTitle = data.meta_title || data.suite?.meta_title || '';
                        var metaDescription = data.meta_description || data.suite?.meta_description || '';
                        var focusKeyword = data.focus_keyword || data.suite?.focus_keyword || '';
                        if (metaTitle) $('#retexify-new-meta-title').val(metaTitle);
                        if (metaDescription) $('#retexify-new-meta-description').val(metaDescription);
                        if (focusKeyword) $('#retexify-new-focus-keyword').val(focusKeyword);
                        if (typeof updateCharCounters === 'function') updateCharCounters();
                        showNotification('✅ Alle SEO-Texte erfolgreich generiert!', 'success');
                    } else {
                        showNotification('❌ Fehler beim Generieren: ' + (response.data || 'Unbekannter Fehler'), 'error');
                    }
                },
                error: function() {
                    $btn.html(originalText).prop('disabled', false);
                    showNotification('❌ Verbindungsfehler bei der Generierung', 'error');
                }
            });
        });

        $(document).off('click', '#retexify-save-seo-data').on('click', '#retexify-save-seo-data', function(e) {
            e.preventDefault();
            if (typeof seoData === 'undefined' || seoData.length === 0) {
                showNotification('❌ Keine SEO-Daten geladen. Bitte laden Sie zuerst SEO-Content.', 'warning');
                return;
            }
            var current = seoData[currentSeoIndex];
            if (!current || !current.id) {
                showNotification('❌ Aktuelle Seite ungültig', 'error');
                return;
            }
            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('💾 Speichere...').prop('disabled', true);
            var newMetaTitle = $('#retexify-new-meta-title').val().trim();
            var newMetaDescription = $('#retexify-new-meta-description').val().trim();
            var newFocusKeyword = $('#retexify-new-focus-keyword').val().trim();
            if (!newMetaTitle && !newMetaDescription && !newFocusKeyword) {
                $btn.html(originalText).prop('disabled', false);
                showNotification('❌ Bitte füllen Sie mindestens ein Feld aus', 'warning');
                return;
            }
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_save_seo_data',
                    nonce: retexify_ajax.nonce,
                    post_id: current.id,
                    meta_title: newMetaTitle,
                    meta_description: newMetaDescription,
                    focus_keyword: newFocusKeyword
                },
                timeout: 30000,
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                    if (response.success) {
                        var savedCount = response.data.saved_count || 0;
                        showNotification('✅ ' + savedCount + ' SEO-Element(e) erfolgreich gespeichert!', 'success');
                        if (newMetaTitle) { $('#retexify-current-meta-title').text(newMetaTitle); current.meta_title = newMetaTitle; }
                        if (newMetaDescription) { $('#retexify-current-meta-description').text(newMetaDescription); current.meta_description = newMetaDescription; }
                        if (newFocusKeyword) { $('#retexify-current-focus-keyword').text(newFocusKeyword); current.focus_keyword = newFocusKeyword; }
                    } else {
                        showNotification('❌ Speicher-Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error');
                    }
                },
                error: function() {
                    $btn.html(originalText).prop('disabled', false);
                    showNotification('❌ Verbindungsfehler beim Speichern', 'error');
                }
            });
        });

        // Felder leeren
        $(document).on('click', '#retexify-clear-seo-fields', function(e) {
            e.preventDefault();
            if (confirm('Möchten Sie wirklich alle neuen SEO-Texte löschen?')) {
                $('#retexify-new-meta-title').val('');
                $('#retexify-new-meta-description').val('');
                $('#retexify-new-focus-keyword').val('');
                if (typeof updateCharCounters === 'function') updateCharCounters();
                showNotification('🗑️ Alle Felder geleert', 'info');
            }
        });
    });
    
    // ========== NEUE PERFORMANCE-OPTIMIERTE FUNKTIONEN (Version 2.1.0) ==========
    
    // OPTIMIERTER ALLE TEXTE GENERIEREN - Handler
$(document).on('click', '#retexify-generate-all-seo', function(e) {
    e.preventDefault();
        console.log('🚀 Optimierte Generierung gestartet');
        
    if (seoData.length === 0) {
        showNotification('❌ Keine SEO-Daten geladen', 'warning');
        return;
    }
    
    var current = seoData[currentSeoIndex];
    var $btn = $(this);
    var originalText = $btn.html();
        
        // Fortschrittsanzeige starten
        startProgressIndicator($btn);
    
    var includeCantons = $('#retexify-include-cantons').is(':checked');
    var premiumTone = $('#retexify-premium-tone').is(':checked');
        
        var startTime = Date.now();
    
    $.ajax({
        url: retexify_ajax.ajax_url,
        type: 'POST',
        data: {
                action: 'retexify_generate_complete_seo_optimized', // Neue optimierte Action
            nonce: retexify_ajax.nonce,
            post_id: current.id,
            include_cantons: includeCantons,
            premium_tone: premiumTone
        },
            timeout: 45000, // Reduziert von 120s auf 45s
        success: function(response) {
                var endTime = Date.now();
                var totalTime = ((endTime - startTime) / 1000).toFixed(1);
                
                stopProgressIndicator($btn, originalText);
                console.log('✅ Optimierte Generierung abgeschlossen in ' + totalTime + 's');
                
                if (response.success && response.data.suite) {
                    var suite = response.data.suite;
                    
                    // Felder mit Animation füllen
                    if (suite.meta_title) {
                        $('#retexify-new-meta-title').val('').fadeOut(200, function() {
                            $(this).val(suite.meta_title).fadeIn(200);
                        });
                    }
                    
                    if (suite.meta_description) {
                        $('#retexify-new-meta-description').val('').fadeOut(200, function() {
                            $(this).val(suite.meta_description).fadeIn(200);
                        });
                    }
                    
                    if (suite.focus_keyword) {
                        $('#retexify-new-focus-keyword').val('').fadeOut(200, function() {
                            $(this).val(suite.focus_keyword).fadeIn(200);
                        });
                    }
                    
                    // Charakterzähler aktualisieren
                    setTimeout(updateCharCounters, 300);
                    
                    // Erweiterte Erfolgsbenachrichtigung
                    var performanceInfo = '';
                    if (response.data.generation_time) {
                        performanceInfo = ' (in ' + response.data.generation_time + 's)';
                    }
                    if (response.data.tokens_used) {
                        performanceInfo += ' - ' + response.data.tokens_used + ' Tokens verwendet';
                    }
                    
                    showNotification('🚀 Alle SEO-Texte parallel generiert!' + performanceInfo, 'success', 5000);
                    
                    // Performance-Statistik anzeigen
                    showPerformanceStats(response.data);
                
            } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Unbekannter Fehler';
                    showNotification('❌ Generierung fehlgeschlagen: ' + errorMsg, 'error');
            }
        },
        error: function(xhr, status, error) {
                stopProgressIndicator($btn, originalText);
                
                if (status === 'timeout') {
                    showNotification('⏱️ Zeitüberschreitung - Versuchen Sie es mit weniger Text', 'warning');
                } else {
                    showNotification('❌ Verbindungsfehler: ' + error, 'error');
                }
                
                console.error('AJAX Error:', { xhr: xhr, status: status, error: error });
            }
        });
    });
    
    // NEUE FUNKTION: Fortschrittsanzeige
    function startProgressIndicator($btn) {
        var step = 0;
        var steps = [
            '🔄 Verbinde mit KI...',
            '📝 Generiere Meta-Titel...',
            '📄 Erstelle Beschreibung...',
            '🎯 Bestimme Keywords...',
            '✨ Finalisiere...'
        ];
        
        $btn.prop('disabled', true);
        
        // Fortschritt-Animation
        $btn.data('progressInterval', setInterval(function() {
            if (step < steps.length) {
                $btn.html(steps[step]);
                step++;
            } else {
                step = 1; // Zurück zum Anfang der Animation
            }
        }, 2000));
    }
    
    // NEUE FUNKTION: Fortschrittsanzeige stoppen
    function stopProgressIndicator($btn, originalText) {
        clearInterval($btn.data('progressInterval'));
            $btn.html(originalText).prop('disabled', false);
    }
    
    // NEUE FUNKTION: Performance-Statistiken anzeigen
    function showPerformanceStats(data) {
        if (!data.generation_time && !data.tokens_used) return;
        
        var statsHtml = '<div class="retexify-performance-stats" style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa; border-radius: 3px;">';
        statsHtml += '<strong>📊 Performance-Info:</strong><br>';
        
        if (data.generation_time) {
            var speedImprovement = Math.round((120 - data.generation_time) / 120 * 100);
            statsHtml += '⚡ Generierungszeit: <strong>' + data.generation_time + 's</strong>';
            if (speedImprovement > 0) {
                statsHtml += ' (<span style="color: green;">+' + speedImprovement + '% schneller</span>)';
            }
            statsHtml += '<br>';
        }
        
        if (data.tokens_used) {
            var costEstimate = (data.tokens_used * 0.0001).toFixed(4);
            statsHtml += '🎯 Tokens verwendet: <strong>' + data.tokens_used + '</strong> (~$' + costEstimate + ')<br>';
        }
        
        statsHtml += '</div>';
        
        // Bestehende Stats entfernen und neue hinzufügen
        $('.retexify-performance-stats').remove();
        $('#retexify-seo-form .form-table').after(statsHtml);
        
        // Nach 10 Sekunden ausblenden
        setTimeout(function() {
            $('.retexify-performance-stats').fadeOut(1000, function() {
                $(this).remove();
            });
        }, 10000);
    }
    
    // VERBESSERTE Benachrichtigungsfunktion
    function showNotification(message, type = 'info', duration = 3000) {
        type = type || 'info';
        duration = duration || 3000;
        
        // Icon basierend auf Typ
        var icons = {
            'success': '✅',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        
        var icon = icons[type] || icons['info'];
        
        // Bestehende Benachrichtigungen entfernen
        $('.retexify-notification').remove();
        
        // Neue Benachrichtigung erstellen
        var $notification = $('<div class="retexify-notification retexify-notification-' + type + '">')
            .html(icon + ' ' + message)
            .css({
                'position': 'fixed',
                'top': '50px',
                'right': '20px',
                'background': getNotificationColor(type),
                'color': '#fff',
                'padding': '12px 20px',
                'border-radius': '6px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
                'z-index': '999999',
                'font-weight': 'bold',
                'max-width': '400px',
                'opacity': '0',
                'transform': 'translateX(100%)',
                'transition': 'all 0.3s ease'
            });
        
        $('body').append($notification);
        
        // Animation einblenden
        setTimeout(function() {
            $notification.css({
                'opacity': '1',
                'transform': 'translateX(0)'
            });
        }, 10);
        
        // Automatisch ausblenden
    setTimeout(function() {
            $notification.css({
                'opacity': '0',
                'transform': 'translateX(100%)'
            });
        setTimeout(function() {
                $notification.remove();
            }, 300);
        }, duration);
    }
    
    // HILFSFUNKTION: Benachrichtigungsfarben
    function getNotificationColor(type) {
        var colors = {
            'success': '#28a745',
            'error': '#dc3545',
            'warning': '#ffc107',
            'info': '#17a2b8'
        };
        return colors[type] || colors['info'];
    }
    
    // NEUE FUNKTION: Cache für bessere Performance
    var textCache = {};
    var cacheExpiry = 300000; // 5 Minuten
    
    function getCachedText(cacheKey) {
        if (textCache[cacheKey] && (Date.now() - textCache[cacheKey].timestamp) < cacheExpiry) {
            console.log('📦 Cache-Hit für:', cacheKey);
            return textCache[cacheKey].data;
        }
        return null;
    }
    
    function setCachedText(cacheKey, data) {
        textCache[cacheKey] = {
            data: data,
            timestamp: Date.now()
        };
        console.log('💾 Text gecacht:', cacheKey);
    }
    
    // KEYBOARD SHORTCUTS für Power-User
    $(document).on('keydown', function(e) {
        // Strg + Shift + G = Alle Texte generieren
        if (e.ctrlKey && e.shiftKey && e.keyCode === 71) {
            e.preventDefault();
            $('#retexify-generate-all-seo').click();
            showNotification('⌨️ Keyboard-Shortcut: Generierung gestartet', 'info', 2000);
        }
        
        // Strg + Shift + S = Alle Texte speichern
        if (e.ctrlKey && e.shiftKey && e.keyCode === 83) {
                e.preventDefault();
            $('#retexify-save-all-seo').click();
            showNotification('⌨️ Keyboard-Shortcut: Speichern gestartet', 'info', 2000);
        }
    });
    
    // TOOLTIP für Keyboard-Shortcuts anzeigen
    function showKeyboardHints() {
        var $hints = $('<div class="retexify-keyboard-hints" style="position: fixed; bottom: 20px; right: 20px; background: #333; color: #fff; padding: 10px; border-radius: 4px; font-size: 11px; z-index: 999998;">')
            .html('💡 <strong>Shortcuts:</strong><br>Strg+Shift+G = Generieren<br>Strg+Shift+S = Speichern');
        
        $('body').append($hints);
        
        setTimeout(function() {
            $hints.fadeOut(1000, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Hints beim ersten Laden anzeigen
    setTimeout(showKeyboardHints, 2000);
    
    console.log('🚀 ReTexify Performance-Optimierungen geladen!');
    
    // ========== SYSTEM-TAB FUNKTIONEN ==========
    
    // System-Tab automatisch laden
    function loadSystemTab() {
        console.log('🔧 Lade System-Tab...');
        loadSystemStatus();
        loadIntelligentResearchStatus();
    }
    
    // System-Status laden (für den oberen Bereich)
    function loadSystemStatus() {
        $('#retexify-system-status-content').html('<div class="retexify-loading">🔧 Lade System-Status...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_test_system_status',
                nonce: retexify_ajax.nonce
            },
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    $('#retexify-system-status-content').html(response.data);
                } else {
                    $('#retexify-system-status-content').html('<div class="retexify-warning">❌ Fehler: ' + (response.data || 'Unbekannter Fehler') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ System-Status Fehler:', error);
                $('#retexify-system-status-content').html('<div class="retexify-warning">❌ Verbindungsfehler beim System-Status</div>');
            }
        });
    }
    
    // Intelligent Research Status laden (für den unteren Bereich)
    function loadIntelligentResearchStatus() {
        $('#research-engine-status-content').html('<div class="retexify-loading">🧠 Lade Research-Status...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_test_api_services',
                nonce: retexify_ajax.nonce
            },
            timeout: 20000,
            success: function(response) {
                if (response.success) {
                    $('#research-engine-status-content').html(response.data);
                } else {
                    $('#research-engine-status-content').html('<div class="retexify-warning">❌ Research-Fehler: ' + (response.data || 'Unbekannt') + '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Research-Status Fehler:', error);
                $('#research-engine-status-content').html('<div class="retexify-warning">❌ Verbindungsfehler beim Research-Status</div>');
            }
        });
    }
    
    // API Services erneut testen (Button-Handler)
    $(document).on('click', '#test-research-apis', function(e) {
        e.preventDefault(); // WICHTIG: Verhindert Seiten-Reload!
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.text('🔄 Teste APIs...').prop('disabled', true);
        
        // Research Status neu laden
        loadIntelligentResearchStatus();
        
        // Button nach 5 Sekunden wieder aktivieren
        setTimeout(function() {
            $btn.text(originalText).prop('disabled', false);
            showNotification('🔄 API-Tests abgeschlossen', 'success');
        }, 5000);
    });
    
    // System-Test Button Handler
    $(document).on('click', '#retexify-test-system-badge', function(e) {
        e.preventDefault(); // WICHTIG: Verhindert Seiten-Reload!
        
        var $btn = $(this);
        var originalText = $btn.text();
        
        $btn.text('🧪 Teste System...').prop('disabled', true);
        
        // System-Status neu laden
        loadSystemStatus();
        
        // Button nach 3 Sekunden wieder aktivieren
        setTimeout(function() {
            $btn.text(originalText).prop('disabled', false);
            showNotification('✅ System-Test abgeschlossen', 'success');
        }, 3000);
    });
    
    // Tab-System erweitern - ERSETZEN Sie die bestehende initializeTabs Funktion:
    function initializeTabs() {
        console.log('🔧 Initialisiere Tab-System...');
        
        // Event-Delegation für Tab-Buttons
        $(document).on('click', '.retexify-tab-btn', function(e) {
            e.preventDefault();
            var tabId = $(this).data('tab');
            console.log('📋 Tab geklickt:', tabId);
            
            // Alle Tabs deaktivieren
            $('.retexify-tab-btn').removeClass('active');
            $('.retexify-tab-content').removeClass('active');
            
            // Aktiven Tab setzen
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // Spezielle Tab-Aktionen
            if (tabId === 'dashboard') {
                loadDashboard();
            } else if (tabId === 'ai-settings') {
                setTimeout(initializeMultiAI, 100);
            } else if (tabId === 'system') {
                // FIXED: Sofortiges Laden des System-Status
                loadSystemStatusOnce();
            }
        });
        
        // Aktuellen Tab beim Laden prüfen
        var currentTab = new URLSearchParams(window.location.search).get('tab') || 'dashboard';
        if (currentTab === 'system') {
            setTimeout(loadSystemTab, 500);
        }
        
        console.log('✅ Tab-System initialisiert');
    }
    
    // Beim DOM-Ready ausführen
    jQuery(document).ready(function($) {
        // Tab-System initialisieren
        initializeTabs();
        
        // Dashboard initial laden
        loadDashboard();
        
        console.log('✅ ReTexify Admin JavaScript geladen');
    });
});

// UTILITY: Performance-Monitoring
window.ReTexifyPerformance = {
    startTime: null,
    endTime: null,
    
    start: function() {
        this.startTime = performance.now();
    },
    
    end: function() {
        this.endTime = performance.now();
        return (this.endTime - this.startTime) / 1000;
    },
    
    log: function(operation) {
        var time = this.end();
        console.log('⏱️ Performance:', operation, 'in', time.toFixed(2), 'Sekunden');
    }
};

/**
 * ENTFERNT: loadSystemStatusImmediate() - Ersetzt durch loadSystemStatusOnce()
 */

/**
 * ENTFERNT: formatSystemStatus() - Ersetzt durch direkte HTML-Ausgabe
 */

// NEUE FUNKTION 1: Tab-Handler bereinigt
function handleTabSwitch(tabId) {
    console.log('🔄 Tab gewechselt zu:', tabId);
    
    // Alle Tabs verstecken
    $('.retexify-tab-content').removeClass('active');
    $('.retexify-tab').removeClass('active');
    
    // Gewählten Tab aktivieren
    $('#' + tabId).addClass('active');
    $('.retexify-tab[data-tab="' + tabId + '"]').addClass('active');
    
    // Tab-spezifische Aktionen
    if (tabId === 'dashboard') {
        loadDashboard();
    } else if (tabId === 'ai-settings') {
        setTimeout(initializeMultiAI, 100);
    } else if (tabId === 'system') {
        // FIXED: Sofortiges Laden des System-Status ohne Dopplung
        loadSystemStatusOnce();
    } else if (tabId === 'export-import' && typeof loadExportImportTab === 'function') {
        loadExportImportTab();
    }
}

// NEUE FUNKTION 2: System-Status einmal laden
function loadSystemStatusOnce() {
    console.log('🔧 Lade System-Status einmalig...');
    
    // Nur laden wenn noch nicht geladen
    if (systemStatusLoaded) {
        console.log('ℹ️ System-Status bereits geladen');
        return;
    }
    
    var $statusContainer = $('#retexify-system-status');
    
    if ($statusContainer.length === 0) {
        console.warn('⚠️ System-Status Container nicht gefunden');
        return;
    }
    
    // Moderner Loading-Indikator
    $statusContainer.html(`
        <div class="retexify-loading-wrapper">
            <div class="retexify-spinner">🔄</div>
            <div class="retexify-loading-text">Prüfe System-Status...</div>
        </div>
    `);
    
    // AJAX-Aufruf mit korrekter Fehlerbehandlung
    $.ajax({
        url: retexify_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'retexify_test_system',
            nonce: retexify_ajax.nonce
        },
        timeout: 20000, // 20 Sekunden Timeout
        success: function(response) {
            console.log('🔧 System-Status Response:', response);
            
            if (response.success) {
                // Erfolgreiche Antwort - HTML direkt einfügen
                $statusContainer.html(response.data);
                systemStatusLoaded = true;
                showNotification('✅ System-Status erfolgreich geladen', 'success');
                
                // CSS-Animation triggern
                setTimeout(function() {
                    $statusContainer.find('.retexify-system-status-content').addClass('loaded');
                }, 100);
                
            } else {
                // Fehler-Anzeige mit korrekten CSS-Klassen
                $statusContainer.html(`
                    <div class="retexify-status-error">
                        <div class="status-error-icon">❌</div>
                        <div class="status-error-text">
                            <strong>System-Test fehlgeschlagen</strong><br>
                            ${response.data || 'Unbekannter Fehler'}
                        </div>
                    </div>
                `);
                showNotification('❌ System-Test fehlgeschlagen', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ System-Status AJAX Fehler:', status, error);
            console.error('Response Text:', xhr.responseText);
            
            $statusContainer.html(`
                <div class="retexify-status-error">
                    <div class="status-error-icon">🔌</div>
                    <div class="status-error-text">
                        <strong>Verbindungsfehler</strong><br>
                        Konnte System-Status nicht laden: ${error}
                    </div>
                </div>
            `);
            showNotification('❌ Verbindungsfehler beim System-Test', 'error');
        }
    });
}

// NEUE FUNKTION 3: Research-Engine-Status laden
function loadResearchEngineStatus() {
    console.log('🧠 Lade Research-Engine-Status...');
    
    var $researchContainer = $('#research-engine-status-content');
    
    if ($researchContainer.length === 0) {
        console.warn('⚠️ Research-Engine Container nicht gefunden');
        return;
    }
    
    $researchContainer.html(`
        <div class="retexify-loading-wrapper">
            <div class="retexify-spinner">🔄</div>
            <div class="retexify-loading-text">Teste Research-APIs...</div>
        </div>
    `);
    
    $.ajax({
        url: retexify_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'retexify_test_api_services',
            nonce: retexify_ajax.nonce
        },
        timeout: 25000,
        success: function(response) {
            console.log('🧠 Research-Engine Response:', response);
            
            if (response.success) {
                $researchContainer.html(response.data);
                showNotification('✅ Research-Engine-Status geladen', 'success');
            } else {
                $researchContainer.html(`
                    <div class="retexify-status-error">
                        <div class="status-error-icon">❌</div>
                        <div class="status-error-text">
                            <strong>Research-Engine Fehler</strong><br>
                            ${response.data || 'Unbekannter Fehler'}
                        </div>
                    </div>
                `);
                showNotification('❌ Research-Engine Fehler', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Research-Engine AJAX Fehler:', error);
            $researchContainer.html(`
                <div class="retexify-status-error">
                    <div class="status-error-icon">🔌</div>
                    <div class="status-error-text">
                        <strong>Research-Engine Verbindungsfehler</strong><br>
                        ${error}
                    </div>
                </div>
            `);
            showNotification('❌ Research-Engine Verbindungsfehler', 'error');
        }
    });
}

// ============================================================================
// 🔄 SCHRITT 3: EVENT-HANDLER HINZUFÜGEN/KORRIGIEREN
// ============================================================================

// System-Test Badge Button Handler
$(document).on('click', '#retexify-test-system-badge', function(e) {
    e.preventDefault();
    console.log('🧪 Manueller System-Test ausgelöst');
    
    var $badge = $(this);
    var originalText = $badge.html();
    
    // Button-Zustand ändern
    $badge.html('🔄 Teste...').addClass('testing').prop('disabled', true);
    
    // Flag zurücksetzen für erneuten Test
    systemStatusLoaded = false;
    
    // System-Status erneut laden
    loadSystemStatusOnce();
    
    // Button nach 5 Sekunden wieder aktivieren
    setTimeout(function() {
        $badge.html(originalText).removeClass('testing').prop('disabled', false);
    }, 5000);
});

// Research-API Test Button Handler
$(document).on('click', '#test-research-apis', function(e) {
    e.preventDefault();
    console.log('🧪 Research-APIs Test ausgelöst');
    
    var $button = $(this);
    var originalText = $button.html();
    
    $button.html('🔄 Teste APIs...').prop('disabled', true);
    
    loadResearchEngineStatus();
    
    setTimeout(function() {
        $button.html(originalText).prop('disabled', false);
    }, 5000);
});

// Tab-Click-Handler korrigiert
$(document).on('click', '.retexify-tab', function(e) {
    e.preventDefault();
    var tabId = $(this).data('tab');
    
    if (tabId) {
        handleTabSwitch(tabId);
    }
});