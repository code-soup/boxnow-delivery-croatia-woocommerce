/**
 * Manages shipping method detection and selection.
 * No jQuery - uses native DOM APIs.
 */
import { DOMSelectors, Timeouts } from '../core/index.js';

export class ShippingService {
	/**
	 * Shipping method identifier
	 */
	static BOXNOW_METHOD_ID = 'codesoup_box_now_delivery';

	/**
	 * @param {LockerState} state - Application state
	 * @param {EventBus} eventBus - Event bus for notifications
	 */
	constructor(state, eventBus) {
		this.state = state;
		this.eventBus = eventBus;
	}

	/**
	 * Check if BoxNow shipping method is currently selected
	 * @returns {boolean}
	 */
	isBoxNowSelected() {
		// Check radio input
		const radio = document.querySelector(DOMSelectors.SHIPPING_METHOD_RADIO_CHECKED);

		if (radio && radio.value.includes(ShippingService.BOXNOW_METHOD_ID)) {
			return true;
		}

		// Fallback: check hidden input (some themes/checkout flows)
		const hidden = document.querySelector(DOMSelectors.SHIPPING_METHOD_HIDDEN);

		if (hidden && hidden.value.includes(ShippingService.BOXNOW_METHOD_ID)) {
			return true;
		}

		return false;
	}

	/**
	 * Get the selected shipping method value
	 * @returns {string|null}
	 */
	getSelectedMethod() {
		const radio = document.querySelector(DOMSelectors.SHIPPING_METHOD_RADIO_CHECKED);

		if (radio) {
			return radio.value;
		}

		const hidden = document.querySelector(DOMSelectors.SHIPPING_METHOD_HIDDEN);

		return hidden ? hidden.value : null;
	}

	/**
	 * Select BoxNow shipping method programmatically
	 * @returns {boolean} True if selection succeeded
	 */
	selectBoxNow() {
		// Prevent concurrent selections
		if (this.state.get('isSelectingShipping')) {
			return false;
		}

		const boxNowRadio = document.querySelector(DOMSelectors.BOXNOW_METHOD_RADIO);

		if (!boxNowRadio || boxNowRadio.checked) {
			return false;
		}

		// Set flag to prevent loops
		this.state.set('isSelectingShipping', true);

		// Trigger selection
		boxNowRadio.checked = true;
		boxNowRadio.dispatchEvent(new Event('change', { bubbles: true }));

		// Reset flag after delay
		setTimeout(() => {
			this.state.set('isSelectingShipping', false);
		}, Timeouts.SHIPPING_SELECT_DEBOUNCE);

		this.eventBus.emit('shipping:boxnow-selected');
		
		return true;
	}

	/**
	 * Get user's selected country (shipping or billing)
	 * @returns {string|null}
	 */
	getUserCountry() {
		const shipToDifferent = document.getElementById(DOMSelectors.SHIP_TO_DIFFERENT_CHECKBOX);
		
		// Use shipping country if "ship to different address" is checked
		if (shipToDifferent && shipToDifferent.checked) {
			// Try select element
			const shippingSelect = document.querySelector('select[name="shipping_country"]');
			if (shippingSelect) {
				return shippingSelect.value;
			}
			
			// Try hidden input
			const shippingInput = document.querySelector('input[name="shipping_country"]');
			if (shippingInput) {
				return shippingInput.value;
			}
		}

		// Fall back to billing country
		const billingSelect = document.querySelector('select[name="billing_country"]');
		if (billingSelect) {
			return billingSelect.value;
		}

		const billingInput = document.querySelector('input[name="billing_country"]');
		if (billingInput) {
			return billingInput.value;
		}

		return null;
	}

	/**
	 * Get BoxNow shipping method radio element
	 * @returns {HTMLElement|null}
	 */
	getBoxNowRadio() {
		return document.querySelector(DOMSelectors.BOXNOW_METHOD_RADIO);
	}

	/**
	 * Check if BoxNow shipping method is available
	 * @returns {boolean}
	 */
	isBoxNowAvailable() {
		return this.getBoxNowRadio() !== null;
	}
}
