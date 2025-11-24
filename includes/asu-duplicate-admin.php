<?php
/**
 * Admin-Styles und Scripts für Duplikations-Funktion
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * JavaScript für Quick-Duplicate Button hinzufügen
 */
add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['edit-page', 'edit-post', 'page', 'post'])) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Quick-Duplicate Button zu jeder Zeile hinzufügen
        $('.row-actions').each(function() {
            var $row = $(this);
            var $duplicateLink = $row.find('a[href*="asu_duplicate_post"]');
            
            if ($duplicateLink.length) {
                // Post-ID aus der Zeile extrahieren
                var $tr = $row.closest('tr');
                var postId = $tr.find('.check-column input[type="checkbox"]').val();
                if (!postId) {
                    var trId = $tr.attr('id');
                    if (trId) {
                        postId = trId.replace('post-', '');
                    }
                }
                
                if (postId && !$row.find('.asu-quick-duplicate').length) {
                    var $quickBtn = $('<a href="#" class="asu-quick-duplicate" data-post-id="' + postId + '">Schnell duplizieren</a>');
                    $quickBtn.on('click', function(e) {
                        e.preventDefault();
                        var $btn = $(this);
                        var postId = $btn.data('post-id');
                        
                        if (!confirm('Möchtest du diese Seite/Beitrag wirklich duplizieren?')) {
                            return false;
                        }
                        
                        $btn.text('Duplizieren...').css('opacity', '0.6');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'asu_quick_duplicate',
                                post_id: postId,
                                nonce: '<?php echo wp_create_nonce('asu_quick_duplicate'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert(response.data.message);
                                    if (response.data.edit_url) {
                                        window.location.href = response.data.edit_url;
                                    } else {
                                        location.reload();
                                    }
                                } else {
                                    alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                                    $btn.text('Schnell duplizieren').css('opacity', '1');
                                }
                            },
                            error: function() {
                                alert('Ein Fehler ist aufgetreten.');
                                $btn.text('Schnell duplizieren').css('opacity', '1');
                            }
                        });
                        
                        return false;
                    });
                    
                    $duplicateLink.after(' | ').after($quickBtn);
                }
            }
        });
    });
    </script>
    <style>
        .asu-quick-duplicate {
            color: #2271b1;
            text-decoration: none;
        }
        .asu-quick-duplicate:hover {
            color: #135e96;
            text-decoration: underline;
        }
    </style>
    <?php
});

