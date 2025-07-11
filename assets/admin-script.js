/**
 * ReTexify AI Pro - Erweiterte Admin-Script-Datei (Version 4.2.0)
 * Mit Bugfixes, Bilder-SEO und direkter Textgenerierung
 */

(function($) {
    'use strict';
    
    // ========================================================================
    // 🔧 GLOBALE VARIABLEN UND INITIALISIERUNG
    // ========================================================================
    
    // Globale Namespace-Objekte
    window.retexifyGlobals = window.retexifyGlobals || {
        currentPostId: null,
        currentPostType: null,
        debugMode: false,
        analysisComplete: false,
        systemStatusLoaded: false,
        researchStatusLoaded: false
    };
    
    // Debug-Modus aktivieren falls URL-Parameter gesetzt
    if (window.location.search.indexOf('retexify_debug=1') !== -1) {
        window.retexifyGlobals.debugMode = true;
        console.log('🐛 ReTexify Debug-Modus aktiviert');
    }
    
    // ========================================================================
    // 🛠️ UTILITY-FUNKTIONEN
    // ========================================================================
    
    /**
     * Verbesserte AJAX-Call-Funktion mit besserem Error-Handling
     */
    function executeAjaxCall(options) {
        const defaults = {
            url: retexify_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            timeout: 30000,
            data: {
                nonce: retexify_ajax.nonce
            }
        };
        
        const settings = $.extend(true, {}, defaults, options);
        settings.data = $.extend({}, defaults.data, options.data || {});
        
        if (window.retexifyGlobals.debugMode) {
            console.log('🔄 AJAX Call:', settings);
        }
        
        return $.ajax(settings)
            .fail(function(jqXHR, textStatus, errorThrown) {
                const error = `AJAX Error: ${textStatus} - ${errorThrown}`;
                console.error('❌', error, jqXHR);
                if (options.error) {
                    options.error(error);
                }
            });
    }
    
    /**
     * Verbesserte Benachrichtigungsfunktion
     */
    function showNotification(message, type = 'info', duration = 5000) {
        type = type || 'info';
        duration = duration || 5000;
        
        // Alte Benachrichtigungen entfernen
        $('.retexify-notification').remove();
        
        const notificationClass = `retexify-notification retexify-notification-${type}`;
        const notification = $(`
            <div class="${notificationClass}" style="
                position: fixed;
                top: 32px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                padding: 15px 20px;
                border-radius: 4px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 100000;
                max-width: 400px;
                word-wrap: break-word;
            ">
                ${message}
            </div>
        `);
        
        $('body').append(notification);
        
        // Nach Ablauf der Zeit automatisch entfernen
        setTimeout(() => {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, duration);
        
        // Click zum Schließen
        notification.click(function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Kantone aus Checkboxes korrekt lesen (BUGFIX)
     */
    function getSelectedCantons() {
        const cantons = [];
        $('input[name="target_cantons[]"]:checked').each(function() {
            cantons.push($(this).val());
        });
        return cantons;
    }
    
    /**
     * Warten bis Analyse vollständig ist
     */
    function waitForAnalysisComplete(maxWait = 30000) {
        return new Promise((resolve, reject) => {
            const startTime = Date.now();
            
            function checkAnalysis() {
                if (window.retexifyGlobals.analysisComplete) {
                    resolve(true);
                    return;
                }
                
                if (Date.now() - startTime > maxWait) {
                    reject(new Error('Analyse-Timeout erreicht'));
                    return;
                }
                
                setTimeout(checkAnalysis, 500);
            }
            
            checkAnalysis();
        });
    }
    
    // ========================================================================
    // 🎯 HAUPTFUNKTIONEN: SEO-OPTIMIZER
    // ========================================================================
    
    /**
     * Content-Analyse vor SEO-Generierung durchführen
     */
    function analyzeContentBeforeGeneration(postId) {
        return new Promise((resolve, reject) => {
            if (!postId) {
                reject(new Error('Keine Post-ID verfügbar'));
                return;
            }
            
            showNotification('🔍 Analysiere Content...', 'info', 2000);
            window.retexifyGlobals.analysisComplete = false;
            
            executeAjaxCall({
                data: {
                    action: 'retexify_analyze_content',
                    post_id: postId
                },
                timeout: 15000,
                success: function(response) {
                    if (response.success) {
                        window.retexifyGlobals.analysisComplete = true;
                        showNotification('Analyse abgeschlossen', 'success', 1500);
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data || 'Analyse fehlgeschlagen'));
                    }
                },
                error: function(error) {
                    reject(new Error(`Analyse-Fehler: ${error}`));
                }
            });
        });
    }
    
    /**
     * SEO-Content laden (erweitert)
     */
    function loadSeoContent() {
        const postSelect = $('#retexify-post-select');
        const selectedPostId = postSelect.val();
        
        if (!selectedPostId) {
            showNotification('Bitte wählen Sie einen Beitrag/Seite aus', 'error');
            return;
        }
        
        window.retexifyGlobals.currentPostId = selectedPostId;
        
        // Lade-Animation
        $('.retexify-seo-content').html('<div class="retexify-loading">⏳ Lade Content...</div>');
        
        executeAjaxCall({
            data: {
                action: 'retexify_load_content',
                post_id: selectedPostId
            },
            success: function(response) {
                if (response.success) {
                    $('.retexify-seo-content').html(response.data.html);
                    
                    // Zusätzlich Bilder-SEO laden
                    loadImageSeoContent(selectedPostId);
                    
                    showNotification('Content geladen', 'success', 2000);
                } else {
                    $('.retexify-seo-content').html('<div class="retexify-error">Fehler beim Laden</div>');
                    showNotification('Fehler: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(error) {
                $('.retexify-seo-content').html('<div class="retexify-error">AJAX-Fehler</div>');
                showNotification('AJAX-Fehler: ' + error, 'error');
            }
        });
    }
    
    /**
     * NEUE FUNKTION: Bilder-SEO Content laden
     */
    function loadImageSeoContent(postId) {
        executeAjaxCall({
            data: {
                action: 'retexify_load_image_seo',
                post_id: postId
            },
            success: function(response) {
                if (response.success && response.data.images.length > 0) {
                    // Bilder-SEO Interface anzeigen
                    $('#retexify-images-seo-interface').show();
                    
                    // Bilder-SEO Grid erstellen
                    const imagesSeoHtml = buildImagesSeoHtml(response.data.images);
                    $('#retexify-images-seo-interface').html(imagesSeoHtml);
                    
                    showNotification(`${response.data.images.length} Bilder geladen`, 'success', 2000);
                } else {
                    showNotification('Keine Bilder für diesen Post gefunden', 'warning');
                }
            },
            error: function(error) {
                console.warn('Bilder-SEO Laden fehlgeschlagen:', error);
                showNotification('Fehler beim Laden der Bilder: ' + error, 'error');
            }
        });
    }
    
    /**
     * NEUE FUNKTION: HTML für Bilder-SEO erstellen
     */
    function buildImagesSeoHtml(images) {
        let html = `
            <div class="retexify-images-grid">
        `;
        
        images.forEach((image, index) => {
            html += `
                <div class="retexify-image-item" data-image-id="${image.id}">
                    <div class="retexify-image-preview">
                        <img src="${image.thumbnail}" alt="Vorschau" style="max-width: 100px; height: auto;">
                    </div>
                    <div class="retexify-image-seo-fields">
                        <label>Alt-Text:</label>
                        <input type="text" class="retexify-image-alt" value="${image.alt || ''}" 
                               placeholder="Beschreibender Alt-Text">
                        
                        <label>Titel:</label>
                        <input type="text" class="retexify-image-title" value="${image.title || ''}" 
                               placeholder="Bild-Titel">
                        
                        <label>Beschreibung:</label>
                        <textarea class="retexify-image-description" rows="2" 
                                  placeholder="Bild-Beschreibung">${image.description || ''}</textarea>
                        
                        <button type="button" class="retexify-btn retexify-btn-small retexify-generate-image-seo" 
                                data-image-id="${image.id}">
                            🤖 Alt-Text generieren
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += `
            </div>
            <button type="button" class="retexify-btn retexify-btn-primary retexify-save-all-image-seo">
                💾 Alle Bilder-SEO speichern
            </button>
        `;
        
        return html;
    }
    
    /**
     * Einzelnes SEO-Element mit verbesserter Logik generieren
     */
    function generateSingleSeo(seoType) {
        const postId = window.retexifyGlobals.currentPostId;
        
        if (!postId) {
            showNotification('Bitte laden Sie zuerst Content', 'error');
            return;
        }
        
        const $button = $(`.retexify-generate-${seoType.replace('_', '-')}`);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('🤖 Generiert...');
        
        // Erst Analyse, dann Generierung
        analyzeContentBeforeGeneration(postId)
            .then(() => {
                return executeAjaxCall({
                    data: {
                        action: 'retexify_generate_single_seo',
                        post_id: postId,
                        seo_type: seoType
                    },
                    timeout: 45000
                });
            })
            .then((response) => {
                if (response.success) {
                    const fieldId = `#retexify-generated-${seoType.replace('_', '-')}`;
                    $(fieldId).val(response.data.generated_text);
                    showNotification(`${seoType.toUpperCase()} erfolgreich generiert`, 'success');
                } else {
                    throw new Error(response.data || 'Generierung fehlgeschlagen');
                }
            })
            .catch((error) => {
                console.error('Generierungs-Fehler:', error);
                showNotification(`Fehler: ${error.message}`, 'error');
            })
            .finally(() => {
                $button.prop('disabled', false).html(originalText);
            });
    }
    
    /**
     * Alle SEO-Texte generieren (verbessert mit Synchronisation)
     */
    function generateAllSeoFixed() {
        const postId = window.retexifyGlobals.currentPostId;
        
        if (!postId) {
            showNotification('Bitte laden Sie zuerst Content', 'error');
            return;
        }
        
        const $button = $('.retexify-generate-all-seo');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('🤖 Generiert alle Texte...');
        
        // Erst Analyse, dann komplette SEO-Generierung
        analyzeContentBeforeGeneration(postId)
            .then(() => {
                showNotification('🚀 Starte komplette SEO-Generierung...', 'info', 3000);
                
                return executeAjaxCall({
                    data: {
                        action: 'retexify_generate_complete_seo',
                        post_id: postId
                    },
                    timeout: 90000
                });
            })
            .then((response) => {
                if (response.success && response.data) {
                    // Felder mit generierten Daten füllen
                    if (response.data.meta_title) {
                        $('#retexify-generated-meta-title').val(response.data.meta_title);
                    }
                    if (response.data.meta_description) {
                        $('#retexify-generated-meta-description').val(response.data.meta_description);
                    }
                    if (response.data.focus_keyword) {
                        $('#retexify-generated-focus-keyword').val(response.data.focus_keyword);
                    }
                    
                    showNotification('Alle SEO-Texte erfolgreich generiert!', 'success', 4000);
                } else {
                    throw new Error(response.data || 'Komplette Generierung fehlgeschlagen');
                }
            })
            .catch((error) => {
                console.error('Komplette Generierungs-Fehler:', error);
                showNotification(`Fehler: ${error.message}`, 'error', 6000);
            })
            .finally(() => {
                $button.prop('disabled', false).html(originalText);
            });
    }
    
    /**
     * NEUE FUNKTION: Direkte Textgenerierung (ohne Post-Auswahl)
     */
    function directTextGeneration() {
        const prompt = $('#retexify-direct-prompt').val();
        const textType = $('#retexify-direct-text-type').val();
        
        if (!prompt.trim()) {
            showNotification('Bitte geben Sie einen Text/Prompt ein', 'error');
            return;
        }
        
        const $button = $('.retexify-generate-direct-text');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('🤖 Generiert...');
        
        executeAjaxCall({
            data: {
                action: 'retexify_generate_direct_text',
                prompt: prompt,
                text_type: textType
            },
            timeout: 60000,
            success: function(response) {
                if (response.success) {
                    $('#retexify-direct-result').val(response.data.generated_text);
                    
                    // Statistiken aktualisieren
                    const text = response.data.generated_text;
                    const charCount = text.length;
                    const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
                    
                    $('#retexify-direct-char-count').text(charCount + ' Zeichen');
                    $('#retexify-direct-word-count').text(wordCount + ' Wörter');
                    
                    showNotification('Text erfolgreich generiert!', 'success');
                } else {
                    showNotification('Fehler: ' + (response.data || 'Generierung fehlgeschlagen'), 'error');
                }
            },
            error: function(error) {
                showNotification('AJAX-Fehler: ' + error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * SEO-Daten speichern (erweitert)
     */
    function saveSeoTexts() {
        const postId = window.retexifyGlobals.currentPostId;
        
        if (!postId) {
            showNotification('Keine Post-ID verfügbar', 'error');
            return;
        }
        
        const metaTitle = $('#retexify-generated-meta-title').val();
        const metaDescription = $('#retexify-generated-meta-description').val();
        const focusKeyword = $('#retexify-generated-focus-keyword').val();
        
        if (!metaTitle && !metaDescription && !focusKeyword) {
            showNotification('Keine Daten zum Speichern vorhanden', 'error');
            return;
        }
        
        const $btn = $('.retexify-save-seo-texts');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('💾 Speichert...');
        
        executeAjaxCall({
            data: {
                action: 'retexify_save_seo_data',
                post_id: postId,
                meta_title: metaTitle,
                meta_description: metaDescription,
                focus_keyword: focusKeyword
            },
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    showNotification('SEO-Daten erfolgreich gespeichert!', 'success', 4000);
                    
                    // Aktuelle Daten aktualisieren
                    if (metaTitle) $('#retexify-current-meta-title').text(metaTitle);
                    if (metaDescription) $('#retexify-current-meta-description').text(metaDescription);
                    if (focusKeyword) $('#retexify-current-focus-keyword').text(focusKeyword);
                } else {
                    showNotification('Fehler: ' + (response.data || 'Speichern fehlgeschlagen'), 'error');
                }
            },
            error: function(error) {
                showNotification('AJAX-Fehler: ' + error, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // ========================================================================
    // 🖼️ BILDER-SEO FUNKTIONEN (NEU)
    // ========================================================================
    
    /**
     * Alt-Text für einzelnes Bild generieren
     */
    function generateImageAltText(imageId) {
        const $button = $(`.retexify-generate-image-seo[data-image-id="${imageId}"]`);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('🤖 Generiert...');
        
        executeAjaxCall({
            data: {
                action: 'retexify_generate_image_seo',
                image_id: imageId
            },
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    const $imageItem = $(`.retexify-image-item[data-image-id="${imageId}"]`);
                    
                    if (response.data.alt_text) {
                        $imageItem.find('.retexify-image-alt').val(response.data.alt_text);
                    }
                    if (response.data.title) {
                        $imageItem.find('.retexify-image-title').val(response.data.title);
                    }
                    if (response.data.description) {
                        $imageItem.find('.retexify-image-description').val(response.data.description);
                    }
                    
                    showNotification('Alt-Text generiert!', 'success', 2000);
                } else {
                    showNotification('Fehler: ' + (response.data || 'Generierung fehlgeschlagen'), 'error');
                }
            },
            error: function(error) {
                showNotification('AJAX-Fehler: ' + error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * Alle Bilder-SEO Daten speichern
     */
    function saveAllImageSeo() {
        const imagesSeoData = [];
        
        $('.retexify-image-item').each(function() {
            const $item = $(this);
            const imageId = $item.data('image-id');
            
            imagesSeoData.push({
                id: imageId,
                alt: $item.find('.retexify-image-alt').val(),
                title: $item.find('.retexify-image-title').val(),
                description: $item.find('.retexify-image-description').val()
            });
        });
        
        if (imagesSeoData.length === 0) {
            showNotification('Keine Bilder-Daten vorhanden', 'error');
            return;
        }
        
        const $button = $('.retexify-save-all-image-seo');
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('💾 Speichert...');
        
        executeAjaxCall({
            data: {
                action: 'retexify_save_image_seo_bulk',
                images_data: imagesSeoData
            },
            timeout: 30000,
            success: function(response) {
                if (response.success) {
                    showNotification(`${response.data.saved_count} Bilder erfolgreich gespeichert!`, 'success');
                } else {
                    showNotification('Fehler: ' + (response.data || 'Speichern fehlgeschlagen'), 'error');
                }
            },
            error: function(error) {
                showNotification('AJAX-Fehler: ' + error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // ========================================================================
    // ⚙️ KI-EINSTELLUNGEN (BUGFIX)
    // ========================================================================
    
    /**
     * KI-Einstellungen speichern (mit Kantone-Bugfix)
     */
    function saveAiSettings() {
        const provider = $('#ai-provider').val();
        const apiKey = $('#retexify-ai-key').val();
        const model = $('#retexify-ai-model').val();
        const optimizationFocus = $('#retexify-optimization-focus').val();
        const businessContext = $('#retexify-business-context').val();
        const targetAudience = $('#retexify-target-audience').val();
        const brandVoice = $('#retexify-brand-voice').val();
        
        // BUGFIX: Kantone korrekt aus Checkboxes lesen
        const targetCantons = getSelectedCantons();
        
        const $btn = $('#retexify-save-ai-settings');
        const originalText = $btn.html();
        
        $btn.prop('disabled', true).html('💾 Speichert...');
        
        executeAjaxCall({
            data: {
                action: 'retexify_save_settings',
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
                if (response.success) {
                    showNotification(`Einstellungen gespeichert! ${targetCantons.length} Kantone gewählt.`, 'success');
                } else {
                    showNotification('Fehler: ' + (response.data || 'Speichern fehlgeschlagen'), 'error');
                }
            },
            error: function(error) {
                showNotification('AJAX-Fehler: ' + error, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // ========================================================================
    // 📊 DASHBOARD UND SYSTEM-STATUS
    // ========================================================================
    
    /**
     * Dashboard laden
     */
    function loadDashboard() {
        if (window.retexifyGlobals.systemStatusLoaded) {
            return;
        }
        
        executeAjaxCall({
            data: {
                action: 'retexify_get_stats'
            },
            success: function(response) {
                if (response.success) {
                    $('#retexify-dashboard-content').html(response.data);
                    window.retexifyGlobals.systemStatusLoaded = true;
                }
            },
            error: function(error) {
                $('#retexify-dashboard-content').html('<div class="retexify-error">Dashboard laden fehlgeschlagen</div>');
            }
        });
    }
    
    /**
     * System-Status laden
     */
    function loadSystemStatus() {
        executeAjaxCall({
            data: {
                action: 'retexify_get_system_info'
            },
            success: function(response) {
                if (response.success) {
                    $('#retexify-system-status-content').html(response.data);
                }
            },
            error: function(error) {
                showNotification('System-Status laden fehlgeschlagen: ' + error, 'error');
            }
        });
    }
    
    /**
     * Posts für Bilder-SEO laden
     */
    function loadPostsForImageSeo() {
        const $select = $('#retexify-images-post-select');
        if ($select.length === 0) return;
        
        // Posts und Seiten abrufen
        const posts = [];
        
        // Beiträge laden
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_posts_for_selection',
                nonce: retexify_ajax.nonce,
                post_type: 'post',
                status: 'publish'
            },
            success: function(response) {
                if (response.success && response.data) {
                    posts.push(...response.data);
                }
                
                // Seiten laden
                $.ajax({
                    url: retexify_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'retexify_get_posts_for_selection',
                        nonce: retexify_ajax.nonce,
                        post_type: 'page',
                        status: 'publish'
                    },
                    success: function(response2) {
                        if (response2.success && response2.data) {
                            posts.push(...response2.data);
                        }
                        
                        // Select-Optionen füllen
                        $select.empty();
                        $select.append('<option value="">-- Post/Seite auswählen --</option>');
                        
                        posts.forEach(function(post) {
                            $select.append(`<option value="${post.ID}">${post.post_title} (${post.post_type})</option>`);
                        });
                    }
                });
            }
        });
    }
    
    // ========================================================================
    // 🎮 EVENT-HANDLER UND INITIALISIERUNG
    // ========================================================================
    
    $(document).ready(function() {
        console.log('🚀 ReTexify AI Pro Admin Script (v4.2.0) wird initialisiert...');
        
        // Dashboard beim ersten Laden automatisch laden
        if ($('#retexify-dashboard-content').length && $('#retexify-dashboard-content').is(':visible')) {
            loadDashboard();
        }
        
        // Post-Auswahl für Bilder-SEO laden
        loadPostsForImageSeo();
        
        // Tab-Wechsel Event-Handler
        $(document).on('click', '.retexify-tab-button', function(e) {
            e.preventDefault();
            
            const targetTab = $(this).data('tab');
            
            // Tab-Buttons aktualisieren
            $('.retexify-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Tab-Content aktualisieren
            $('.retexify-tab-content').removeClass('active');
            $(`#retexify-tab-${targetTab}`).addClass('active');
            
            // Spezielle Aktionen bei Tab-Wechsel
            if (targetTab === 'dashboard' && !window.retexifyGlobals.systemStatusLoaded) {
                loadDashboard();
            } else if (targetTab === 'system') {
                loadSystemStatus();
            }
        });
        
        // SEO-Optimizer Event-Handler
        $(document).on('click', '.retexify-load-content, #retexify-load-content', loadSeoContent);
        $(document).on('click', '.retexify-generate-meta-title, #retexify-generate-meta-title', () => generateSingleSeo('meta_title'));
        $(document).on('click', '.retexify-generate-meta-description, #retexify-generate-meta-description', () => generateSingleSeo('meta_description'));
        $(document).on('click', '.retexify-generate-focus-keyword, #retexify-generate-focus-keyword', () => generateSingleSeo('focus_keyword'));
        $(document).on('click', '.retexify-generate-all-seo, #retexify-generate-all-seo', generateAllSeoFixed);
        $(document).on('click', '.retexify-save-seo-texts, #retexify-save-seo-texts', saveSeoTexts);
        
        // Direkte Textgenerierung Event-Handler
        $(document).on('click', '.retexify-generate-direct-text, #retexify-generate-direct-text', directTextGeneration);
        
        // Text kopieren Event-Handler
        $(document).on('click', '.retexify-copy-direct-text, #retexify-copy-direct-text', function() {
            const text = $('#retexify-direct-result').val();
            if (text) {
                navigator.clipboard.writeText(text).then(function() {
                    showNotification('Text in Zwischenablage kopiert!', 'success', 2000);
                }).catch(function() {
                    // Fallback für ältere Browser
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    showNotification('Text in Zwischenablage kopiert!', 'success', 2000);
                });
            } else {
                showNotification('Kein Text zum Kopieren vorhanden', 'error');
            }
        });
        
        // Text-Statistiken aktualisieren
        $(document).on('input', '#retexify-direct-result', function() {
            const text = $(this).val();
            const charCount = text.length;
            const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
            
            $('#retexify-direct-char-count').text(charCount + ' Zeichen');
            $('#retexify-direct-word-count').text(wordCount + ' Wörter');
        });
        
        // Bilder-SEO Event-Handler
        $(document).on('click', '.retexify-generate-image-seo, #retexify-generate-image-seo', function() {
            const imageId = $(this).data('image-id');
            generateImageAltText(imageId);
        });
        $(document).on('click', '.retexify-save-all-image-seo, #retexify-save-all-image-seo', saveAllImageSeo);
        
        // Bilder-SEO laden Event-Handler
        $(document).on('click', '#retexify-load-images-seo', function() {
            const postId = $('#retexify-images-post-select').val();
            if (postId) {
                loadImageSeoContent(postId);
            } else {
                showNotification('Bitte wählen Sie einen Post/Seite aus', 'error');
            }
        });
        
        // KI-Einstellungen Event-Handler (mit Bugfix)
        $(document).on('click', '#retexify-save-ai-settings, .retexify-save-ai-settings', function(e) {
            e.preventDefault();
            saveAiSettings();
        });
        
        // Kantone-Auswahl Helper-Buttons
        $(document).on('click', '#retexify-select-all-cantons, .retexify-select-all-cantons', function(e) {
            e.preventDefault();
            $('input[name="target_cantons[]"]').prop('checked', true);
        });
        
        $(document).on('click', '#retexify-clear-cantons, .retexify-clear-cantons', function(e) {
            e.preventDefault();
            $('input[name="target_cantons[]"]').prop('checked', false);
        });
        
        $(document).on('click', '#retexify-select-main-cantons, .retexify-select-main-cantons', function(e) {
            e.preventDefault();
            const mainCantons = ['ZH', 'BE', 'VD', 'AG', 'SG', 'GE', 'LU', 'TI', 'BS', 'BL'];
            $('input[name="target_cantons[]"]').each(function() {
                const val = $(this).val();
                $(this).prop('checked', mainCantons.includes(val));
            });
        });
        
        // API-Verbindung testen
        $(document).on('click', '#retexify-test-api-connection, .retexify-test-api-connection', function(e) {
            e.preventDefault();
            
            const provider = $('#ai-provider').val();
            const apiKey = $('#retexify-ai-key').val();
            
            if (!apiKey) {
                showNotification('Bitte geben Sie einen API-Key ein', 'error');
                return;
            }
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            $btn.prop('disabled', true).html('🔄 Teste...');
            
            executeAjaxCall({
                data: {
                    action: 'retexify_test_api_connection',
                    api_provider: provider,
                    api_key: apiKey
                },
                success: function(response) {
                    if (response.success) {
                        showNotification('API-Verbindung erfolgreich!', 'success');
                    } else {
                        showNotification('API-Test fehlgeschlagen: ' + (response.data || 'Unbekannt'), 'error');
                    }
                },
                error: function(error) {
                    showNotification('Verbindungstest fehlgeschlagen: ' + error, 'error');
                },
                complete: function() {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        });
        
        console.log('✅ ReTexify AI Pro Admin Script vollständig initialisiert!');
    });
    
    // Globale Funktionen für externe Verwendung verfügbar machen
    window.retexifyLoadSeoContent = loadSeoContent;
    window.retexifyGenerateSingleSeo = generateSingleSeo;
    window.retexifyGenerateAllSeo = generateAllSeoFixed;
    window.retexifySaveSeoTexts = saveSeoTexts;
    window.retexifyDirectTextGeneration = directTextGeneration;
    window.retexifyGenerateImageAltText = generateImageAltText;
    window.retexifySaveAllImageSeo = saveAllImageSeo;
    window.retexifyShowNotification = showNotification;
    window.retexifyLoadDashboard = loadDashboard;
    window.retexifyLoadSystemStatus = loadSystemStatus;
    
})(jQuery);