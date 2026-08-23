<?php
/**
 * Global Constants
 * 
 * Single source of truth for CSS classes, element IDs, and selectors.
 * These are synchronized with JavaScript constants.
 *
 * @package CodeSoup\BoxNow\Core
 */

namespace CodeSoup\BoxNow\Core;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

/**
 * Constants class
 */
class Constants {

	/**
	 * CSS Classes
	 */
	const CSS_CLASSES = array(
		// Buttons
		'BUTTON_BASE'        => 'box-now-delivery-button',
		'BUTTON_INLINE'      => 'box-now-delivery-button-inline',
		'BUTTON_CHECKBOX'    => 'box-now-delivery-button-checkbox',
		'BUTTON_AUTO_SELECT' => 'box-now-delivery-button-auto-select',

		// Locker Details
		'LOCKER_DETAILS'   => 'boxnow-selected-locker-details',
		'LOCKER_TITLE'     => 'boxnow-locker-title',
		'LOCKER_DETAIL'    => 'boxnow-locker-detail',
		'LOCKER_NOTE'      => 'boxnow-locker-note',
		'LOCKER_IMAGE'     => 'boxnow-locker-image',
		'CLEAR_LOCKER'     => 'boxnow-clear-locker',

		// Popup
		'POPUP_OVERLAY'        => 'boxnow-popup-overlay',
		'POPUP_IFRAME_FIXED'   => 'boxnow-popup-iframe-fixed',
		'POPUP_IFRAME_EMBEDDED' => 'boxnow-popup-iframe-embedded',

		// Embedded
		'EMBEDDED_CONTAINER'      => 'boxnow-embedded-container',
		'EMBEDDED_DETAILS'        => 'boxnow-embedded-details',
		'EMBEDDED_MAP_SHORTCODE'  => 'boxnow-delivery-embedded-map-container',
		'AUTO_MAP'                => 'boxnow-auto-map',
		'LOCKER_INFO_CONTAINER'   => 'boxnow-locker-info-container',
		'LOCKER_DETAILS_CONTAINER' => 'boxnow-locker-details-container',

		// Utility
		'HIDDEN'         => 'hidden',
		'VISIBLE'        => 'visible',
		'VISIBLE_INLINE' => 'visible-inline',

		// WordPress/WooCommerce
		'SHIPPING_ADDRESS_WRAPPER' => 'shipping_address',
	);

	/**
	 * Element IDs
	 */
	const ELEMENT_IDS = array(
		// Locker Details
		'LOCKER_DETAILS'        => 'boxnow-selected-locker-details',
		'LOCKER_DETAILS_BLOCKS' => 'boxnow-selected-locker-details-blocks',
		'LOCKER_INFO'           => 'boxnow-locker-info',

		// Buttons
		'DELIVERY_BUTTON'        => 'boxnow-delivery-button',
		'DELIVERY_BUTTON_BLOCKS' => 'boxnow-delivery-button-blocks',

		// Popup
		'POPUP_OVERLAY' => 'boxnow-delivery-overlay',

		// Embedded
		'EMBEDDED_MAP' => 'boxnow-delivery-embedded-map',

		// Hidden Inputs
		'LOCKER_ID_INPUT'   => '_boxnow_locker_id',
		'LOCKER_DATA_INPUT' => 'boxnow-selected-locker-input',
	);

	/**
	 * Get CSS class by key
	 *
	 * @param string $key Class key.
	 * @return string
	 */
	public static function get_css_class( string $key ): string {
		return self::CSS_CLASSES[ $key ] ?? '';
	}

	/**
	 * Get element ID by key
	 *
	 * @param string $key ID key.
	 * @return string
	 */
	public static function get_element_id( string $key ): string {
		return self::ELEMENT_IDS[ $key ] ?? '';
	}

	/**
	 * Get all CSS classes as array
	 *
	 * @return array
	 */
	public static function get_all_css_classes(): array {
		return self::CSS_CLASSES;
	}

	/**
	 * Get all element IDs as array
	 *
	 * @return array
	 */
	public static function get_all_element_ids(): array {
		return self::ELEMENT_IDS;
	}
}
