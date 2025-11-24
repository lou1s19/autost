<?php
/**
 * Kernfunktionen für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Elementor: Container prüfen ob aktiv
 */
function asu_is_container_active(): bool {
    $experiments = get_option('elementor_experimentation', []);
    if (is_array($experiments)) {
        if (isset($experiments['container']) && $experiments['container'] === 'active') return true;
        if (isset($experiments['elementor_container']) && $experiments['elementor_container'] === 'active') return true;
    }
    $single = get_option('elementor_experiment-container');
    return ($single === 'active');
}

/**
 * Elementor: Container aktivieren
 */
function asu_activate_container(): void {
    // 1) Elementor-API
    if (did_action('elementor/loaded')) {
        try {
            if (class_exists('\Elementor\Plugin') && isset(\Elementor\Plugin::$instance->experiments)) {
                \Elementor\Plugin::$instance->experiments->set_feature_state('container', 'active');
            }
            if (class_exists('\Elementor\Core\Experiments\Manager')) {
                \Elementor\Core\Experiments\Manager::get_instance()->set_feature_state('container', 'active');
            }
        } catch (\Throwable $e) {
            // Fallback unten
        }
    }

    // 2) Fallback: Optionen direkt setzen
    $experiments = get_option('elementor_experimentation', []);
    if (!is_array($experiments)) { $experiments = []; }
    $experiments['container'] = 'active';
    $experiments['elementor_container'] = 'active';
    update_option('elementor_experimentation', $experiments);
    update_option('elementor_experiment-container', 'active');
}

/**
 * Container automatisch aktivieren (wenn pending)
 */
function asu_try_set_container() {
    if (get_option(ASU_CONTAINER_PENDING) && !get_option(ASU_CONTAINER_OK)) {
        asu_activate_container();
        if (asu_is_container_active()) {
            update_option(ASU_CONTAINER_OK, 1);
            delete_option(ASU_CONTAINER_PENDING);
        }
    }
}
add_action('elementor/init', 'asu_try_set_container', 999);
add_action('plugins_loaded', 'asu_try_set_container', 999);
add_action('admin_init', 'asu_try_set_container', 1);
add_action('init', 'asu_try_set_container', 1);

/**
 * Admin-Notice nach erfolgreichem Setup
 */
add_action('admin_notices', function () {
    if (current_user_can('manage_options') && get_option(ASU_CONTAINER_OK)) {
        echo '<div class="notice notice-success is-dismissible">
                <p><strong>Auto Setup:</strong> Setup abgeschlossen – Container ist aktiv, Permalinks und Startseite gesetzt.</p>
              </div>';
        delete_option(ASU_CONTAINER_OK);
    }
});

/**
 * Prüfen ob Plugin installiert ist
 */
function asu_is_plugin_installed($slug) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (strpos($plugin_file, $slug . '/') === 0) {
            return true;
        }
    }
    return false;
}

/**
 * Plugin-Datei finden
 */
function asu_get_plugin_file($slug) {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (strpos($plugin_file, $slug . '/') === 0) {
            return $plugin_file;
        }
    }
    return null;
}

