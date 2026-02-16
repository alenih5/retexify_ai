<?php
/**
 * ReTexify AI - Standalone Diagnose-Script
 * 
 * ANLEITUNG:
 * 1. Diese Datei in den Plugin-Ordner kopieren: wp-content/plugins/retexify_ai/diagnose.php
 * 2. Im Browser öffnen: https://imponi.ch/wp-content/plugins/retexify_ai/diagnose.php
 * 3. Ergebnis als Screenshot an Claude schicken
 * 4. NACH DER DIAGNOSE: Diese Datei LÖSCHEN! (Sicherheit)
 */

// WordPress laden
$wp_load_paths = array(
    dirname(__FILE__) . '/../../../wp-load.php',
    dirname(__FILE__) . '/../../../../wp-load.php',
);

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('❌ WordPress konnte nicht geladen werden. Pfad prüfen.');
}

// Nur für Admins
if (!current_user_can('manage_options')) {
    die('❌ Nur für Administratoren zugänglich. Bitte zuerst in WordPress einloggen.');
}

// Header
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>ReTexify AI - Diagnose</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; border-radius: 12px 12px 0 0; font-size: 22px; }
        .card { background: white; border-radius: 0 0 12px 12px; padding: 25px 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .section { margin-bottom: 25px; }
        .section h2 { font-size: 16px; color: #374151; margin-bottom: 10px; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
        .ok { color: #10b981; font-weight: 700; }
        .fail { color: #ef4444; font-weight: 700; }
        .warn { color: #f59e0b; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td, th { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        th { background: #f9fafb; font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; }
        tr:hover { background: #f9fafb; }
        .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 15px; margin-top: 10px; font-size: 13px; color: #991b1b; white-space: pre-wrap; word-break: break-all; max-height: 300px; overflow-y: auto; }
        .fix-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 15px; margin-top: 10px; font-size: 13px; color: #1e40af; }
        .summary { background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border: 2px solid #86efac; border-radius: 12px; padding: 20px; margin-top: 20px; }
        .summary.bad { background: linear-gradient(135deg, #fef2f2, #fff1f2); border-color: #fca5a5; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-fail { background: #fee2e2; color: #991b1b; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        .delete-warning { background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; padding: 15px; margin-top: 20px; text-align: center; font-weight: 600; color: #92400e; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 ReTexify AI - Vollständige Diagnose</h1>
    <div class="card">
<?php

$issues = 0;
$critical = 0;

// ============================================================
// 1. SYSTEM-INFORMATIONEN
// ============================================================
echo '<div class="section">';
echo '<h2>📋 System-Informationen</h2>';
echo '<table>';
echo '<tr><td><strong>WordPress-Version</strong></td><td>' . get_bloginfo('version') . '</td></tr>';
echo '<tr><td><strong>PHP-Version</strong></td><td>' . PHP_VERSION . '</td></tr>';
echo '<tr><td><strong>Plugin-Version</strong></td><td>' . (defined('RETEXIFY_VERSION') ? RETEXIFY_VERSION : '<span class="fail">NICHT DEFINIERT!</span>') . '</td></tr>';
echo '<tr><td><strong>Plugin-Pfad</strong></td><td><code>' . (defined('RETEXIFY_PLUGIN_PATH') ? RETEXIFY_PLUGIN_PATH : 'NICHT DEFINIERT') . '</code></td></tr>';
echo '<tr><td><strong>Plugin-URL</strong></td><td><code>' . (defined('RETEXIFY_PLUGIN_URL') ? RETEXIFY_PLUGIN_URL : 'NICHT DEFINIERT') . '</code></td></tr>';
echo '<tr><td><strong>WP_DEBUG</strong></td><td>' . (defined('WP_DEBUG') && WP_DEBUG ? '<span class="ok">Aktiv</span>' : '<span class="warn">Inaktiv</span>') . '</td></tr>';
echo '</table>';
echo '</div>';

// ============================================================
// 2. DATEI-PRÜFUNG
// ============================================================
echo '<div class="section">';
echo '<h2>📁 Datei-Prüfung</h2>';

$plugin_path = defined('RETEXIFY_PLUGIN_PATH') ? RETEXIFY_PLUGIN_PATH : dirname(__FILE__) . '/';

$required_files = array(
    'retexify.php' => 'Haupt-Plugin-Datei',
    'includes/class-ai-engine.php' => 'KI-Engine (KRITISCH)',
    'includes/class-admin-renderer.php' => 'Admin-Renderer (KRITISCH)',
    'includes/class-media-seo-manager.php' => 'Medien-SEO Manager (KRITISCH)',
    'includes/class-system-status.php' => 'System-Status',
    'includes/class-performance-optimizer.php' => 'Performance Optimizer',
    'includes/class-rate-limiter.php' => 'Rate Limiter',
    'includes/class-error-handler.php' => 'Error Handler',
    'includes/class-admin-renderer-minimal.php' => 'Admin-Renderer Minimal',
    'includes/class-api-manager.php' => 'API Manager',
    'includes/class-intelligent-keyword-research.php' => 'Keyword Research',
    'includes/class_retexify_config.php' => 'Config (Unterstrich!)',
    'includes/class-retexify-config.php' => 'Config (Bindestrich)',
    'includes/class-advanced-content-analyzer.php' => 'Advanced Content Analyzer',
    'includes/class-serp-competitor-analyzer.php' => 'SERP Competitor Analyzer',
    'includes/class-advanced-prompt-builder.php' => 'Advanced Prompt Builder',
    'includes/class-german-text-processor.php' => 'German Text Processor',
    'includes/class-export-import-manager.php' => 'Export/Import Manager',
    'assets/admin-script.js' => 'Haupt-JavaScript',
    'assets/admin-style.css' => 'Haupt-CSS',
    'assets/admin_styles_extended.css' => 'Erweiterte CSS',
    'assets/export_import.js' => 'Export/Import JS',
);

echo '<table>';
echo '<tr><th>Datei</th><th>Beschreibung</th><th>Status</th><th>Grösse</th></tr>';

$missing_files = array();
foreach ($required_files as $file => $desc) {
    $full_path = $plugin_path . $file;
    $exists = file_exists($full_path);
    $size = $exists ? size_format(filesize($full_path)) : '-';
    $is_critical = strpos($desc, 'KRITISCH') !== false;
    
    echo '<tr>';
    echo '<td><code>' . $file . '</code></td>';
    echo '<td>' . $desc . '</td>';
    
    if ($exists) {
        if (filesize($full_path) < 50) {
            echo '<td><span class="warn">⚠️ Fast leer (' . filesize($full_path) . ' Bytes)</span></td>';
            $issues++;
        } else {
            echo '<td><span class="ok">✅ Vorhanden</span></td>';
        }
    } else {
        echo '<td><span class="fail">❌ FEHLT!</span></td>';
        $missing_files[] = $file;
        $issues++;
        if ($is_critical) $critical++;
    }
    
    echo '<td>' . $size . '</td>';
    echo '</tr>';
}
echo '</table>';

if (!empty($missing_files)) {
    echo '<div class="fix-box">';
    echo '<strong>💡 Fehlende Dateien:</strong><br>';
    foreach ($missing_files as $mf) {
        echo '• <code>' . $mf . '</code><br>';
    }
    echo '<br><strong>→ Diese Dateien müssen erstellt werden!</strong>';
    echo '</div>';
}

echo '</div>';

// ============================================================
// 3. KLASSEN-PRÜFUNG
// ============================================================
echo '<div class="section">';
echo '<h2>🏗️ PHP-Klassen-Prüfung</h2>';

$required_classes = array(
    'ReTexify_AI_Pro_Universal' => 'Hauptklasse',
    'ReTexify_AI_Engine' => 'KI-Engine',
    'ReTexify_Admin_Renderer' => 'Admin-Renderer',
    'ReTexify_Admin_Renderer_Minimal' => 'Admin-Renderer Minimal',
    'ReTexify_Media_SEO_Manager' => 'Medien-SEO Manager',
    'ReTexify_Export_Import_Manager' => 'Export/Import Manager',
    'ReTexify_Rate_Limiter' => 'Rate Limiter',
    'ReTexify_Error_Handler' => 'Error Handler',
    'ReTexify_System_Status' => 'System Status',
    'ReTexify_Performance_Optimizer' => 'Performance Optimizer',
    'ReTexify_Intelligent_Keyword_Research' => 'Keyword Research',
    'ReTexify_Advanced_Content_Analyzer' => 'Advanced Content Analyzer',
    'ReTexify_Serp_Competitor_Analyzer' => 'SERP Competitor Analyzer',
    'ReTexify_Advanced_Prompt_Builder' => 'Advanced Prompt Builder',
    'ReTexify_German_Text_Processor' => 'German Text Processor',
);

echo '<table>';
echo '<tr><th>Klasse</th><th>Beschreibung</th><th>Status</th></tr>';

$missing_classes = array();
foreach ($required_classes as $class => $desc) {
    echo '<tr>';
    echo '<td><code>' . $class . '</code></td>';
    echo '<td>' . $desc . '</td>';
    
    if (class_exists($class)) {
        echo '<td><span class="ok">✅ Geladen</span></td>';
    } else {
        echo '<td><span class="fail">❌ NICHT GELADEN!</span></td>';
        $missing_classes[] = $class;
        $issues++;
    }
    echo '</tr>';
}
echo '</table>';

if (!empty($missing_classes)) {
    echo '<div class="fix-box">';
    echo '<strong>💡 Fehlende Klassen:</strong> Diese Klassen konnten nicht geladen werden.<br>';
    echo 'Mögliche Ursachen: Datei fehlt, Syntax-Fehler in der Datei, oder Klasse heisst anders.<br>';
    foreach ($missing_classes as $mc) {
        echo '• <code>' . $mc . '</code><br>';
    }
    echo '</div>';
}

echo '</div>';

// ============================================================
// 4. AJAX-HANDLER PRÜFUNG
// ============================================================
echo '<div class="section">';
echo '<h2>🔌 AJAX-Handler Prüfung (Medien-SEO)</h2>';

$ajax_actions = array(
    'retexify_load_media' => 'Medien laden',
    'retexify_get_media_stats' => 'Statistiken laden',
    'retexify_generate_image_alt' => 'Alt-Text generieren',
    'retexify_save_image_seo' => 'Bild-SEO speichern',
    'retexify_export_media_csv' => 'CSV Export',
    'retexify_generate_bulk_image_alt' => 'Bulk Alt-Text',
    'retexify_get_all_images_without_alt' => 'Alle ohne Alt-Text',
);

echo '<table>';
echo '<tr><th>AJAX-Action</th><th>Funktion</th><th>Status</th></tr>';

$unregistered_actions = array();
foreach ($ajax_actions as $action => $desc) {
    $hook = 'wp_ajax_' . $action;
    $registered = has_action($hook);
    
    echo '<tr>';
    echo '<td><code>' . $action . '</code></td>';
    echo '<td>' . $desc . '</td>';
    
    if ($registered) {
        echo '<td><span class="ok">✅ Registriert (Priorität: ' . $registered . ')</span></td>';
    } else {
        echo '<td><span class="fail">❌ NICHT REGISTRIERT!</span></td>';
        $unregistered_actions[] = $action;
        $issues++;
        $critical++;
    }
    echo '</tr>';
}
echo '</table>';

if (!empty($unregistered_actions)) {
    echo '<div class="fix-box">';
    echo '<strong>💡 Das ist die HAUPTURSACHE!</strong> Diese AJAX-Handler sind nicht registriert, deshalb funktionieren die Buttons nicht.<br>';
    echo 'Ursache: Die Methoden existieren nicht in der Hauptklasse, oder die Klasse konnte nicht initialisiert werden.<br>';
    echo '</div>';
}

echo '</div>';

// ============================================================
// 5. AUCH ANDERE AJAX-HANDLER PRÜFEN
// ============================================================
echo '<div class="section">';
echo '<h2>🔌 AJAX-Handler Prüfung (Andere Features)</h2>';

$other_actions = array(
    'retexify_get_stats' => 'Dashboard Stats',
    'retexify_load_content' => 'SEO Content laden',
    'retexify_generate_complete_seo' => 'Komplette SEO generieren',
    'retexify_save_seo_data' => 'SEO Daten speichern',
    'retexify_save_settings' => 'Einstellungen speichern',
    'retexify_test_api_connection' => 'API-Test',
    'retexify_test_system' => 'System-Test',
);

echo '<table>';
echo '<tr><th>AJAX-Action</th><th>Funktion</th><th>Status</th></tr>';

foreach ($other_actions as $action => $desc) {
    $hook = 'wp_ajax_' . $action;
    $registered = has_action($hook);
    
    echo '<tr>';
    echo '<td><code>' . $action . '</code></td>';
    echo '<td>' . $desc . '</td>';
    echo '<td>' . ($registered ? '<span class="ok">✅</span>' : '<span class="fail">❌</span>') . '</td>';
    echo '</tr>';
}
echo '</table>';
echo '</div>';

// ============================================================
// 6. DIREKTER STATS-TEST
// ============================================================
echo '<div class="section">';
echo '<h2>📊 Direkter Medien-Stats Test</h2>';

if (class_exists('ReTexify_Media_SEO_Manager')) {
    try {
        $stats = ReTexify_Media_SEO_Manager::get_media_stats();
        echo '<table>';
        echo '<tr><td><strong>Bilder gesamt</strong></td><td><span class="ok">' . (isset($stats['total_images']) ? $stats['total_images'] : '?') . '</span></td></tr>';
        echo '<tr><td><strong>Mit Alt-Text</strong></td><td><span class="ok">' . (isset($stats['with_alt']) ? $stats['with_alt'] : '?') . '</span></td></tr>';
        echo '<tr><td><strong>Ohne Alt-Text</strong></td><td><span class="warn">' . (isset($stats['without_alt']) ? $stats['without_alt'] : '?') . '</span></td></tr>';
        echo '<tr><td><strong>Nicht zugeordnet</strong></td><td>' . (isset($stats['unattached']) ? $stats['unattached'] : '?') . '</td></tr>';
        echo '<tr><td><strong>Optimierung</strong></td><td>' . (isset($stats['optimization_pct']) ? $stats['optimization_pct'] : '?') . '%</td></tr>';
        echo '</table>';
        echo '<div class="fix-box">✅ <strong>Medien-Stats funktionieren direkt!</strong> Das Problem liegt also beim AJAX-Transport, nicht bei der Datenbank-Abfrage.</div>';
    } catch (\Throwable $e) {
        echo '<div class="error-box">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . "\nDatei: " . $e->getFile() . "\nZeile: " . $e->getLine() . '</div>';
        $critical++;
    }
} else {
    echo '<div class="error-box">❌ Klasse ReTexify_Media_SEO_Manager existiert NICHT! Die Datei includes/class-media-seo-manager.php fehlt oder hat einen Syntax-Fehler.</div>';
    $critical++;
}

echo '</div>';

// ============================================================
// 7. DIREKTER MEDIEN-LADE TEST
// ============================================================
echo '<div class="section">';
echo '<h2>🖼️ Direkter Medien-Lade Test (erste 3 Bilder)</h2>';

if (class_exists('ReTexify_Media_SEO_Manager')) {
    try {
        $result = ReTexify_Media_SEO_Manager::get_media(array('per_page' => 3, 'filter_type' => 'all'));
        if (!empty($result['items'])) {
            echo '<p><span class="ok">✅</span> ' . $result['total'] . ' Bilder gefunden. Zeige erste 3:</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Dateiname</th><th>Alt-Text</th><th>Verwendet auf</th></tr>';
            foreach ($result['items'] as $item) {
                echo '<tr>';
                echo '<td>' . $item['id'] . '</td>';
                echo '<td>' . htmlspecialchars($item['filename']) . '</td>';
                echo '<td>' . ($item['alt_text'] ? '<span class="ok">' . htmlspecialchars(substr($item['alt_text'], 0, 50)) . '</span>' : '<span class="fail">FEHLT</span>') . '</td>';
                echo '<td>' . htmlspecialchars($item['parent_title'] ? $item['parent_title'] : 'Nicht zugeordnet') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<span class="warn">⚠️ Keine Bilder gefunden (Datenbank leer?)</span>';
        }
    } catch (\Throwable $e) {
        echo '<div class="error-box">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . "\nZeile: " . $e->getLine() . '</div>';
        $critical++;
    }
} else {
    echo '<span class="fail">❌ Klasse nicht verfügbar</span>';
}

echo '</div>';

// ============================================================
// 8. JAVASCRIPT-DATEIEN PRÜFUNG
// ============================================================
echo '<div class="section">';
echo '<h2>📜 JavaScript Syntax-Check</h2>';

$js_file = $plugin_path . 'assets/admin-script.js';
if (file_exists($js_file)) {
    $js_content = file_get_contents($js_file);
    $js_size = strlen($js_content);
    
    echo '<table>';
    echo '<tr><td><strong>Dateigrösse</strong></td><td>' . size_format(filesize($js_file)) . ' (' . number_format($js_size) . ' Zeichen)</td></tr>';
    
    $has_media_obj = strpos($js_content, 'window.ReTexifyMedia') !== false;
    echo '<tr><td><strong>ReTexifyMedia Objekt</strong></td><td>' . ($has_media_obj ? '<span class="ok">✅ Gefunden</span>' : '<span class="fail">❌ FEHLT!</span>') . '</td></tr>';
    
    $has_loadStats = strpos($js_content, 'loadStats') !== false;
    echo '<tr><td><strong>loadStats Funktion</strong></td><td>' . ($has_loadStats ? '<span class="ok">✅ Gefunden</span>' : '<span class="fail">❌ FEHLT!</span>') . '</td></tr>';
    
    $has_loadItems = strpos($js_content, 'loadItems') !== false;
    echo '<tr><td><strong>loadItems Funktion</strong></td><td>' . ($has_loadItems ? '<span class="ok">✅ Gefunden</span>' : '<span class="fail">❌ FEHLT!</span>') . '</td></tr>';
    
    $has_diagnose = strpos($js_content, 'retexify-diagnose-media-btn') !== false;
    echo '<tr><td><strong>Diagnose-Handler</strong></td><td>' . ($has_diagnose ? '<span class="ok">✅ Gefunden</span>' : '<span class="warn">⚠️ Nicht gefunden</span>') . '</td></tr>';
    
    $bracket_open = substr_count($js_content, '{');
    $bracket_close = substr_count($js_content, '}');
    $paren_open = substr_count($js_content, '(');
    $paren_close = substr_count($js_content, ')');
    
    $brackets_ok = abs($bracket_open - $bracket_close) <= 2;
    $parens_ok = abs($paren_open - $paren_close) <= 2;
    
    echo '<tr><td><strong>Klammern { }</strong></td><td>' . ($brackets_ok ? '<span class="ok">✅ Balanciert (' . $bracket_open . '/' . $bracket_close . ')</span>' : '<span class="fail">❌ UNBALANCIERT! Öffnend: ' . $bracket_open . ', Schliessend: ' . $bracket_close . '</span>') . '</td></tr>';
    echo '<tr><td><strong>Klammern ( )</strong></td><td>' . ($parens_ok ? '<span class="ok">✅ Balanciert (' . $paren_open . '/' . $paren_close . ')</span>' : '<span class="fail">❌ UNBALANCIERT! Öffnend: ' . $paren_open . ', Schliessend: ' . $paren_close . '</span>') . '</td></tr>';
    
    if (!$brackets_ok || !$parens_ok) {
        $issues++;
        echo '<tr><td colspan="2"><div class="error-box">⚠️ Unbalancierte Klammern können dazu führen, dass JavaScript-Code NACH dem Fehler NICHT MEHR AUSGEFÜHRT wird. Das erklärt warum Buttons nicht funktionieren!</div></td></tr>';
    }
    
    echo '</table>';
} else {
    echo '<span class="fail">❌ admin-script.js FEHLT!</span>';
    $critical++;
}

echo '</div>';

// ============================================================
// 9. DEBUG-LOG
// ============================================================
echo '<div class="section">';
echo '<h2>🚨 Letzte Fehler (debug.log)</h2>';

$debug_log = WP_CONTENT_DIR . '/debug.log';
if (file_exists($debug_log)) {
    $log_content = file_get_contents($debug_log);
    $log_lines = explode("\n", $log_content);
    
    $relevant_errors = array_filter(array_slice($log_lines, -200), function($line) {
        $lower = strtolower($line);
        return strpos($lower, 'retexify') !== false 
            || strpos($lower, 'fatal') !== false 
            || strpos($lower, 'parse error') !== false
            || strpos($lower, 'class not found') !== false
            || strpos($lower, 'undefined') !== false;
    });
    
    $relevant_errors = array_slice($relevant_errors, -30);
    
    if (!empty($relevant_errors)) {
        echo '<div class="error-box">';
        foreach ($relevant_errors as $err) {
            echo htmlspecialchars(trim($err)) . "\n";
        }
        echo '</div>';
    } else {
        echo '<span class="ok">✅ Keine relevanten Fehler in den letzten 200 Zeilen</span>';
    }
} else {
    echo '<div class="fix-box">';
    echo '⚠️ debug.log nicht gefunden. Aktiviere WP_DEBUG in wp-config.php:<br><br>';
    echo "<code>define('WP_DEBUG', true);</code><br>";
    echo "<code>define('WP_DEBUG_LOG', true);</code><br>";
    echo "<code>define('WP_DEBUG_DISPLAY', false);</code>";
    echo '</div>';
}

echo '</div>';

// ============================================================
// 10. PHP ERROR CHECK
// ============================================================
echo '<div class="section">';
echo '<h2>🧪 PHP Syntax-Test (Medien-SEO Manager)</h2>';

$media_file = $plugin_path . 'includes/class-media-seo-manager.php';
if (file_exists($media_file)) {
    $output = array();
    $return_var = 0;
    exec('php -l ' . escapeshellarg($media_file) . ' 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo '<span class="ok">✅ Keine Syntax-Fehler in class-media-seo-manager.php</span>';
    } else {
        echo '<div class="error-box">❌ SYNTAX-FEHLER gefunden:<br>' . htmlspecialchars(implode("\n", $output)) . '</div>';
        $critical++;
    }
} else {
    echo '<span class="fail">❌ Datei existiert nicht</span>';
}

$main_file = $plugin_path . 'retexify.php';
if (file_exists($main_file)) {
    $output2 = array();
    $return_var2 = 0;
    exec('php -l ' . escapeshellarg($main_file) . ' 2>&1', $output2, $return_var2);
    
    echo '<br><br>';
    if ($return_var2 === 0) {
        echo '<span class="ok">✅ Keine Syntax-Fehler in retexify.php</span>';
    } else {
        echo '<div class="error-box">❌ SYNTAX-FEHLER in retexify.php:<br>' . htmlspecialchars(implode("\n", $output2)) . '</div>';
        $critical++;
    }
}

echo '</div>';

// ============================================================
// ZUSAMMENFASSUNG
// ============================================================
$is_healthy = ($critical === 0);
echo '<div class="summary ' . ($is_healthy ? '' : 'bad') . '">';
echo '<h2 style="margin-bottom:10px;">' . ($is_healthy ? '✅ Zusammenfassung' : '❌ Zusammenfassung') . '</h2>';
echo '<p><strong>Probleme gefunden:</strong> ' . $issues . ' (davon ' . $critical . ' kritisch)</p>';

if ($critical > 0) {
    echo '<p style="margin-top:10px;"><strong>🔴 Hauptprobleme:</strong></p>';
    echo '<ul style="margin-left:20px;">';
    
    if (!empty($missing_files)) {
        echo '<li>Fehlende Dateien: <code>' . implode('</code>, <code>', $missing_files) . '</code></li>';
    }
    if (!empty($missing_classes)) {
        echo '<li>Fehlende Klassen: <code>' . implode('</code>, <code>', $missing_classes) . '</code></li>';
    }
    if (!empty($unregistered_actions)) {
        echo '<li>Nicht registrierte AJAX-Handler: <code>' . implode('</code>, <code>', $unregistered_actions) . '</code></li>';
    }
    
    echo '</ul>';
    echo '<p style="margin-top:10px;">👉 <strong>Schicke einen Screenshot dieser Seite an Claude für den Fix!</strong></p>';
} else {
    echo '<p>Wenn trotzdem nichts funktioniert, liegt das Problem möglicherweise an einem JavaScript-Fehler. Prüfe die Browser-Konsole (F12 → Console).</p>';
}

echo '</div>';

echo '<div class="delete-warning">⚠️ SICHERHEIT: Lösche diese Datei (diagnose.php) nachdem du die Diagnose abgeschlossen hast!</div>';

?>
    </div>
</div>
</body>
</html>
