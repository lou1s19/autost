<?php
/**
 * AJAX-Handler für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * AJAX: Auto-Start
 */
add_action('wp_ajax_asu_auto_start', function() {
    check_ajax_referer('asu_auto_start', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    // Prüfe ob Auto Clean Up aktiviert ist
    if (!get_option(ASU_ENABLE_CLEANUP, 1)) {
        wp_send_json_error(['message' => 'Auto Clean Up ist in den Einstellungen deaktiviert']);
        return;
    }
    
    try {
        // Flag setzen, um save_post Hooks zu überspringen (verhindert Endlosschleife)
        if (!defined('ASU_DOING_CLEANUP')) {
            define('ASU_DOING_CLEANUP', true);
        }
        
        // (a) Beiträge/Seiten entfernen
        $items = get_posts([
            'post_type'   => ['post','page'],
            'numberposts' => -1,
            'post_status' => ['publish','draft','pending','future','private','trash'],
        ]);
        foreach ($items as $it) {
            wp_delete_post($it->ID, true);
        }

        // (b) Startseite erstellen & als statische Homepage setzen
        $home_id = wp_insert_post([
            'post_title'  => 'Startseite',
            'post_status' => 'publish',
            'post_type'   => 'page',
        ]);
        if ($home_id && !is_wp_error($home_id)) {
            update_option('show_on_front', 'page');
            update_option('page_on_front', $home_id);
            
            // Elementor Edit Mode aktivieren
            update_post_meta($home_id, '_elementor_edit_mode', 'builder');
            update_post_meta($home_id, '_elementor_template_type', 'wp-page');
        }

        // (c) Permalinks = /%postname%/
        global $wp_rewrite;
        if ($wp_rewrite) {
            $wp_rewrite->set_permalink_structure('/%postname%/');
            $wp_rewrite->flush_rules();
        }

        // (d) Sichtbarkeit: Indexierung erlauben (Einstellungen -> Lesen)
        update_option('blog_public', '1');

        // (e) Themes bereinigen
        if (!function_exists('wp_get_themes')) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }
        if (!function_exists('delete_theme')) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
        }
        if (!function_exists('switch_theme')) {
            require_once ABSPATH . 'wp-includes/theme.php';
        }

        $keep_stylesheets = ['hello-elementor','hello','hello-child','hello-elementor-child'];
        $current_stylesheet = get_option('stylesheet');
        $hello_preferred = wp_get_theme('hello-elementor');
        $hello_fallback = wp_get_theme('hello');
        $hello_available_stylesheet = '';
        if ($hello_preferred && $hello_preferred->exists()) {
            $hello_available_stylesheet = 'hello-elementor';
        } elseif ($hello_fallback && $hello_fallback->exists()) {
            $hello_available_stylesheet = 'hello';
        }
        if ($hello_available_stylesheet && $current_stylesheet !== $hello_available_stylesheet) {
            switch_theme($hello_available_stylesheet);
            $current_stylesheet = $hello_available_stylesheet;
        }

        $themes = function_exists('wp_get_themes') ? wp_get_themes() : [];
        foreach ($themes as $stylesheet => $theme_obj) {
            $template = method_exists($theme_obj, 'get_template') ? $theme_obj->get_template() : '';
            if (in_array($stylesheet, $keep_stylesheets, true) || in_array($template, $keep_stylesheets, true)) {
                continue;
            }
            if ($stylesheet === $current_stylesheet) {
                continue;
            }
            try { delete_theme($stylesheet); } catch (\Throwable $e) { /* ignore */ }
        }

        // (f) Plugins bereinigen
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (!function_exists('delete_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $targets = ['hello.php', 'akismet/akismet.php'];
        $installed = function_exists('get_plugins') ? get_plugins() : [];
        foreach ($installed as $plugin_file => $data) {
            if (in_array($plugin_file, $targets, true)) {
                try { deactivate_plugins($plugin_file, true); } catch (\Throwable $e) { /* ignore */ }
                try { delete_plugins([$plugin_file]); } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // (g) Impressum und Datenschutz-Seiten erstellen (falls nicht vorhanden)
        $required_pages = [
            'impressum' => 'Impressum',
            'datenschutz' => 'Datenschutz'
        ];
        
        foreach ($required_pages as $slug => $title) {
            $existing_page = get_page_by_path($slug);
            if (!$existing_page) {
                $page_id = wp_insert_post([
                    'post_title'  => $title,
                    'post_name'   => $slug,
                    'post_status' => 'publish',
                    'post_type'   => 'page',
                ]);
                
                if ($page_id && !is_wp_error($page_id)) {
                    // Elementor Edit Mode aktivieren
                    update_post_meta($page_id, '_elementor_edit_mode', 'builder');
                    update_post_meta($page_id, '_elementor_template_type', 'wp-page');
                }
            } else {
                // Elementor Edit Mode aktivieren
                update_post_meta($existing_page->ID, '_elementor_edit_mode', 'builder');
                update_post_meta($existing_page->ID, '_elementor_template_type', 'wp-page');
            }
        }

        // (i) Automatische Plugin-Updates aktivieren
        asu_enable_auto_updates();

        // Flags setzen
        update_option(ASU_BASE_DONE, 1);
        update_option(ASU_CONTAINER_PENDING, 1);

        // Container aktivieren (wenn Elementor verfügbar)
        asu_activate_container();
        
        // Container-Padding automatisch auf 0 setzen
        if (did_action('elementor/loaded')) {
            asu_set_container_padding_zero();
        }

        wp_send_json_success([
            'message' => '✅ Auto-Start erfolgreich abgeschlossen! Die Seite wird in 2 Sekunden neu geladen...',
            'reload' => true
        ]);
    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => 'Fehler: ' . $e->getMessage()
        ]);
    }
});

