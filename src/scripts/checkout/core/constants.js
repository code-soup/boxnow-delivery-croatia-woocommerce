/**
 * Global Constants
 * Centralized constants used across the checkout application
 */

/**
 * HTML Element IDs
 */
export const ElementIDs = {
	// Locker Details
	LOCKER_DETAILS: 'boxnow-selected-locker-details',
	LOCKER_DETAILS_BLOCKS: 'boxnow-selected-locker-details-blocks',
	LOCKER_INFO: 'boxnow-locker-info',
	
	// Buttons
	DELIVERY_BUTTON: 'boxnow-delivery-button',
	DELIVERY_BUTTON_BLOCKS: 'boxnow-delivery-button-blocks',
	
	// Popup
	POPUP_OVERLAY: 'boxnow-delivery-overlay',
	
	// Embedded
	EMBEDDED_MAP: 'boxnow-delivery-embedded-map',
	
	// Hidden Inputs (WordPress meta keys use underscores)
	LOCKER_ID_INPUT: '_boxnow_locker_id',
	LOCKER_DATA_INPUT: 'boxnow-selected-locker-input',
	
	// WordPress Form Fields (use underscores per WP convention)
	SHIPPING_ADDRESS_1: 'shipping_address_1',
	SHIPPING_ADDRESS_2: 'shipping_address_2',
	SHIPPING_CITY: 'shipping_city',
	SHIPPING_POSTCODE: 'shipping_postcode',
	SHIPPING_COUNTRY: 'shipping_country',
	SHIPPING_STATE: 'shipping_state',
	BILLING_COUNTRY: 'billing_country',
	BILLING_POSTCODE: 'billing_postcode',
	SHIP_TO_DIFFERENT_CHECKBOX: 'ship-to-different-address-checkbox',
	PLACE_ORDER_BUTTON: 'place_order',
	BLOCKS_SHIPPING_COUNTRY: 'shipping-country',
};

/**
 * CSS Classes
 */
export const CSSClasses = {
	// Buttons (match PHP-generated classes with dashes)
	BUTTON_BASE: 'box-now-delivery-button',
	BUTTON_INLINE: 'box-now-delivery-button-inline',
	BUTTON_CHECKBOX: 'box-now-delivery-button-checkbox',
	BUTTON_AUTO_SELECT: 'box-now-delivery-button-auto-select',
	
	// Locker Details
	LOCKER_DETAILS: 'boxnow-selected-locker-details',
	LOCKER_TITLE: 'boxnow-locker-title',
	LOCKER_DETAIL: 'boxnow-locker-detail',
	LOCKER_NOTE: 'boxnow-locker-note',
	LOCKER_IMAGE: 'boxnow-locker-image',
	CLEAR_LOCKER: 'boxnow-clear-locker',
	
	// Popup
	POPUP_OVERLAY: 'boxnow-popup-overlay',
	POPUP_IFRAME_FIXED: 'boxnow-popup-iframe-fixed',
	POPUP_IFRAME_EMBEDDED: 'boxnow-popup-iframe-embedded',
	
	// Embedded
	EMBEDDED_CONTAINER: 'boxnow-embedded-container',
	EMBEDDED_DETAILS: 'boxnow-embedded-details',
	EMBEDDED_MAP_SHORTCODE: 'boxnow-delivery-embedded-map-container',
	AUTO_MAP: 'boxnow-auto-map',
	LOCKER_INFO_CONTAINER: 'boxnow-locker-info-container',
	LOCKER_DETAILS_CONTAINER: 'boxnow-locker-details-container',
	
	// Utility
	HIDDEN: 'hidden',
	VISIBLE: 'visible',
	VISIBLE_INLINE: 'visible-inline',
	
	// WordPress/WooCommerce
	SHIPPING_ADDRESS_WRAPPER: 'shipping_address',
};

/**
 * DOM Selectors
 */
export const Selectors = {
	// Shipping Method
	SHIPPING_METHOD_RADIO: 'input[type="radio"][name="shipping_method[0]"]',
	SHIPPING_METHOD_RADIO_CHECKED: 'input[type="radio"][name="shipping_method[0]"]:checked',
	SHIPPING_METHOD_HIDDEN: 'input[type="hidden"][name="shipping_method[0]"]',
	BOXNOW_METHOD_RADIO: 'input[name^="shipping_method"][value*="codesoup_box_now_delivery"]',
	BOXNOW_METHOD_LABEL: 'label[for^="shipping_method"][for*="codesoup_box_now_delivery"]',
	
	// Blocks
	BLOCKS_SHIPPING_RADIO: 'input[name^="radio-control-0"]',
	
	// Widget
	WIDGET_IFRAME: 'iframe[src*="widget-v5.boxnow."]',
	
	// Wrappers
	SHIPPING_ADDRESS_WRAPPER: '.shipping_address',
};

/**
 * LocalStorage Keys
 */
export const StorageKeys = {
	SELECTED_LOCKER: 'box_now_selected_locker',
	WAREHOUSE: 'boxnow_warehouse',
};

/**
 * AJAX Actions (WordPress convention uses underscores)
 * These must match the PHP action names registered in class-checkout-handler.php
 */
export const AjaxActions = {
	SAVE_LOCKER: 'codesoup_bndp_save_boxnow_locker',
	REMOVE_LOCKER: 'codesoup_bndp_clear_boxnow_locker',
};

/**
 * Shipping Method IDs
 */
export const ShippingMethods = {
	BOXNOW_ID: 'codesoup_box_now_delivery',
	BOXNOW_PARTIAL: 'box_now_delivery',
};

/**
 * WooCommerce Blocks
 */
export const BlocksConstants = {
	EXTENSION_NAMESPACE: 'box-now-delivery',
	LOCKER_ID_KEY: '_boxnow_locker_id',
	ERROR_CODE: 'box-now-delivery-locker-not-selected',
};

/**
 * Timeout Constants (milliseconds)
 */
export const TimeoutConstants = {
	SHIPPING_SELECT_DEBOUNCE: 500,
	ADDRESS_POPULATE_DELAY: 200,
	ADDRESS_POPULATE_RESET: 1000,
	REGISTRY_POLL_INTERVAL: 100,
	BLOCKS_RENDER_DELAY: 800,
	CHECKOUT_UPDATE_DEBOUNCE: 100,
};

/**
 * Custom Event Names
 */
export const EventNames = {
	UPDATE_CHECKOUT: 'update_checkout',
	UPDATED_CHECKOUT: 'updated_checkout',
};
