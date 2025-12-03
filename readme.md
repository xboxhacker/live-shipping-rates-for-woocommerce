# Debug Log - Manager Tool

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/debug-log-config-tool)](https://wordpress.org/plugins/debug-log-config-tool/)
[![WordPress Plugin Rating](https://img.shields.io/wordpress/plugin/rating/debug-log-config-tool)](https://wordpress.org/plugins/debug-log-config-tool/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/debug-log-config-tool)](https://wordpress.org/plugins/debug-log-config-tool/)

**Contributors:** pyrobd
**Tags:** debug, debug log, developer, tools
**Requires at least:** 5.6
**Tested up to:** 6.6.1
**Stable tag:** 2.0.0
**Requires PHP:** 5.6
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

The "Debug Log Config Tool" simplifies WordPress debugging by providing a powerful interface to manage debug logs, toggle settings, and analyze issues directly from your dashboard.

## Description

A comprehensive debugging toolkit for WordPress developers and site administrators. This plugin gives you complete control over WordPress debugging without editing wp-config.php files or using FTP.

### 🎥 Quick Demo

[youtube https://youtu.be/moJPyyVfm3A]

### ✨ Key Features

- **WP-CLI Style Terminal**: Execute WordPress commands directly from your browser with syntax highlighting and auto-completion
- **Database Tools**: Run SQL queries, view table structures, and optimize your database (super admin only)
- **Debug Constants Manager**: Toggle all WordPress debug constants with a single click
- **Log Viewer**: View, filter, and analyze debug logs with syntax highlighting and error categorization
- **Query Inspector**: Examine database queries with SAVEQUERIES support
- **Email Notifications**: Get alerts when new errors appear in your logs
- **Safe Mode**: Quickly disable all plugins except selected ones for troubleshooting
- **Custom Log Paths**: Set custom log file locations with filter support

### 🔧 Debug Constants Available

| Constant | Default Value | Description |
|----------|---------------|-------------|
| **WP_DEBUG** | true | Enables WordPress debug mode |
| **WP_DEBUG_LOG** | true | Saves all errors to a debug.log file |
| **SCRIPT_DEBUG** | false | Uses development versions of core JS and CSS files |
| **WP_DEBUG_DISPLAY** | false | Controls whether debug messages display on screen |
| **SAVEQUERIES** | false | Saves database queries for analysis |

### 🛠️ Developer Tools

- **Terminal Commands**: Use WP-CLI style commands like `wp core version` or `wp plugin list`
- **Database Explorer**: Run SELECT queries and view results in a formatted table
- **Stack Trace Analysis**: Visualize error stack traces for easier debugging
- **Hook Inspector**: View all registered hooks and their callbacks
- **Environment Detection**: Using Laravel Mix to automatically hide development features in production

> **Developer API**: Apply custom filters like `apply_filters('wp_debuglog_log_file_path', $file);` to extend functionality

Please note: Constant values will be restored on plugin deactivation as it was before activating the plugin.

### 🚀 Improvements

We're constantly working to improve the Debug Log Config Tool. Here are some features we're planning to add in future releases:

#### Developer Tools
- **Code Snippets Runner**: Securely run PHP code snippets for testing (admin only)
- **Theme Template Debugger**: See which template files are being used on each page
- **Shortcode Analyzer**: Debug shortcodes and their rendered output
- **Cron Job Manager**: View, add, edit, and delete WordPress cron jobs
- **Transients Manager**: View and clean up transients in the database

#### Performance Tools
- **Memory Usage Profiling**: Track memory usage across different parts of your site
- **Page Load Time Analysis**: Measure and optimize page load performance
- **Asset Loading Monitor**: See which scripts and styles are loaded on each page

#### Enhanced Debugging
- **REST API Debugger**: Monitor and log REST API requests and responses
- **AJAX Request Logger**: Track AJAX requests for easier debugging
- **Conditional Debugging**: Enable debug logging only for specific pages or conditions

#### UI Improvements
- **Dark Mode**: Dark theme for the debugging interface
- **Customizable Dashboard**: Personalize which debug widgets appear
- **Export/Import Settings**: Save and load your debug configurations

Want to contribute or suggest features? Visit our [GitHub repository](https://github.com/nkb-bd/debug-log-config-tool).


## Installation

1. Upload the plugin files to the `/wp-content/plugins/debug-log-config-tool` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Go to Tools-> Debug Logs screen to see the debug logs or access it from the top navbar.


## Frequently Asked Questions

### Do I need file manager/ftp or modify wp-config.php file?

No, just activate the plugin and turn off/on debug mode from plugin settings

### Can I see full debug in dashboard?

Yes you can see a simple log in dashboard widget and nicely formatted view in the plugin

### What does safe mode do?
Safe mode will deactivate all the plugin except the selected one. When you turn safe mode off it will restore all the previous activated plugin.

## Screenshots
1. **Plugin Settings**
2. **Debug Log**

## Changelog
### 1.0.0
- Initial Version

### 1.4.4
- Fixed Refresh Log
- Added dashboard widget

### 1.4
- Clean UI
- Refresh Log
- Email Notification

### 1.4.2
- New Constants
- Removed database dependency

### 1.4.5
- Fixed refresh

### 1.5
- Fixed Vulnerability of debug log file. Generating random file for debug.
- Added a new safe mode which will turn off all plugins excluding selected ones.

### 1.5.2
- Added query logs

### 1.5.3
- Fix footer text on all page

### 2.0.0
- Added WP-CLI style command structure in terminal (e.g., `wp core version` instead of `wp-version`)
- Added database commands with WP-CLI syntax (`wp db query`, `wp db tables`, etc.)
- Added terminal settings page to enable/disable terminal and database features
- Added super admin restriction for database commands
- Added support for SQL queries with proper security measures
- Added stack trace visualization for better error analysis
- Enhanced developer profile in support page
- Improved UI for support and notification pages
- Added command auto-completion for WP-CLI style commands
- Added support for colon syntax in commands (e.g., `wp:db:query` instead of `wp db query`)
- Added environment detection to hide development features in production
- Reorganized terminal commands into logical categories (core, plugin, theme, db, etc.)
- Updated help command to show commands by category with organized sections
- Improved error messages for better debugging and troubleshooting
- Enhanced security for terminal commands (preventing SQL injection, restricting destructive commands)
- Fixed issue with SQL query execution where quotes were included in the query
- Fixed terminal command parsing for complex arguments
- Fixed case sensitivity issues in command validation

