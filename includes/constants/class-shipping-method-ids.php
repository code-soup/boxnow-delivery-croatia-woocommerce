<?php
/**
 * Shipping Method IDs Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping method identifiers.
 */
final class Shipping_Method_Ids {

	/**
	 * Shipping method ID.
	 */
	const CURRENT = 'codesoup_box_now_delivery';

	/**
	 * Check if a given method ID is a Box Now method.
	 *
	 * @param string $method_id Method ID to check.
	 * @return bool
	 */
	public static function is_box_now_method( string $method_id ): bool {
		return strpos( $method_id, self::CURRENT ) !== false;
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
