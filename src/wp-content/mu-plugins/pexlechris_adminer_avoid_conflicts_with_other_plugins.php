<?php
/**
 * Plugin Name: pexlechris_adminer_avoid_conflicts_with_other_plugins.php
 * Description: This mu plugin disables on the fly all other plugins to avoid conflicts.
 * 				Version is controlled by option pexlechris_adminer_mu_plugin_version.
 * 				Delete option to reinstall, or set option to 0 to ignore version updates forever
 *  Version: 3.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}



add_filter( 'option_active_plugins', function( $plugins ){

	$plugin_constructor_file = 'pexlechris-adminer/pexlechris-adminer.php';

	if( !in_array( $plugin_constructor_file, $plugins ) ){
		return $plugins;
	}

	include_once WP_PLUGIN_DIR . '/pexlechris-adminer/pluggable-functions.php';

	// Only disable all other plugins, when WP Adminer will be shown
	if( pexlechris_is_current_url_the_wp_adminer_url() && have_current_user_access_to_pexlechris_adminer() ){
		return [$plugin_constructor_file];
	}

	return $plugins;
} );