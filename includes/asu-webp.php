<?php
/**
 * WebP-Konvertierung für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin-Menü für WebP-Konvertierung hinzufügen
 */
add_action('admin_menu', function() {
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
    $file_info = pathinfo($webp_path);
    $webp_url = str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, $webp_path);
    
    // WebP als Meta hinzufügen
    update_post_meta($attachment_id, '_asu_webp_path', $webp_path);
    update_post_meta($attachment_id, '_asu_webp_url', $webp_url);
    
    // Optional: Original durch WebP ersetzen (wenn gewünscht)
    // Das machen wir hier nicht, sondern behalten beide Versionen
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
 * AJAX: Alle Bilder zu WebP konvertieren (Bulk)
 */
add_action('wp_ajax_asu_convert_all_webp', function() {
    check_ajax_referer('asu_convert_webp', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $quality = intval($_POST['quality'] ?? 85);
    $quality = max(1, min(100, $quality));
    
    $skip_existing = isset($_POST['skip_existing']) && $_POST['skip_existing'] === 'true';
    
    // Alle Bilder holen
    $args = [
        'post_type' => 'attachment',
        'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif'],
        'posts_per_page' => -1,
        'post_status' => 'inherit'
    ];
    
    $images = get_posts($args);
    $total = count($images);
    $converted = 0;
    $skipped = 0;
    $errors = 0;
    $error_messages = [];
    
    foreach ($images as $image) {
        $file_path = get_attached_file($image->ID);
        if (!$file_path || !file_exists($file_path)) {
            $errors++;
            continue;
        }
        
        // Prüfen ob WebP bereits existiert
        $file_info = pathinfo($file_path);
        $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
        
        if ($skip_existing && file_exists($webp_path)) {
            $skipped++;
            continue;
        }
        
        $result = asu_convert_to_webp($image->ID, $quality);
        
        if (is_wp_error($result)) {
            $errors++;
            $error_messages[] = $image->post_title . ': ' . $result->get_error_message();
        } else {
            asu_add_webp_attachment($image->ID, $result['path']);
            $converted++;
        }
        
        // Kurze Pause um Server nicht zu überlasten
        usleep(100000); // 0.1 Sekunde
    }
    
    $message = "✅ {$converted} Bilder konvertiert";
    if ($skipped > 0) {
        $message .= ", {$skipped} übersprungen";
    }
    if ($errors > 0) {
        $message .= ", {$errors} Fehler";
    }
    
    wp_send_json_success([
        'message' => $message,
        'converted' => $converted,
        'skipped' => $skipped,
        'errors' => $errors,
        'total' => $total,
        'error_messages' => array_slice($error_messages, 0, 10) // Max 10 Fehler anzeigen
    ]);
});

/**
 * WebP-Version in srcset einbinden (optional)
 */
add_filter('wp_calculate_image_srcset', function($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    $webp_path = get_post_meta($attachment_id, '_asu_webp_path', true);
    if ($webp_path && file_exists($webp_path)) {
        $webp_url = get_post_meta($attachment_id, '_asu_webp_url', true);
        // WebP-Version zu srcset hinzufügen
        // (Erweiterte Implementierung möglich)
    }
    return $sources;
}, 10, 5);

