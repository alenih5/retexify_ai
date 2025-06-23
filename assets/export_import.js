/**
 * ReTexify AI Pro - Export/Import JavaScript
 * Version: 3.5.7 - Vollständige Export/Import-Funktionalität
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        return;
    }

    console.log('ReTexify Export/Import Script geladen.');
    
    // ==== EXPORT FUNKTIONALITÄT ====
    
    // Export-Statistiken beim Tab-Wechsel laden
    $(document).on('click', '.retexify-tab-btn[data-tab="export-import"]', function() {
        console.log('📤 Export/Import Tab aktiviert - lade Statistiken...');
        setTimeout(loadExportStats, 100);
    });
    
    // Export-Statistiken laden
    function loadExportStats() {
        console.log('📊 Lade Export-Statistiken...');
        
        var data = {
            'action': 'retexify_get_export_stats',
            'nonce': retexify_export_import_ajax.nonce
        };

        $.post(retexify_export_import_ajax.ajax_url, data, function(response) {
            if (response.success) {
                console.log('📊 Export-Statistiken erhalten:', response.data);
                updateExportCounts(response.data);
            } else {
                console.error('❌ Fehler beim Laden der Export-Statistiken:', response.data);
                // Fallback-Zählung
                performFallbackCounting();
            }
        });
    }
    
    // Export-Zahlen aktualisieren
    function updateExportCounts(stats) {
        console.log('📊 Aktualisiere Export-Zahlen:', stats);
        
        // Post-Typen
        $('#post-count').text(stats.post || 0);
        $('#page-count').text(stats.page || 0);
        
        // Status
        $('#publish-count').text(stats.publish || 0);
        $('#draft-count').text(stats.draft || 0);
        
        // Content-Typen
        $('#title-count').text(stats.title || 0);
        $('#content-count').text(stats.content || 0);
        $('#meta-title-count').text(stats.meta_title || 0);
        $('#meta-desc-count').text(stats.meta_description || 0);
        $('#focus-keyword-count').text(stats.focus_keyword || 0);
        
        // WPBakery und Alt-Text (falls verfügbar)
        $('#wpbakery-count').text(stats.wpbakery || 0);
        $('#alt-texts-count').text(stats.alt_texts || 0);
        
        console.log('✅ Export-Zahlen aktualisiert');
    }
    
    // Fallback-Zählung (falls AJAX fehlschlägt)
    function performFallbackCounting() {
        console.log('⚠️ Führe Fallback-Zählung durch...');
        
        // Einfache Schätzungen basierend auf WordPress-Statistiken
        var estimatedPosts = 10; // Placeholder
        var estimatedPages = 5;  // Placeholder
        
        $('#post-count').text(estimatedPosts);
        $('#page-count').text(estimatedPages);
        $('#publish-count').text(estimatedPosts + estimatedPages);
        $('#title-count').text(estimatedPosts + estimatedPages);
        $('#content-count').text(estimatedPosts + estimatedPages);
        $('#meta-title-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.3));
        $('#meta-desc-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.2));
        $('#focus-keyword-count').text(Math.floor((estimatedPosts + estimatedPages) * 0.1));
        
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
        previewHtml += '<p><strong>Content:</strong> ' + selectedData.content.join(', ') + '</p>';
        previewHtml += '<p><strong>Geschätzte Zeilen:</strong> ~' + estimateExportRows(selectedData) + '</p>';
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
        
        // Content-Typen
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
        });
        
        return selection;
    }
    
    // Export-Zeilen schätzen
    function estimateExportRows(selection) {
        var totalRows = 0;
        
        selection.post_types.forEach(function(type) {
            selection.status.forEach(function(status) {
                var countElement = $('#' + type + '-count');
                if (countElement.length > 0) {
                    totalRows += parseInt(countElement.text()) || 0;
                }
            });
        });
        
        return Math.max(totalRows, 1);
    }
    
    // CSV-Export starten
    $(document).on('click', '#retexify-start-export', function(e) {
        e.preventDefault();
        console.log('📤 CSV-Export gestartet');
        
        var $btn = $(this);
        var originalText = $btn.html();
        var selectedData = collectExportSelection();
        
        if (selectedData.post_types.length === 0 || selectedData.content.length === 0) {
            showNotification('❌ Bitte treffen Sie eine gültige Auswahl', 'error');
            return;
        }
        
        $btn.html('📤 Exportiere...').prop('disabled', true);
        
        var data = {
            'action': 'retexify_export_content_csv',
            'nonce': retexify_export_import_ajax.nonce,
            'post_types': selectedData.post_types,
            'status': selectedData.status,
            'content': selectedData.content
        };

        $.post(retexify_export_import_ajax.ajax_url, data, function(response) {
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
        });
    });
    
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
        
        if (file.size > retexify_export_import_ajax.max_file_size) {
            showNotification('❌ Datei zu groß. Maximum: ' + formatFileSize(retexify_export_import_ajax.max_file_size), 'error');
            return;
        }
        
        // Upload-Status anzeigen
        $('#retexify-upload-status').show();
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
                
                $('#retexify-upload-status').hide();
            },
            error: function(xhr, status, error) {
                console.error('❌ Upload AJAX-Fehler:', status, error);
                showNotification('❌ Upload-Verbindungsfehler', 'error');
                $('#retexify-upload-status').hide();
            }
        });
    }
    
    // Upload-Progress aktualisieren
    function updateUploadProgress(percent, text) {
        $('#retexify-progress-fill').css('width', percent + '%');
        $('#retexify-progress-text').text(Math.round(percent) + '%');
        
        if (text) {
            $('#retexify-upload-status .retexify-upload-progress').attr('title', text);
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
                previewHtml += '<td>' + escapeHtml(cell) + '</td>';
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
        
        // Einfache Spalten-Zuordnung (könnte erweitert werden)
        var columnMapping = {};
        if (importData.preview && importData.preview.headers) {
            importData.preview.headers.forEach(function(header, index) {
                var normalizedHeader = header.toLowerCase().trim();
                
                if (normalizedHeader.includes('id')) {
                    columnMapping[index] = 'id';
                } else if (normalizedHeader.includes('titel')) {
                    columnMapping[index] = 'title';
                } else if (normalizedHeader.includes('meta-titel') || normalizedHeader.includes('meta_title')) {
                    columnMapping[index] = 'meta_title';
                } else if (normalizedHeader.includes('meta-beschreibung') || normalizedHeader.includes('meta_description')) {
                    columnMapping[index] = 'meta_description';
                } else if (normalizedHeader.includes('focus') && normalizedHeader.includes('keyword')) {
                    columnMapping[index] = 'focus_keyword';
                } else if (normalizedHeader.includes('content') || normalizedHeader.includes('inhalt')) {
                    columnMapping[index] = 'content';
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
        
        $('#retexify-import-summary-results').html(resultsHtml);
        $('#retexify-import-results').slideDown(300);
    }
    
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
    
    // Export-Statistiken AJAX-Handler registrieren
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.data && settings.data.includes('action=retexify_get_export_stats')) {
            console.log('📊 Export-Statistiken AJAX abgeschlossen');
        }
    });
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen!');
    console.log('📤 Export-Funktionen bereit');
    console.log('📥 Import-Funktionen bereit');
    console.log('🎯 Drag & Drop aktiviert');
    
    // Initial Export-Statistiken laden falls Tab bereits aktiv
    if ($('.retexify-tab-btn[data-tab="export-import"]').hasClass('active')) {
        setTimeout(loadExportStats, 500);
    }
});