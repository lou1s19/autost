<?php
/**
 * Elementor-Einstellungen für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin-Menü für Elementor-Einstellungen hinzufügen
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'auto-setup',
        'Elementor Einstellungen',
        'Elementor Einstellungen',
        'manage_options',
        'auto-setup-elementor',
        'asu_render_elementor_settings_page'
    );
});

/**
 * Elementor-Einstellungen Admin-Seite rendern
 */
function asu_render_elementor_settings_page() {
    include ASU_PLUGIN_DIR . 'includes/asu-elementor-settings-page.php';
}

/**
 * Container-Abstand auf 0 setzen
 */
function asu_set_container_padding_zero() {
    if (!did_action('elementor/loaded')) {
        return new WP_Error('elementor_not_loaded', 'Elementor ist nicht geladen');
    }
    
    try {
        // Elementor Kit Settings
        if (!class_exists('\Elementor\Plugin')) {
            return new WP_Error('elementor_class', 'Elementor-Klasse nicht gefunden');
        }
        
        $elementor_plugin = \Elementor\Plugin::$instance;
        if (!isset($elementor_plugin->kits_manager)) {
            return new WP_Error('kits_manager', 'Elementor Kits Manager nicht verfügbar');
        }
        
        $kit = $elementor_plugin->kits_manager->get_active_kit();
        
        if (!$kit) {
            return new WP_Error('no_kit', 'Kein aktives Elementor Kit gefunden');
        }
        
        // Container Padding auf 0 setzen (für alle Breakpoints)
        $settings = [
            'container_padding' => [
                'unit' => 'px',
                'size' => 0,
                'sizes' => [
                    'desktop' => 0,
                    'tablet' => 0,
                    'mobile' => 0,
                ]
            ]
        ];
        
        // Kit Settings aktualisieren
        $kit->update_settings($settings);
        
        // Zusätzlich: In Elementor Global Settings speichern
        $elementor_settings = get_option('elementor_settings', []);
        if (!is_array($elementor_settings)) {
            $elementor_settings = [];
        }
        $elementor_settings['container_padding'] = 0;
        update_option('elementor_settings', $elementor_settings);
        
        // Cache leeren (wenn verfügbar)
        if (isset($elementor_plugin->files_manager)) {
            $elementor_plugin->files_manager->clear_cache();
        }
        
        return true;
    } catch (\Throwable $e) {
        return new WP_Error('error', $e->getMessage());
    }
}

/**
 * Standard-Typografie-Einstellungen setzen (Inter-ähnlich)
 */
function asu_set_default_typography($custom_sizes = null) {
    if (!did_action('elementor/loaded')) {
        return new WP_Error('elementor_not_loaded', 'Elementor ist nicht geladen');
    }
    
    // Standard-Schriftgrößen (Inter-ähnlich)
    $default_sizes = [
        'h1' => [
            'desktop' => ['size' => 72, 'unit' => 'px'],
            'tablet' => ['size' => 56, 'unit' => 'px'],
            'mobile' => ['size' => 40, 'unit' => 'px'],
        ],
        'h2' => [
            'desktop' => ['size' => 56, 'unit' => 'px'],
            'tablet' => ['size' => 44, 'unit' => 'px'],
            'mobile' => ['size' => 32, 'unit' => 'px'],
        ],
        'h3' => [
            'desktop' => ['size' => 40, 'unit' => 'px'],
            'tablet' => ['size' => 32, 'unit' => 'px'],
            'mobile' => ['size' => 24, 'unit' => 'px'],
        ],
        'h4' => [
            'desktop' => ['size' => 32, 'unit' => 'px'],
            'tablet' => ['size' => 28, 'unit' => 'px'],
            'mobile' => ['size' => 20, 'unit' => 'px'],
        ],
        'h5' => [
            'desktop' => ['size' => 24, 'unit' => 'px'],
            'tablet' => ['size' => 22, 'unit' => 'px'],
            'mobile' => ['size' => 18, 'unit' => 'px'],
        ],
        'h6' => [
            'desktop' => ['size' => 20, 'unit' => 'px'],
            'tablet' => ['size' => 18, 'unit' => 'px'],
            'mobile' => ['size' => 16, 'unit' => 'px'],
        ],
        'body' => [
            'desktop' => ['size' => 16, 'unit' => 'px'],
            'tablet' => ['size' => 16, 'unit' => 'px'],
            'mobile' => ['size' => 14, 'unit' => 'px'],
        ],
    ];
    
    // Custom Sizes verwenden falls vorhanden
    if ($custom_sizes && is_array($custom_sizes)) {
        $default_sizes = array_merge($default_sizes, $custom_sizes);
    }
    
    try {
        if (!class_exists('\Elementor\Plugin')) {
            return new WP_Error('elementor_class', 'Elementor-Klasse nicht gefunden');
        }
        
        $elementor_plugin = \Elementor\Plugin::$instance;
        if (!isset($elementor_plugin->kits_manager)) {
            return new WP_Error('kits_manager', 'Elementor Kits Manager nicht verfügbar');
        }
        
        $kit = $elementor_plugin->kits_manager->get_active_kit();
        
        if (!$kit) {
            return new WP_Error('no_kit', 'Kein aktives Elementor Kit gefunden');
        }
        
        // Typografie-Einstellungen für jedes Heading
        $typography_settings = [];
        
        foreach ($default_sizes as $tag => $sizes) {
            $setting_key = $tag . '_typography';
            
            $typography_settings[$setting_key] = [
                'font_family' => 'Inter',
                'font_weight' => $tag === 'body' ? '400' : '600',
                'font_size' => [
                    'unit' => 'px',
                    'size' => $sizes['desktop']['size'],
                    'sizes' => [
                        'desktop' => $sizes['desktop']['size'],
                        'tablet' => $sizes['tablet']['size'],
                        'mobile' => $sizes['mobile']['size'],
                    ]
                ],
                'line_height' => [
                    'unit' => 'em',
                    'size' => 1.2,
                ],
            ];
        }
        
        // Settings aktualisieren
        $kit->update_settings($typography_settings);
        
        // Cache leeren (wenn verfügbar)
        if (isset($elementor_plugin->files_manager)) {
            $elementor_plugin->files_manager->clear_cache();
        }
        
        // Zusätzlich: Global Typography Settings speichern
        update_option('asu_elementor_typography', $default_sizes);
        
        return true;
    } catch (\Throwable $e) {
        return new WP_Error('error', $e->getMessage());
    }
}

/**
 * AJAX: Container-Abstand auf 0 setzen
 */
add_action('wp_ajax_asu_set_container_padding_zero', function() {
    check_ajax_referer('asu_elementor_settings', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $result = asu_set_container_padding_zero();
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }
    
    wp_send_json_success([
        'message' => '✅ Container-Abstand wurde auf 0 gesetzt. Neue Container haben jetzt standardmäßig keinen Abstand.'
    ]);
});

/**
 * AJAX: Typografie-Einstellungen setzen
 */
add_action('wp_ajax_asu_set_default_typography', function() {
    check_ajax_referer('asu_elementor_settings', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Keine Berechtigung']);
        return;
    }
    
    $custom_sizes = null;
    if (isset($_POST['typography_sizes']) && is_array($_POST['typography_sizes'])) {
        $custom_sizes = [];
        foreach ($_POST['typography_sizes'] as $tag => $sizes) {
            $custom_sizes[$tag] = [
                'desktop' => ['size' => intval($sizes['desktop'] ?? 0), 'unit' => 'px'],
                'tablet' => ['size' => intval($sizes['tablet'] ?? 0), 'unit' => 'px'],
                'mobile' => ['size' => intval($sizes['mobile'] ?? 0), 'unit' => 'px'],
            ];
        }
    }
    
    $result = asu_set_default_typography($custom_sizes);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }
    
    wp_send_json_success([
        'message' => '✅ Typografie-Einstellungen wurden erfolgreich gesetzt. H1-H6 haben jetzt die Standard-Schriftgrößen für Desktop, Tablet und Handy.'
    ]);
});

