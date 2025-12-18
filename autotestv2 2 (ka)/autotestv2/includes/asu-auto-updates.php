<?php
/**
 * Automatische Plugin-Updates für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Automatische Updates für alle Plugins aktivieren
 */
add_filter('auto_update_plugin', '__return_true', 10, 2);

/**
 * Automatische Updates auch für Themes aktivieren (optional)
 * Kann auskommentiert werden, wenn nicht gewünscht
 */
// add_filter('auto_update_theme', '__return_true', 10, 2);

/**
 * Automatische Core-Updates aktivieren (optional)
 * Kann auskommentiert werden, wenn nicht gewünscht
 */
// add_filter('auto_update_core', '__return_true', 10, 2);

/**
 * Sicherstellen, dass automatische Updates aktiviert sind
 * Wird beim Auto-Start ausgeführt
 */
function asu_enable_auto_updates() {
    // WordPress 5.5+ Methode: auto_update_plugins Option setzen
    $auto_update_plugins = get_option('auto_update_plugins', []);
    
    if (!is_array($auto_update_plugins)) {
        $auto_update_plugins = [];
    }
    
    // Alle installierten Plugins zur Auto-Update-Liste hinzufügen
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $all_plugins = get_plugins();
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        // Überspringe dieses Plugin selbst (um Endlosschleifen zu vermeiden)
        if (strpos($plugin_file, 'autosetup/') !== false) {
            continue;
        }
        
        if (!in_array($plugin_file, $auto_update_plugins)) {
            $auto_update_plugins[] = $plugin_file;
        }
    }
    
    update_option('auto_update_plugins', $auto_update_plugins);
}

/**
 * Neue Plugins automatisch zu Auto-Updates hinzufügen
 */
add_action('upgrader_process_complete', function($upgrader_object, $options) {
    if ($options['action'] === 'install' && $options['type'] === 'plugin') {
        // Neues Plugin wurde installiert - zu Auto-Updates hinzufügen
        if (isset($options['plugin'])) {
            $plugin_file = $options['plugin'];
            $auto_update_plugins = get_option('auto_update_plugins', []);
            
            if (!is_array($auto_update_plugins)) {
                $auto_update_plugins = [];
            }
            
            if (!in_array($plugin_file, $auto_update_plugins)) {
                $auto_update_plugins[] = $plugin_file;
                update_option('auto_update_plugins', $auto_update_plugins);
            }
        }
    }
}, 10, 2);

/**
 * Admin-Notice für Auto-Updates (optional)
 */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Nur einmal anzeigen
    $notice_shown = get_option('asu_auto_updates_notice_shown', false);
    if ($notice_shown) {
        return;
    }
    
    $auto_update_plugins = get_option('auto_update_plugins', []);
    if (!empty($auto_update_plugins)) {
        echo '<div class="notice notice-info is-dismissible" id="asu-auto-updates-notice">
                <p><strong>Auto Setup:</strong> Automatische Plugin-Updates sind aktiviert. Alle Plugins werden automatisch aktualisiert.</p>
              </div>';
        
        // JavaScript zum Schließen der Notice
        ?>
        <script>
        jQuery(document).on('click', '#asu-auto-updates-notice .notice-dismiss', function() {
            jQuery.post(ajaxurl, {
                action: 'asu_dismiss_auto_updates_notice',
                nonce: '<?php echo wp_create_nonce('asu_dismiss_notice'); ?>'
            });
        });
        </script>
        <?php
    }
});

/**
 * AJAX: Notice schließen
 */
add_action('wp_ajax_asu_dismiss_auto_updates_notice', function() {
    check_ajax_referer('asu_dismiss_notice', 'nonce');
    update_option('asu_auto_updates_notice_shown', true);
    wp_send_json_success();
});

