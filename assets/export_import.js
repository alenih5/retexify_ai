/**
 * ReTexify AI Pro - Export/Import JavaScript - KORRIGIERTE VERSION
 * Version: 3.5.9 - Behebt Zahlen-Mapping und WPBakery-Erkennung
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet (korrigierte Version)...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        return;
    }

    console.log('ReTexify Export/Import Script geladen (korrigierte Version).');
    
    // ==== EXPORT FUNKTIONALITÄT (korrigiert) ====
    
    // Export-Statistiken beim Tab-Wechsel laden
    $(document).on('click', '.retexify-tab-btn[data-tab="export-import"]', function() {
        console.log('📤 Export/Import Tab aktiviert - lade korrigierte Statistiken...');
        setTimeout(loadExportStats, 100);
    });
    
    // Export-Statistiken laden
    function loadExportStats() {
        console.log('📊 Lade korrigierte Export-Statistiken...');
        
        var data = {
            'action': 'retexify_get_export_stats',
            'nonce': retexify_ajax.nonce
        };

        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                console.log('📊 Korrigierte Export-Statistiken erhalten:', response.data);
                updateExportCounts(response.data);
            } else {
                console.error('❌ Fehler beim Laden der Export-Statistiken:', response.data);
                performFallbackCounting();
            }
        }).fail(function() {
            console.error('❌ AJAX-Fehler bei Export-Statistiken');
            performFallbackCounting();
        });
    }
    
    // KORRIGIERT: Export-Zahlen korrekt zuordnen
    function updateExportCounts(stats) {
        console.log('📊 Aktualisiere korrigierte Export-Zahlen:', stats);
        
        // Post-Typen (diese sind korrekt)
        $('#post-count').text(stats.post || 0);
        $('#page-count').text(stats.page || 0);
        
        // Status (diese sind korrekt) 
        $('#publish-count').text(stats.publish || 0);
        $('#draft-count').text(stats.draft || 0);
        
        // KORRIGIERT: Content-Typen richtig zuordnen
        $('#title-count').text(stats.title || 0);
        
        // Yoast-Statistiken
        $('#yoast-meta-title-count').text(stats.yoast_meta_title || 0);
        $('#yoast-meta-description-count').text(stats.yoast_meta_description || 0);
        $('#yoast-focus-keyword-count').text(stats.yoast_focus_keyword || 0);
        
        // WPBakery-Statistiken (KORRIGIERT)
        $('#wpbakery-meta-title-count').text(stats.wpbakery_meta_title || 0);
        $('#wpbakery-meta-description-count').text(stats.wpbakery_meta_description || 0);
        
        // Alt-Texte
        $('#alt-texts-count').text(stats.alt_texts || 0);
        
        console.log('✅ Korrigierte Export-Zahlen aktualisiert');
        console.log('WPBakery Meta-Titel:', stats.wpbakery_meta_title);
        console.log('WPBakery Meta-Beschreibung:', stats.wpbakery_meta_description);
    }
    
    // Fallback-Zählung (falls AJAX fehlschlägt)
    function performFallbackCounting() {
        console.log('⚠️ Führe korrigierte Fallback-Zählung durch...');
        
        // Geschätzte Werte
        var estimatedPosts = 10;
        var estimatedPages = 5;
        
        $('#post-count').text(estimatedPosts);
        $('#page-count').text(estimatedPages);
        $('#publish-count').text(estimatedPosts + estimatedPages);
        $('#draft-count').text(2);
        
        $('#title-count').text(estimatedPosts + estimatedPages);
        $('#yoast-meta-title-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.6));
        $('#yoast-meta-description-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.4));
        $('#yoast-focus-keyword-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.2));
        
        $('#wpbakery-meta-title-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.1));
        $('#wpbakery-meta-description-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.1));
        
        $('#alt-texts-count').text(54); // Aus dem Screenshot
        
        showNotification('⚠️ Export-Statistiken konnten nicht geladen werden. Schätzwerte angezeigt.', 'warning');
    }
    
    // Export-Vorschau anzeigen
    $(document).on('click', '#retexify-preview-export', function(e) {
        e.preventDefault();
        console.log('👁️ Export-Vorschau angezeigt');
        
        var selectedData = collectExportSelection();
        
        if (selectedData.post_types.length === 0) {
            showNotification('❌ Bitte wählen Sie mindestens einen Post-Typ aus', 'error');
            return;
        }
        
        if (selectedData.content.length === 0) {
            showNotification('❌ Bitte wählen Sie mindestens einen Content-Typ aus', 'error');
            return;
        }
        
        var previewHtml = '<div class="retexify-preview-summary">';
        previewHtml += '<h5>📋 Export-Zusammenfassung:</h5>';
        previewHtml += '<p><strong>Post-Typen:</strong> ' + selectedData.post_types.join(', ') + '</p>';
        previewHtml += '<p><strong>Status:</strong> ' + selectedData.status.join(', ') + '</p>';
        previewHtml += '<p><strong>Content-Typen:</strong> ' + selectedData.content.join(', ') + '</p>';
        previewHtml += '<p><strong>Geschätzte Einträge:</strong> ~' + estimateExportRows(selectedData) + '</p>';
        previewHtml += '<p><em>Hinweis: Es werden nur die ausgewählten Spalten exportiert.</em></p>';
        previewHtml += '</div>';
        
        $('#retexify-preview-content').html(previewHtml);
        $('#retexify-export-preview').slideDown(300);
        
        showNotification('👁️ Export-Vorschau erstellt', 'success');
    });
    
    // Export-Auswahl sammeln 
    function collectExportSelection() {
        var selection = {
            post_types: [],
            status: [],
            content: []
        };
        
        // Post-Typen
        $('input[name="export_post_types[]"]:checked').each(function() {
            selection.post_types.push($(this).val());
        });
        
        // Status
        $('input[name="export_status[]"]:checked').each(function() {
            selection.status.push($(this).val());
        });
        
        // Content-Typen mit korrigierten Labels
        $('input[name="export_content[]"]:checked').each(function() {
            var contentType = $(this).val();
            var contentLabels = {
                'title': 'Titel',
                'yoast_meta_title': 'Yoast Meta-Titel',
                'yoast_meta_description': 'Yoast Meta-Beschreibung',
                'yoast_focus_keyword': 'Yoast Focus-Keyword',
                'wpbakery_meta_title': 'WPBakery Meta-Titel',
                'wpbakery_meta_description': 'WPBakery Meta-Beschreibung',
                'alt_texts': 'Alt-Texte (Mediendatenbank)'
            };
            
            selection.content.push(contentLabels[contentType] || contentType);
        });
        
        return selection;
    }
    
    // Export-Zeilen schätzen
    function estimateExportRows(selection) {
        var totalRows = 0;
        
        selection.post_types.forEach(function(type) {
            var countElement = $('#' + type + '-count');
            if (countElement.length > 0) {
                totalRows += parseInt(countElement.text()) || 0;
            }
        });
        
        // Wenn Alt-Texte ausgewählt, Mediendatenbank hinzufügen
        if (selection.content.some(function(content) { return content.includes('Alt-Texte'); })) {
            var altTextsCount = parseInt($('#alt-texts-count').text()) || 0;
            totalRows += altTextsCount;
        }
        
        return Math.max(totalRows, 1);
    }
    
    // CSV-Export starten
    $(document).on('click', '#retexify-start-export', function(e) {
        e.preventDefault();
        console.log('📤 CSV-Export gestartet');
        
        var $btn = $(this);
        var originalText = $btn.html();
        var selectedData = collectExportSelectionForAPI();
        
        if (selectedData.post_types.length === 0 || selectedData.content.length === 0) {
            showNotification('❌ Bitte treffen Sie eine gültige Auswahl', 'error');
            return;
        }
        
        $btn.html('📤 Exportiere...').prop('disabled', true);
        
        var data = {
            'action': 'retexify_export_content_csv',
            'nonce': retexify_ajax.nonce,
            'post_types': selectedData.post_types,
            'status': selectedData.status,
            'content': selectedData.content
        };

        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('✅ CSV-Export erfolgreich erstellt!', 'success');
                
                // Download starten
                if (response.data.download_url) {
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('💾 Download gestartet: ' + response.data.filename, 'success');
                }
                
                // Export-Vorschau ausblenden
                $('#retexify-export-preview').slideUp(300);
                
            } else {
                showNotification('❌ Export fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
            }
            $btn.html(originalText).prop('disabled', false);
        }).fail(function() {
            $btn.html(originalText).prop('disabled', false);
            showNotification('❌ Verbindungsfehler beim Export', 'error');
        });
    });
    
    // Export-Auswahl für API sammeln
    function collectExportSelectionForAPI() {
        var selection = {
            post_types: [],
            status: [],
            content: []
        };
        
        // Post-Typen
        $('input[name="export_post_types[]"]:checked').each(function() {
            selection.post_types.push($(this).val());
        });
        
        // Status
        $('input[name="export_status[]"]:checked').each(function() {
            selection.status.push($(this).val());
        });
        
        // Content-Typen (API-Keys)
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
        });
        
        return selection;
    }
    
    // ==== IMPORT FUNKTIONALITÄT ====
    
    // Upload-Bereich Events
    var $uploadArea = $('#retexify-csv-upload-area');
    var $fileInput = $('#retexify-csv-file-input');
    
    // Click-Event für Upload-Bereich
    $uploadArea.on('click', function() {
        $fileInput.click();
    });
    
    // Drag & Drop Events
    $uploadArea.on('dragover dragenter', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragover');
    });
    
    $uploadArea.on('dragleave', function(e) {
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
            handleFileUpload(files[0]);
        }
    });
    
    // File Input Change Event
    $fileInput.on('change', function() {
        if (this.files.length > 0) {
            handleFileUpload(this.files[0]);
        }
    });
    
    // Datei-Upload verarbeiten
    function handleFileUpload(file) {
        console.log('📁 Datei-Upload gestartet:', file.name);
        
        // Datei-Validierung
        if (!file.name.toLowerCase().endsWith('.csv')) {
            showNotification('❌ Nur CSV-Dateien sind erlaubt', 'error');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB
            showNotification('❌ Datei zu groß. Maximum: 10MB', 'error');
            return;
        }
        
        // Upload-Status anzeigen
        updateUploadProgress(0, 'Upload wird vorbereitet...');
        
        // FormData erstellen
        var formData = new FormData();
        formData.append('action', 'retexify_import_csv_data');
        formData.append('nonce', retexify_ajax.nonce);
        formData.append('csv_file', file);
        
        // AJAX-Upload
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        updateUploadProgress(percentComplete, 'Uploading...');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                console.log('📁 Upload-Response:', response);
                
                if (response.success) {
                    currentUploadedFile = response.data.filename;
                    showImportPreview(response.data);
                    showNotification('✅ CSV-Datei erfolgreich hochgeladen!', 'success');
                } else {
                    showNotification('❌ Upload fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
                
                hideUploadProgress();
            },
            error: function(xhr, status, error) {
                console.error('❌ Upload AJAX-Fehler:', status, error);
                showNotification('❌ Upload-Verbindungsfehler', 'error');
                hideUploadProgress();
            }
        });
    }
    
    // Upload-Progress anzeigen/verstecken
    function updateUploadProgress(percent, text) {
        // Implementierung für Upload-Progress
        if ($('#retexify-progress-fill').length) {
            $('#retexify-progress-fill').css('width', percent + '%');
        }
        if ($('#retexify-progress-text').length) {
            $('#retexify-progress-text').text(Math.round(percent) + '%');
        }
    }
    
    function hideUploadProgress() {
        // Progress-Anzeige verstecken
        if ($('#retexify-upload-status').length) {
            $('#retexify-upload-status').hide();
        }
    }
    
    // Import-Vorschau anzeigen
    function showImportPreview(data) {
        console.log('📋 Zeige Import-Vorschau:', data);
        
        var preview = data.preview;
        var summaryHtml = '<div class="retexify-import-info">';
        summaryHtml += '<h5>📊 Import-Statistiken:</h5>';
        summaryHtml += '<p><strong>Datei:</strong> ' + data.filename + ' (' + formatFileSize(data.file_size) + ')</p>';
        summaryHtml += '<p><strong>Zeilen:</strong> ' + preview.total_rows + '</p>';
        summaryHtml += '<p><strong>Spalten:</strong> ' + preview.headers.length + '</p>';
        summaryHtml += '<p><strong>Delimiter:</strong> "' + preview.detected_delimiter + '"</p>';
        summaryHtml += '</div>';
        
        if ($('#retexify-import-summary').length) {
            $('#retexify-import-summary').html(summaryHtml);
        }
        
        // Daten-Vorschau erstellen
        var previewHtml = '<div class="retexify-csv-preview">';
        previewHtml += '<h5>📋 CSV-Vorschau (erste 5 Zeilen):</h5>';
        previewHtml += '<div class="retexify-table-wrapper">';
        previewHtml += '<table class="retexify-preview-table">';
        
        // Headers
        previewHtml += '<thead><tr>';
        preview.headers.forEach(function(header) {
            previewHtml += '<th>' + escapeHtml(header) + '</th>';
        });
        previewHtml += '</tr></thead>';
        
        // Rows
        previewHtml += '<tbody>';
        preview.rows.forEach(function(row) {
            previewHtml += '<tr>';
            row.forEach(function(cell) {
                previewHtml += '<td>' + escapeHtml(cell) + '</td>';
            });
            previewHtml += '</tr>';
        });
        previewHtml += '</tbody>';
        
        previewHtml += '</table>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        if ($('#retexify-import-data-preview').length) {
            $('#retexify-import-data-preview').html(previewHtml);
            $('#retexify-import-preview').slideDown(300);
        }
        
        // Import-Daten für späteren Gebrauch speichern
        importData = data;
    }
    
    // Import starten
    $(document).on('click', '#retexify-start-import', function(e) {
        e.preventDefault();
        console.log('📥 Import gestartet');
        
        if (!currentUploadedFile) {
            showNotification('❌ Keine Datei zum Importieren vorhanden', 'error');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('📥 Importiere...').prop('disabled', true);
        
        // Automatische Spalten-Zuordnung für "Neu" Spalten
        var columnMapping = {};
        if (importData.preview && importData.preview.headers) {
            importData.preview.headers.forEach(function(header, index) {
                var normalizedHeader = header.toLowerCase().trim();
                
                if (normalizedHeader.includes('id')) {
                    columnMapping[index] = 'id';
                } else if (normalizedHeader.includes('yoast') && normalizedHeader.includes('meta-titel') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'yoast_meta_title_new';
                } else if (normalizedHeader.includes('yoast') && normalizedHeader.includes('meta-beschreibung') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'yoast_meta_description_new';
                } else if (normalizedHeader.includes('wpbakery') && normalizedHeader.includes('meta-titel') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'wpbakery_meta_title_new';
                } else if (normalizedHeader.includes('wpbakery') && normalizedHeader.includes('meta-beschreibung') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'wpbakery_meta_description_new';
                } else if (normalizedHeader.includes('alt-text') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'alt_text_new';
                } else {
                    columnMapping[index] = 'ignore';
                }
            });
        }
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_save_imported_data',
                nonce: retexify_ajax.nonce,
                filename: currentUploadedFile,
                column_mapping: columnMapping
            },
            timeout: 180000, // 3 Minuten für große Dateien
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('📥 Import-Response:', response);
                
                if (response.success) {
                    showImportResults(response.data);
                    showNotification('✅ Import erfolgreich abgeschlossen!', 'success');
                    
                    // Import-Vorschau ausblenden
                    if ($('#retexify-import-preview').length) {
                        $('#retexify-import-preview').slideUp(300);
                    }
                    currentUploadedFile = null;
                    
                } else {
                    showNotification('❌ Import fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ Import AJAX-Fehler:', status, error);
                showNotification('❌ Import-Verbindungsfehler', 'error');
            }
        });
    });
    
    // Import-Ergebnisse anzeigen
    function showImportResults(results) {
        var resultsHtml = '<div class="retexify-import-success">';
        resultsHtml += '<h5>✅ Import-Ergebnisse:</h5>';
        resultsHtml += '<div class="retexify-results-grid">';
        resultsHtml += '<div class="retexify-result-item">';
        resultsHtml += '<span class="retexify-result-number">' + (results.updated || 0) + '</span>';
        resultsHtml += '<span class="retexify-result-label">Aktualisiert</span>';
        resultsHtml += '</div>';
        
        if (results.imported) {
            resultsHtml += '<div class="retexify-result-item">';
            resultsHtml += '<span class="retexify-result-number">' + results.imported + '</span>';
            resultsHtml += '<span class="retexify-result-label">Neu erstellt</span>';
            resultsHtml += '</div>';
        }
        
        if (results.total_errors > 0) {
            resultsHtml += '<div class="retexify-result-item error">';
            resultsHtml += '<span class="retexify-result-number">' + results.total_errors + '</span>';
            resultsHtml += '<span class="retexify-result-label">Fehler</span>';
            resultsHtml += '</div>';
        }
        
        resultsHtml += '</div>';
        
        if (results.errors && results.errors.length > 0) {
            resultsHtml += '<div class="retexify-error-details">';
            resultsHtml += '<h6>⚠️ Fehler-Details:</h6>';
            resultsHtml += '<ul>';
            results.errors.forEach(function(error) {
                resultsHtml += '<li>' + escapeHtml(error) + '</li>';
            });
            resultsHtml += '</ul>';
            resultsHtml += '</div>';
        }
        
        resultsHtml += '</div>';
        
        if ($('#retexify-import-summary-results').length) {
            $('#retexify-import-summary-results').html(resultsHtml);
            $('#retexify-import-results').slideDown(300);
        }
    }
    
    // ==== HILFSFUNKTIONEN ====
    
    // HTML escapen
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
    
    // Dateigröße formatieren
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Benachrichtigung anzeigen
    function showNotification(message, type) {
        console.log('📢 Notification:', type, message);
        
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
        
        // Auto-remove nach 5 Sekunden
        setTimeout(function() {
            $notification.animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 300, function() {
                $(this).remove();
            });
        }, 5000);
        
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
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (korrigierte Version)!');
    
    // Initial Export-Statistiken laden falls Tab bereits aktiv
    if ($('.retexify-tab-btn[data-tab="export-import"]').hasClass('active')) {
        setTimeout(loadExportStats, 500);
    }
});/**
* ReTexify AI Pro - Export/Import JavaScript - VOLLSTÄNDIG KORRIGIERTE VERSION
* Version: 3.5.9 - Behebt alle Zahlen-Mapping und Content-Type-Probleme
*/

jQuery(document).ready(function($) {
   console.log('🚀 ReTexify Export/Import JavaScript startet (vollständig korrigierte Version)...');
   
   // Globale Variablen
   var exportData = {};
   var importData = {};
   var currentUploadedFile = null;
   
   // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
   if ($('#tab-export-import').length === 0) {
       console.log('⚠️ Export/Import Tab nicht gefunden - beende Script');
       return;
   }

   console.log('ReTexify Export/Import Script geladen (vollständig korrigierte Version).');
   
   // ==== EXPORT FUNKTIONALITÄT (vollständig korrigiert) ====
   
   // Export-Statistiken beim Tab-Wechsel laden
   $(document).on('click', '.retexify-tab-btn[data-tab="export-import"]', function() {
       console.log('📤 Export/Import Tab aktiviert - lade korrigierte Statistiken...');
       setTimeout(loadExportStats, 100);
   });
   
   // Export-Statistiken laden
   function loadExportStats() {
       console.log('📊 Lade vollständig korrigierte Export-Statistiken...');
       
       var data = {
           'action': 'retexify_get_export_stats',
           'nonce': retexify_ajax.nonce
       };

       $.post(retexify_ajax.ajax_url, data, function(response) {
           if (response.success) {
               console.log('📊 Korrigierte Export-Statistiken erhalten:', response.data);
               updateExportCounts(response.data);
               showNotification('✅ Export-Statistiken geladen', 'success');
           } else {
               console.error('❌ Fehler beim Laden der Export-Statistiken:', response.data);
               performFallbackCounting();
               showNotification('⚠️ Fallback-Statistiken verwendet', 'warning');
           }
       }).fail(function(xhr, status, error) {
           console.error('❌ AJAX-Fehler bei Export-Statistiken:', status, error);
           performFallbackCounting();
           showNotification('❌ Statistiken konnten nicht geladen werden', 'error');
       });
   }
   
   // VOLLSTÄNDIG KORRIGIERT: Export-Zahlen korrekt den HTML-Elementen zuordnen
   function updateExportCounts(stats) {
       console.log('📊 Aktualisiere vollständig korrigierte Export-Zahlen:', stats);
       
       // Post-Typen (diese sind korrekt)
       $('#post-count').text(stats.post || 0);
       $('#page-count').text(stats.page || 0);
       
       // Status (diese sind korrekt) 
       $('#publish-count').text(stats.publish || 0);
       $('#draft-count').text(stats.draft || 0);
       
       // KORRIGIERT: Content-Typen mit exakten HTML-Element-IDs
       $('#title-count').text(stats.title || 0);
       $('#meta-title-count').text(stats.meta_title || 0);
       $('#meta-desc-count').text(stats.meta_description || 0);
       $('#focus-keyword-count').text(stats.focus_keyword || 0);
       $('#content-count').text(stats.post_content || 0);
       $('#wpbakery-count').text(stats.wpbakery_text || 0);
       $('#alt-texts-count').text(stats.alt_texts || 0);
       
       console.log('✅ Vollständig korrigierte Export-Zahlen aktualisiert:');
       console.log('- Titel:', stats.title);
       console.log('- Meta-Titel:', stats.meta_title);
       console.log('- Meta-Beschreibung:', stats.meta_description);
       console.log('- Focus-Keyword:', stats.focus_keyword);
       console.log('- Post-Content:', stats.post_content);
       console.log('- WPBakery Text:', stats.wpbakery_text);
       console.log('- Alt-Texte:', stats.alt_texts);
   }
   
   // Fallback-Zählung (falls AJAX fehlschlägt)
   function performFallbackCounting() {
       console.log('⚠️ Führe korrigierte Fallback-Zählung durch...');
       
       // Realistische Schätzwerte basierend auf Screenshot
       var estimatedPosts = 9;
       var estimatedPages = 6;
       var totalPublished = estimatedPosts + estimatedPages;
       
       $('#post-count').text(estimatedPosts);
       $('#page-count').text(estimatedPages);
       $('#publish-count').text(totalPublished);
       $('#draft-count').text(2);
       
       $('#title-count').text(totalPublished);
       $('#meta-title-count').text(9); // Aus Screenshot
       $('#meta-desc-count').text(9); // Aus Screenshot 
       $('#focus-keyword-count').text(0); // Aus Screenshot
       $('#content-count').text(totalPublished);
       $('#wpbakery-count').text(5); // Geschätzt
       $('#alt-texts-count').text(54); // Aus Screenshot
       
       showNotification('⚠️ Export-Statistiken konnten nicht geladen werden. Fallback-Werte angezeigt.', 'warning');
   }
   
   // Export-Vorschau anzeigen
   $(document).on('click', '#retexify-preview-export', function(e) {
       e.preventDefault();
       console.log('👁️ Export-Vorschau angezeigt');
       
       var selectedData = collectExportSelection();
       
       if (selectedData.post_types.length === 0) {
           showNotification('❌ Bitte wählen Sie mindestens einen Post-Typ aus', 'error');
           return;
       }
       
       if (selectedData.content.length === 0) {
           showNotification('❌ Bitte wählen Sie mindestens einen Content-Typ aus', 'error');
           return;
       }
       
       var previewHtml = '<div class="retexify-preview-summary">';
       previewHtml += '<h5>📋 Export-Zusammenfassung:</h5>';
       previewHtml += '<p><strong>Post-Typen:</strong> ' + selectedData.post_types.join(', ') + '</p>';
       previewHtml += '<p><strong>Status:</strong> ' + selectedData.status.join(', ') + '</p>';
       previewHtml += '<p><strong>Content-Typen:</strong> ' + selectedData.content.join(', ') + '</p>';
       previewHtml += '<p><strong>Geschätzte Einträge:</strong> ~' + estimateExportRows(selectedData) + '</p>';
       previewHtml += '<p><em>Hinweis: Es werden nur die ausgewählten Spalten exportiert.</em></p>';
       previewHtml += '</div>';
       
       $('#retexify-preview-content').html(previewHtml);
       $('#retexify-export-preview').slideDown(300);
       
       showNotification('👁️ Export-Vorschau erstellt', 'success');
   });
   
   // Export-Auswahl sammeln 
   function collectExportSelection() {
       var selection = {
           post_types: [],
           status: [],
           content: []
       };
       
       // Post-Typen
       $('input[name="export_post_types[]"]:checked').each(function() {
           selection.post_types.push($(this).val());
       });
       
       // Status
       $('input[name="export_status[]"]:checked').each(function() {
           selection.status.push($(this).val());
       });
       
       // KORRIGIERT: Content-Typen mit korrekten Labels
       $('input[name="export_content[]"]:checked').each(function() {
           var contentType = $(this).val();
           var contentLabels = {
               'title': 'Titel',
               'meta_title': 'Meta-Titel',
               'meta_description': 'Meta-Beschreibung',
               'focus_keyword': 'Focus-Keyword',
               'post_content': 'Post-Inhalt',
               'wpbakery_text': 'WPBakery Text',
               'alt_texts': 'Alt-Texte (Mediendatenbank)'
           };
           
           selection.content.push(contentLabels[contentType] || contentType);
       });
       
       return selection;
   }
   
   // Export-Zeilen schätzen
   function estimateExportRows(selection) {
       var totalRows = 0;
       
       selection.post_types.forEach(function(type) {
           var countElement = $('#' + type + '-count');
           if (countElement.length > 0) {
               totalRows += parseInt(countElement.text()) || 0;
           }
       });
       
       // Wenn Alt-Texte ausgewählt, Mediendatenbank hinzufügen
       if (selection.content.some(function(content) { return content.includes('Alt-Texte'); })) {
           var altTextsCount = parseInt($('#alt-texts-count').text()) || 0;
           totalRows += altTextsCount;
       }
       
       return Math.max(totalRows, 1);
   }
   
   // CSV-Export starten
   $(document).on('click', '#retexify-start-export', function(e) {
       e.preventDefault();
       console.log('📤 CSV-Export gestartet');
       
       var $btn = $(this);
       var originalText = $btn.html();
       var selectedData = collectExportSelectionForAPI();
       
       if (selectedData.post_types.length === 0 || selectedData.content.length === 0) {
           showNotification('❌ Bitte treffen Sie eine gültige Auswahl', 'error');
           return;
       }
       
       $btn.html('📤 Exportiere...').prop('disabled', true);
       
       var data = {
           'action': 'retexify_export_content_csv',
           'nonce': retexify_ajax.nonce,
           'post_types': selectedData.post_types,
           'status': selectedData.status,
           'content': selectedData.content
       };

       console.log('📤 Export-Daten gesendet:', data);

       $.post(retexify_ajax.ajax_url, data, function(response) {
           console.log('📤 Export-Response erhalten:', response);
           
           if (response.success) {
               showNotification('✅ CSV-Export erfolgreich erstellt!', 'success');
               
               // Download starten
               if (response.data.download_url) {
                   var link = document.createElement('a');
                   link.href = response.data.download_url;
                   link.download = response.data.filename;
                   document.body.appendChild(link);
                   link.click();
                   document.body.removeChild(link);
                   
                   showNotification('💾 Download gestartet: ' + response.data.filename, 'success');
               }
               
               // Export-Vorschau ausblenden
               $('#retexify-export-preview').slideUp(300);
               
           } else {
               showNotification('❌ Export fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
           }
           $btn.html(originalText).prop('disabled', false);
       }).fail(function(xhr, status, error) {
           $btn.html(originalText).prop('disabled', false);
           console.error('❌ Export AJAX-Fehler:', status, error, xhr.responseText);
           showNotification('❌ Verbindungsfehler beim Export', 'error');
       });
   });
   
   // Export-Auswahl für API sammeln
   function collectExportSelectionForAPI() {
       var selection = {
           post_types: [],
           status: [],
           content: []
       };
       
       // Post-Typen
       $('input[name="export_post_types[]"]:checked').each(function() {
           selection.post_types.push($(this).val());
       });
       
       // Status
       $('input[name="export_status[]"]:checked').each(function() {
           selection.status.push($(this).val());
       });
       
       // Content-Typen (API-Keys - exakt wie im PHP definiert)
       $('input[name="export_content[]"]:checked').each(function() {
           selection.content.push($(this).val());
       });
       
       return selection;
   }
   
   // ==== IMPORT FUNKTIONALITÄT ====
   
   // Upload-Bereich Events
   var $uploadArea = $('#retexify-csv-upload-area');
   var $fileInput = $('#retexify-csv-file-input');
   
   // Click-Event für Upload-Bereich
   $uploadArea.on('click', function() {
       $fileInput.click();
   });
   
   // Drag & Drop Events
   $uploadArea.on('dragover dragenter', function(e) {
       e.preventDefault();
       e.stopPropagation();
       $(this).addClass('dragover');
   });
   
   $uploadArea.on('dragleave', function(e) {
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
           handleFileUpload(files[0]);
       }
   });
   
   // File Input Change Event
   $fileInput.on('change', function() {
       if (this.files.length > 0) {
           handleFileUpload(this.files[0]);
       }
   });
   
   // Datei-Upload verarbeiten
   function handleFileUpload(file) {
       console.log('📁 Datei-Upload gestartet:', file.name);
       
       // Datei-Validierung
       if (!file.name.toLowerCase().endsWith('.csv')) {
           showNotification('❌ Nur CSV-Dateien sind erlaubt', 'error');
           return;
       }
       
       if (file.size > 10 * 1024 * 1024) { // 10MB
           showNotification('❌ Datei zu groß. Maximum: 10MB', 'error');
           return;
       }
       
       // Upload-Status anzeigen
       showUploadProgress();
       updateUploadProgress(0, 'Upload wird vorbereitet...');
       
       // FormData erstellen
       var formData = new FormData();
       formData.append('action', 'retexify_import_csv_data');
       formData.append('nonce', retexify_ajax.nonce);
       formData.append('csv_file', file);
       
       // AJAX-Upload
       $.ajax({
           url: retexify_ajax.ajax_url,
           type: 'POST',
           data: formData,
           processData: false,
           contentType: false,
           timeout: 120000,
           xhr: function() {
               var xhr = new window.XMLHttpRequest();
               xhr.upload.addEventListener('progress', function(e) {
                   if (e.lengthComputable) {
                       var percentComplete = (e.loaded / e.total) * 100;
                       updateUploadProgress(percentComplete, 'Uploading...');
                   }
               }, false);
               return xhr;
           },
           success: function(response) {
               console.log('📁 Upload-Response:', response);
               hideUploadProgress();
               
               if (response.success) {
                   currentUploadedFile = response.data.filename;
                   showImportPreview(response.data);
                   showNotification('✅ CSV-Datei erfolgreich hochgeladen!', 'success');
               } else {
                   showNotification('❌ Upload fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
               }
           },
           error: function(xhr, status, error) {
               console.error('❌ Upload AJAX-Fehler:', status, error);
               hideUploadProgress();
               showNotification('❌ Upload-Verbindungsfehler', 'error');
           }
       });
   }
   
   // Upload-Progress Funktionen
   function showUploadProgress() {
       if ($('#retexify-upload-status').length === 0) {
           var progressHtml = '<div id="retexify-upload-status" class="retexify-upload-status">';
           progressHtml += '<div class="retexify-upload-progress">';
           progressHtml += '<div class="retexify-progress-bar">';
           progressHtml += '<div id="retexify-progress-fill" class="retexify-progress-fill" style="width: 0%"></div>';
           progressHtml += '</div>';
           progressHtml += '<p id="retexify-progress-text" class="retexify-progress-text">0%</p>';
           progressHtml += '</div>';
           progressHtml += '</div>';
           
           $('#retexify-csv-upload-area').after(progressHtml);
       }
       $('#retexify-upload-status').show();
   }
   
   function updateUploadProgress(percent, text) {
       $('#retexify-progress-fill').css('width', percent + '%');
       $('#retexify-progress-text').text(Math.round(percent) + '%');
       
       if (text) {
           $('#retexify-progress-text').attr('title', text);
       }
   }
   
   function hideUploadProgress() {
       $('#retexify-upload-status').hide();
   }
   
   // Import-Vorschau anzeigen
   function showImportPreview(data) {
       console.log('📋 Zeige Import-Vorschau:', data);
       
       var preview = data.preview;
       var summaryHtml = '<div class="retexify-import-info">';
       summaryHtml += '<h5>📊 Import-Statistiken:</h5>';
       summaryHtml += '<p><strong>Datei:</strong> ' + data.filename + ' (' + formatFileSize(data.file_size) + ')</p>';
       summaryHtml += '<p><strong>Zeilen:</strong> ' + preview.total_rows + '</p>';
       summaryHtml += '<p><strong>Spalten:</strong> ' + preview.headers.length + '</p>';
       summaryHtml += '<p><strong>Delimiter:</strong> "' + preview.detected_delimiter + '"</p>';
       summaryHtml += '<p><em>Hinweis: Es werden nur die "Neu" Spalten importiert!</em></p>';
       summaryHtml += '</div>';
       
       // Import-Vorschau Container erstellen falls nicht vorhanden
       if ($('#retexify-import-preview').length === 0) {
           var previewContainer = '<div id="retexify-import-preview" class="retexify-import-preview" style="display: none;">';
           previewContainer += '<h4>📋 Import-Vorschau:</h4>';
           previewContainer += '<div id="retexify-import-summary"></div>';
           previewContainer += '<div id="retexify-import-data-preview"></div>';
           previewContainer += '<div class="retexify-import-actions">';
           previewContainer += '<button type="button" id="retexify-start-import" class="retexify-btn retexify-btn-primary retexify-btn-large">📥 Import starten</button>';
           previewContainer += '<button type="button" id="retexify-cancel-import" class="retexify-btn retexify-btn-secondary retexify-btn-large">❌ Abbrechen</button>';
           previewContainer += '</div>';
           previewContainer += '</div>';
           
           $('#retexify-csv-upload-area').after(previewContainer);
       }
       
       $('#retexify-import-summary').html(summaryHtml);
       
       // Daten-Vorschau erstellen
       var previewHtml = '<div class="retexify-csv-preview">';
       previewHtml += '<h5>📋 CSV-Vorschau (erste 5 Zeilen):</h5>';
       previewHtml += '<div class="retexify-table-wrapper">';
       previewHtml += '<table class="retexify-preview-table">';
       
       // Headers
       previewHtml += '<thead><tr>';
       preview.headers.forEach(function(header) {
           previewHtml += '<th>' + escapeHtml(header) + '</th>';
       });
       previewHtml += '</tr></thead>';
       
       // Rows
       previewHtml += '<tbody>';
       preview.rows.forEach(function(row) {
           previewHtml += '<tr>';
           row.forEach(function(cell) {
               previewHtml += '<td>' + escapeHtml(cell || '') + '</td>';
           });
           previewHtml += '</tr>';
       });
       previewHtml += '</tbody>';
       
       previewHtml += '</table>';
       previewHtml += '</div>';
       previewHtml += '</div>';
       
       $('#retexify-import-data-preview').html(previewHtml);
       $('#retexify-import-preview').slideDown(300);
       
       // Import-Daten für späteren Gebrauch speichern
       importData = data;
   }
   
   // Import starten
   $(document).on('click', '#retexify-start-import', function(e) {
       e.preventDefault();
       console.log('📥 Import gestartet');
       
       if (!currentUploadedFile) {
           showNotification('❌ Keine Datei zum Importieren vorhanden', 'error');
           return;
       }
       
       var $btn = $(this);
       var originalText = $btn.html();
       $btn.html('📥 Importiere...').prop('disabled', true);
       
       // Automatische Spalten-Zuordnung für "Neu" Spalten
       var columnMapping = {};
       if (importData.preview && importData.preview.headers) {
           importData.preview.headers.forEach(function(header, index) {
               var normalizedHeader = header.toLowerCase().trim();
               
               if (normalizedHeader.includes('id')) {
                   columnMapping[index] = 'id';
               } else if (normalizedHeader.includes('meta-titel') && normalizedHeader.includes('neu')) {
                   columnMapping[index] = 'meta_title_new';
               } else if (normalizedHeader.includes('meta-beschreibung') && normalizedHeader.includes('neu')) {
                   columnMapping[index] = 'meta_description_new';
               } else if (normalizedHeader.includes('focus-keyword') && normalizedHeader.includes('neu')) {
                   columnMapping[index] = 'focus_keyword_new';
               } else if (normalizedHeader.includes('wpbakery') && normalizedHeader.includes('neu')) {
                   columnMapping[index] = 'wpbakery_text_new';
               } else if (normalizedHeader.includes('alt-text') && normalizedHeader.includes('neu')) {
                   columnMapping[index] = 'alt_text_new';
               } else {
                   columnMapping[index] = 'ignore';
               }
           });
       }
       
       console.log('📥 Column Mapping:', columnMapping);
       
       $.ajax({
           url: retexify_ajax.ajax_url,
           type: 'POST',
           data: {
               action: 'retexify_save_imported_data',
               nonce: retexify_ajax.nonce,
               filename: currentUploadedFile,
               column_mapping: columnMapping
           },
           timeout: 180000, // 3 Minuten für große Dateien
           success: function(response) {
               $btn.html(originalText).prop('disabled', false);
               console.log('📥 Import-Response:', response);
               
               if (response.success) {
                   showImportResults(response.data);
                   showNotification('✅ Import erfolgreich abgeschlossen!', 'success');
                   
                   // Import-Vorschau ausblenden
                   $('#retexify-import-preview').slideUp(300);
                   currentUploadedFile = null;
                   
               } else {
                   showNotification('❌ Import fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
               }
           },
           error: function(xhr, status, error) {
               $btn.html(originalText).prop('disabled', false);
               console.error('❌ Import AJAX-Fehler:', status, error);
               showNotification('❌ Import-Verbindungsfehler', 'error');
           }
       });
   });
   
   // Import abbrechen
   $(document).on('click', '#retexify-cancel-import', function(e) {
       e.preventDefault();
       console.log('❌ Import abgebrochen');
       
       if (currentUploadedFile) {
           // Datei löschen
           $.ajax({
               url: retexify_ajax.ajax_url,
               type: 'POST',
               data: {
                   action: 'retexify_delete_upload',
                   nonce: retexify_ajax.nonce,
                   filename: currentUploadedFile
               },
               success: function(response) {
                   console.log('🗑️ Datei gelöscht:', response);
               }
           });
       }
       
       // UI zurücksetzen
       $('#retexify-import-preview').slideUp(300);
       $('#retexify-import-results').slideUp(300);
       currentUploadedFile = null;
       importData = {};
       
       showNotification('❌ Import abgebrochen', 'warning');
   });
   
   // Import-Ergebnisse anzeigen
   function showImportResults(results) {
       var resultsHtml = '<div class="retexify-import-success">';
       resultsHtml += '<h5>✅ Import-Ergebnisse:</h5>';
       resultsHtml += '<div class="retexify-results-grid">';
       
       resultsHtml += '<div class="retexify-result-item">';
       resultsHtml += '<span class="retexify-result-number">' + (results.updated || 0) + '</span>';
       resultsHtml += '<span class="retexify-result-label">Aktualisiert</span>';
       resultsHtml += '</div>';
       
       if (results.imported) {
           resultsHtml += '<div class="retexify-result-item">';
           resultsHtml += '<span class="retexify-result-number">' + results.imported + '</span>';
           resultsHtml += '<span class="retexify-result-label">Neu erstellt</span>';
           resultsHtml += '</div>';
       }
       
       if (results.total_errors > 0) {
           resultsHtml += '<div class="retexify-result-item error">';
           resultsHtml += '<span class="retexify-result-number">' + results.total_errors + '</span>';
           resultsHtml += '<span class="retexify-result-label">Fehler</span>';
           resultsHtml += '</div>';
       }
       
       resultsHtml += '</div>';
       
       if (results.errors && results.errors.length > 0) {
           resultsHtml += '<div class="retexify-error-details">';
           resultsHtml += '<h6>⚠️ Fehler-Details:</h6>';
           resultsHtml += '<ul>';
           results.errors.forEach(function(error) {
               resultsHtml += '<li>' + escapeHtml(error) + '</li>';
           });
           resultsHtml += '</ul>';
           resultsHtml += '</div>';
       }
       
       resultsHtml += '</div>';
       
       // Import-Ergebnisse Container erstellen falls nicht vorhanden
       if ($('#retexify-import-results').length === 0) {
           var resultsContainer = '<div id="retexify-import-results" class="retexify-import-results" style="display: none;">';
           resultsContainer += '<h4>✅ Import-Ergebnisse:</h4>';
           resultsContainer += '<div id="retexify-import-summary-results"></div>';
           resultsContainer += '</div>';
           
           $('#retexify-import-preview').after(resultsContainer);
       }
       
       $('#retexify-import-summary-results').html(resultsHtml);
       $('#retexify-import-results').slideDown(300);
   }
   
   // ==== HILFSFUNKTIONEN ====
   
   // HTML escapen
   function escapeHtml(text) {
       if (!text) return '';
       var map = {
           '&': '&amp;',
           '<': '&lt;',
           '>': '&gt;',
           '"': '&quot;',
           "'": '&#039;'
       };
       return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
   }
   
   // Dateigröße formatieren
   function formatFileSize(bytes) {
       if (bytes === 0) return '0 Bytes';
       var k = 1024;
       var sizes = ['Bytes', 'KB', 'MB', 'GB'];
       var i = Math.floor(Math.log(bytes) / Math.log(k));
       return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
   }
   
   // Benachrichtigung anzeigen
   function showNotification(message, type) {
       console.log('📢 Notification:', type, message);
       
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
       
       // Auto-remove nach 5 Sekunden
       setTimeout(function() {
           $notification.animate({
               transform: 'translateX(100%)',
               opacity: 0
           }, 300, function() {
               $(this).remove();
           });
       }, 5000);
       
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
   
   console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (korrigierte Version)!');
   console.log('📤 Korrigierte Export-Funktionen bereit');
   console.log('📥 Import-Funktionen bereit (nur Neu-Spalten)');
   console.log('🎯 Drag & Drop aktiviert');
   console.log('🗃️ Komplette Mediendatenbank-Unterstützung');
   console.log('🔧 Element-ID-Mapping korrigiert');
   
   // Initial Export-Statistiken laden falls Tab bereits aktiv
   if ($('.retexify-tab-btn[data-tab="export-import"]').hasClass('active')) {
       setTimeout(loadExportStats, 500);
   }
});