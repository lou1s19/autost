<?php
/**
 * WebP-Konvertierung für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin-Menü für WebP-Konvertierung hinzufügen
 */
add_action('admin_menu', function() {
    // Prüfe ob WebP-Konvertierung aktiviert ist
    if (!get_option(ASU_ENABLE_WEBP, 1)) {
        return;
    }
    
    add_submenu_page(
        'auto-setup',
        'WebP Konvertierung',
        'WebP Konvertierung',
        'manage_options',
        'auto-setup-webp',
        'asu_render_webp_page'
    );
});

/**
 * WebP Admin-Seite rendern
 */
function asu_render_webp_page() {
    include ASU_PLUGIN_DIR . 'includes/asu-webp-page.php';
}

/**
 * Prüfen ob WebP-Unterstützung vorhanden ist
 */
function asu_check_webp_support() {
    $support = [
        'gd' => false,
        'imagick' => false,
        'webp' => false
    ];
    
    // GD Library prüfen
    if (function_exists('gd_info')) {
        $gd_info = gd_info();
        $support['gd'] = true;
        $support['webp'] = isset($gd_info['WebP Support']) && $gd_info['WebP Support'];
    }
    
    // Imagick prüfen
    if (extension_loaded('imagick')) {
        try {
            if (class_exists('Imagick')) {
                $imagick = @new Imagick();
                if ($imagick) {
                    $formats = @$imagick->queryFormats();
                    if ($formats && is_array($formats)) {
                        $support['imagick'] = true;
                        $support['webp'] = in_array('WEBP', $formats);
                    }
                    @$imagick->clear();
                    @$imagick->destroy();
                }
            }
        } catch (\Throwable $e) {
            // Imagick nicht verfügbar
        } catch (\Exception $e) {
            // Imagick nicht verfügbar
        }
    }
    
    return $support;
}

/**
 * Bild zu WebP konvertieren (mit Transparenz-Erhaltung)
 */
function asu_convert_to_webp($attachment_id, $quality = 85) {
    // Standard-Qualität: 85 (gute Balance)
    if ($quality < 1 || $quality > 100) {
        $quality = 85;
    }
    $file_path = get_attached_file($attachment_id);
    
    if (!$file_path || !file_exists($file_path)) {
        return new WP_Error('file_not_found', 'Datei nicht gefunden');
    }
    
    $file_info = pathinfo($file_path);
    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
    
    // Prüfen ob bereits WebP existiert
    if (file_exists($webp_path)) {
        return ['success' => true, 'path' => $webp_path, 'message' => 'WebP bereits vorhanden'];
    }
    
    $mime_type = get_post_mime_type($attachment_id);
    
    // Unterstützung prüfen
    $support = asu_check_webp_support();
    
    if (!$support['webp']) {
        return new WP_Error('no_webp_support', 'WebP wird von diesem Server nicht unterstützt');
    }
    
    // Imagick verwenden (besser für Transparenz)
    if ($support['imagick'] && class_exists('Imagick')) {
        try {
            $imagick = @new Imagick($file_path);
            if (!$imagick) {
                throw new \Exception('Imagick konnte nicht initialisiert werden');
            }
            
            // Transparenz erhalten
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality($quality);
            
            // Wichtig: Alpha-Kanal erhalten
            if (defined('Imagick::ALPHACHANNEL_ACTIVATE')) {
                $imagick->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            }
            
            // Für PNG: Transparenz explizit setzen
            if ($mime_type === 'image/png') {
                $imagick->setOption('webp:lossless', 'false');
                $imagick->setOption('webp:alpha-quality', '100');
            }
            
            $imagick->writeImage($webp_path);
            $imagick->clear();
            $imagick->destroy();
            
            return ['success' => true, 'path' => $webp_path];
        } catch (\Throwable $e) {
            // Fallback zu GD
        } catch (\Exception $e) {
            // Fallback zu GD
        }
    }
    
    // GD Library verwenden (Fallback)
    if ($support['gd']) {
        $image = null;
        
        switch ($mime_type) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image = imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($file_path);
                break;
            default:
                return new WP_Error('unsupported_format', 'Nicht unterstütztes Bildformat');
        }
        
        if (!$image) {
            return new WP_Error('image_create_failed', 'Bild konnte nicht geladen werden');
        }
        
        // Transparenz erhalten (wichtig für PNG/GIF)
        if ($mime_type === 'image/png' || $mime_type === 'image/gif') {
            // Alpha-Blending deaktivieren für korrekte Transparenz
            imagealphablending($image, false);
            imagesavealpha($image, true);
            
            // Für PNG: Transparenz-Informationen beibehalten
            // Die Transparenz wird automatisch von imagecreatefrompng übernommen
            // imagewebp sollte diese dann auch beibehalten
        }
        
        // WebP speichern (Transparenz wird automatisch erhalten wenn imagesavealpha aktiv ist)
        $result = imagewebp($image, $webp_path, $quality);
        
        // Speicher freigeben
        imagedestroy($image);
        
        if (!$result) {
            return new WP_Error('webp_save_failed', 'WebP konnte nicht gespeichert werden');
        }
        
        return ['success' => true, 'path' => $webp_path];
    }
    
    return new WP_Error('no_support', 'Keine WebP-Unterstützung gefunden');
}

/**
 * WebP-Version als Attachment hinzufügen
 */
function asu_add_webp_attachment($attachment_id, $webp_path) {
    if (!file_exists($webp_path)) {
        return false;
    }
    
    // Korrekte URL generieren
    $upload_dir = wp_upload_dir();
    $webp_url = '';
    
    // Prüfe ob Datei im Upload-Verzeichnis ist
    if (strpos($webp_path, $upload_dir['basedir']) === 0) {
        $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
    } else {
        // Fallback: Standard WordPress-Methode
        $webp_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $webp_path);
    }
    
    // URL normalisieren (Backslashes zu Slashes)
    $webp_url = str_replace('\\', '/', $webp_url);
    
    // WebP als Meta hinzufügen
    update_post_meta($attachment_id, '_asu_webp_path', $webp_path);
    update_post_meta($attachment_id, '_asu_webp_url', $webp_url);
    
    return true;
}

/**
 * AJAX: Einzelnes Bild zu WebP konvertieren
 */
add_action('wp_ajax_asu_convert_single_webp', function() {
    check_ajax_referer('asu_convert_webp', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $attachment_id = intval($_POST['attachment_id'] ?? 0);
    if (!$attachment_id) {
        wp_send_json_error(['message' => 'Ungültige Attachment-ID']);
        return;
    }
    
    $quality = intval($_POST['quality'] ?? 85);
    $quality = max(1, min(100, $quality)); // Zwischen 1-100
    
    $result = asu_convert_to_webp($attachment_id, $quality);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }
    
    // WebP-Version als Meta hinzufügen
    asu_add_webp_attachment($attachment_id, $result['path']);
    
    wp_send_json_success([
        'message' => 'Bild erfolgreich zu WebP konvertiert',
        'path' => $result['path']
    ]);
});

/**
 * AJAX: WebP-Konvertierung - Gesamtanzahl der Bilder holen
 */
add_action('wp_ajax_asu_webp_count', function() {
    check_ajax_referer('asu_convert_webp', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
        'posts_per_page' => -1,
        'post_status' => 'inherit',
        'fields' => 'ids'
    ];
    
    $images = get_posts($args);
    $total = count($images);
    
    wp_send_json_success(['total' => $total]);
});

/**
 * AJAX: Alle Bilder zu WebP konvertieren (Chunk-basiert)
 */
add_action('wp_ajax_asu_convert_all_webp', function() {
    check_ajax_referer('asu_convert_webp', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $chunk_size = 10; // 10 Bilder pro Request
    
    $skip_existing = isset($_POST['skip_existing']) && $_POST['skip_existing'] === 'true';
    
    // Bilder holen (Chunk)
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
        'posts_per_page' => $chunk_size,
        'offset' => $offset,
        'post_status' => 'inherit',
        'orderby' => 'ID',
        'order' => 'ASC'
    ];
    
    $images = get_posts($args);
    $converted = 0;
    $skipped = 0;
    $errors = 0;
    $error_messages = [];
    
    foreach ($images as $image) {
        $file_path = get_attached_file($image->ID);
        if (!$file_path || !file_exists($file_path)) {
            $errors++;
            $error_messages[] = 'ID ' . $image->ID . ': Datei nicht gefunden';
            continue;
        }
        
        // Prüfen ob WebP bereits existiert
        $file_info = pathinfo($file_path);
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        
        if ($skip_existing && file_exists($webp_path)) {
            $skipped++;
            continue;
        }
        
        $result = asu_convert_to_webp($image->ID, 85); // Standard-Qualität
        
        if (is_wp_error($result)) {
            $errors++;
            $error_messages[] = 'ID ' . $image->ID . ': ' . $result->get_error_message();
        } else {
            asu_add_webp_attachment($image->ID, $result['path']);
            $converted++;
        }
    }
    
    $has_more = count($images) === $chunk_size;
    $next_offset = $has_more ? $offset + $chunk_size : null;
    
    wp_send_json_success([
        'converted' => $converted,
        'skipped' => $skipped,
        'errors' => $errors,
        'has_more' => $has_more,
        'next_offset' => $next_offset,
        'current_offset' => $offset,
        'error_messages' => array_slice($error_messages, 0, 5) // Max 5 Fehler pro Chunk
    ]);
});

/**
 * WebP-Version automatisch verwenden, wenn verfügbar
 */
add_filter('wp_get_attachment_image_src', function($image, $attachment_id, $size, $icon) {
    if (!$attachment_id) {
        return $image;
    }
    
    $webp_path = get_post_meta($attachment_id, '_asu_webp_path', true);
    if ($webp_path && file_exists($webp_path)) {
        $webp_url = get_post_meta($attachment_id, '_asu_webp_url', true);
        if ($webp_url && $image) {
            // WebP-URL verwenden
            $image[0] = $webp_url;
        }
    }
    
    return $image;
}, 10, 4);

/**
 * WebP-Version in img-Tags verwenden
 */
add_filter('the_content', function($content) {
    // Prüfe ob WebP aktiviert ist
    if (!get_option(ASU_ENABLE_WEBP, 1)) {
        return $content;
    }
    
    // Finde alle img-Tags mit src-Attribut
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
    
    if (empty($matches[0])) {
        return $content;
    }
    
    foreach ($matches[0] as $index => $img_tag) {
        $original_url = $matches[1][$index];
        
        // Prüfe ob es eine WordPress-Upload-URL ist
        $upload_dir = wp_upload_dir();
        if (strpos($original_url, $upload_dir['baseurl']) === false) {
            continue;
        }
        
        // Extrahiere Dateipfad
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $original_url);
        
        // Prüfe ob WebP-Version existiert
        $file_info = pathinfo($file_path);
        if (!isset($file_info['extension']) || !in_array(strtolower($file_info['extension']), ['jpg', 'jpeg', 'png', 'gif'])) {
            continue;
        }
        
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        
        if (file_exists($webp_path)) {
            $webp_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
            
            // Ersetze src mit WebP-Version
            $new_img_tag = str_replace($original_url, $webp_url, $img_tag);
            
            // Füge type="image/webp" hinzu (optional, für bessere Browser-Unterstützung)
            if (strpos($new_img_tag, 'type=') === false) {
                $new_img_tag = str_replace('<img', '<img type="image/webp"', $new_img_tag);
            }
            
            $content = str_replace($img_tag, $new_img_tag, $content);
        }
    }
    
    return $content;
}, 999);

/**
 * WebP-Version in srcset einbinden
 */
add_filter('wp_calculate_image_srcset', function($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    if (!$attachment_id) {
        return $sources;
    }
    
    $webp_path = get_post_meta($attachment_id, '_asu_webp_path', true);
    if ($webp_path && file_exists($webp_path)) {
        $webp_url = get_post_meta($attachment_id, '_asu_webp_url', true);
        
        if ($webp_url && is_array($sources)) {
            // Ersetze alle URLs in srcset mit WebP-Versionen
            foreach ($sources as $width => &$source) {
                if (isset($source['url'])) {
                    // Prüfe ob die URL zur gleichen Datei gehört
                    $original_path = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $source['url']);
                    $original_info = pathinfo($original_path);
                    $webp_info = pathinfo($webp_path);
                    
                    if (isset($original_info['filename']) && isset($webp_info['filename']) && 
                        $original_info['filename'] === $webp_info['filename']) {
                        $source['url'] = $webp_url;
                    }
                }
            }
        }
    }
    
    return $sources;
}, 10, 5);

/**
 * WebP-Version für wp_get_attachment_url verwenden
 */
add_filter('wp_get_attachment_url', function($url, $post_id) {
    $webp_path = get_post_meta($post_id, '_asu_webp_path', true);
    if ($webp_path && file_exists($webp_path)) {
        $webp_url = get_post_meta($post_id, '_asu_webp_url', true);
        if ($webp_url) {
            return $webp_url;
        }
    }
    return $url;
}, 10, 2);

/**
 * Hinweis in Mediathek: WebP verfügbar
 */
add_filter('attachment_fields_to_edit', function($form_fields, $post) {
    $webp_path = get_post_meta($post->ID, '_asu_webp_path', true);
    if ($webp_path && file_exists($webp_path)) {
        $webp_url = get_post_meta($post->ID, '_asu_webp_url', true);
        $form_fields['asu_webp'] = [
            'label' => 'WebP-Version',
            'input' => 'html',
            'html' => '<p style="color: green; font-weight: bold;">✅ WebP-Version verfügbar</p>' . 
                     ($webp_url ? '<p><a href="' . esc_url($webp_url) . '" target="_blank">WebP-Datei anzeigen</a></p>' : '')
        ];
    } else {
        $mime_type = get_post_mime_type($post->ID);
        if (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
            $form_fields['asu_webp'] = [
                'label' => 'WebP-Version',
                'input' => 'html',
                'html' => '<p style="color: #666;">Keine WebP-Version vorhanden</p>'
            ];
        }
    }
    return $form_fields;
}, 10, 2);

/**
 * CSS für Mediathek-Hinweis
 */
add_action('admin_head', function() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'attachment') {
        echo '<style>
            .attachment-details .asu-webp-info {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                border-radius: 5px;
                padding: 10px;
                margin: 10px 0;
            }
        </style>';
    }
});

