<?php
/**
 * Admin-Styles und Scripts für Duplikations-Funktion
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * JavaScript für Quick-Duplicate Button hinzufügen
 */
add_action('admin_footer', function() {
    // Prüfe ob Duplizieren-Funktion aktiviert ist
    if (!get_option(ASU_ENABLE_DUPLICATE, 1)) {
        return;
    }
    
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->id, ['edit-page', 'edit-post', 'page', 'post'])) {
        return;
    }
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Funktion zum Hinzufügen des Duplizieren-Links
        function addDuplicateLink() {
            $('.row-actions').each(function() {
                var $row = $(this);
                
                // Prüfe ob bereits ein Duplizieren-Link vorhanden ist
                if ($row.find('.asu-quick-duplicate').length) {
                    return; // Bereits vorhanden
                }
                
                // Post-ID aus der Zeile extrahieren
                var $tr = $row.closest('tr');
                var postId = $tr.find('.check-column input[type="checkbox"]').val();
                if (!postId) {
                    var trId = $tr.attr('id');
                    if (trId) {
                        postId = trId.replace('post-', '');
                    }
                }
                
                if (!postId) {
                    return;
                }
                
                // Entferne alle vorhandenen Duplizieren-Links (falls welche da sind)
                $row.find('a[href*="asu_duplicate"], .asu-quick-duplicate').remove();
                
                var $quickBtn = $('<a href="#" class="asu-quick-duplicate" data-post-id="' + postId + '">Duplizieren</a>');
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
                                // Seite neu laden, um die duplizierte Seite in der Liste zu sehen
                                location.reload();
                            } else {
                                alert('Fehler: ' + (response.data.message || 'Unbekannter Fehler'));
                                $btn.text('Duplizieren').css('opacity', '1');
                            }
                        },
                        error: function() {
                            alert('Ein Fehler ist aufgetreten.');
                            $btn.text('Duplizieren').css('opacity', '1');
                        }
                    });
                    
                    return false;
                });
                
                // Finde "Anzeigen" Link - suche nach Link mit "view" im href oder Text "Anzeigen"
                var $viewLink = null;
                
                // Methode 1: Suche nach Link mit "view" im href
                $row.find('a').each(function() {
                    var href = $(this).attr('href') || '';
                    var text = $(this).text().trim().toLowerCase();
                    if (href.indexOf('view=') !== -1 || href.indexOf('action=view') !== -1 || text === 'anzeigen') {
                        $viewLink = $(this);
                        return false; // break
                    }
                });
                
                // Methode 2: Suche nach Link der "Anzeigen" enthält
                if (!$viewLink || $viewLink.length === 0) {
                    $row.find('a').each(function() {
                        var text = $(this).text().trim();
                        if (text.toLowerCase().indexOf('anzeigen') !== -1) {
                            $viewLink = $(this);
                            return false; // break
                        }
                    });
                }
                
                // Methode 3: Suche nach Link mit "post.php" und "view" Parameter
                if (!$viewLink || $viewLink.length === 0) {
                    $viewLink = $row.find('a[href*="post.php"][href*="view="]').first();
                }
                
                if ($viewLink && $viewLink.length) {
                    // Füge direkt nach "Anzeigen" ein: " | Duplizieren"
                    // Verwende native DOM-Methoden für zuverlässige Einfügung
                    var separator = document.createTextNode(' | ');
                    var viewLinkNode = $viewLink[0];
                    var parent = viewLinkNode.parentNode;
                    
                    // Füge Separator und Button nach dem View-Link ein
                    parent.insertBefore(separator, viewLinkNode.nextSibling);
                    parent.insertBefore($quickBtn[0], separator.nextSibling);
                } else {
                    // Fallback: Nach "Bearbeiten" suchen
                    var $editLink = $row.find('a[href*="post.php"][href*="action=edit"]').first();
                    if ($editLink.length) {
                        var separator = document.createTextNode(' | ');
                        var editLinkNode = $editLink[0];
                        var parent = editLinkNode.parentNode;
                        parent.insertBefore(separator, editLinkNode.nextSibling);
                        parent.insertBefore($quickBtn[0], separator.nextSibling);
                    } else {
                        // Letzter Fallback: Am Ende einfügen
                        var separator = document.createTextNode(' | ');
                        $row[0].appendChild(separator);
                        $row[0].appendChild($quickBtn[0]);
                    }
                }
            });
        }
        
        // Sofort ausführen
        addDuplicateLink();
        
        // Auch nach AJAX-Updates (z.B. Quick Edit)
        $(document).on('DOMNodeInserted', function(e) {
            if ($(e.target).find('.row-actions').length || $(e.target).hasClass('row-actions')) {
                setTimeout(addDuplicateLink, 100);
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
            text-decoration: none;
        }
    </style>
    <?php
});

