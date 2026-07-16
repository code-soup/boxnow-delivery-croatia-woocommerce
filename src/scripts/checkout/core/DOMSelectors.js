/**
 * Centralized DOM selectors to reduce duplication.
 */
export const DOMSelectors = {
	// Shipping method selectors
	SHIPPING_METHOD_RADIO: 'input[type="radio"][name="shipping_method[0]"]',
	SHIPPING_METHOD_RADIO_CHECKED: 'input[type="radio"][name="shipping_method[0]"]:checked',
	SHIPPING_METHOD_HIDDEN: 'input[type="hidden"][name="shipping_method[0]"]',
	BOXNOW_METHOD_RADIO: 'input[name^="shipping_method"][value*="codesoup_box_now_delivery"]',
	BOXNOW_METHOD_LABEL: 'label[for^="shipping_method"][for*="codesoup_box_now_delivery"]',
	
	// Address field IDs
	SHIPPING_ADDRESS_1: 'shipping_address_1',
	SHIPPING_ADDRESS_2: 'shipping_address_2',
	SHIPPING_CITY: 'shipping_city',
	SHIPPING_POSTCODE: 'shipping_postcode',
	SHIPPING_COUNTRY: 'shipping_country',
	SHIPPING_STATE: 'shipping_state',
	
	BILLING_COUNTRY: 'billing_country',
	BILLING_POSTCODE: 'billing_postcode',
	
	// Checkout elements
	SHIP_TO_DIFFERENT_CHECKBOX: 'ship-to-different-address-checkbox',
	PLACE_ORDER_BUTTON: 'place_order',
	SHIPPING_ADDRESS_WRAPPER: '.shipping_address',
	
	// Blocks-specific
	BLOCKS_SHIPPING_RADIO: 'input[name^="radio-control-0"]',
	BLOCKS_SHIPPING_COUNTRY: 'shipping-country',
	
	// Widget iframes
	WIDGET_IFRAME: 'iframe[src*="widget-v5.boxnow."]',
};

/**
 * Timeout constants in milliseconds.
 */
export const Timeouts = {
	SHIPPING_SELECT_DEBOUNCE: 500,
	ADDRESS_POPULATE_DELAY: 200,
	ADDRESS_POPULATE_RESET: 1000,
	REGISTRY_POLL_INTERVAL: 100,
	BLOCKS_RENDER_DELAY: 800,
	CHECKOUT_UPDATE_DEBOUNCE: 100,
};

/**
 * Event names used across the application.
 */
export const CustomEvents = {
	UPDATE_CHECKOUT: 'update_checkout',
	UPDATED_CHECKOUT: 'updated_checkout',
};
