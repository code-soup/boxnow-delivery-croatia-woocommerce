<?php
/**
 * Meta Keys Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Post/Order meta keys used throughout the plugin.
 */
final class Meta_Keys {

	/**
	 * Locker Information Meta Keys
	 */
	const LOCKER_ID       = '_boxnow_locker_id';
	const LOCKER_NAME     = '_boxnow_locker_name';
	const LOCKER_ADDRESS  = '_boxnow_locker_address';
	const LOCKER_CITY     = '_boxnow_locker_city';
	const LOCKER_POSTCODE = '_boxnow_locker_postcode';
	const LOCKER_COUNTRY  = '_boxnow_locker_country';
	const LOCKER_NOTE     = '_boxnow_locker_note';
	const LOCKER_IMAGE    = '_boxnow_locker_image';

	/**
	 * Warehouse Selection
	 */
	const WAREHOUSE = '_selected_warehouse';

	/**
	 * Parcel Meta Keys
	 */
	const PARCEL_ID  = '_boxnow_parcel_id';  // Single parcel ID (automatic creation)
	const PARCEL_IDS = '_boxnow_parcel_ids'; // Array of parcel IDs (manual creation)

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
