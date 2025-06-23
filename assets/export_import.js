/**
 * ReTexify AI Pro - Export/Import JavaScript (KORRIGIERT)
 * Version: 3.5.9 - Funktionierender Export mit korrigierten AJAX-Calls
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
    
    // ==== EXPORT FUNKTIONALITÄT (KORRIGIERT) ====
    
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
            'nonce': retexify_ajax.nonce
        };

        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                console.log('📊 Export-Statistiken erhalten:', response.data);
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
        
        // WPBakery und Alt-Text
        $('#wpbakery-count').text(stats.wpbakery || 0);
        $('#alt-texts-count').text(stats.alt_texts || 0);
        
        console.log('✅ Export-Zahlen aktualisiert');
    }
    
    // Fallback-Zählung (falls AJAX fehlschlägt)
    function performFallbackCounting() {
        console.log('⚠️ Führe Fallback-Zählung durch...');
        
        var estimatedPosts = 10;
        var estimatedPages = 5;
        
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
            var countElement = $('#' + type + '-count');
            if (countElement.length > 0) {
                totalRows += parseInt(countElement.text()) || 0;
            }
        });
        
        return Math.max(totalRows, 1);
    }
    
    // CSV-Export starten (KORRIGIERT)
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
            'nonce': retexify_ajax.nonce,
            'post_types': selectedData.post_types,
            'status': selectedData.status,
            'content': selectedData.content
        };

        console.log('📤 Sende Export-Request:', data);

        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: data,
            timeout: 120000, // 2 Minuten
            success: function(response) {
                console.log('📤 Export Response:', response);
                
                if (response.success) {
                    showNotification('✅ CSV-Export erfolgreich erstellt!', 'success');
                    
                    // Download starten
                    if (response.data.download_url) {
                        console.log('💾 Starte Download:', response.data.download_url);
                        
                        // Download per iframe (funktioniert besser als createElement)
                        var iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = response.data.download_url;
                        document.body.appendChild(iframe);
                        
                        // iframe nach 10 Sekunden entfernen
                        setTimeout(function() {
                            document.body.removeChild(iframe);
                        }, 10000);
                        
                        showNotification('💾 Download gestartet: ' + (response.data.filename || 'export.csv'), 'success');
                    }
                    
                    // Export-Vorschau ausblenden
                    $('#retexify-export-preview').slideUp(300);
                    
                } else {
                    showNotification('❌ Export fehlgeschlagen: ' + (response.data || 'Unbekannter Fehler'), 'error');
                }
                
                $btn.html(originalText).prop('disabled', false);
            },
            error: function(xhr, status, error) {
                console.error('❌ Export AJAX-Fehler:', status, error, xhr);
                showNotification('❌ Export-Verbindungsfehler: ' + error, 'error');
                $btn.html(originalText).prop('disabled', false);
            }
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
        
        if (file.size > 10 * 1024 * 1024) { // 10MB Limit
            showNotification('❌ Datei zu groß. Maximum: 10MB', 'error');
            return;
        }
        
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
            success: function(response) {
                console.log('📁 Upload-Response:', response);
                
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
                showNotification('❌ Upload-Verbindungsfehler', 'error');
            }
        });
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
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen!');
    console.log('📤 Export-Funktionen bereit');
    console.log('📥 Import-Funktionen bereit');
    console.log('🎯 Drag & Drop aktiviert');
    
    // Initial Export-Statistiken laden falls Tab bereits aktiv
    if ($('.retexify-tab-btn[data-tab="export-import"]').hasClass('active')) {
        setTimeout(loadExportStats, 500);
    }
});