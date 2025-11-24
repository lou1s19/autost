<?php
/**
 * Plugin Name: Auto Setup (Cleanup von WP)
 * Description: Löscht Standard-Beiträge/Seiten, erstellt eine statische „Startseite" (Elementor Full Width), setzt Permalinks auf /%postname%/ und aktiviert den Elementor-Container. Mit modernem Setup-Menü.
 * Version: 2.0.0
 * Author: Louis
 */

if ( ! defined('ABSPATH') ) { exit; }

// Konstanten definieren
if (!defined('ASU_BASE_DONE')) {
    define('ASU_BASE_DONE',                 'asu_base_done');
}
if (!defined('ASU_CONTAINER_OK')) {
    define('ASU_CONTAINER_OK',              'asu_container_ok');
}
if (!defined('ASU_CONTAINER_PENDING')) {
    define('ASU_CONTAINER_PENDING',         'asu_container_pending');
}
if (!defined('ASU_SETUP_SHOWN')) {
    define('ASU_SETUP_SHOWN',               'asu_setup_shown');
}

// Plugin-Pfade definieren (sicher auch wenn WordPress-Funktionen noch nicht verfügbar sind)
if (!defined('ASU_PLUGIN_DIR')) {
    $plugin_dir = dirname(__FILE__);
    define('ASU_PLUGIN_DIR', $plugin_dir . '/');
}
if (!defined('ASU_PLUGIN_URL')) {
    if (function_exists('plugin_dir_url')) {
        define('ASU_PLUGIN_URL', plugin_dir_url(__FILE__));
    } elseif (function_exists('plugins_url')) {
        define('ASU_PLUGIN_URL', plugins_url('', __FILE__) . '/');
    } else {
        // Fallback - wird später überschrieben wenn WordPress geladen ist
        define('ASU_PLUGIN_URL', '');
    }
}

// Includes laden (mit Fehlerbehandlung)
$includes = [
    'includes/asu-functions.php',
    'includes/asu-admin-page.php',
    'includes/asu-ajax-handlers.php',
    'includes/asu-plugin-installer.php',
    'includes/asu-alttext.php',
    'includes/asu-duplicate.php',
    'includes/asu-auto-updates.php',
    'includes/asu-webp.php',
];

foreach ($includes as $include) {
    $file_path = ASU_PLUGIN_DIR . $include;
    if (file_exists($file_path)) {
        try {
            require_once $file_path;
        } catch (\Throwable $e) {
            // Fehler beim Laden ignorieren, damit Plugin trotzdem aktiviert werden kann
            error_log('Auto Setup: Fehler beim Laden von ' . $include . ': ' . $e->getMessage());
        }
    }
}

// Elementor-Settings erst nach plugins_loaded laden (wenn WordPress bereit ist)
if (function_exists('add_action')) {
    add_action('plugins_loaded', function() {
        $file_path = ASU_PLUGIN_DIR . 'includes/asu-elementor-settings.php';
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }, 5);
}
