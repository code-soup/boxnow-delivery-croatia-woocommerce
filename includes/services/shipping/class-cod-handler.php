<?php
/**
 * COD Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Shipping;

use WC_Shipping_Zones;
use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Helpers\Order_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Handles Cash on Delivery modifications.
 */
class COD_Handler {

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
		$this->hooker->add_filter( 'woocommerce_gateway_description', $this, 'modify_cod_description', 10, 2 );
		$this->hooker->add_filter( 'woocommerce_gateway_title', $this, 'modify_cod_title', 10, 2 );
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
	 * Modify COD title for Box Now Delivery.
	 *
	 * @param string $title      Payment gateway title.
	 * @param string $payment_id Payment gateway ID.
	 * @return string
	 */
	public function modify_cod_title( $title, $payment_id ) {
		if ( is_admin() || 'cod' !== $payment_id ) {
			return $title;
		}

		if ( $this->is_box_now_selected() ) {
			return __( 'BOX NOW PAY ON THE GO!', 'codesoup-woo-boxnow' );
		}

		return $title;
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
