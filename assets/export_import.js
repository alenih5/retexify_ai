/**
 * ReTexify AI Pro - Export/Import JavaScript - VERBESSERTES DESIGN
 * Version: 3.5.9 - Schöne Statistiken mit einheitlichem X/Y Format
 * 
 * VERBESSERUNGEN:
 * ✅ Einheitliches "X/Y" Format für alle Statistiken  
 * ✅ Schöneres Design mit modernen Karten
 * ✅ WPBakery-Statistiken versteckt (nicht relevant neben Yoast)
 * ✅ Zusammengefasste, übersichtlichere Darstellung
 */

jQuery(document).ready(function($) {
    console.log('🚀 ReTexify Export/Import JavaScript startet (verbessertes Design)...');
    
    // Globale Variablen
    var exportData = {};
    var importData = {};
    var currentUploadedFile = null;
    
    // Nur ausführen, wenn der Export/Import-Tab vorhanden ist
    if ($('#tab-export-import').length === 0) {
        console.log('⚠️ Export/Import Tab nicht gefunden - beende Script');
        return;
    }

    console.log('✅ ReTexify Export/Import Script geladen (verbessertes Design).');
    
    // ==== VERBESSERTE EXPORT FUNKTIONALITÄT ====
    
    // Export-Statistiken beim Tab-Wechsel laden
    $(document).on('click', '.retexify-tab-btn[data-tab="export-import"]', function() {
        console.log('📤 Export/Import Tab aktiviert - lade verbesserte Statistiken...');
        initializeStatsGrid();
        setTimeout(loadExportStats, 100);
    });
    
    // Initiale Statistik-Grid erstellen (falls nicht vorhanden)
    function initializeStatsGrid() {
        if ($('.retexify-stats-grid').length === 0) {
            var statsHtml = `
                <div class="retexify-export-stats">
                    <h4>Export-Statistiken</h4>
                    <div class="retexify-stats-grid">
                        <!-- Statistik-Karten werden hier dynamisch eingefügt -->
                    </div>
                </div>
            `;
            
            // Am Anfang des Export-Containers einfügen
            $('#tab-export-import').prepend(statsHtml);
        }
        
        // Loading-Animation starten
        $('.retexify-stats-grid').html(`
            <div class="retexify-stat-card loading posts-pages">
                <div class="retexify-stat-label">Posts/Seiten</div>
                <div class="retexify-stat-number">
                    <span class="retexify-stat-current">--</span>
                    <span class="retexify-stat-separator">/</span>
                    <span class="retexify-stat-total">--</span>
                </div>
                <div class="retexify-stat-description">Lädt...</div>
            </div>
            <div class="retexify-stat-card loading yoast-data">
                <div class="retexify-stat-label">Yoast SEO Daten</div>
                <div class="retexify-stat-number">
                    <span class="retexify-stat-current">--</span>
                    <span class="retexify-stat-separator">/</span>
                    <span class="retexify-stat-total">--</span>
                </div>
                <div class="retexify-stat-description">Lädt...</div>
            </div>
            <div class="retexify-stat-card loading media-data">
                <div class="retexify-stat-label">Medien Alt-Texte</div>
                <div class="retexify-stat-number">
                    <span class="retexify-stat-current">--</span>
                    <span class="retexify-stat-separator">/</span>
                    <span class="retexify-stat-total">--</span>
                </div>
                <div class="retexify-stat-description">Lädt...</div>
            </div>
        `);
    }
    
    // Export-Statistiken laden
    function loadExportStats() {
        console.log('📊 Lade verbesserte Export-Statistiken...');
        
        var data = {
            'action': 'retexify_get_export_stats',
            'nonce': retexify_ajax.nonce
        };

        $.post(retexify_ajax.ajax_url, data, function(response) {
            if (response.success) {
                console.log('📊 Verbesserte Export-Statistiken erhalten:', response.data);
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
    
    // VERBESSERTE Export-Zahlen aktualisieren mit einheitlichem X/Y Format
    function updateExportCounts(stats) {
        console.log('📊 Aktualisiere verbesserte Export-Zahlen:', stats);
        
        // Gesamtzahl Posts/Seiten für Referenz
        var totalPosts = (stats.post || 0) + (stats.page || 0);
        
        // 1. Posts/Seiten Übersicht (X/Y Format)
        updateStatCard('posts-pages', {
            current: stats.publish || 0,
            total: totalPosts,
            label: 'Posts/Seiten',
            description: 'Verfügbare Inhalte zum Export',
            cssClass: 'posts-pages'
        });
        
        // 2. Yoast SEO Daten zusammengefasst (X/Y Format)
        var yoastTotal = Math.max(
            stats.yoast_meta_title || 0,
            stats.yoast_meta_description || 0,
            stats.yoast_focus_keyword || 0
        );
        updateStatCard('yoast-seo', {
            current: yoastTotal,
            total: totalPosts,
            label: 'Yoast SEO Daten',
            description: 'Meta-Titel, Beschreibungen, Keywords',
            cssClass: 'yoast-data'
        });
        
        // 3. Medien/Alt-Texte (eigenständige Kategorie)
        updateStatCard('media-alt', {
            current: stats.alt_texts || 0,
            total: stats.alt_texts || 0,
            label: 'Medien Alt-Texte',
            description: 'Bilder in der Mediendatenbank',
            cssClass: 'media-data'
        });
        
        console.log('✅ Verbesserte Export-Zahlen aktualisiert');
    }
    
    // Hilfsfunktion: Einzelne Statistik-Karte aktualisieren
    function updateStatCard(cardId, data) {
        var $card = $('.retexify-stat-card').filter(function() {
            return $(this).hasClass(data.cssClass);
        });
        
        // Falls Karte nicht existiert, erstellen
        if ($card.length === 0) {
            createStatCard(cardId, data);
            return;
        }
        
        // Zahlen aktualisieren
        $card.find('.retexify-stat-current').text(data.current);
        $card.find('.retexify-stat-total').text(data.total);
        $card.find('.retexify-stat-label').text(data.label);
        $card.find('.retexify-stat-description').text(data.description);
        
        // CSS-Klasse setzen und Loading entfernen
        $card.removeClass('loading').addClass(data.cssClass);
    }
    
    // Neue Statistik-Karte erstellen
    function createStatCard(cardId, data) {
        var cardHtml = `
            <div class="retexify-stat-card ${data.cssClass}">
                <div class="retexify-stat-label">${data.label}</div>
                <div class="retexify-stat-number">
                    <span class="retexify-stat-current">${data.current}</span>
                    <span class="retexify-stat-separator">/</span>
                    <span class="retexify-stat-total">${data.total}</span>
                </div>
                <div class="retexify-stat-description">${data.description}</div>
            </div>
        `;
        
        // In Grid einfügen
        $('.retexify-stats-grid').append(cardHtml);
    }
    
    // VERBESSERTE Fallback-Zählung
    function performFallbackCounting() {
        console.log('⚠️ Führe verbesserte Fallback-Zählung durch...');
        
        // Geschätzte Werte mit realistischen Zahlen
        var estimatedTotal = 15;
        var estimatedPublished = 9;
        var estimatedYoast = 9;
        var estimatedMedia = 54;
        
        setTimeout(function() {
            updateStatCard('posts-pages', {
                current: estimatedPublished,
                total: estimatedTotal,
                label: 'Posts/Seiten',
                description: 'Verfügbare Inhalte zum Export',
                cssClass: 'posts-pages'
            });
            
            updateStatCard('yoast-seo', {
                current: estimatedYoast,
                total: estimatedTotal,
                label: 'Yoast SEO Daten',
                description: 'Meta-Titel, Beschreibungen, Keywords',
                cssClass: 'yoast-data'
            });
            
            updateStatCard('media-alt', {
                current: estimatedMedia,
                total: estimatedMedia,
                label: 'Medien Alt-Texte',
                description: 'Bilder in der Mediendatenbank',
                cssClass: 'media-data'
            });
            
            showNotification('⚠️ Export-Statistiken konnten nicht geladen werden. Schätzwerte angezeigt.', 'warning');
        }, 500);
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
        previewHtml += '<h5>📋 Export-Zusammenfassung (Optimiertes Design):</h5>';
        previewHtml += '<p><strong>Post-Typen:</strong> ' + selectedData.post_types.join(', ') + '</p>';
        previewHtml += '<p><strong>Status:</strong> ' + selectedData.status.join(', ') + '</p>';
        previewHtml += '<p><strong>Content-Typen:</strong> ' + selectedData.content.join(', ') + '</p>';
        previewHtml += '<p><strong>Geschätzte Einträge:</strong> ~' + estimateExportRows(selectedData) + '</p>';
        previewHtml += '<div class="retexify-export-highlight">';
        previewHtml += '<p><strong>✅ Nur ausgewählte Daten werden exportiert!</strong></p>';
        previewHtml += '<p><em>Übersichtliches Design mit besserer Benutzerführung</em></p>';
        previewHtml += '</div>';
        previewHtml += '</div>';
        
        $('#retexify-preview-content').html(previewHtml);
        $('#retexify-export-preview').slideDown(300);
        
        showNotification('👁️ Export-Vorschau erstellt - optimierte Darstellung', 'success');
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
    
    // Export-Zeilen schätzen
    function estimateExportRows(selection) {
        var totalRows = 0;
        
        selection.post_types.forEach(function(type) {
            // Verwende die neuen Statistik-Karten
            var $statCard = $('.retexify-stat-card.posts-pages');
            if ($statCard.length > 0) {
                totalRows += parseInt($statCard.find('.retexify-stat-current').text()) || 0;
            }
        });
        
        // Wenn Alt-Texte ausgewählt, Mediendatenbank hinzufügen
        if (selection.content.some(function(content) { return content.includes('Alt-Texte'); })) {
            var $mediaCard = $('.retexify-stat-card.media-data');
            if ($mediaCard.length > 0) {
                totalRows += parseInt($mediaCard.find('.retexify-stat-current').text()) || 0;
            }
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
        console.log('📤 CSV-Export gestartet (verbessertes Design)');
        
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
                showNotification('✅ CSV-Export erfolgreich! Optimierte Darstellung.', 'success');
                
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
        previewHtml += '<h4>📋 Export-Vorschau (Optimiertes Design)</h4>';
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
        
        // Erwartete Spalten-Info
        previewHtml += '<div class="retexify-export-item retexify-highlight">';
        previewHtml += '<strong>✅ Optimierte Darstellung:</strong> Nur ausgewählte Daten werden exportiert!';
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
    
    // Initial-Setup beim Tab-Wechsel
    $(document).on('click', 'a[href="#retexify-export"]', function() {
        setTimeout(function() {
            console.log('📤 Export-Tab geladen - verbessertes Design aktiv');
            updateExportPreview();
        }, 100);
    });
    
    console.log('✅ ReTexify Export/Import JavaScript vollständig geladen (verbessertes Design)!');
    console.log('🎨 VERBESSERUNGEN: Schöne Statistiken im X/Y Format, moderne Karten, bessere UX');
    
    // Initial Export-Statistiken laden falls Tab bereits aktiv
    if ($('.retexify-tab-btn[data-tab="export-import"]').hasClass('active')) {
        setTimeout(function() {
            initializeStatsGrid();
            loadExportStats();
        }, 500);
    }
});