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
	 * Current shipping method ID.
	 */
	const CURRENT = 'codesoup_box_now_delivery';

	/**
	 * Legacy shipping method ID (for backwards compatibility).
	 */
	const LEGACY = 'box_now_delivery';

	/**
	 * Get all valid shipping method IDs.
	 *
	 * @return array<string>
	 */
	public static function get_all(): array {
		return array(
			self::CURRENT,
			self::LEGACY,
		);
	}

	/**
	 * Check if a given method ID is a Box Now method.
	 *
	 * @param string $method_id Method ID to check.
	 * @return bool
	 */
	public static function is_box_now_method( string $method_id ): bool {
		foreach ( self::get_all() as $valid_id ) {
			if ( strpos( $method_id, $valid_id ) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
