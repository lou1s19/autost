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
    
    try {
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
            // Elementor Full Width Template setzen
            update_post_meta($home_id, '_wp_page_template', 'elementor_full_width');
            // Zusätzlich für Elementor: Container-Einstellungen
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

        // (g) Index-Seite erstellen (falls nicht vorhanden) und auf Index setzen
        $index_page = get_page_by_path('index');
        if (!$index_page) {
            $index_id = wp_insert_post([
                'post_title'  => 'Index',
                'post_name'   => 'index',
                'post_status' => 'publish',
                'post_type'   => 'page',
            ]);
            if ($index_id && !is_wp_error($index_id)) {
                // Index-Seite auf Index setzen (Yoast SEO)
                update_post_meta($index_id, '_yoast_wpseo_meta-robots-noindex', '0');
            }
        } else {
            // Bestehende Index-Seite auf Index setzen
            update_post_meta($index_page->ID, '_yoast_wpseo_meta-robots-noindex', '0');
        }

        // (h) No-Index für bestimmte Seiten setzen
        $noindex_pages = ['danke', 'impressum', 'datenschutz', 'cookie', 'cookies', 'datenschutzerklaerung', 'impressum-datenschutz'];
        foreach ($noindex_pages as $page_slug) {
            $page = get_page_by_path($page_slug);
            if ($page) {
                // Yoast SEO
                update_post_meta($page->ID, '_yoast_wpseo_meta-robots-noindex', '1');
                // Rank Math (falls vorhanden)
                update_post_meta($page->ID, 'rank_math_robots', ['noindex']);
                // Allgemein
                update_post_meta($page->ID, '_meta_robots_noindex', '1');
            }
        }

        // (i) Automatische Plugin-Updates aktivieren
        asu_enable_auto_updates();

        // Flags setzen
        update_option(ASU_BASE_DONE, 1);
        update_option(ASU_CONTAINER_PENDING, 1);

        // Container aktivieren (wenn Elementor verfügbar)
        asu_activate_container();

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

