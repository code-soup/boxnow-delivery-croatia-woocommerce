/**
 * Validates locker selection before order placement.
 */
export class ValidationService {
	/**
	 * @param {LockerState} state - Application state
	 * @param {ShippingService} shippingService - Shipping service
	 * @param {LockerStorage} storage - Locker storage
	 * @param {Object} config - Global settings
	 */
	constructor(state, shippingService, storage, config) {
		this.state = state;
		this.shippingService = shippingService;
		this.storage = storage;
		this.config = config;
	}

	/**
	 * Validate that a locker is selected if BoxNow shipping is active
	 * @returns {boolean} True if validation passes
	 */
	validate() {
		// Only validate if BoxNow shipping is selected
		if (!this.shippingService.isBoxNowSelected()) {
			return true;
		}

		// Check if locker data exists
		const hasLocker = this.storage.hasData();

		if (!hasLocker) {
			const message = this.config.lockerNotSelectedMessage || 'Please select a locker first!';
			alert(message);
			return false;
		}

		return true;
	}

	/**
	 * Attach validation to place order button
	 */
	attachPlaceOrderValidation() {
		document.body.addEventListener('click', (event) => {
			// Check if click is on place order button
			if (event.target.id === 'place_order' || event.target.closest('#place_order')) {
				if (!this.validate()) {
					event.preventDefault();
					event.stopImmediatePropagation();
				}
			}
		});
	}

	/**
	 * Validate for WooCommerce Blocks checkout
	 * @param {Object} checkoutResponse - Checkout response object
	 * @throws {Object} Error object if validation fails
	 * @returns {Object} Checkout response if validation passes
	 */
	validateBlocksCheckout(checkoutResponse) {
		const shippingMethod = checkoutResponse.shipping_method;
		
		if (!shippingMethod || !shippingMethod.includes('box_now_delivery')) {
			return checkoutResponse;
		}

		// Get locker ID from state or storage
		const lockerData = this.storage.load();
		const lockerId = lockerData ? lockerData.locker_id : null;

		if (!lockerId) {
			throw {
				code: 'box-now-delivery-locker-not-selected',
				message: this.config.lockerNotSelectedMessage || 'Please select a locker first!',
				messageContext: 'wc/checkout'
			};
		}

		return checkoutResponse;
	}

	/**
	 * Prepare checkout data with locker ID for Blocks checkout
	 * @param {Object} checkoutData - Checkout data object
	 * @returns {Object} Modified checkout data
	 */
	prepareBlocksCheckoutData(checkoutData) {
		const lockerData = this.storage.load();
		const lockerId = lockerData ? lockerData.locker_id : null;

		if (!lockerId) {
			return checkoutData;
		}

		// Add locker ID to top-level for PHP
		checkoutData._boxnow_locker_id = lockerId;

		// Add to extensions for backward compatibility
		checkoutData.extensions = checkoutData.extensions || {};
		checkoutData.extensions['box-now-delivery'] = checkoutData.extensions['box-now-delivery'] || {};
		checkoutData.extensions['box-now-delivery']['_boxnow_locker_id'] = lockerId;

		return checkoutData;
	}
}
