<?php
/**
 * Einstellungen-Seite für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

// Konstanten für Einstellungen
define('ASU_DISABLE_ALL_NOTICES', 'asu_disable_all_notices');
define('ASU_ENABLE_DUPLICATE', 'asu_enable_duplicate');
define('ASU_ENABLE_CLEANUP', 'asu_enable_cleanup');
define('ASU_ENABLE_ALTTEXT', 'asu_enable_alttext');
define('ASU_ENABLE_WEBP', 'asu_enable_webp');
define('ASU_ENABLE_ELEMENTOR', 'asu_enable_elementor');

/**
 * Admin-Menü für Einstellungen hinzufügen
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'auto-setup',
        'Einstellungen',
        'Einstellungen',
        'manage_options',
        'auto-setup-settings',
        'asu_render_settings_page'
    );
});

/**
 * Einstellungen-Seite rendern
 */
function asu_render_settings_page() {
    // Einstellungen speichern
    if (isset($_POST['asu_settings_save']) && check_admin_referer('asu_settings_save')) {
        update_option(ASU_DISABLE_ALL_NOTICES, isset($_POST['asu_disable_all_notices']) ? 1 : 0);
        update_option(ASU_ENABLE_DUPLICATE, isset($_POST['asu_enable_duplicate']) ? 1 : 0);
        update_option(ASU_ENABLE_CLEANUP, isset($_POST['asu_enable_cleanup']) ? 1 : 0);
        update_option(ASU_ENABLE_ALTTEXT, isset($_POST['asu_enable_alttext']) ? 1 : 0);
        update_option(ASU_ENABLE_WEBP, isset($_POST['asu_enable_webp']) ? 1 : 0);
        update_option(ASU_ENABLE_ELEMENTOR, isset($_POST['asu_enable_elementor']) ? 1 : 0);
        
        echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
    }
    
    $disable_notices = get_option(ASU_DISABLE_ALL_NOTICES, 0);
    $enable_duplicate = get_option(ASU_ENABLE_DUPLICATE, 1); // Standard: aktiviert
    $enable_cleanup = get_option(ASU_ENABLE_CLEANUP, 1); // Standard: aktiviert
    $enable_alttext = get_option(ASU_ENABLE_ALTTEXT, 1); // Standard: aktiviert
    $enable_webp = get_option(ASU_ENABLE_WEBP, 1); // Standard: aktiviert
    $enable_elementor = get_option(ASU_ENABLE_ELEMENTOR, 1); // Standard: aktiviert
    
    ?>
    <div class="wrap asu-settings-wrapper">
        <style>
            .asu-settings-wrapper {
                max-width: 1200px;
                margin: 20px auto;
            }
            .asu-settings-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 10px;
                margin-bottom: 30px;
            }
            .asu-settings-header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
            }
            .asu-settings-card {
                background: white;
                border-radius: 10px;
                padding: 30px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .asu-settings-card h2 {
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
            .asu-checkbox-group {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .asu-checkbox-group input[type="checkbox"] {
                width: 20px;
                height: 20px;
            }
            .asu-form-group .description {
                color: #666;
                font-size: 13px;
                margin-top: 5px;
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
        </style>

        <div class="asu-settings-header">
            <h1>⚙️ Einstellungen</h1>
            <p>Generelle Einstellungen für Auto Clean Up</p>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('asu_settings_save'); ?>
            
            <div class="asu-settings-card">
                <h2>🔕 Admin-Notices</h2>
                
                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_disable_all_notices" 
                               name="asu_disable_all_notices" 
                               value="1" 
                               <?php checked($disable_notices, 1); ?>>
                        <label for="asu_disable_all_notices"><strong>Alle Admin-Notices von allen Plugins deaktivieren</strong></label>
                    </div>
                    <p class="description">
                        Wenn aktiviert, werden alle Admin-Notices (Hinweise, Warnungen, Erfolgsmeldungen) von allen Plugins im WordPress-Admin-Bereich ausgeblendet. 
                        Dies kann hilfreich sein, um eine saubere Admin-Oberfläche zu haben.
                    </p>
                </div>
            </div>

            <div class="asu-settings-card">
                <h2>⚙️ Features aktivieren/deaktivieren</h2>
                <p>Hier kannst du einzelne Features von Auto Clean Up aktivieren oder deaktivieren.</p>
                
                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_enable_duplicate" 
                               name="asu_enable_duplicate" 
                               value="1" 
                               <?php checked($enable_duplicate, 1); ?>>
                        <label for="asu_enable_duplicate"><strong>Duplizieren-Funktion aktivieren</strong></label>
                    </div>
                    <p class="description">
                        Aktiviert die Duplizieren-Funktion für Seiten und Beiträge in der WordPress-Admin-Oberfläche.
                    </p>
                </div>

                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_enable_cleanup" 
                               name="asu_enable_cleanup" 
                               value="1" 
                               <?php checked($enable_cleanup, 1); ?>>
                        <label for="asu_enable_cleanup"><strong>Auto Clean Up aktivieren</strong></label>
                    </div>
                    <p class="description">
                        Aktiviert die Auto Clean Up-Funktion (Löschen von Beiträgen/Seiten, Bereinigung von Themes/Plugins, etc.).
                    </p>
                </div>

                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_enable_alttext" 
                               name="asu_enable_alttext" 
                               value="1" 
                               <?php checked($enable_alttext, 1); ?>>
                        <label for="asu_enable_alttext"><strong>Alt-Text Manager aktivieren</strong></label>
                    </div>
                    <p class="description">
                        Aktiviert den Alt-Text Manager für automatische Alt-Texte bei Bildern.
                    </p>
                </div>

                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_enable_webp" 
                               name="asu_enable_webp" 
                               value="1" 
                               <?php checked($enable_webp, 1); ?>>
                        <label for="asu_enable_webp"><strong>WebP-Konvertierung aktivieren</strong></label>
                    </div>
                    <p class="description">
                        Aktiviert die WebP-Konvertierung für Bilder.
                    </p>
                </div>

                <div class="asu-form-group">
                    <div class="asu-checkbox-group">
                        <input type="checkbox" 
                               id="asu_enable_elementor" 
                               name="asu_enable_elementor" 
                               value="1" 
                               <?php checked($enable_elementor, 1); ?>>
                        <label for="asu_enable_elementor"><strong>Elementor-Einstellungen aktivieren</strong></label>
                    </div>
                    <p class="description">
                        Aktiviert die Elementor-Einstellungen und Container-Funktionen.
                    </p>
                </div>
            </div>

            <div class="asu-settings-card">
                <button type="submit" name="asu_settings_save" class="asu-button">
                    💾 Einstellungen speichern
                </button>
            </div>
        </form>
    </div>
    <?php
}

/**
 * Admin-Notices deaktivieren (wenn Option aktiviert)
 */
if (get_option(ASU_DISABLE_ALL_NOTICES, 0)) {
    // Alle Admin-Notices ausblenden mit hoher Priority (sehr früh)
    add_action('admin_notices', function() {
        // Entferne alle Notices von allen Plugins
        global $wp_filter;
        if (isset($wp_filter['admin_notices'])) {
            $wp_filter['admin_notices']->callbacks = [];
        }
    }, 1);
    
    // Zusätzlich: CSS um alle Notices zu verstecken (als Fallback)
    add_action('admin_head', function() {
        echo '<style>
            .notice, .notice-error, .notice-warning, .notice-info, .notice-success, .update-nag, .updated, .is-dismissible {
                display: none !important;
                visibility: hidden !important;
            }
        </style>';
    }, 999);
    
    // Auch für Network Admin (Multisite)
    add_action('network_admin_notices', function() {
        global $wp_filter;
        if (isset($wp_filter['network_admin_notices'])) {
            $wp_filter['network_admin_notices']->callbacks = [];
        }
    }, 1);
    
    // Auch für User Admin
    add_action('user_admin_notices', function() {
        global $wp_filter;
        if (isset($wp_filter['user_admin_notices'])) {
            $wp_filter['user_admin_notices']->callbacks = [];
        }
    }, 1);
    
    // Auch für All Admin Notices
    add_action('all_admin_notices', function() {
        global $wp_filter;
        if (isset($wp_filter['all_admin_notices'])) {
            $wp_filter['all_admin_notices']->callbacks = [];
        }
    }, 1);
}

