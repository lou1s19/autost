<?php
/**
 * Alt-Text Manager für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

// Konstanten für Alt-Text-Einstellungen
define('ASU_ALTTEXT_ENABLED', 'asu_alttext_enabled');
define('ASU_ALTTEXT_MODE', 'asu_alttext_mode');
define('ASU_ALTTEXT_PREFIX', 'asu_alttext_prefix');
define('ASU_ALTTEXT_SUFFIX', 'asu_alttext_suffix');
define('ASU_ALTTEXT_USE_TITLE', 'asu_alttext_use_title');
define('ASU_ALTTEXT_USE_CAPTION', 'asu_alttext_use_caption');
define('ASU_ALTTEXT_PAGE_MAPPING', 'asu_alttext_page_mapping');
define('ASU_ALTTEXT_APPLY_TO', 'asu_alttext_apply_to');

/**
 * Admin-Menü für Alt-Text hinzufügen
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'auto-setup',
        'Alt-Text Manager',
        'Alt-Text Manager',
        'manage_options',
        'auto-setup-alttext',
        'asu_render_alttext_page'
    );
});

/**
 * Alt-Text Admin-Seite rendern
 */
function asu_render_alttext_page() {
    // Einstellungen speichern
    if (isset($_POST['asu_alttext_save']) && check_admin_referer('asu_alttext_save')) {
        update_option(ASU_ALTTEXT_ENABLED, isset($_POST['asu_alttext_enabled']) ? 1 : 0);
        update_option(ASU_ALTTEXT_MODE, sanitize_text_field($_POST['asu_alttext_mode'] ?? 'title'));
        update_option(ASU_ALTTEXT_PREFIX, sanitize_text_field($_POST['asu_alttext_prefix'] ?? ''));
        update_option(ASU_ALTTEXT_SUFFIX, sanitize_text_field($_POST['asu_alttext_suffix'] ?? ''));
        update_option(ASU_ALTTEXT_USE_TITLE, isset($_POST['asu_alttext_use_title']) ? 1 : 0);
        update_option(ASU_ALTTEXT_USE_CAPTION, isset($_POST['asu_alttext_use_caption']) ? 1 : 0);
        update_option(ASU_ALTTEXT_APPLY_TO, sanitize_text_field($_POST['asu_alttext_apply_to'] ?? 'all'));
        
        // Seiten-Mapping speichern
        if (isset($_POST['asu_page_mapping']) && is_array($_POST['asu_page_mapping'])) {
            $mapping = [];
            foreach ($_POST['asu_page_mapping'] as $page_id => $alttext) {
                $mapping[intval($page_id)] = sanitize_text_field($alttext);
            }
            update_option(ASU_ALTTEXT_PAGE_MAPPING, $mapping);
        }
        
        echo '<div class="notice notice-success"><p>Einstellungen gespeichert!</p></div>';
    }
    
    $enabled = get_option(ASU_ALTTEXT_ENABLED, 0);
    $mode = get_option(ASU_ALTTEXT_MODE, 'title');
    $prefix = get_option(ASU_ALTTEXT_PREFIX, '');
    $suffix = get_option(ASU_ALTTEXT_SUFFIX, '');
    $use_title = get_option(ASU_ALTTEXT_USE_TITLE, 1);
    $use_caption = get_option(ASU_ALTTEXT_USE_CAPTION, 0);
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    $page_mapping = get_option(ASU_ALTTEXT_PAGE_MAPPING, []);
    
    // Alle Seiten holen
    $pages = get_pages(['sort_column' => 'post_title']);
    
    include ASU_PLUGIN_DIR . 'includes/asu-alttext-page.php';
}

/**
 * Alt-Text basierend auf Einstellungen generieren
 */
function asu_generate_alttext($attachment_id, $post_id = null) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return false;
    }
    
    $attachment = get_post($attachment_id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return false;
    }
    
    $mode = get_option(ASU_ALTTEXT_MODE, 'title');
    $alttext = '';
    
    // Modus 1: Seitenbasiert
    if ($mode === 'page' && $post_id) {
        $page_mapping = get_option(ASU_ALTTEXT_PAGE_MAPPING, []);
        if (isset($page_mapping[$post_id]) && !empty($page_mapping[$post_id])) {
            $alttext = $page_mapping[$post_id];
        } else {
            // Fallback: Seitentitel verwenden
            $page = get_post($post_id);
            if ($page) {
                $alttext = 'Bild auf ' . $page->post_title;
            }
        }
    }
    // Modus 2: Titel des Bildes verwenden
    elseif ($mode === 'title') {
        $title = $attachment->post_title;
        if (empty($title)) {
            $title = pathinfo(get_attached_file($attachment_id), PATHINFO_FILENAME);
        }
        
        $prefix = get_option(ASU_ALTTEXT_PREFIX, '');
        $suffix = get_option(ASU_ALTTEXT_SUFFIX, '');
        
        $alttext = trim($prefix . ' ' . $title . ' ' . $suffix);
    }
    // Modus 3: Titel + Seitenkontext
    elseif ($mode === 'title_page') {
        $title = $attachment->post_title;
        if (empty($title)) {
            $title = pathinfo(get_attached_file($attachment_id), PATHINFO_FILENAME);
        }
        
        $prefix = get_option(ASU_ALTTEXT_PREFIX, '');
        $suffix = get_option(ASU_ALTTEXT_SUFFIX, '');
        
        $page_text = '';
        if ($post_id) {
            $page = get_post($post_id);
            if ($page) {
                $page_text = ' auf ' . $page->post_title;
            }
        }
        
        $alttext = trim($prefix . ' ' . $title . $page_text . ' ' . $suffix);
    }
    
    // Alt-Text setzen
    if (!empty($alttext)) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alttext);
        
        // Optional: Auch als Caption setzen
        if (get_option(ASU_ALTTEXT_USE_CAPTION, 0)) {
            wp_update_post([
                'ID' => $attachment_id,
                'post_excerpt' => $alttext
            ]);
        }
        
        // Optional: Auch als Titel setzen
        if (get_option(ASU_ALTTEXT_USE_TITLE, 0)) {
            wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $alttext
            ]);
        }
        
        return $alttext;
    }
    
    return false;
}

/**
 * Automatisch Alt-Text bei Bild-Upload hinzufügen
 */
add_action('add_attachment', function($attachment_id) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return;
    }
    
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    
    // Prüfen ob Bild bereits Alt-Text hat
    $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    if (!empty($existing_alt) && $apply_to === 'empty_only') {
        return;
    }
    
    // Alt-Text generieren (ohne Post-ID, da Upload)
    asu_generate_alttext($attachment_id);
});

/**
 * Alt-Text bei Bild-Update aktualisieren
 */
add_action('edit_attachment', function($attachment_id) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return;
    }
    
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    
    // Prüfen ob Bild bereits Alt-Text hat
    $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    if (!empty($existing_alt) && $apply_to === 'empty_only') {
        return;
    }
    
    // Alt-Text generieren
    asu_generate_alttext($attachment_id);
});

/**
 * Alt-Text bei Verwendung in Post/Page hinzufügen
 */
add_action('wp_insert_post', function($post_id, $post) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Bilder aus Post-Inhalt extrahieren
    $content = $post->post_content;
    $attachment_ids = [];
    
    // WordPress-Klassen: wp-image-123
    preg_match_all('/wp-image-(\d+)/', $content, $matches);
    if (!empty($matches[1])) {
        $attachment_ids = array_merge($attachment_ids, $matches[1]);
    }
    
    // Attachment IDs in Shortcodes: [gallery ids="1,2,3"]
    preg_match_all('/ids=["\']([^"\']+)["\']/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $ids_string) {
            $ids = explode(',', $ids_string);
            $attachment_ids = array_merge($attachment_ids, array_map('trim', $ids));
        }
    }
    
    // img-Tags mit data-id oder class
    preg_match_all('/data-id=["\'](\d+)["\']/', $content, $matches);
    if (!empty($matches[1])) {
        $attachment_ids = array_merge($attachment_ids, $matches[1]);
    }
    
    // Eindeutige IDs
    $attachment_ids = array_unique(array_map('intval', $attachment_ids));
    
    if (!empty($attachment_ids)) {
        foreach ($attachment_ids as $attachment_id) {
            if (!$attachment_id) continue;
            
            $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
            $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            
            if ($apply_to === 'all' || ($apply_to === 'empty_only' && empty($existing_alt))) {
                asu_generate_alttext($attachment_id, $post_id);
            }
        }
    }
}, 10, 2);

/**
 * AJAX: Bulk Alt-Text für alle Bilder
 */
add_action('wp_ajax_asu_bulk_alttext', function() {
    check_ajax_referer('asu_bulk_alttext', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'post_status' => 'inherit'
    ];
    
    $images = get_posts($args);
    $processed = 0;
    $errors = 0;
    
    foreach ($images as $image) {
        $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
        $existing_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
        
        if ($apply_to === 'all' || ($apply_to === 'empty_only' && empty($existing_alt))) {
            // Versuche Post-ID zu finden, wo Bild verwendet wird
            $post_id = null;
            
            // 1. Prüfe Parent (Upload-Kontext)
            $parent_id = $image->post_parent;
            if ($parent_id) {
                $parent = get_post($parent_id);
                if ($parent && in_array($parent->post_type, ['post', 'page'])) {
                    $post_id = $parent_id;
                }
            }
            
            // 2. Suche in Posts/Pages nach Verwendung
            if (!$post_id) {
                $posts = get_posts([
                    'post_type' => ['post', 'page'],
                    'posts_per_page' => 50,
                    's' => 'wp-image-' . $image->ID
                ]);
                if (!empty($posts)) {
                    $post_id = $posts[0]->ID;
                }
            }
            
            if (asu_generate_alttext($image->ID, $post_id)) {
                $processed++;
            } else {
                $errors++;
            }
        }
    }
    
    wp_send_json_success([
        'message' => "✅ {$processed} Bilder verarbeitet" . ($errors > 0 ? ", {$errors} Fehler" : "")
    ]);
});

