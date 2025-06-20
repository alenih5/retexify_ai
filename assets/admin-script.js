/**
 * Retexify_AI Plugin - Admin JavaScript
 * Author: Imponi
 */

jQuery(document).ready(function($) {
    
    // Initialisierung
    initializeRetexifyAIInterface();
    
    function initializeRetexifyAIInterface() {
        // Tab-System initialisieren
        initializeTabs();
        
        // Sofort beim Laden initialisieren
        checkWPBakeryStatus();
        loadDashboard();
        updateContentCounts();
        loadPostTypes();
        initializeAISettings();
        
        // Vorschau automatisch beim Laden anzeigen
        updatePreview();
        
        // Event-Listener für Checkbox-Änderungen
        $('.retexify-post-type-checkbox, .retexify-content-checkbox, .retexify-status-checkbox').on('change', function() {
            updatePreview();
        });
        
        // Smooth Animationen für Checkboxen
        $('.retexify-checkbox-item').each(function(index) {
            $(this).css('opacity', '0').delay(index * 50).animate({
                opacity: 1
            }, 300);
        });
    }
    
    function initializeTabs() {
        $(".retexify-tab-btn").on("click", function() {
            var targetTab = $(this).data("tab");
            
            // Tab-Buttons
            $(".retexify-tab-btn").removeClass("active");
            $(this).addClass("active");
            
            // Tab-Content
            $(".retexify-tab-content").removeClass("active");
            $("#tab-" + targetTab).addClass("active");
            
            // URL-Hash setzen für Deep-Linking
            if (history.pushState) {
                history.pushState(null, null, '#' + targetTab);
            }
        });
        
        // Deep-Linking: Tab basierend auf URL-Hash aktivieren
        var hash = window.location.hash.replace('#', '');
        if (hash && $("#tab-" + hash).length) {
            $(".retexify-tab-btn[data-tab='" + hash + "']").click();
        }
    }
    
    function initializeAISettings() {
        // Temperature Slider
        $("#ai-temperature").on("input", function() {
            $("#temperature-value").text($(this).val());
        });
        
        // Quality Threshold Slider
        $("#ai-quality-threshold").on("input", function() {
            $("#quality-value").text($(this).val() + "%");
        });
        
        // API Provider Change Event
        $("#ai-provider").on("change", function() {
            var provider = $(this).val();
            updateModelOptionsForProvider(provider);
        });
        
        // KI-Bereiche basierend auf Aktivierung zeigen/verstecken
        var aiEnabled = retexify_ajax.ai_enabled;
        toggleAIFeatures(aiEnabled);
        
        // Einstellungsformular Auto-Save beim Verlassen der Seite
        $(window).on('beforeunload', function() {
            if ($("#retexify-ai-settings-form").hasClass('changed')) {
                return 'Sie haben ungespeicherte Änderungen. Möchten Sie die Seite wirklich verlassen?';
            }
        });
        
        // Änderungen verfolgen
        $("#retexify-ai-settings-form input, #retexify-ai-settings-form select").on('change', function() {
            $("#retexify-ai-settings-form").addClass('changed');
        });
    }
    
    function updateModelOptionsForProvider(provider) {
        var $modelSelect = $("#ai-model");
        $modelSelect.empty();
        
        var models = {
            'openai': [
                { value: 'gpt-3.5-turbo', text: 'GPT-3.5 Turbo (Schnell & Günstig)' },
                { value: 'gpt-4', text: 'GPT-4 (Höchste Qualität)' },
                { value: 'gpt-4-turbo', text: 'GPT-4 Turbo (Balanced)' },
                { value: 'gpt-4o', text: 'GPT-4o (Neuestes Modell)' }
            ],
            'anthropic': [
                { value: 'claude-3-haiku', text: 'Claude 3 Haiku (Schnell)' },
                { value: 'claude-3-sonnet', text: 'Claude 3 Sonnet (Balanced)' },
                { value: 'claude-3-opus', text: 'Claude 3 Opus (Beste Qualität)' }
            ],
            'google': [
                { value: 'gemini-pro', text: 'Gemini Pro' },
                { value: 'gemini-pro-vision', text: 'Gemini Pro Vision' }
            ],
            'custom': [
                { value: 'custom-model', text: 'Custom Model' }
            ]
        };
        
        if (models[provider]) {
            models[provider].forEach(function(model) {
                $modelSelect.append('<option value="' + model.value + '">' + model.text + '</option>');
            });
        }
    }
    
    function toggleAIFeatures(enabled) {
        if (enabled) {
            $(".retexify-ai-section, .retexify-ai-feature, #retexify-ai-test-btn").show();
            $(".retexify-tab-btn[data-tab='ai-settings'], .retexify-tab-btn[data-tab='ai-tools']").show();
        } else {
            $(".retexify-ai-section, .retexify-ai-feature, #retexify-ai-test-btn").hide();
            $(".retexify-tab-btn[data-tab='ai-settings'], .retexify-tab-btn[data-tab='ai-tools']").show(); // Tabs immer zeigen für Konfiguration
        }
    }
    
    function checkWPBakeryStatus() {
        $.post(retexify_ajax.ajax_url, {
            action: "retexify_check_wpbakery",
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success && response.data.wpbakery_detected) {
                // WPBakery-Optionen anzeigen
                $("#retexify-wpbakery-option").show();
                $("#retexify-wpbakery-meta-title-option").show();
                $("#retexify-wpbakery-meta-content-option").show();
                $("#retexify-wpbakery-info").show();
                $("#retexify-wpbakery-import-info").show();
            } else {
                // WPBakery-Optionen verstecken
                $("#retexify-wpbakery-option").hide();
                $("#retexify-wpbakery-meta-title-option").hide();
                $("#retexify-wpbakery-meta-content-option").hide();
                $("#retexify-wpbakery-info").hide();
                $("#retexify-wpbakery-import-info").hide();
            }
        });
    }
    
    function loadDashboard() {
        $.post(retexify_ajax.ajax_url, {
            action: "retexify_get_stats",
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success) {
                $("#retexify-enhanced-dashboard").html(response.data);
            } else {
                $("#retexify-enhanced-dashboard").html("<div class=\"retexify-loading-dashboard\">❌ Fehler: " + response.data + "</div>");
            }
        }).fail(function() {
            $("#retexify-enhanced-dashboard").html("<div class=\"retexify-loading-dashboard\">❌ Verbindungsfehler</div>");
        });
    }
    
    function updateContentCounts() {
        $.post(retexify_ajax.ajax_url, {
            action: "retexify_get_counts",
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success) {
                var counts = response.data;
                
                // Status-Counts
                $("#count-publish").text(counts.status.publish || 0);
                $("#count-draft").text(counts.status.draft || 0);
                $("#count-private").text(counts.status.private || 0);
                
                // Content-Counts
                $("#count-title").text(counts.content.title || 0);
                $("#count-content").text(counts.content.content || 0);
                $("#count-meta-title").text(counts.content.meta_title || 0);
                $("#count-meta-desc").text(counts.content.meta_description || 0);
                $("#count-focus").text(counts.content.focus_keyphrase || 0);
                $("#count-wpbakery").text(counts.content.wpbakery_text || 0);
                $("#count-wpbakery-meta-title").text(counts.content.wpbakery_meta_title || 0);
                $("#count-wpbakery-meta-content").text(counts.content.wpbakery_meta_content || 0);
                $("#count-images").text(counts.content.alt_texts || 0);
                
                // Post-Typ-Counts
                if (counts.post_types) {
                    $("#retexify-post-types-grid .retexify-count").each(function() {
                        var postType = $(this).data("type");
                        if (postType && counts.post_types[postType] !== undefined) {
                            $(this).text(counts.post_types[postType]);
                        }
                    });
                }
            }
        });
    }
    
    function loadPostTypes() {
        // Post-Typen dynamisch laden
        var postTypesHtml = "";
        var defaultTypes = ["post", "page"];
        
        defaultTypes.forEach(function(type, index) {
            var checked = defaultTypes.includes(type) ? "checked" : "";
            var label = type === "post" ? "Beiträge" : "Seiten";
            
            postTypesHtml += "<label class=\"retexify-checkbox-item\">";
            postTypesHtml += "<input type=\"checkbox\" class=\"retexify-post-type-checkbox\" name=\"post_types[]\" value=\"" + type + "\" " + checked + ">";
            postTypesHtml += "<span class=\"retexify-checkbox-label\">" + label + " (<span class=\"retexify-count\" data-type=\"" + type + "\">0</span>)</span>";
            postTypesHtml += "</label>";
        });
        
        $("#retexify-post-types-grid").html(postTypesHtml);
    }
    
    // Vorschau automatisch aktualisieren
    function updatePreview() {
        var selections = getSelections();
        
        // Mindestens eine Auswahl erforderlich
        if (!selections || selections.post_types.length === 0 || 
            selections.content_types.length === 0 || 
            selections.post_status.length === 0) {
            $('#retexify-preview-result').hide().text('');
            return;
        }
        
        // AJAX-Request für Vorschau
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_preview',
                nonce: retexify_ajax.nonce,
                selections: JSON.stringify(selections)
            },
            success: function(response) {
                if (response.success) {
                    $('#retexify-preview-result').html(response.data).fadeIn(300);
                } else {
                    $('#retexify-preview-result').hide();
                }
            },
            error: function() {
                $('#retexify-preview-result').hide();
            }
        });
    }
    
    // SYSTEM BUTTONS
    $("#retexify-refresh-stats").on("click", function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop("disabled", true).html("<span class=\"dashicons dashicons-update retexify-spin\"></span> Aktualisiere...");
        
        checkWPBakeryStatus();
        loadDashboard();
        updateContentCounts();
        
        setTimeout(function() {
            $btn.prop("disabled", false).html(originalText);
        }, 2000);
    });
    
    $("#retexify-debug-btn").on("click", function() {
        executeTest("retexify_debug_export", $(this), "Erstelle Debug-Info...");
    });
    
    $("#retexify-test-btn").on("click", function() {
        executeTest("retexify_test", $(this), "System wird getestet...");
    });
    
    $("#retexify-wpbakery-btn").on("click", function() {
        executeTest("retexify_test", $(this), "WPBakery/Salient wird analysiert...");
    });
    
    $("#retexify-ai-test-btn").on("click", function() {
        executeTest("retexify_ai_test_connection", $(this), "KI-Verbindung wird getestet...");
    });
    
    // EXPORT/IMPORT BUTTONS
    $('#retexify-preview-btn').on('click', function() {
        var selections = getSelections();
        if (!selections) {
            alert('Bitte treffen Sie eine Auswahl.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Lade...');
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_preview',
            nonce: retexify_ajax.nonce,
            selections: JSON.stringify(selections)
        }, function(response) {
            if (response.success) {
                $('#retexify-preview-result').html(response.data).addClass('show');
            } else {
                alert('Vorschau-Fehler: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    $('#retexify-export-btn').on('click', function() {
        var selections = getSelections();
        if (!selections) {
            alert('Bitte wählen Sie mindestens einen Post-Typ und Inhaltstyp aus.');
            return;
        }
        
        // KI-Optionen sammeln
        var aiOptions = [];
        $('.retexify-ai-option:checked').each(function() {
            aiOptions.push($(this).val());
        });
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Exportiere...');
        
        // Progress Animation starten
        showProgress('export');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_export',
                nonce: retexify_ajax.nonce,
                selections: JSON.stringify(selections),
                ai_options: JSON.stringify(aiOptions)
            },
            timeout: 300000, // 5 Minuten Timeout für KI-Verarbeitung
            success: function(response) {
                hideProgress('export');
                $btn.prop('disabled', false).html(originalText);
                
                if (response.success) {
                    var result = '<div class="retexify-result success">';
                    result += '<h4>✅ ' + response.data.message + '</h4>';
                    result += '<p>Posts: ' + response.data.posts_exported + ' • Bilder: ' + response.data.images_exported + ' • CSV-Zeilen: ' + response.data.total_items + '</p>';
                    if (response.data.ai_enhanced) {
                        result += '<p style="color: #0066cc;">🤖 KI-Optimierungen angewendet</p>';
                    }
                    result += '<a href="' + response.data.download_url + '" class="retexify-download-link">';
                    result += '<span class="dashicons dashicons-download"></span> ' + response.data.filename + ' herunterladen</a>';
                    result += '</div>';
                    $('#retexify-export-result').html(result);
                    
                    // Erfolgs-Animation
                    animateSuccess($('#retexify-export-result'));
                    playSuccessSound();
                    
                    // Retexify_AI-Hinweis anzeigen
                    setTimeout(function() {
                        var formatHint = '<div class="retexify-ai-info-box">';
                        formatHint += '🤖 <strong>Retexify_AI:</strong> Content ohne WPBakery-Shortcodes • KI-Optimierungen verfügbar • WPBakery Meta getrennt<br>';
                        formatHint += '📊 <strong>Import-Regel:</strong> Nur "(Neu)"-Spalten werden importiert, KI-Spalten als Vorschläge';
                        formatHint += '</div>';
                        $('#retexify-export-result').append(formatHint);
                    }, 1000);
                    
                } else {
                    showError($('#retexify-export-result'), response.data || 'Unbekannter Fehler');
                }
            },
            error: function(xhr, status, error) {
                hideProgress('export');
                $btn.prop('disabled', false).html(originalText);
                var errorMsg = 'Export-Fehler: ';
                if (status === 'timeout') {
                    errorMsg += 'Timeout - Der Export dauerte zu lange. Versuchen Sie es mit weniger Inhalten oder deaktivieren Sie KI-Optionen.';
                } else {
                    errorMsg += error;
                }
                showError($('#retexify-export-result'), errorMsg);
            }
        });
    });
    
    // Import-Datei-Auswahl
    $('#retexify-select-file-btn').on('click', function() {
        $('#retexify-import-file').click();
    });
    
    $('#retexify-import-file').on('change', function() {
        var file = this.files[0];
        var $btn = $('#retexify-import-btn');
        var $info = $('#retexify-file-info');
        
        if (file) {
            var extension = file.name.toLowerCase().split('.').pop();
            var isValidFile = (extension === 'csv' || extension === 'xlsx');
            
            if (isValidFile) {
                $btn.prop('disabled', false);
                $('#retexify-import-preview-btn').prop('disabled', false);
                
                // Detaillierte Dateiinfo anzeigen
                var fileInfo = '📁 Datei ausgewählt: <strong>' + file.name + '</strong><br>';
                fileInfo += '<small>Größe: ' + formatFileSize(file.size) + ' • ';
                fileInfo += 'Typ: ' + (file.type || 'unbekannt') + ' • ';
                fileInfo += 'Geändert: ' + new Date(file.lastModified).toLocaleString() + '</small>';
                
                $('#retexify-file-name').html(fileInfo).fadeIn(300);
                
                // CSV-Validierung
                if (extension === 'csv') {
                    validateCSVFile(file);
                }
                
            } else {
                $btn.prop('disabled', true);
                $('#retexify-import-preview-btn').prop('disabled', true);
                $('#retexify-file-name').html('❌ Ungültiges Format. Bitte wählen Sie eine CSV- oder Excel-Datei (.xlsx) aus.')
                    .css('color', '#d63384').fadeIn(300);
            }
        } else {
            $btn.prop('disabled', true);
            $('#retexify-import-preview-btn').prop('disabled', true);
            $('#retexify-file-name').fadeOut(300).html('');
        }
    });
    
    $('#retexify-import-btn').on('click', function() {
        var fileInput = $('#retexify-import-file')[0];
        if (!fileInput.files[0]) {
            alert('Bitte wählen Sie zuerst eine CSV-Datei aus.');
            return;
        }
        
        if (!confirm('⚠️ WARNUNG: Dieser Import überschreibt bestehende Texte!\\n\\nMöchten Sie den Import durchführen?')) {
            return;
        }
        
        // KI-Import-Optionen sammeln
        var aiImportOptions = [];
        $('.retexify-ai-import-option:checked').each(function() {
            aiImportOptions.push($(this).val());
        });
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Importiere...');
        
        // Progress Animation starten
        showProgress('import');
        
        var formData = new FormData();
        formData.append('action', 'retexify_import');
        formData.append('nonce', retexify_ajax.nonce);
        formData.append('import_file', fileInput.files[0]);
        formData.append('ai_import_options', JSON.stringify(aiImportOptions));
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 300000, // 5 Minuten Timeout für KI-Verarbeitung
            success: function(response) {
                hideProgress('import');
                $btn.prop('disabled', false).html(originalText);
                
                if (response.success) {
                    var result = '<div class="retexify-result success">';
                    result += '<h4>✅ ' + response.data.message + '</h4>';
                    result += '<p>Posts: ' + response.data.posts_updated + ' • Meta: ' + response.data.meta_updated + ' • Content: ' + response.data.content_updated + ' • WPBakery: ' + response.data.wpbakery_updated + ' • Alt-Texte: ' + response.data.alt_texts_updated + '</p>';
                    if (response.data.ai_enhanced) {
                        result += '<p style="color: #0066cc;">🤖 KI-Validierung angewendet</p>';
                    }
                    result += '</div>';
                    $('#retexify-import-result').html(result);
                    
                    // UI zurücksetzen
                    $('#retexify-import-file').val('');
                    $('#retexify-file-name').fadeOut(300).html('');
                    $('#retexify-import-preview').hide();
                    $('#retexify-import-preview-btn').prop('disabled', true);
                    $btn.prop('disabled', true);
                    
                    // Erfolgs-Animation
                    animateSuccess($('#retexify-import-result'));
                    playSuccessSound();
                    
                    // Dashboard aktualisieren
                    setTimeout(function() {
                        loadDashboard();
                        updateContentCounts();
                    }, 1000);
                    
                    // Seite nach 3 Sekunden neu laden um aktuelle Daten zu zeigen
                    setTimeout(function() {
                        if (confirm('Import erfolgreich! Seite neu laden um aktuelle Statistiken zu sehen?')) {
                            location.reload();
                        }
                    }, 3000);
                    
                } else {
                    showError($('#retexify-import-result'), response.data || 'Unbekannter Fehler');
                }
            },
            error: function(xhr, status, error) {
                hideProgress('import');
                $btn.prop('disabled', false).html(originalText);
                
                var errorMsg = 'Import-Fehler: ';
                if (status === 'timeout') {
                    errorMsg += 'Timeout - Der Import dauerte zu lange. Versuchen Sie es mit einer kleineren Datei oder deaktivieren Sie KI-Optionen.';
                } else {
                    errorMsg += error;
                }
                showError($('#retexify-import-result'), errorMsg);
            }
        });
    });
    
    // KI-EINSTELLUNGEN EVENTS
    $('#retexify-ai-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Speichere...');
        
        var formData = new FormData($form[0]);
        formData.append('action', 'retexify_ai_save_settings');
        formData.append('nonce', retexify_ajax.nonce);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#retexify-ai-settings-result').html('<div class="retexify-result success">✅ ' + response.data + '</div>');
                    $form.removeClass('changed');
                    
                    // KI-Bereiche aktualisieren
                    setTimeout(function() {
                        if (confirm('Einstellungen gespeichert! Seite neu laden um Änderungen zu aktivieren?')) {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    $('#retexify-ai-settings-result').html('<div class="retexify-result error">❌ ' + response.data + '</div>');
                }
            },
            error: function() {
                $('#retexify-ai-settings-result').html('<div class="retexify-result error">❌ Verbindungsfehler</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    $('#retexify-ai-test-connection').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Teste...');
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_ai_test_connection',
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#retexify-ai-settings-result').html('<div class="retexify-result success">' + response.data + '</div>');
            } else {
                $('#retexify-ai-settings-result').html('<div class="retexify-result error">' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    // KI-TOOLS EVENTS
    $('#ai-optimize-text-btn').on('click', function() {
        var text = $('#ai-input-text').val();
        var type = $('input[name=ai-optimize-type]:checked').val();
        
        if (!text.trim()) {
            alert('Bitte geben Sie einen Text ein.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Optimiere...');
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_ai_optimize_text',
            nonce: retexify_ajax.nonce,
            text: text,
            type: type
        }, function(response) {
            if (response.success) {
                var result = '<div class="retexify-ai-result">';
                result += '<h4>🤖 Optimiertes Ergebnis:</h4>';
                result += '<div class="retexify-ai-comparison">';
                result += '<div class="retexify-ai-original"><strong>Original:</strong><br>' + escapeHtml(response.data.original) + '</div>';
                result += '<div class="retexify-ai-optimized"><strong>Optimiert:</strong><br>' + escapeHtml(response.data.optimized) + '</div>';
                result += '</div>';
                if (response.data.improvement) {
                    result += '<div class="retexify-ai-score">Verbesserungs-Score: ' + response.data.improvement + '%</div>';
                }
                result += '<button type="button" class="button retexify-copy-result" data-text="' + escapeHtml(response.data.optimized) + '">📋 Ergebnis kopieren</button>';
                result += '</div>';
                $('#ai-optimize-result').html(result);
            } else {
                $('#ai-optimize-result').html('<div class="retexify-result error">❌ ' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    $('#ai-translate-btn').on('click', function() {
        var text = $('#ai-translate-text').val();
        var fromLang = $('#ai-translate-from').val();
        var toLang = $('#ai-translate-to').val();
        
        if (!text.trim()) {
            alert('Bitte geben Sie einen Text ein.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Übersetze...');
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_ai_translate_text',
            nonce: retexify_ajax.nonce,
            text: text,
            from_lang: fromLang,
            to_lang: toLang
        }, function(response) {
            if (response.success) {
                var result = '<div class="retexify-ai-result">';
                result += '<h4>🌍 Übersetzung:</h4>';
                result += '<div class="retexify-ai-comparison">';
                result += '<div class="retexify-ai-original"><strong>Original (' + response.data.from_language + '):</strong><br>' + escapeHtml(response.data.original) + '</div>';
                result += '<div class="retexify-ai-optimized"><strong>Übersetzt (' + response.data.to_language + '):</strong><br>' + escapeHtml(response.data.translated) + '</div>';
                result += '</div>';
                result += '<button type="button" class="button retexify-copy-result" data-text="' + escapeHtml(response.data.translated) + '">📋 Übersetzung kopieren</button>';
                result += '</div>';
                $('#ai-translate-result').html(result);
            } else {
                $('#ai-translate-result').html('<div class="retexify-result error">❌ ' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    $('#ai-generate-meta-btn').on('click', function() {
        var content = $('#ai-meta-content').val();
        var keyword = $('#ai-meta-keyword').val();
        
        if (!content.trim()) {
            alert('Bitte geben Sie Content oder Stichworte ein.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Generiere...');
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_ai_generate_meta',
            nonce: retexify_ajax.nonce,
            content: content,
            keyword: keyword
        }, function(response) {
            if (response.success) {
                var result = '<div class="retexify-ai-result">';
                result += '<h4>🎯 Generierte Meta-Daten:</h4>';
                
                if (response.data.title) {
                    result += '<div class="retexify-meta-item">';
                    result += '<strong>Meta-Titel:</strong>';
                    result += '<div class="retexify-meta-content">' + escapeHtml(response.data.title) + '</div>';
                    result += '<button type="button" class="button-small retexify-copy-result" data-text="' + escapeHtml(response.data.title) + '">📋</button>';
                    result += '</div>';
                }
                
                if (response.data.description) {
                    result += '<div class="retexify-meta-item">';
                    result += '<strong>Meta-Beschreibung:</strong>';
                    result += '<div class="retexify-meta-content">' + escapeHtml(response.data.description) + '</div>';
                    result += '<button type="button" class="button-small retexify-copy-result" data-text="' + escapeHtml(response.data.description) + '">📋</button>';
                    result += '</div>';
                }
                
                if (response.data.keywords) {
                    result += '<div class="retexify-meta-item">';
                    result += '<strong>Keywords:</strong>';
                    result += '<div class="retexify-meta-content">' + escapeHtml(response.data.keywords) + '</div>';
                    result += '<button type="button" class="button-small retexify-copy-result" data-text="' + escapeHtml(response.data.keywords) + '">📋</button>';
                    result += '</div>';
                }
                
                result += '</div>';
                $('#ai-meta-result').html(result);
            } else {
                $('#ai-meta-result').html('<div class="retexify-result error">❌ ' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    $('#ai-bulk-process-btn').on('click', function() {
        var postType = $('#ai-bulk-post-type').val();
        var limit = $('#ai-bulk-limit').val();
        var action = $('#ai-bulk-action').val();
        
        if (!confirm('Bulk-Verarbeitung von ' + limit + ' ' + postType + ' starten?\\n\\nAktion: ' + action + '\\n\\nDies kann mehrere Minuten dauern.')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> Verarbeite...');
        
        // Bulk Progress anzeigen
        showBulkProgress(limit);
        
        $.post(retexify_ajax.ajax_url, {
            action: 'retexify_ai_bulk_process',
            nonce: retexify_ajax.nonce,
            post_type: postType,
            limit: limit,
            action_type: action
        }, function(response) {
            hideBulkProgress();
            
            if (response.success) {
                var result = '<div class="retexify-result success">';
                result += '<h4>✅ Bulk-Verarbeitung abgeschlossen</h4>';
                result += '<p><strong>Verarbeitet:</strong> ' + response.data.processed + ' von ' + response.data.total + ' Posts</p>';
                
                if (response.data.errors && response.data.errors.length > 0) {
                    result += '<details style="margin-top: 10px;"><summary style="cursor: pointer;">Fehler anzeigen (' + response.data.errors.length + ')</summary>';
                    result += '<ul style="margin: 10px 0; padding-left: 20px; max-height: 200px; overflow-y: auto;">';
                    response.data.errors.forEach(function(error) {
                        result += '<li>' + escapeHtml(error) + '</li>';
                    });
                    result += '</ul></details>';
                }
                
                result += '</div>';
                $('#ai-bulk-result').html(result);
                
                // Dashboard nach Bulk-Operation aktualisieren
                setTimeout(function() {
                    loadDashboard();
                    updateContentCounts();
                }, 2000);
            } else {
                $('#ai-bulk-result').html('<div class="retexify-result error">❌ ' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
    
    // Copy-to-Clipboard Funktionalität
    $(document).on('click', '.retexify-copy-result', function() {
        var text = $(this).data('text');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showNotification('✅ In Zwischenablage kopiert', 'success');
            });
        } else {
            // Fallback für ältere Browser
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showNotification('✅ In Zwischenablage kopiert', 'success');
            } catch (err) {
                showNotification('❌ Kopieren fehlgeschlagen', 'error');
            }
            document.body.removeChild(textArea);
        }
    });
    
    // CSV-Datei vorab validieren
    function validateCSVFile(file) {
        if (file.size > 100 * 1024) return; // Nur kleine Dateien validieren
        
        var reader = new FileReader();
        reader.onload = function(e) {
            var content = e.target.result;
            var lines = content.split('\n');
            
            if (lines.length < 2) {
                showNotification('CSV-Datei scheint leer oder unvollständig zu sein.', 'warning');
                return;
            }
            
            var header = lines[0];
            var expectedColumns = ['ID', 'Typ', 'URL', 'Titel'];
            var hasValidHeader = expectedColumns.some(col => header.includes(col));
            
            if (!hasValidHeader) {
                showNotification('CSV-Header nicht erkannt. Stellen Sie sicher, dass die Datei von diesem Plugin exportiert wurde.', 'warning');
            } else if (retexify_ajax.debug) {
                console.log('CSV-Validierung erfolgreich - Header erkannt:', header.substring(0, 100));
            }
        };
        
        reader.readAsText(file.slice(0, 1024)); // Nur erste 1KB lesen
    }
    
    // Hilfsfunktionen
    function getSelections() {
        var post_types = [];
        $('.retexify-post-type-checkbox:checked').each(function() {
            post_types.push($(this).val());
        });
        
        var content_types = [];
        $('.retexify-content-checkbox:checked').each(function() {
            content_types.push($(this).val());
        });
        
        var post_status = [];
        $('.retexify-status-checkbox:checked').each(function() {
            post_status.push($(this).val());
        });
        
        if (post_types.length === 0 || content_types.length === 0) {
            return null;
        }
        
        if (post_status.length === 0) {
            post_status = ['publish'];
        }
        
        return {
            post_types: post_types,
            content_types: content_types,
            post_status: post_status
        };
    }
    
    function executeTest(action, $btn, loadingText) {
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update retexify-spin"></span> ' + loadingText);
        
        $.post(retexify_ajax.ajax_url, {
            action: action,
            nonce: retexify_ajax.nonce
        }, function(response) {
            if (response.success) {
                $('#retexify-test-result').html(response.data);
            } else {
                $('#retexify-test-result').html('<div class="retexify-result error">❌ Fehler: ' + response.data + '</div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    }
    
    function showProgress(type) {
        // Progress-Animation anzeigen
        var $container = type === 'export' ? $('#retexify-export-result') : $('#retexify-import-result');
        $container.html('<div class="retexify-progress"><div class="retexify-progress-bar"><div class="retexify-progress-fill"></div></div><div class="retexify-progress-text">Verarbeitung läuft...</div></div>');
    }
    
    function hideProgress(type) {
        // Progress-Animation verstecken
    }
    
    function showBulkProgress(totalItems) {
        $('#ai-bulk-result').html(
            '<div class="retexify-progress">' +
            '<div class="retexify-progress-bar"><div class="retexify-progress-fill" style="animation: retexify-progress-fill 10s ease-in-out;"></div></div>' +
            '<div class="retexify-progress-text">Verarbeite ' + totalItems + ' Posts mit KI...</div>' +
            '</div>'
        );
    }
    
    function hideBulkProgress() {
        // Progress wird durch Ergebnis ersetzt
    }
    
    function showError($result, message) {
        $result.html('<div class="retexify-result error">❌ <strong>Fehler:</strong><br>' + message + '</div>');
        
        // Shake-Animation für Fehler
        $result.effect && $result.effect('shake', { times: 3 }, 600);
    }
    
    function animateSuccess($element) {
        $element.fadeOut(300).fadeIn(300).fadeOut(300).fadeIn(300);
    }
    
    function showNotification(message, type) {
        var bgColor = '#d1e7dd';
        var textColor = '#0f5132';
        var icon = '✅';
        
        if (type === 'warning') {
            bgColor = '#fff3cd';
            textColor = '#856404';
            icon = '⚠️';
        } else if (type === 'error') {
            bgColor = '#f8d7da';
            textColor = '#842029';
            icon = '❌';
        }
        
        var $notification = $('<div>')
            .html('<strong>' + icon + ' ' + message + '</strong>')
            .css({
                'position': 'fixed',
                'top': '50px',
                'right': '20px',
                'background': bgColor,
                'color': textColor,
                'padding': '15px 20px',
                'border-radius': '6px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
                'z-index': '9999',
                'max-width': '400px',
                'font-size': '14px',
                'border': '1px solid ' + (type === 'warning' ? '#ffc107' : type === 'error' ? '#f5c2c7' : '#badbcc')
            });
        
        $('body').append($notification);
        
        $notification.fadeIn(300).delay(4000).fadeOut(300, function() {
            $(this).remove();
        });
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
    
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Erfolgs-Sound (optional)
    function playSuccessSound() {
        try {
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        } catch (e) {
            // Browser unterstützt Web Audio API nicht
        }
    }
    
    // Drag & Drop für Import-Datei
    var $importCard = $('.retexify-import-card');
    
    $importCard.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('retexify-drag-over');
    });
    
    $importCard.on('dragleave dragend', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('retexify-drag-over');
    });
    
    $importCard.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('retexify-drag-over');
        
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            var file = files[0];
            var extension = file.name.toLowerCase().split('.').pop();
            
            if (extension === 'csv' || extension === 'xlsx') {
                var $fileInput = $('#retexify-import-file')[0];
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                $fileInput.files = dataTransfer.files;
                
                $('#retexify-import-file').trigger('change');
                
                showNotification('Datei erfolgreich ausgewählt: ' + file.name, 'success');
            } else {
                showNotification('Bitte verwenden Sie eine CSV- oder Excel-Datei.', 'warning');
            }
        }
    });
    
    // Keyboard-Shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+E für Export
        if (e.ctrlKey && e.key === 'e' && !e.shiftKey && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            $('#retexify-export-btn').click();
        }
        
        // Ctrl+I für Import (wenn Datei ausgewählt)
        if (e.ctrlKey && e.key === 'i' && !e.shiftKey && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            if (!$('#retexify-import-btn').prop('disabled')) {
                $('#retexify-import-btn').click();
            }
        }
        
        // Ctrl+Shift+A für alle auswählen
        if (e.ctrlKey && e.shiftKey && e.key === 'A') {
            e.preventDefault();
            $('.retexify-post-type-checkbox, .retexify-content-checkbox, .retexify-status-checkbox').prop('checked', true);
            updatePreview();
        }
        
        // Ctrl+Shift+D für alle abwählen
        if (e.ctrlKey && e.shiftKey && e.key === 'D') {
            e.preventDefault();
            $('.retexify-post-type-checkbox, .retexify-content-checkbox, .retexify-status-checkbox').prop('checked', false);
            updatePreview();
        }
        
        // Escape für Tabs zurücksetzen
        if (e.key === 'Escape') {
            $('.retexify-tab-btn[data-tab="export-import"]').click();
        }
    });
    
    // Benachrichtigung wenn Seite verlassen wird während eines Prozesses
    window.addEventListener('beforeunload', function(e) {
        if ($('.button:disabled').length > 0) {
            var message = 'Ein Export, Import oder eine KI-Verarbeitung läuft noch. Möchten Sie die Seite wirklich verlassen?';
            e.returnValue = message;
            return message;
        }
    });
    
    // Smooth reveal animation für Cards
    $('.retexify-card').each(function(index) {
        $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)'
        }).delay(index * 150).animate({
            opacity: 1
        }, 400, function() {
            $(this).css('transform', 'translateY(0)');
        });
    });
    
    // Tooltip für bessere UX (falls jQuery UI verfügbar)
    if ($.fn.tooltip) {
        $('[title]').tooltip({
            position: { my: "center bottom-20", at: "center top" },
            hide: { duration: 200 }
        });
    }
    
    // Auto-Update der Vorschau alle 30 Sekunden (falls sich Inhalte geändert haben)
    setInterval(function() {
        if (!document.hidden && $('.retexify-post-type-checkbox:checked').length > 0) {
            updatePreview();
        }
    }, 30000);
    
    // Auto-Update der Counts alle 60 Sekunden
    setInterval(function() {
        if (!document.hidden) {
            updateContentCounts();
        }
    }, 60000);
    
    // Abschließende Initialisierung
    console.log('Retexify_AI Plugin von Imponi erfolgreich geladen');
    
    // Debug-Informationen ausgeben
    if (retexify_ajax.debug) {
        console.log('Debug-Modus aktiviert');
        console.log('AJAX URL:', retexify_ajax.ajax_url);
        console.log('Nonce:', retexify_ajax.nonce);
        console.log('KI aktiviert:', retexify_ajax.ai_enabled);
        
        // Debug-Button hinzufügen
        $('<button>')
            .text('🔍 Debug-Info')
            .addClass('button')
            .css('margin-left', '10px')
            .click(function() {
                showDebugInfo();
            })
            .insertAfter('#retexify-test-btn');
    }
    
    // Debug-Informationen anzeigen
    function showDebugInfo() {
        var info = 'Retexify_AI Plugin - Debug-Informationen\n\n';
        info += '=== PLUGIN-INFORMATIONEN ===\n';
        info += 'Plugin-Version: 2.5.0 (KI-Integration)\n';
        info += 'WordPress-URL: ' + window.location.origin + '\n';
        info += 'AJAX-URL: ' + retexify_ajax.ajax_url + '\n';
        info += 'Debug-Modus: ' + (retexify_ajax.debug ? 'Aktiv' : 'Inaktiv') + '\n';
        info += 'KI-Integration: ' + (retexify_ajax.ai_enabled ? 'Aktiv' : 'Inaktiv') + '\n\n';
        
        info += '=== SYSTEM-INFORMATIONEN ===\n';
        info += 'User-Agent: ' + navigator.userAgent + '\n';
        info += 'Bildschirmauflösung: ' + screen.width + 'x' + screen.height + '\n';
        info += 'Browser-Sprache: ' + navigator.language + '\n';
        info += 'Lokale Zeit: ' + new Date().toString() + '\n';
        info += 'Zeitzone: ' + Intl.DateTimeFormat().resolvedOptions().timeZone + '\n\n';
        
        info += '=== BROWSER-SUPPORT ===\n';
        info += 'File API Support: ' + (window.File && window.FileReader ? 'Ja' : 'Nein') + '\n';
        info += 'Drag & Drop Support: ' + ('draggable' in document.createElement('span') ? 'Ja' : 'Nein') + '\n';
        info += 'FormData Support: ' + (window.FormData ? 'Ja' : 'Nein') + '\n';
        info += 'Local Storage Support: ' + (window.localStorage ? 'Ja' : 'Nein') + '\n';
        info += 'Web Audio API Support: ' + (window.AudioContext || window.webkitAudioContext ? 'Ja' : 'Nein') + '\n';
        info += 'Clipboard API Support: ' + (navigator.clipboard ? 'Ja' : 'Nein') + '\n\n';
        
        info += '=== AKTUELLE AUSWAHL ===\n';
        var selections = getSelections();
        if (selections) {
            info += 'Post-Typen: ' + selections.post_types.join(', ') + '\n';
            info += 'Inhaltsfelder: ' + selections.content_types.join(', ') + '\n';
            info += 'Status: ' + selections.post_status.join(', ') + '\n';
        } else {
            info += 'Keine gültige Auswahl getroffen\n';
        }
        info += '\n';
        
        info += '=== PERFORMANCE ===\n';
        if (window.performance && window.performance.memory) {
            info += 'Heap-Größe: ' + Math.round(window.performance.memory.totalJSHeapSize / 1024 / 1024) + ' MB\n';
            info += 'Genutzter Heap: ' + Math.round(window.performance.memory.usedJSHeapSize / 1024 / 1024) + ' MB\n';
        }
        if (navigator.connection) {
            info += 'Connection Type: ' + navigator.connection.effectiveType + '\n';
            info += 'Downlink: ' + navigator.connection.downlink + ' Mbps\n';
        }
        
        // In neuem Fenster oder Alert anzeigen
        if (confirm('Debug-Informationen in neuem Fenster öffnen?\n\n(Abbrechen = In Zwischenablage kopieren)')) {
            var debugWindow = window.open('', 'debug', 'width=800,height=700,scrollbars=yes,resizable=yes');
            debugWindow.document.write('<html><head><title>Retexify_AI - Debug</title></head><body>');
            debugWindow.document.write('<h1>Retexify_AI Debug</h1>');
            debugWindow.document.write('<pre style="font-family: monospace; padding: 20px; font-size: 12px; line-height: 1.4; background: #f5f5f5; border-radius: 4px;">' + info + '</pre>');
            debugWindow.document.write('</body></html>');
        } else {
            // In Zwischenablage kopieren
            if (navigator.clipboard) {
                navigator.clipboard.writeText(info).then(function() {
                    showNotification('Debug-Informationen in Zwischenablage kopiert', 'success');
                });
            } else {
                // Fallback für ältere Browser
                var textArea = document.createElement('textarea');
                textArea.value = info;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    showNotification('Debug-Informationen in Zwischenablage kopiert', 'success');
                } catch (err) {
                    showNotification('Kopieren fehlgeschlagen', 'error');
                }
                document.body.removeChild(textArea);
            }
        }
    }
});

// CSS für Animationen und KI-Features dynamisch hinzufügen
const retexifyAICSS = `
    .retexify-spin {
        animation: retexify-spin 1s linear infinite;
    }
    
    @keyframes retexify-spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    @keyframes retexify-progress-fill {
        from { width: 0%; }
        to { width: 100%; }
    }
    
    .retexify-drag-over {
        border-color: #2271b1 !important;
        box-shadow: 0 0 15px rgba(34, 113, 177, 0.3) !important;
        transform: scale(1.02);
        transition: all 0.2s ease;
    }
    
    .retexify-drag-over .retexify-card-header {
        background: #e3f2fd !important;
    }
    
    .retexify-ai-result {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-top: 15px;
        border: 1px solid #e9ecef;
    }
    
    .retexify-ai-comparison {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin: 15px 0;
    }
    
    .retexify-ai-original,
    .retexify-ai-optimized {
        padding: 12px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .retexify-ai-original {
        background: #fff;
    }
    
    .retexify-ai-optimized {
        background: #e7f3ff;
        border-color: #b8daff;
    }
    
    .retexify-ai-score {
        text-align: center;
        margin: 10px 0;
        font-weight: 500;
        color: #646970;
    }
    
    .retexify-meta-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 15px;
        padding: 10px;
        background: #e7f3ff;
        border-radius: 4px;
        border: 1px solid #b8daff;
    }
    
    .retexify-meta-content {
        flex: 1;
        font-family: monospace;
        background: #fff;
        padding: 8px;
        border-radius: 3px;
        border: 1px solid #ddd;
    }
    
    .retexify-ai-info-box {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        padding: 12px;
        border-radius: 4px;
        margin-top: 10px;
        font-size: 13px;
        color: #155724;
    }
    
    .retexify-result {
        padding: 15px;
        border-radius: 6px;
        text-align: center;
        margin-top: 15px;
    }
    
    .retexify-result.success {
        background: #d1e7dd;
        border: 1px solid #badbcc;
        color: #0f5132;
    }
    
    .retexify-result.error {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        color: #842029;
    }
    
    .retexify-progress {
        text-align: center;
        margin: 20px 0;
    }
    
    .retexify-progress-bar {
        background: #f0f0f1;
        border-radius: 10px;
        height: 20px;
        margin: 10px auto;
        max-width: 400px;
        overflow: hidden;
        position: relative;
    }
    
    .retexify-progress-fill {
        background: linear-gradient(90deg, #2271b1, #135e96);
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 10px;
        position: relative;
        overflow: hidden;
    }
    
    .retexify-progress-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background-image: linear-gradient(
            -45deg,
            rgba(255, 255, 255, .2) 25%,
            transparent 25%,
            transparent 50%,
            rgba(255, 255, 255, .2) 50%,
            rgba(255, 255, 255, .2) 75%,
            transparent 75%,
            transparent
        );
        background-size: 40px 40px;
        animation: retexify-progress-animation 1s linear infinite;
    }
    
    @keyframes retexify-progress-animation {
        0% { background-position: 0 0; }
        100% { background-position: 40px 0; }
    }
    
    .retexify-progress-text {
        margin: 10px 0 0 0;
        color: #1d2327;
        font-size: 14px;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .retexify-ai-comparison {
            grid-template-columns: 1fr;
        }
        
        .retexify-meta-item {
            flex-direction: column;
        }
    }
`;

// CSS dynamisch hinzufügen
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = retexifyAICSS;
    document.head.appendChild(style);
}