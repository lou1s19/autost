<?php
/**
 * Elementor-Einstellungen für Auto Setup
 */

if ( ! defined('ABSPATH') ) { exit; }

/**
 * Admin-Menü für Elementor-Einstellungen hinzufügen
 */
add_action('admin_menu', function() {
    // Prüfe ob Elementor-Einstellungen aktiviert sind
    if (!get_option(ASU_ENABLE_ELEMENTOR, 1)) {
        return;
    }
    
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
        
        $kit_id = $kit->get_id();
        
        // Container Padding auf 0 setzen - Elementor Format mit isLinked
        $container_padding = [
            'unit' => 'px',
            'size' => 0,
            'sizes' => [
                'desktop' => 0,
                'tablet' => 0,
                'mobile' => 0,
            ],
            'isLinked' => true  // WICHTIG: Verknüpft die Werte
        ];
        
        // Methode 1: Über Kit Settings API
        $settings = [
            'container_padding' => $container_padding
        ];
        $kit->update_settings($settings);
        
        // Methode 2: Direkt in Kit Settings Meta
        $kit_settings = get_post_meta($kit_id, '_elementor_page_settings', true);
        if (!is_array($kit_settings)) {
            $kit_settings = [];
        }
        $kit_settings['container_padding'] = $container_padding;
        update_post_meta($kit_id, '_elementor_page_settings', $kit_settings);
        
        // Methode 3: In Elementor Settings Option
        $elementor_settings = get_option('elementor_settings', []);
        if (!is_array($elementor_settings)) {
            $elementor_settings = [];
        }
        $elementor_settings['container_padding'] = $container_padding;
        update_option('elementor_settings', $elementor_settings);
        
        // Methode 4: In Kit Data (falls vorhanden)
        $kit_data = get_post_meta($kit_id, '_elementor_data', true);
        if ($kit_data) {
            $kit_data_array = json_decode($kit_data, true);
            if (is_array($kit_data_array)) {
                // Container Padding in Settings setzen
                if (!isset($kit_data_array['settings'])) {
                    $kit_data_array['settings'] = [];
                }
                $kit_data_array['settings']['container_padding'] = $container_padding;
                update_post_meta($kit_id, '_elementor_data', json_encode($kit_data_array));
            }
        }
        
        // Flag setzen, dass Container-Padding auf 0 gesetzt wurde
        update_option('asu_container_padding_zero', true);
        
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
 * CSS: Container-Padding automatisch auf 0 setzen
 */
add_action('wp_head', function() {
    if (!get_option('asu_container_padding_zero', false)) {
        return;
    }
    ?>
    <style>
    /* Container Padding auf 0 setzen - ALLE Container (auch verschachtelte) */
    .e-container,
    .e-con-inner,
    .e-con,
    .e-con-full,
    .e-con-boxed,
    .e-child,
    .elementor-element[data-element_type="container"] .e-container,
    .elementor-element[data-element_type="container"] .e-con-inner,
    .elementor-element[data-element_type="container"].e-con,
    .elementor-element[data-element_type="container"].e-con-full,
    .elementor-element[data-element_type="container"].e-con-boxed,
    .elementor-element[data-element_type="container"].e-child {
        padding-top: 0 !important;
        padding-right: 0 !important;
        padding-bottom: 0 !important;
        padding-left: 0 !important;
    }
    
    /* Auch für alle Container-Varianten und verschachtelte Container */
    .elementor-container .e-container,
    .elementor-container .e-con-inner,
    .e-con .e-con-inner,
    .e-con .e-container,
    .e-con-full .e-con-inner,
    .e-con-full .e-container,
    .e-child .e-con-inner,
    .e-child .e-container {
        padding-top: 0 !important;
        padding-right: 0 !important;
        padding-bottom: 0 !important;
        padding-left: 0 !important;
    }
    
    /* Speziell für verschachtelte Container (e-child) */
    .e-con .e-con,
    .e-con .e-con-full,
    .e-con .e-child,
    .e-con-full .e-con,
    .e-con-full .e-con-full,
    .e-con-full .e-child {
        padding-top: 0 !important;
        padding-right: 0 !important;
        padding-bottom: 0 !important;
        padding-left: 0 !important;
    }
    </style>
    <?php
}, 999);

/**
 * JavaScript Hook: Container-Padding automatisch auf 0 setzen für neue Container
 */
add_action('elementor/frontend/after_enqueue_scripts', function() {
    if (!get_option('asu_container_padding_zero', false)) {
        return;
    }
    ?>
    <script>
    (function() {
        if (typeof elementorFrontend === 'undefined') {
            return;
        }
        
        // Hook für neue Container - setze Padding automatisch auf 0 (auch verschachtelte)
        elementorFrontend.hooks.addAction('frontend/element_ready/container', function($scope) {
            // Finde alle Container-Varianten (auch verschachtelte)
            var $containers = $scope.find('.e-container, .e-con-inner, .e-con, .e-con-full, .e-con-boxed, .e-child');
            
            // Auch der Container selbst
            if ($scope.hasClass('e-con') || $scope.hasClass('e-con-full') || $scope.hasClass('e-con-boxed') || $scope.hasClass('e-child')) {
                $containers = $containers.add($scope);
            }
            
            if ($containers.length) {
                // Setze Padding auf 0 via CSS (Fallback)
                $containers.css({
                    'padding-top': '0',
                    'padding-right': '0',
                    'padding-bottom': '0',
                    'padding-left': '0'
                });
            }
        });
    })();
    </script>
    <?php
});

/**
 * JavaScript für Elementor Editor: Container-Padding automatisch auf 0 setzen
 */
add_action('elementor/editor/before_enqueue_scripts', function() {
    if (!get_option('asu_container_padding_zero', false)) {
        return;
    }
    ?>
    <script>
    (function() {
        // Warte bis Elementor Editor vollständig geladen ist
        function initContainerPadding() {
            if (typeof elementor === 'undefined' || !elementor.hooks) {
                setTimeout(initContainerPadding, 100);
                return;
            }
            
            // Hook für Container-Defaults - setze Padding auf 0 für neue Container
            elementor.hooks.addFilter('elementor/container/defaults', function(defaults) {
                defaults.padding = {
                    'unit': 'px',
                    'size': 0,
                    'sizes': {
                        'desktop': 0,
                        'tablet': 0,
                        'mobile': 0
                    },
                    'isLinked': true
                };
                return defaults;
            }, 10);
            
            // Hook wenn Container erstellt wird
            elementor.hooks.addAction('panel/open_editor/widget/container', function(panel, model, view) {
                if (model && model.get) {
                    var padding = model.get('settings').get('padding');
                    if (!padding || padding.size !== 0) {
                        model.get('settings').set('padding', {
                            'unit': 'px',
                            'size': 0,
                            'sizes': {
                                'desktop': 0,
                                'tablet': 0,
                                'mobile': 0
                            },
                            'isLinked': true
                        });
                    }
                }
            });
            
            // Hook wenn Container hinzugefügt wird (auch verschachtelte)
            elementor.hooks.addAction('elementor/elements/new', function(model) {
                if (model && model.get) {
                    var widgetType = model.get('widgetType');
                    var elementType = model.get('elType');
                    
                    // Prüfe ob es ein Container ist (auch verschachtelte)
                    if (widgetType === 'container' || elementType === 'container') {
                        var settings = model.get('settings');
                        if (settings) {
                            // Setze Padding auf 0
                            var currentPadding = settings.get('padding');
                            if (!currentPadding || currentPadding.size !== 0) {
                                settings.set('padding', {
                                    'unit': 'px',
                                    'size': 0,
                                    'sizes': {
                                        'desktop': 0,
                                        'tablet': 0,
                                        'mobile': 0
                                    },
                                    'isLinked': true
                                });
                            }
                        }
                    }
                }
            });
            
            // Hook wenn Container-Einstellungen geändert werden
            elementor.hooks.addAction('elementor/element/before_section_end', function(element, section) {
                if (section && section.name === 'section_layout' && element && element.model) {
                    var widgetType = element.model.get('widgetType');
                    var elementType = element.model.get('elType');
                    
                    if (widgetType === 'container' || elementType === 'container') {
                        var settings = element.model.get('settings');
                        if (settings) {
                            var padding = settings.get('padding');
                            // Wenn Padding nicht 0 ist, setze es auf 0
                            if (padding && padding.size !== 0) {
                                settings.set('padding', {
                                    'unit': 'px',
                                    'size': 0,
                                    'sizes': {
                                        'desktop': 0,
                                        'tablet': 0,
                                        'mobile': 0
                                    },
                                    'isLinked': true
                                });
                            }
                        }
                    }
                }
            });
        }
        
        // Starte nach Elementor Init
        if (typeof elementor !== 'undefined' && elementor.hooks) {
            elementor.hooks.addAction('elementor/init', initContainerPadding);
        } else {
            jQuery(window).on('elementor:init', initContainerPadding);
        }
        
        // Auch direkt versuchen (falls bereits geladen)
        setTimeout(initContainerPadding, 500);
    })();
    </script>
    <?php
});

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

