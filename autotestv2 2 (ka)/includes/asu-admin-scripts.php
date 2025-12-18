<?php
/**
 * JavaScript für Admin-Seite
 */
?>
<script>
jQuery(document).ready(function($) {
    // Auto-Start Button
    $('#asu-auto-start').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-auto-start-status');
        var $spinner = $btn.find('.asu-spinner');
        
        // Warnung anzeigen
        var confirmMessage = '⚠️ WARNUNG: Auto Clean Up wird folgende Daten unwiderruflich löschen:\n\n' +
            '• Alle Beiträge und Seiten (publiziert, Entwurf, ausstehend, zukünftig, privat, gelöscht)\n' +
            '• Alle Themes außer Hello Elementor\n' +
            '• Plugins: Hello Dolly und Akismet\n\n' +
            'Bitte stelle sicher, dass du ein Backup erstellt hast!\n\n' +
            'Möchtest du wirklich fortfahren?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Zweite Bestätigung
        if (!confirm('Letzte Bestätigung: Möchtest du wirklich ALLE Beiträge, Seiten, Themes und Plugins löschen? Dies kann NICHT rückgängig gemacht werden!')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error info').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_auto_start',
                nonce: '<?php echo wp_create_nonce('asu_auto_start'); ?>'
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error info').addClass('success').html(response.data.message).show();
                    $btn.prop('disabled', false);
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    $status.removeClass('success info').addClass('error').html(response.data.message || 'Fehler beim Auto-Start').show();
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success info').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });

    // Plugin Installation Button
    $('#asu-install-plugins').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-plugins-status');
        var $spinner = $btn.find('.asu-spinner');
        var selected = [];
        
        $('#asu-plugin-list input[type="checkbox"]:checked:not(:disabled)').each(function() {
            selected.push($(this).val());
        });
        
        if (selected.length === 0) {
            $status.removeClass('success error').addClass('info').html('Bitte wähle mindestens ein Plugin aus.').show();
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error info').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_install_plugins',
                plugins: selected,
                nonce: '<?php echo wp_create_nonce('asu_install_plugins'); ?>'
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error info').addClass('success').html(response.data.message).show();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $status.removeClass('success info').addClass('error').html(response.data.message || 'Fehler beim Installieren der Plugins').show();
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success info').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });

    // Plugin Upload Handler
    var uploadedFiles = [];
    
    $('#asu-plugin-upload').on('change', function() {
        var files = this.files;
        var $list = $('#asu-upload-list');
        var $btn = $('#asu-upload-install-plugins');
        
        uploadedFiles = [];
        $list.empty();
        
        if (files.length > 0) {
            $list.append('<strong>Ausgewählte Dateien:</strong><ul style="margin-top: 10px;">');
            for (var i = 0; i < files.length; i++) {
                var fileName = files[i].name;
                uploadedFiles.push(files[i]);
                $list.append('<li>' + fileName + ' (' + (files[i].size / 1024 / 1024).toFixed(2) + ' MB)</li>');
            }
            $list.append('</ul>');
            $btn.show();
        } else {
            $btn.hide();
        }
    });
    
    $('#asu-upload-install-plugins').on('click', function() {
        if (uploadedFiles.length === 0) {
            $('#asu-upload-status').removeClass('success error').addClass('info').html('Bitte wähle zuerst Plugin-Dateien aus.').show();
            return;
        }
        
        var $btn = $(this);
        var $status = $('#asu-upload-status');
        var $spinner = $btn.find('.asu-spinner');
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error info').hide();
        
        var formData = new FormData();
        formData.append('action', 'asu_upload_install_plugins');
        formData.append('nonce', '<?php echo wp_create_nonce('asu_upload_install_plugins'); ?>');
        
        for (var i = 0; i < uploadedFiles.length; i++) {
            formData.append('plugins[]', uploadedFiles[i]);
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error info').addClass('success').html(response.data.message).show();
                    $('#asu-plugin-upload').val('');
                    $('#asu-upload-list').empty();
                    uploadedFiles = [];
                    $btn.hide();
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $status.removeClass('success info').addClass('error').html(response.data.message || 'Fehler beim Installieren der Plugins').show();
                    $btn.prop('disabled', false);
                }
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success info').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>

