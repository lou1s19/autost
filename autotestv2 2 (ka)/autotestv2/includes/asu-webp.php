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
    // WICHTIG: Konstante könnte noch nicht definiert sein, daher Fallback
    $enabled = true;
    if (defined('ASU_ENABLE_WEBP')) {
        $enabled = get_option(ASU_ENABLE_WEBP, 1);
    } else {
        $enabled = get_option('asu_enable_webp', 1);
    }
    
    if (!$enabled) {
        return;
    }
    
    add_submenu_page(
        'auto-setup',
        'WebP Konvertierung',
        'WebP Konvertierung',
        'manage_options',
        'auto-setup-webp',
        'asu_render_webp_page',
        2  // Position: nach Alt-Text Manager
    );
}, 11);  // Spätere Priorität, damit Hauptmenü zuerst existiert

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
 * Bild zu WebP konvertieren und Original-Datei ersetzen
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
    
    // Prüfen ob bereits WebP
    $mime_type = get_post_mime_type($attachment_id);
    if ($mime_type === 'image/webp') {
        return ['success' => true, 'path' => $file_path, 'message' => 'Bereits WebP'];
    }
    
    $file_info = pathinfo($file_path);
    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
    
    // Unterstützung prüfen
    $support = asu_check_webp_support();
    
    if (!$support['webp']) {
        return new WP_Error('no_webp_support', 'WebP wird von diesem Server nicht unterstützt');
    }
    
    // Temporäre WebP-Datei erstellen
    $temp_webp = $webp_path;
    
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
            
            $imagick->writeImage($temp_webp);
            $imagick->clear();
            $imagick->destroy();
        } catch (\Throwable $e) {
            // Fallback zu GD
            if (file_exists($temp_webp)) {
                @unlink($temp_webp);
            }
        } catch (\Exception $e) {
            // Fallback zu GD
            if (file_exists($temp_webp)) {
                @unlink($temp_webp);
            }
        }
    }
    
    // GD Library verwenden (Fallback oder wenn Imagick fehlgeschlagen ist)
    if (!file_exists($temp_webp) && $support['gd']) {
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
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
        
        // WebP speichern
        $result = imagewebp($image, $temp_webp, $quality);
        imagedestroy($image);
        
        if (!$result) {
            return new WP_Error('webp_save_failed', 'WebP konnte nicht gespeichert werden');
        }
    }
    
    if (!file_exists($temp_webp)) {
        return new WP_Error('no_support', 'Keine WebP-Unterstützung gefunden');
    }
    
    // Original-Datei löschen
    @unlink($file_path);
    
    // WebP-Datei als neue Original-Datei speichern
    if (!@rename($temp_webp, $file_path)) {
        // Fallback: Kopieren und dann löschen
        @copy($temp_webp, $file_path);
        @unlink($temp_webp);
    }
    
    // WordPress über die neue Datei informieren
    update_attached_file($attachment_id, $file_path);
    
    // MIME-Type auf image/webp setzen
    wp_update_post([
        'ID' => $attachment_id,
        'post_mime_type' => 'image/webp'
    ]);
    
    // Metadaten aktualisieren
    $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $metadata);
    
    return ['success' => true, 'path' => $file_path];
}

/**
 * WebP-Version als Attachment hinzufügen (Legacy-Funktion, nicht mehr benötigt)
 * Die Datei wird jetzt direkt ersetzt, daher ist diese Funktion obsolet
 */
function asu_add_webp_attachment($attachment_id, $webp_path) {
    // Funktion ist jetzt obsolet, da die Original-Datei direkt ersetzt wird
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
    
    wp_send_json_success([
        'message' => 'Bild erfolgreich zu WebP konvertiert und ersetzt',
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
        
        // Prüfen ob bereits WebP
        $mime_type = get_post_mime_type($image->ID);
        if ($skip_existing && $mime_type === 'image/webp') {
            $skipped++;
            continue;
        }
        
        $result = asu_convert_to_webp($image->ID, 85); // Standard-Qualität
        
        if (is_wp_error($result)) {
            $errors++;
            $error_messages[] = 'ID ' . $image->ID . ': ' . $result->get_error_message();
        } else {
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
 * Filter sind nicht mehr nötig, da die Original-Datei direkt durch WebP ersetzt wird
 * Alle Referenzen verwenden automatisch die WebP-Version, da die Datei selbst WebP ist
 */

/**
 * Hinweis in Mediathek: WebP verfügbar
 */
add_filter('wp_prepare_attachment_for_js', function($response, $attachment, $meta) {
    $mime_type = get_post_mime_type($attachment->ID);
    if ($mime_type === 'image/webp') {
        $response['asu_webp'] = true;
        $response['asu_webp_message'] = '✅ Dieses Bild ist bereits als WebP konvertiert';
    } elseif (in_array($mime_type, ['image/jpeg', 'image/png', 'image/gif'])) {
        $response['asu_webp'] = false;
        $response['asu_webp_message'] = 'Kann zu WebP konvertiert werden';
    }
    return $response;
}, 10, 3);

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

