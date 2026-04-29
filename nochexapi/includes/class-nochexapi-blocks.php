<?php
/**
 * WooCommerce Blocks integration for Nochex API Widget.
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Nochexapi\WC_Nochexapi_Constants as Nochexapi_CONSTANTS;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WC_Nochexapi_Blocks extends AbstractPaymentMethodType {

	/**
	 * Must match gateway ID.
	 *
	 * @var string
	 */
	protected $name = Nochexapi_CONSTANTS::GATEWAY_ID;

	/**
	 * @var WC_Payment_Gateway_Nochexapi|null
	 */
	private $gateway = null;

	public function initialize() {
		$gateways      = WC()->payment_gateways->payment_gateways();
		$this->gateway = $gateways[ $this->name ] ?? null;
	}

	public function is_active() {
		return $this->gateway instanceof WC_Payment_Gateway_Nochexapi && $this->gateway->is_available();
	}

	public function get_supported_features() {
		return array( 'products' );
	}

	public function get_payment_method_script_handles() {
		$handle = 'nochexapi-blocks-checkout';

		wp_register_script(
			$handle,
			plugins_url( '../assets/js/nochexapi-blocks.js', __FILE__ ),
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element' ),
			Nochexapi_CONSTANTS::VERSION,
			true
		);

		return array( $handle );
	}

	public function get_payment_method_data() {
		if ( ! $this->gateway ) {
			return array();
		}

		return array(
			'title'       => $this->gateway->get_title(),
			'description' => $this->gateway->get_description(),
			'supports'    => array_values( (array) $this->gateway->supports ),
			'gatewayId'   => $this->name,
		);
	}
}
