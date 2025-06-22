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
                setTimeout(function() {
                    $('#retexify-test-system').trigger('click');
                }, 200);
            }
        });
        
        console.log('✅ Tab-System initialisiert');
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
    $(document).on('click', '#retexify-refresh-stats', function(e) {
        e.preventDefault();
        console.log('🔄 Dashboard Refresh ausgelöst');
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('🔄 Aktualisiere...').prop('disabled', true);
        
        loadDashboard();
        
        setTimeout(function() {
            $btn.html(originalText).prop('disabled', false);
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
    
    $(document).on('click', '#retexify-test-system', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('🧪 Teste System...').prop('disabled', true);
        
        $('#retexify-system-status').html('<div class="retexify-loading">🔧 System wird getestet...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_test',
                nonce: retexify_ajax.nonce
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                if (response.success) {
                    $('#retexify-system-status').html(response.data);
                    showNotification('🧪 System-Test abgeschlossen', 'success');
                } else {
                    $('#retexify-system-status').html(
                        '<div class="retexify-warning">❌ System-Test fehlgeschlagen: ' + (response.data || 'Unbekannt') + '</div>'
                    );
                    showNotification('❌ System-Test fehlgeschlagen', 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim System-Test:', status, error);
                showNotification('❌ Verbindungsfehler beim System-Test', 'error');
            }
        });
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
    
    // BENACHRICHTIGUNG WENN SEITE VERLASSEN WIRD WÄHREND EINES PROZESSES
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
});