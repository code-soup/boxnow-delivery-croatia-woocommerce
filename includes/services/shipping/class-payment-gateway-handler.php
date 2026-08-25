<?php
/**
 * Payment Gateway Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Shipping;

use WC_Shipping_Zones;
use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Helpers\Order_Helper;
use CodeSoup\BoxNow\Constants\Option_Keys;
use CodeSoup\BoxNow\Services\Shipping\Pay_At_Locker_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Handles payment gateway filtering and modifications for BoxNow shipping.
 */
class Payment_Gateway_Handler {

	/**
	 * Hooker instance.
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

	/**
	 * Constructor.
	 *
	 * @param Hooker $hooker Hooker instance.
	 */
	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		$this->hooker->add_filter( 'woocommerce_available_payment_gateways', $this, 'filter_payment_gateways', 10, 1 );
		$this->hooker->add_filter( 'woocommerce_available_payment_gateways', $this, 'add_pay_at_locker_gateway', 20, 1 );
		$this->hooker->add_filter( 'woocommerce_gateway_description', $this, 'modify_cod_description', 10, 2 );
	}

	/**
	 * Filter available payment gateways based on BoxNow shipping selection.
	 *
	 * @param array $available_gateways Available payment gateways.
	 * @return array
	 */
	public function filter_payment_gateways( $available_gateways ) {
		if ( is_admin() || ! $this->is_box_now_selected() ) {
			return $available_gateways;
		}

		$allowed_methods = get_option( Option_Keys::ALLOWED_PAYMENT_METHODS, array() );

		if ( ! is_array( $allowed_methods ) || empty( $allowed_methods ) ) {
			return $available_gateways;
		}

		$filtered_gateways = array();

		foreach ( $available_gateways as $gateway_id => $gateway ) {
			if ( in_array( $gateway_id, $allowed_methods, true ) ) {
				$filtered_gateways[ $gateway_id ] = $gateway;
			}
		}

		return $filtered_gateways;
	}

	/**
	 * Modify COD description for Box Now Delivery.
	 *
	 * @param string $description Payment gateway description.
	 * @param string $payment_id  Payment gateway ID.
	 * @return string
	 */
	public function modify_cod_description( $description, $payment_id ) {
		if ( 'cod' !== $payment_id ) {
			return $description;
		}

		if ( ! $this->is_box_now_selected() ) {
			return $description;
		}

		$shipping_zone   = $this->get_current_shipping_zone();
		$shipping_methods = $shipping_zone ? $shipping_zone->get_shipping_methods() : array();

		foreach ( $shipping_methods as $shipping_method ) {
			if ( in_array( $shipping_method->id, array( 'codesoup_box_now_delivery', 'box_now_delivery' ), true ) ) {
				$enable_custom = $shipping_method->get_option( 'enable_custom_cod_description' );
				$custom_desc   = $shipping_method->get_option( 'custom_cod_description' );

				if ( 'yes' === $enable_custom && ! empty( $custom_desc ) ) {
					return $custom_desc;
				}
			}
		}

		return $description;
	}

	/**
	 * Add pay-at-locker gateway when BoxNow shipping is selected.
	 *
	 * @param array $available_gateways Available payment gateways.
	 * @return array
	 */
	public function add_pay_at_locker_gateway( $available_gateways ) {
		if ( is_admin() || ! $this->is_box_now_selected() ) {
			return $available_gateways;
		}

		$enable_pay_at_locker = get_option( Option_Keys::ENABLE_PAY_AT_LOCKER, 'no' );

		if ( 'yes' !== $enable_pay_at_locker ) {
			return $available_gateways;
		}

		$gateway_title = get_option(
			Option_Keys::PAY_AT_LOCKER_TITLE,
			__( 'Pay with Card at BoxNow', 'codesoup-woo-boxnow' )
		);

		$available_gateways['boxnow_pay_at_locker'] = new Pay_At_Locker_Gateway( $gateway_title );

		return $available_gateways;
	}

	/**
	 * Check if Box Now Delivery is selected.
	 *
	 * @return bool
	 */
	private function is_box_now_selected() {
		return Order_Helper::is_box_now_selected();
	}

	/**
	 * Get current shipping zone.
	 *
	 * @return \WC_Shipping_Zone|null
	 */
	private function get_current_shipping_zone() {
		if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
			return null;
		}

		$package = array(
			'destination' => array(
				'country'  => WC()->customer->get_shipping_country(),
				'state'    => WC()->customer->get_shipping_state(),
				'postcode' => WC()->customer->get_shipping_postcode(),
			),
		);

		return WC_Shipping_Zones::get_zone_matching_package( $package );
	}
}
