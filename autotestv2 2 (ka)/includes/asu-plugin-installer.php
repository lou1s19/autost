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

/**
 * AJAX: Hochgeladene Plugins installieren
 */
add_action('wp_ajax_asu_upload_install_plugins', function() {
    check_ajax_referer('asu_upload_install_plugins', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    if (empty($_FILES['plugins'])) {
        wp_send_json_error(['message' => 'Keine Dateien hochgeladen']);
        return;
    }
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    $installed = [];
    $errors = [];
    
    // WordPress File Upload Handler
    $upload_overrides = ['test_form' => false];
    
    // Verarbeite alle hochgeladenen Dateien
    $files = [];
    if (is_array($_FILES['plugins']['name'])) {
        // Mehrere Dateien
        foreach ($_FILES['plugins']['name'] as $key => $name) {
            if (!empty($name)) {
                $files[] = [
                    'name' => $_FILES['plugins']['name'][$key],
                    'type' => $_FILES['plugins']['type'][$key],
                    'tmp_name' => $_FILES['plugins']['tmp_name'][$key],
                    'error' => $_FILES['plugins']['error'][$key],
                    'size' => $_FILES['plugins']['size'][$key]
                ];
            }
        }
    } else {
        // Eine Datei
        if (!empty($_FILES['plugins']['name'])) {
            $files[] = $_FILES['plugins'];
        }
    }
    
    foreach ($files as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $file['name'] . ': Upload-Fehler';
            continue;
        }
        
        // Prüfe ob es eine ZIP-Datei ist
        $file_info = wp_check_filetype($file['name']);
        if ($file_info['ext'] !== 'zip') {
            $errors[] = $file['name'] . ': Keine gültige ZIP-Datei';
            continue;
        }
        
        // Verschiebe die Datei in den temporären Ordner
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            $errors[] = $file['name'] . ': ' . $uploaded_file['error'];
            continue;
        }
        
        // Installiere das Plugin
        $upgrader = new Plugin_Upgrader(new ASU_Quiet_Upgrader_Skin());
        $result = $upgrader->install($uploaded_file['file']);
        
        // Lösche die temporäre Datei
        @unlink($uploaded_file['file']);
        
        if (is_wp_error($result)) {
            $errors[] = $file['name'] . ': ' . $result->get_error_message();
            continue;
        }
        
        if (!$result) {
            $errors[] = $file['name'] . ': Installation fehlgeschlagen';
            continue;
        }
        
        // Finde das Plugin-File und aktiviere es
        // Extrahiere den Plugin-Namen aus dem Dateinamen (ohne .zip)
        $plugin_name_from_file = basename($file['name'], '.zip');
        
        // Suche nach dem Plugin-Verzeichnis
        $plugin_file = null;
        $plugin_dirs = glob(WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR);
        
        foreach ($plugin_dirs as $plugin_dir) {
            $dir_name = basename($plugin_dir);
            // Prüfe ob der Verzeichnisname dem Dateinamen ähnelt
            if (stripos($dir_name, $plugin_name_from_file) !== false || stripos($plugin_name_from_file, $dir_name) !== false) {
                // Suche nach der Haupt-Plugin-Datei
                $php_files = glob($plugin_dir . '/*.php');
                if (!empty($php_files)) {
                    // Prüfe ob eine Datei den gleichen Namen wie das Verzeichnis hat
                    $main_file = $plugin_dir . '/' . $dir_name . '.php';
                    if (file_exists($main_file)) {
                        $plugin_file = $dir_name . '/' . $dir_name . '.php';
                    } else {
                        // Nimm die erste PHP-Datei
                        $plugin_file = $dir_name . '/' . basename($php_files[0]);
                    }
                    break;
                }
            }
        }
        
        // Fallback: Suche in allen Plugin-Verzeichnissen nach der neuesten Installation
        if (!$plugin_file) {
            // Finde das neueste Plugin-Verzeichnis (basierend auf Änderungsdatum)
            $newest_dir = null;
            $newest_time = 0;
            foreach ($plugin_dirs as $plugin_dir) {
                $mtime = filemtime($plugin_dir);
                if ($mtime > $newest_time && $mtime > (time() - 60)) { // Innerhalb der letzten Minute
                    $newest_time = $mtime;
                    $newest_dir = $plugin_dir;
                }
            }
            
            if ($newest_dir) {
                $php_files = glob($newest_dir . '/*.php');
                if (!empty($php_files)) {
                    $dir_name = basename($newest_dir);
                    $main_file = $newest_dir . '/' . $dir_name . '.php';
                    if (file_exists($main_file)) {
                        $plugin_file = $dir_name . '/' . $dir_name . '.php';
                    } else {
                        $plugin_file = $dir_name . '/' . basename($php_files[0]);
                    }
                }
            }
        }
        
        if ($plugin_file && file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
            $activation_result = activate_plugin($plugin_file);
            if (is_wp_error($activation_result)) {
                $errors[] = $file['name'] . ': Aktivierung fehlgeschlagen - ' . $activation_result->get_error_message();
            } else {
                $installed[] = $file['name'] . ' (installiert & aktiviert)';
            }
        } else {
            $installed[] = $file['name'] . ' (installiert, bitte manuell aktivieren)';
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

