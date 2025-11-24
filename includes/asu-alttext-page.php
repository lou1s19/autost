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

    <form method="post" action="">
        <?php wp_nonce_field('asu_alttext_save'); ?>
        
        <div class="asu-alttext-card">
            <h2>⚙️ Allgemeine Einstellungen</h2>
            
            <div class="asu-form-group">
                <div class="asu-checkbox-group">
                    <input type="checkbox" 
                           id="asu_alttext_enabled" 
                           name="asu_alttext_enabled" 
                           value="1" 
                           <?php checked($enabled, 1); ?>>
                    <label for="asu_alttext_enabled">Alt-Text Manager aktivieren</label>
                </div>
                <p class="description">Wenn aktiviert, werden Alt-Texte automatisch generiert</p>
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
                <p class="description">Bestimmt, ob bestehende Alt-Texte überschrieben werden sollen</p>
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
        <h2>🚀 Bulk-Operationen</h2>
        <p>Alt-Texte für alle bestehenden Bilder generieren</p>
        <button type="button" class="asu-button secondary" id="asu-bulk-alttext">
            Alt-Texte für alle Bilder generieren
            <span class="asu-spinner" style="display:none;"></span>
        </button>
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
        
        if (!confirm('Möchtest du wirklich Alt-Texte für ALLE Bilder generieren? Dies kann einige Zeit dauern.')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_bulk_alttext',
                nonce: '<?php echo wp_create_nonce('asu_bulk_alttext'); ?>'
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error').addClass('success').html(response.data.message).show();
                } else {
                    $status.removeClass('success').addClass('error').html(response.data.message || 'Fehler').show();
                }
                $btn.prop('disabled', false);
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>

