<?php
/**
 * Admin-Seite für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Setup-Seite rendern
 * WICHTIG: Funktion muss VOR dem admin_menu Hook definiert werden!
 */
function asu_render_setup_page() {
    try {
        // Sicherstellen, dass alle benötigten Konstanten definiert sind
        if (!defined('ASU_SETUP_SHOWN')) {
            define('ASU_SETUP_SHOWN', 'asu_setup_shown');
        }
        if (!defined('ASU_PLUGIN_DIR')) {
            define('ASU_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
        }
        
        // Prüfen ob Setup bereits gezeigt wurde
        $setup_shown = get_option(ASU_SETUP_SHOWN);
        if (!$setup_shown) {
            update_option(ASU_SETUP_SHOWN, 1);
        }
        
        // Prüfe ob Auto Clean Up aktiviert ist (Konstante könnte noch nicht definiert sein)
        $cleanup_enabled = true;
        if (defined('ASU_ENABLE_CLEANUP')) {
            $cleanup_enabled = get_option(ASU_ENABLE_CLEANUP, 1);
        } else {
            // Fallback: Direkt aus der Datenbank lesen
            $cleanup_enabled = get_option('asu_enable_cleanup', 1);
        }
        
        // Sicherstellen, dass Include-Dateien existieren
        $styles_file = ASU_PLUGIN_DIR . 'includes/asu-admin-styles.php';
        $scripts_file = ASU_PLUGIN_DIR . 'includes/asu-admin-scripts.php';
        
        if (!file_exists($styles_file) || !file_exists($scripts_file)) {
            wp_die('Plugin-Dateien nicht gefunden. Bitte Plugin neu installieren.');
            return;
        }
    } catch (\Exception $e) {
        wp_die('Fehler beim Laden der Seite: ' . $e->getMessage());
        return;
    }
    ?>
    <div class="wrap asu-setup-wrapper">
        <?php 
        if (file_exists($styles_file)) {
            include $styles_file; 
        }
        ?>

        <div class="asu-header">
            <h1>🚀 Auto Clean Up</h1>
            <p>Willkommen! Richte deine WordPress-Installation schnell und einfach ein.</p>
        </div>

        <?php if (!$cleanup_enabled): ?>
        <div class="asu-warning-box">
            <h3>⚠️ Auto Clean Up ist deaktiviert</h3>
            <p>Die Auto Clean Up-Funktion ist in den <a href="<?php echo admin_url('admin.php?page=auto-setup-settings'); ?>">Einstellungen</a> deaktiviert. Bitte aktiviere sie, um diese Funktion zu nutzen.</p>
        </div>
        <?php endif; ?>

        <div class="asu-card" <?php echo !$cleanup_enabled ? 'style="opacity: 0.6;"' : ''; ?>>
            <h2>⚡ Auto Clean Up</h2>
            <p>Führt das komplette Setup automatisch durch:</p>
            
            <div class="asu-warning-box">
                <h3>⚠️ WICHTIG: Was wird gelöscht?</h3>
                <p><strong>Folgende Daten werden unwiderruflich gelöscht:</strong></p>
                <ul class="asu-warning-list">
                    <li><strong>Alle Beiträge und Seiten</strong> (publiziert, Entwurf, ausstehend, zukünftig, privat, gelöscht)</li>
                    <li><strong>Alle Themes</strong> außer Hello Elementor</li>
                    <li><strong>Plugins:</strong> Hello Dolly und Akismet</li>
                </ul>
                <p><strong>Bitte erstelle vorher ein Backup!</strong></p>
            </div>
            
            <ul class="asu-feature-list">
                <li>Löscht alle Standard-Beiträge und -Seiten</li>
                <li>Erstellt eine statische Startseite (Elementor Full Width - Gesamtbreite)</li>
                <li>Erstellt Impressum- und Datenschutz-Seiten (Elementor Full Width - Gesamtbreite)</li>
                <li>Setzt Permalinks auf /%postname%/</li>
                <li>Aktiviert Elementor Container</li>
                <li>Bereinigt Themes (nur Hello Elementor behalten)</li>
                <li>Bereinigt Plugins (Hello Dolly, Akismet entfernen)</li>
                <li>Aktiviert automatische Updates für alle Plugins</li>
            </ul>
            <button class="asu-button asu-button-danger" id="asu-auto-start" <?php echo !$cleanup_enabled ? 'disabled' : ''; ?>>
                Auto Clean Up durchführen
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            <?php if (!$cleanup_enabled): ?>
            <p style="color: #856404; margin-top: 10px;">⚠️ Auto Clean Up ist in den Einstellungen deaktiviert.</p>
            <?php endif; ?>
            <div class="asu-status" id="asu-auto-start-status"></div>
        </div>

        <div class="asu-card">
            <h2>📦 Wichtige Plugins installieren</h2>
            <p>Wähle die Plugins aus, die du installieren möchtest:</p>
            <div class="asu-plugin-list" id="asu-plugin-list">
                <?php
                $plugins = [
                    ['slug' => 'limit-login-attempts-reloaded', 'name' => 'Limit Login Attempts Reloaded', 'desc' => 'Schutz vor Brute-Force-Angriffen'],
                    ['slug' => 'elementor', 'name' => 'Elementor Website Builder', 'desc' => 'Page Builder'],
                    ['slug' => 'seo-by-rank-math', 'name' => 'Rank Math SEO', 'desc' => 'SEO-Optimierung'],
                    ['slug' => 'instant-images', 'name' => 'Instant Images - One Click Image Uploads', 'desc' => 'Bilder von Unsplash, Openverse, Pixabay und Pexels'],
                    ['slug' => 'complianz-gdpr', 'name' => 'Complianz - GDPR/CCPA Cookie Consent', 'desc' => 'DSGVO-konforme Cookie-Einwilligung'],
                ];
                foreach ($plugins as $plugin) {
                    // Sicherstellen, dass Funktionen existieren
                    $installed = false;
                    $active = false;
                    if (function_exists('asu_is_plugin_installed') && function_exists('asu_get_plugin_file')) {
                        $installed = asu_is_plugin_installed($plugin['slug']);
                        $plugin_file = asu_get_plugin_file($plugin['slug']);
                        $active = $installed && $plugin_file && function_exists('is_plugin_active') && is_plugin_active($plugin_file);
                    }
                    ?>
                    <div class="asu-plugin-item">
                        <input type="checkbox" 
                               id="plugin-<?php echo esc_attr($plugin['slug']); ?>" 
                               value="<?php echo esc_attr($plugin['slug']); ?>"
                               <?php echo $active ? 'disabled' : ''; ?>>
                        <label for="plugin-<?php echo esc_attr($plugin['slug']); ?>">
                            <?php echo esc_html($plugin['name']); ?>
                            <?php if ($active): ?>
                                <span style="color: green; margin-left: 10px;">✓ Aktiv</span>
                            <?php elseif ($installed): ?>
                                <span style="color: orange; margin-left: 10px;">Installiert</span>
                            <?php endif; ?>
                        </label>
                        <div class="asu-plugin-desc"><?php echo esc_html($plugin['desc']); ?></div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button class="asu-button secondary" id="asu-install-plugins">
                Ausgewählte Plugins installieren & aktivieren
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            <div class="asu-status" id="asu-plugins-status"></div>
            
            <div style="margin-top: 30px; padding-top: 30px; border-top: 1px solid #ddd;">
                <h3>📤 Eigene Plugins hochladen</h3>
                <p>Lade eigene Plugin-ZIP-Dateien hoch (z.B. Elementor Pro, WP Rocket):</p>
                <div class="asu-upload-area" style="border: 2px dashed #ccc; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;">
                    <input type="file" id="asu-plugin-upload" multiple accept=".zip" style="display: none;">
                    <label for="asu-plugin-upload" style="cursor: pointer; color: #2271b1; text-decoration: underline;">
                        📁 Plugin-ZIP-Dateien auswählen
                    </label>
                    <div id="asu-upload-list" style="margin-top: 15px; text-align: left;"></div>
                </div>
                <button class="asu-button secondary" id="asu-upload-install-plugins" style="display: none;">
                    Hochgeladene Plugins installieren & aktivieren
                    <span class="asu-spinner" style="display:none;"></span>
                </button>
                <div class="asu-status" id="asu-upload-status"></div>
            </div>
        </div>
    </div>

    <?php 
    if (file_exists($scripts_file)) {
        include $scripts_file; 
    }
    ?>
    <?php
}

/**
 * Admin-Menü registrieren
 * WICHTIG: Das Hauptmenü muss immer registriert werden, damit die Submenüs funktionieren!
 */
add_action('admin_menu', function() {
    // Hauptmenü IMMER zuerst registrieren (unabhängig von Einstellungen)
    // Dies muss vor allen Submenüs passieren!
    add_menu_page(
        'Auto Clean Up',
        'Auto Clean Up',
        'manage_options',
        'auto-setup',
        'asu_render_setup_page',
        'dashicons-admin-tools',
        30
    );
}, 0);  // Früheste Priorität (0), damit Hauptmenü garantiert zuerst registriert wird

