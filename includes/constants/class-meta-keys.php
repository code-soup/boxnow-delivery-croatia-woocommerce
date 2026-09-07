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
	const LOCKER_ID       = '_codesoup_boxnow_locker_id';
	const LOCKER_NAME     = '_codesoup_boxnow_locker_name';
	const LOCKER_ADDRESS  = '_codesoup_boxnow_locker_address';
	const LOCKER_CITY     = '_codesoup_boxnow_locker_city';
	const LOCKER_POSTCODE = '_codesoup_boxnow_locker_postcode';
	const LOCKER_COUNTRY  = '_codesoup_boxnow_locker_country';
	const LOCKER_NOTE     = '_codesoup_boxnow_locker_note';
	const LOCKER_IMAGE    = '_codesoup_boxnow_locker_image';

	/**
	 * Warehouse Selection
	 */
	const WAREHOUSE = '_codesoup_boxnow_warehouse';

	/**
	 * Parcel Meta Keys
	 */
	const PARCEL_ID  = '_codesoup_boxnow_parcel_id';  // Single parcel ID (automatic creation)
	const PARCEL_IDS = '_codesoup_boxnow_parcel_ids'; // Array of parcel IDs (manual creation)

	/**
	 * Voucher Flags
	 */
	const VOUCHER_CREATED  = '_codesoup_boxnow_voucher_created';  // Flag: 'yes' or empty (auto-creation)
	const VOUCHERS_CREATED = '_codesoup_boxnow_vouchers_created'; // Flag: 1 or empty (manual creation)

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
