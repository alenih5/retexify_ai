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
    var lastExportSelection = null;

    $(document).on('click', '#retexify-preview-export', function(e) {
        e.preventDefault();
        updateExportPreview(true);
    });

    $(document).on('change', 'input[name="export_post_types[]"], input[name="export_status[]"], input[name="export_content[]"]', function() {
        // Vorschau nur aktualisieren, wenn sie sichtbar ist
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
        // Hole aktuelle Statistiken für die Anzeige der Anzahlen
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
                    // Post-Typen mit Anzahl
                    previewHtml += '<div class="retexify-export-item">';
                    var postTypeLabels = [];
                    if (selection.post_types.includes('post')) postTypeLabels.push('Beiträge (' + (stats.posts ? stats.posts.total : 0) + ')');
                    if (selection.post_types.includes('page')) postTypeLabels.push('Seiten (' + (stats.pages ? stats.pages.total : 0) + ')');
                    previewHtml += '<strong>Post-Typen:</strong> ' + postTypeLabels.join(', ');
                    previewHtml += '</div>';
                    // Status mit Anzahl (Dummy, da keine genaue Zählung im Beispiel)
                    previewHtml += '<div class="retexify-export-item">';
                    var statusLabels = [];
                    if (selection.status.includes('publish')) statusLabels.push('Veröffentlicht');
                    if (selection.status.includes('draft')) statusLabels.push('Entwürfe');
                    previewHtml += '<strong>Status:</strong> ' + statusLabels.join(', ');
                    previewHtml += '</div>';
                    // Content-Typen mit Anzahl
                    previewHtml += '<div class="retexify-export-item">';
                    var contentLabels = [];
                    selection.content.forEach(function(key) {
                        var count = stats[key] || (stats.images && key === 'alt_texts' ? stats.images.total : 0);
                        var label = $("input[name='export_content[]'][value='"+key+"']").closest('label').text().trim();
                        contentLabels.push(label + ' (' + count + ')');
                    });
                    previewHtml += '<strong>Inhalte:</strong> ' + contentLabels.join(', ');
                    previewHtml += '</div>';
                    // Geschätzte Zeilen
                    previewHtml += '<div class="retexify-export-item">';
                    previewHtml += '<strong>Geschätzte Zeilen:</strong> ~' + estimateExportRows(selection);
                    previewHtml += '</div>';
                    // Info
                    previewHtml += '<div class="retexify-export-item retexify-highlight">';
                    previewHtml += '<strong>✅ Sauberes Design:</strong> Nur ausgewählte Daten werden exportiert!';
                    previewHtml += '</div>';
                    previewHtml += '</div>';
                    previewHtml += '<button id="retexify-start-export" class="button button-primary">📤 Export starten</button>';
                    previewHtml += '</div>';
                    if (forceShow) {
                        $('#retexify-export-preview').html(previewHtml).slideDown(300);
                    } else {
                        $('#retexify-export-preview').html(previewHtml);
                    }
                }
            }
        });
    }
    
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
        // Content-Typen (nur angehakt!)
        $('input[name="export_content[]"]:checked').each(function() {
            selection.content.push($(this).val());
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
        
        // Button-Handling
        $('#retexify-import-start').remove(); // Vorherigen Button entfernen
        var btn = $('<button id="retexify-import-start" class="button button-primary" style="margin-top:15px;">Import starten</button>');
        btn.on('click', function(e) {
            e.preventDefault();
            startImport();
        });
        $('#retexify-import-preview').after(btn);
    }
    
    // Import starten
    function startImport() {
        // Hier kann die Import-Logik ergänzt/geprüft werden
        showNotification('🚀 Import wird gestartet...', 'success');
        // TODO: AJAX-Call für Import
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
    
    // --- Import-Button nach Upload anzeigen und Import-Logik prüfen ---
    $(document).on('change', '#retexify-csv-file-input', function() {
        setTimeout(function() {
            if ($('#retexify-import-start').length === 0) {
                var btn = $('<button id="retexify-import-start" class="button button-primary" style="margin-top:15px;">Import starten</button>');
                btn.on('click', function(e) {
                    e.preventDefault();
                    startImport();
                });
                $('#retexify-import-results').after(btn);
            }
        }, 500);
    });

    function startImport() {
        // Hier kann die Import-Logik ergänzt/geprüft werden
        showNotification('🚀 Import wird gestartet...', 'success');
        // TODO: AJAX-Call für Import
    }
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (Clean Version)!');
    console.log('✅ VERBESSERUNGEN: Keine überflüssigen Statistiken, sauberes minimalistisches Design');
});