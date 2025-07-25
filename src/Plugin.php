<?php

namespace WPCLIShell;

class Plugin {
    /**
     * @var Command
     */
    private $command;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var SettingsPage
     */
    private $settings_page;

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize settings
        $this->settings = new Settings();
        
        // Initialize settings page
        $this->settings_page = new SettingsPage($this->settings);

        // Initialize command handler with settings
        $this->command = new Command($this->settings);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_filter('plugin_action_links_' . plugin_basename(WPCLI_SHELL_PLUGIN_DIR . 'wp-cli-shell.php'), [$this, 'add_settings_link']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('WP Shell', 'wp-cli-shell'),
            __('WP Shell', 'wp-cli-shell'),
            'manage_options',
            'wp-cli-shell',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Add settings link to plugin listing
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('tools.php?page=wp-cli-shell-settings'),
            __('Settings', 'wp-cli-shell')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, ['tools_page_wp-cli-shell', 'tools_page_wp-cli-shell-settings'])) {
            return;
        }

        wp_enqueue_style(
            'wp-cli-shell-admin',
            WPCLI_SHELL_PLUGIN_URL . 'assets/css/admin.css',
            [],
            WPCLI_SHELL_VERSION
        );

        wp_enqueue_script(
            'wp-cli-shell-admin',
            WPCLI_SHELL_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WPCLI_SHELL_VERSION,
            true
        );

        wp_localize_script('wp-cli-shell-admin', 'wpCliShell', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp-cli-shell-nonce'),
            'prompt' => 'wp-cli> ',
            'version' => WPCLI_SHELL_VERSION,
            'welcomeMessage' => sprintf(
                'WordPress CLI Shell [Version %s]%sType \'help\' to see available commands.',
                WPCLI_SHELL_VERSION,
                "\n"
            )
        ]);
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__('WP Shell', 'wp-cli-shell'); ?>
                <a href="<?php echo esc_url(admin_url('tools.php?page=wp-cli-shell-settings')); ?>" class="page-title-action">
                    <?php echo esc_html__('Settings', 'wp-cli-shell'); ?>
                </a>
                <button id="check-wp-cli" class="page-title-action">
                    <?php echo esc_html__('Check WP-CLI Status', 'wp-cli-shell'); ?>
                </button>
            </h1>
            <div id="wp-cli-status" style="display:none; margin: 10px 0;">
                <div class="wp-cli-shell-test-output">
                    <pre></pre>
                </div>
            </div>
            <div class="wp-cli-shell-container">
                <div class="wp-cli-shell-output" id="wp-cli-shell-output">
                    <div class="wp-cli-shell-welcome">
                        WordPress CLI Shell [Version <?php echo esc_html(WPCLI_SHELL_VERSION); ?>]
                        Type 'help' to see available commands.
                    </div>
                </div>
                <div class="wp-cli-shell-input-container">
                    <span class="wp-cli-shell-prompt">wp-cli> </span>
                    <input type="text" 
                           id="wp-cli-shell-input" 
                           class="wp-cli-shell-input" 
                           autocomplete="off" 
                           spellcheck="false">
                    <button class="button button-primary" id="wp-cli-shell-execute" style="display:none">
                        Execute Command
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
} 