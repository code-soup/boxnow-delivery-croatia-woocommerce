/**
 * Manages shipping address population and restoration.
 * No jQuery - uses native DOM APIs.
 */
import { DOMSelectors, Timeouts, CustomEvents } from '../core/index.js';

export class AddressService {
	/**
	 * @param {LockerState} state - Application state
	 * @param {EventBus} eventBus - Event bus for notifications
	 */
	constructor(state, eventBus) {
		this.state = state;
		this.eventBus = eventBus;
	}

	/**
	 * Backup current shipping address before modification
	 */
	backup() {
		// Only backup if not already saved
		if (this.state.get('originalAddress') !== null) {
			return;
		}

		const original = {
			address_1: this.#getFieldValue(DOMSelectors.SHIPPING_ADDRESS_1),
			address_2: this.#getFieldValue(DOMSelectors.SHIPPING_ADDRESS_2),
			city: this.#getFieldValue(DOMSelectors.SHIPPING_CITY),
			postcode: this.#getFieldValue(DOMSelectors.SHIPPING_POSTCODE),
			country: this.#getFieldValue(DOMSelectors.SHIPPING_COUNTRY),
			state: this.#getFieldValue(DOMSelectors.SHIPPING_STATE),
		};

		this.state.set('originalAddress', original);
	}

	/**
	 * Restore original shipping address
	 */
	restore() {
		const original = this.state.get('originalAddress');
		
		if (!original) {
			return;
		}

		// Restore all fields
		this.#setFieldValue(DOMSelectors.SHIPPING_ADDRESS_1, original.address_1, true);
		this.#setFieldValue(DOMSelectors.SHIPPING_ADDRESS_2, original.address_2, true);
		this.#setFieldValue(DOMSelectors.SHIPPING_CITY, original.city, true);
		this.#setFieldValue(DOMSelectors.SHIPPING_POSTCODE, original.postcode, true);
		this.#setFieldValue(DOMSelectors.SHIPPING_COUNTRY, original.country, true);
		this.#setFieldValue(DOMSelectors.SHIPPING_STATE, original.state, true);

		// Clear stored original
		this.state.set('originalAddress', null);

		// Trigger WooCommerce checkout update
		this.#triggerCheckoutUpdate();

		this.eventBus.emit('address:restored', original);
	}

	/**
	 * Populate shipping address fields with locker data
	 * @param {Object} lockerData - Normalized locker data
	 */
	populate(lockerData) {
		if (!lockerData || this.state.get('isPopulatingAddress')) {
			return;
		}

		this.state.set('isPopulatingAddress', true);

		// Backup original address first
		this.backup();

		// Enable "Ship to different address" checkbox if needed
		const needsCheckboxChange = this.#enableShipToDifferent();

		// Extract locker data
		const addressLine = lockerData.addressLine1 || '';
		const city = lockerData.city || lockerData.addressLine2 || '';
		const postalCode = lockerData.postalCode || '';
		const country = lockerData.country || '';
		const lockerName = lockerData.name || '';

		// Build full address line 1
		let fullAddress = lockerName;
		if (addressLine) {
			fullAddress += (fullAddress ? ' - ' : '') + addressLine;
		}

		// Build address line 2 (note)
		const addressLine2 = lockerData.note 
			? lockerData.note.substring(0, 100) 
			: '';

		// Wait for shipping fields to be visible if checkbox changed
		const delay = needsCheckboxChange ? Timeouts.ADDRESS_POPULATE_DELAY : 0;

		setTimeout(() => {
			// Set all fields without triggering individual change events
			this.#setFieldValue(DOMSelectors.SHIPPING_ADDRESS_1, fullAddress, false);
			this.#setFieldValue(DOMSelectors.SHIPPING_ADDRESS_2, addressLine2, false);
			this.#setFieldValue(DOMSelectors.SHIPPING_CITY, city, false);
			this.#setFieldValue(DOMSelectors.SHIPPING_POSTCODE, postalCode, false);
			this.#setFieldValue(DOMSelectors.SHIPPING_COUNTRY, country, false);

			// Single checkout update trigger
			this.#triggerCheckoutUpdate();

			// Reset flag after delay to ensure update completes
			setTimeout(() => {
				this.state.set('isPopulatingAddress', false);
			}, Timeouts.ADDRESS_POPULATE_RESET);

			this.eventBus.emit('address:populated', lockerData);
		}, delay);
	}

	/**
	 * Enable "Ship to different address" checkbox if needed
	 * @private
	 * @returns {boolean} True if checkbox was changed
	 */
	#enableShipToDifferent() {
		const checkbox = document.getElementById(DOMSelectors.SHIP_TO_DIFFERENT_CHECKBOX);

		if (!checkbox || checkbox.checked) {
			return false;
		}

		checkbox.checked = true;

		// Manually show shipping fields
		const shippingAddress = document.querySelector(DOMSelectors.SHIPPING_ADDRESS_WRAPPER);
		if (shippingAddress) {
			shippingAddress.style.display = 'block';
		}

		return true;
	}

	/**
	 * Get field value by ID
	 * @private
	 */
	#getFieldValue(fieldId) {
		const field = document.getElementById(fieldId);
		return field ? field.value : '';
	}

	/**
	 * Set field value by ID
	 * @private
	 * @param {string} fieldId - Field ID
	 * @param {string} value - Value to set
	 * @param {boolean} triggerChange - Whether to trigger change event
	 */
	#setFieldValue(fieldId, value, triggerChange = false) {
		const field = document.getElementById(fieldId);
		
		if (!field) {
			return;
		}

		field.value = value;

		if (triggerChange) {
			field.dispatchEvent(new Event('change', { bubbles: true }));
		}
	}

	/**
	 * Trigger WooCommerce checkout update
	 * @private
	 */
	#triggerCheckoutUpdate() {
		const body = document.body;
		body.dispatchEvent(new Event(CustomEvents.UPDATE_CHECKOUT, { bubbles: true }));
	}
}
