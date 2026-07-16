<?php
/**
 * Order Service
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services;

use CodeSoup\BoxNow\Constants\Shipping_Method_Ids;

defined( 'ABSPATH' ) || exit;

/**
 * Service for order-related operations.
 */
class Order_Service {

	/**
	 * Check if order uses Box Now delivery.
	 *
	 * Supports both new and legacy shipping method IDs.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_box_now_order( \WC_Order $order ): bool {
		foreach ( Shipping_Method_Ids::get_all() as $method_id ) {
			if ( $order->has_shipping_method( $method_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if Box Now delivery is currently selected.
	 *
	 * @return bool
	 */
	public function is_box_now_selected(): bool {
		if ( ! $this->is_wc_session_available() ) {
			return false;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! is_array( $chosen_methods ) ) {
			return false;
		}

		foreach ( $chosen_methods as $method ) {
			if ( Shipping_Method_Ids::is_box_now_method( $method ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if WooCommerce session is available.
	 *
	 * @return bool
	 */
	private function is_wc_session_available(): bool {
		return function_exists( 'WC' ) && WC()->session instanceof \WC_Session;
	}
}
