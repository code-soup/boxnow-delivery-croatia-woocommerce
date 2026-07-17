<?php
/**
 * Order Helper
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Helpers;

use function CodeSoup\BoxNow\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Helper functions for order operations.
 *
 * @deprecated Use Order_Service instead. Will be removed in future version.
 */
class Order_Helper {

	/**
	 * Check if order uses Box Now delivery.
	 *
	 * @deprecated Use Order_Service::is_box_now_order() instead.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public static function is_box_now_order( $order ) {
		return plugin()->get( 'order_service' )->is_box_now_order( $order );
	}

	/**
	 * Check if Box Now delivery is currently selected.
	 *
	 * @deprecated Use Order_Service::is_box_now_selected() instead.
	 *
	 * @return bool
	 */
	public static function is_box_now_selected() {
		return plugin()->get( 'order_service' )->is_box_now_selected();
	}
}
