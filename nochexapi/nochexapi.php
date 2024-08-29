<?php

/*
Plugin Name: Nochex API Widget
Plugin URI: https://github.com/NochexDevTeam/Woocommerce-API-Widget
Description: Accept all major credit / debit cards directly on your WooCommerce site using the Nochex API Widget.
Version: 3.4
Author: Nochex Ltd
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'NOCHEXAPI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Currently plugin version.
 */
//define( 'nochexapi_VERSION', '5.2.0' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-nochexapi-constants.php';

/**
 * WooCommerce not activated admin notice
 *
 * @since    5.2.0
 */
function nochexapi_install_wc_notice(){
	?>
	<div class="error">
		<p><?php _e( 'Nochex for WooCommerce is enabled but not effective. It requires WooCommerce in order to work.', 'nochexapi' ); ?></p>
	</div>
	<?php
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    5.2.0
 */
function run_nochexwidget() {

	$plugin = new nochexapi();
	$plugin->run();

}

/**
 * Check if WooCommerce is activated
 *
 * @since    5.2.0
 */
function run_nochexwidget_init(){
	if ( function_exists( 'WC' ) ) {
		/**
         * The core plugin class that is used to define internationalization,
         * admin-specific hooks, and public-facing site hooks.
         */
        require plugin_dir_path( __FILE__ ) . 'includes/class-nochexapi.php';
		run_nochexwidget();
	}
	else{
		add_action( 'admin_notices', 'nochex_install_wcAPI_notice' );
	}
}

if ( function_exists( 'woocommerce_nochex_init' ) or function_exists( 'run_nochexapi' ) ) {	 
	add_action( 'admin_notices', 'nochex_install_ncxAPI_notice' );	
	add_action( 'admin_init', 'deactivate_apiplugin_now' );
} else {
	add_action('plugins_loaded','run_nochexwidget_init');
}

function deactivate_apiplugin_now() {
    if ( is_plugin_active('NochexWidget/nochexapi.php') ) {
        deactivate_plugins('NochexWidget/nochexapi.php');
    }
}


/**
 * WooCommerce not activated admin notice
 *
 * @since    5.2.0
 */
function nochex_install_wcAPI_notice(){
	?>
	<div class="error">
		<p><?php _e( 'WooCommerce is Required', 'nochexapi' ); ?></p>
	</div>
	<?php
}

/**
 * WooCommerce not activated admin notice
 *
 * @since    5.2.0
 */
function nochex_install_ncxAPI_notice(){
	?>
	<div class="error">
		<p><?php _e( 'You can only have 1 Nochex integration on your website, please deactivate all other Nochex plugins first before enabling. If you are having integration issues we encourage you to contact us at support.nochex.com', 'nochexapi' ); ?></p>
	</div>
	<?php
}

