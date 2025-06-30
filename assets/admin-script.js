/**
 * ReTexify AI Pro - KORRIGIERTE Admin JavaScript
 * Version: 3.7.1 - Bug Fixes für System-Status und jQuery-Konflikte
 * FIXES: jQuery-Conflict, AJAX-Loading, Performance-Optimierung
 */

// ============================================================================
// 🔧 KRITISCHER FIX: jQuery-Conflict verhindern
// ============================================================================

(function($) {
    'use strict';
    
    // Warten bis DOM vollständig geladen ist
    $(document).ready(function() {
        console.log('🚀 ReTexify AI Pro JavaScript startet...');
        console.log('📊 AJAX URL:', retexify_ajax.ajax_url);
        console.log('🔑 Nonce:', retexify_ajax.nonce);
        
        // Globale Variablen für Status-Tracking
        var systemStatusLoaded = false;
        var researchStatusLoaded = false;
        var seoData = [];
        var currentSeoIndex = 0;
        
        // ========================================================================
        // 🎯 TAB-SYSTEM INITIALISIERUNG
        // ========================================================================
        
        initializeTabs();
        loadDashboard();
        
        function initializeTabs() {
            console.log('🔄 Initialisiere Tab-System...');
            
            // Event-Delegation für Tab-Clicks
            $(document).on('click', '.retexify-tab-btn', function(e) {
                e.preventDefault();
                
                var tabId = $(this).data('tab');
                if (!tabId) return;
                
                console.log('🔄 Tab-Wechsel zu:', tabId);
                
                // Alle Tabs deaktivieren
                $('.retexify-tab-btn').removeClass('active');
                $('.retexify-tab-content').removeClass('active');
                
                // Aktuellen Tab aktivieren
                $(this).addClass('active');
                $('#tab-' + tabId).addClass('active');
                
                // Tab-spezifische Aktionen
                handleTabSwitch(tabId);
            });
        }
        
        function handleTabSwitch(tabId) {
            switch(tabId) {
                case 'system':
                    if (!systemStatusLoaded) {
                        setTimeout(loadSystemStatus, 100);
                    }
                    if (!researchStatusLoaded) {
                        setTimeout(loadResearchStatus, 1500); // Warte 1.5s nach System-Status
                    }
                    break;
                    
                case 'dashboard':
                    loadDashboard();
                    break;
                    
                case 'export-import':
                    if (typeof loadExportImportTab === 'function') {
                        loadExportImportTab();
                    }
                    break;
            }
        }
        
        // ========================================================================
        // 📊 DASHBOARD FUNKTIONEN
        // ========================================================================
        
        function loadDashboard() {
            console.log('📊 Lade Dashboard...');
            
            var $container = $('#retexify-dashboard-content');
            if ($container.length === 0) return;
            
            $container.html('<div class="retexify-loading">📊 Lade Dashboard...</div>');
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_get_stats',
                    nonce: retexify_ajax.nonce
                },
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data);
                        showNotification('✅ Dashboard geladen', 'success', 2000);
                    } else {
                        $container.html('<div class="retexify-error">❌ Dashboard-Fehler: ' + (response.data || 'Unbekannt') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Dashboard AJAX Fehler:', error);
                    $container.html('<div class="retexify-error">❌ Verbindungsfehler beim Dashboard</div>');
                }
            });
        }
        
        // Dashboard Refresh Button
        $(document).on('click', '#retexify-refresh-stats-badge', function(e) {
            e.preventDefault();
            var $badge = $(this);
            var originalText = $badge.html();
            $badge.html('🔄 Lädt...');
            
            loadDashboard();
            
            setTimeout(function() {
                $badge.html(originalText);
            }, 2000);
        });
        
        // ========================================================================
        // 🔧 SYSTEM-STATUS FUNKTIONEN - OPTIMIERT
        // ========================================================================
        
        function loadSystemStatus() {
            if (systemStatusLoaded) {
                console.log('📊 System-Status bereits geladen');
                return;
            }
            
            console.log('🔍 Lade System-Status...');
            systemStatusLoaded = true;
            
            var $container = $('#retexify-system-status');
            if ($container.length === 0) {
                console.error('❌ System-Status Container nicht gefunden');
                return;
            }
            
            // Loading-Anzeige
            $container.html(`
                <div class="retexify-loading-status">
                    <div class="loading-spinner">🔄</div>
                    <div class="loading-text">System wird getestet...</div>
                </div>
            `);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_test_system',
                    nonce: retexify_ajax.nonce
                },
                timeout: 15000, // 15 Sekunden
                success: function(response) {
                    console.log('📊 System-Status Response:', response);
                    
                    if (response.success) {
                        $container.html(response.data);
                        showNotification('✅ System-Status geladen', 'success', 2000);
                    } else {
                        $container.html(createErrorHTML('System-Test fehlgeschlagen', response.data));
                        showNotification('❌ System-Test fehlgeschlagen', 'error', 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ System-Status AJAX Fehler:', status, error);
                    systemStatusLoaded = false; // Reset für Retry
                    $container.html(createErrorHTML('Verbindungsfehler', 'System-Status konnte nicht geladen werden: ' + error));
                    showNotification('❌ System-Verbindungsfehler', 'error', 5000);
                }
            });
        }
        
        // ========================================================================
        // 🧠 RESEARCH-STATUS FUNKTIONEN - OPTIMIERT
        // ========================================================================
        
        function loadResearchStatus() {
            if (researchStatusLoaded) {
                console.log('🧠 Research-Status bereits geladen');
                return;
            }
            
            console.log('🧠 Lade Research-Status...');
            researchStatusLoaded = true;
            
            var $container = $('#retexify-research-engine-status, #research-engine-status-content');
            if ($container.length === 0) {
                console.warn('⚠️ Research-Status Container nicht gefunden');
                return;
            }
            
            // Loading-Anzeige
            $container.html(`
                <div class="retexify-loading-status">
                    <div class="loading-spinner">🧠</div>
                    <div class="loading-text">Research-Engine wird getestet...</div>
                </div>
            `);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_test_research_apis',
                    nonce: retexify_ajax.nonce
                },
                timeout: 20000, // 20 Sekunden für externe APIs
                success: function(response) {
                    console.log('🧠 Research-Status Response:', response);
                    
                    if (response.success) {
                        $container.html(response.data);
                        showNotification('✅ Research-Engine getestet', 'success', 2000);
                    } else {
                        $container.html(createErrorHTML('Research-Test fehlgeschlagen', response.data));
                        showNotification('❌ Research-Test fehlgeschlagen', 'error', 3000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Research-Status AJAX Fehler:', status, error);
                    researchStatusLoaded = false; // Reset für Retry
                    $container.html(createErrorHTML('Verbindungsfehler', 'Research-Status konnte nicht geladen werden: ' + error));
                    showNotification('❌ Research-Verbindungsfehler', 'error', 5000);
                }
            });
        }
        
        // ========================================================================
        // 🔄 BUTTON-HANDLER FÜR MANUELLE TESTS
        // ========================================================================
        
        // System-Test Button
        $(document).on('click', '#retexify-test-system-badge, .retexify-test-system-btn', function(e) {
            e.preventDefault();
            console.log('🧪 Manueller System-Test ausgelöst');
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.html('🔄 Teste...').prop('disabled', true);
            systemStatusLoaded = false; // Reset
            
            loadSystemStatus();
            
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 5000);
        });
        
        // Research-Test Button
        $(document).on('click', '#test-research-apis, .retexify-test-research-btn', function(e) {
            e.preventDefault();
            console.log('🧪 Manueller Research-Test ausgelöst');
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.html('🔄 Teste APIs...').prop('disabled', true);
            researchStatusLoaded = false; // Reset
            
            loadResearchStatus();
            
            setTimeout(function() {
                $btn.html(originalText).prop('disabled', false);
            }, 8000);
        });
        
        // ========================================================================
        // 🎨 SEO-OPTIMIZER FUNKTIONEN
        // ========================================================================
        
        // SEO Content laden
        $(document).on('click', '#retexify-load-seo-content', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.html();
            var postId = $('#retexify-post-select').val();
            
            if (!postId) {
                showNotification('❌ Bitte wähle einen Post/Page aus', 'error', 3000);
                return;
            }
            
            $btn.html('🔄 Lade...').prop('disabled', true);
            
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_load_content',
                    nonce: retexify_ajax.nonce,
                    post_id: postId
                },
                timeout: 10000,
                success: function(response) {
                    $btn.html(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        $('#retexify-current-title').text(response.data.title || '');
                        $('#retexify-current-meta-title').text(response.data.meta_title || '');
                        $('#retexify-current-meta-description').text(response.data.meta_description || '');
                        $('#retexify-current-content').text(response.data.content || '');
                        $('#retexify-full-content').slideDown(300);
                        showNotification('📄 Content geladen', 'success', 2000);
                    } else {
                        showNotification('❌ Content-Fehler: ' + (response.data || 'Unbekannt'), 'error', 3000);
                    }
                },
                error: function(xhr, status, error) {
                    $btn.html(originalText).prop('disabled', false);
                    console.error('❌ Content-Load Fehler:', error);
                    showNotification('❌ Verbindungsfehler beim Content laden', 'error', 3000);
                }
            });
        });
        
        // Character Counter für Meta-Felder
        $(document).on('input', '#retexify-new-meta-title, #retexify-new-meta-description', function() {
            updateCharCounters();
        });
        
        function updateCharCounters() {
            var titleLength = $('#retexify-new-meta-title').val().length;
            var descLength = $('#retexify-new-meta-description').val().length;
            
            $('#title-chars').text(titleLength);
            $('#description-chars').text(descLength);
            
            // Farben setzen
            $('#title-chars').css('color', getTitleColor(titleLength));
            $('#description-chars').css('color', getDescColor(descLength));
        }
        
        function getTitleColor(length) {
            if (length > 60) return '#dc3545'; // Rot
            if (length > 54) return '#ffc107'; // Gelb
            if (length > 0) return '#28a745';  // Grün
            return '#6c757d'; // Grau
        }
        
        function getDescColor(length) {
            if (length > 160) return '#dc3545'; // Rot
            if (length > 150) return '#ffc107'; // Gelb
            if (length > 0) return '#28a745';   // Grün
            return '#6c757d'; // Grau
        }
        
        // ========================================================================
        // 🛠️ UTILITY FUNKTIONEN
        // ========================================================================
        
        function createErrorHTML(title, message) {
            return `
                <div class="retexify-status-error">
                    <div class="error-icon">❌</div>
                    <div class="error-content">
                        <strong>${title}</strong><br>
                        ${message || 'Unbekannter Fehler'}
                    </div>
                </div>
            `;
        }
        
        function showNotification(message, type, duration) {
            type = type || 'info';
            duration = duration || 3000;
            
            var className = 'retexify-notification retexify-notification-' + type;
            var $notification = $('<div class="' + className + '">' + message + '</div>');
            
            // Notification Container erstellen falls nicht vorhanden
            var $container = $('#retexify-notifications');
            if ($container.length === 0) {
                $container = $('<div id="retexify-notifications"></div>');
                $('body').append($container);
            }
            
            $container.append($notification);
            
            // Animation
            $notification.fadeIn(200);
            
            // Auto-Remove
            setTimeout(function() {
                $notification.fadeOut(200, function() {
                    $(this).remove();
                });
            }, duration);
            
            console.log('📢 Notification:', message);
        }
        
        // Performance Monitoring
        window.ReTexifyPerformance = {
            start: function() {
                this.startTime = performance.now();
            },
            
            end: function(operation) {
                var endTime = performance.now();
                var duration = (endTime - this.startTime) / 1000;
                console.log('⏱️ Performance:', operation, 'in', duration.toFixed(2), 'Sekunden');
                return duration;
            }
        };
        
        // ========================================================================
        // 🚀 INITIALISIERUNG ABGESCHLOSSEN
        // ========================================================================
        
        console.log('✅ ReTexify AI Pro JavaScript vollständig geladen');
        showNotification('🚀 ReTexify AI bereit', 'success', 2000);
        
    }); // Ende document.ready
    
})(jQuery); // Ende jQuery Wrapper 