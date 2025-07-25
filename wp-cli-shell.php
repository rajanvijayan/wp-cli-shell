<?php
/**
 * Plugin Name: WP CLI Shell
 * Plugin URI: https://github.com/rajanvijayan/wp-cli-shell
 * Description: Execute WordPress commands through a web interface
 * Version: 1.0.0
 * Author: Rajan Vijayan
 * Author URI: https://rajanvijayan.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-cli-shell
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

namespace WPCLIShell;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WPCLI_SHELL_VERSION', '1.0.0');
define('WPCLI_SHELL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPCLI_SHELL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoloader
require_once WPCLI_SHELL_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize the plugin
function init_plugin() {
    $plugin = new Plugin();
    $plugin->init();
}
add_action('plugins_loaded', 'WPCLIShell\\init_plugin'); 