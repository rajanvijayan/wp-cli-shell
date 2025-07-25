<?php

namespace WPCLIShell;

class Command {
    /**
     * @var Settings
     */
    private $settings;

    /**
     * Initialize the command handler
     */
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        add_action('wp_ajax_execute_wp_cli', [$this, 'execute_command']);
    }

    /**
     * Execute WordPress command
     */
    public function execute_command() {
        // Check nonce
        if (!check_ajax_referer('wp-cli-shell-nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security token');
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $command = isset($_POST['command']) ? sanitize_text_field($_POST['command']) : '';
        
        if (empty($command)) {
            wp_send_json_error('No command provided');
        }

        // Parse command
        $args = $this->parse_command($command);
        if (is_wp_error($args)) {
            wp_send_json_error($args->get_error_message());
        }

        try {
            // Execute command
            $output = $this->execute_wp_command($args);
            
            if (is_wp_error($output)) {
                wp_send_json_error($output->get_error_message());
            }

            wp_send_json_success($output);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Parse command into arguments
     */
    private function parse_command($command) {
        // Remove 'wp' from the beginning if present
        $command = preg_replace('/^wp\s+/', '', trim($command));

        // Split command into parts
        $parts = array();
        if (preg_match_all('/"([^"]*)"|\S+/', $command, $matches)) {
            foreach ($matches[0] as $part) {
                $parts[] = trim($part, '"');
            }
        }

        if (empty($parts)) {
            return new \WP_Error('invalid_command', 'Invalid command format');
        }

        return $parts;
    }

    /**
     * Execute WordPress command
     */
    private function execute_wp_command($args) {
        ob_start();
        $command = array_shift($args);

        switch ($command) {
            case 'clear':
            case 'cls':
                return '<clear>'; // Special marker for clear screen

            case 'plugin':
                $this->handle_plugin_command($args);
                break;

            case 'theme':
                $this->handle_theme_command($args);
                break;

            case 'user':
                $this->handle_user_command($args);
                break;

            case 'post':
                $this->handle_post_command($args);
                break;

            case 'option':
                $this->handle_option_command($args);
                break;

            case 'site':
                $this->handle_site_command($args);
                break;

            case 'help':
                $this->show_help();
                break;

            default:
                echo "Unknown command: $command\n";
                echo "Type 'help' to see available commands.\n";
        }

        $output = ob_get_clean();
        return $output ?: 'Command executed successfully';
    }

    /**
     * Handle plugin commands
     */
    private function handle_plugin_command($args) {
        if (empty($args)) {
            echo "Available plugin commands:\n";
            echo "  list          - List plugins\n";
            echo "  activate      - Activate a plugin\n";
            echo "  deactivate    - Deactivate a plugin\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'list':
                $plugins = get_plugins();
                echo "Installed plugins:\n\n";
                foreach ($plugins as $file => $data) {
                    $status = is_plugin_active($file) ? 'active' : 'inactive';
                    echo sprintf(
                        "%s (%s) - %s\n",
                        $data['Name'],
                        $data['Version'],
                        $status
                    );
                }
                break;

            case 'activate':
                if (empty($args)) {
                    echo "Usage: plugin activate <plugin-name>\n";
                    return;
                }
                $plugin = $this->find_plugin_by_name($args[0]);
                if ($plugin) {
                    activate_plugin($plugin);
                    echo "Plugin activated successfully.\n";
                } else {
                    echo "Plugin not found.\n";
                }
                break;

            case 'deactivate':
                if (empty($args)) {
                    echo "Usage: plugin deactivate <plugin-name>\n";
                    return;
                }
                $plugin = $this->find_plugin_by_name($args[0]);
                if ($plugin) {
                    deactivate_plugins($plugin);
                    echo "Plugin deactivated successfully.\n";
                } else {
                    echo "Plugin not found.\n";
                }
                break;

            default:
                echo "Unknown plugin command: $action\n";
        }
    }

    /**
     * Handle theme commands
     */
    private function handle_theme_command($args) {
        if (empty($args)) {
            echo "Available theme commands:\n";
            echo "  list          - List themes\n";
            echo "  activate      - Activate a theme\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'list':
                $themes = wp_get_themes();
                $current = wp_get_theme();
                echo "Installed themes:\n\n";
                foreach ($themes as $theme) {
                    $status = ($theme->get_stylesheet() === $current->get_stylesheet()) ? 'active' : 'inactive';
                    echo sprintf(
                        "%s (%s) - %s\n",
                        $theme->get('Name'),
                        $theme->get('Version'),
                        $status
                    );
                }
                break;

            case 'activate':
                if (empty($args)) {
                    echo "Usage: theme activate <theme-name>\n";
                    return;
                }
                $theme = wp_get_theme($args[0]);
                if ($theme->exists()) {
                    switch_theme($theme->get_stylesheet());
                    echo "Theme activated successfully.\n";
                } else {
                    echo "Theme not found.\n";
                }
                break;

            default:
                echo "Unknown theme command: $action\n";
        }
    }

    /**
     * Handle user commands
     */
    private function handle_user_command($args) {
        if (empty($args)) {
            echo "Available user commands:\n";
            echo "  list          - List users\n";
            echo "  create        - Create a new user\n";
            echo "  delete        - Delete a user\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'list':
                $users = get_users();
                echo "Users:\n\n";
                foreach ($users as $user) {
                    $roles = implode(', ', $user->roles);
                    echo sprintf(
                        "%s (%s) - %s\n",
                        $user->user_login,
                        $user->user_email,
                        $roles
                    );
                }
                break;

            case 'create':
                if (count($args) < 3) {
                    echo "Usage: user create <username> <email> <password> [role]\n";
                    return;
                }
                $userdata = array(
                    'user_login' => $args[0],
                    'user_email' => $args[1],
                    'user_pass'  => $args[2],
                    'role'       => isset($args[3]) ? $args[3] : 'subscriber'
                );
                $user_id = wp_insert_user($userdata);
                if (is_wp_error($user_id)) {
                    echo "Error creating user: " . $user_id->get_error_message() . "\n";
                } else {
                    echo "User created successfully.\n";
                }
                break;

            case 'delete':
                if (empty($args)) {
                    echo "Usage: user delete <username>\n";
                    return;
                }
                $user = get_user_by('login', $args[0]);
                if ($user) {
                    wp_delete_user($user->ID);
                    echo "User deleted successfully.\n";
                } else {
                    echo "User not found.\n";
                }
                break;

            default:
                echo "Unknown user command: $action\n";
        }
    }

    /**
     * Handle post commands
     */
    private function handle_post_command($args) {
        if (empty($args)) {
            echo "Available post commands:\n";
            echo "  list          - List posts\n";
            echo "  create        - Create a new post\n";
            echo "  delete        - Delete a post\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'list':
                $posts = get_posts(['posts_per_page' => -1]);
                echo "Posts:\n\n";
                foreach ($posts as $post) {
                    echo sprintf(
                        "%s (ID: %d) - %s\n",
                        $post->post_title,
                        $post->ID,
                        $post->post_status
                    );
                }
                break;

            case 'create':
                if (count($args) < 2) {
                    echo "Usage: post create <title> <content>\n";
                    return;
                }
                $post_data = array(
                    'post_title'   => $args[0],
                    'post_content' => $args[1],
                    'post_status'  => 'publish',
                    'post_author'  => get_current_user_id()
                );
                $post_id = wp_insert_post($post_data);
                if (is_wp_error($post_id)) {
                    echo "Error creating post: " . $post_id->get_error_message() . "\n";
                } else {
                    echo "Post created successfully.\n";
                }
                break;

            case 'delete':
                if (empty($args)) {
                    echo "Usage: post delete <post-id>\n";
                    return;
                }
                $result = wp_delete_post(intval($args[0]), true);
                if ($result) {
                    echo "Post deleted successfully.\n";
                } else {
                    echo "Post not found or error deleting.\n";
                }
                break;

            default:
                echo "Unknown post command: $action\n";
        }
    }

    /**
     * Handle option commands
     */
    private function handle_option_command($args) {
        if (empty($args)) {
            echo "Available option commands:\n";
            echo "  get           - Get option value\n";
            echo "  update        - Update option value\n";
            echo "  delete        - Delete option\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'get':
                if (empty($args)) {
                    echo "Usage: option get <option-name>\n";
                    return;
                }
                $value = get_option($args[0]);
                if ($value !== false) {
                    if (is_array($value) || is_object($value)) {
                        print_r($value);
                    } else {
                        echo $value . "\n";
                    }
                } else {
                    echo "Option not found.\n";
                }
                break;

            case 'update':
                if (count($args) < 2) {
                    echo "Usage: option update <option-name> <value>\n";
                    return;
                }
                update_option($args[0], $args[1]);
                echo "Option updated successfully.\n";
                break;

            case 'delete':
                if (empty($args)) {
                    echo "Usage: option delete <option-name>\n";
                    return;
                }
                if (delete_option($args[0])) {
                    echo "Option deleted successfully.\n";
                } else {
                    echo "Option not found or error deleting.\n";
                }
                break;

            default:
                echo "Unknown option command: $action\n";
        }
    }

    /**
     * Handle site commands
     */
    private function handle_site_command($args) {
        if (empty($args)) {
            echo "Available site commands:\n";
            echo "  info          - Show site information\n";
            echo "  url           - Show site URL\n";
            return;
        }

        $action = array_shift($args);
        switch ($action) {
            case 'info':
                echo "Site Information:\n\n";
                echo "Site Title: " . get_bloginfo('name') . "\n";
                echo "Description: " . get_bloginfo('description') . "\n";
                echo "URL: " . get_bloginfo('url') . "\n";
                echo "Admin Email: " . get_bloginfo('admin_email') . "\n";
                echo "Language: " . get_bloginfo('language') . "\n";
                echo "WordPress Version: " . get_bloginfo('version') . "\n";
                break;

            case 'url':
                echo get_bloginfo('url') . "\n";
                break;

            default:
                echo "Unknown site command: $action\n";
        }
    }

    /**
     * Show help information
     */
    private function show_help() {
        echo "Available commands:\n\n";
        echo "System:\n";
        echo "  clear/cls         Clear the screen\n\n";
        
        echo "plugin\n";
        echo "  list              List installed plugins\n";
        echo "  activate          Activate a plugin\n";
        echo "  deactivate        Deactivate a plugin\n\n";
        
        echo "theme\n";
        echo "  list              List installed themes\n";
        echo "  activate          Activate a theme\n\n";
        
        echo "user\n";
        echo "  list              List users\n";
        echo "  create            Create a new user\n";
        echo "  delete            Delete a user\n\n";
        
        echo "post\n";
        echo "  list              List posts\n";
        echo "  create            Create a new post\n";
        echo "  delete            Delete a post\n\n";
        
        echo "option\n";
        echo "  get               Get option value\n";
        echo "  update            Update option value\n";
        echo "  delete            Delete option\n\n";
        
        echo "site\n";
        echo "  info              Show site information\n";
        echo "  url               Show site URL\n";
    }

    /**
     * Find plugin by name
     */
    private function find_plugin_by_name($name) {
        $plugins = get_plugins();
        foreach ($plugins as $file => $data) {
            if (strtolower($data['Name']) === strtolower($name)) {
                return $file;
            }
        }
        return false;
    }
} 