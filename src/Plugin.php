<?php

namespace WPCLIShell;

class Plugin {
    /**
     * @var Command
     */
    private $command;

    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize command handler
        $this->command = new Command();

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WP Shell', 'wp-cli-shell'),
            __('WP Shell', 'wp-cli-shell'),
            'manage_options',
            'wp-cli-shell',
            [$this, 'render_admin_page'],
            'dashicons-editor-code', // Using code editor icon as it's more recognizable
            100 // Position after Comments (90)
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_wp-cli-shell') {
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
            <h1><?php echo esc_html__('WP Shell', 'wp-cli-shell'); ?></h1>
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
                </div>
            </div>
        </div>

        <style>
        /* Admin menu icon color */
        #adminmenu .toplevel_page_wp-cli-shell .wp-menu-image::before {
            color: #00ff00 !important;
        }
        
        /* Active menu item color */
        #adminmenu .toplevel_page_wp-cli-shell.current .wp-menu-image::before {
            color: #00ff00 !important;
        }

        /* Hover state color */
        #adminmenu .toplevel_page_wp-cli-shell:hover .wp-menu-image::before {
            color: #00ff00 !important;
        }
        </style>
        <?php
    }
} 