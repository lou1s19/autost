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
define('ASU_ALTTEXT_USE_DESCRIPTION', 'asu_alttext_use_description');
define('ASU_ALTTEXT_PAGE_MAPPING', 'asu_alttext_page_mapping');
define('ASU_ALTTEXT_APPLY_TO', 'asu_alttext_apply_to');
define('ASU_ALTTEXT_SKIP_EXISTING', 'asu_alttext_skip_existing');

/**
 * Admin-Menü für Alt-Text hinzufügen
 */
add_action('admin_menu', function() {
    // Prüfe ob Alt-Text Manager aktiviert ist
    // WICHTIG: Konstante könnte noch nicht definiert sein, daher Fallback
    $enabled = true;
    if (defined('ASU_ENABLE_ALTTEXT')) {
        $enabled = get_option(ASU_ENABLE_ALTTEXT, 1);
    } else {
        $enabled = get_option('asu_enable_alttext', 1);
    }
    
    if (!$enabled) {
        return;
    }
    
    add_submenu_page(
        'auto-setup',
        'Alt-Text Manager',
        'Alt-Text Manager',
        'manage_options',
        'auto-setup-alttext',
        'asu_render_alttext_page',
        1  // Position: ganz oben
    );
}, 10);  // Spätere Priorität, damit Hauptmenü zuerst existiert

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
        update_option(ASU_ALTTEXT_USE_DESCRIPTION, isset($_POST['asu_alttext_use_description']) ? 1 : 0);
        update_option(ASU_ALTTEXT_APPLY_TO, sanitize_text_field($_POST['asu_alttext_apply_to'] ?? 'all'));
        update_option(ASU_ALTTEXT_SKIP_EXISTING, isset($_POST['asu_alttext_skip_existing']) ? 1 : 0);
        
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
    $use_title = get_option(ASU_ALTTEXT_USE_TITLE, 0); // Standard: AUS
    $use_caption = get_option(ASU_ALTTEXT_USE_CAPTION, 0);
    $use_description = get_option(ASU_ALTTEXT_USE_DESCRIPTION, 1); // Standard: AN
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    $skip_existing = get_option(ASU_ALTTEXT_SKIP_EXISTING, 1); // Standard: aktiviert
    $page_mapping = get_option(ASU_ALTTEXT_PAGE_MAPPING, []);
    
    // Alle Seiten holen
    $pages = get_pages(['sort_column' => 'post_title']);
    
    include ASU_PLUGIN_DIR . 'includes/asu-alttext-page.php';
}

/**
 * Prüft ob ein Bild bereits Alt-Text, Caption oder Beschreibung hat
 */
function asu_has_existing_content($attachment_id) {
    $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    $attachment = get_post($attachment_id);
    $existing_caption = $attachment ? $attachment->post_excerpt : '';
    $existing_description = $attachment ? $attachment->post_content : '';
    
    return !empty($existing_alt) || !empty($existing_caption) || !empty($existing_description);
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
    
    // Prüfe ob bestehende Alt-Texte/Captions überschrieben werden sollen
    $skip_existing = get_option(ASU_ALTTEXT_SKIP_EXISTING, 1);
    if ($skip_existing && asu_has_existing_content($attachment_id)) {
        return false; // Überspringe, wenn bereits Inhalt vorhanden ist
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
            $file_path = get_attached_file($attachment_id);
            if ($file_path) {
                $title = pathinfo($file_path, PATHINFO_FILENAME);
            }
            // Fallback: Dateiname aus URL
            if (empty($title)) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $title = pathinfo(basename($url), PATHINFO_FILENAME);
                }
            }
            // Letzter Fallback
            if (empty($title)) {
                $title = 'Bild ' . $attachment_id;
            }
        }
        
        $prefix = get_option(ASU_ALTTEXT_PREFIX, '');
        $suffix = get_option(ASU_ALTTEXT_SUFFIX, '');
        
        // Zusammensetzen ohne doppelte Leerzeichen
        $parts = array_filter([$prefix, $title, $suffix]);
        $alttext = trim(implode(' ', $parts));
    }
    // Modus 3: Titel + Seitenkontext
    elseif ($mode === 'title_page') {
        $title = $attachment->post_title;
        if (empty($title)) {
            $file_path = get_attached_file($attachment_id);
            if ($file_path) {
                $title = pathinfo($file_path, PATHINFO_FILENAME);
            }
            // Fallback: Dateiname aus URL
            if (empty($title)) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $title = pathinfo(basename($url), PATHINFO_FILENAME);
                }
            }
            // Letzter Fallback
            if (empty($title)) {
                $title = 'Bild ' . $attachment_id;
            }
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
        
        // Zusammensetzen ohne doppelte Leerzeichen
        $parts = array_filter([$prefix, $title . $page_text, $suffix]);
        $alttext = trim(implode(' ', $parts));
    }
    
    // Alt-Text setzen
    if (!empty($alttext)) {
        // Alt-Text in WordPress-Metadaten speichern
        $result = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alttext);
        
        // Falls update_post_meta false zurückgibt, versuche es mit add_post_meta
        if ($result === false) {
            $existing = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if ($existing === '') {
                add_post_meta($attachment_id, '_wp_attachment_image_alt', $alttext, true);
            }
        }
        
        // Optional: Auch als Caption setzen
        if (get_option(ASU_ALTTEXT_USE_CAPTION, 0)) {
            $result = wp_update_post([
                'ID' => $attachment_id,
                'post_excerpt' => $alttext
            ], true);
            if (is_wp_error($result)) {
                error_log('Auto Setup Alt-Text: Fehler beim Setzen der Caption für Attachment ' . $attachment_id . ': ' . $result->get_error_message());
            }
        }
        
        // Optional: Auch als Titel setzen
        if (get_option(ASU_ALTTEXT_USE_TITLE, 0)) {
            $result = wp_update_post([
                'ID' => $attachment_id,
                'post_title' => $alttext
            ], true);
            if (is_wp_error($result)) {
                error_log('Auto Setup Alt-Text: Fehler beim Setzen des Titels für Attachment ' . $attachment_id . ': ' . $result->get_error_message());
            }
        }
        
        // Optional: Auch als Beschreibung setzen
        if (get_option(ASU_ALTTEXT_USE_DESCRIPTION, 0)) {
            $result = wp_update_post([
                'ID' => $attachment_id,
                'post_content' => $alttext
            ], true);
            if (is_wp_error($result)) {
                error_log('Auto Setup Alt-Text: Fehler beim Setzen der Beschreibung für Attachment ' . $attachment_id . ': ' . $result->get_error_message());
            }
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
    
    // Prüfen ob es ein Bild ist
    $mime_type = get_post_mime_type($attachment_id);
    if (strpos($mime_type, 'image/') !== 0) {
        return;
    }
    
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    $skip_existing = get_option(ASU_ALTTEXT_SKIP_EXISTING, 1);
    
    // Prüfen ob Bild bereits Alt-Text hat (für apply_to Option)
    if ($apply_to === 'empty_only') {
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }
    }
    
    // skip_existing wird in asu_generate_alttext() geprüft
    
    // Prüfe Parent (Upload-Kontext)
    $parent_id = wp_get_post_parent_id($attachment_id);
    
    // Alt-Text generieren
    asu_generate_alttext($attachment_id, $parent_id);
}, 20); // Priority 20, damit andere Plugins zuerst laufen können

/**
 * Alt-Text bei Bild-Update aktualisieren
 */
add_action('edit_attachment', function($attachment_id) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return;
    }
    
    // Prüfen ob es ein Bild ist
    $mime_type = get_post_mime_type($attachment_id);
    if (strpos($mime_type, 'image/') !== 0) {
        return;
    }
    
    $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
    
    // Prüfen ob Bild bereits Alt-Text hat (für apply_to Option)
    if ($apply_to === 'empty_only') {
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }
    }
    
    // skip_existing wird in asu_generate_alttext() geprüft
    
    // Prüfe Parent
    $parent_id = wp_get_post_parent_id($attachment_id);
    
    // Alt-Text generieren
    asu_generate_alttext($attachment_id, $parent_id);
}, 20);

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
        $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
        
        foreach ($attachment_ids as $attachment_id) {
            if (!$attachment_id) continue;
            
            // Prüfen ob Bild bereits Alt-Text hat (für apply_to Option)
            if ($apply_to === 'empty_only') {
                $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                if (!empty($existing_alt)) {
                    continue;
                }
            }
            
            // skip_existing wird in asu_generate_alttext() geprüft
            asu_generate_alttext($attachment_id, $post_id);
        }
    }
}, 10, 2);

/**
 * Alt-Text für Elementor-Seiten (wenn Elementor aktiv ist)
 * Funktion zum Extrahieren von Bild-IDs aus Elementor-Daten
 */
function asu_extract_elementor_images($post_id) {
    if (!class_exists('\Elementor\Plugin')) {
        return [];
    }
    
    // Elementor-Daten holen
    $elementor_data = get_post_meta($post_id, '_elementor_data', true);
    if (empty($elementor_data)) {
        return [];
    }
    
    // JSON dekodieren
    $data = json_decode($elementor_data, true);
    if (!is_array($data)) {
        return [];
    }
    
    // Rekursiv nach Bildern suchen
    $attachment_ids = [];
    $find_images = function($elements) use (&$find_images, &$attachment_ids) {
        foreach ($elements as $element) {
            // Image Widget
            if (isset($element['widgetType']) && $element['widgetType'] === 'image') {
                if (isset($element['settings']['image']['id'])) {
                    $attachment_ids[] = intval($element['settings']['image']['id']);
                }
            }
            // Gallery Widget
            if (isset($element['settings']['gallery']) && is_array($element['settings']['gallery'])) {
                foreach ($element['settings']['gallery'] as $gallery_item) {
                    if (isset($gallery_item['id'])) {
                        $attachment_ids[] = intval($gallery_item['id']);
                    }
                }
            }
            // Image Box Widget
            if (isset($element['widgetType']) && $element['widgetType'] === 'image-box') {
                if (isset($element['settings']['image']['id'])) {
                    $attachment_ids[] = intval($element['settings']['image']['id']);
                }
            }
            // Media Carousel
            if (isset($element['widgetType']) && $element['widgetType'] === 'media-carousel') {
                if (isset($element['settings']['slides']) && is_array($element['settings']['slides'])) {
                    foreach ($element['settings']['slides'] as $slide) {
                        if (isset($slide['image']['id'])) {
                            $attachment_ids[] = intval($slide['image']['id']);
                        }
                    }
                }
            }
            // Rekursiv durch Elemente gehen
            if (isset($element['elements']) && is_array($element['elements'])) {
                $find_images($element['elements']);
            }
        }
    };
    
    $find_images($data);
    
    return array_unique($attachment_ids);
}

/**
 * Alt-Text für Elementor-Seiten nach dem Speichern
 */
add_action('elementor/document/after_save', function($document) {
    if (!get_option(ASU_ALTTEXT_ENABLED, 0)) {
        return;
    }
    
    $post_id = $document->get_main_id();
    if (!$post_id) {
        return;
    }
    
    $attachment_ids = asu_extract_elementor_images($post_id);
    
    if (!empty($attachment_ids)) {
        $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
        
        foreach ($attachment_ids as $attachment_id) {
            if (!$attachment_id) continue;
            
            // Prüfen ob Bild bereits Alt-Text hat (für apply_to Option)
            if ($apply_to === 'empty_only') {
                $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                if (!empty($existing_alt)) {
                    continue;
                }
            }
            
            // skip_existing wird in asu_generate_alttext() geprüft
            asu_generate_alttext($attachment_id, $post_id);
        }
    }
}, 10, 1);

/**
 * AJAX: Bulk Alt-Text - Gesamtanzahl der Bilder holen
 */
add_action('wp_ajax_asu_bulk_alttext_count', function() {
    check_ajax_referer('asu_bulk_alttext', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'post_status' => 'inherit',
        'fields' => 'ids'
    ];
    
    $images = get_posts($args);
    $total = count($images);
    
    wp_send_json_success(['total' => $total]);
});

/**
 * AJAX: Bulk Alt-Text für Bilder (Chunk-basiert)
 */
add_action('wp_ajax_asu_bulk_alttext', function() {
    check_ajax_referer('asu_bulk_alttext', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $chunk_size = 20; // 20 Bilder pro Request
    
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'posts_per_page' => $chunk_size,
        'offset' => $offset,
        'post_status' => 'inherit',
        'orderby' => 'ID',
        'order' => 'ASC'
    ];
    
    $images = get_posts($args);
    $processed = 0;
    $errors = 0;
    $skipped = 0;
    
    foreach ($images as $image) {
        $apply_to = get_option(ASU_ALTTEXT_APPLY_TO, 'all');
        $skip_existing = get_option(ASU_ALTTEXT_SKIP_EXISTING, 1);
        
        // Prüfen ob Bild bereits Alt-Text hat (für apply_to Option)
        if ($apply_to === 'empty_only') {
            $existing_alt = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
            if (!empty($existing_alt)) {
                $skipped++;
                continue;
            }
        }
        
        // Prüfen ob skip_existing aktiviert ist und bereits Inhalt vorhanden ist
        if ($skip_existing && asu_has_existing_content($image->ID)) {
            $skipped++;
            continue;
        }
        
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
        
        // 2. Suche in Posts/Pages nach Verwendung (WordPress) - nur wenn nötig
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
        
        // 3. Suche in Elementor-Seiten - nur wenn nötig und nicht zu viele Bilder
        if (!$post_id && class_exists('\Elementor\Plugin') && $offset < 100) {
            $all_posts = get_posts([
                'post_type' => ['post', 'page'],
                'posts_per_page' => 100,
                'meta_query' => [
                    [
                        'key' => '_elementor_data',
                        'compare' => 'EXISTS'
                    ]
                ]
            ]);
            
            foreach ($all_posts as $post) {
                $elementor_images = asu_extract_elementor_images($post->ID);
                if (in_array($image->ID, $elementor_images)) {
                    $post_id = $post->ID;
                    break;
                }
            }
        }
        
        $result = asu_generate_alttext($image->ID, $post_id);
        if ($result !== false && !empty($result)) {
            $processed++;
        } else {
            // Nur als Fehler zählen, wenn wirklich ein Fehler aufgetreten ist
            // (nicht wenn skip_existing aktiviert ist)
            if (!$skip_existing || !asu_has_existing_content($image->ID)) {
                $errors++;
            } else {
                $skipped++;
            }
        }
    }
    
    $has_more = count($images) === $chunk_size;
    $next_offset = $has_more ? $offset + $chunk_size : null;
    
    wp_send_json_success([
        'processed' => $processed,
        'errors' => $errors,
        'skipped' => $skipped,
        'has_more' => $has_more,
        'next_offset' => $next_offset,
        'current_offset' => $offset
    ]);
});

