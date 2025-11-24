<?php
/**
 * Seiten-Duplikations-Funktion für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

// Admin-Styles und Scripts laden
if (file_exists(ASU_PLUGIN_DIR . 'includes/asu-duplicate-admin.php')) {
    require_once ASU_PLUGIN_DIR . 'includes/asu-duplicate-admin.php';
}

/**
 * Duplizieren-Button in Seiten-Liste hinzufügen
 */
add_filter('page_row_actions', 'asu_add_duplicate_link', 10, 2);
add_filter('post_row_actions', 'asu_add_duplicate_link', 10, 2);

function asu_add_duplicate_link($actions, $post) {
    // Nur für Seiten und Beiträge
    if (!in_array($post->post_type, ['page', 'post'])) {
        return $actions;
    }
    
    // Nur für Benutzer mit Berechtigung
    if (!current_user_can('edit_posts')) {
        return $actions;
    }
    
    $duplicate_url = wp_nonce_url(
        admin_url('admin.php?action=asu_duplicate_post&post=' . $post->ID),
        'asu_duplicate_' . $post->ID,
        'asu_duplicate_nonce'
    );
    
    $actions['asu_duplicate'] = '<a href="' . esc_url($duplicate_url) . '" title="' . esc_attr__('Seite/Beitrag duplizieren', 'auto-setup') . '">' . __('Duplizieren', 'auto-setup') . '</a>';
    
    return $actions;
}

/**
 * Bulk-Duplikations-Action hinzufügen
 */
add_filter('bulk_actions-edit-page', 'asu_add_bulk_duplicate');
add_filter('bulk_actions-edit-post', 'asu_add_bulk_duplicate');

function asu_add_bulk_duplicate($actions) {
    $actions['asu_duplicate'] = __('Duplizieren', 'auto-setup');
    return $actions;
}

/**
 * Bulk-Duplikation verarbeiten
 */
add_filter('handle_bulk_actions-edit-page', 'asu_bulk_duplicate_handler', 10, 3);
add_filter('handle_bulk_actions-edit-post', 'asu_bulk_duplicate_handler', 10, 3);

function asu_bulk_duplicate_handler($redirect_to, $action, $post_ids) {
    if ($action !== 'asu_duplicate') {
        return $redirect_to;
    }
    
    if (!current_user_can('edit_posts')) {
        return $redirect_to;
    }
    
    $duplicated = 0;
    foreach ($post_ids as $post_id) {
        $new_id = asu_duplicate_post($post_id);
        if ($new_id && !is_wp_error($new_id)) {
            $duplicated++;
        }
    }
    
    $redirect_to = add_query_arg('asu_duplicated', $duplicated, $redirect_to);
    return $redirect_to;
}

/**
 * Admin-Notice für erfolgreiche Duplikation
 */
add_action('admin_notices', function() {
    if (isset($_GET['asu_duplicated']) && intval($_GET['asu_duplicated']) > 0) {
        $count = intval($_GET['asu_duplicated']);
        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(_n('%d Seite/Beitrag wurde dupliziert.', '%d Seiten/Beiträge wurden dupliziert.', $count, 'auto-setup'), $count);
        echo '</p></div>';
    }
    
    if (isset($_GET['asu_duplicate_success']) && $_GET['asu_duplicate_success'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>';
        _e('Seite/Beitrag wurde erfolgreich dupliziert.', 'auto-setup');
        echo '</p></div>';
    }
});

/**
 * Duplikations-Handler für einzelne Seiten/Beiträge
 */
add_action('admin_action_asu_duplicate_post', function() {
    if (!isset($_GET['post']) || !isset($_GET['asu_duplicate_nonce'])) {
        wp_die(__('Ungültige Anfrage.', 'auto-setup'));
    }
    
    $post_id = intval($_GET['post']);
    
    if (!wp_verify_nonce($_GET['asu_duplicate_nonce'], 'asu_duplicate_' . $post_id)) {
        wp_die(__('Sicherheitsprüfung fehlgeschlagen.', 'auto-setup'));
    }
    
    if (!current_user_can('edit_posts')) {
        wp_die(__('Keine Berechtigung.', 'auto-setup'));
    }
    
    $new_id = asu_duplicate_post($post_id);
    
    if ($new_id && !is_wp_error($new_id)) {
        $post_type = get_post_type($post_id);
        $redirect_url = admin_url('edit.php?post_type=' . $post_type . '&asu_duplicate_success=1');
        wp_redirect($redirect_url);
        exit;
    } else {
        wp_die(__('Fehler beim Duplizieren.', 'auto-setup'));
    }
});

/**
 * Hauptfunktion zum Duplizieren einer Seite/Beitrags
 */
function asu_duplicate_post($post_id, $status = 'draft') {
    $post = get_post($post_id);
    
    if (!$post) {
        return false;
    }
    
    // Neue Seite/Beitrag erstellen
    $new_post_data = [
        'post_title'     => $post->post_title . ' (Kopie)',
        'post_content'   => $post->post_content,
        'post_excerpt'   => $post->post_excerpt,
        'post_status'    => $status,
        'post_type'      => $post->post_type,
        'post_author'    => get_current_user_id(),
        'post_parent'    => $post->post_parent,
        'menu_order'     => $post->menu_order,
        'post_password'  => $post->post_password,
        'post_name'      => $post->post_name . '-kopie',
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
    ];
    
    $new_post_id = wp_insert_post($new_post_data);
    
    if (is_wp_error($new_post_id)) {
        return $new_post_id;
    }
    
    // Taxonomien kopieren
    $taxonomies = get_object_taxonomies($post->post_type);
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'slugs']);
        if (!empty($terms) && !is_wp_error($terms)) {
            wp_set_object_terms($new_post_id, $terms, $taxonomy);
        }
    }
    
    // Meta-Daten kopieren
    $meta_data = get_post_meta($post_id);
    foreach ($meta_data as $key => $values) {
        // Überspringe bestimmte Meta-Keys, die nicht kopiert werden sollen
        if (in_array($key, ['_edit_lock', '_edit_last', '_wp_old_slug'])) {
            continue;
        }
        
        foreach ($values as $value) {
            $value = maybe_unserialize($value);
            add_post_meta($new_post_id, $key, $value);
        }
    }
    
    // Featured Image kopieren
    $thumbnail_id = get_post_thumbnail_id($post_id);
    if ($thumbnail_id) {
        set_post_thumbnail($new_post_id, $thumbnail_id);
    }
    
    // Elementor-Daten kopieren (falls vorhanden)
    $elementor_data = get_post_meta($post_id, '_elementor_data', true);
    if ($elementor_data) {
        update_post_meta($new_post_id, '_elementor_data', $elementor_data);
        update_post_meta($new_post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($new_post_id, '_elementor_template_type', get_post_meta($post_id, '_elementor_template_type', true));
        update_post_meta($new_post_id, '_elementor_version', get_post_meta($post_id, '_elementor_version', true));
        update_post_meta($new_post_id, '_elementor_pro_version', get_post_meta($post_id, '_elementor_pro_version', true));
        update_post_meta($new_post_id, '_elementor_css', get_post_meta($post_id, '_elementor_css', true));
    }
    
    // Page Template kopieren
    $page_template = get_post_meta($post_id, '_wp_page_template', true);
    if ($page_template) {
        update_post_meta($new_post_id, '_wp_page_template', $page_template);
    }
    
    // Yoast SEO-Daten kopieren (falls vorhanden)
    $yoast_wpseo_focuskw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
    if ($yoast_wpseo_focuskw) {
        update_post_meta($new_post_id, '_yoast_wpseo_focuskw', $yoast_wpseo_focuskw);
        update_post_meta($new_post_id, '_yoast_wpseo_metadesc', get_post_meta($post_id, '_yoast_wpseo_metadesc', true));
        update_post_meta($new_post_id, '_yoast_wpseo_title', get_post_meta($post_id, '_yoast_wpseo_title', true));
        update_post_meta($new_post_id, '_yoast_wpseo_meta-robots-noindex', get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true));
        update_post_meta($new_post_id, '_yoast_wpseo_meta-robots-nofollow', get_post_meta($post_id, '_yoast_wpseo_meta-robots-nofollow', true));
        update_post_meta($new_post_id, '_yoast_wpseo_canonical', get_post_meta($post_id, '_yoast_wpseo_canonical', true));
        update_post_meta($new_post_id, '_yoast_wpseo_opengraph-title', get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true));
        update_post_meta($new_post_id, '_yoast_wpseo_opengraph-description', get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true));
        update_post_meta($new_post_id, '_yoast_wpseo_twitter-title', get_post_meta($post_id, '_yoast_wpseo_twitter-title', true));
        update_post_meta($new_post_id, '_yoast_wpseo_twitter-description', get_post_meta($post_id, '_yoast_wpseo_twitter-description', true));
    }
    
    // Rank Math SEO-Daten kopieren (falls vorhanden)
    $rank_math_title = get_post_meta($post_id, 'rank_math_title', true);
    if ($rank_math_title) {
        update_post_meta($new_post_id, 'rank_math_title', $rank_math_title);
        update_post_meta($new_post_id, 'rank_math_description', get_post_meta($post_id, 'rank_math_description', true));
        update_post_meta($new_post_id, 'rank_math_focus_keyword', get_post_meta($post_id, 'rank_math_focus_keyword', true));
        update_post_meta($new_post_id, 'rank_math_robots', get_post_meta($post_id, 'rank_math_robots', true));
    }
    
    // ACF-Felder kopieren (falls ACF aktiv ist)
    if (function_exists('get_fields') && function_exists('update_field')) {
        $fields = get_fields($post_id);
        if ($fields) {
            foreach ($fields as $field_name => $field_value) {
                update_field($field_name, $field_value, $new_post_id);
            }
        }
    }
    
    // Action Hook für weitere Anpassungen
    do_action('asu_after_duplicate_post', $new_post_id, $post_id);
    
    return $new_post_id;
}

/**
 * AJAX-Handler für schnelle Duplikation
 */
add_action('wp_ajax_asu_quick_duplicate', function() {
    check_ajax_referer('asu_quick_duplicate', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $post_id = intval($_POST['post_id'] ?? 0);
    if (!$post_id) {
        wp_send_json_error(['message' => 'Ungültige Post-ID']);
        return;
    }
    
    $new_id = asu_duplicate_post($post_id);
    
    if ($new_id && !is_wp_error($new_id)) {
        $edit_url = get_edit_post_link($new_id, 'raw');
        wp_send_json_success([
            'message' => 'Seite/Beitrag erfolgreich dupliziert!',
            'edit_url' => $edit_url,
            'new_id' => $new_id
        ]);
    } else {
        wp_send_json_error([
            'message' => 'Fehler beim Duplizieren: ' . ($new_id->get_error_message() ?? 'Unbekannter Fehler')
        ]);
    }
});

