/**
 * ReTexify AI Pro - Export/Import JavaScript - CLEAN VERSION
 * Version: 3.5.9 - Ohne überflüssige Export-Statistiken
 * 
 * VERBESSERUNGEN:
 * ✅ Keine aufdringlichen Export-Statistiken mehr
 * ✅ Sauberes, minimalistisches Design
 * ✅ Fokus auf die eigentlichen Export/Import-Funktionen
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet (Clean Version)...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        console.log('⚠️ Export/Import Tab nicht gefunden - beende Script');
        return;
    }

    console.log('✅ ReTexify Export/Import Script geladen (Clean Version).');
    
    // ==== CLEAN EXPORT FUNKTIONALITÄT ====
    
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
        previewHtml += '<div class="retexify-export-highlight">';
        previewHtml += '<p><strong>✅ Nur ausgewählte Daten werden exportiert!</strong></p>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        $('#retexify-preview-content').html(previewHtml);
        $('#retexify-export-preview').slideDown(300);
        
        showNotification('👁️ Export-Vorschau erstellt', 'success');
    });
    
    // Export-Auswahl sammeln (für Anzeige)
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
        
        // Content-Typen mit lesbaren Labels (für die Anzeige)
        $('input[name="export_content[]"]:checked').each(function() {
            var contentType = $(this).val();
            var contentLabels = {
                'title': 'Titel',
                'yoast_meta_title': 'Yoast Meta-Titel',
                'yoast_meta_description': 'Yoast Meta-Beschreibung',
                'yoast_focus_keyword': 'Yoast Focus-Keyword',
                'wpbakery_meta_title': 'WPBakery Meta-Titel',
                'wpbakery_meta_description': 'WPBakery Meta-Beschreibung',
                'wpbakery_text': 'WPBakery Text',
                'post_content': 'Post-Inhalt',
                'alt_texts': 'Alt-Texte (Mediendatenbank)'
            };
            
            selection.content.push(contentLabels[contentType] || contentType);
        });
        
        return selection;
    }
    
    // Export-Zeilen schätzen (einfache Schätzung)
    function estimateExportRows(selection) {
        var totalRows = 0;
        
        // Einfache Schätzung basierend auf Post-Typen
        selection.post_types.forEach(function(type) {
            if (type === 'post') totalRows += 5; // Geschätzte Posts
            if (type === 'page') totalRows += 10; // Geschätzte Seiten
        });
        
        // Wenn Alt-Texte ausgewählt, Medien hinzufügen
        if (selection.content.some(function(content) { return content.includes('Alt-Texte'); })) {
            totalRows += 20; // Geschätzte Medien
        }
        
        return Math.max(totalRows, 1);
    }
    
    // Export-Auswahl für API sammeln (DIREKTE ÜBERTRAGUNG)
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
        
        // Content-Typen DIREKT verwenden - KEIN MAPPING!
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
        });
        
        console.log('📤 Export-Auswahl für API (direkte Übertragung):', selection);
        return selection;
    }
    
    // CSV-Export starten
    $(document).on('click', '#retexify-start-export', function(e) {
        e.preventDefault();
        console.log('📤 CSV-Export gestartet (Clean Version)');
        
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
                showNotification('✅ CSV-Export erfolgreich!', 'success');
                
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
    
    // Event-Handler für Checkbox-Änderungen
    $(document).on('change', 'input[name="export_post_types[]"], input[name="export_status[]"], input[name="export_content[]"]', function() {
        updateExportPreview();
    });
    
    // Export-Vorschau aktualisieren
    function updateExportPreview() {
        var selection = collectExportSelection();
        
        if (selection.post_types.length === 0 || selection.content.length === 0) {
            $('#retexify-export-preview').slideUp(300);
            return;
        }
        
        var estimatedRows = estimateExportRows(selection);
        
        var previewHtml = '<div class="retexify-export-summary">';
        previewHtml += '<h4>📋 Export-Vorschau</h4>';
        previewHtml += '<div class="retexify-export-details">';
        
        // Post-Typen
        previewHtml += '<div class="retexify-export-item">';
        previewHtml += '<strong>Post-Typen:</strong> ' + selection.post_types.join(', ');
        previewHtml += '</div>';
        
        // Status
        previewHtml += '<div class="retexify-export-item">';
        previewHtml += '<strong>Status:</strong> ' + selection.status.join(', ');
        previewHtml += '</div>';
        
        // Content-Typen
        previewHtml += '<div class="retexify-export-item">';
        previewHtml += '<strong>Inhalte:</strong> ' + selection.content.join(', ');
        previewHtml += '</div>';
        
        // Geschätzte Zeilen
        previewHtml += '<div class="retexify-export-item">';
        previewHtml += '<strong>Geschätzte Zeilen:</strong> ~' + estimatedRows;
        previewHtml += '</div>';
        
        // Info
        previewHtml += '<div class="retexify-export-item retexify-highlight">';
        previewHtml += '<strong>✅ Sauberes Design:</strong> Nur ausgewählte Daten werden exportiert!';
        previewHtml += '</div>';
        
        previewHtml += '</div>';
        previewHtml += '<button id="retexify-start-export" class="button button-primary">📤 Export starten</button>';
        previewHtml += '</div>';
        
        $('#retexify-export-preview').html(previewHtml).slideDown(300);
    }
    
    // ==== IMPORT FUNKTIONALITÄT (unverändert) ====
    
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
            showNotification('❌ Datei zu groß. Maximum 10MB erlaubt.', 'error');
            return;
        }
        
        var formData = new FormData();
        formData.append('csv_file', file);
        formData.append('action', 'retexify_import_csv_data');
        formData.append('nonce', retexify_ajax.nonce);
        
        // Upload-Status anzeigen
        var $uploadStatus = $('<div class="retexify-upload-status">');
        $uploadStatus.html('📤 Uploading: ' + file.name + '...');
        $uploadArea.append($uploadStatus);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $uploadStatus.remove();
                
                if (response.success) {
                    currentUploadedFile = response.data.filename;
                    showNotification('✅ Datei erfolgreich hochgeladen: ' + file.name, 'success');
                    loadImportPreview(response.data.filename);
                } else {
                    showNotification('❌ Upload fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function() {
                $uploadStatus.remove();
                showNotification('❌ Upload fehlgeschlagen - Verbindungsfehler', 'error');
            }
        });
    }
    
    // Import-Vorschau laden
    function loadImportPreview(filename) {
        console.log('👁️ Lade Import-Vorschau für:', filename);
        
        var data = {
            'action': 'retexify_get_import_preview',
            'nonce': retexify_ajax.nonce,
            'filename': filename
        };
        
        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                displayImportPreview(response.data);
                importData = response.data;
            } else {
                showNotification('❌ Vorschau-Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error');
            }
        }).fail(function() {
            showNotification('❌ Verbindungsfehler bei Import-Vorschau', 'error');
        });
    }
    
    // Import-Vorschau anzeigen
    function displayImportPreview(data) {
        var preview = data.preview;
        var fileInfo = data.file_info;
        
        // Datei-Info
        var summaryHtml = '<div class="retexify-file-info">';
        summaryHtml += '<h5>📄 Datei-Informationen:</h5>';
        summaryHtml += '<p><strong>Name:</strong> ' + fileInfo.name + '</p>';
        summaryHtml += '<p><strong>Größe:</strong> ' + formatFileSize(fileInfo.size) + '</p>';
        summaryHtml += '<p><strong>Zeilen:</strong> ' + preview.total_rows + '</p>';
        summaryHtml += '<p><strong>Spalten:</strong> ' + preview.headers.length + '</p>';
        summaryHtml += '<p><strong>Delimiter:</strong> "' + preview.detected_delimiter + '"</p>';
        summaryHtml += '</div>';
        
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
        
        // Vorschau anzeigen
        $('#retexify-import-summary').html(summaryHtml);
        $('#retexify-import-data-preview').html(previewHtml);
        $('#retexify-import-preview').slideDown(300);
        
        showNotification('👁️ Import-Vorschau geladen', 'success');
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
                
                // Automatische Zuordnung basierend auf Header-Namen
                if (normalizedHeader.includes('id')) {
                    columnMapping[index] = 'id';
                } else if (normalizedHeader.includes('meta-titel') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'meta_title_new';
                } else if (normalizedHeader.includes('meta-beschreibung') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'meta_description_new';
                } else if (normalizedHeader.includes('focus-keyword') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'focus_keyword_new';
                } else if (normalizedHeader.includes('alt-text') && normalizedHeader.includes('neu')) {
                    columnMapping[index] = 'alt_text_new';
                }
            });
        }
        
        var data = {
            'action': 'retexify_save_imported_data',
            'nonce': retexify_ajax.nonce,
            'filename': currentUploadedFile,
            'column_mapping': columnMapping
        };
        
        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                showNotification('✅ Import erfolgreich abgeschlossen!', 'success');
                displayImportResults(response.data);
                
                // Datei-Upload-Bereich zurücksetzen
                currentUploadedFile = null;
                $('#retexify-import-preview').slideUp(300);
                $uploadArea.removeClass('has-file');
                
            } else {
                showNotification('❌ Import fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
            }
            $btn.html(originalText).prop('disabled', false);
        }).fail(function() {
            $btn.html(originalText).prop('disabled', false);
            showNotification('❌ Verbindungsfehler beim Import', 'error');
        });
    });
    
    // Import-Ergebnisse anzeigen
    function displayImportResults(results) {
        var resultsHtml = '<div class="retexify-import-results-summary">';
        resultsHtml += '<h5>✅ Import-Ergebnisse:</h5>';
        
        resultsHtml += '<div class="retexify-result-stats">';
        resultsHtml += '<div class="retexify-result-stat">';
        resultsHtml += '<span class="retexify-result-number">' + results.total_processed + '</span>';
        resultsHtml += '<span class="retexify-result-label">Verarbeitet</span>';
        resultsHtml += '</div>';
        
        resultsHtml += '<div class="retexify-result-stat">';
        resultsHtml += '<span class="retexify-result-number">' + results.updated + '</span>';
        resultsHtml += '<span class="retexify-result-label">Aktualisiert</span>';
        resultsHtml += '</div>';
        
        resultsHtml += '<div class="retexify-result-stat">';
        resultsHtml += '<span class="retexify-result-number">' + results.imported + '</span>';
        resultsHtml += '<span class="retexify-result-label">Importiert</span>';
        resultsHtml += '</div>';
        
        if (results.total_errors > 0) {
            resultsHtml += '<div class="retexify-result-stat error">';
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
        
        // Ergebnisse Container erstellen falls nicht vorhanden
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
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (Clean Version)!');
    console.log('🎨 VERBESSERUNGEN: Keine überflüssigen Statistiken, sauberes minimalistisches Design');
});