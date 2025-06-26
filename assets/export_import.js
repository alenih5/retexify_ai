/**
 * ReTexify AI Pro - KORRIGIERTE Export/Import JavaScript
 * Version: 3.5.9 - Vollständige Import-Funktionalität
 * 
 * FIXES:
 * ✅ Vollständige Import-UI mit Datei-Verwaltung
 * ✅ Import-Button und Vorschau-Funktionalität
 * ✅ Datei-Anzeige und Lösch-Funktionalität
 * ✅ Korrekte AJAX-Behandlung
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet (KORRIGIERTE Version)...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        console.log('⚠️ Export/Import Tab nicht gefunden - beende Script');
        return;
    }

    console.log('✅ ReTexify Export/Import Script geladen (KORRIGIERTE Version).');
    
    // ==== EXPORT FUNKTIONALITÄT (unverändert, funktioniert bereits) ====
    
    // Export-Vorschau anzeigen
    var lastExportSelection = null;

    $(document).on('click', '#retexify-preview-export', function(e) {
        e.preventDefault();
        updateExportPreview(true);
    });

    $(document).on('change', 'input[name="export_post_types[]"], input[name="export_status[]"], input[name="export_content[]"]', function() {
        if ($('#retexify-export-preview').is(':visible')) {
            updateExportPreview(false);
        }
    });

    function updateExportPreview(forceShow) {
        var selection = collectExportSelection();
        lastExportSelection = selection;
        if (selection.post_types.length === 0 || selection.content.length === 0) {
            $('#retexify-export-preview').slideUp(300);
            return;
        }
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_get_export_stats',
                nonce: retexify_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    var stats = response.data;
                    var previewHtml = '<div class="retexify-export-summary">';
                    previewHtml += '<h4>📋 Export-Vorschau</h4>';
                    previewHtml += '<div class="retexify-export-details">';
                    
                    var postTypeLabels = [];
                    if (selection.post_types.includes('post')) postTypeLabels.push('Beiträge (' + (stats.posts ? stats.posts.total : 0) + ')');
                    if (selection.post_types.includes('page')) postTypeLabels.push('Seiten (' + (stats.pages ? stats.pages.total : 0) + ')');
                    previewHtml += '<div class="retexify-export-item">';
                    previewHtml += '<strong>Post-Typen:</strong> ' + postTypeLabels.join(', ');
                    previewHtml += '</div>';
                    
                    var statusLabels = [];
                    if (selection.status.includes('publish')) statusLabels.push('Veröffentlicht');
                    if (selection.status.includes('draft')) statusLabels.push('Entwürfe');
                    previewHtml += '<div class="retexify-export-item">';
                    previewHtml += '<strong>Status:</strong> ' + statusLabels.join(', ');
                    previewHtml += '</div>';
                    
                    var contentLabels = [];
                    selection.content.forEach(function(key) {
                        var count = stats[key] || (stats.images && key === 'alt_texts' ? stats.images.total : 0);
                        var label = $("input[name='export_content[]'][value='"+key+"']").closest('label').text().trim();
                        contentLabels.push(label + ' (' + count + ')');
                    });
                    previewHtml += '<div class="retexify-export-item">';
                    previewHtml += '<strong>Inhalte:</strong> ' + contentLabels.join(', ');
                    previewHtml += '</div>';
                    
                    previewHtml += '</div></div>';
                    
                    if (forceShow) {
                        $('#retexify-export-preview').html(previewHtml).slideDown(300);
                    } else {
                        $('#retexify-export-preview').html(previewHtml);
                    }
                }
            }
        });
    }
    
    function collectExportSelection() {
        var selection = {
            post_types: [],
            status: [],
            content: []
        };
        
        $('input[name="export_post_types[]"]:checked').each(function() {
            selection.post_types.push($(this).val());
        });
        
        $('input[name="export_status[]"]:checked').each(function() {
            selection.status.push($(this).val());
        });
        
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
        });
        
        return selection;
    }
    
    function collectExportSelectionForAPI() {
        var selection = {
            post_types: [],
            status: [],
            content: []
        };
        
        $('input[name="export_post_types[]"]:checked').each(function() {
            selection.post_types.push($(this).val());
        });
        
        $('input[name="export_status[]"]:checked').each(function() {
            selection.status.push($(this).val());
        });
        
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
        });
        
        console.log('📤 Export-Auswahl für API:', selection);
        return selection;
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
            console.log('📤 Export-Response:', response);
            
            if (response.success) {
                showNotification('✅ CSV-Export erfolgreich!', 'success');
                
                if (response.data.download_url) {
                    var link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = response.data.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    showNotification('💾 Download gestartet: ' + response.data.filename, 'success');
                }
                
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
    
    // ==== KORRIGIERTE IMPORT FUNKTIONALITÄT ====
    
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
    
    // KORRIGIERT: Datei-Upload mit vollständiger UI-Behandlung
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
        
        // Upload-Status anzeigen
        showUploadProgress(file.name, file.size);
        
        var formData = new FormData();
        formData.append('csv_file', file);
        formData.append('action', 'retexify_import_csv_data');
        formData.append('nonce', retexify_ajax.nonce);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000,
            success: function(response) {
                console.log('📁 Upload Response:', response);
                
                if (response.success) {
                    currentUploadedFile = response.data.filename;
                    showNotification('✅ Datei erfolgreich hochgeladen: ' + file.name, 'success');
                    
                    // NEU: Import-Vorschau laden
                    loadImportPreview(response.data.filename);
                } else {
                    showNotification('❌ Upload fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                    hideUploadStatus();
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Upload AJAX-Fehler:', status, error);
                showNotification('❌ Upload fehlgeschlagen - Verbindungsfehler', 'error');
                hideUploadStatus();
            }
        });
    }
    
    // NEU: Upload-Progress anzeigen
    function showUploadProgress(filename, filesize) {
        var progressHtml = '<div class="retexify-upload-status">';
        progressHtml += '<h4>📤 Upload läuft...</h4>';
        progressHtml += '<div class="retexify-upload-progress">';
        progressHtml += '<div class="retexify-progress-bar">';
        progressHtml += '<div class="retexify-progress-fill" id="upload-progress-fill"></div>';
        progressHtml += '</div>';
        progressHtml += '<div class="retexify-progress-text">';
        progressHtml += '<p><strong>Datei:</strong> ' + filename + '</p>';
        progressHtml += '<p><strong>Größe:</strong> ' + formatFileSize(filesize) + '</p>';
        progressHtml += '</div>';
        progressHtml += '</div>';
        progressHtml += '</div>';
        
        // Bestehende Ergebnisse ausblenden
        $('#retexify-import-results').hide();
        
        // Progress anzeigen
        $uploadArea.after(progressHtml);
        
        // Animiere Progress Bar
        var progress = 0;
        var progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            $('#upload-progress-fill').css('width', progress + '%');
        }, 200);
        
        window.uploadProgressInterval = progressInterval;
    }
    
    // NEU: Upload-Status ausblenden
    function hideUploadStatus() {
        $('.retexify-upload-status').remove();
        if (window.uploadProgressInterval) {
            clearInterval(window.uploadProgressInterval);
            window.uploadProgressInterval = null;
        }
    }
    
    // KORRIGIERT: Import-Vorschau laden
    function loadImportPreview(filename) {
        console.log('👁️ Lade Import-Vorschau für:', filename);
        
        hideUploadStatus();
        
        var data = {
            'action': 'retexify_get_import_preview',
            'nonce': retexify_ajax.nonce,
            'filename': filename
        };
        
        $.post(retexify_ajax.ajax_url, data, function(response) {
            console.log('👁️ Import-Vorschau Response:', response);
            
            if (response.success) {
                displayImportPreview(response.data);
                importData = response.data;
                showNotification('👁️ Import-Vorschau geladen', 'success');
            } else {
                showNotification('❌ Vorschau-Fehler: ' + (response.data || 'Unbekannter Fehler'), 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('❌ Vorschau AJAX-Fehler:', status, error);
            showNotification('❌ Verbindungsfehler bei Import-Vorschau', 'error');
        });
    }
    
    // KORRIGIERT: Import-Vorschau anzeigen mit Datei-Verwaltung
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
        
        // NEU: Import-Aktionen
        previewHtml += '<div class="retexify-import-actions">';
        previewHtml += '<button type="button" id="retexify-start-import" class="retexify-btn retexify-btn-primary retexify-btn-large">';
        previewHtml += '📥 Import starten';
        previewHtml += '</button>';
        previewHtml += '<button type="button" id="retexify-show-import-mapping" class="retexify-btn retexify-btn-secondary retexify-btn-large">';
        previewHtml += '🔗 Spalten-Zuordnung';
        previewHtml += '</button>';
        previewHtml += '</div>';
        
        previewHtml += '</div>';
        
        // Vorschau anzeigen
        $('#retexify-import-results').html(previewHtml).slideDown(300);
    }
    
    // NEU: Import starten (vollständige Funktionalität)
    $(document).on('click', '#retexify-start-import', function(e) {
        e.preventDefault();
        console.log('📥 Import starten ausgelöst');
        
        if (!currentUploadedFile) {
            showNotification('❌ Keine Datei zum Importieren vorhanden', 'error');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('📥 Importiere...').prop('disabled', true);
        
        // Import-Progress anzeigen
        showImportProgress();
        
        var data = {
            'action': 'retexify_save_imported_data',
            'nonce': retexify_ajax.nonce,
            'filename': currentUploadedFile,
            'column_mapping': {} // Standard-Mapping verwenden
        };
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: data,
            timeout: 120000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                hideImportProgress();
                console.log('📥 Import Response:', response);
                
                if (response.success) {
                    displayImportSuccess(response.data);
                    showNotification('✅ Import erfolgreich! ' + response.data.total_processed + ' Einträge verarbeitet', 'success');
                    
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
    
    // NEU: Hochgeladene Datei löschen
    $(document).on('click', '#retexify-delete-uploaded-file', function(e) {
        e.preventDefault();
        console.log('🗑️ Datei löschen ausgelöst');
        
        if (!currentUploadedFile) {
            showNotification('❌ Keine Datei zum Löschen vorhanden', 'error');
            return;
        }
        
        if (!confirm('Möchten Sie die hochgeladene Datei wirklich entfernen?')) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('🗑️ Lösche...').prop('disabled', true);
        
        var data = {
            'action': 'retexify_delete_upload',
            'nonce': retexify_ajax.nonce,
            'filename': currentUploadedFile
        };
        
        $.post(retexify_ajax.ajax_url, data, function(response) {
            $btn.html(originalText).prop('disabled', false);
            
            if (response.success) {
                showNotification('✅ Datei erfolgreich entfernt', 'success');
                
                // UI zurücksetzen
                $('#retexify-import-results').slideUp(300);
                currentUploadedFile = null;
                $fileInput.val('');
                
            } else {
                showNotification('❌ Fehler beim Löschen: ' + (response.data || 'Unbekannter Fehler'), 'error');
            }
        }).fail(function() {
            $btn.html(originalText).prop('disabled', false);
            showNotification('❌ Verbindungsfehler beim Löschen', 'error');
        });
    });
    
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
    }
    
    // KORRIGIERT: Import-Erfolg anzeigen
    function displayImportSuccess(data) {
        var successHtml = '<div class="retexify-import-success">';
        successHtml += '<h4>🎉 Import erfolgreich abgeschlossen!</h4>';
        
        successHtml += '<div class="retexify-import-stats">';
        
        successHtml += '<div class="retexify-import-stat">';
        successHtml += '<span class="retexify-import-stat-number">' + data.total_processed + '</span>';
        successHtml += '<span class="retexify-import-stat-label">Verarbeitet</span>';
        successHtml += '</div>';
        
        successHtml += '<div class="retexify-import-stat">';
        successHtml += '<span class="retexify-import-stat-number">' + (data.updated || 0) + '</span>';
        successHtml += '<span class="retexify-import-stat-label">Aktualisiert</span>';
        successHtml += '</div>';
        
        successHtml += '<div class="retexify-import-stat">';
        successHtml += '<span class="retexify-import-stat-number">' + (data.imported || 0) + '</span>';
        successHtml += '<span class="retexify-import-stat-label">Neu importiert</span>';
        successHtml += '</div>';
        
        successHtml += '</div>';
        
        // Fehler anzeigen falls vorhanden
        if (data.errors && data.errors.length > 0) {
            successHtml += '<div class="retexify-import-errors">';
            successHtml += '<h6>⚠️ Warnungen (' + data.errors.length + '):</h6>';
            successHtml += '<ul class="retexify-error-list">';
            data.errors.slice(0, 5).forEach(function(error) {
                successHtml += '<li>' + error + '</li>';
            });
            if (data.errors.length > 5) {
                successHtml += '<li>... und ' + (data.errors.length - 5) + ' weitere</li>';
            }
            successHtml += '</ul>';
            successHtml += '</div>';
        }
        
        successHtml += '<div class="retexify-import-actions">';
        successHtml += '<button type="button" id="retexify-new-import" class="retexify-btn retexify-btn-primary">';
        successHtml += '📁 Neue Datei importieren';
        successHtml += '</button>';
        successHtml += '</div>';
        
        successHtml += '</div>';
        
        $('#retexify-import-results').html(successHtml);
    }
    
    // NEU: Neuen Import starten
    $(document).on('click', '#retexify-new-import', function(e) {
        e.preventDefault();
        
        // UI zurücksetzen
        $('#retexify-import-results').slideUp(300);
        currentUploadedFile = null;
        $fileInput.val('');
        
        showNotification('📁 Bereit für neuen Import', 'success');
    });
    
    // ==== HILFSFUNKTIONEN ====
    
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
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
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
        
        $notification.css('transform', 'translateX(100%)').animate({
            transform: 'translateX(0)'
        }, 300);
        
        setTimeout(function() {
            $notification.animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 300, function() {
                $(this).remove();
            });
        }, 5000);
        
        $notification.click(function() {
            $(this).animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 200, function() {
                $(this).remove();
            });
        });
    }
    
    // Dynamische Content-Optionen laden
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
            { key: 'wpbakery_meta_title', label: 'WPBakery Meta-Titel', icon: '🧩', count: stats.wpbakery_meta_title || 0 },
            { key: 'wpbakery_meta_description', label: 'WPBakery Meta-Beschreibung', icon: '🧩', count: stats.wpbakery_meta_description || 0 },
            { key: 'wpbakery_focus_keyword', label: 'WPBakery Focus-Keyword', icon: '🧩', count: stats.wpbakery_focus_keyword || 0 },
            { key: 'alt_texts', label: 'Alt-Texte', icon: '🖼️', count: stats.images ? stats.images.total : 0 }
        ];
        
        var html = '';
        options.forEach(function(opt) {
            html += '<label class="retexify-checkbox">';
            html += '<input type="checkbox" name="export_content[]" value="' + opt.key + '" checked> ';
            html += '<span class="retexify-checkbox-icon">' + opt.icon + '</span> ';
            html += opt.label + ' <span class="retexify-content-count">(' + opt.count + ')</span>';
            html += '</label>';
        });
        $('#retexify-export-content-options').html(html);
    }

    // Beim Laden Content-Optionen laden
    if ($('#retexify-export-content-options').length) {
        loadExportContentOptions();
    }
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (KORRIGIERTE Version)!');
    console.log('✅ NEUE FEATURES: Vollständige Import-UI, Datei-Verwaltung, Import-Button');
});