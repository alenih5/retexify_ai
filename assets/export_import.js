/**
 * ReTexify AI Pro - Export/Import JavaScript Erweiterung
 * Version: 3.5.6 - Vollständige Export/Import Funktionalität
 * Ergänzt die bestehende admin-script.js
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet...');
    
    // ==== EXPORT/IMPORT TAB INITIALIZATION ====
    
    // Event-Delegation für Export/Import Tab
    $(document).on('click', '.retexify-tab-btn[data-tab="export-import"]', function() {
        console.log('📦 Export/Import Tab aktiviert');
        setTimeout(function() {
            initializeExportImport();
        }, 100);
    });
    
    function initializeExportImport() {
        console.log('📦 Initialisiere Export/Import Funktionen...');
        
        // Auto-load Export Stats wenn Tab geöffnet wird
        if ($('#tab-export-import').hasClass('active')) {
            loadExportStats();
        }
    }
    
    // ==== EXPORT FUNKTIONEN ====
    
    // Export-Statistiken laden
    $(document).on('click', '#retexify-load-export-stats', function(e) {
        e.preventDefault();
        console.log('📊 Lade Export-Statistiken...');
        loadExportStats();
    });
    
    function loadExportStats() {
        var $btn = $('#retexify-load-export-stats');
        var originalText = $btn.html();
        $btn.html('📊 Lade...').prop('disabled', true);
        
        $('#retexify-export-stats').html('<div class="retexify-loading">📊 Lade Export-Statistiken...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_export_stats',
                nonce: retexify_ajax.nonce
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('📊 Export Stats Response:', response);
                
                if (response.success) {
                    $('#retexify-export-stats').html(response.data).addClass('retexify-fade-in');
                    showNotification('✅ Export-Statistiken geladen', 'success');
                } else {
                    $('#retexify-export-stats').html('<div class="retexify-warning">❌ Fehler: ' + (response.data || 'Unbekannt') + '</div>');
                    showNotification('❌ Fehler beim Laden der Statistiken', 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Statistiken laden:', status, error);
                $('#retexify-export-stats').html('<div class="retexify-warning">❌ Verbindungsfehler: ' + error + '</div>');
                showNotification('❌ Verbindungsfehler bei Statistiken', 'error');
            }
        });
    }
    
    // Export-Vorschau
    $(document).on('click', '#retexify-export-preview', function(e) {
        e.preventDefault();
        console.log('👁️ Generiere Export-Vorschau...');
        
        var exportOptions = getExportOptions();
        if (!validateExportOptions(exportOptions)) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('👁️ Generiere...').prop('disabled', true);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_export_preview',
                nonce: retexify_ajax.nonce,
                post_types: exportOptions.post_types,
                post_status: exportOptions.post_status,
                fields: exportOptions.fields
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('👁️ Export Preview Response:', response);
                
                if (response.success) {
                    displayExportPreview(response.data);
                    showNotification('✅ Export-Vorschau generiert', 'success');
                } else {
                    showNotification('❌ Vorschau-Fehler: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler bei Export-Vorschau:', status, error);
                showNotification('❌ Verbindungsfehler bei Vorschau', 'error');
            }
        });
    });
    
    // Export starten
    $(document).on('click', '#retexify-export-start', function(e) {
        e.preventDefault();
        console.log('📤 Starte Export...');
        
        var exportOptions = getExportOptions();
        if (!validateExportOptions(exportOptions)) {
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<div class="retexify-processing"><div class="retexify-spinner"></div>📤 Exportiere...</div>').prop('disabled', true);
        
        // Progress-Bar anzeigen
        showProgressBar('#retexify-export-results');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_export_perform',
                nonce: retexify_ajax.nonce,
                post_types: exportOptions.post_types,
                post_status: exportOptions.post_status,
                fields: exportOptions.fields
            },
            timeout: 120000, // 2 Minuten für größere Exports
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('📤 Export Response:', response);
                
                if (response.success) {
                    displayExportResults(response.data);
                    showNotification('✅ Export erfolgreich abgeschlossen!', 'success');
                } else {
                    $('#retexify-export-results').html('<div class="retexify-results-error"><h4>❌ Export fehlgeschlagen</h4><p>' + (response.data || 'Unbekannter Fehler') + '</p></div>');
                    showNotification('❌ Export-Fehler: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Export:', status, error);
                $('#retexify-export-results').html('<div class="retexify-results-error"><h4>❌ Verbindungsfehler</h4><p>Export konnte nicht durchgeführt werden: ' + error + '</p></div>');
                showNotification('❌ Verbindungsfehler beim Export', 'error');
            }
        });
    });
    
    // ==== IMPORT FUNKTIONEN ====
    
    // CSV-Datei Upload Handler
    $(document).on('change', '#retexify-csv-file', function(e) {
        var file = e.target.files[0];
        console.log('📁 CSV-Datei ausgewählt:', file);
        
        if (file) {
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showNotification('❌ Bitte wählen Sie eine CSV-Datei aus', 'error');
                $(this).val('');
                return;
            }
            
            if (file.size > 10 * 1024 * 1024) { // 10MB Limit
                showNotification('❌ Datei zu groß (max. 10MB)', 'error');
                $(this).val('');
                return;
            }
            
            $('#retexify-file-name').text(file.name);
            $('#retexify-file-size').text(formatFileSize(file.size));
            $('#retexify-file-info').show();
            $('#retexify-import-start').prop('disabled', false);
            
            showNotification('✅ CSV-Datei bereit für Import', 'success');
        } else {
            $('#retexify-file-info').hide();
            $('#retexify-import-start').prop('disabled', true);
        }
    });
    
    // Drag & Drop Support
    $(document).on('dragover dragenter', '.retexify-file-label', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-over');
    });
    
    $(document).on('dragleave dragend drop', '.retexify-file-label', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-over');
    });
    
    $(document).on('drop', '.retexify-file-label', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $('#retexify-csv-file')[0].files = files;
            $('#retexify-csv-file').trigger('change');
        }
    });
    
    // Import starten
    $(document).on('click', '#retexify-import-start', function(e) {
        e.preventDefault();
        console.log('📥 Starte Import...');
        
        var csvFile = $('#retexify-csv-file')[0].files[0];
        if (!csvFile) {
            showNotification('❌ Bitte wählen Sie zuerst eine CSV-Datei aus', 'error');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<div class="retexify-processing"><div class="retexify-spinner"></div>📥 Importiere...</div>').prop('disabled', true);
        
        // Progress-Bar anzeigen
        showProgressBar('#retexify-import-results');
        
        var formData = new FormData();
        formData.append('action', 'retexify_import_perform');
        formData.append('nonce', retexify_ajax.nonce);
        formData.append('csv_file', csvFile);
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 180000, // 3 Minuten für größere Imports
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('📥 Import Response:', response);
                
                if (response.success) {
                    displayImportResults(response.data);
                    showNotification('✅ Import erfolgreich abgeschlossen!', 'success');
                    
                    // Datei-Input zurücksetzen
                    $('#retexify-csv-file').val('');
                    $('#retexify-file-info').hide();
                    $btn.prop('disabled', true);
                } else {
                    $('#retexify-import-results').html('<div class="retexify-results-error"><h4>❌ Import fehlgeschlagen</h4><p>' + (response.data || 'Unbekannter Fehler') + '</p></div>');
                    showNotification('❌ Import-Fehler: ' + (response.data || 'Unbekannt'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim Import:', status, error);
                $('#retexify-import-results').html('<div class="retexify-results-error"><h4>❌ Verbindungsfehler</h4><p>Import konnte nicht durchgeführt werden: ' + error + '</p></div>');
                showNotification('❌ Verbindungsfehler beim Import', 'error');
            }
        });
    });
    
    // ==== SYSTEM CHECK ====
    
    $(document).on('click', '#retexify-system-check', function(e) {
        e.preventDefault();
        console.log('🔧 Starte System-Check...');
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('🧪 Teste...').prop('disabled', true);
        
        $('#retexify-system-check-results').html('<div class="retexify-loading">🔧 Führe System-Check durch...</div>');
        
        $.ajax({
            url: retexify_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'retexify_system_check',
                nonce: retexify_ajax.nonce
            },
            timeout: 30000,
            success: function(response) {
                $btn.html(originalText).prop('disabled', false);
                console.log('🔧 System Check Response:', response);
                
                if (response.success) {
                    $('#retexify-system-check-results').html(response.data).addClass('retexify-slide-up');
                    showNotification('✅ System-Check abgeschlossen', 'success');
                } else {
                    $('#retexify-system-check-results').html('<div class="retexify-warning">❌ System-Check fehlgeschlagen: ' + (response.data || 'Unbekannt') + '</div>');
                    showNotification('❌ System-Check Fehler', 'error');
                }
            },
            error: function(xhr, status, error) {
                $btn.html(originalText).prop('disabled', false);
                console.error('❌ AJAX Fehler beim System-Check:', status, error);
                $('#retexify-system-check-results').html('<div class="retexify-warning">❌ Verbindungsfehler beim System-Check</div>');
                showNotification('❌ Verbindungsfehler beim System-Check', 'error');
            }
        });
    });
    
    // ==== HELPER FUNKTIONEN ====
    
    function getExportOptions() {
        var post_types = [];
        $('input[name="export_post_types[]"]:checked').each(function() {
            post_types.push($(this).val());
        });
        
        var post_status = [];
        $('input[name="export_post_status[]"]:checked').each(function() {
            post_status.push($(this).val());
        });
        
        var fields = [];
        $('input[name="export_fields[]"]:checked').each(function() {
            fields.push($(this).val());
        });
        
        return {
            post_types: post_types,
            post_status: post_status,
            fields: fields
        };
    }
    
    function validateExportOptions(options) {
        if (options.post_types.length === 0) {
            showNotification('❌ Bitte wählen Sie mindestens einen Post-Typ aus', 'error');
            return false;
        }
        
        if (options.post_status.length === 0) {
            showNotification('❌ Bitte wählen Sie mindestens einen Status aus', 'error');
            return false;
        }
        
        if (options.fields.length === 0) {
            showNotification('❌ Bitte wählen Sie mindestens ein Feld aus', 'error');
            return false;
        }
        
        return true;
    }
    
    function displayExportPreview(data) {
        var html = '<div class="retexify-results-success retexify-fade-in">';
        html += '<h4>👁️ Export-Vorschau</h4>';
        html += '<p>' + data.message + '</p>';
        
        if (data.preview && data.preview.length > 0) {
            html += '<div class="retexify-preview-table">';
            html += '<table class="widefat fixed striped">';
            
            // Header
            var firstRow = data.preview[0];
            html += '<thead><tr>';
            for (var key in firstRow) {
                html += '<th>' + escapeHtml(key) + '</th>';
            }
            html += '</tr></thead>';
            
            // Daten
            html += '<tbody>';
            data.preview.forEach(function(row) {
                html += '<tr>';
                for (var key in row) {
                    var value = row[key] || '';
                    if (typeof value === 'string' && value.length > 50) {
                        value = value.substring(0, 50) + '...';
                    }
                    html += '<td>' + escapeHtml(value) + '</td>';
                }
                html += '</tr>';
            });
            html += '</tbody>';
            
            html += '</table>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $('#retexify-export-results').html(html);
    }
    
    function displayExportResults(data) {
        var html = '<div class="retexify-results-success retexify-fade-in">';
        html += '<h4>✅ Export erfolgreich abgeschlossen</h4>';
        html += '<p>' + data.message + '</p>';
        
        html += '<div class="retexify-summary-grid">';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + data.total_posts + '</div>';
        html += '<div class="retexify-summary-label">Posts</div>';
        html += '</div>';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + data.total_fields + '</div>';
        html += '<div class="retexify-summary-label">Felder</div>';
        html += '</div>';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + data.file_size + '</div>';
        html += '<div class="retexify-summary-label">Dateigröße</div>';
        html += '</div>';
        html += '</div>';
        
        if (data.download_url) {
            html += '<a href="' + data.download_url + '" class="retexify-download-link" download="' + data.filename + '">';
            html += '📥 CSV-Datei herunterladen (' + data.filename + ')';
            html += '</a>';
        }
        
        html += '</div>';
        
        $('#retexify-export-results').html(html);
    }
    
    function displayImportResults(data) {
        var results = data.results;
        
        var html = '<div class="retexify-results-success retexify-fade-in">';
        html += '<h4>✅ Import abgeschlossen</h4>';
        html += '<p>' + data.message + '</p>';
        
        html += '<div class="retexify-import-summary">';
        html += '<h5>📊 Import-Zusammenfassung</h5>';
        
        html += '<div class="retexify-summary-grid">';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + results.processed + '</div>';
        html += '<div class="retexify-summary-label">Verarbeitet</div>';
        html += '</div>';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + results.updated + '</div>';
        html += '<div class="retexify-summary-label">Aktualisiert</div>';
        html += '</div>';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + results.skipped + '</div>';
        html += '<div class="retexify-summary-label">Übersprungen</div>';
        html += '</div>';
        html += '<div class="retexify-summary-item">';
        html += '<div class="retexify-summary-number">' + results.errors.length + '</div>';
        html += '<div class="retexify-summary-label">Fehler</div>';
        html += '</div>';
        html += '</div>';
        
        if (results.details && results.details.length > 0) {
            html += '<h5>✅ Erfolgreich aktualisierte Posts:</h5>';
            html += '<div class="retexify-error-list">';
            html += '<ul>';
            results.details.forEach(function(detail) {
                html += '<li>✓ ' + escapeHtml(detail) + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        if (results.errors && results.errors.length > 0) {
            html += '<h5>❌ Fehler-Details:</h5>';
            html += '<div class="retexify-error-list">';
            html += '<ul>';
            results.errors.forEach(function(error) {
                html += '<li>❌ ' + escapeHtml(error) + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        html += '</div>';
        html += '</div>';
        
        $('#retexify-import-results').html(html);
    }
    
    function showProgressBar(container) {
        var html = '<div class="retexify-progress-bar">';
        html += '<div class="retexify-progress-fill"></div>';
        html += '</div>';
        html += '<div class="retexify-processing">';
        html += '<div class="retexify-spinner"></div>';
        html += '<p>Verarbeitung läuft...</p>';
        html += '</div>';
        
        $(container).html(html);
        
        // Simuliere Progress (da wir keinen echten Progress-Callback haben)
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            $('.retexify-progress-fill').css('width', progress + '%');
        }, 500);
        
        // Stop nach 30 Sekunden
        setTimeout(function() {
            clearInterval(interval);
        }, 30000);
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Gemeinsame Notification-Funktion (falls nicht bereits in admin-script.js vorhanden)
    function showNotification(message, type) {
        // Prüfen ob Funktion bereits existiert
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
            return;
        }
        
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
        
        // Auto-remove nach 4 Sekunden
        setTimeout(function() {
            $notification.animate({
                transform: 'translateX(100%)',
                opacity: 0
            }, 300, function() {
                $(this).remove();
            });
        }, 4000);
        
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
    
    // Auto-Initialize wenn Export/Import Tab bereits aktiv ist
    if ($('#tab-export-import').hasClass('active')) {
        initializeExportImport();
    }
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen!');
});