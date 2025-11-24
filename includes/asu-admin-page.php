<?php
/**
 * Admin-Seite für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin-Menü registrieren
 */
add_action('admin_menu', function() {
    add_menu_page(
        'Auto Clean Up',
        'Auto Clean Up',
        'manage_options',
        'auto-setup',
        'asu_render_setup_page',
        'dashicons-admin-tools',
        30
    );
});

/**
 * Setup-Seite rendern
 */
function asu_render_setup_page() {
    // Prüfen ob Setup bereits gezeigt wurde
    $setup_shown = get_option(ASU_SETUP_SHOWN);
    if (!$setup_shown) {
        update_option(ASU_SETUP_SHOWN, 1);
    }
    ?>
    <div class="wrap asu-setup-wrapper">
        <?php include ASU_PLUGIN_DIR . 'includes/asu-admin-styles.php'; ?>

        <div class="asu-header">
            <h1>🚀 Auto Clean Up</h1>
            <p>Willkommen! Richte deine WordPress-Installation schnell und einfach ein.</p>
        </div>

        <div class="asu-card">
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
            <button class="asu-button asu-button-danger" id="asu-auto-start">
                Auto Clean Up durchführen
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            <div class="asu-status" id="asu-auto-start-status"></div>
        </div>

        <div class="asu-card">
            <h2>📦 Wichtige Plugins installieren</h2>
            <p>Wähle die Plugins aus, die du installieren möchtest:</p>
            <div class="asu-plugin-list" id="asu-plugin-list">
                <?php
                $plugins = [
                    ['slug' => 'wordfence', 'name' => 'Wordfence Security', 'desc' => 'Firewall & Malware Scanner'],
                    ['slug' => 'yoast-seo', 'name' => 'Yoast SEO', 'desc' => 'SEO-Optimierung'],
                    ['slug' => 'contact-form-7', 'name' => 'Contact Form 7', 'desc' => 'Kontaktformulare'],
                    ['slug' => 'wp-super-cache', 'name' => 'WP Super Cache', 'desc' => 'Caching für bessere Performance'],
                    ['slug' => 'updraftplus', 'name' => 'UpdraftPlus', 'desc' => 'Backup & Wiederherstellung'],
                    ['slug' => 'really-simple-ssl', 'name' => 'Really Simple SSL', 'desc' => 'SSL-Zertifikat Management'],
                    ['slug' => 'wp-mail-smtp', 'name' => 'WP Mail SMTP', 'desc' => 'E-Mail-Versand konfigurieren'],
                    ['slug' => 'elementor', 'name' => 'Elementor', 'desc' => 'Page Builder (falls nicht installiert)'],
                ];
                foreach ($plugins as $plugin) {
                    $installed = asu_is_plugin_installed($plugin['slug']);
                    $active = $installed && is_plugin_active(asu_get_plugin_file($plugin['slug']));
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
        </div>
    </div>

    <?php include ASU_PLUGIN_DIR . 'includes/asu-admin-scripts.php'; ?>
    <?php
}

