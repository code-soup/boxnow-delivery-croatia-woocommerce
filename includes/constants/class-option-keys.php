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
	const API_URL        = 'boxnow_api_url';
	const CLIENT_ID      = 'boxnow_client_id';
	const CLIENT_SECRET  = 'boxnow_client_secret';
	const PARTNER_ID     = 'boxnow_partner_id';

	/**
	 * Widget Settings
	 */
	const DISPLAY_MODE       = 'boxnow_display_mode';
	const BUTTON_COLOR       = 'boxnow_button_color';
	const BUTTON_TEXT        = 'boxnow_button_text';
	const BUTTON_POSITION    = 'boxnow_button_position';
	const ENABLE_GEOLOCATION = 'boxnow_enable_geolocation';

	/**
	 * Locker Messages
	 */
	const LOCKER_NOT_SELECTED_MESSAGE = 'boxnow_locker_not_selected_message';

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
