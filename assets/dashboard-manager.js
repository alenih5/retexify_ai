/**
 * ReTexify AI - Dashboard Manager JavaScript
 * Verwaltet Dashboard-Funktionen und Statistiken
 * Version: 4.2.1
 */

jQuery(document).ready(function($) {
    console.log('📊 ReTexify Dashboard Manager JavaScript startet...');
    
    // ========================================================================
    // 📊 DASHBOARD FUNKTIONEN
    // ========================================================================
    
    function loadDashboard() {
        console.log('📊 Lade Dashboard...');
        
        var $container = $('#retexify-dashboard-content');
        if ($container.length === 0) {
            console.warn('⚠️ Dashboard-Container nicht gefunden');
            return;
        }
        
        $container.html('<div class="retexify-loading">📊 Lade Dashboard-Statistiken...</div>');
        
        if (typeof executeAjaxCall === 'function') {
            executeAjaxCall({
                action: 'retexify_get_stats',
                timeout: 15000,
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data);
                        if (typeof showNotification === 'function') {
                            showNotification('✅ Dashboard geladen', 'success', 2000);
                        }
                    } else {
                        throw new Error(response.data || 'Dashboard-Fehler');
                    }
                },
                error: function(error) {
                    $container.html('<div class="retexify-error">❌ Dashboard-Fehler: ' + error + '</div>');
                    if (typeof showNotification === 'function') {
                        showNotification('❌ Dashboard-Fehler', 'error', 3000);
                    }
                }
            });
        } else {
            console.error('❌ executeAjaxCall Funktion nicht verfügbar');
            $container.html('<div class="retexify-error">❌ AJAX-Funktionen nicht verfügbar</div>');
        }
    }
    
    // Globale Funktion verfügbar machen
    window.loadDashboard = loadDashboard;
    
    console.log('✅ ReTexify Dashboard Manager JavaScript Setup abgeschlossen');
}); 