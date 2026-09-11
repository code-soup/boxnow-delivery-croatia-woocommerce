<?php
/**
 * Form Field Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * Form field IDs and names used in templates and JavaScript.
 */
final class Form_Fields {

	/**
	 * Voucher Metabox Fields
	 */
	const VOUCHER_ORDER_ID            = 'codesoup_boxnow_order_id';
	const VOUCHER_PARCEL_IDS          = 'codesoup_boxnow_parcel_ids';
	const VOUCHER_CREATE_ENABLED      = 'codesoup_boxnow_create_vouchers_enabled';
	const VOUCHER_MAX_VOUCHERS        = 'codesoup_boxnow_max_vouchers';
	const VOUCHER_CURRENT_COUNT       = 'codesoup_boxnow_current_count';
	const VOUCHER_QUANTITY_INPUT      = 'codesoup_boxnow_voucher_code';
	const VOUCHER_COMPARTMENT_SIZE    = 'codesoup_boxnow_compartment_size';
	const VOUCHER_SHOW_RECIPIENT_INFO = 'codesoup_boxnow_show_recipient_information';
	const VOUCHER_CREATE_BUTTON       = 'codesoup_boxnow_create_voucher';
	const VOUCHER_CANCEL_ALL_BUTTON   = 'codesoup_boxnow_cancel_all_vouchers';
	const VOUCHER_LINK_CONTAINER      = 'codesoup_boxnow_voucher_link';

	/**
	 * CSS Classes
	 */
	const VOUCHER_CONTAINER_CLASS           = 'codesoup-boxnow-vouchers';
	const VOUCHER_QUANTITY_CLASS            = 'codesoup-boxnow-voucher-quantity';
	const VOUCHER_COMPARTMENT_CHECKBOXES    = 'codesoup-boxnow-compartment-checkboxes';
	const VOUCHER_LINK_CLASS                = 'codesoup-boxnow-voucher-link';
	const VOUCHER_CANCEL_ALL_CLASS          = 'codesoup-boxnow-cancel-all-vouchers';
	const LOCKER_DETAILS_CLASS              = 'boxnow-locker-details';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
