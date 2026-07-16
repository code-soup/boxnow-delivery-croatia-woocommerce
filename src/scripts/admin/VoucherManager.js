/**
 * Manages voucher creation and cancellation in admin order page.
 * No jQuery - uses native fetch.
 */
import { safeJsonParse, isStringArray, sanitizeErrorMessage } from '../checkout/core/ValidationHelpers.js';

export class VoucherManager {
	/**
	 * Size mapping for compartments
	 */
	static SIZE_MAPPING = {
		small: 1,
		medium: 2,
		large: 3,
	};

	/**
	 * Button size identifiers
	 */
	static SIZES = ['small', 'medium', 'large'];

	/**
	 * @param {Object} apiClient - API client for AJAX calls
	 */
	constructor(apiClient) {
		this.apiClient = apiClient;
		this.buttons = [];
	}

	/**
	 * Initialize voucher manager
	 */
	init() {
		// Get elements
		const enabledField = document.getElementById('create_vouchers_enabled');
		const isEnabled = enabledField && enabledField.value === 'true';

		// Get all buttons
		this.buttons = [
			document.getElementById('box_now_create_voucher_small'),
			document.getElementById('box_now_create_voucher_medium'),
			document.getElementById('box_now_create_voucher_large'),
		];

		// Setup each button
		VoucherManager.SIZES.forEach((size, index) => {
			const button = this.buttons[index];
			
			if (!button) {
				return;
			}

			// Enable/disable based on settings
			button.disabled = !isEnabled;

			if (isEnabled) {
				this.#attachCreateListener(button, size);
			}
		});

		// Check if vouchers already exist and disable buttons
		this.#checkExistingVouchers();
	}

	/**
	 * Attach create voucher click listener
	 * @private
	 * @param {HTMLElement} button - Button element
	 * @param {string} size - Size (small, medium, large)
	 */
	#attachCreateListener(button, size) {
		button.addEventListener('click', async () => {
			const orderId = document.getElementById('box_now_order_id')?.value;
			const voucherQuantity = document.getElementById('box_now_voucher_code')?.value;
			const maxVouchers = parseInt(document.getElementById('max_vouchers')?.value, 10);

			// Validation
			if (!orderId || !voucherQuantity) {
				alert('Please provide the required data.');
				return;
			}

			if (voucherQuantity > maxVouchers) {
				alert('The number of vouchers you want to create is larger than the max vouchers number.');
				return;
			}

			// Disable button during request
			button.disabled = true;

			try {
				const response = await this.apiClient.post('create_box_now_vouchers', {
					order_id: orderId,
					voucher_quantity: voucherQuantity,
					compartment_size: VoucherManager.SIZE_MAPPING[size],
				});

				if (response.success && response.data?.new_parcel_ids) {
					const parcelIds = response.data.new_parcel_ids;

					// Update hidden field
					const parcelIdsField = document.getElementById('box_now_parcel_ids');
					if (parcelIdsField) {
						parcelIdsField.value = JSON.stringify(parcelIds);
					}

					// Display parcel links
					this.displayParcelLinks(parcelIds);

					// Disable all buttons after creation
					this.buttons.forEach(btn => btn && (btn.disabled = true));
				} else {
					alert('Error: New parcel IDs are not available in the response data.');
					button.disabled = false;
				}
			} catch (error) {
				alert('Error: ' + sanitizeErrorMessage(error));
				button.disabled = false;
			}
		});
	}

	/**
	 * Check for existing vouchers and disable buttons if found
	 * @private
	 */
	#checkExistingVouchers() {
		const parcelIdsField = document.getElementById('box_now_parcel_ids');

		if (!parcelIdsField) {
			return;
		}

		const parcelIds = safeJsonParse(parcelIdsField.value, []);

		if (!isStringArray(parcelIds)) {
			console.warn('Invalid parcel IDs format');
			return;
		}
		
		// Display existing parcel links
		if (parcelIds.length > 0) {
			this.displayParcelLinks(parcelIds);
			
			// Disable all buttons if vouchers exist
			this.buttons.forEach(btn => btn && (btn.disabled = true));
		}
	}

	/**
	 * Display parcel ID links
	 * @param {Array<string>} parcelIds - Array of parcel IDs
	 */
	displayParcelLinks(parcelIds) {
		const container = document.getElementById('box_now_voucher_link');
		
		if (!container) {
			return;
		}

		// Clear container
		container.innerHTML = '';

		// Add links for each parcel
		parcelIds.forEach(parcelId => {
			const orderId = document.getElementById('box_now_order_id')?.value;

			const html = `
				<a href="#" data-parcel-id="${parcelId}" class="parcel-id-link box-now-link">&#128196; ${parcelId}</a>
				<button class="cancel-voucher-btn" data-order-id="${orderId}" data-parcel-id="${parcelId}" style="color: white; background-color: red; margin: 4px 0; border: none; border-radius: 4px; cursor: pointer; padding: 6px 12px; font-size: 13px;">&#9664; Cancel Voucher</button>
				<br>
			`;

			container.insertAdjacentHTML('beforeend', html);
		});

		// Attach event delegation
		this.#attachLinkListeners(container);
	}

	/**
	 * Attach event listeners to parcel links container
	 * @private
	 * @param {HTMLElement} container - Container element
	 */
	#attachLinkListeners(container) {
		container.addEventListener('click', async (event) => {
			// Handle parcel link click
			if (event.target.matches('.parcel-id-link')) {
				event.preventDefault();
				const parcelId = event.target.getAttribute('data-parcel-id');
				const url = `${this.apiClient.ajaxUrl}?action=print_box_now_voucher&parcel_id=${parcelId}`;
				window.open(url, '_blank', 'noopener,noreferrer');
			}

			// Handle cancel button click
			if (event.target.matches('.cancel-voucher-btn')) {
				event.preventDefault();
				await this.#handleCancelVoucher(event.target);
			}
		});
	}

	/**
	 * Handle voucher cancellation
	 * @private
	 * @param {HTMLElement} button - Cancel button
	 */
	async #handleCancelVoucher(button) {
		const orderId = button.getAttribute('data-order-id');
		const parcelId = button.getAttribute('data-parcel-id');

		try {
			const response = await this.apiClient.post('cancel_voucher', {
				order_id: orderId,
				parcel_id: parcelId,
			});

			if (response.success) {
				const canceledParcelId = response.data;

				// Update hidden field
				const parcelIdsField = document.getElementById('box_now_parcel_ids');
				if (parcelIdsField) {
					const parcelIds = safeJsonParse(parcelIdsField.value, []);

					if (isStringArray(parcelIds)) {
						const index = parcelIds.indexOf(canceledParcelId);

						if (index !== -1) {
							parcelIds.splice(index, 1);
						}

						parcelIdsField.value = JSON.stringify(parcelIds);

						// Enable buttons if all vouchers canceled
						if (parcelIds.length === 0) {
							this.buttons.forEach(btn => btn && (btn.disabled = false));
						}
					}
				}

				// Reload page to reflect changes
				location.reload();
			} else {
				console.error('Error canceling voucher:', response.data);
			}
		} catch (error) {
			console.error('Error canceling voucher:', error);
		}
	}
}
