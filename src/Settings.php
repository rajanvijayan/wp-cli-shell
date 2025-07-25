<?php

namespace WPCLIShell;

class Settings {
    /**
     * Option name in wp_options table
     */
    const OPTION_NAME = 'wp_cli_shell_settings';

    /**
     * Default settings
     */
    private $defaults = [
        'php_binary' => '/opt/homebrew/bin/php',
        'wp_cli_path' => '/opt/homebrew/bin/wp',
        'auto_detect' => true,
    ];

    /**
     * Current settings
     */
    private $settings;

    /**
     * Initialize settings
     */
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        $this->settings = $this->get_settings();
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'wp_cli_shell_settings',
            self::OPTION_NAME,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings']
            ]
        );

        add_settings_section(
            'wp_cli_shell_paths',
            __('Path Configuration', 'wp-cli-shell'),
            [$this, 'render_paths_section'],
            'wp_cli_shell_settings'
        );

        add_settings_field(
            'auto_detect',
            __('Auto Detect Paths', 'wp-cli-shell'),
            [$this, 'render_auto_detect_field'],
            'wp_cli_shell_settings',
            'wp_cli_shell_paths'
        );

        add_settings_field(
            'php_binary',
            __('PHP Binary Path', 'wp-cli-shell'),
            [$this, 'render_php_binary_field'],
            'wp_cli_shell_settings',
            'wp_cli_shell_paths'
        );

        add_settings_field(
            'wp_cli_path',
            __('WP-CLI Path', 'wp-cli-shell'),
            [$this, 'render_wp_cli_path_field'],
            'wp_cli_shell_settings',
            'wp_cli_shell_paths'
        );
    }

    /**
     * Render paths section description
     */
    public function render_paths_section() {
        ?>
        <p>
            <?php _e('Configure the paths to PHP binary and WP-CLI executable. If auto-detect is enabled, these paths will be used as fallback.', 'wp-cli-shell'); ?>
        </p>
        <?php
    }

    /**
     * Render auto detect field
     */
    public function render_auto_detect_field() {
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[auto_detect]" 
                   value="1" 
                   <?php checked($this->settings['auto_detect']); ?>>
            <?php _e('Enable auto-detection of PHP and WP-CLI paths', 'wp-cli-shell'); ?>
        </label>
        <p class="description">
            <?php _e('If enabled, the plugin will try to automatically find PHP and WP-CLI executables.', 'wp-cli-shell'); ?>
        </p>
        <?php
    }

    /**
     * Render PHP binary field
     */
    public function render_php_binary_field() {
        ?>
        <input type="text" 
               class="regular-text code"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[php_binary]"
               value="<?php echo esc_attr($this->settings['php_binary']); ?>"
               placeholder="/usr/bin/php">
        <p class="description">
            <?php _e('Full path to PHP CLI binary (e.g., /usr/bin/php, /opt/homebrew/bin/php)', 'wp-cli-shell'); ?>
        </p>
        <?php
    }

    /**
     * Render WP-CLI path field
     */
    public function render_wp_cli_path_field() {
        ?>
        <input type="text" 
               class="regular-text code"
               name="<?php echo esc_attr(self::OPTION_NAME); ?>[wp_cli_path]"
               value="<?php echo esc_attr($this->settings['wp_cli_path']); ?>"
               placeholder="/usr/local/bin/wp">
        <p class="description">
            <?php _e('Full path to WP-CLI executable (e.g., /usr/local/bin/wp, /opt/homebrew/bin/wp)', 'wp-cli-shell'); ?>
        </p>
        <?php
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = [];

        // Sanitize auto detect
        $sanitized['auto_detect'] = !empty($input['auto_detect']);

        // Sanitize PHP binary path
        $sanitized['php_binary'] = $this->sanitize_path($input['php_binary']);
        
        // Sanitize WP-CLI path
        $sanitized['wp_cli_path'] = $this->sanitize_path($input['wp_cli_path']);

        return $sanitized;
    }

    /**
     * Sanitize file path
     */
    private function sanitize_path($path) {
        // Remove any potentially harmful characters
        $path = preg_replace('/[^a-zA-Z0-9\/\-\._]/', '', $path);
        
        // Remove any parent directory references
        $path = str_replace(['../', '..\\'], '', $path);
        
        return $path;
    }

    /**
     * Get settings
     */
    public function get_settings() {
        $settings = get_option(self::OPTION_NAME, []);
        return wp_parse_args($settings, $this->defaults);
    }

    /**
     * Get setting value
     */
    public function get($key) {
        return isset($this->settings[$key]) ? $this->settings[$key] : null;
    }
} 