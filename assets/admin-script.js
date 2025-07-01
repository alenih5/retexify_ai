/**
 * ReTexify AI Pro - VOLLSTÄNDIGE FUNKTIONSFÄHIGE Admin JavaScript
 * Version: 4.3.0 - Komplette Neuentwicklung mit Bugfixes
 * 
 * FIXES:
 * ✅ Behebung aller 400-Fehler und AJAX-Probleme  
 * ✅ Korrekte Parameter für SEO Content Loading
 * ✅ Robuste Nonce-Behandlung und Fehlerbehandlung
 * ✅ Meta-Text-Generierung funktionsfähig
 * ✅ jQuery-Konflikte behoben
 * ✅ Vollständige SEO-Optimizer Funktionalität
 */

// ============================================================================
// 🌍 GLOBALE VARIABLEN (Persistent außerhalb jQuery)
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
    performanceTimers: {}
};

// ============================================================================
// 🚀 HAUPT-JAVASCRIPT (jQuery-Wrapper mit No-Conflict)
// ============================================================================

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('🚀 ReTexify AI Pro JavaScript startet (Version 4.3.0)...');
    console.log('📊 AJAX Setup:', {
        url: retexify_ajax.ajax_url,
        nonce: retexify_ajax.nonce,
        user_can_manage: retexify_ajax.user_can_manage || true
    });
    
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
        
        console.log('✅ ReTexify AI Pro vollständig initialisiert');
        showNotification('🚀 ReTexify AI bereit', 'success', 2000);
    }
    
    // ========================================================================
    // 🎯 TAB-SYSTEM
    // ========================================================================
    
    function initializeTabs() {
        console.log('🔄 Initialisiere Tab-System...');
        
        // Event-Delegation für Tab-Clicks (robuster)
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
            
            // Tab-spezifische Aktionen mit Delay
            setTimeout(function() {
                handleTabSwitch(tabId);
            }, 100);
        });
    }
    
    function handleTabSwitch(tabId) {
        console.log('🎯 Behandle Tab-Wechsel:', tabId);
        
        switch(tabId) {
            case 'system':
                if (!window.retexifyGlobals.systemStatusLoaded) {
                    console.log('🔄 Lade System-Status automatisch...');
                    setTimeout(loadSystemStatus, 200);
                }
                if (!window.retexifyGlobals.researchStatusLoaded) {
                    console.log('🔄 Lade Research-Status automatisch...');
                    setTimeout(loadResearchStatus, 2500);
                }
                break;
                
            case 'dashboard':
                if ($('#retexify-dashboard-content').length > 0) {
                    loadDashboard();
                }
                break;
                
            case 'seo-optimizer':
                initializeSeoOptimizer();
                break;
                
            case 'export-import':
                if (typeof loadExportImportTab === 'function') {
                    loadExportImportTab();
                }
                break;
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
                $container.html(response.data);
                showNotification('✅ Dashboard geladen', 'success', 2000);
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
            console.error('❌ System-Status Container (#retexify-system-status) nicht gefunden');
            return;
        }
        
        console.log('✅ System-Status Container gefunden');
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
                $container.html(response.data);
                showNotification('✅ System-Status geladen', 'success', 2000);
                console.log('✅ System-Status erfolgreich geladen');
            },
            error: function(error) {
                window.retexifyGlobals.systemStatusLoaded = false;
                $container.html(createErrorHTML('System-Test fehlgeschlagen', error));
                showNotification('❌ System-Test fehlgeschlagen', 'error', 3000);
            }
        });
    }
    
    // ========================================================================
    // 🧠 RESEARCH-STATUS FUNKTIONEN
    // ========================================================================
    
    function loadResearchStatus() {
        console.log('🧠 loadResearchStatus() aufgerufen');
        
        if (window.retexifyGlobals.researchStatusLoaded) {
            console.log('🧠 Research-Status bereits geladen');
            return;
        }
        
        var $container = $('#retexify-research-engine-status, #research-engine-status-content, .retexify-research-status').first();
        if ($container.length === 0) {
            console.warn('⚠️ Research-Status Container nicht gefunden - das ist optional');
            window.retexifyGlobals.researchStatusLoaded = true;
            return;
        }
        
        console.log('✅ Research Container gefunden');
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
                $container.html(response.data);
                showNotification('✅ Research-Engine getestet', 'success', 2000);
                console.log('✅ Research-Status erfolgreich geladen');
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
    // 🚀 SEO-OPTIMIZER FUNKTIONEN - KOMPLETT NEU IMPLEMENTIERT
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
    
    // SEO Content laden - KOMPLETT ÜBERARBEITET
    function loadSeoContent() {
        if (window.retexifyGlobals.ajaxInProgress) {
            console.warn('⚠️ AJAX bereits in Bearbeitung, warte...');
            return;
        }
        
        var $btn = $('#retexify-load-seo-content');
        var originalText = $btn.html();
        var postType = $('#seo-post-type').val() || 'page';
        
        console.log('📄 Lade SEO Content für Post-Typ:', postType);
        
        $btn.html('🔄 Lade Content...').prop('disabled', true);
        window.retexifyGlobals.ajaxInProgress = true;
        
        // KORRIGIERTE AJAX-Parameter
        executeAjaxCall({
            action: 'retexify_load_content',
            data: {
                post_type: postType
            },
            timeout: 20000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                window.retexifyGlobals.ajaxInProgress = false;
                
                console.log('📄 SEO Content Response:', response);
                
                var data = response.data;
                var items = [];
                
                // Flexible Datenstruktur-Behandlung
                if (data && data.items) {
                    items = data.items;
                } else if (data && data.posts) {
                    items = data.posts;
                } else if (data && data.pages) {
                    items = data.pages;
                } else if (Array.isArray(data)) {
                    items = data;
                } else {
                    console.warn('⚠️ Unbekannte Datenstruktur:', data);
                    items = [];
                }
                
                // Globale SEO-Daten speichern
                window.retexifyGlobals.seoData = items;
                window.retexifyGlobals.currentSeoIndex = 0;
                window.retexifyGlobals.totalSeoItems = items.length;
                
                if (window.retexifyGlobals.totalSeoItems > 0) {
                    // Content-Liste anzeigen
                    $('#retexify-seo-content-list').show();
                    
                    // Ersten Eintrag anzeigen
                    displayCurrentSeoItem();
                    
                    // Navigation aktualisieren
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
    
    // Aktuellen SEO-Eintrag anzeigen
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
        
        // Aktuelle Post-ID speichern
        window.retexifyGlobals.currentPostId = currentItem.id || currentItem.ID;
        
        // Page-Informationen anzeigen
        $('#retexify-current-page-title').text(currentItem.title || currentItem.post_title || 'Unbekannter Titel');
        $('#retexify-page-info').text('ID: ' + window.retexifyGlobals.currentPostId + ' | Status: ' + (currentItem.status || currentItem.post_status || 'publish'));
        
        // Links setzen (falls verfügbar)
        if (currentItem.url || currentItem.permalink || currentItem.guid) {
            $('#retexify-page-url').attr('href', currentItem.url || currentItem.permalink || currentItem.guid);
        }
        if (currentItem.edit_url || currentItem.edit_link) {
            $('#retexify-edit-page').attr('href', currentItem.edit_url || currentItem.edit_link);
        }
        
        // Content anzeigen
        var contentText = currentItem.full_content || currentItem.content || currentItem.post_content || currentItem.content_excerpt || '';
        if (contentText) {
            // HTML-Tags entfernen für bessere Lesbarkeit
            var cleanContent = $('<div>').html(contentText).text();
            $('#retexify-content-text').text(cleanContent.substring(0, 500) + (cleanContent.length > 500 ? '...' : ''));
            updateContentStats(cleanContent);
        }
        
        // Aktuelle SEO-Daten anzeigen
        $('#retexify-current-meta-title').text(currentItem.meta_title || currentItem.current_meta_title || 'Nicht gesetzt');
        $('#retexify-current-meta-description').text(currentItem.meta_description || currentItem.current_meta_description || 'Nicht gesetzt');
        $('#retexify-current-focus-keyword').text(currentItem.focus_keyword || currentItem.current_focus_keyword || 'Nicht gesetzt');
        
        // Generierungs-Buttons aktivieren
        $('.retexify-generate-single, #retexify-generate-all-seo').prop('disabled', false);
        
        console.log('✅ SEO-Eintrag angezeigt für Post-ID:', window.retexifyGlobals.currentPostId);
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
        
        var wordCount = content.split(/\s+/).filter(function(word) { return word.length > 0; }).length;
        var charCount = content.length;
        
        $('#retexify-word-count').text(wordCount + ' Wörter');
        $('#retexify-char-count').text(charCount + ' Zeichen');
    }
    
    // ========================================================================
    // 🎨 SEO-GENERIERUNG FUNKTIONEN - VOLLSTÄNDIG IMPLEMENTIERT
    // ========================================================================
    
    // Einzelne SEO-Texte generieren
    function generateSingleSeo(seoType) {
        if (!window.retexifyGlobals.currentPostId) {
            showNotification('❌ Keine Post-ID verfügbar', 'error', 3000);
            return;
        }
        
        var $btn = $('.retexify-generate-single[data-type="' + seoType + '"]');
        var originalText = $btn.html();
        
        console.log('🔄 Generiere', seoType, 'für Post-ID:', window.retexifyGlobals.currentPostId);
        
        $btn.html('🔄 Generiert...').prop('disabled', true);
        
        // ✅ KORRIGIERT: Richtige AJAX-Action verwenden
        var ajaxAction = 'retexify_generate_single_seo';
        
        executeAjaxCall({
            action: ajaxAction,
            data: {
                post_id: window.retexifyGlobals.currentPostId,
                seo_type: seoType
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                
                console.log('✅ Generierung erfolgreich:', response);
                
                if (response.data && response.data.generated_text) {
                    // Generierte Texte in die entsprechenden Felder einfügen
                    var generatedText = response.data.generated_text;
                    
                    if (seoType === 'meta_title') {
                        $('#retexify-new-meta-title').val(generatedText);
                    } else if (seoType === 'meta_description') {
                        $('#retexify-new-meta-description').val(generatedText);
                    } else if (seoType === 'focus_keyword') {
                        $('#retexify-new-focus-keyword').val(generatedText);
                    }
                    
                    updateCharCounters();
                    showNotification('✅ ' + getSeoTypeLabel(seoType) + ' generiert', 'success', 3000);
                } else {
                    showNotification('⚠️ Keine generierten Daten erhalten', 'warning', 3000);
                }
            },
            error: function(error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ Generierung fehlgeschlagen:', error);
                showNotification('❌ Generierung fehlgeschlagen: ' + error, 'error', 4000);
            }
        });
    }
    
    // Alle SEO-Texte generieren
    function generateAllSeo() {
        if (!window.retexifyGlobals.currentPostId) {
            showNotification('❌ Keine Post-ID verfügbar', 'error', 3000);
            return;
        }
        
        var $btn = $('#retexify-generate-all-seo');
        var originalText = $btn.html();
        
        console.log('🔄 Generiere alle SEO-Texte für Post-ID:', window.retexifyGlobals.currentPostId);
        
        $btn.html('🔄 Generiert alle...').prop('disabled', true);
        
        // ✅ KORRIGIERT: Richtige AJAX-Action verwenden
        var ajaxAction = 'retexify_generate_complete_seo';
        
        executeAjaxCall({
            action: ajaxAction,
            data: {
                post_id: window.retexifyGlobals.currentPostId
            },
            timeout: 60000, // 60 Sekunden für alle Texte
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                
                console.log('✅ Komplette Generierung erfolgreich:', response);
                
                if (response.data) {
                    var data = response.data;
                    
                    // Alle generierten Texte einfügen
                    if (data.meta_title) {
                        $('#retexify-new-meta-title').val(data.meta_title);
                    }
                    if (data.meta_description) {
                        $('#retexify-new-meta-description').val(data.meta_description);
                    }
                    if (data.focus_keyword) {
                        $('#retexify-new-focus-keyword').val(data.focus_keyword);
                    }
                    
                    updateCharCounters();
                    showNotification('✅ Alle SEO-Texte generiert', 'success', 4000);
                } else {
                    showNotification('⚠️ Keine generierten Daten erhalten', 'warning', 3000);
                }
            },
            error: function(error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ Komplette Generierung fehlgeschlagen:', error);
                showNotification('❌ Generierung fehlgeschlagen: ' + error, 'error', 4000);
            }
        });
    }
    
    // SEO-Texte speichern
    function saveSeoTexts() {
        if (!window.retexifyGlobals.currentPostId) {
            showNotification('❌ Keine Post-ID verfügbar', 'error', 3000);
            return;
        }
        
        var metaTitle = $('#retexify-new-meta-title').val();
        var metaDescription = $('#retexify-new-meta-description').val();
        var focusKeyword = $('#retexify-new-focus-keyword').val();
        
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
                
                if (response.data && response.data.saved_count) {
                    showNotification('💾 ' + response.data.saved_count + ' SEO-Elemente gespeichert', 'success', 4000);
                    
                    // Aktuelle Anzeige aktualisieren
                    if (metaTitle) $('#retexify-current-meta-title').text(metaTitle);
                    if (metaDescription) $('#retexify-current-meta-description').text(metaDescription);
                    if (focusKeyword) $('#retexify-current-focus-keyword').text(focusKeyword);
                } else {
                    showNotification('✅ SEO-Daten gespeichert', 'success', 3000);
                }
            },
            error: function(error) {
                $btn.html(originalText).prop('disabled', false);
                showNotification('❌ Speichern fehlgeschlagen: ' + error, 'error', 4000);
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
        
        // Merge options
        var settings = $.extend(true, {}, defaults, options);
        
        // Action hinzufügen
        if (options.action) {
            settings.data.action = options.action;
        }
        
        // Zusätzliche Daten hinzufügen
        if (options.data) {
            $.extend(settings.data, options.data);
        }
        
        console.log('📡 Execute AJAX Call:', settings.data.action, settings.data);
        
        return $.ajax(settings)
            .done(function(response) {
                console.log('✅ AJAX Success:', response);
                
                if (response && response.success) {
                    if (typeof options.success === 'function') {
                        options.success(response);
                    }
                } else {
                    var errorMsg = 'Server-Fehler';
                    if (response && response.data) {
                        errorMsg = typeof response.data === 'string' ? response.data : JSON.stringify(response.data);
                    }
                    console.error('❌ AJAX Server Error:', errorMsg);
                    if (typeof options.error === 'function') {
                        options.error(errorMsg);
                    }
                }
            })
            .fail(function(xhr, status, error) {
                var detailedError = 'Verbindungsfehler: ' + error;
                
                // Detaillierte Fehleranalyse
                if (xhr.status === 400) {
                    detailedError = 'Ungültige Anfrage (400) - Prüfe Parameter und Nonce';
                } else if (xhr.status === 403) {
                    detailedError = 'Zugriff verweigert (403) - Prüfe Berechtigung';
                } else if (xhr.status === 404) {
                    detailedError = 'AJAX-Handler nicht gefunden (404) - Prüfe Action';
                } else if (xhr.status === 500) {
                    detailedError = 'Server-Fehler (500) - Prüfe PHP-Logs';
                } else if (status === 'timeout') {
                    detailedError = 'Zeitüberschreitung - Server antwortet nicht';
                } else if (status === 'parsererror') {
                    detailedError = 'JSON-Parse-Fehler - Ungültige Server-Antwort';
                }
                
                console.error('❌ AJAX Fail:', {
                    status: xhr.status,
                    statusText: status,
                    error: error,
                    responseText: xhr.responseText ? xhr.responseText.substring(0, 200) : 'Keine Antwort'
                });
                
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
        $(document).off('click.system-test').on('click.system-test', '#retexify-test-system-badge, #test-system-badge, .retexify-test-system-btn', function(e) {
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
        $(document).off('click.research-test').on('click.research-test', '#retexify-test-research-badge, #test-research-apis, .retexify-test-research-btn', function(e) {
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
            generateAllSeo();
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
                    .retexify-status-warning {
                        background: #fff3cd;
                        border: 1px solid #ffeaa7;
                        border-radius: 6px;
                        padding: 15px;
                        margin: 10px 0;
                        color: #856404;
                    }
                    .retexify-status-warning .warning-icon {
                        font-size: 24px;
                        margin-bottom: 8px;
                    }
                    .retexify-status-warning h4 {
                        margin: 0 0 8px 0;
                        color: #856404;
                    }
                    .retexify-status-warning p {
                        margin: 0 0 8px 0;
                        line-height: 1.4;
                    }
                    .retexify-status-warning small {
                        font-size: 12px;
                        opacity: 0.8;
                    }
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
    // 🌍 GLOBALE FUNKTIONEN (für Kompatibilität und Debug)
    // ========================================================================
    
    // Globale Funktionen für andere Skripte verfügbar machen
    window.retexifyLoadSystemStatus = loadSystemStatus;
    window.retexifyLoadResearchStatus = loadResearchStatus;
    window.retexifyLoadDashboard = loadDashboard;
    window.retexifyShowNotification = showNotification;
    window.retexifyLoadSeoContent = loadSeoContent;
    window.retexifyDisplayCurrentSeoItem = displayCurrentSeoItem;
    window.retexifyExecuteAjaxCall = executeAjaxCall;
    window.retexifyGenerateSingleSeo = generateSingleSeo;
    window.retexifyGenerateAllSeo = generateAllSeo;
    window.retexifySaveSeoTexts = saveSeoTexts;
    
    console.log('✅ ReTexify AI Pro JavaScript vollständig geladen (Version 4.3.0)');
    
}); // Ende jQuery(document).ready

// ============================================================================
// 🐛 DEBUG UND ENTWICKLUNGSHELFER
// ============================================================================

// Globale Debug-Funktion
window.retexifyDebug = function() {
    console.log('🐛 ReTexify Debug Info:', {
        version: '4.3.0',
        globals: window.retexifyGlobals,
        jquery: typeof jQuery !== 'undefined',
        ajax: typeof retexify_ajax !== 'undefined' ? retexify_ajax : 'undefined',
        containers: {
            system: jQuery('#retexify-system-status').length,
            research: jQuery('#retexify-research-engine-status, #research-engine-status-content').length,
            dashboard: jQuery('#retexify-dashboard-content').length,
            seoOptimizer: jQuery('#retexify-load-seo-content').length,
            seoContentList: jQuery('#retexify-seo-content-list').length,
            seoPostType: jQuery('#seo-post-type').length
        },
        eventListeners: {
            tabs: jQuery('.retexify-tab-btn').length,
            buttons: jQuery('[id*="retexify-"]').length
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
    action = action || 'retexify_load_content';
    data = data || { post_type: 'page' };
    
    console.log('🧪 Teste AJAX-Verbindung...', action, data);
    
    if (typeof window.retexifyExecuteAjaxCall === 'function') {
        window.retexifyExecuteAjaxCall({
            action: action,
            data: data,
            success: function(response) {
                console.log('✅ AJAX-Test erfolgreich:', response);
            },
            error: function(error) {
                console.error('❌ AJAX-Test fehlgeschlagen:', error);
            }
        });
    } else {
        console.error('❌ retexifyExecuteAjaxCall-Funktion nicht verfügbar');
    }
};

// Status-Reset-Funktion für Debugging
window.retexifyResetStatus = function() {
    window.retexifyGlobals.systemStatusLoaded = false;
    window.retexifyGlobals.researchStatusLoaded = false;
    window.retexifyGlobals.ajaxInProgress = false;
    console.log('🔄 Status zurückgesetzt');
};

// SEO-Daten-Reset für Debugging
window.retexifyResetSeoData = function() {
    window.retexifyGlobals.seoData = [];
    window.retexifyGlobals.currentSeoIndex = 0;
    window.retexifyGlobals.totalSeoItems = 0;
    window.retexifyGlobals.currentPostId = null;
    jQuery('#retexify-seo-content-list').hide();
    console.log('🔄 SEO-Daten zurückgesetzt');
};

// Fallback für alte Browser oder jQuery-Probleme
if (typeof jQuery === 'undefined') {
    console.error('❌ jQuery nicht verfügbar - ReTexify AI Pro benötigt jQuery');
} else {
    console.log('✅ jQuery verfügbar:', jQuery.fn.jquery);
}

console.log('📄 ReTexify AI Pro JavaScript-Datei vollständig geladen (Version 4.3.0)');