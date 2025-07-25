<?php

namespace WPCLIShell;

class SettingsPage {
    /**
     * @var Settings
     */
    private $settings;

    /**
     * Initialize settings page
     */
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        add_action('admin_menu', [$this, 'add_settings_page']);
    }

    /**
     * Add settings page to menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php', // Parent slug for Settings menu
            __('WP CLI Shell Settings', 'wp-cli-shell'),
            __('WP CLI Shell', 'wp-cli-shell'),
            'manage_options',
            'wp-cli-shell-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings if form was submitted
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'wp_cli_shell_messages',
                'wp_cli_shell_message',
                __('Settings Saved', 'wp-cli-shell'),
                'updated'
            );
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php settings_errors('wp_cli_shell_messages'); ?>

            <form action="options.php" method="post">
                <?php
                settings_fields('wp_cli_shell_settings');
                do_settings_sections('wp_cli_shell_settings');
                submit_button('Save Settings');
                ?>
            </form>

            <div class="wp-cli-shell-test-paths">
                <h2><?php _e('Test Configuration', 'wp-cli-shell'); ?></h2>
                <p><?php _e('Click the button below to test if the configured paths are working correctly.', 'wp-cli-shell'); ?></p>
                <button type="button" id="test-wp-cli-paths" class="button button-secondary">
                    <?php _e('Test Paths', 'wp-cli-shell'); ?>
                </button>
                <div id="test-results" style="display:none; margin-top: 15px;">
                    <h3><?php _e('Test Results', 'wp-cli-shell'); ?></h3>
                    <div class="wp-cli-shell-test-output">
                        <pre></pre>
                    </div>
                </div>
            </div>

            <style>
            .wp-cli-shell-test-output {
                background: #000000;
                padding: 15px;
                border: 1px solid #333;
                font-family: "Courier New", Courier, monospace;
                margin-top: 10px;
            }
            .wp-cli-shell-test-output pre {
                background: transparent;
                padding: 0;
                margin: 0;
                color: #ffffff;
                font-family: "Courier New", Courier, monospace;
                font-size: 14px;
                line-height: 1.4;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            </style>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-wp-cli-paths').on('click', function() {
                const button = $(this);
                const results = $('#test-results');
                const pre = results.find('pre');

                button.prop('disabled', true);
                button.text('<?php echo esc_js(__('Testing...', 'wp-cli-shell')); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'check_wp_cli',
                        nonce: '<?php echo wp_create_nonce('wp-cli-shell-nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const data = response.data;
                            let output = '';
                            output += 'PHP Binary:\n';
                            output += '  Path: ' + (data.php_binary || 'Not found') + '\n';
                            output += '  Executable: ' + (data.php_binary_exists ? '✅ Yes' : '❌ No') + '\n\n';
                            output += 'WP-CLI:\n';
                            output += '  Path: ' + (data.path || 'Not found') + '\n';
                            output += '  Executable: ' + (data.executable ? '✅ Yes' : '❌ No') + '\n';
                            output += '  Working: ' + (data.wp_cli_works ? '✅ Yes' : '❌ No') + '\n\n';
                            output += 'Test Output:\n' + data.test_output;
                            
                            pre.text(output);
                            results.slideDown();
                        } else {
                            pre.text('Error: ' + response.data);
                            results.slideDown();
                        }
                    },
                    error: function() {
                        pre.text('Failed to test configuration. Please try again.');
                        results.slideDown();
                    },
                    complete: function() {
                        button.prop('disabled', false);
                        button.text('<?php echo esc_js(__('Test Paths', 'wp-cli-shell')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
} 