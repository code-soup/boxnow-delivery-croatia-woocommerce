<?php
/**
 * Session Keys Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce session keys used throughout the plugin.
 */
final class Session_Keys {

	/**
	 * Locker Session Keys
	 */
	const LOCKER_ID       = 'boxnow_locker_id';
	const LOCKER_NAME     = 'boxnow_locker_name';
	const LOCKER_ADDRESS  = 'boxnow_locker_address';
	const LOCKER_CITY     = 'boxnow_locker_city';
	const LOCKER_POSTCODE = 'boxnow_locker_postcode';
	const LOCKER_COUNTRY  = 'boxnow_locker_country';
	const LOCKER_NOTE     = 'boxnow_locker_note';
	const LOCKER_IMAGE    = 'boxnow_locker_image';

	/**
	 * Warehouse Selection
	 */
	const WAREHOUSE = 'boxnow_warehouse';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
