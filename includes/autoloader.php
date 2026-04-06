<?php
/**
 * Custom autoloader file for Patchwing plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

spl_autoload_register( function( $class ) {

	// Only handle classes in Patchwing namespace.
	$namespace = 'Patchwing\\';
	$namespace_len = strlen( $namespace );

	if ( strncmp( $class, $namespace, $namespace_len ) !== 0 ) {
		return;
	}

	// Strip the namespace prefix to get the bare class name.
	$class_name = substr( $class, $namespace_len );

	// Convert to WordPress filename convention
	$filename = 'class-' . str_replace( '_', '-', strtolower( $class_name ) ) . '.php';

	$filepath = PATCHWING_PLUGIN_DIRECTORY . 'includes/' . $filename;

	if ( file_exists( $filepath ) ) {
		require_once $filepath;
	}
} );
