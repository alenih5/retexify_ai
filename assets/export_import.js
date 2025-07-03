/**
 * ReTexify AI Pro - Export/Import JavaScript - VOLLSTÄNDIGE VERSION
 * Version: 3.6.0 - Mit funktionierender Löschfunktion
 * 
 * VERBESSERUNGEN:
 * ✅ Keine aufdringlichen Export-Statistiken mehr
 * ✅ Sauberes, minimalistisches Design
 * ✅ Fokus auf die eigentlichen Export/Import-Funktionen
 * ✅ NEUE: Funktionierende Löschfunktion für hochgeladene Dateien
 * ✅ Verbesserte Fehlerbehandlung und Notifications
 * ✅ Längere Anzeigezeit für Fehlermeldungen
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet (Vollständige Version mit Löschfunktion)...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        console.log('⚠️ Export/Import Tab nicht gefunden - beende Script');
        return;
    }

    console.log('✅ ReTexify Export/Import Script geladen (Vollständige Version).');
    
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
        previewHtml += '<div class="retexify-export-icons-row" style="display:flex;gap:24px;justify-content:center;margin:18px 0;">';
        previewHtml += '<div style="text-align:center;"><div style="font-size:2rem;">📄</div><div style="font-size:1rem;">Gesamt-Posts<br><b>' + (window.retexifyExportStats?.totalPosts || '–') + '</b></div></div>';
        previewHtml += '<div style="text-align:center;"><div style="font-size:2rem;">🗂️</div><div style="font-size:1rem;">Spalten<br><b>' + (window.retexifyExportStats?.totalColumns || '–') + '</b></div></div>';
        previewHtml += '<div style="text-align:center;"><div style="font-size:2rem;">👁️</div><div style="font-size:1rem;">Vorschau<br><b>' + (window.retexifyExportStats?.previewCount || '–') + '</b></div></div>';
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
        $('input[name="export_content[]"]').each(function() {
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
            // Zeige alle Felder als auswählbar an
            $(this).closest('label').show();
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
                showNotification('CSV-Export erfolgreich!', 'success');
                
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
    
    // ==== IMPORT FUNKTIONALITÄT (ERWEITERT) ====
    
    // Upload-Bereich Events
    var $uploadArea = $('#retexify-csv-upload-area');
    var $fileInput = $('#retexify-csv-file-input');
    
    // Input-Feld immer über den Upload-Bereich legen (unsichtbar, aber klickbar)
    $uploadArea.css({position: 'relative'});
    $fileInput.css({
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        opacity: 0,
        cursor: 'pointer',
        zIndex: 10,
        display: 'block'
    });
    
    // Click-Event für Upload-Bereich entfällt, da Input immer klickbar ist
    
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
    
    // Import-Vorschau anzeigen - ERWEITERT mit Lösch-Button
    function displayImportPreview(data) {
        var preview = data.preview;
        var fileInfo = data.file_info;
        
        var previewHtml = '<div class="retexify-import-preview">';
        previewHtml += '<h4>✅ CSV-Datei hochgeladen</h4>';
        
        // NEU: Datei-Informationen mit Lösch-Button
        previewHtml += '<div class="retexify-import-file-info">';
        previewHtml += '<div class="retexify-file-details">';
        previewHtml += '<h5>📄 Datei-Informationen:</h5>';
        previewHtml += '<p><strong>Name:</strong> ' + fileInfo.name + '</p>';
        previewHtml += '<p><strong>Größe:</strong> ' + formatFileSize(fileInfo.size) + '</p>';
        previewHtml += '<p><strong>Zeilen:</strong> ' + preview.total_rows + '</p>';
        previewHtml += '<p><strong>Spalten:</strong> ' + preview.headers.length + '</p>';
        previewHtml += '</div>';
        
        // NEU: Datei-Aktionen
        previewHtml += '<div class="retexify-file-actions">';
        previewHtml += '<button type="button" id="retexify-delete-uploaded-file" class="retexify-btn retexify-btn-secondary">';
        previewHtml += '🗑️ Datei entfernen';
        previewHtml += '</button>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        // Daten-Vorschau
        previewHtml += '<div class="retexify-csv-preview">';
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
        
        // Import-Button
        previewHtml += '<div class="retexify-import-actions">';
        previewHtml += '<button id="retexify-start-import" class="button button-primary">📥 Import starten</button>';
        previewHtml += '</div>';
        
        previewHtml += '</div>';
        
        // Vorschau anzeigen
        $('#retexify-import-results').html(previewHtml).slideDown(300);
        
        showNotification('👁️ Import-Vorschau geladen', 'success');
    }
    
    // NEU: LÖSCHFUNKTION für hochgeladene Dateien
    $(document).on('click', '#retexify-delete-uploaded-file', function(e) {
        e.preventDefault();
        console.log('🗑️ Datei löschen ausgelöst');
        
        // Prüfen ob eine Datei vorhanden ist
        if (!currentUploadedFile) {
            showNotification('❌ Keine Datei zum Löschen vorhanden', 'error', 5000);
            return;
        }
        
        // Bestätigung mit detaillierter Information
        if (!confirm('Möchten Sie die hochgeladene Datei "' + currentUploadedFile + '" wirklich entfernen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        
        // Button-Status während der Löschung
        $btn.html('🗑️ Lösche...').prop('disabled', true);
        
        // AJAX-Daten mit zusätzlicher Validierung
        var data = {
            'action': 'retexify_delete_upload',
            'nonce': retexify_ajax.nonce,
            'filename': currentUploadedFile
        };
        
        console.log('📤 Sende Lösch-Anfrage:', data);
        
        // AJAX-Request mit verbesserter Fehlerbehandlung
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: data,
            timeout: 15000, // 15 Sekunden Timeout
            success: function(response) {
                console.log('📥 Lösch-Antwort erhalten:', response);
                
                // Button zurücksetzen
                $btn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    // Erfolgreiche Löschung
                    showNotification('✅ ' + (response.data || 'Datei erfolgreich entfernt'), 'success', 4000);
                    
                    // UI komplett zurücksetzen
                    resetImportUI();
                    
                } else {
                    // Fehler vom Server
                    var errorMsg = response.data || 'Unbekannter Fehler beim Löschen';
                    console.error('❌ Server-Fehler:', errorMsg);
                    showNotification('❌ Löschfehler: ' + errorMsg, 'error', 8000);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX-Fehler beim Löschen:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    readyState: xhr.readyState
                });
                
                // Button zurücksetzen
                $btn.html(originalText).prop('disabled', false);
                
                // Detaillierte Fehlermeldung
                var errorMessage = 'Verbindungsfehler beim Löschen';
                
                if (status === 'timeout') {
                    errorMessage = 'Zeitüberschreitung - Vorgang abgebrochen';
                } else if (status === 'error') {
                    errorMessage = 'Serverfehler beim Löschen';
                } else if (status === 'parsererror') {
                    errorMessage = 'Antwort-Format-Fehler vom Server';
                }
                
                showNotification('❌ ' + errorMessage, 'error', 10000);
            }
        });
    });
    
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
        
        showImportProgress();
        
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
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: data,
            timeout: 60000, // 60 Sekunden für Import
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                hideImportProgress();
                
                if (response.success) {
                    showNotification('✅ Import erfolgreich abgeschlossen! ' + 
                        response.data.total_processed + ' Einträge verarbeitet', 'success');
                    
                    // Aufräumen
                    currentUploadedFile = null;
                } else {
                    showNotification('❌ Import-Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                hideImportProgress();
                console.error('❌ Import AJAX-Fehler:', status, error);
                showNotification('❌ Verbindungsfehler beim Import', 'error');
            }
        });
    });
    
    // ==== HILFSFUNKTIONEN ====
    
    /**
     * UI komplett zurücksetzen nach erfolgreicher Löschung
     */
    function resetImportUI() {
        console.log('🔄 Import-UI wird zurückgesetzt');
        
        // Import-Ergebnisse ausblenden
        $('#retexify-import-results').slideUp(300, function() {
            $(this).empty();
        });
        
        // Globale Variable zurücksetzen
        currentUploadedFile = null;
        
        // File-Input zurücksetzen
        var $fileInput = $('#retexify-csv-file-input');
        if ($fileInput.length) {
            $fileInput.val('');
        }
        
        // Upload-Bereich zurücksetzen
        var $uploadArea = $('#retexify-csv-upload-area');
        if ($uploadArea.length) {
            $uploadArea.removeClass('dragover has-file');
        }
        
        // Fortschrittsbalken entfernen (falls vorhanden)
        $('.retexify-import-progress').remove();
        
        console.log('✅ Import-UI erfolgreich zurückgesetzt');
    }
    
    // NEU: Import-Progress anzeigen
    function showImportProgress() {
        var progressHtml = '<div class="retexify-import-progress">';
        progressHtml += '<h4>📥 Import läuft...</h4>';
        progressHtml += '<div class="retexify-progress-bar">';
        progressHtml += '<div class="retexify-progress-fill" id="import-progress-fill"></div>';
        progressHtml += '</div>';
        progressHtml += '<p class="retexify-progress-text">Daten werden importiert, bitte warten...</p>';
        progressHtml += '</div>';
        
        $('#retexify-import-results').html(progressHtml);
        
        // Animiere Progress Bar
        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += Math.random() * 10;
            if (progress > 85) progress = 85;
            $('#import-progress-fill').css('width', progress + '%');
        }, 500);
        
        window.importProgressInterval = progressInterval;
    }
    
    // NEU: Import-Progress ausblenden
    function hideImportProgress() {
        if (window.importProgressInterval) {
            clearInterval(window.importProgressInterval);
            window.importProgressInterval = null;
        }
        $('#import-progress-fill').css('width', '100%');
        setTimeout(function() {
            $('.retexify-import-progress').fadeOut(300);
        }, 500);
    }
    
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
    
    // VERBESSERTE Benachrichtigung anzeigen mit konfigurierbarer Anzeigedauer
    function showNotification(message, type, duration) {
        type = type || 'info';
        duration = duration || 3000; // Standard: 3 Sekunden
        
        // Längere Anzeigezeit für Fehlermeldungen
        if (type === 'error' && duration < 5000) {
            duration = 5000; // Mindestens 5 Sekunden für Fehler
        }
        
        console.log('📢 Notification:', type, message);
        
        // Entferne vorherige Notifications
        $('.retexify-notification').remove();
        
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
            .html('<span class="retexify-notification-icon">' + icon + '</span>' + 
                  '<span class="retexify-notification-message">' + message + '</span>' +
                  '<button class="retexify-notification-close">&times;</button>')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'background': bgColor,
                'color': textColor,
                'padding': '12px 16px',
                'border-radius': '8px',
                'box-shadow': '0 4px 12px rgba(0,0,0,0.15)',
                'z-index': '999999',
                'max-width': '400px',
                'font-size': '14px',
                'font-weight': '500',
                'border': '1px solid rgba(255,255,255,0.2)',
                'display': 'flex',
                'align-items': 'center',
                'gap': '8px',
                'word-wrap': 'break-word'
            });
        
        // Close-Button Style
        $notification.find('.retexify-notification-close').css({
            'background': 'none',
            'border': 'none',
            'font-size': '18px',
            'cursor': 'pointer',
            'padding': '0',
            'margin-left': 'auto',
            'color': 'inherit',
            'opacity': '0.7'
        });
        
        $('body').append($notification);
        
        // Einblendanimation
        $notification.hide().fadeIn(300);
        
        // Close-Button Event
        $notification.find('.retexify-notification-close').on('click', function() {
            $notification.fadeOut(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-Hide nach der angegebenen Zeit
        setTimeout(function() {
            if ($notification.is(':visible')) {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }
        }, duration);
        
        // Click to dismiss
        $notification.click(function() {
            $(this).fadeOut(200, function() {
                $(this).remove();
            });
        });
    }
    
    // Dynamische Content-Optionen mit Zählung laden
    function loadExportContentOptions() {
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_export_stats',
                nonce: retexify_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    renderExportContentOptions(response.data);
                }
            }
        });
    }

    function renderExportContentOptions(stats) {
        var options = [
            { key: 'title', label: 'Titel', icon: '🏷️', count: (stats.posts ? stats.posts.total : 0) + (stats.pages ? stats.pages.total : 0) },
            { key: 'yoast_meta_title', label: 'Yoast Meta-Titel', icon: '🎯', count: stats.yoast_meta_title || 0 },
            { key: 'yoast_meta_description', label: 'Yoast Meta-Beschreibung', icon: '📝', count: stats.yoast_meta_description || 0 },
            { key: 'yoast_focus_keyword', label: 'Yoast Focus-Keyword', icon: '🔍', count: stats.yoast_focus_keyword || 0 },
            { key: 'wpbakery_meta_title', label: 'WPBakery Meta-Titel (Original)', icon: '🧩', count: stats.wpbakery_meta_title || 0 },
            { key: 'wpbakery_meta_description', label: 'WPBakery Meta-Beschreibung', icon: '🧩', count: stats.wpbakery_meta_description || 0 },
            { key: 'wpbakery_focus_keyword', label: 'WPBakery Focus-Keyword', icon: '🧩', count: stats.wpbakery_focus_keyword || 0 },
            { key: 'alt_texts', label: 'Alt-Texte', icon: '🖼️', count: stats.images ? stats.images.total : 0 }
        ];
        var html = '';
        options.forEach(function(opt) {
            html += '<label class="retexify-checkbox">';
            html += '<input type="checkbox" name="export_content[]" value="' + opt.key + '" checked> ';
            html += '<span class="retexify-checkbox-icon">' + opt.icon + '</span> ';
            html += opt.label +
                ' <span class="retexify-content-count">(' + opt.count + ')</span>';
            html += '</label>';
        });
        $('#retexify-export-content-options').html(html);
    }

    // Beim Laden des Export-Tabs Content-Optionen laden
    $(document).ready(function() {
        if ($('#retexify-export-content-options').length) {
            loadExportContentOptions();
        }
    });
    
    /**
     * Debug-Funktion für bessere Problemdiagnose
     */
    function debugImportState() {
        console.log('🔍 Debug Import-Status:', {
            currentUploadedFile: currentUploadedFile,
            fileInputValue: $('#retexify-csv-file-input').val(),
            importResultsVisible: $('#retexify-import-results').is(':visible'),
            uploadAreaClasses: $('#retexify-csv-upload-area').attr('class')
        });
    }
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (Vollständige Version mit Löschfunktion)!');
    console.log('🎯 NEUE FEATURES: Funktionierende Löschfunktion, verbesserte Notifications, längere Fehlermeldungen');
});