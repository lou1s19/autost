<?php
/**
 * Plugin-Installation für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Stille Upgrader Skin für Plugin-Installation
 */
class ASU_Quiet_Upgrader_Skin extends WP_Upgrader_Skin {
    public function feedback($string, ...$args) {
        // Stumm - keine Ausgabe
    }
    public function header() {}
    public function footer() {}
}

/**
 * AJAX: Plugins installieren
 */
add_action('wp_ajax_asu_install_plugins', function() {
    check_ajax_referer('asu_install_plugins', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    if (!isset($_POST['plugins']) || !is_array($_POST['plugins'])) {
        wp_send_json_error(['message' => 'Keine Plugins ausgewählt']);
        return;
    }
    
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    $plugins = array_map('sanitize_text_field', $_POST['plugins']);
    $installed = [];
    $errors = [];
    
    foreach ($plugins as $plugin_slug) {
        // Prüfen ob bereits installiert
        if (asu_is_plugin_installed($plugin_slug)) {
            $plugin_file = asu_get_plugin_file($plugin_slug);
            if ($plugin_file && !is_plugin_active($plugin_file)) {
                $activation_result = activate_plugin($plugin_file);
                if (is_wp_error($activation_result)) {
                    $errors[] = $plugin_slug . ': Aktivierung fehlgeschlagen - ' . $activation_result->get_error_message();
                } else {
                    $installed[] = $plugin_slug . ' (aktiviert)';
                }
            } else {
                $installed[] = $plugin_slug . ' (bereits aktiv)';
            }
            continue;
        }
        
        // Plugin installieren
        $api = plugins_api('plugin_information', [
            'slug' => $plugin_slug,
            'fields' => ['short_description' => false]
        ]);
        
        if (is_wp_error($api)) {
            $errors[] = $plugin_slug . ': ' . $api->get_error_message();
            continue;
        }
        
        if (empty($api->download_link)) {
            $errors[] = $plugin_slug . ': Download-Link nicht verfügbar';
            continue;
        }
        
        $upgrader = new Plugin_Upgrader(new ASU_Quiet_Upgrader_Skin());
        $result = $upgrader->install($api->download_link);
        
        if (is_wp_error($result)) {
            $errors[] = $plugin_slug . ': ' . $result->get_error_message();
            continue;
        }
        
        if (!$result) {
            $errors[] = $plugin_slug . ': Installation fehlgeschlagen';
            continue;
        }
        
        // Plugin aktivieren
        $plugin_file = asu_get_plugin_file($plugin_slug);
        if ($plugin_file) {
            $activation_result = activate_plugin($plugin_file);
            if (is_wp_error($activation_result)) {
                $errors[] = $plugin_slug . ': Aktivierung fehlgeschlagen - ' . $activation_result->get_error_message();
            } else {
                $installed[] = $plugin_slug;
            }
        } else {
            $errors[] = $plugin_slug . ': Plugin-Datei nicht gefunden';
        }
    }
    
    $message = '';
    if (!empty($installed)) {
        $message .= '✅ Installiert: ' . implode(', ', $installed) . '<br>';
    }
    if (!empty($errors)) {
        $message .= '❌ Fehler: ' . implode(', ', $errors);
    }
    
    if (empty($message)) {
        $message = 'Keine Aktion erforderlich.';
    }
    
    wp_send_json_success(['message' => $message]);
});

