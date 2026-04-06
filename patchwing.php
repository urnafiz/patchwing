<?php
/**
 * Plugin Name: 		Patchwing – Essential Debug Tools
 * Plugin URI: 			https://wordpress.org/plugins/patchwing/
 * Description: 		Patchwing is a lightweight, powerful tool designed to make WordPress debugging simple and effective for site administrators. Instead of wasting time digging through complicated configuration files, Patchwing provides clear debug data right when you need it. Whether you are fixing the infamous white screen of death or working to boost site performance, Patchwing helps you debug issues quickly and keep your WordPress site running smoothly.
 * Author: 				Nafiz
 * Author URI: 			https://profiles.wordpress.org/urnafiz/
 * Version: 			1.0.1
 * Requires PHP: 		7.4
 * Requires at least: 	5.9
 * Tested up to: 		6.9
 * License: 			GPL v2 or later
 * License URI: 		https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 		patchwing
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

// Set plugin version.
define( 'PATCHWING_PLUGIN_VERSION', '1.0.1' );

// Set plugin file.
define( 'PATCHWING_PLUGIN_FILE', __FILE__ );

// Set absolute path for the plugin.
define( 'PATCHWING_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ) );

// Set plugin URL root.
define( 'PATCHWING_PLUGIN_URL', plugins_url( '/', __FILE__ ) );

// Register custom autoloader.
require_once PATCHWING_PLUGIN_DIRECTORY . 'includes/autoloader.php';

add_action(
	'plugins_loaded',
	function() {
		// Initialize our plugin.
		new \Patchwing\Core();
	}
);
