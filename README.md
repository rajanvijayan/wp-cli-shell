# WP CLI Shell

A WordPress plugin that provides a web interface for executing WP-CLI commands directly from the WordPress admin panel.

## Features

- Execute WP-CLI commands through a web interface
- Command validation and security checks
- Terminal-like interface with command history
- Requires appropriate user permissions
- PSR-4 compliant code structure

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- WP-CLI installed and accessible
- User with 'manage_options' capability

## Installation

1. Download or clone this repository to your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/yourusername/wp-cli-shell.git
   ```

2. Install dependencies using Composer:
   ```bash
   cd wp-cli-shell
   composer install
   ```

3. Activate the plugin through the WordPress admin panel or using WP-CLI:
   ```bash
   wp plugin activate wp-cli-shell
   ```

## Usage

1. After activation, you'll find a new menu item "WP Shell" in your WordPress admin panel.
2. Click on "WP Shell" to access the command interface.
3. Enter a WP-CLI command in the text area (must start with 'wp').
4. Click "Execute Command" or press Enter to run the command.
5. The command output will be displayed in the terminal-like interface above.

## Security

- Only users with 'manage_options' capability can access the interface
- All commands are validated before execution
- AJAX requests are protected with nonces
- Input is sanitized before processing

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please open an issue in the GitHub repository. 