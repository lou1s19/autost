<?php
/**
 * WebP-Konvertierung Admin-Seite
 */

if (!function_exists('asu_check_webp_support')) {
    return;
}

$support = asu_check_webp_support();
$has_support = isset($support['webp']) ? $support['webp'] : false;
$method = isset($support['imagick']) && $support['imagick'] ? 'Imagick' : (isset($support['gd']) && $support['gd'] ? 'GD Library' : 'Keine');
?>
<div class="wrap asu-webp-wrapper">
    <style>
        .asu-webp-wrapper {
            max-width: 1200px;
            margin: 20px auto;
        }
        .asu-webp-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .asu-webp-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .asu-webp-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .asu-webp-card h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .asu-status-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .asu-status-box.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .asu-status-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .asu-status-box.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .asu-form-group {
            margin-bottom: 20px;
        }
        .asu-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .asu-form-group input[type="range"],
        .asu-form-group input[type="number"] {
            width: 100%;
            max-width: 300px;
        }
        .asu-form-group .range-value {
            display: inline-block;
            margin-left: 10px;
            font-weight: bold;
            color: #667eea;
        }
        .asu-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .asu-button:hover {
            opacity: 0.9;
        }
        .asu-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .asu-button.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .asu-status {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .asu-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .asu-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .asu-status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .asu-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .asu-info-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .asu-info-list li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .asu-info-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
    </style>

    <div class="asu-webp-header">
        <h1>🖼️ WebP Konvertierung</h1>
        <p>Konvertiere alle Bilder zu WebP für bessere Performance und kleinere Dateigrößen</p>
    </div>

    <?php if (!$has_support): ?>
        <div class="asu-status-box error">
            <strong>⚠️ WebP wird nicht unterstützt</strong>
            <p>Dein Server unterstützt keine WebP-Konvertierung. Bitte installiere entweder:</p>
            <ul>
                <li>GD Library mit WebP-Unterstützung</li>
                <li>oder Imagick Extension mit WebP-Unterstützung</li>
            </ul>
            <p><strong>Gefundene Methoden:</strong> <?php echo esc_html($method); ?></p>
        </div>
    <?php else: ?>
        <div class="asu-status-box success">
            <strong>✅ WebP wird unterstützt</strong>
            <p>Verwendete Methode: <strong><?php echo esc_html($method); ?></strong></p>
            <?php if ($support['imagick']): ?>
                <p>✨ Imagick wird verwendet - beste Qualität und Transparenz-Erhaltung</p>
            <?php endif; ?>
        </div>

        <div class="asu-webp-card">
            <h2>⚙️ Einstellungen</h2>
            
            <div class="asu-form-group">
                <label>
                    <input type="checkbox" id="asu_skip_existing" checked>
                    Bereits konvertierte Bilder überspringen
                </label>
                <p class="description">
                    Wenn aktiviert, werden Bilder die bereits eine WebP-Version haben übersprungen.
                </p>
            </div>
            
            <p class="description" style="color: #666; font-style: italic;">
                <strong>Hinweis:</strong> Die Konvertierung verwendet eine optimale Qualität (85%) für beste Balance zwischen Dateigröße und Bildqualität.
            </p>
        </div>

        <div class="asu-webp-card">
            <h2>🚀 Konvertierung starten</h2>
            <p>Konvertiere alle Bilder in deiner Medienbibliothek zu WebP:</p>
            <ul class="asu-info-list">
                <li>Transparenz wird erhalten (kein gelber Hintergrund)</li>
                <li>Originale Bilder bleiben erhalten</li>
                <li>WebP-Versionen werden als separate Dateien gespeichert</li>
                <li>Kleinere Dateigrößen für schnellere Ladezeiten</li>
            </ul>
            
            <button type="button" class="asu-button" id="asu-convert-all-webp">
                Alle Bilder zu WebP konvertieren
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            
            <!-- Progress Bar -->
            <div id="asu-webp-progress" style="display: none; margin-top: 20px;">
                <div style="background: #f0f0f0; border-radius: 10px; height: 30px; position: relative; overflow: hidden;">
                    <div id="asu-webp-progress-bar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;"></div>
                </div>
                <p id="asu-webp-progress-text" style="text-align: center; margin-top: 10px; color: #666; font-size: 14px;"></p>
            </div>
            
            <div class="asu-status" id="asu-webp-status"></div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#asu-convert-all-webp').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-webp-status');
        var $spinner = $btn.find('.asu-spinner');
        var $progress = $('#asu-webp-progress');
        var $progressBar = $('#asu-webp-progress-bar');
        var $progressText = $('#asu-webp-progress-text');
        var skipExisting = $('#asu_skip_existing').is(':checked');
        
        if (!confirm('Möchtest du wirklich ALLE Bilder zu WebP konvertieren? Dies kann einige Zeit dauern.')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error info').hide();
        $progress.show();
        $progressBar.css('width', '0%').text('Initialisiere...');
        $progressText.text('Lade Bildliste...');
        
        var totalImages = 0;
        var totalConverted = 0;
        var totalSkipped = 0;
        var totalErrors = 0;
        var currentOffset = 0;
        var allErrorMessages = [];
        
        // Zuerst Gesamtanzahl holen
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_webp_count',
                nonce: '<?php echo wp_create_nonce('asu_convert_webp'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    totalImages = response.data.total;
                    $progressText.text('0 von ' + totalImages + ' Bildern verarbeitet');
                    
                    // Jetzt mit der Verarbeitung beginnen
                    processWebPChunk();
                } else {
                    $spinner.hide();
                    $progress.hide();
                    $status.removeClass('success').addClass('error').html('Fehler beim Laden der Bildliste.').show();
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                $spinner.hide();
                $progress.hide();
                $status.removeClass('success').addClass('error').html('Fehler beim Laden der Bildliste.').show();
                $btn.prop('disabled', false);
            }
        });
        
        function processWebPChunk() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asu_convert_all_webp',
                    offset: currentOffset,
                    skip_existing: skipExisting ? 'true' : 'false',
                    nonce: '<?php echo wp_create_nonce('asu_convert_webp'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        totalConverted += response.data.converted || 0;
                        totalSkipped += response.data.skipped || 0;
                        totalErrors += response.data.errors || 0;
                        
                        if (response.data.error_messages && response.data.error_messages.length > 0) {
                            allErrorMessages = allErrorMessages.concat(response.data.error_messages);
                        }
                        
                        currentOffset = response.data.next_offset || currentOffset;
                        
                        // Progress aktualisieren
                        var processedCount = currentOffset || (totalConverted + totalSkipped + totalErrors);
                        var percentage = totalImages > 0 ? Math.min(100, Math.round((processedCount / totalImages) * 100)) : 0;
                        $progressBar.css('width', percentage + '%').text(percentage + '%');
                        $progressText.text(processedCount + ' von ' + totalImages + ' Bildern verarbeitet | ✅ ' + totalConverted + ' | ⚠️ ' + totalSkipped + ' | ❌ ' + totalErrors);
                        
                        // Nächsten Chunk verarbeiten
                        if (response.data.has_more && response.data.next_offset !== null) {
                            setTimeout(function() {
                                processWebPChunk();
                            }, 200); // Kurze Pause zwischen Chunks
                        } else {
                            // Fertig!
                            $spinner.hide();
                            $progressBar.css('width', '100%').text('100%');
                            
                            var message = "✅ Fertig! " + totalConverted + " Bilder konvertiert";
                            if (totalSkipped > 0) {
                                message += ", " + totalSkipped + " übersprungen";
                            }
                            if (totalErrors > 0) {
                                message += ", " + totalErrors + " Fehler";
                            }
                            
                            if (allErrorMessages.length > 0) {
                                message += '<br><br><strong>Fehlerdetails (erste ' + Math.min(10, allErrorMessages.length) + '):</strong><br>';
                                message += allErrorMessages.slice(0, 10).join('<br>');
                            }
                            
                            $status.removeClass('error').addClass('success').html(message).show();
                            $progressText.text(message.replace(/<[^>]*>/g, ''));
                            $btn.prop('disabled', false);
                            
                            // Progress nach 5 Sekunden ausblenden
                            setTimeout(function() {
                                $progress.fadeOut();
                            }, 5000);
                        }
                    } else {
                        $spinner.hide();
                        $progress.hide();
                        $status.removeClass('success').addClass('error').html(response.data.message || 'Fehler bei der Verarbeitung').show();
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    $spinner.hide();
                    $progress.hide();
                    $status.removeClass('success').addClass('error').html('Ein Fehler ist aufgetreten: ' + error).show();
                    $btn.prop('disabled', false);
                }
            });
        }
    });
});
</script>

