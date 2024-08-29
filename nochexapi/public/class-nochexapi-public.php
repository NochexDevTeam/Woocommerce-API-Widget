<?php
 
use Nochexapi\WC_Nochexapi_Constants AS NOCHEXAPI;
use WC_Payment_Gateway_Nochexapi AS NochexAPI_Cards;

class Nochexapi_Public {

	/**
	 *
	 * @since    5.2.0
	 * @access   private
	 * @var      string    $plugin_name
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    5.2.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    5.2.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    5.2.0
	 */
	public function enqueue_styles() {

		/**
		 *
		 * An instance of this class should be passed to the run() function 
		 * 
		 */

		wp_enqueue_style( 
            $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'css/nochexapi-public.css', 
            array(), 
            $this->version, 
            'all' 
        );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    5.2.0
	 */
	public function enqueue_scripts() {

		/**
		 *
		 * An instance of this class should be passed to the run() function
		 *
		 */

		wp_enqueue_script( $this->plugin_name, 
            plugin_dir_url( __FILE__ ) . 'js/nochexapi-public.js', 
            array( 'jquery' ), 
            $this->version, 
            false 
        );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    5.2.0
	 */
	public function add_new_woocommerce_payment_gateways( $gateways ) {

		$gateways[] = 'WC_Payment_Gateway_Nochexapi';
	    return $gateways;

	}
    
    public function exclude_from_siteground_script_minification( $exclude_list ){
        $exclude_list[] = NOCHEXAPI::GLOBAL_PREFIX . '_cards';
        return $exclude_list;
    }
}
