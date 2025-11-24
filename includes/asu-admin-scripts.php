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
});
</script>

