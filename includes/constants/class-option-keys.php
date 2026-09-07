<?php
/**
 * Option Keys Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress option keys used throughout the plugin.
 */
final class Option_Keys {

	/**
	 * API Configuration
	 */
	const API_URL        = 'codesoup_boxnow_api_url';
	const CLIENT_ID      = 'codesoup_boxnow_client_id';
	const CLIENT_SECRET  = 'codesoup_boxnow_client_secret';
	const PARTNER_ID     = 'codesoup_boxnow_partner_id';
	const WAREHOUSE_ID   = 'codesoup_boxnow_warehouse_id';

	/**
	 * Widget Settings
	 */
	const DISPLAY_MODE        = 'codesoup_boxnow_display_mode';
	const BUTTON_COLOR        = 'codesoup_boxnow_button_color';
	const BUTTON_TEXT         = 'codesoup_boxnow_button_text';
	const BUTTON_DESCRIPTION  = 'codesoup_boxnow_button_description';
	const BUTTON_POSITION     = 'codesoup_boxnow_button_position';
	const ENABLE_GEOLOCATION  = 'codesoup_boxnow_enable_geolocation';

	/**
	 * Payment Settings
	 */
	const ALLOWED_PAYMENT_METHODS = 'codesoup_boxnow_allowed_payment_methods';
	const ENABLE_PAY_AT_LOCKER    = 'codesoup_boxnow_enable_pay_at_locker';
	const PAY_AT_LOCKER_TITLE     = 'codesoup_boxnow_pay_at_locker_title';

	/**
	 * Locker Messages
	 */
	const LOCKER_NOT_SELECTED_MESSAGE = 'codesoup_boxnow_locker_not_selected_message';

	/**
	 * Voucher Settings
	 */
	const VOUCHER_OPTION        = 'codesoup_boxnow_voucher_option';
	const VOUCHER_EMAIL         = 'codesoup_boxnow_voucher_email';
	const MOBILE_NUMBER         = 'codesoup_boxnow_mobile_number';
	const ALLOW_RETURNS         = 'codesoup_boxnow_allow_returns';
	const THANKYOU_PAGE_DISPLAY = 'codesoup_boxnow_thankyou_page';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
