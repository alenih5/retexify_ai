/**
 * ReTexify AI Pro - KOMPLETTE Admin JavaScript
 * Version: 4.2.1 - Exakte HTML-IDs und AJAX-Actions aus dem Plugin
 * 
 * ALLE FEATURES:
 * ✅ Automatisches Laden von System-Status und Research-Status
 * ✅ Korrekte SEO-Optimizer Funktionen mit echten HTML-IDs
 * ✅ Dashboard mit echten AJAX-Actions
 * ✅ Alle Button-Handler und Event-Listener
 * ✅ Character-Counter und UI-Features
 * ✅ Performance-Monitoring und Debug-Funktionen
 */

// ============================================================================
// 🌍 GLOBALE VARIABLEN (außerhalb jQuery für Persistenz)
// ============================================================================

window.retexifyGlobals = window.retexifyGlobals || {
    systemStatusLoaded: false,
    researchStatusLoaded: false,
    seoData: [],
    currentSeoIndex: 0,
    totalSeoItems: 0,
    isInitialized: false,
    performanceTimers: {}
};

// ============================================================================
// 🚀 HAUPT-JAVASCRIPT (jQuery-Wrapper)
// ============================================================================

(function($) {
    'use strict';
    
    // Warten bis DOM vollständig geladen ist
    $(document).ready(function() {
        console.log('🚀 ReTexify AI Pro JavaScript startet (Version 4.2.1)...');
        console.log('📊 AJAX URL:', retexify_ajax.ajax_url);
        console.log('🔑 Nonce:', retexify_ajax.nonce);
        console.log('🌍 Globale Variablen:', window.retexifyGlobals);
        
        // Initialisierung nur einmal ausführen
        if (!window.retexifyGlobals.isInitialized) {
            initializeReTexify();
            window.retexifyGlobals.isInitialized = true;
        }
        
        // ========================================================================
        // 🎯 HAUPT-INITIALISIERUNG
        // ========================================================================
        
        function initializeReTexify() {
            console.log('🔄 Initialisiere ReTexify AI Pro...');
            
            // Tab-System initialisieren
            initializeTabs();
            
            // Dashboard laden falls sichtbar
            if ($('#retexify-dashboard-content').length > 0) {
                loadDashboard();
            }
            
            // Event-Listener einrichten
            setupEventListeners();
            
            // SEO-Optimizer initialisieren falls sichtbar
            if ($('#retexify-load-seo-content').length > 0) {
                initializeSeoOptimizer();
            }
            
            console.log('✅ ReTexify AI Pro vollständig initialisiert');
            showNotification('🚀 ReTexify AI bereit', 'success', 2000);
        }
        
        // ========================================================================
        // 🎯 TAB-SYSTEM
        // ========================================================================
        
        function initializeTabs() {
            console.log('🔄 Initialisiere Tab-System...');
            
            // Event-Delegation für Tab-Clicks
            $(document).off('click.retexify', '.retexify-tab-btn').on('click.retexify', '.retexify-tab-btn', function(e) {
                e.preventDefault();
                
                var tabId = $(this).data('tab');
                if (!tabId) {
                    console.warn('⚠️ Keine Tab-ID gefunden');
                    return;
                }
                
                console.log('🔄 Tab-Wechsel zu:', tabId);
                
                // Alle Tabs deaktivieren
                $('.retexify-tab-btn').removeClass('active');
                $('.retexify-tab-content').removeClass('active');
                
                // Aktuellen Tab aktivieren
                $(this).addClass('active');
                $('#tab-' + tabId).addClass('active');
                
                // Tab-spezifische Aktionen mit Delay für bessere UX
                setTimeout(function() {
                    handleTabSwitch(tabId);
                }, 100);
            });
        }
        
        function handleTabSwitch(tabId) {
            console.log('🎯 Behandle Tab-Wechsel:', tabId);
            
            switch(tabId) {
                case 'system':
                    console.log('📊 System-Tab aktiviert');
                    
                    // System-Status automatisch laden
                    if (!window.retexifyGlobals.systemStatusLoaded) {
                        console.log('🔄 Lade System-Status automatisch...');
                        setTimeout(loadSystemStatus, 200);
                    } else {
                        console.log('📊 System-Status bereits geladen');
                    }
                    
                    // Research-Status automatisch laden (mit Verzögerung)
                    if (!window.retexifyGlobals.researchStatusLoaded) {
                        console.log('🔄 Lade Research-Status automatisch...');
                        setTimeout(loadResearchStatus, 2000); // 2s nach System-Status
                    } else {
                        console.log('🧠 Research-Status bereits geladen');
                    }
                    break;
                    
                case 'dashboard':
                    if ($('#retexify-dashboard-content').length > 0) {
                        loadDashboard();
                    }
                    break;
                    
                case 'export-import':
                    if (typeof loadExportImportTab === 'function') {
                        loadExportImportTab();
                    }
                    break;
                    
                case 'seo-optimizer':
                    initializeSeoOptimizer();
                    break;
                    
                case 'ai-settings':
                    // AI-Settings spezifische Initialisierung
                    console.log('🤖 KI-Einstellungen-Tab aktiviert');
                    break;
            }
        }
        
        // ========================================================================
        // 📊 DASHBOARD FUNKTIONEN
        // ========================================================================
        
        function loadDashboard() {
            console.log('📊 Lade Dashboard...');
            startPerformanceTimer('dashboard');
            
            var $container = $('#retexify-dashboard-content');
            if ($container.length === 0) {
                console.warn('⚠️ Dashboard-Container nicht gefunden');
                return;
            }
            
            $container.html('<div class="retexify-loading">📊 Lade Dashboard...</div>');
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_get_stats',
                    nonce: retexify_ajax.nonce
                },
                timeout: 10000,
                success: function(response) {
                    endPerformanceTimer('dashboard');
                    console.log('📊 Dashboard Response:', response);
                    
                    if (response && response.success) {
                        $container.html(response.data);
                        showNotification('✅ Dashboard geladen', 'success', 2000);
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        $container.html('<div class="retexify-error">❌ Dashboard-Fehler: ' + errorMsg + '</div>');
                        showNotification('❌ Dashboard-Fehler', 'error', 3000);
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('dashboard');
                    console.error('❌ Dashboard AJAX Fehler:', error);
                    $container.html('<div class="retexify-error">❌ Verbindungsfehler beim Dashboard laden</div>');
                    showNotification('❌ Dashboard-Verbindungsfehler', 'error', 3000);
                }
            });
        }
        
        // ========================================================================
        // 🔧 SYSTEM-STATUS FUNKTIONEN
        // ========================================================================
        
        function loadSystemStatus() {
            console.log('🔍 loadSystemStatus() aufgerufen');
            startPerformanceTimer('system');
            
            // Prüfen ob bereits geladen
            if (window.retexifyGlobals.systemStatusLoaded) {
                console.log('📊 System-Status bereits geladen');
                return;
            }
            
            console.log('🔄 Starte System-Status-Laden...');
            
            // Container suchen - exakte ID aus dem Plugin
            var $container = $('#retexify-system-status');
            if ($container.length === 0) {
                console.error('❌ System-Status Container nicht gefunden');
                // Alternative Container versuchen
                $container = $('.retexify-system-status, [id*="system-status"]');
                if ($container.length === 0) {
                    console.error('❌ Kein System-Status Container gefunden');
                    return;
                }
            }
            
            console.log('✅ Container gefunden:', $container.length);
            
            // Status setzen
            window.retexifyGlobals.systemStatusLoaded = true;
            
            // Loading-Anzeige
            var loadingHTML = `
                <div class="retexify-loading-status">
                    <div class="loading-spinner">🔄</div>
                    <div class="loading-text">System wird getestet...</div>
                    <div class="loading-detail">Prüfe WordPress, PHP, APIs...</div>
                </div>
            `;
            $container.html(loadingHTML);
            
            console.log('🔄 AJAX-Request startet...');
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_test_system',
                    nonce: retexify_ajax.nonce
                },
                timeout: 15000, // 15 Sekunden
                success: function(response) {
                    endPerformanceTimer('system');
                    console.log('📊 System-Status Response:', response);
                    
                    if (response && response.success) {
                        $container.html(response.data);
                        showNotification('✅ System-Status geladen', 'success', 2000);
                        console.log('✅ System-Status erfolgreich geladen');
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        $container.html(createErrorHTML('System-Test fehlgeschlagen', errorMsg));
                        showNotification('❌ System-Test fehlgeschlagen', 'error', 3000);
                        console.error('❌ System-Status Fehler:', errorMsg);
                        
                        // Reset für Retry
                        window.retexifyGlobals.systemStatusLoaded = false;
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('system');
                    console.error('❌ System-Status AJAX Fehler:', {
                        status: status,
                        error: error,
                        xhr: xhr
                    });
                    
                    // Reset für Retry
                    window.retexifyGlobals.systemStatusLoaded = false;
                    
                    var errorHTML = createErrorHTML(
                        'Verbindungsfehler', 
                        'System-Status konnte nicht geladen werden: ' + error + ' (Status: ' + status + ')'
                    );
                    $container.html(errorHTML);
                    showNotification('❌ System-Verbindungsfehler', 'error', 5000);
                }
            });
        }
        
        // ========================================================================
        // 🧠 RESEARCH-STATUS FUNKTIONEN
        // ========================================================================
        
        function loadResearchStatus() {
            console.log('🧠 loadResearchStatus() aufgerufen');
            startPerformanceTimer('research');
            
            // Prüfen ob bereits geladen
            if (window.retexifyGlobals.researchStatusLoaded) {
                console.log('🧠 Research-Status bereits geladen');
                return;
            }
            
            console.log('🔄 Starte Research-Status-Laden...');
            
            // Container suchen (mehrere mögliche IDs)
            var $container = $('#retexify-research-engine-status, #research-engine-status-content, .retexify-research-status');
            if ($container.length === 0) {
                console.warn('⚠️ Research-Status Container nicht gefunden');
                return;
            }
            
            console.log('✅ Research Container gefunden:', $container.length);
            
            // Status setzen
            window.retexifyGlobals.researchStatusLoaded = true;
            
            // Loading-Anzeige
            var loadingHTML = `
                <div class="retexify-loading-status">
                    <div class="loading-spinner">🧠</div>
                    <div class="loading-text">Research-Engine wird getestet...</div>
                    <div class="loading-detail">Teste Google, Wikipedia, OpenStreetMap...</div>
                </div>
            `;
            $container.html(loadingHTML);
            
            console.log('🔄 Research AJAX-Request startet...');
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_test_research_apis',
                    nonce: retexify_ajax.nonce
                },
                timeout: 20000, // 20 Sekunden für externe APIs
                success: function(response) {
                    endPerformanceTimer('research');
                    console.log('🧠 Research-Status Response:', response);
                    
                    if (response && response.success) {
                        $container.html(response.data);
                        showNotification('✅ Research-Engine getestet', 'success', 2000);
                        console.log('✅ Research-Status erfolgreich geladen');
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        $container.html(createErrorHTML('Research-Test fehlgeschlagen', errorMsg));
                        showNotification('❌ Research-Test fehlgeschlagen', 'error', 3000);
                        console.error('❌ Research-Status Fehler:', errorMsg);
                        
                        // Reset für Retry
                        window.retexifyGlobals.researchStatusLoaded = false;
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('research');
                    console.error('❌ Research-Status AJAX Fehler:', {
                        status: status,
                        error: error,
                        xhr: xhr
                    });
                    
                    // Reset für Retry
                    window.retexifyGlobals.researchStatusLoaded = false;
                    
                    var errorHTML = createErrorHTML(
                        'Verbindungsfehler', 
                        'Research-Status konnte nicht geladen werden: ' + error + ' (Status: ' + status + ')'
                    );
                    $container.html(errorHTML);
                    showNotification('❌ Research-Verbindungsfehler', 'error', 5000);
                }
            });
        }
        
        // ========================================================================
        // 🚀 SEO-OPTIMIZER FUNKTIONEN
        // ========================================================================
        
        function initializeSeoOptimizer() {
            console.log('🚀 Initialisiere SEO-Optimizer...');
            
            // Character-Counter für Meta-Felder initialisieren
            updateCharCounters();
            
            // Post-Type Change Handler (korrigierte ID)
            $(document).off('change.seo', '#seo-post-type').on('change.seo', '#seo-post-type', function() {
                var $btn = $('#retexify-load-seo-content');
                $btn.prop('disabled', false);
                $('#retexify-seo-content-list').hide();
                console.log('📝 Post-Typ geändert zu:', $(this).val());
            });
        }
        
        // SEO Content laden (korrigierte Funktion basierend auf Plugin-Struktur)
        function loadSeoContent() {
            var $btn = $('#retexify-load-seo-content');
            var originalText = $btn.html();
            var postType = $('#seo-post-type').val() || 'page';
            
            console.log('📄 Lade SEO Content für Post-Typ:', postType);
            startPerformanceTimer('seoload');
            
            $btn.html('🔄 Lade Content...').prop('disabled', true);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_load_content',
                    nonce: retexify_ajax.nonce,
                    post_type: postType
                },
                timeout: 15000,
                success: function(response) {
                    endPerformanceTimer('seoload');
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.log('📄 SEO Content Response:', response);
                    
                    if (response && response.success) {
                        var data = response.data;
                        
                        // SEO-Daten in globale Variable speichern
                        window.retexifyGlobals.seoData = data.posts || data.pages || [];
                        window.retexifyGlobals.currentSeoIndex = 0;
                        window.retexifyGlobals.totalSeoItems = window.retexifyGlobals.seoData.length;
                        
                        if (window.retexifyGlobals.totalSeoItems > 0) {
                            // Content-Liste anzeigen
                            $('#retexify-seo-content-list').show();
                            
                            // Ersten Eintrag anzeigen
                            displayCurrentSeoItem();
                            
                            // Navigation aktualisieren
                            updateSeoNavigation();
                            
                            showNotification('📄 ' + window.retexifyGlobals.totalSeoItems + ' Einträge geladen', 'success', 2000);
                        } else {
                            showNotification('⚠️ Keine Einträge gefunden', 'warning', 3000);
                        }
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        showNotification('❌ Content-Fehler: ' + errorMsg, 'error', 3000);
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('seoload');
                    $btn.html(originalText).prop('disabled', false);
                    console.error('❌ Content-Load Fehler:', error);
                    showNotification('❌ Verbindungsfehler beim Content laden', 'error', 3000);
                }
            });
        }
        
        // Aktuellen SEO-Eintrag anzeigen
        function displayCurrentSeoItem() {
            if (!window.retexifyGlobals.seoData || window.retexifyGlobals.seoData.length === 0) {
                return;
            }
            
            var currentItem = window.retexifyGlobals.seoData[window.retexifyGlobals.currentSeoIndex];
            if (!currentItem) {
                return;
            }
            
            console.log('📄 Zeige SEO-Eintrag:', currentItem);
            
            // Page-Informationen anzeigen
            $('#retexify-current-page-title').text(currentItem.title || 'Unbekannter Titel');
            $('#retexify-page-info').text('ID: ' + currentItem.id + ' | Status: ' + (currentItem.status || 'publish'));
            
            // Links setzen
            if (currentItem.permalink) {
                $('#retexify-page-url').attr('href', currentItem.permalink);
            }
            if (currentItem.edit_link) {
                $('#retexify-edit-page').attr('href', currentItem.edit_link);
            }
            
            // Content anzeigen (falls verfügbar)
            if (currentItem.content) {
                $('#retexify-content-text').text(currentItem.content);
                updateContentStats(currentItem.content);
            }
            
            // Aktuelle SEO-Daten anzeigen
            $('#retexify-current-meta-title').text(currentItem.meta_title || 'Nicht gesetzt');
            $('#retexify-current-meta-description').text(currentItem.meta_description || 'Nicht gesetzt');
            $('#retexify-current-focus-keyword').text(currentItem.focus_keyword || 'Nicht gesetzt');
            
            // Generierung-Buttons aktivieren
            $('.retexify-generate-single, #retexify-generate-all-seo').prop('disabled', false);
        }
        
        // SEO-Navigation aktualisieren
        function updateSeoNavigation() {
            var current = window.retexifyGlobals.currentSeoIndex + 1;
            var total = window.retexifyGlobals.totalSeoItems;
            
            $('#retexify-seo-counter').text(current + ' / ' + total);
            
            $('#retexify-seo-prev').prop('disabled', window.retexifyGlobals.currentSeoIndex === 0);
            $('#retexify-seo-next').prop('disabled', window.retexifyGlobals.currentSeoIndex >= total - 1);
        }
        
        // Content-Statistiken aktualisieren
        function updateContentStats(content) {
            if (!content) return;
            
            var wordCount = content.split(/\s+/).length;
            var charCount = content.length;
            
            $('#retexify-word-count').text(wordCount + ' Wörter');
            $('#retexify-char-count').text(charCount + ' Zeichen');
        }
        
        // Einzelnes SEO-Element generieren
        function generateSingleSeo(seoType) {
            var currentItem = window.retexifyGlobals.seoData[window.retexifyGlobals.currentSeoIndex];
            if (!currentItem) {
                showNotification('❌ Kein Eintrag ausgewählt', 'error', 3000);
                return;
            }
            
            console.log('🎯 Generiere einzelnes SEO:', seoType, 'für:', currentItem.title);
            startPerformanceTimer('generate_single');
            
            var $btn = $('.retexify-generate-single[data-type="' + seoType + '"]');
            var originalText = $btn.html();
            $btn.html('🔄 Generiere...').prop('disabled', true);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_generate_single_seo',
                    nonce: retexify_ajax.nonce,
                    post_id: currentItem.id,
                    seo_type: seoType,
                    include_cantons: $('#retexify-include-cantons').is(':checked'),
                    premium_tone: $('#retexify-premium-tone').is(':checked')
                },
                timeout: 30000, // 30 Sekunden
                success: function(response) {
                    endPerformanceTimer('generate_single');
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.log('🎯 Single SEO Response:', response);
                    
                    if (response && response.success) {
                        var content = response.data.content || response.data;
                        var fieldId = '#retexify-new-' + seoType.replace('_', '-');
                        
                        $(fieldId).val(content);
                        updateCharCounters();
                        
                        // Speichern-Button aktivieren
                        $('#retexify-save-seo').prop('disabled', false);
                        
                        showNotification('✅ ' + getSeoTypeLabel(seoType) + ' generiert', 'success', 2000);
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        showNotification('❌ Fehler beim Generieren: ' + errorMsg, 'error', 4000);
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('generate_single');
                    $btn.html(originalText).prop('disabled', false);
                    console.error('❌ Single SEO AJAX Fehler:', error);
                    showNotification('❌ Verbindungsfehler beim Generieren', 'error', 4000);
                }
            });
        }
        
        // Alle SEO-Texte generieren
        function generateAllSeo() {
            var currentItem = window.retexifyGlobals.seoData[window.retexifyGlobals.currentSeoIndex];
            if (!currentItem) {
                showNotification('❌ Kein Eintrag ausgewählt', 'error', 3000);
                return;
            }
            
            console.log('🚀 Generiere alle SEO-Texte für:', currentItem.title);
            startPerformanceTimer('generate_all');
            
            var $btn = $('#retexify-generate-all-seo');
            var originalText = $btn.html();
            $btn.html('🔄 Generiere alle...').prop('disabled', true);
            
            // Progress-Anzeige (falls intelligent-progress.js geladen ist)
            if (typeof ReTexifyIntelligent !== 'undefined' && ReTexifyIntelligent.ProgressManager) {
                ReTexifyIntelligent.ProgressManager.startProgress();
            }
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_generate_complete_seo',
                    nonce: retexify_ajax.nonce,
                    post_id: currentItem.id,
                    include_cantons: $('#retexify-include-cantons').is(':checked'),
                    premium_tone: $('#retexify-premium-tone').is(':checked')
                },
                timeout: 60000, // 60 Sekunden
                success: function(response) {
                    endPerformanceTimer('generate_all');
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.log('🚀 Complete SEO Response:', response);
                    
                    if (response && response.success) {
                        var data = response.data;
                        
                        // Alle generierten Felder füllen
                        if (data.meta_title) $('#retexify-new-meta-title').val(data.meta_title);
                        if (data.meta_description) $('#retexify-new-meta-description').val(data.meta_description);
                        if (data.focus_keyword) $('#retexify-new-focus-keyword').val(data.focus_keyword);
                        
                        updateCharCounters();
                        
                        // Speichern-Button aktivieren
                        $('#retexify-save-seo').prop('disabled', false);
                        
                        // Progress beenden (falls verfügbar)
                        if (typeof ReTexifyIntelligent !== 'undefined' && ReTexifyIntelligent.ProgressManager) {
                            ReTexifyIntelligent.ProgressManager.completeProgress();
                        }
                        
                        showNotification('✅ Alle SEO-Texte erfolgreich generiert', 'success', 3000);
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        
                        // Progress mit Fehler beenden
                        if (typeof ReTexifyIntelligent !== 'undefined' && ReTexifyIntelligent.ProgressManager) {
                            ReTexifyIntelligent.ProgressManager.errorProgress(errorMsg);
                        }
                        
                        showNotification('❌ Fehler beim Generieren: ' + errorMsg, 'error', 4000);
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('generate_all');
                    $btn.html(originalText).prop('disabled', false);
                    
                    // Progress mit Fehler beenden
                    if (typeof ReTexifyIntelligent !== 'undefined' && ReTexifyIntelligent.ProgressManager) {
                        ReTexifyIntelligent.ProgressManager.errorProgress('Verbindungsfehler');
                    }
                    
                    console.error('❌ Complete SEO AJAX Fehler:', error);
                    showNotification('❌ Verbindungsfehler beim Generieren', 'error', 4000);
                }
            });
        }
        
        // SEO-Daten speichern
        function saveSeoData() {
            var currentItem = window.retexifyGlobals.seoData[window.retexifyGlobals.currentSeoIndex];
            if (!currentItem) {
                showNotification('❌ Kein Eintrag ausgewählt', 'error', 3000);
                return;
            }
            
            var metaTitle = $('#retexify-new-meta-title').val();
            var metaDescription = $('#retexify-new-meta-description').val();
            var focusKeyword = $('#retexify-new-focus-keyword').val();
            
            if (!metaTitle && !metaDescription && !focusKeyword) {
                showNotification('❌ Keine Daten zum Speichern vorhanden', 'error', 3000);
                return;
            }
            
            console.log('💾 Speichere SEO-Daten für:', currentItem.title);
            startPerformanceTimer('save');
            
            var $btn = $('#retexify-save-seo');
            var originalText = $btn.html();
            $btn.html('💾 Speichere...').prop('disabled', true);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_save_seo_data',
                    nonce: retexify_ajax.nonce,
                    post_id: currentItem.id,
                    meta_title: metaTitle,
                    meta_description: metaDescription,
                    focus_keyword: focusKeyword
                },
                timeout: 15000,
                success: function(response) {
                    endPerformanceTimer('save');
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.log('💾 Save Response:', response);
                    
                    if (response && response.success) {
                        var savedCount = response.data.saved_count || 0;
                        showNotification('✅ ' + savedCount + ' SEO-Elemente gespeichert', 'success', 3000);
                        
                        // Aktuellen Eintrag in globalen Daten aktualisieren
                        if (metaTitle) currentItem.meta_title = metaTitle;
                        if (metaDescription) currentItem.meta_description = metaDescription;
                        if (focusKeyword) currentItem.focus_keyword = focusKeyword;
                        
                        // Aktuelle Anzeige aktualisieren
                        displayCurrentSeoItem();
                        
                        // Felder leeren
                        $('#retexify-new-meta-title, #retexify-new-meta-description, #retexify-new-focus-keyword').val('');
                        updateCharCounters();
                    } else {
                        var errorMsg = response && response.data ? response.data : 'Unbekannter Fehler';
                        showNotification('❌ Speicher-Fehler: ' + errorMsg, 'error', 4000);
                    }
                },
                error: function(xhr, status, error) {
                    endPerformanceTimer('save');
                    $btn.html(originalText).prop('disabled', false);
                    console.error('❌ Save AJAX Fehler:', error);
                    showNotification('❌ Verbindungsfehler beim Speichern', 'error', 4000);
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
            $(document).off('click.system-test', '#retexify-test-system-badge, #test-system-badge, .retexify-test-system-btn').on('click.system-test', '#retexify-test-system-badge, #test-system-badge, .retexify-test-system-btn', function(e) {
                e.preventDefault();
                console.log('🔄 Manueller System-Test gestartet');
                
                var $badge = $(this);
                var originalText = $badge.html();
                $badge.html('🔄 Teste...');
                
                // Status zurücksetzen für neuen Test
                window.retexifyGlobals.systemStatusLoaded = false;
                
                // Test starten
                setTimeout(function() {
                    loadSystemStatus();
                    
                    // Badge-Text zurücksetzen
                    setTimeout(function() {
                        $badge.html(originalText);
                    }, 3000);
                }, 100);
            });
            
            // Research-APIs manuell testen  
            $(document).off('click.research-test', '#retexify-test-research-badge, #test-research-apis, .retexify-test-research-btn').on('click.research-test', '#retexify-test-research-badge, #test-research-apis, .retexify-test-research-btn', function(e) {
                e.preventDefault();
                console.log('🔄 Manueller Research-Test gestartet');
                
                var $badge = $(this);
                var originalText = $badge.html();
                $badge.html('🔄 Teste...');
                
                // Status zurücksetzen für neuen Test
                window.retexifyGlobals.researchStatusLoaded = false;
                
                // Test starten
                setTimeout(function() {
                    loadResearchStatus();
                    
                    // Badge-Text zurücksetzen
                    setTimeout(function() {
                        $badge.html(originalText);
                    }, 3000);
                }, 100);
            });
            
            // Dashboard Refresh
            $(document).off('click.dashboard-refresh', '#retexify-refresh-stats-badge').on('click.dashboard-refresh', '#retexify-refresh-stats-badge', function(e) {
                e.preventDefault();
                console.log('🔄 Dashboard Refresh gestartet');
                
                var $badge = $(this);
                var originalText = $badge.html();
                $badge.html('🔄 Lädt...');
                
                loadDashboard();
                
                setTimeout(function() {
                    $badge.html(originalText);
                }, 2000);
            });
            
            // SEO-Optimizer Event-Listener
            
            // SEO Content laden
            $(document).off('click.seo-load', '#retexify-load-seo-content').on('click.seo-load', '#retexify-load-seo-content', function(e) {
                e.preventDefault();
                loadSeoContent();
            });
            
            // Vollständigen Content anzeigen/verbergen
            $(document).off('click.content-toggle', '#retexify-show-content').on('click.content-toggle', '#retexify-show-content', function(e) {
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
            
            // SEO-Navigation
            $(document).off('click.seo-nav', '#retexify-seo-prev').on('click.seo-nav', '#retexify-seo-prev', function(e) {
                e.preventDefault();
                if (window.retexifyGlobals.currentSeoIndex > 0) {
                    window.retexifyGlobals.currentSeoIndex--;
                    displayCurrentSeoItem();
                    updateSeoNavigation();
                }
            });
            
            $(document).off('click.seo-nav', '#retexify-seo-next').on('click.seo-nav', '#retexify-seo-next', function(e) {
                e.preventDefault();
                if (window.retexifyGlobals.currentSeoIndex < window.retexifyGlobals.totalSeoItems - 1) {
                    window.retexifyGlobals.currentSeoIndex++;
                    displayCurrentSeoItem();
                    updateSeoNavigation();
                }
            });
            
            // Einzelne SEO-Generierung
            $(document).off('click.seo-single', '.retexify-generate-single').on('click.seo-single', '.retexify-generate-single', function(e) {
                e.preventDefault();
                var seoType = $(this).data('type');
                if (seoType) {
                    generateSingleSeo(seoType);
                }
            });
            
            // Alle SEO-Texte generieren
            $(document).off('click.seo-all', '#retexify-generate-all-seo').on('click.seo-all', '#retexify-generate-all-seo', function(e) {
                e.preventDefault();
                generateAllSeo();
            });
            
            // SEO-Daten speichern
            $(document).off('click.seo-save', '#retexify-save-seo').on('click.seo-save', '#retexify-save-seo', function(e) {
                e.preventDefault();
                saveSeoData();
            });
            
            // Character Counter für Meta-Felder
            $(document).off('input.counter', '#retexify-new-meta-title, #retexify-new-meta-description').on('input.counter', '#retexify-new-meta-title, #retexify-new-meta-description', function() {
                updateCharCounters();
            });
            
            console.log('✅ Event-Listener eingerichtet');
        }
        
        // ========================================================================
        // 🛠️ UTILITY FUNKTIONEN
        // ========================================================================
        
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
            
            // Existierende Notification entfernen
            $('.retexify-notification').remove();
            
            var typeClass = 'notification-' + type;
            var $notification = $(`
                <div class="retexify-notification ${typeClass}">
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            // CSS für Notification falls nicht vorhanden
            if ($('#retexify-notification-styles').length === 0) {
                $('head').append(`
                    <style id="retexify-notification-styles">
                        .retexify-notification {
                            position: fixed;
                            top: 32px;
                            right: 20px;
                            padding: 15px 20px;
                            border-radius: 6px;
                            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                            z-index: 9999;
                            max-width: 350px;
                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif;
                            font-size: 14px;
                            line-height: 1.4;
                            animation: slideInRight 0.3s ease-out;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
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
                            margin-left: 10px;
                            opacity: 0.7;
                            color: inherit;
                        }
                        .notification-close:hover { opacity: 1; }
                        @keyframes slideInRight {
                            from { transform: translateX(100%); opacity: 0; }
                            to { transform: translateX(0); opacity: 1; }
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
            
            console.log('📢 Notification:', message);
        }
        
        // ========================================================================
        // ⏱️ PERFORMANCE MONITORING
        // ========================================================================
        
        function startPerformanceTimer(operation) {
            window.retexifyGlobals.performanceTimers[operation] = performance.now();
        }
        
        function endPerformanceTimer(operation) {
            if (window.retexifyGlobals.performanceTimers[operation]) {
                var duration = (performance.now() - window.retexifyGlobals.performanceTimers[operation]) / 1000;
                console.log('⏱️ Performance [' + operation + ']:', duration.toFixed(2) + 's');
                delete window.retexifyGlobals.performanceTimers[operation];
                return duration;
            }
            return 0;
        }
        
        // ========================================================================
        // 🌍 GLOBALE FUNKTIONEN (für Kompatibilität und Debug)
        // ========================================================================
        
        // Globale Funktionen für andere Skripte verfügbar machen
        window.retexifyLoadSystemStatus = loadSystemStatus;
        window.retexifyLoadResearchStatus = loadResearchStatus;
        window.retexifyLoadDashboard = loadDashboard;
        window.retexifyShowNotification = showNotification;
        window.retexifyLoadSeoContent = loadSeoContent;
        window.retexifyGenerateSingleSeo = generateSingleSeo;
        window.retexifyGenerateAllSeo = generateAllSeo;
        window.retexifySaveSeoData = saveSeoData;
        window.retexifyDisplayCurrentSeoItem = displayCurrentSeoItem;
        
        console.log('✅ ReTexify AI Pro JavaScript vollständig geladen (Version 4.2.1)');
        
    }); // Ende document.ready
    
})(jQuery); // Ende jQuery Wrapper

// ============================================================================
// 🐛 DEBUG UND ENTWICKLUNGSHELFER
// ============================================================================

// Globale Debug-Funktion
window.retexifyDebug = function() {
    console.log('🐛 ReTexify Debug Info:', {
        version: '4.2.1',
        globals: window.retexifyGlobals,
        jquery: typeof jQuery !== 'undefined',
        ajax: typeof retexify_ajax !== 'undefined' ? retexify_ajax : 'undefined',
        containers: {
            system: $('#retexify-system-status').length,
            research: $('#retexify-research-engine-status, #research-engine-status-content').length,
            dashboard: $('#retexify-dashboard-content').length,
            seoOptimizer: $('#retexify-load-seo-content').length,
            seoContentList: $('#retexify-seo-content-list').length
        },
        eventListeners: {
            tabs: $('.retexify-tab-btn').length,
            buttons: $('[id*="retexify-"]').length
        },
        seoData: {
            total: window.retexifyGlobals.totalSeoItems,
            current: window.retexifyGlobals.currentSeoIndex,
            hasData: window.retexifyGlobals.seoData.length > 0
        }
    });
};

// Performance-Test-Funktion
window.retexifyPerformanceTest = function() {
    console.log('🚀 Performance Test startet...');
    window.retexifyGlobals.performanceTimers['test'] = performance.now();
    
    setTimeout(function() {
        var duration = (performance.now() - window.retexifyGlobals.performanceTimers['test']) / 1000;
        console.log('⏱️ Performance Test abgeschlossen in', duration.toFixed(2), 'Sekunden');
        delete window.retexifyGlobals.performanceTimers['test'];
    }, 100);
};

// Status-Reset-Funktion für Debugging
window.retexifyResetStatus = function() {
    window.retexifyGlobals.systemStatusLoaded = false;
    window.retexifyGlobals.researchStatusLoaded = false;
    console.log('🔄 Status zurückgesetzt');
};

// SEO-Daten-Reset für Debugging
window.retexifyResetSeoData = function() {
    window.retexifyGlobals.seoData = [];
    window.retexifyGlobals.currentSeoIndex = 0;
    window.retexifyGlobals.totalSeoItems = 0;
    $('#retexify-seo-content-list').hide();
    console.log('🔄 SEO-Daten zurückgesetzt');
};

// Fallback für alte Browser oder jQuery-Probleme
if (typeof jQuery === 'undefined') {
    console.error('❌ jQuery nicht verfügbar - ReTexify AI Pro benötigt jQuery');
}

console.log('📄 ReTexify AI Pro JavaScript-Datei vollständig geladen (Version 4.2.1)');