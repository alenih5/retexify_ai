/**
 * ReTexify AI - Intelligent Progress Enhancement (REPARIERT)
 * Erweiterte Fortschrittsanzeige für intelligente Keyword-Research
 * 
 * @version 1.1.0 - BEHEBT POST-ID PROBLEM
 * @requires jQuery
 * 
 * FIXES:
 * ✅ Entfernt "Bitte laden Sie zuerst SEO-Content" Validierung  
 * ✅ Fügt automatische Post-ID-Erkennung hinzu
 * ✅ Behält alle schönen Progress-Features bei
 * ✅ Kompatibel mit admin-script.js
 */

(function($) {
    'use strict';
    
    // Namespace für intelligente Features
    window.ReTexifyIntelligent = window.ReTexifyIntelligent || {};
    
    /**
     * 🔍 POST-ID-ERKENNUNG (aus admin-script.js übernommen)
     */
    ReTexifyIntelligent.getCurrentPostId = function() {
        // Priorisierung: 1. Geladene Post-ID, 2. Auto-erkannte Post-ID, 3. URL-Parameter
        
        // Methode 1: Aus geladenen SEO-Daten
        if (window.seoData && window.seoData.length > 0) {
            const current = window.seoData[window.currentSeoIndex || 0];
            if (current && current.id) {
                return current.id;
            }
        }
        
        // Methode 2: Aus retexifyGlobals
        if (window.retexifyGlobals && window.retexifyGlobals.currentPostId) {
            return window.retexifyGlobals.currentPostId;
        }
        
        // Methode 3: URL-Parameter (WordPress Edit-Seiten)
        const urlParams = new URLSearchParams(window.location.search);
        const postFromUrl = urlParams.get('post') || urlParams.get('post_id');
        if (postFromUrl && parseInt(postFromUrl) > 0) {
            return parseInt(postFromUrl);
        }
        
        // Methode 4: DOM-Elemente
        const postIdFromDom = $('#post_ID').val() || $('[data-post-id]').first().data('post-id');
        if (postIdFromDom && parseInt(postIdFromDom) > 0) {
            return parseInt(postIdFromDom);
        }
        
        // Methode 5: WordPress Adminbar
        const $editLink = $('#wp-admin-bar-edit a');
        if ($editLink.length > 0) {
            const editHref = $editLink.attr('href') || '';
            const postIdMatch = editHref.match(/post=(\d+)/);
            if (postIdMatch && postIdMatch[1]) {
                return parseInt(postIdMatch[1]);
            }
        }
        
        // Methode 6: Body-Klassen
        const bodyClasses = $('body').attr('class') || '';
        const postIdMatch = bodyClasses.match(/post-(\d+)/);
        if (postIdMatch && postIdMatch[1]) {
            return parseInt(postIdMatch[1]);
        }
        
        return null;
    };
    
    /**
     * Intelligente Fortschrittsanzeige
     */
    ReTexifyIntelligent.ProgressManager = {
        
        /**
         * Research-Schritte Definition
         */
        researchSteps: [
            { id: 'analyze', text: '🔍 Analysiere Content-Themen...', duration: 1000 },
            { id: 'keywords', text: '📊 Recherchiere relevante Keywords...', duration: 3000 },
            { id: 'apis', text: '🌐 Sammle semantische Begriffe...', duration: 2000 },
            { id: 'intent', text: '🎯 Erkenne Suchintention...', duration: 1000 },
            { id: 'prompt', text: '🧠 Erstelle optimierten Prompt...', duration: 1000 },
            { id: 'generate', text: '🤖 Generiere mit KI...', duration: 0 } // Variable Dauer
        ],
        
        /**
         * Fallback-Schritte (wenn APIs nicht verfügbar)
         */
        fallbackSteps: [
            { id: 'fallback', text: '🤖 Generiere optimierte Meta-Texte...', duration: 0 }
        ],
        
        /**
         * Aktuelle Progress-Container
         */
        currentContainer: null,
        currentStepIndex: 0,
        isIntelligentMode: true,
        startTime: null,
        
        /**
         * Intelligente Progress starten
         */
        startIntelligentProgress: function($button, isIntelligentMode = true) {
            this.isIntelligentMode = isIntelligentMode;
            this.currentStepIndex = 0;
            this.startTime = Date.now();
            
            // Schritte basierend auf Modus auswählen
            const steps = isIntelligentMode ? this.researchSteps : this.fallbackSteps;
            
            // Progress-Container erstellen
            this.createProgressContainer($button, steps);
            
            // Schritte abarbeiten
            this.executeSteps(steps);
            
            // Timer für Button starten
            this.startButtonTimer($button);
        },
        
        /**
         * Progress-Container erstellen
         */
        createProgressContainer: function($button, steps) {
            // Bestehende Progress entfernen
            $('.retexify-intelligent-progress').remove();
            
            // Neuen Container erstellen
            const $container = $('<div class="retexify-intelligent-progress"></div>');
            
            // Header hinzufügen
            const modeText = this.isIntelligentMode ? 'Intelligente SEO-Optimierung' : 'Standard SEO-Optimierung';
            $container.append(`<div class="progress-header"><strong>${modeText}</strong></div>`);
            
            // Timer-Anzeige hinzufügen
            $container.append('<div class="progress-timer">⏱️ <span id="progress-elapsed">00:00</span></div>');
            
            // Schritte-Liste erstellen
            const $stepsList = $('<ul class="retexify-research-steps"></ul>');
            
            steps.forEach((step, index) => {
                const $stepItem = $(`<li data-step="${step.id}" class="${index === 0 ? 'active' : ''}">${step.text}</li>`);
                $stepsList.append($stepItem);
            });
            
            $container.append($stepsList);
            
            // Nach dem Button einfügen
            $button.after($container);
            this.currentContainer = $container;
        },
        
        /**
         * Button-Timer starten
         */
        startButtonTimer: function($button) {
            const originalText = $button.html();
            
            const updateTimer = () => {
                if (this.startTime) {
                    const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
                    const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
                    const seconds = (elapsed % 60).toString().padStart(2, '0');
                    
                    // Button-Text mit Timer aktualisieren
                    $button.html(`🔄 Generiert... ${minutes}:${seconds}`);
                    
                    // Timer im Progress-Container aktualisieren
                    $('#progress-elapsed').text(`${minutes}:${seconds}`);
                }
            };
            
            // Timer alle Sekunde aktualisieren
            this.timerInterval = setInterval(updateTimer, 1000);
        },
        
        /**
         * Timer stoppen
         */
        stopTimer: function() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
            this.startTime = null;
        },
        
        /**
         * Schritte ausführen
         */
        executeSteps: function(steps) {
            if (this.currentStepIndex >= steps.length) {
                return;
            }
            
            const currentStep = steps[this.currentStepIndex];
            
            // Aktuellen Schritt als aktiv markieren
            this.updateStepStatus(currentStep.id, 'active');
            
            // Nach der definierten Zeit zum nächsten Schritt
            if (currentStep.duration > 0) {
                setTimeout(() => {
                    this.completeStep(currentStep.id);
                    this.currentStepIndex++;
                    this.executeSteps(steps);
                }, currentStep.duration);
            }
            // Für den letzten Schritt (KI-Generierung) warten wir auf externes Signal
        },
        
        /**
         * Schritt-Status aktualisieren
         */
        updateStepStatus: function(stepId, status) {
            if (!this.currentContainer) return;
            
            const $step = this.currentContainer.find(`[data-step="${stepId}"]`);
            
            // Alle Status-Klassen entfernen
            $step.removeClass('active completed error');
            
            // Neue Status-Klasse hinzufügen
            if (status) {
                $step.addClass(status);
            }
        },
        
        /**
         * Schritt als abgeschlossen markieren
         */
        completeStep: function(stepId) {
            this.updateStepStatus(stepId, 'completed');
        },
        
        /**
         * Progress beenden (erfolgreich)
         */
        completeProgress: function() {
            if (!this.currentContainer) return;
            
            // Timer stoppen
            this.stopTimer();
            
            // Letzten Schritt als abgeschlossen markieren
            if (this.currentStepIndex < (this.isIntelligentMode ? this.researchSteps.length : this.fallbackSteps.length)) {
                const lastStep = this.isIntelligentMode ? this.researchSteps[this.researchSteps.length - 1] : this.fallbackSteps[0];
                this.completeStep(lastStep.id);
            }
            
            // Success-Message hinzufügen
            const $successMsg = $('<div class="progress-success">SEO-Texte erfolgreich generiert!</div>');
            this.currentContainer.append($successMsg);
            
            // Container nach 3 Sekunden ausblenden
            setTimeout(() => {
                this.hideProgress();
            }, 3000);
        },
        
        /**
         * Progress beenden (Fehler)
         */
        errorProgress: function(errorMessage = 'Ein Fehler ist aufgetreten') {
            if (!this.currentContainer) return;
            
            // Timer stoppen
            this.stopTimer();
            
            // Error-Message hinzufügen
            const $errorMsg = $(`<div class="progress-error">❌ ${errorMessage}</div>`);
            this.currentContainer.append($errorMsg);
            
            // Container nach 5 Sekunden ausblenden
            setTimeout(() => {
                this.hideProgress();
            }, 5000);
        },
        
        /**
         * Progress ausblenden
         */
        hideProgress: function() {
            this.stopTimer();
            if (this.currentContainer) {
                this.currentContainer.fadeOut(500, function() {
                    $(this).remove();
                });
                this.currentContainer = null;
            }
            this.currentStepIndex = 0;
        },
        
        /**
         * API-Status erkennen und entsprechenden Modus wählen
         */
        detectIntelligentMode: function() {
            // Einfache Heuristik: Wenn Intelligent Research Klassen verfügbar sind
            return typeof window.ReTexifyIntelligentAvailable !== 'undefined' && window.ReTexifyIntelligentAvailable;
        }
    };
    
    /**
     * Enhanced Generate Button Handler - REPARIERT!
     */
    ReTexifyIntelligent.enhanceGenerateButton = function() {
        
        // Original Button-Handler überschreiben
        $(document).off('click', '#retexify-generate-all-seo').on('click', '#retexify-generate-all-seo', function(e) {
            e.preventDefault();
            
            console.log('🧠 Enhanced Generate Button clicked!');
            
            // ✅ NEUE VERBESSERTE VALIDIERUNG - KEINE SEO-DATA-ABHÄNGIGKEIT!
            const postId = ReTexifyIntelligent.getCurrentPostId();
            
            if (!postId) {
                ReTexifyIntelligent.showNotification('❌ Keine Post-ID gefunden. Gehen Sie zu einer Post/Page-Bearbeitungsseite oder laden Sie SEO-Content.', 'error');
                console.log('⚠️ Verfügbare Post-ID-Quellen:', {
                    seoData: window.seoData ? window.seoData.length : 0,
                    retexifyGlobals: window.retexifyGlobals ? window.retexifyGlobals.currentPostId : 'undefined',
                    urlParam: new URLSearchParams(window.location.search).get('post'),
                    domElement: $('#post_ID').val()
                });
                return;
            }
            
            console.log('✅ Post-ID gefunden:', postId, '- starte Enhanced Generierung');
            
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Button deaktivieren
            $btn.html('⏳ Startet...').prop('disabled', true);
            
            // Intelligent Mode erkennen
            const isIntelligentMode = ReTexifyIntelligent.ProgressManager.detectIntelligentMode();
            
            // Enhanced Progress starten
            ReTexifyIntelligent.ProgressManager.startIntelligentProgress($btn, isIntelligentMode);
            
            // AJAX-Parameter sammeln
            const includeCantons = $('#retexify-include-cantons').is(':checked');
            const premiumTone = $('#retexify-premium-tone').is(':checked');
            
            console.log(`🧠 Starting ${isIntelligentMode ? 'intelligent' : 'standard'} generation for Post ID:`, postId);
            
            // AJAX-Request
            $.ajax({
                url: retexify_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'retexify_generate_complete_seo',
                    nonce: retexify_ajax.nonce,
                    post_id: postId,  // ✅ Verwende die erkannte Post-ID
                    include_cantons: includeCantons,
                    premium_tone: premiumTone
                },
                timeout: 120000,
                success: function(response) {
                    // Button wiederherstellen
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.log('✅ Enhanced AJAX Response:', response);
                    
                    if (response.success) {
                        const data = response.data;
                        
                        // Datenstruktur handhaben
                        const metaTitle = data.meta_title || data.suite?.meta_title || '';
                        const metaDescription = data.meta_description || data.suite?.meta_description || '';
                        const focusKeyword = data.focus_keyword || data.suite?.focus_keyword || '';
                        
                        // Felder füllen
                        if (metaTitle) $('#retexify-new-meta-title').val(metaTitle);
                        if (metaDescription) $('#retexify-new-meta-description').val(metaDescription);
                        if (focusKeyword) $('#retexify-new-focus-keyword').val(focusKeyword);
                        
                        // Charakterzähler aktualisieren
                        if (typeof updateCharCounters === 'function') {
                            updateCharCounters();
                        }
                        
                        // Progress als erfolgreich beenden
                        ReTexifyIntelligent.ProgressManager.completeProgress();
                        
                        // Success-Notification
                        const modeText = data.research_mode === 'intelligent' ? ' (Intelligent Mode)' : '';
                        ReTexifyIntelligent.showNotification('SEO-Texte erfolgreich generiert!' + modeText, 'success');
                        
                    } else {
                        // Progress mit Fehler beenden
                        const errorMsg = response.data?.message || 'Unbekannter Fehler';
                        ReTexifyIntelligent.ProgressManager.errorProgress(errorMsg);
                        ReTexifyIntelligent.showNotification('❌ Fehler: ' + errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    // Button wiederherstellen
                    $btn.html(originalText).prop('disabled', false);
                    
                    console.error('❌ Enhanced AJAX Error:', error);
                    
                    // Progress mit Fehler beenden
                    ReTexifyIntelligent.ProgressManager.errorProgress('Verbindungsfehler');
                    ReTexifyIntelligent.showNotification('❌ Verbindungsfehler beim Generieren', 'error');
                }
            });
        });
    };
    
    /**
     * Enhanced Notification System
     */
    ReTexifyIntelligent.showNotification = function(message, type = 'info') {
        // Bestehende showNotification verwenden falls verfügbar
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }
        
        // Fallback: Einfache Notification
        const $notification = $(`
            <div class="retexify-notification retexify-${type}" style="
                position: fixed; 
                top: 32px; 
                right: 20px; 
                padding: 15px 20px; 
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#17a2b8'}; 
                color: white; 
                border-radius: 5px; 
                z-index: 100000;
                max-width: 400px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            ">
                ${message}
            </div>
        `);
        
        $('body').append($notification);
        
        // Nach 5 Sekunden entfernen
        setTimeout(() => {
            $notification.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    /**
     * Plugin-Initialisierung
     */
    ReTexifyIntelligent.init = function() {
        console.log('🧠 ReTexify Intelligent Features initializing...');
        
        // Warten bis DOM bereit ist
        $(document).ready(function() {
            
            // Enhanced Generate Button Handler
            ReTexifyIntelligent.enhanceGenerateButton();
            
            // API-Verfügbarkeit prüfen und Flag setzen
            if (typeof retexify_ajax !== 'undefined') {
                // Flag für Intelligent Mode Verfügbarkeit setzen
                window.ReTexifyIntelligentAvailable = true;
                console.log('✅ ReTexify Intelligent Mode available');
            }
            
            console.log('✅ ReTexify Intelligent Features loaded!');
        });
    };
    
    // Auto-Initialisierung
    ReTexifyIntelligent.init();
    
})(jQuery);

/**
 * CSS für Enhanced Progress (erweitert mit Timer)
 */
const intelligentProgressCSS = `
.retexify-intelligent-progress {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin: 15px 0;
    animation: slideIn 0.5s ease-out;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.progress-header {
    font-size: 16px;
    margin-bottom: 15px;
    text-align: center;
    font-weight: bold;
}

.progress-timer {
    text-align: center;
    font-size: 14px;
    margin-bottom: 15px;
    font-family: monospace;
    background: rgba(255,255,255,0.1);
    padding: 8px;
    border-radius: 6px;
}

.retexify-research-steps {
    list-style: none;
    padding: 0;
    margin: 0;
}

.retexify-research-steps li {
    padding: 8px 0;
    position: relative;
    padding-left: 35px;
    transition: all 0.3s ease;
    opacity: 0.6;
}

.retexify-research-steps li:before {
    content: '⏳';
    position: absolute;
    left: 0;
    top: 8px;
    font-size: 18px;
}

.retexify-research-steps li.active {
    opacity: 1;
    font-weight: bold;
    transform: translateX(5px);
}

.retexify-research-steps li.active:before {
    content: '🔄';
    animation: spin 1s linear infinite;
}

.retexify-research-steps li.completed {
    opacity: 0.8;
}

.retexify-research-steps li.completed:before {
    content: '';
    animation: none;
}

.progress-success, .progress-error {
    margin-top: 15px;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: bold;
}

.progress-success {
    background: rgba(40, 167, 69, 0.3);
    border: 1px solid rgba(40, 167, 69, 0.5);
}

.progress-error {
    background: rgba(220, 53, 69, 0.3);
    border: 1px solid rgba(220, 53, 69, 0.5);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Button-Erweiterungen für Timer */
#retexify-generate-all-seo {
    position: relative;
    min-width: 200px;
}

#retexify-generate-all-seo:disabled {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.8; }
    100% { opacity: 1; }
}
`;

// CSS dynamisch hinzufügen
if (typeof document !== 'undefined') {
    const style = document.createElement('style');
    style.textContent = intelligentProgressCSS;
    document.head.appendChild(style);
}