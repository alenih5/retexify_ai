/**
 * ReTexify AI Pro - Universal Admin JavaScript
 * Version: 3.5.7 - Added Manual Export/Import functionality and refactored for clarity.
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify AI JavaScript startet...');
    
    // =========================================================================
    // Globale Variablen & Initialisierung
    // =========================================================================
    let seoData = [];
    let currentSeoIndex = 0;
    
    // Initialisierungen beim Laden der Seite
    initializeTabs();
    loadDashboard(); // Das Dashboard ist der erste aktive Tab

    // =========================================================================
    // Tab-System
    // =========================================================================
    function initializeTabs() {
        $(document).on('click', '.retexify-tab-btn', function(e) {
            e.preventDefault();
            const tabId = $(this).data('tab');
            
            $('.retexify-tab-btn, .retexify-tab-content').removeClass('active');
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
            
            // Tab-spezifische Aktionen auslösen
            switch(tabId) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'ai-settings':
                    setTimeout(initializeAiSettings, 50);
                    break;
                case 'system':
                    $('#retexify-test-system-badge').trigger('click');
                    break;
                case 'manual-export-import':
                    initializeManualExportImport();
                    break;
            }
        });
        console.log('✅ Tab-System initialisiert');
    }

    // =========================================================================
    // Dashboard & System
    // =========================================================================
    function loadDashboard() {
        const $container = $('#retexify-dashboard-content');
        // Nur laden, wenn es noch nicht geladen wurde oder ein Fehler aufgetreten ist.
        if ($container.find('.retexify-loading').length > 0 || $container.find('.retexify-warning').length > 0) {
            ajaxHelper('retexify_get_stats', {}, 'Dashboard wird geladen...', $container, (res) => {
                $container.html(res.data);
            });
        }
    }

    $(document).on('click', '#retexify-refresh-stats-badge', loadDashboard);
    $(document).on('click', '#retexify-test-system-badge', function() {
        ajaxHelper('retexify_test', {}, 'Systemtest wird ausgeführt...', $('#retexify-system-status'), (res) => {
            $('#retexify-system-status').html(res.data);
        });
    });

    // =========================================================================
    // SEO-Optimizer
    // =========================================================================
    $(document).on('click', '#retexify-load-seo-content', function(e) {
        const postType = $('#seo-post-type').val();
        ajaxHelper('retexify_load_seo_content', { post_type: postType }, 'Lade SEO-Content...', null, (res) => {
            seoData = res.data.items;
            currentSeoIndex = 0;
            if (seoData && seoData.length > 0) {
                $('#retexify-seo-content-list').show();
                displayCurrentSeoPage();
                showNotification(`✅ ${seoData.length} ${postType}(s) geladen!`, 'success');
            } else {
                $('#retexify-seo-content-list').hide();
                showNotification('Keine Inhalte für diesen Post-Typ gefunden.', 'warning');
            }
        }, e);
    });

    function displayCurrentSeoPage() {
        if (!seoData || seoData.length === 0) return;
        const current = seoData[currentSeoIndex];
        
        $('#retexify-current-page-title').text(current.title);
        $('#retexify-page-info').html(`ID: ${current.id} • Typ: ${current.type} • Geändert: ${current.modified}`);
        $('#retexify-page-url').attr('href', current.url);
        $('#retexify-edit-page').attr('href', current.edit_url);
        $('#retexify-seo-counter').text(`${currentSeoIndex + 1} / ${seoData.length}`);
        
        $('#retexify-current-meta-title').text(current.meta_title || 'Nicht gesetzt');
        $('#retexify-current-meta-description').text(current.meta_description || 'Nicht gesetzt');
        $('#retexify-current-focus-keyword').text(current.focus_keyword || 'Nicht gesetzt');
        
        $('#retexify-new-meta-title, #retexify-new-meta-description, #retexify-new-focus-keyword').val('');
        updateCharCounters();
        
        $('#retexify-seo-prev').prop('disabled', currentSeoIndex === 0);
        $('#retexify-seo-next').prop('disabled', currentSeoIndex >= seoData.length - 1);
        
        $('#retexify-full-content').hide();
        $('#retexify-seo-results').empty();
    }

    // Navigation, Content-Anzeige, etc.
    $(document).on('click', '#retexify-seo-prev', () => { if (currentSeoIndex > 0) { currentSeoIndex--; displayCurrentSeoPage(); } });
    $(document).on('click', '#retexify-seo-next', () => { if (currentSeoIndex < seoData.length - 1) { currentSeoIndex++; displayCurrentSeoPage(); } });
    $(document).on('click', '#retexify-clear-seo-fields', () => {
         $('#retexify-new-meta-title, #retexify-new-meta-description, #retexify-new-focus-keyword').val('');
         updateCharCounters();
    });
    $(document).on('click', '#retexify-show-content', function() {
        const $contentDiv = $('#retexify-full-content');
        if($contentDiv.is(':visible')) { $contentDiv.slideUp(); return; }
        
        const current = seoData[currentSeoIndex];
        $('#retexify-content-text').html(current.full_content || "Kein Content gefunden.");
        const wordCount = current.full_content ? current.full_content.split(/\s+/).filter(Boolean).length : 0;
        $('#retexify-word-count').text(`${wordCount} Wörter`);
        $('#retexify-char-count').text(`${current.full_content?.length || 0} Zeichen`);
        $contentDiv.slideDown();
    });

    // Generierungs- und Speicher-Aktionen
    $(document).on('click', '.retexify-generate-single', function(e) {
        if (!seoData[currentSeoIndex]) return;
        const seoType = $(this).data('type');
        ajaxHelper('retexify_generate_seo_item', {
            post_id: seoData[currentSeoIndex].id,
            seo_type: seoType,
            include_cantons: $('#retexify-include-cantons').is(':checked'),
            premium_tone: $('#retexify-premium-tone').is(':checked')
        }, `Generiere ${seoType}...`, $('#retexify-seo-results'), (res) => {
            $(`#retexify-new-${res.data.type.replace('_', '-')}`).val(res.data.content).trigger('input');
        }, e);
    });

    $(document).on('click', '#retexify-generate-all-seo', function(e) {
        if (!seoData[currentSeoIndex]) return;
        ajaxHelper('retexify_generate_complete_seo', {
            post_id: seoData[currentSeoIndex].id,
            include_cantons: $('#retexify-include-cantons').is(':checked'),
            premium_tone: $('#retexify-premium-tone').is(':checked')
        }, 'Generiere komplette SEO-Suite...', $('#retexify-seo-results'), (res) => {
            const { suite } = res.data;
            $('#retexify-new-meta-title').val(suite.meta_title).trigger('input');
            $('#retexify-new-meta-description').val(suite.meta_description).trigger('input');
            $('#retexify-new-focus-keyword').val(suite.focus_keyword).trigger('input');
        }, e);
    });
    
    $(document).on('click', '#retexify-save-seo-data', function(e) {
        if (!seoData[currentSeoIndex]) return;
        ajaxHelper('retexify_save_seo_data', {
            post_id: seoData[currentSeoIndex].id,
            meta_title: $('#retexify-new-meta-title').val(),
            meta_description: $('#retexify-new-meta-description').val(),
            focus_keyword: $('#retexify-new-focus-keyword').val()
        }, 'Speichere SEO-Daten...', $('#retexify-seo-results'), (res) => {
            showNotification(`✅ ${res.data.message}`, 'success');
            seoData[currentSeoIndex].meta_title = $('#retexify-new-meta-title').val();
            seoData[currentSeoIndex].meta_description = $('#retexify-new-meta-description').val();
            seoData[currentSeoIndex].focus_keyword = $('#retexify-new-focus-keyword').val();
            displayCurrentSeoPage();
        }, e);
    });
    
    // Zeichenzähler
    $(document).on('input', '#retexify-new-meta-title, #retexify-new-meta-description', updateCharCounters);
    function updateCharCounters() {
        $('#title-chars').text($('#retexify-new-meta-title').val()?.length || 0);
        $('#description-chars').text($('#retexify-new-meta-description').val()?.length || 0);
    }

    // =========================================================================
    // KI-Einstellungen
    // =========================================================================
    function initializeAiSettings() {
        const { providerModels, api_keys, current_model } = window.retexify_ajax;
        let currentProvider = $('#ai-provider').val();

        const updateProviderUI = () => {
            const models = providerModels[currentProvider] || {};
            const $modelSelect = $('#ai-model').empty();
            $.each(models, (key, name) => {
                $modelSelect.append($('<option>', { value: key, text: name, selected: key === current_model }));
            });
            updateCostEstimation(currentProvider, $modelSelect.val());

            const helpTexts = {
                'openai': 'Auf <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>',
                'anthropic': 'Auf <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>',
                'gemini': 'Auf <a href="https://makersuite.google.com/app/apikey" target="_blank">makersuite.google.com</a>'
            };
            $('#api-key-help').html(helpTexts[currentProvider] || '');

            const providerInfo = {
                'openai': { title: '📊 OpenAI GPT:', features: ['Günstig & bewährt', 'Schnell & zuverlässig'] },
                'anthropic': { title: '📊 Anthropic Claude:', features: ['Exzellente Textqualität', 'Sehr präzise'] },
                'gemini': { title: '📊 Google Gemini:', features: ['Innovative Technologie', 'Sehr kostengünstig'] }
            };
            const info = providerInfo[currentProvider];
            if (info) {
                $('#current-provider-title').text(info.title);
                $('#current-provider-info').html(`<ul>${info.features.map(f => `<li>${f}</li>`).join('')}</ul>`);
            }
        };

        $('#ai-api-key').val(api_keys[currentProvider] || '');
        updateProviderUI();

        $('#ai-provider').off('change.retexify').on('change.retexify', function() {
            currentProvider = $(this).val();
            $('#ai-api-key').val(api_keys[currentProvider] || '');
            updateProviderUI();
        });
        
        $('#ai-model').off('change.retexify').on('change.retexify', function() {
            updateCostEstimation(currentProvider, $(this).val());
        });

        $('#retexify-ai-settings-form').off('submit.retexify').on('submit.retexify', function(e) {
            e.preventDefault();
            ajaxHelper('retexify_ai_save_settings', $(this).serialize(), 'Speichere Einstellungen...', $('#retexify-ai-settings-result'), (res) => {
                showNotification(res.data, 'success');
                api_keys[currentProvider] = $('#ai-api-key').val();
            }, e);
        });

        $('#retexify-ai-test-connection').off('click.retexify').on('click.retexify', function(e) {
             const apiKey = $('#ai-api-key').val();
             // Wichtig: Erst den Schlüssel speichern, dann testen.
             ajaxHelper('retexify_save_api_key', { provider: currentProvider, api_key: apiKey }, 'Speichere API-Key...', null, (res) => {
                api_keys[currentProvider] = apiKey; // Lokal aktualisieren
                ajaxHelper('retexify_ai_test_connection', {}, 'Teste Verbindung...', $('#retexify-ai-settings-result'), (res) => {
                     showNotification(res.data, 'success');
                });
             }, e);
        });

        // Kanton-Buttons
        const setupCantonButtons = () => {
             $('#retexify-select-all-cantons').off('click.retexify').on('click.retexify', () => $('.retexify-canton-item input').prop('checked', true));
             $('#retexify-select-main-cantons').off('click.retexify').on('click.retexify', () => {
                $('.retexify-canton-item input').prop('checked', false);
                ['ZH', 'BE', 'LU', 'GE', 'VD', 'BS'].forEach(c => $(`input[value="${c}"]`).prop('checked', true));
             });
             $('#retexify-clear-cantons').off('click.retexify').on('click.retexify', () => $('.retexify-canton-item input').prop('checked', false));
        };
        setupCantonButtons();
    }
    
    function updateCostEstimation(provider, model) {
        $('.retexify-cost-estimation').remove();
        if (!model) return;
        const estimates = window.retexify_ajax.costEstimates || {};
        const costs = estimates[provider]?.[model];
        if (costs) {
            const costHtml = `<div class="retexify-cost-estimation"><h5>💰 Schätzung:</h5><div class="retexify-cost-grid">` +
                `<div class="retexify-cost-item"><span class="retexify-cost-value">$${costs.perRequest}</span><span class="retexify-cost-label">/ Suite</span></div>` +
                `<div class="retexify-cost-item"><span class="retexify-cost-value">${costs.speed}</span><span class="retexify-cost-label">Speed</span></div>` +
                `<div class="retexify-cost-item"><span class="retexify-cost-value">${costs.quality}</span><span class="retexify-cost-label">Qualität</span></div>` +
                `</div></div>`;
            $('#ai-model').closest('.retexify-field').after(costHtml);
        }
    }
    
    // =========================================================================
    // Manueller Export/Import
    // =========================================================================
    function initializeManualExportImport() {
        const $statsContainer = $('.retexify-manual-stats');
        if ($statsContainer.is(':empty') || $statsContainer.find('.retexify-error').length) {
            loadManualStats();
        }

        const container = '#tab-manual-export-import';
        $(document)
            .off('.manual')
            .on('click.manual', `${container} #retexify-refresh-manual-stats`, (e) => loadManualStats(e))
            .on('click.manual', `${container} #retexify-export-form button[type='submit']`, (e) => { e.preventDefault(); handleExport(e); })
            .on('click.manual', `${container} label[for="import-file"]`, () => $(`${container} #import-file`).click())
            .on('change.manual', `${container} #import-file`, function() {
                const fileName = $(this).val().split('\\').pop();
                $(`${container} #import-file-name`).text(fileName || 'Keine Datei ausgewählt').toggleClass('active', !!fileName);
                $(`${container} #retexify-import-submit`).prop('disabled', !fileName);
            })
            .on('submit.manual', `${container} #retexify-import-form`, (e) => { e.preventDefault(); handleImport(e); });
    }

    function loadManualStats(event = null) {
        ajaxHelper('retexify_get_manual_export_stats', {}, 'Lade Statistiken...', $('.retexify-manual-stats'), (res) => {
            const $statsContainer = $('.retexify-manual-stats');
            let statsHtml = res.data?.length > 0
                ? res.data.map(stat => `<div class="retexify-manual-stat-item"><span class="dashicons ${stat.icon}"></span><span class="retexify-stat-label">${stat.label}:</span><span class="retexify-stat-count">${stat.count}</span></div>`).join('')
                : '<p>Keine exportierbaren Inhalte gefunden.</p>';
            $statsContainer.html(statsHtml);
        }, event);
    }

    function handleExport(event) {
        const $form = $('#retexify-export-form');
        const data = {
            export_post_types: $form.find('input[name="export_post_types[]"]:checked').map((_, el) => $(el).val()).get(),
            export_status: $form.find('input[name="export_status[]"]:checked').map((_, el) => $(el).val()).get(),
            export_content: $form.find('input[name="export_content[]"]:checked').map((_, el) => $(el).val()).get(),
        };

        ajaxHelper('retexify_manual_export', data, 'Export wird vorbereitet...', $('#retexify-manual-results'), (res) => {
            const successMessage = `✅ ${res.data.message}<br><a href="${res.data.file_url}" class="retexify-btn retexify-btn-success" download><span class="dashicons dashicons-download"></span> CSV Herunterladen</a>`;
            showManualResult(successMessage, 'success');
        }, event);
    }

    function handleImport(event) {
        const $form = $('#retexify-import-form');
        const fileInput = $form.find('#import-file')[0];
        if (!fileInput.files?.length) {
            showManualResult('Bitte wählen Sie zuerst eine CSV-Datei aus.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'retexify_manual_import');
        formData.append('nonce', window.retexify_ajax.nonce);
        formData.append('import_file', fileInput.files[0]);

        const $button = $(event.target).closest('form').find('button');
        const originalButtonText = $button.html();
        $button.html('📥 Importiere...').prop('disabled', true);
        showManualResult('Datei wird hochgeladen und verarbeitet...', 'loading');

        $.ajax({
            url: window.retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (res) => {
                if (res.success) {
                    showManualResult(`✅ Import erfolgreich: ${res.data.message}`, 'success');
                    $form[0].reset();
                    $('#import-file-name').text('Keine Datei ausgewählt').removeClass('active');
                    $button.prop('disabled', true);
                    loadManualStats(); // Statistiken nach erfolgreichem Import aktualisieren
                } else {
                    showManualResult(`❌ Import fehlgeschlagen: ${res.data}`, 'error');
                }
            },
            error: (xhr) => showManualResult(`❌ Fehler: ${xhr.statusText}`, 'error'),
            complete: () => $button.html(originalButtonText).prop('disabled', false)
        });
    }

    // =========================================================================
    // Helper-Funktionen
    // =========================================================================
    function ajaxHelper(action, data, loadingMessage, resultContainer, successCallback, event = null) {
        const $btn = event ? $(event.target).closest('button, .retexify-header-badge') : null;
        const originalButtonText = $btn?.html();
        const isBadge = $btn?.hasClass('retexify-header-badge');

        if ($btn && !isBadge) $btn.html('⏳').prop('disabled', true);
        else if (isBadge) $btn.addClass('retexify-loading-badge');

        if (resultContainer) {
            resultContainer.html(`<div class="retexify-result-message loading">${loadingMessage}</div>`).show();
        } else {
            showNotification(loadingMessage, 'loading');
        }

        $.ajax({
            url: window.retexify_ajax.ajax_url,
            type: 'POST',
            data: { action, nonce: window.retexify_ajax.nonce, ...data },
            success: (res) => {
                if (res.success) {
                    if (successCallback) successCallback(res);
                    
                    if (resultContainer && !['retexify_get_stats', 'retexify_test', 'retexify_get_manual_export_stats'].includes(action)) {
                        resultContainer.empty();
                    }

                    if (!['retexify_get_stats', 'retexify_test', 'retexify_load_seo_content', 'retexify_get_manual_export_stats'].includes(action)) {
                        showNotification('Aktion erfolgreich!', 'success');
                    }
                } else {
                    const error = res.data || 'Unbekannter Fehler';
                    if (resultContainer) resultContainer.html(`<div class="retexify-result-message error">❌ ${error}</div>`);
                    showNotification(`Fehler: ${error}`, 'error');
                }
            },
            error: (xhr) => {
                const error = xhr.responseText || xhr.statusText;
                if (resultContainer) resultContainer.html(`<div class="retexify-result-message error">❌ Fehler: ${error}</div>`);
                showNotification('Ein schwerwiegender AJAX-Fehler ist aufgetreten.', 'error');
            },
            complete: () => {
                if ($btn && !isBadge) $btn.html(originalButtonText).prop('disabled', false);
                else if (isBadge) $btn.removeClass('retexify-loading-badge');
            }
        });
    }

    function showNotification(message, type = 'info') {
        let $container = $('#retexify-notifications');
        if ($container.length === 0) {
            $container = $('<div id="retexify-notifications"></div>').appendTo('body');
        }
        const $notification = $(`<div class="retexify-notification ${type}">${message}</div>`).appendTo($container);
        setTimeout(() => $notification.fadeOut(400, () => $notification.remove()), 5000);
    }
    
    function showManualResult(message, type) {
        const $resultsDiv = $('#retexify-manual-results');
        $resultsDiv.html(`<div class="retexify-result-message ${type}">${message}</div>`).show();
    }
});