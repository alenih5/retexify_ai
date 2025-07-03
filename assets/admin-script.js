/**
 * ReTexify AI Pro - KOMPLETT ÜBERARBEITETES Admin JavaScript
 * Version: 4.4.0 - Vollständige Neuentwicklung mit allen Bugfixes
 * 
 * FIXES:
 * ✅ Meta-Text-Generierung vollständig funktionsfähig
 * ✅ CSV-Export mit korrektem File-Handling
 * ✅ Robuste AJAX-Error-Behandlung
 * ✅ Korrekte Parameter-Übertragung
 * ✅ Verbesserte Debugging-Funktionen
 */

// ============================================================================
// 🌍 GLOBALE VARIABLEN UND INITIALISIERUNG
// ============================================================================

window.retexifyGlobals = window.retexifyGlobals || {
    systemStatusLoaded: false,
    researchStatusLoaded: false,
    seoData: [],
    currentSeoIndex: 0,
    totalSeoItems: 0,
    isInitialized: false,
    ajaxInProgress: false,
    currentPostId: null,
    debugMode: false,
    autoDetectedPostId: null
};

// ============================================================================
// 🚀 HAUPT-JAVASCRIPT MIT VOLLSTÄNDIGER ERROR-BEHANDLUNG
// ============================================================================

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 ReTexify AI Pro JavaScript startet (Version 4.4.0)...');
    
    // Debug-Informationen
    if (typeof retexify_ajax !== 'undefined') {
        console.log('📊 AJAX Setup:', {
            url: retexify_ajax.ajax_url,
            nonce: retexify_ajax.nonce ? retexify_ajax.nonce.substring(0, 8) + '...' : 'FEHLT',
            debug: retexify_ajax.debug || false
        });
        window.retexifyGlobals.debugMode = retexify_ajax.debug || false;
    } else {
        console.error('❌ retexify_ajax Objekt nicht verfügbar!');
        showNotification('❌ JavaScript-Konfiguration fehlt', 'error', 5000);
        return;
    }
    
    // Einmalige Initialisierung
    if (!window.retexifyGlobals.isInitialized) {
        initializeReTexify();
        window.retexifyGlobals.isInitialized = true;
    }
    
    // ========================================================================
    // 🎯 HAUPT-INITIALISIERUNG
    // ========================================================================
    
    function initializeReTexify() {
        console.log('🔄 Initialisiere ReTexify AI Pro...');
        
        try {
            // Tab-System initialisieren
            initializeTabs();
            
            // Event-Listener einrichten
            setupEventListeners();
            
            // Dashboard laden falls sichtbar
            if ($('#retexify-dashboard-content').length > 0) {
                loadDashboard();
            }
            
            // SEO-Optimizer initialisieren
            initializeSeoOptimizer();
            
            console.log('✅ ReTexify AI Pro erfolgreich initialisiert');
            showNotification('🚀 ReTexify AI erfolgreich geladen', 'success', 3000);
            
        } catch (error) {
            console.error('❌ Fehler bei der Initialisierung:', error);
            showNotification('❌ Initialisierungsfehler: ' + error.message, 'error', 5000);
        }
    }
    
    // ========================================================================
    // 🎯 TAB-SYSTEM
    // ========================================================================
    
    function initializeTabs() {
        console.log('🔄 Initialisiere Tab-System...');
        
        // Event-Delegation für Tab-Clicks
        $(document).off('click.retexify-tabs').on('click.retexify-tabs', '.retexify-tab-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var tabId = $btn.data('tab') || $btn.attr('data-tab');
            
            if (!tabId) {
                console.warn('⚠️ Keine Tab-ID gefunden in:', $btn);
                return;
            }
            
            console.log('🔄 Tab-Wechsel zu:', tabId);
            
            // UI-Update
            $('.retexify-tab-btn').removeClass('active');
            $('.retexify-tab-content').removeClass('active');
            
            $btn.addClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // Tab-spezifische Aktionen
            setTimeout(function() {
                handleTabSwitch(tabId);
            }, 100);
        });
    }
    
    function handleTabSwitch(tabId) {
        console.log('🎯 Behandle Tab-Wechsel:', tabId);
        
        try {
            switch(tabId) {
                case 'system':
                    if (!window.retexifyGlobals.systemStatusLoaded) {
                        setTimeout(loadSystemStatus, 200);
                    }
                    if (!window.retexifyGlobals.researchStatusLoaded) {
                        setTimeout(loadResearchStatus, 2500);
                    }
                    break;
                    
                case 'dashboard':
                    loadDashboard();
                    break;
                    
                case 'seo-optimizer':
                    initializeSeoOptimizer();
                    break;
                    
                case 'export-import':
                    initializeExportImport();
                    break;
            }
        } catch (error) {
            console.error('❌ Fehler beim Tab-Wechsel:', error);
        }
    }
    
    // ========================================================================
    // 📊 DASHBOARD FUNKTIONEN
    // ========================================================================
    
    function loadDashboard() {
        console.log('📊 Lade Dashboard...');
        
        var $container = $('#retexify-dashboard-content');
        if ($container.length === 0) {
            console.warn('⚠️ Dashboard-Container nicht gefunden');
            return;
        }
        
        $container.html('<div class="retexify-loading">📊 Lade Dashboard-Statistiken...</div>');
        
        executeAjaxCall({
            action: 'retexify_get_stats',
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    $container.html(response.data);
                    showNotification('Dashboard geladen', 'success', 2000);
                    } else {
                    throw new Error(response.data || 'Dashboard-Fehler');
                }
            },
            error: function(error) {
                $container.html('<div class="retexify-error">❌ Dashboard-Fehler: ' + error + '</div>');
                showNotification('❌ Dashboard-Fehler', 'error', 3000);
            }
        });
    }
    
    // ========================================================================
    // 🔧 SYSTEM-STATUS FUNKTIONEN
    // ========================================================================
    
    function loadSystemStatus() {
        console.log('🔍 loadSystemStatus() aufgerufen');
        
        if (window.retexifyGlobals.systemStatusLoaded) {
            console.log('📊 System-Status bereits geladen');
            return;
        }
        
        var $container = $('#retexify-system-status');
        if ($container.length === 0) {
            console.error('❌ System-Status Container nicht gefunden');
            return;
        }
        
        window.retexifyGlobals.systemStatusLoaded = true;
        
        var loadingHTML = `
            <div class="retexify-loading-status">
                <div class="loading-spinner">🔄</div>
                <div class="loading-text">System wird analysiert...</div>
                <div class="loading-detail">Teste WordPress, PHP, APIs...</div>
            </div>
        `;
        $container.html(loadingHTML);
        
        executeAjaxCall({
            action: 'retexify_test_system',
            timeout: 20000,
            success: function(response) {
                if (response.success) {
                    $container.html(response.data);
                    showNotification('System-Status geladen', 'success', 2000);
                } else {
                    throw new Error(response.data || 'System-Test fehlgeschlagen');
                }
            },
            error: function(error) {
                window.retexifyGlobals.systemStatusLoaded = false;
                $container.html(createErrorHTML('System-Test fehlgeschlagen', error));
                showNotification('❌ System-Test fehlgeschlagen', 'error', 3000);
            }
        });
    }
    
    function loadResearchStatus() {
        console.log('🧠 loadResearchStatus() aufgerufen');
        
        if (window.retexifyGlobals.researchStatusLoaded) {
            console.log('🧠 Research-Status bereits geladen');
            return;
        }
        
        var $container = $('#retexify-research-engine-status, #research-engine-status-content').first();
        if ($container.length === 0) {
            console.log('ℹ️ Research-Status Container nicht gefunden - das ist optional');
            window.retexifyGlobals.researchStatusLoaded = true;
            return;
        }
        
        window.retexifyGlobals.researchStatusLoaded = true;
        
        var loadingHTML = `
            <div class="retexify-loading-status">
                <div class="loading-spinner">🧠</div>
                <div class="loading-text">Research-Engine wird getestet...</div>
                <div class="loading-detail">Teste Google, Wikipedia, OpenStreetMap...</div>
            </div>
        `;
        $container.html(loadingHTML);
        
        executeAjaxCall({
            action: 'retexify_test_research_apis',
            timeout: 25000,
            success: function(response) {
                if (response.success) {
                    $container.html(response.data);
                    showNotification('Research-Engine getestet', 'success', 2000);
                } else {
                    throw new Error(response.data || 'Research-Test fehlgeschlagen');
                }
            },
            error: function(error) {
                var warningHTML = `
                    <div class="retexify-status-warning">
                        <div class="warning-icon">⚠️</div>
                        <div class="warning-content">
                            <h4>Research-Engine</h4>
                            <p>Externe APIs temporär nicht erreichbar. Das beeinträchtigt die Hauptfunktionen nicht.</p>
                            <small>Details: ${error}</small>
                        </div>
                    </div>
                `;
                $container.html(warningHTML);
                showNotification('ℹ️ Research-Engine temporär offline', 'info', 3000);
            }
        });
    }
    
    // ========================================================================
    // 🚀 SEO-OPTIMIZER FUNKTIONEN - KOMPLETT ÜBERARBEITET
    // ========================================================================
    
    function initializeSeoOptimizer() {
        console.log('🚀 Initialisiere SEO-Optimizer...');
        
        // Character-Counter initialisieren
        updateCharCounters();
        
        // Post-Type Change Handler
        $(document).off('change.seo-post-type').on('change.seo-post-type', '#seo-post-type', function() {
            var $loadBtn = $('#retexify-load-seo-content');
            $loadBtn.prop('disabled', false);
            $('#retexify-seo-content-list').hide();
            console.log('📝 Post-Typ geändert zu:', $(this).val());
        });
    }
    
    function loadSeoContent() {
        if (window.retexifyGlobals.ajaxInProgress) {
            console.warn('⚠️ AJAX bereits in Bearbeitung, warte...');
            showNotification('⚠️ Bitte warten, Vorgang läuft bereits...', 'warning', 3000);
            return;
        }
        
        var $btn = $('#retexify-load-seo-content');
        var originalText = $btn.html();
        var postType = $('#seo-post-type').val() || 'page';
        
        console.log('📄 Lade SEO Content für Post-Typ:', postType);
        
        $btn.html('🔄 Lade Content...').prop('disabled', true);
        window.retexifyGlobals.ajaxInProgress = true;
        
        executeAjaxCall({
            action: 'retexify_load_content',
            data: {
                post_type: postType
            },
            timeout: 20000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                
                if (!response.success) {
                    throw new Error(response.data || 'Content-Laden fehlgeschlagen');
                }
                
                console.log('📄 SEO Content Response:', response);
                
                var data = response.data;
                var items = data.items || data.posts || data.pages || data || [];
                
                // Globale SEO-Daten speichern
                window.retexifyGlobals.seoData = items;
                window.retexifyGlobals.currentSeoIndex = 0;
                window.retexifyGlobals.totalSeoItems = items.length;
                
                if (window.retexifyGlobals.totalSeoItems > 0) {
                    $('#retexify-seo-content-list').show();
                    displayCurrentSeoItem();
                    updateSeoNavigation();
                    showNotification('📄 ' + window.retexifyGlobals.totalSeoItems + ' Einträge für "' + postType + '" geladen', 'success', 3000);
                } else {
                    $('#retexify-seo-content-list').hide();
                    showNotification('⚠️ Keine Einträge für Post-Typ "' + postType + '" gefunden', 'warning', 4000);
                }
            },
            error: function(error) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                console.error('❌ SEO Content Fehler:', error);
                showNotification('❌ Content-Laden fehlgeschlagen: ' + error, 'error', 5000);
            }
        });
    }
    
    function displayCurrentSeoItem() {
        if (!window.retexifyGlobals.seoData || window.retexifyGlobals.seoData.length === 0) {
            console.warn('⚠️ Keine SEO-Daten verfügbar');
            return;
        }
        var currentItem = window.retexifyGlobals.seoData[window.retexifyGlobals.currentSeoIndex];
        if (!currentItem) {
            console.warn('⚠️ Kein aktueller SEO-Eintrag gefunden');
            return;
        }
        console.log('📄 Zeige SEO-Eintrag:', currentItem);
        window.retexifyGlobals.currentPostId = currentItem.id || currentItem.ID;
        $('#retexify-current-page-title').text(currentItem.title || currentItem.post_title || 'Unbekannter Titel');
        $('#retexify-page-info').text('ID: ' + window.retexifyGlobals.currentPostId + ' | Status: ' + (currentItem.status || currentItem.post_status || 'publish'));
        if (currentItem.url || currentItem.permalink) {
            $('#retexify-page-url').attr('href', currentItem.url || currentItem.permalink);
        }
        if (currentItem.edit_url || currentItem.edit_link) {
            $('#retexify-edit-page').attr('href', currentItem.edit_url || currentItem.edit_link);
        }
        var contentText = currentItem.full_content || currentItem.content || currentItem.post_content || '';
        if (contentText) {
            var cleanContent = $('<div>').html(contentText).text();
            $('#retexify-content-text').text(cleanContent.substring(0, 500) + (cleanContent.length > 500 ? '...' : ''));
            updateContentStats(cleanContent);
        }
        $('#retexify-current-meta-title').text(currentItem.meta_title || 'Nicht gesetzt');
        $('#retexify-current-meta-description').text(currentItem.meta_description || 'Nicht gesetzt');
        $('#retexify-current-focus-keyword').text(currentItem.focus_keyword || 'Nicht gesetzt');
        // Generierte Felder leeren (Fix für Problem 1)
        $('#retexify-new-meta-title').val('');
        $('#retexify-new-meta-description').val('');
        $('#retexify-new-focus-keyword').val('');
        $('.retexify-generate-single, #retexify-generate-all-seo').prop('disabled', false);
        console.log('SEO-Eintrag angezeigt für Post-ID:', window.retexifyGlobals.currentPostId);
    }
    
    function updateSeoNavigation() {
        var current = window.retexifyGlobals.currentSeoIndex + 1;
        var total = window.retexifyGlobals.totalSeoItems;
        
        $('#retexify-seo-counter').text(current + ' / ' + total);
        
        $('#retexify-seo-prev').prop('disabled', window.retexifyGlobals.currentSeoIndex === 0);
        $('#retexify-seo-next').prop('disabled', window.retexifyGlobals.currentSeoIndex >= total - 1);
    }
    
    function updateContentStats(content) {
        if (!content) return;
        
        var wordCount = content.split(/\s+/).filter(function(word) { return word.length > 0; }).length;
        var charCount = content.length;
        
        $('#retexify-word-count').text(wordCount + ' Wörter');
        $('#retexify-char-count').text(charCount + ' Zeichen');
    }
    
    // ========================================================================
    // 🎨 SEO-GENERIERUNG FUNKTIONEN - VOLLSTÄNDIG ÜBERARBEITET
    // ========================================================================
    
    function generateSingleSeo(seoType) {
        if (!window.retexifyGlobals.currentPostId) {
            showNotification('❌ Keine Post-ID verfügbar', 'error', 3000);
            return;
        }
        
        if (window.retexifyGlobals.ajaxInProgress) {
            showNotification('⚠️ Bitte warten, Generierung läuft bereits...', 'warning', 3000);
            return;
        }
        
        var $btn = $('.retexify-generate-single[data-type="' + seoType + '"]');
        if ($btn.length === 0) {
            showNotification('❌ Button für ' + seoType + ' nicht gefunden', 'error', 3000);
            return;
        }
        var originalText = $btn.html();
        
        console.log('🔄 Generiere', seoType, 'für Post-ID:', window.retexifyGlobals.currentPostId);
        
        $btn.html('🔄 Generiert...').prop('disabled', true);
        window.retexifyGlobals.ajaxInProgress = true;
        
        executeAjaxCall({
            action: 'retexify_generate_single_seo',
            data: {
                post_id: window.retexifyGlobals.currentPostId,
                seo_type: seoType
            },
            timeout: 45000, // 45 Sekunden für KI-Generierung
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                
                if (!response.success) {
                    throw new Error(response.data || 'Generierung fehlgeschlagen');
                }
                
                console.log('✅ Generierung erfolgreich:', response);
                
                if (response.data && response.data.generated_text) {
                    var generatedText = response.data.generated_text;
                    
                    // Generierte Texte in die entsprechenden Felder einfügen
                    switch(seoType) {
                        case 'meta_title':
                            $('#retexify-new-meta-title').val(generatedText);
                            break;
                        case 'meta_description':
                            $('#retexify-new-meta-description').val(generatedText);
                            break;
                        case 'focus_keyword':
                            $('#retexify-new-focus-keyword').val(generatedText);
                            break;
                    }
                    
                    updateCharCounters();
                    showNotification(getSeoTypeLabel(seoType) + ' erfolgreich generiert', 'success', 3000);
                } else {
                    throw new Error('Keine generierten Daten erhalten');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                console.error('❌ Generierung fehlgeschlagen:', {
                    seoType: seoType,
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'Keine Antwort'
                });
                var detailedError = 'Generierung fehlgeschlagen';
                if (xhr.status === 400) {
                    detailedError = 'Ungültige Anfrage - prüfe Parameter';
                } else if (xhr.status === 500) {
                    detailedError = 'Server-Fehler - prüfe API-Konfiguration';
                } else if (status === 'timeout') {
                    detailedError = 'Zeitüberschreitung - KI antwortet nicht';
                } else if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data) {
                            detailedError = errorResponse.data;
                        }
                    } catch (e) {
                        if (xhr.responseText.includes('Fatal error')) {
                            detailedError = 'PHP-Fehler - prüfe KI-Engine Konfiguration';
                        }
                    }
                }
                showNotification('❌ ' + getSeoTypeLabel(seoType) + ': ' + detailedError, 'error', 5000);
            }
        });
    }
    
    // ========================================================================
    // 🆕 AUTO-POST-ID-ERKENNUNG (LÖSUNG FÜR "ALLE TEXTE GENERIEREN")
    // ========================================================================

    function autoDetectCurrentPost() {
        console.log('🔍 Auto-Erkenne aktuelle Post-ID...');
        // Methode 1: Aus URL-Parametern (WordPress Admin)
        const urlParams = new URLSearchParams(window.location.search);
        const postFromUrl = urlParams.get('post') || urlParams.get('post_id');
        if (postFromUrl && parseInt(postFromUrl) > 0) {
            window.retexifyGlobals.autoDetectedPostId = parseInt(postFromUrl);
            console.log('✅ Post-ID aus URL erkannt:', window.retexifyGlobals.autoDetectedPostId);
            return;
        }
        // Methode 2: Aus WordPress Adminbar (falls verfügbar)
        const editLink = $('#wp-admin-bar-edit a').attr('href');
        if (editLink) {
            const match = editLink.match(/post=(\d+)/);
            if (match) {
                window.retexifyGlobals.autoDetectedPostId = parseInt(match[1]);
                console.log('✅ Post-ID aus Adminbar erkannt:', window.retexifyGlobals.autoDetectedPostId);
                return;
            }
        }
        // Methode 3: Aus DOM-Elementen mit post-id Attributen
        const postIdFromDom = $('[data-post-id]').first().data('post-id') || $('#post_ID').val();
        if (postIdFromDom && parseInt(postIdFromDom) > 0) {
            window.retexifyGlobals.autoDetectedPostId = parseInt(postIdFromDom);
            console.log('✅ Post-ID aus DOM erkannt:', window.retexifyGlobals.autoDetectedPostId);
            return;
        }
        console.log('⚠️ Keine Post-ID automatisch erkannt - Content laden erforderlich');
    }

    function getCurrentPostId() {
        // Priorisierung: 1. Geladene Post-ID, 2. Auto-erkannte Post-ID
        return window.retexifyGlobals.currentPostId || window.retexifyGlobals.autoDetectedPostId;
    }

    // ========================================================================
    // 🔧 VERBESSERTE "ALLE TEXTE GENERIEREN" FUNKTION
    // ========================================================================

    function generateAllSeoFixed() {
        console.log('🚀 Starte verbesserte Alle-SEO-Generierung...');
        const postId = getCurrentPostId();
        if (!postId) {
            showNotification('❌ Keine Post-ID verfügbar. Bitte wählen Sie zuerst eine Seite/Post aus oder laden Sie Content.', 'error', 5000);
            setTimeout(function() {
                showNotification('💡 Tipp: Gehen Sie zu einer Post/Page im WordPress Admin oder klicken Sie "SEO-Content laden"', 'info', 7000);
            }, 2000);
            return;
        }
        if (window.retexifyGlobals.ajaxInProgress) {
            showNotification('⚠️ Bitte warten, Generierung läuft bereits...', 'warning', 3000);
            return;
        }
        var $btn = $('#retexify-generate-all-seo, #retexify-enhanced-generate');
        if ($btn.length === 0) {
            showNotification('❌ "Alle Texte generieren" Button nicht gefunden', 'error', 3000);
            return;
        }
        var originalText = $btn.html();
        console.log('🎯 Generiere alle SEO-Texte für Post-ID:', postId);
        $btn.html('🔄 Generiert alle...').prop('disabled', true);
        window.retexifyGlobals.ajaxInProgress = true;
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 120000, // 2 Minuten für alle 3 Texte
            data: {
                action: 'retexify_generate_complete_seo',
                nonce: retexify_ajax.nonce,
                post_id: postId
            },
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                console.log('✅ Komplette Generierung erfolgreich:', response);
                if (response.success && response.data) {
                    var data = response.data;
                    var generatedCount = 0;
                    if (data.meta_title) {
                        $('#retexify-new-meta-title').val(data.meta_title);
                        generatedCount++;
                    }
                    if (data.meta_description) {
                        $('#retexify-new-meta-description').val(data.meta_description);
                        generatedCount++;
                    }
                    if (data.focus_keyword) {
                        $('#retexify-new-focus-keyword').val(data.focus_keyword);
                        generatedCount++;
                    }
                    updateCharCounters();
                    if (generatedCount > 0) {
                        showNotification('✅ ' + generatedCount + ' SEO-Texte erfolgreich generiert', 'success', 4000);
                } else {
                        showNotification('⚠️ Keine SEO-Texte generiert - prüfe die Einzelgenerierung', 'warning', 4000);
                    }
                } else {
                    var errorMessage = 'Keine generierten Daten erhalten';
                    if (response.data && typeof response.data === 'string') {
                        errorMessage = response.data;
                    }
                    throw new Error(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                console.error('❌ Komplette Generierung fehlgeschlagen:', {
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 300) : 'Keine Antwort'
                });
                var detailedError = 'Komplette Generierung fehlgeschlagen';
                if (xhr.status === 500) {
                    detailedError = 'Server-Fehler - prüfe PHP-Logs für Details';
                } else if (xhr.status === 0) {
                    detailedError = 'Verbindungsfehler - Server nicht erreichbar';
                } else if (status === 'timeout') {
                    detailedError = 'Zeitüberschreitung - Generierung dauert zu lange (versuche einzeln)';
                } else if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data) {
                            detailedError = errorResponse.data;
                        }
                    } catch (e) {
                        if (xhr.responseText.includes('Fatal error')) {
                            detailedError = 'PHP-Fehler - prüfe API-Konfiguration';
                        }
                    }
                }
                showNotification('❌ ' + detailedError, 'error', 6000);
                setTimeout(function() {
                    showNotification('💡 Tipp: Versuche die Texte einzeln zu generieren', 'info', 5000);
                }, 2000);
            }
        });
    }
    
    function saveSeoTexts() {
        if (!window.retexifyGlobals.currentPostId) {
            showNotification('❌ Keine Post-ID verfügbar', 'error', 3000);
            return;
        }
        
        var metaTitle = $('#retexify-new-meta-title').val().trim();
        var metaDescription = $('#retexify-new-meta-description').val().trim();
        var focusKeyword = $('#retexify-new-focus-keyword').val().trim();
        
        if (!metaTitle && !metaDescription && !focusKeyword) {
            showNotification('⚠️ Keine Daten zum Speichern vorhanden', 'warning', 3000);
            return;
        }
        
        var $btn = $('#retexify-save-seo-texts');
        var originalText = $btn.html();
        
        console.log('💾 Speichere SEO-Texte für Post-ID:', window.retexifyGlobals.currentPostId);
        
        $btn.html('💾 Speichert...').prop('disabled', true);
        
        executeAjaxCall({
            action: 'retexify_save_seo_data',
            data: {
                post_id: window.retexifyGlobals.currentPostId,
                meta_title: metaTitle,
                meta_description: metaDescription,
                focus_keyword: focusKeyword
            },
            timeout: 15000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                
                if (!response.success) {
                    throw new Error(response.data || 'Speichern fehlgeschlagen');
                }
                
                if (response.data && response.data.saved_count) {
                    showNotification('💾 ' + response.data.saved_count + ' SEO-Elemente erfolgreich gespeichert', 'success', 4000);
                } else {
                    showNotification('✅ SEO-Daten erfolgreich gespeichert', 'success', 3000);
                }
                
                // Aktuelle Anzeige aktualisieren
                if (metaTitle) $('#retexify-current-meta-title').text(metaTitle);
                if (metaDescription) $('#retexify-current-meta-description').text(metaDescription);
                if (focusKeyword) $('#retexify-current-focus-keyword').text(focusKeyword);
                
            },
            error: function(error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ Speichern fehlgeschlagen:', error);
                showNotification('❌ Speichern fehlgeschlagen: ' + error, 'error', 4000);
            }
        });
    }
    
    // ========================================================================
    // 📤 EXPORT/IMPORT FUNKTIONEN - VOLLSTÄNDIG IMPLEMENTIERT
    // ========================================================================
    
    function initializeExportImport() {
        console.log('📤 Initialisiere Export/Import...');
        
        // File-Drop Handler für CSV-Import
        var $uploadArea = $('#retexify-csv-upload-area');
        var $fileInput = $('#retexify-csv-file-input');
        
        if ($uploadArea.length > 0 && $fileInput.length > 0) {
            // Drag & Drop Events
            $uploadArea.on('dragover dragenter', function(e) {
        e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
    });
    
            $uploadArea.on('dragleave dragend', function(e) {
        e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $uploadArea.on('drop', function(e) {
        e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleCsvFileUpload(files[0]);
                }
            });
            
            // Click Handler für Upload-Area
            $uploadArea.on('click', function() {
                $fileInput.click();
            });
            
            // File Input Change Handler
            $fileInput.on('change', function() {
                var files = this.files;
                if (files.length > 0) {
                    handleCsvFileUpload(files[0]);
                }
            });
        }
    }
    
    function startCsvExport() {
        console.log('📤 Starte CSV-Export...');
        var $btn = $('#retexify-start-export');
        if ($btn.length === 0) {
            showNotification('❌ Export-Button nicht gefunden', 'error', 3000);
                return;
            }
        var originalText = $btn.html();
        // Export-Optionen sammeln
        var postTypes = [];
        $('input[name="export_post_types[]"]:checked').each(function() {
            postTypes.push($(this).val());
        });
        var statusTypes = [];
        $('input[name="export_status[]"]:checked').each(function() {
            statusTypes.push($(this).val());
        });
        var contentTypes = [];
        $('input[name="export_content[]"]:checked').each(function() {
            contentTypes.push($(this).val());
        });
        if (postTypes.length === 0) {
            showNotification('⚠️ Bitte wählen Sie mindestens einen Post-Typ aus', 'warning', 3000);
                return;
            }
        console.log('📤 Export-Parameter:', {
            post_types: postTypes,
            status: statusTypes,
            content: contentTypes
        });
        $btn.html('📤 Exportiert...').prop('disabled', true);
        // KORRIGIERTE AJAX-Parameter
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
            dataType: 'json',
            timeout: 60000, // 60 Sekunden
                data: {
                action: 'retexify_export_content_csv',
                    nonce: retexify_ajax.nonce,
                post_types: postTypes,
                status: statusTypes,
                content: contentTypes
            },
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                console.log('✅ Export erfolgreich:', response);
                if (response.success && response.data && response.data.download_url) {
                    showNotification('Export erfolgreich - Download startet...', 'success', 3000);
                    // Download starten
                    window.location.href = response.data.download_url;
                    // Export-Ergebnis anzeigen
                    if (response.data.row_count) {
                        setTimeout(function() {
                            showNotification('📊 ' + response.data.row_count + ' Einträge exportiert', 'info', 5000);
                        }, 2000);
                    }
                    } else {
                    var errorMessage = 'Export fehlgeschlagen';
                    if (response.data && typeof response.data === 'string') {
                        errorMessage = response.data;
                    }
                    throw new Error(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                    $btn.html(originalText).prop('disabled', false);
                console.error('❌ Export fehlgeschlagen:', {
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'Keine Antwort'
                });
                var detailedError = 'Export fehlgeschlagen';
                if (xhr.status === 500) {
                    detailedError = 'Server-Fehler - prüfe PHP-Logs';
                } else if (xhr.status === 0) {
                    detailedError = 'Verbindungsfehler - Server nicht erreichbar';
                } else if (status === 'timeout') {
                    detailedError = 'Zeitüberschreitung - Export dauert zu lange';
                } else if (xhr.responseText) {
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data) {
                            detailedError = errorResponse.data;
                        }
                    } catch (e) {
                        // HTML-Response (WordPress-Fehlerseite)
                        if (xhr.responseText.includes('Fatal error')) {
                            detailedError = 'PHP-Fehler - prüfe KI-Engine Konfiguration';
                        }
                    }
                }
                showNotification('❌ ' + detailedError, 'error', 5000);
            }
        });
    }
    
    function handleCsvFileUpload(file) {
        console.log('📥 Verarbeite CSV-Upload:', file.name);
        
        // Datei-Validierung
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showNotification('❌ Bitte wählen Sie eine CSV-Datei aus', 'error', 3000);
                return;
            }
        
        if (file.size > 10 * 1024 * 1024) { // 10 MB Limit
            showNotification('❌ Datei zu groß (Maximum: 10 MB)', 'error', 3000);
                return;
            }
        
        var formData = new FormData();
        formData.append('csv_file', file);
        formData.append('action', 'retexify_import_csv_data');
        formData.append('nonce', retexify_ajax.nonce);
        
        var $importResults = $('#retexify-import-results');
        $importResults.show().html(`
            <div class="retexify-loading">
                📥 Lade CSV-Datei "${file.name}" hoch...
            </div>
        `);
        
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
                timeout: 30000,
                success: function(response) {
                console.log('📥 Upload-Response:', response);
                
                    if (response.success) {
                    displayImportPreview(response.data);
                    showNotification('✅ CSV-Datei erfolgreich hochgeladen', 'success', 3000);
                    } else {
                    throw new Error(response.data || 'Upload fehlgeschlagen');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Upload-Fehler:', error);
                $importResults.html(`
                    <div class="retexify-error">
                        ❌ Upload fehlgeschlagen: ${error}
                    </div>
                `);
                showNotification('❌ Upload fehlgeschlagen: ' + error, 'error', 5000);
            }
        });
    }
    
    function displayImportPreview(data) {
        console.log('👁️ Zeige Import-Vorschau:', data);
        
        var $importResults = $('#retexify-import-results');
        
        if (!data.preview || !data.headers) {
            $importResults.html('<div class="retexify-error">❌ Ungültige CSV-Daten</div>');
        return;
    }
    
        var previewHtml = `
            <div class="retexify-import-preview">
                <h4>📊 Import-Vorschau: ${data.filename}</h4>
                <p><strong>Zeilen:</strong> ${data.row_count} | <strong>Spalten:</strong> ${data.headers.length}</p>
                
                <div class="retexify-preview-table">
                    <table class="retexify-table">
                        <thead>
                            <tr>
                                ${data.headers.map(header => '<th>' + header + '</th>').join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${data.preview.slice(0, 5).map(row => 
                                '<tr>' + data.headers.map(header => '<td>' + (row[header] || '') + '</td>').join('') + '</tr>'
                            ).join('')}
                        </tbody>
                    </table>
                </div>
                
                <div class="retexify-import-actions">
                    <button type="button" id="retexify-confirm-import" class="retexify-btn retexify-btn-primary">
                        ✅ Import durchführen
                    </button>
                    <button type="button" id="retexify-cancel-import" class="retexify-btn retexify-btn-secondary">
                        ❌ Abbrechen
                    </button>
                </div>
            </div>
        `;
        
        $importResults.html(previewHtml);
        
        // Import-Actions Event-Handler
        $('#retexify-confirm-import').on('click', function() {
            executeImport(data.filename);
        });
        
        $('#retexify-cancel-import').on('click', function() {
            $importResults.hide().html('');
            showNotification('Import abgebrochen', 'info', 2000);
        });
    }
    
    function executeImport(filename) {
        console.log('🔄 Führe Import durch für:', filename);
        
        var $btn = $('#retexify-confirm-import');
        var originalText = $btn.html();
        
        $btn.html('🔄 Importiert...').prop('disabled', true);
        
        executeAjaxCall({
            action: 'retexify_save_imported_data',
            data: {
                filename: filename
            },
            timeout: 120000, // 2 Minuten für Import
            success: function(response) {
                if (!response.success) {
                    throw new Error(response.data || 'Import fehlgeschlagen');
                }
                
                console.log('✅ Import erfolgreich:', response);
                
                var $importResults = $('#retexify-import-results');
                $importResults.html(`
                    <div class="retexify-success">
                        <h4>✅ Import erfolgreich abgeschlossen</h4>
                        <p><strong>Verarbeitete Einträge:</strong> ${response.data.imported_count || 0}</p>
                        <p><strong>Aktualisierte Posts:</strong> ${response.data.updated_count || 0}</p>
                        ${response.data.errors && response.data.errors.length > 0 ? 
                            '<p><strong>Fehler:</strong> ' + response.data.errors.length + '</p>' : ''
                        }
                    </div>
                `);
                
                showNotification('✅ Import erfolgreich: ' + (response.data.imported_count || 0) + ' Einträge verarbeitet', 'success', 5000);
            },
            error: function(error) {
            $btn.html(originalText).prop('disabled', false);
                console.error('❌ Import fehlgeschlagen:', error);
                showNotification('❌ Import fehlgeschlagen: ' + error, 'error', 5000);
            }
        });
    }
    
    // ========================================================================
    // 🛠️ ROBUSTE AJAX-WRAPPER FUNKTION
    // ========================================================================
    
    function executeAjaxCall(options) {
        var defaults = {
            url: retexify_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 15000,
            data: {
                nonce: retexify_ajax.nonce
            }
        };
        
        // Options mergen
        var settings = $.extend(true, {}, defaults, options);
        
        // Action hinzufügen
        if (options.action) {
            settings.data.action = options.action;
        }
        
        // Zusätzliche Daten hinzufügen
        if (options.data) {
            $.extend(settings.data, options.data);
        }
        
        console.log('📡 Execute AJAX Call:', {
            action: settings.data.action,
            post_id: settings.data.post_id,
            timeout: settings.timeout
        });
        
        if (window.retexifyGlobals.debugMode) {
            console.log('🐛 Debug AJAX Data:', settings.data);
        }
        
        return $.ajax(settings)
            .done(function(response) {
                console.log('✅ AJAX Success für', settings.data.action, ':', response);
                
                if (typeof options.success === 'function') {
                    options.success(response);
                }
            })
            .fail(function(xhr, status, error) {
                var detailedError = 'Verbindungsfehler';
                
                console.error('❌ AJAX Fail für', settings.data.action, ':', {
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'Keine Antwort'
                });
                
                // Detaillierte Fehleranalyse
                if (xhr.status === 400) {
                    detailedError = 'Ungültige Anfrage - Prüfe Parameter';
                } else if (xhr.status === 403) {
                    detailedError = 'Zugriff verweigert - Prüfe Berechtigung';
                } else if (xhr.status === 404) {
                    detailedError = 'AJAX-Handler nicht gefunden';
                } else if (xhr.status === 500) {
                    detailedError = 'Server-Fehler - Prüfe PHP-Logs';
                } else if (status === 'timeout') {
                    detailedError = 'Zeitüberschreitung - Server antwortet nicht';
                } else if (status === 'parsererror') {
                    detailedError = 'JSON-Parse-Fehler - Ungültige Server-Antwort';
                } else if (xhr.responseText) {
                    // Versuche Fehler aus Response-Text zu extrahieren
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data) {
                            detailedError = errorResponse.data;
                        }
                    } catch (e) {
                        // HTML-Response (WordPress-Fehlerseite)
                        if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('PHP')) {
                            detailedError = 'PHP-Fehler auf dem Server';
                        }
                    }
                }
                
                if (typeof options.error === 'function') {
                    options.error(detailedError);
                }
            });
    }
    
    // ========================================================================
    // 🎨 CHARACTER COUNTER UND UI-HELPERS
    // ========================================================================
    
    function updateCharCounters() {
        var titleLength = $('#retexify-new-meta-title').val().length;
        var descLength = $('#retexify-new-meta-description').val().length;
        
        $('#title-chars').text(titleLength);
        $('#description-chars').text(descLength);
        
        // Farben setzen
        $('#title-chars').css('color', getTitleColor(titleLength));
        $('#description-chars').css('color', getDescColor(descLength));
    }
    
    function getTitleColor(length) {
        if (length > 60) return '#dc3545'; // Rot
        if (length > 54) return '#ffc107'; // Gelb
        if (length > 0) return '#28a745';  // Grün
        return '#6c757d'; // Grau
    }
    
    function getDescColor(length) {
        if (length > 160) return '#dc3545'; // Rot
        if (length > 150) return '#ffc107'; // Gelb
        if (length > 0) return '#28a745';   // Grün
        return '#6c757d'; // Grau
    }
    
    function getSeoTypeLabel(seoType) {
        var labels = {
            'meta_title': 'Meta-Titel',
            'meta_description': 'Meta-Beschreibung',
            'focus_keyword': 'Focus-Keyword'
        };
        return labels[seoType] || seoType;
    }
    
    // ========================================================================
    // 🎧 EVENT LISTENERS SETUP
    // ========================================================================
    
    function setupEventListeners() {
        console.log('🎧 Richte Event-Listener ein...');
        
        // System-Status manuell testen
        $(document).off('click.system-test').on('click.system-test', '#retexify-test-system-badge, .retexify-test-system-btn', function(e) {
            e.preventDefault();
            console.log('🔄 Manueller System-Test gestartet');
            
            var $badge = $(this);
            var originalText = $badge.html();
            $badge.html('🔄 Teste...');
            
            window.retexifyGlobals.systemStatusLoaded = false;
            
        setTimeout(function() {
                loadSystemStatus();
                setTimeout(function() {
                    $badge.html(originalText);
                }, 3000);
            }, 100);
        });
        
        // Research-APIs manuell testen
        $(document).off('click.research-test').on('click.research-test', '#retexify-test-research-badge, .retexify-test-research-btn', function(e) {
            e.preventDefault();
            console.log('🔄 Manueller Research-Test gestartet');
            
            var $badge = $(this);
            var originalText = $badge.html();
            $badge.html('🔄 Teste...');
            
            window.retexifyGlobals.researchStatusLoaded = false;
            
        setTimeout(function() {
                loadResearchStatus();
                setTimeout(function() {
                    $badge.html(originalText);
        }, 3000);
            }, 100);
        });
        
        // Dashboard Refresh
        $(document).off('click.dashboard-refresh').on('click.dashboard-refresh', '#retexify-refresh-stats-badge', function(e) {
            e.preventDefault();
            console.log('🔄 Dashboard Refresh gestartet');
            loadDashboard();
        });
        
        // SEO Content laden
        $(document).off('click.seo-load').on('click.seo-load', '#retexify-load-seo-content', function(e) {
            e.preventDefault();
            loadSeoContent();
        });
        
        // SEO-Navigation
        $(document).off('click.seo-nav').on('click.seo-nav', '#retexify-seo-prev', function(e) {
            e.preventDefault();
            if (window.retexifyGlobals.currentSeoIndex > 0) {
                window.retexifyGlobals.currentSeoIndex--;
                displayCurrentSeoItem();
                updateSeoNavigation();
            }
        });
        
        $(document).off('click.seo-nav').on('click.seo-nav', '#retexify-seo-next', function(e) {
            e.preventDefault();
            if (window.retexifyGlobals.currentSeoIndex < window.retexifyGlobals.totalSeoItems - 1) {
                window.retexifyGlobals.currentSeoIndex++;
                displayCurrentSeoItem();
                updateSeoNavigation();
            }
        });
        
        // Einzelne SEO-Generierung
        $(document).off('click.seo-generate-single').on('click.seo-generate-single', '.retexify-generate-single', function(e) {
            e.preventDefault();
            var seoType = $(this).data('type') || $(this).attr('data-type');
            if (seoType) {
                generateSingleSeo(seoType);
            }
        });
        
        // Alle SEO-Texte generieren
        $(document).off('click.seo-generate-all').on('click.seo-generate-all', '#retexify-generate-all-seo', function(e) {
            e.preventDefault();
            generateAllSeoFixed();
        });
        
        // SEO-Texte speichern
        $(document).off('click.seo-save').on('click.seo-save', '#retexify-save-seo-texts', function(e) {
            e.preventDefault();
            saveSeoTexts();
        });
        
        // Character Counter für Meta-Felder
        $(document).off('input.counter').on('input.counter', '#retexify-new-meta-title, #retexify-new-meta-description', function() {
            updateCharCounters();
        });
        
        // Content anzeigen/verbergen
        $(document).off('click.content-toggle').on('click.content-toggle', '#retexify-show-content', function(e) {
            e.preventDefault();
            var $contentDiv = $('#retexify-full-content');
            var isVisible = $contentDiv.is(':visible');
            
            if (isVisible) {
                $contentDiv.slideUp(300);
                $(this).text('📄 Vollständigen Content anzeigen');
            } else {
                $contentDiv.slideDown(300);
                $(this).text('📄 Content verbergen');
            }
        });
        
        // Export/Import Event-Listener
        $(document).off('click.export-start').on('click.export-start', '#retexify-start-export', function(e) {
            e.preventDefault();
            startCsvExport();
        });
        
        // Export-Vorschau
        $(document).off('click.export-preview').on('click.export-preview', '#retexify-preview-export', function(e) {
            e.preventDefault();
            showExportPreview();
        });
        
        // Verbindung testen (KI)
        $(document).off('click.ai-test-connection').on('click.ai-test-connection', '#retexify-ai-test-connection', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).html('🔄 Teste...');
            executeAjaxCall({
                action: 'retexify_test_api_connection',
                success: function(response) {
                    showNotification(response.data || 'Verbindung erfolgreich!', 'success', 4000);
                    $btn.prop('disabled', false).html('🔗 Verbindung testen');
                },
                error: function(error) {
                    showNotification(error || 'Verbindung fehlgeschlagen!', 'error', 5000);
                    $btn.prop('disabled', false).html('🔗 Verbindung testen');
                }
            });
        });
        
        // KI-Einstellungen speichern (AJAX)
        $(document).off('click.save-ai-settings').on('click.save-ai-settings', '#retexify-save-ai-settings', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $btn.prop('disabled', true).html('💾 Speichert...');
            var provider = $('#ai-provider').val();
            var apiKey = $('#retexify-ai-key').val();
            var model = $('#retexify-ai-model').val();
            var optimizationFocus = $('#retexify-optimization-focus').val();
            var businessContext = $('#retexify-business-context').val();
            var targetAudience = $('#retexify-target-audience').val();
            var brandVoice = $('#retexify-brand-voice').val();
            var targetCantons = $('#retexify-target-cantons').val() || [];
            if (!Array.isArray(targetCantons)) targetCantons = [targetCantons];
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'retexify_save_settings',
                    nonce: retexify_ajax.nonce,
                    api_provider: provider,
                    api_key: apiKey,
                    model: model,
                    optimization_focus: optimizationFocus,
                    business_context: businessContext,
                    target_audience: targetAudience,
                    brand_voice: brandVoice,
                    target_cantons: targetCantons
                },
                success: function(response) {
                    $btn.prop('disabled', false).html('💾 Einstellungen speichern');
                    if (response.success) {
                        showNotification('✅ Einstellungen gespeichert! Seite wird neu geladen...', 'success', 2500);
                        setTimeout(function() {
                            window.location.href = window.location.pathname + '?page=retexify-ai-pro';
                        }, 1200);
                    } else {
                        showNotification('❌ Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.prop('disabled', false).html('💾 Einstellungen speichern');
                    showNotification('❌ Fehler beim Speichern: ' + error, 'error', 5000);
                }
            });
        });
        
        console.log('✅ Event-Listener eingerichtet');
    }
    
    function showExportPreview() {
        console.log('👁️‍🗨️ Zeige Export-Vorschau... (HOTFIX Version)');
        
        // Daten sammeln
        var postTypes = [];
        var contentTypes = [];
        var statusTypes = [];
        
        $('input[name="export_post_types[]"]:checked').each(function() {
            postTypes.push($(this).val());
        });
        $('input[name="export_content[]"]:checked').each(function() {
            contentTypes.push($(this).val());
        });
        $('input[name="export_status[]"]:checked').each(function() {
            statusTypes.push($(this).val());
        });
        
        // Validierung
        if (postTypes.length === 0) {
            showNotification('⚠️ Bitte wählen Sie mindestens einen Post-Typ aus', 'warning', 3000);
            return;
        }
        if (contentTypes.length === 0) {
            showNotification('⚠️ Bitte wählen Sie mindestens einen Content-Typ aus', 'warning', 3000);
            return;
        }
        
        // Vorschau-Container finden
        var $preview = $('#retexify-export-preview');
        if ($preview.length === 0) {
            console.warn('⚠️ Export-Vorschau Container nicht gefunden');
            return;
        }
        
        // Loading-Status anzeigen
        $preview.show().html(`
            <div class="retexify-loading">
                <div class="retexify-spinner"></div>
                <h4>👁️‍🗨️ Lade Export-Vorschau...</h4>
                <p>Analysiere Ihre Inhalte...</p>
            </div>
        `);
        
        // AJAX-Request mit verbesserter Fehlerbehandlung
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 15000,
            data: {
                action: 'retexify_get_export_preview', // ← Dieser Handler war komplett missing!
                nonce: retexify_ajax.nonce,
                post_types: postTypes,
                content: contentTypes,
                status: statusTypes
            },
            success: function(response) {
                console.log('✅ Export-Vorschau Response:', response);
                
                if (response.success && response.data) {
                    displayExportPreviewData(response.data);
                } else {
                    var errorMsg = response.data || 'Unbekannter Fehler bei der Vorschau-Generierung';
                    console.error('❌ Export-Vorschau Fehler:', errorMsg);
                    
                    $preview.html(`
                        <div class="retexify-error">
                            <h4>❌ Vorschau-Fehler</h4>
                            <p>${errorMsg}</p>
                            <button type="button" onclick="showExportPreview()" class="retexify-btn retexify-btn-secondary">
                                🔄 Erneut versuchen
                            </button>
                        </div>
                    `);
                    
                    showNotification('❌ Vorschau-Fehler: ' + errorMsg, 'error', 8000);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX-Fehler bei Export-Vorschau:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState
                });
                
                var errorMessage = 'Verbindungsfehler bei Export-Vorschau';
                
                // Spezifische Fehlermeldungen
                if (status === 'timeout') {
                    errorMessage = 'Zeitüberschreitung - Vorgang dauerte zu lange';
                } else if (status === 'error') {
                    if (xhr.status === 400) {
                        errorMessage = 'Ungültige Anfrage (400) - Möglicherweise fehlen Parameter';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Zugriff verweigert (403) - Berechtigungsfehler';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server-Fehler (500) - Interne Probleme';
                    } else {
                        errorMessage = `HTTP-Fehler ${xhr.status}: ${error}`;
                    }
                } else if (status === 'parsererror') {
                    errorMessage = 'Antwort-Format-Fehler - Server sendete ungültige Daten';
                }
                
                $preview.html(`
                    <div class="retexify-error">
                        <h4>❌ Verbindungsfehler</h4>
                        <p>${errorMessage}</p>
                        <details>
                            <summary>Technische Details</summary>
                            <p><strong>Status:</strong> ${status}</p>
                            <p><strong>HTTP-Code:</strong> ${xhr.status}</p>
                            <p><strong>Fehler:</strong> ${error}</p>
                        </details>
                        <button type="button" onclick="showExportPreview()" class="retexify-btn retexify-btn-secondary">
                            🔄 Erneut versuchen
                        </button>
                    </div>
                `);
                
                showNotification('❌ ' + errorMessage, 'error', 10000);
            }
        });
    }
    
    /**
     * Export-Vorschau-Daten anzeigen - NEUE FUNKTION
     */
    function displayExportPreviewData(data) {
        console.log('📊 Zeige Export-Vorschau-Daten:', data);
        
        var previewHtml = '<div class="retexify-export-preview-content">';
        
        // Header
        previewHtml += '<h4>👁️‍🗨️ Export-Vorschau</h4>';
        
        // Zusammenfassung
        previewHtml += '<div class="retexify-preview-summary">';
        previewHtml += '<div class="retexify-summary-cards">';
        
        // Card 1: Gesamt-Posts
        previewHtml += '<div class="retexify-summary-card">';
        previewHtml += '<div class="retexify-card-icon">📄</div>';
        previewHtml += '<div class="retexify-card-content">';
        previewHtml += '<h5>Gesamt-Posts</h5>';
        previewHtml += '<span class="retexify-card-number">' + (data.total_count || 0) + '</span>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        // Card 2: Spalten
        previewHtml += '<div class="retexify-summary-card">';
        previewHtml += '<div class="retexify-card-icon">📊</div>';
        previewHtml += '<div class="retexify-card-content">';
        previewHtml += '<h5>Spalten</h5>';
        previewHtml += '<span class="retexify-card-number">' + (data.headers ? data.headers.length : 0) + '</span>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        // Card 3: Vorschau-Einträge
        previewHtml += '<div class="retexify-summary-card">';
        previewHtml += '<div class="retexify-card-icon">👁️</div>';
        previewHtml += '<div class="retexify-card-content">';
        previewHtml += '<h5>Vorschau</h5>';
        previewHtml += '<span class="retexify-card-number">' + (data.preview_count || 0) + ' Einträge</span>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        previewHtml += '</div>'; // Ende summary-cards
        previewHtml += '</div>'; // Ende preview-summary
        
        // Spalten-Details
        if (data.headers && data.headers.length > 0) {
            previewHtml += '<div class="retexify-columns-preview">';
            previewHtml += '<h5>📋 Exportierte Spalten:</h5>';
            previewHtml += '<div class="retexify-columns-list">';
            data.headers.forEach(function(header) {
                previewHtml += '<span class="retexify-column-tag">' + escapeHtml(header) + '</span>';
            });
            previewHtml += '</div>';
            previewHtml += '</div>';
        }
        
        // Sample-Daten (falls verfügbar)
        if (data.preview && data.preview.length > 0) {
            previewHtml += '<div class="retexify-sample-preview">';
            previewHtml += '<h5>🔍 Beispiel-Daten (erste ' + data.preview.length + ' Einträge):</h5>';
            previewHtml += '<div class="retexify-table-wrapper">';
            previewHtml += '<table class="retexify-preview-table">';
            
            // Table-Header
            var firstRow = data.preview[0];
            previewHtml += '<thead><tr>';
            Object.keys(firstRow).forEach(function(key) {
                previewHtml += '<th>' + escapeHtml(key) + '</th>';
            });
            previewHtml += '</tr></thead>';
            
            // Table-Body
            previewHtml += '<tbody>';
            data.preview.forEach(function(row) {
                previewHtml += '<tr>';
                Object.values(row).forEach(function(value) {
                    previewHtml += '<td>' + escapeHtml(String(value || '')) + '</td>';
                });
                previewHtml += '</tr>';
            });
            previewHtml += '</tbody>';
            
            previewHtml += '</table>';
            previewHtml += '</div>';
            previewHtml += '</div>';
        }
        
        // Erfolgs-Status
        previewHtml += '<div class="retexify-preview-success">';
        previewHtml += '<p><strong>✅ Export-Vorschau erfolgreich erstellt!</strong></p>';
        previewHtml += '<p>Klicken Sie auf "📤 CSV Export starten" um den Download zu beginnen.</p>';
        previewHtml += '</div>';
        
        previewHtml += '</div>'; // Ende export-preview-content
        
        // Vorschau anzeigen mit Animation
        $('#retexify-export-preview').html(previewHtml);
        
        // Erfolgs-Notification
        showNotification('✅ Export-Vorschau erfolgreich geladen!', 'success', 4000);
    }
    
    // ========================================================================
    // 🛠️ UTILITY FUNKTIONEN
    // ========================================================================
    
    /**
     * HTML-Escaping für sichere Ausgabe
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return String(text);
        }
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    
    function createErrorHTML(title, message) {
        return `
                <div class="retexify-status-error">
                <div class="error-icon">❌</div>
                <div class="error-content">
                    <h4>${title}</h4>
                    <p>${message}</p>
                    <button onclick="location.reload()" class="retexify-btn retexify-btn-secondary">🔄 Seite neu laden</button>
                    </div>
                </div>
        `;
    }
    
    function showNotification(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000;
        
        // Existierende Notifications entfernen
        $('.retexify-notification').remove();
        
        var typeClass = 'notification-' + type;
        var iconMap = {
            'success': '',
            'error': '❌',
            'warning': '⚠️',
            'info': 'ℹ️'
        };
        
        var $notification = $(`
            <div class="retexify-notification ${typeClass}">
                <div class="notification-content">
                    <span class="notification-icon">${iconMap[type] || 'ℹ️'}</span>
                    <span class="notification-message">${message}</span>
        </div>
                <button class="notification-close">&times;</button>
                    </div>
                `);
        
        // CSS für Notification sicherstellen
        if ($('#retexify-notification-styles').length === 0) {
            $('head').append(`
                <style id="retexify-notification-styles">
                    .retexify-notification {
                        position: fixed;
                        top: 32px;
                        right: 20px;
                        padding: 12px 16px;
                        border-radius: 8px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                        z-index: 9999;
                        max-width: 400px;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
                        font-size: 14px;
                        line-height: 1.4;
                        animation: slideInRight 0.3s ease-out;
                        display: flex;
                        align-items: flex-start;
                        justify-content: space-between;
                        gap: 12px;
                    }
                    .notification-content {
                        display: flex;
                        align-items: flex-start;
                        gap: 8px;
                        flex: 1;
                    }
                    .notification-icon {
                        font-size: 16px;
                        flex-shrink: 0;
                        margin-top: 1px;
                    }
                    .notification-message {
                        flex: 1;
                        word-wrap: break-word;
                    }
                    .notification-success { 
                        background: #d4edda; 
                        color: #155724; 
                        border: 1px solid #c3e6cb; 
                    }
                    .notification-error { 
                        background: #f8d7da; 
                        color: #721c24; 
                        border: 1px solid #f5c6cb; 
                    }
                    .notification-info { 
                        background: #d1ecf1; 
                        color: #0c5460; 
                        border: 1px solid #bee5eb; 
                    }
                    .notification-warning { 
                        background: #fff3cd; 
                        color: #856404; 
                        border: 1px solid #ffeaa7; 
                    }
                    .notification-close {
                        background: none;
                        border: none;
                        font-size: 18px;
                        cursor: pointer;
                        opacity: 0.7;
                        color: inherit;
                        padding: 0;
                        margin: 0;
                        flex-shrink: 0;
                    }
                    .notification-close:hover { opacity: 1; }
                    @keyframes slideInRight {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOutRight {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                    .retexify-notification.hiding {
                        animation: slideOutRight 0.3s ease-out forwards;
                    }
                </style>
            `);
        }
        
        $('body').append($notification);
        
        // Close-Button Handler
        $notification.find('.notification-close').on('click', function() {
            $notification.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-Hide
    setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, duration);
        
        console.log('📢 Notification:', type.toUpperCase(), message);
    }
    
    // ========================================================================
    // 🌍 GLOBALE FUNKTIONEN FÜR KOMPATIBILITÄT
    // ========================================================================
    
    // Globale Funktionen für andere Skripte verfügbar machen
    window.retexifyLoadSystemStatus = loadSystemStatus;
    window.retexifyLoadResearchStatus = loadResearchStatus;
    window.retexifyLoadDashboard = loadDashboard;
    window.retexifyShowNotification = showNotification;
    window.retexifyLoadSeoContent = loadSeoContent;
    window.retexifyGenerateSingleSeo = generateSingleSeo;
    window.retexifyGenerateAllSeo = generateAllSeoFixed;
    window.retexifySaveSeoTexts = saveSeoTexts;
    window.retexifyStartCsvExport = startCsvExport;
    window.retexifyExecuteAjaxCall = executeAjaxCall;
    
    console.log('✅ ReTexify AI Pro JavaScript vollständig geladen (Version 4.4.0)');
    
    // Provider-Wechsel: API-Key-Feld aktualisieren
    $(document).on('change', '#ai-provider', function() {
        var provider = $(this).val();
        $('#ai-api-key').val('');
        $.post(ajaxurl, {
            action: 'retexify_get_api_keys',
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success && response.data) {
                var key = response.data[provider] || '';
                $('#ai-api-key').val(key);
            }
        });
    });

    // Einstellungen speichern per AJAX
    $(document).on('submit', '#retexify-ai-settings-form', function(e) {
        e.preventDefault();
        var provider = $('#ai-provider').val();
        var apiKey = $('#ai-api-key').val();
        var formData = $(this).serializeArray();
        formData.push({name: 'api_provider', value: provider});
        formData.push({name: 'api_key', value: apiKey});
        formData.push({name: 'action', value: 'retexify_save_settings'});
        formData.push({name: 'nonce', value: retexify_ajax.nonce});
        $('#retexify-ai-settings-result').html('⏳ Speichern...');
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                $('#retexify-ai-settings-result').html('<span style="color:green">Einstellungen gespeichert!</span>');
            } else {
                $('#retexify-ai-settings-result').html('<span style="color:red">❌ Fehler: '+(response.data||'Unbekannter Fehler')+'</span>');
            }
        }).fail(function(xhr) {
            $('#retexify-ai-settings-result').html('<span style="color:red">❌ AJAX-Fehler: '+xhr.statusText+'</span>');
        });
    });

    // Verbindung testen per AJAX
    $(document).on('click', '#retexify-ai-test-connection', function(e) {
        e.preventDefault();
        var provider = $('#ai-provider').val();
        var apiKey = $('#ai-api-key').val();
        var model = $('#ai-model').val();
        $('#retexify-ai-settings-result').html('⏳ Teste Verbindung...');
        $.post(ajaxurl, {
            action: 'retexify_test_api_connection',
            nonce: retexify_ajax.nonce,
            api_provider: provider,
            api_key: apiKey,
            model: model
        }, function(response) {
            if (response.success) {
                $('#retexify-ai-settings-result').html('<span style="color:green">'+response.data.message+'</span>');
            } else {
                $('#retexify-ai-settings-result').html('<span style="color:red">❌ Fehler: '+(response.data||'Unbekannter Fehler')+'</span>');
            }
        }).fail(function(xhr) {
            $('#retexify-ai-settings-result').html('<span style="color:red">❌ AJAX-Fehler: '+xhr.statusText+'</span>');
        });
    });
}); // Ende jQuery(document).ready

// ============================================================================
// 🐛 DEBUG UND ENTWICKLUNGSHELFER
// ============================================================================

// Globale Debug-Funktion
window.retexifyDebug = function() {
    console.log('🐛 ReTexify Debug Info:', {
        version: '4.4.0',
        globals: window.retexifyGlobals,
        jquery: typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'Nicht verfügbar',
        ajax: typeof retexify_ajax !== 'undefined' ? {
            url: retexify_ajax.ajax_url,
            nonce: retexify_ajax.nonce ? retexify_ajax.nonce.substring(0, 8) + '...' : 'FEHLT',
            debug: retexify_ajax.debug
        } : 'Nicht verfügbar',
        containers: {
            system: jQuery('#retexify-system-status').length,
            research: jQuery('#retexify-research-engine-status').length,
            dashboard: jQuery('#retexify-dashboard-content').length,
            seoOptimizer: jQuery('#retexify-load-seo-content').length,
            exportImport: jQuery('#retexify-csv-upload-area').length
        },
        seoData: {
            total: window.retexifyGlobals.totalSeoItems,
            current: window.retexifyGlobals.currentSeoIndex,
            hasData: window.retexifyGlobals.seoData.length > 0,
            currentPostId: window.retexifyGlobals.currentPostId
        }
    });
};

// AJAX-Test für Debugging
window.retexifyTestAjax = function(action, data) {
    action = action || 'retexify_test_system';
    data = data || {};
    
    console.log('🧪 Teste AJAX-Verbindung...', action, data);
    
    if (typeof window.retexifyExecuteAjaxCall === 'function') {
        window.retexifyExecuteAjaxCall({
            action: action,
            data: data,
            success: function(response) {
                console.log('AJAX-Test erfolgreich:', response);
            },
            error: function(error) {
                console.error('❌ AJAX-Test fehlgeschlagen:', error);
            }
        });
    } else {
        console.error('❌ retexifyExecuteAjaxCall-Funktion nicht verfügbar');
    }
};

// Fallback für jQuery-Probleme
if (typeof jQuery === 'undefined') {
    console.error('❌ jQuery nicht verfügbar - ReTexify AI Pro benötigt jQuery');
    if (typeof retexify_ajax !== 'undefined') {
        document.addEventListener('DOMContentLoaded', function() {
            var notice = document.createElement('div');
            notice.style.cssText = 'position:fixed;top:32px;right:20px;padding:15px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:6px;z-index:9999;';
            notice.innerHTML = '❌ jQuery nicht verfügbar - ReTexify AI benötigt jQuery';
            document.body.appendChild(notice);
        });
    }
} else {
    console.log('jQuery verfügbar:', jQuery.fn.jquery);
}

console.log('📄 ReTexify AI Pro JavaScript-Datei vollständig geladen (Version 4.4.0)');