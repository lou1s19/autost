<?php
/**
 * Alt-Text Admin-Seite Template
 */
?>
<div class="wrap asu-alttext-wrapper">
    <style>
        .asu-alttext-wrapper {
            max-width: 1200px;
            margin: 20px auto;
        }
        .asu-alttext-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .asu-alttext-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .asu-alttext-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .asu-alttext-card h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
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
        .asu-form-group input[type="text"],
        .asu-form-group textarea,
        .asu-form-group select {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .asu-form-group textarea {
            min-height: 100px;
        }
        .asu-form-group .description {
            color: #666;
            font-size: 13px;
            margin-top: 5px;
        }
        .asu-checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .asu-checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
        .asu-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .asu-button:hover {
            opacity: 0.9;
        }
        .asu-button.secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .asu-page-mapping {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            background: #f9f9f9;
        }
        .asu-page-mapping-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .asu-page-mapping-item label {
            min-width: 200px;
            font-weight: normal;
        }
        .asu-page-mapping-item input {
            flex: 1;
            max-width: none;
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
    </style>

    <div class="asu-alttext-header">
        <h1>🖼️ Alt-Text Manager</h1>
        <p>Automatische Alt-Texte für Bilder verwalten und generieren</p>
    </div>

    <div class="asu-alttext-card" style="background: #fff3cd; border-left: 5px solid #ff9800; margin-bottom: 20px;">
        <h3 style="color: #856404; margin-top: 0;">⚠️ Wichtig: Einstellungen speichern!</h3>
        <p style="color: #856404; margin-bottom: 0;">
            <strong>Vergiss nicht, nach dem Ändern der Einstellungen auf "Einstellungen speichern" zu klicken!</strong><br>
            Die Bulk-Operation funktioniert nur, wenn der "Alt-Text Manager aktivieren" Häckchen aktiviert und gespeichert wurde.
        </p>
    </div>

    <form method="post" action="">
        <?php wp_nonce_field('asu_alttext_save'); ?>
        
        <div class="asu-alttext-card">
            <h2>⚙️ Allgemeine Einstellungen</h2>
            
            <div class="asu-form-group" style="background: #e7f3ff; border: 2px solid #2196F3; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_enabled" 
                           name="asu_alttext_enabled" 
                           value="1" 
                           style="width: 25px; height: 25px; cursor: pointer;"
                           <?php checked($enabled, 1); ?>>
                    <label for="asu_alttext_enabled" style="font-size: 18px; font-weight: bold; color: #1976D2;">
                        ✅ Alt-Text Manager aktivieren
                    </label>
                </div>
                <p class="description" style="margin-top: 10px; color: #333;">
                    <strong>Wichtig:</strong> Dieses Häckchen <strong>muss aktiviert sein</strong>, damit die Bulk-Operation funktioniert!<br>
                    Wenn aktiviert, werden Alt-Texte automatisch für <strong>zukünftig hochgeladene oder verwendete Bilder</strong> generiert. 
                    Für bestehende Bilder nutze die Bulk-Operation weiter unten.
                </p>
            </div>

            <div class="asu-form-group">
                <label for="asu_alttext_mode">Alt-Text Modus:</label>
                <select id="asu_alttext_mode" name="asu_alttext_mode">
                    <option value="title" <?php selected($mode, 'title'); ?>>Bildtitel verwenden</option>
                    <option value="title_page" <?php selected($mode, 'title_page'); ?>>Bildtitel + Seitenkontext</option>
                    <option value="page" <?php selected($mode, 'page'); ?>>Seitenbasiert (siehe unten)</option>
                </select>
                <p class="description">
                    <strong>Bildtitel:</strong> Verwendet den Dateinamen/Titel des Bildes<br>
                    <strong>Bildtitel + Seitenkontext:</strong> Bildtitel + Name der Seite wo es verwendet wird<br>
                    <strong>Seitenbasiert:</strong> Verwendet die individuellen Einstellungen pro Seite (siehe unten)
                </p>
            </div>

            <div class="asu-form-group">
                <label for="asu_alttext_apply_to">Anwenden auf:</label>
                <select id="asu_alttext_apply_to" name="asu_alttext_apply_to">
                    <option value="all" <?php selected($apply_to, 'all'); ?>>Alle Bilder</option>
                    <option value="empty_only" <?php selected($apply_to, 'empty_only'); ?>>Nur Bilder ohne Alt-Text</option>
                </select>
                <p class="description">
                    <strong>Alle Bilder:</strong> Alt-Texte werden für alle zukünftigen Bilder generiert, auch wenn bereits ein Alt-Text vorhanden ist (wird überschrieben).<br>
                    <strong>Nur Bilder ohne Alt-Text:</strong> Alt-Texte werden nur für zukünftige Bilder ohne vorhandenen Alt-Text generiert.
                </p>
            </div>

            <div class="asu-form-group">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_skip_existing" 
                           name="asu_alttext_skip_existing" 
                           value="1" 
                           <?php checked($skip_existing, 1); ?>>
                    <label for="asu_alttext_skip_existing"><strong>Bestehende Alt-Texte und Untertitel nicht überschreiben</strong></label>
                </div>
                <p class="description">
                    Wenn aktiviert, werden Bilder mit bereits vorhandenen Alt-Texten oder Untertiteln (Captions) komplett übersprungen und nicht bearbeitet. 
                    Dies schützt manuell erstellte Alt-Texte vor Überschreibung.
                </p>
            </div>
        </div>

        <div class="asu-alttext-card" id="asu-title-settings">
            <h2>📝 Titel-Einstellungen</h2>
            
            <div class="asu-form-group">
                <label for="asu_alttext_prefix">Text vor dem Bildtitel:</label>
                <input type="text" 
                       id="asu_alttext_prefix" 
                       name="asu_alttext_prefix" 
                       value="<?php echo esc_attr($prefix); ?>"
                       placeholder="z.B. 'Bild:' oder 'Foto:'">
                <p class="description">Wird vor dem Bildtitel eingefügt (z.B. "Bild: bild1.jpg")</p>
            </div>

            <div class="asu-form-group">
                <label for="asu_alttext_suffix">Text nach dem Bildtitel:</label>
                <input type="text" 
                       id="asu_alttext_suffix" 
                       name="asu_alttext_suffix" 
                       value="<?php echo esc_attr($suffix); ?>"
                       placeholder="z.B. ' - Foto' oder ' - Bild'">
                <p class="description">Wird nach dem Bildtitel eingefügt (z.B. "bild1.jpg - Foto")</p>
            </div>

            <div class="asu-form-group">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_use_title" 
                           name="asu_alttext_use_title" 
                           value="1" 
                           <?php checked($use_title, 1); ?>>
                    <label for="asu_alttext_use_title">Alt-Text auch als Bildtitel setzen</label>
                </div>
                <p class="description">Der generierte Alt-Text wird zusätzlich als Titel des Bildes in der Mediathek gespeichert.</p>
            </div>

            <div class="asu-form-group">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_use_caption" 
                           name="asu_alttext_use_caption" 
                           value="1" 
                           <?php checked($use_caption, 1); ?>>
                    <label for="asu_alttext_use_caption">Alt-Text auch als Bildunterschrift (Caption) setzen</label>
                </div>
                <p class="description">Der generierte Alt-Text wird zusätzlich als Bildunterschrift (Caption) gespeichert, die unter dem Bild angezeigt werden kann.</p>
            </div>

            <div class="asu-form-group">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_use_description" 
                           name="asu_alttext_use_description" 
                           value="1" 
                           <?php checked($use_description, 1); ?>>
                    <label for="asu_alttext_use_description">Alt-Text auch als Beschreibung setzen</label>
                </div>
                <p class="description">Der generierte Alt-Text wird zusätzlich in das Beschreibungsfeld (Description) des Bildes in der Mediathek eingefügt.</p>
            </div>
        </div>

        <div class="asu-alttext-card" id="asu-page-mapping" style="display: <?php echo $mode === 'page' ? 'block' : 'none'; ?>;">
            <h2>🗺️ Seitenbasierte Alt-Texte</h2>
            <p>Hier kannst du für jede Seite einen individuellen Alt-Text definieren, der für alle Bilder auf dieser Seite verwendet wird.</p>
            
            <div class="asu-page-mapping">
                <?php foreach ($pages as $page): ?>
                    <div class="asu-page-mapping-item">
                        <label for="page_<?php echo $page->ID; ?>">
                            <strong><?php echo esc_html($page->post_title); ?></strong>
                        </label>
                        <input type="text" 
                               id="page_<?php echo $page->ID; ?>" 
                               name="asu_page_mapping[<?php echo $page->ID; ?>]" 
                               value="<?php echo esc_attr($page_mapping[$page->ID] ?? ''); ?>"
                               placeholder="z.B. 'Bild auf <?php echo esc_attr($page->post_title); ?>'">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="asu-alttext-card">
            <button type="submit" name="asu_alttext_save" class="asu-button">
                💾 Einstellungen speichern
            </button>
        </div>
    </form>

    <div class="asu-alttext-card">
        <h2>🚀 Bulk-Operationen für bestehende Bilder</h2>
        <div style="background: #fff3cd; border-left: 5px solid #ff9800; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <p style="color: #856404; margin: 0;">
                <strong>⚠️ Wichtig:</strong> Die Bulk-Operation funktioniert <strong>nur, wenn "Alt-Text Manager aktivieren" aktiviert und gespeichert wurde</strong>!<br>
                Bitte stelle sicher, dass das Häckchen oben aktiviert ist und du die Einstellungen gespeichert hast, bevor du die Bulk-Operation startest.
            </p>
        </div>
        <p>
            <strong>Hinweis:</strong> Diese Funktion wendet die oben konfigurierten Einstellungen auf <strong>alle bereits vorhandenen Bilder</strong> in deiner Mediathek an. 
            Die automatischen Einstellungen oben gelten nur für zukünftige Bilder.
        </p>
        <p class="description">
            Die Bulk-Operation durchsucht alle Bilder in deiner Mediathek und generiert Alt-Texte basierend auf deinen aktuellen Einstellungen. 
            Dies kann bei vielen Bildern einige Zeit dauern.
        </p>
        <button type="button" class="asu-button secondary" id="asu-bulk-alttext" <?php echo !$enabled ? 'disabled' : ''; ?>>
            Alt-Texte für alle bestehenden Bilder generieren
            <span class="asu-spinner" style="display:none;"></span>
        </button>
        <?php if (!$enabled): ?>
            <p style="color: #dc3545; margin-top: 10px;">
                <strong>❌ Bulk-Operation deaktiviert:</strong> Bitte aktiviere zuerst "Alt-Text Manager aktivieren" oben und speichere die Einstellungen!
            </p>
        <?php endif; ?>
        
        <!-- Progress Bar -->
        <div id="asu-bulk-progress" style="display: none; margin-top: 20px;">
            <div style="background: #f0f0f0; border-radius: 10px; height: 30px; position: relative; overflow: hidden;">
                <div id="asu-bulk-progress-bar" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;"></div>
            </div>
            <p id="asu-bulk-progress-text" style="text-align: center; margin-top: 10px; color: #666; font-size: 14px;"></p>
        </div>
        
        <div class="asu-status" id="asu-bulk-status"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Seiten-Mapping anzeigen/verstecken basierend auf Modus
    $('#asu_alttext_mode').on('change', function() {
        if ($(this).val() === 'page') {
            $('#asu-page-mapping').slideDown();
        } else {
            $('#asu-page-mapping').slideUp();
        }
    });

    // Bulk Alt-Text
    $('#asu-bulk-alttext').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-bulk-status');
        var $spinner = $btn.find('.asu-spinner');
        var $progress = $('#asu-bulk-progress');
        var $progressBar = $('#asu-bulk-progress-bar');
        var $progressText = $('#asu-bulk-progress-text');
        
        // Prüfe ob Alt-Text Manager aktiviert ist
        var isEnabled = $('#asu_alttext_enabled').is(':checked');
        if (!isEnabled) {
            $status.removeClass('success error').addClass('error').html('❌ Fehler: Alt-Text Manager ist nicht aktiviert! Bitte aktiviere das Häckchen oben und speichere die Einstellungen.').show();
            return;
        }
        
        if (!confirm('Möchtest du wirklich Alt-Texte für ALLE Bilder generieren? Dies kann einige Zeit dauern.')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error').hide();
        $progress.show();
        $progressBar.css('width', '0%').text('Initialisiere...');
        $progressText.text('Lade Bildliste...');
        
        var totalImages = 0;
        var totalProcessed = 0;
        var totalErrors = 0;
        var totalSkipped = 0;
        var currentOffset = 0;
        
        // Zuerst Gesamtanzahl holen
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_bulk_alttext_count',
                nonce: '<?php echo wp_create_nonce('asu_bulk_alttext'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    totalImages = response.data.total;
                    $progressText.text('0 von ' + totalImages + ' Bildern verarbeitet');
                    
                    // Jetzt mit der Verarbeitung beginnen
                    processBulkChunk();
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
        
        function processBulkChunk() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'asu_bulk_alttext',
                    offset: currentOffset,
                    nonce: '<?php echo wp_create_nonce('asu_bulk_alttext'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        totalProcessed += response.data.processed || 0;
                        totalErrors += response.data.errors || 0;
                        totalSkipped += response.data.skipped || 0;
                        currentOffset = response.data.next_offset || currentOffset;
                        
                        // Progress aktualisieren
                        var processedCount = currentOffset || (totalProcessed + totalErrors + totalSkipped);
                        var percentage = totalImages > 0 ? Math.min(100, Math.round((processedCount / totalImages) * 100)) : 0;
                        $progressBar.css('width', percentage + '%').text(percentage + '%');
                        $progressText.text(processedCount + ' von ' + totalImages + ' Bildern verarbeitet | ✅ ' + totalProcessed + ' | ⚠️ ' + totalSkipped + ' | ❌ ' + totalErrors);
                        
                        // Nächsten Chunk verarbeiten
                        if (response.data.has_more && response.data.next_offset !== null) {
                            setTimeout(function() {
                                processBulkChunk();
                            }, 100); // Kurze Pause zwischen Chunks
                        } else {
                            // Fertig!
                            $spinner.hide();
                            $progressBar.css('width', '100%').text('100%');
                            
                            var message = "✅ Fertig! " + totalProcessed + " Bilder verarbeitet";
                            if (totalSkipped > 0) {
                                message += ", " + totalSkipped + " übersprungen";
                            }
                            if (totalErrors > 0) {
                                message += ", " + totalErrors + " Fehler";
                            }
                            
                            $status.removeClass('error').addClass('success').html(message).show();
                            $progressText.text(message);
                            $btn.prop('disabled', false);
                            
                            // Progress nach 3 Sekunden ausblenden
                            setTimeout(function() {
                                $progress.fadeOut();
                            }, 3000);
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

