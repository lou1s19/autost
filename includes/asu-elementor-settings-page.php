<?php
/**
 * Elementor-Einstellungen Admin-Seite
 */

if (!function_exists('did_action')) {
    return;
}

$elementor_loaded = function_exists('did_action') ? did_action('elementor/loaded') : false;
$saved_typography = function_exists('get_option') ? get_option('asu_elementor_typography', null) : null;

// Standard-Werte
$default_typography = [
    'h1' => ['desktop' => 72, 'tablet' => 56, 'mobile' => 40],
    'h2' => ['desktop' => 56, 'tablet' => 44, 'mobile' => 32],
    'h3' => ['desktop' => 40, 'tablet' => 32, 'mobile' => 24],
    'h4' => ['desktop' => 32, 'tablet' => 28, 'mobile' => 20],
    'h5' => ['desktop' => 24, 'tablet' => 22, 'mobile' => 18],
    'h6' => ['desktop' => 20, 'tablet' => 18, 'mobile' => 16],
    'body' => ['desktop' => 16, 'tablet' => 16, 'mobile' => 14],
];

$typography = $saved_typography ?: $default_typography;
?>
<div class="wrap asu-elementor-wrapper">
    <style>
        .asu-elementor-wrapper {
            max-width: 1200px;
            margin: 20px auto;
        }
        .asu-elementor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .asu-elementor-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        .asu-elementor-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .asu-elementor-card h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .asu-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .asu-button:hover {
            opacity: 0.9;
        }
        .asu-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .asu-status {
            margin-top: 15px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        .asu-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .asu-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .asu-status-box {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .asu-status-box.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .asu-typography-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .asu-typography-table th,
        .asu-typography-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .asu-typography-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .asu-typography-table input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .asu-typography-table input:focus {
            border-color: #667eea;
            outline: none;
        }
        .asu-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .asu-info-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .asu-info-list li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        .asu-info-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
    </style>

    <div class="asu-elementor-header">
        <h1>🎨 Elementor Einstellungen</h1>
        <p>Container-Abstand und Typografie-Einstellungen für Elementor konfigurieren</p>
    </div>

    <?php if (!$elementor_loaded): ?>
        <div class="asu-status-box error">
            <strong>⚠️ Elementor ist nicht aktiv</strong>
            <p>Bitte aktiviere zuerst das Elementor Plugin.</p>
        </div>
    <?php else: ?>

        <div class="asu-elementor-card">
            <h2>📦 Container-Abstand</h2>
            <p>Setzt den Standard-Container-Abstand auf 0. Neue Container haben dann automatisch keinen Abstand.</p>
            <ul class="asu-info-list">
                <li>Container-Abstand wird auf 0 gesetzt</li>
                <li>Gilt für Desktop, Tablet und Mobile</li>
                <li>Neue Container haben standardmäßig keinen Abstand</li>
            </ul>
            <button type="button" class="asu-button" id="asu-set-container-padding">
                Container-Abstand auf 0 setzen
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            <div class="asu-status" id="asu-container-status"></div>
        </div>

        <div class="asu-elementor-card">
            <h2>✍️ Typografie-Einstellungen</h2>
            <p>Setzt Standard-Schriftgrößen für H1-H6 und Body-Text (Inter-ähnlich). Anpassbar für Desktop, Tablet und Handy.</p>
            
            <table class="asu-typography-table">
                <thead>
                    <tr>
                        <th>Element</th>
                        <th>Desktop (px)</th>
                        <th>Tablet (px)</th>
                        <th>Handy (px)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($typography as $tag => $sizes): ?>
                        <tr>
                            <td><strong><?php echo strtoupper($tag); ?></strong></td>
                            <td>
                                <input type="number" 
                                       name="typography[<?php echo $tag; ?>][desktop]" 
                                       value="<?php echo esc_attr($sizes['desktop']); ?>"
                                       min="8" max="200">
                            </td>
                            <td>
                                <input type="number" 
                                       name="typography[<?php echo $tag; ?>][tablet]" 
                                       value="<?php echo esc_attr($sizes['tablet']); ?>"
                                       min="8" max="200">
                            </td>
                            <td>
                                <input type="number" 
                                       name="typography[<?php echo $tag; ?>][mobile]" 
                                       value="<?php echo esc_attr($sizes['mobile']); ?>"
                                       min="8" max="200">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button type="button" class="asu-button" id="asu-set-typography">
                Typografie-Einstellungen anwenden
                <span class="asu-spinner" style="display:none;"></span>
            </button>
            <div class="asu-status" id="asu-typography-status"></div>
        </div>

    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Container-Abstand setzen
    $('#asu-set-container-padding').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-container-status');
        var $spinner = $btn.find('.asu-spinner');
        
        if (!confirm('Möchtest du wirklich den Container-Abstand auf 0 setzen? Neue Container haben dann standardmäßig keinen Abstand.')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_set_container_padding_zero',
                nonce: '<?php echo wp_create_nonce('asu_elementor_settings'); ?>'
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error').addClass('success').html(response.data.message).show();
                } else {
                    $status.removeClass('success').addClass('error').html(response.data.message || 'Fehler').show();
                }
                $btn.prop('disabled', false);
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });

    // Typografie-Einstellungen setzen
    $('#asu-set-typography').on('click', function() {
        var $btn = $(this);
        var $status = $('#asu-typography-status');
        var $spinner = $btn.find('.asu-spinner');
        
        // Werte sammeln
        var typographySizes = {};
        $('input[name^="typography["]').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/typography\[([^\]]+)\]\[([^\]]+)\]/);
            if (matches) {
                var tag = matches[1];
                var device = matches[2];
                if (!typographySizes[tag]) {
                    typographySizes[tag] = {};
                }
                typographySizes[tag][device] = $(this).val();
            }
        });
        
        if (!confirm('Möchtest du wirklich die Typografie-Einstellungen anwenden? Dies setzt die Standard-Schriftgrößen für H1-H6 und Body-Text.')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $spinner.show();
        $status.removeClass('success error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'asu_set_default_typography',
                typography_sizes: typographySizes,
                nonce: '<?php echo wp_create_nonce('asu_elementor_settings'); ?>'
            },
            success: function(response) {
                $spinner.hide();
                if (response.success) {
                    $status.removeClass('error').addClass('success').html(response.data.message).show();
                } else {
                    $status.removeClass('success').addClass('error').html(response.data.message || 'Fehler').show();
                }
                $btn.prop('disabled', false);
            },
            error: function() {
                $spinner.hide();
                $status.removeClass('success').addClass('error').html('Ein Fehler ist aufgetreten.').show();
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>

