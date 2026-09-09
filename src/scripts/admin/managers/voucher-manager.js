/**
 * Manages voucher creation and cancellation in admin order page.
 * No jQuery - uses native fetch.
 */
import { safeJsonParse, isStringArray, sanitizeErrorMessage } from '../../checkout/utils/validation-helpers.js';
import * as FormFields from '../constants/form-fields.js';

export class VoucherManager {
	/**
	 * @param {Object} apiClient - API client for AJAX calls
	 */
	constructor(apiClient) {
		this.apiClient = apiClient;
		this.button = null;
	}

	/**
	 * Initialize voucher manager
	 */
	init() {
		// Get elements
		const enabledField = document.getElementById(FormFields.VOUCHER_CREATE_ENABLED);
		const isEnabled = enabledField && enabledField.value === 'true';

		// Get the create button
		this.button = document.getElementById(FormFields.VOUCHER_CREATE_BUTTON);

		if (!this.button) {
			return;
		}

		// Enable/disable based on settings
		this.button.disabled = !isEnabled;

		if (isEnabled) {
			this.#attachCreateListener(this.button);
		}

		// Check if vouchers already exist and disable button
		this.#checkExistingVouchers();

		// Attach cancel-all listener
		this.#attachCancelAllListener();

		// Update button state on load
		this.#updateButtonState();
	}

	/**
	 * Attach create voucher click listener
	 * @private
	 * @param {HTMLElement} button - Button element
	 */
	#attachCreateListener(button) {
		button.addEventListener('click', async () => {
			const orderId = document.getElementById(FormFields.VOUCHER_ORDER_ID)?.value;
			const voucherQuantity = document.getElementById(FormFields.VOUCHER_QUANTITY_INPUT)?.value;
			const maxVouchers = parseInt(document.getElementById(FormFields.VOUCHER_MAX_VOUCHERS)?.value, 10);

			// Get selected compartment size (radio button)
			const selectedRadio = document.querySelector(`input[name="${FormFields.VOUCHER_COMPARTMENT_SIZE}"]:checked`);
			const compartmentSize = selectedRadio ? parseInt(selectedRadio.value, 10) : null;
			const showRecipientInformation = document.getElementById(FormFields.VOUCHER_SHOW_RECIPIENT_INFO)?.checked ?? true;

			// Validation
			if (!orderId || !voucherQuantity) {
				alert('Please provide the required data.');
				return;
			}

			if (voucherQuantity > maxVouchers) {
				alert('The number of vouchers you want to create is larger than the max vouchers number.');
				return;
			}

			if (!compartmentSize) {
				alert('Please select a compartment size.');
				return;
			}

			// Disable button during request
			button.disabled = true;

			try {
				const response = await this.apiClient.post('create_box_now_vouchers', {
					order_id: orderId,
					voucher_quantity: voucherQuantity,
					compartment_size: compartmentSize,
					show_recipient_information: showRecipientInformation ? '1' : '0',
				});

				if (response.success && response.data?.new_parcel_ids && response.data?.html) {
					const parcelIds = response.data.new_parcel_ids;
					const html = response.data.html;

					// Update hidden field
					const parcelIdsField = document.getElementById(FormFields.VOUCHER_PARCEL_IDS);
					if (parcelIdsField) {
						parcelIdsField.value = JSON.stringify(parcelIds);
					}

					// Update current count
					const currentCountField = document.getElementById(FormFields.VOUCHER_CURRENT_COUNT);
					if (currentCountField) {
						currentCountField.value = parcelIds.length.toString();
					}

					// Show table and insert HTML
					this.#showVoucherTable();

					const container = document.getElementById(FormFields.VOUCHER_LINK_CONTAINER);
					if (container) {
						container.innerHTML = html;
						this.#attachLinkListeners(container);
					}

					// Update button state
					this.#updateButtonState();
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
	 * Check for existing vouchers and attach listeners
	 * @private
	 */
	#checkExistingVouchers() {
		// Attach listeners to pre-rendered parcel items (if any)
		const container = document.getElementById(FormFields.VOUCHER_LINK_CONTAINER);
		if (container && container.children.length > 0) {
			this.#attachLinkListeners(container);
		}
	}

	/**
	 * Show voucher table and hide "no vouchers" message
	 * @private
	 */
	#showVoucherTable() {
		const vouchersColumn = document.getElementById('codesoup-boxnow-vouchers-column');
		if (!vouchersColumn) {
			return;
		}

		// Hide "no vouchers" message
		const noVouchersMsg = vouchersColumn.querySelector('.codesoup-boxnow-no-vouchers');
		if (noVouchersMsg) {
			noVouchersMsg.remove();
		}

		// Create table if it doesn't exist
		let table = vouchersColumn.querySelector('.form-table');
		if (!table) {
			table = document.createElement('table');
			table.className = 'form-table';

			const tbody = document.createElement('tbody');
			tbody.id = FormFields.VOUCHER_LINK_CONTAINER;
			tbody.className = FormFields.VOUCHER_LINK_CLASS;

			table.appendChild(tbody);
			vouchersColumn.appendChild(table);
		}
	}



	/**
	 * Attach event listeners to parcel links container
	 * @private
	 * @param {HTMLElement} container - Container element
	 */
	#attachLinkListeners(container) {
		container.addEventListener('click', async (event) => {
			// Handle parcel link click
			if (event.target.matches('.codesoup-boxnow-parcel-link')) {
				event.preventDefault();
				const parcelId = event.target.getAttribute('data-parcel-id');
				const url = `${this.apiClient.ajaxUrl}?action=print_box_now_voucher&parcel_id=${parcelId}`;
				this.#showPdfModal(url, parcelId);
			}

			// Handle cancel button click
			if (event.target.matches('.codesoup-boxnow-cancel-voucher')) {
				event.preventDefault();
				await this.#handleCancelVoucher(event.target);
			}
		});
	}

	/**
	 * Show PDF in modal viewer
	 * @private
	 * @param {string} url - PDF URL
	 * @param {string} parcelId - Parcel ID
	 */
	#showPdfModal(url, parcelId) {
		// Create modal overlay
		const overlay = document.createElement('div');
		overlay.className = 'codesoup-boxnow-pdf-modal-overlay';

		// Create modal container
		const modal = document.createElement('div');
		modal.className = 'codesoup-boxnow-pdf-modal';

		// Create header
		const header = document.createElement('div');
		header.className = 'codesoup-boxnow-pdf-modal-header';

		const title = document.createElement('h2');
		title.textContent = `Voucher: ${parcelId}`;

		const closeBtn = document.createElement('button');
		closeBtn.className = 'codesoup-boxnow-pdf-modal-close';
		closeBtn.innerHTML = '&times;';
		closeBtn.setAttribute('aria-label', 'Close');

		header.appendChild(title);
		header.appendChild(closeBtn);

		// Create PDF viewer (iframe)
		const iframe = document.createElement('iframe');
		iframe.className = 'codesoup-boxnow-pdf-viewer';
		iframe.src = url;
		iframe.setAttribute('title', `Voucher PDF ${parcelId}`);

		// Create footer with actions
		const footer = document.createElement('div');
		footer.className = 'codesoup-boxnow-pdf-modal-footer';

		const downloadBtn = document.createElement('a');
		downloadBtn.href = url;
		downloadBtn.download = `voucher-${parcelId}.pdf`;
		downloadBtn.className = 'button button-primary';
		downloadBtn.textContent = 'Download PDF';

		const openNewTabBtn = document.createElement('a');
		openNewTabBtn.href = url;
		openNewTabBtn.target = '_blank';
		openNewTabBtn.rel = 'noopener noreferrer';
		openNewTabBtn.className = 'button button-secondary';
		openNewTabBtn.textContent = 'Open in New Tab';

		footer.appendChild(downloadBtn);
		footer.appendChild(openNewTabBtn);

		// Assemble modal
		modal.appendChild(header);
		modal.appendChild(iframe);
		modal.appendChild(footer);
		overlay.appendChild(modal);

		// Close handlers
		const closeModal = () => {
			overlay.remove();
		};

		closeBtn.addEventListener('click', closeModal);
		overlay.addEventListener('click', (e) => {
			if (e.target === overlay) {
				closeModal();
			}
		});

		// ESC key to close
		const handleEsc = (e) => {
			if (e.key === 'Escape') {
				closeModal();
				document.removeEventListener('keydown', handleEsc);
			}
		};
		document.addEventListener('keydown', handleEsc);

		// Add to DOM
		document.body.appendChild(overlay);
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
				const parcelIdsField = document.getElementById(FormFields.VOUCHER_PARCEL_IDS);
				if (parcelIdsField) {
					const parcelIds = safeJsonParse(parcelIdsField.value, []);

					if (isStringArray(parcelIds)) {
						const index = parcelIds.indexOf(canceledParcelId);

						if (index !== -1) {
							parcelIds.splice(index, 1);
						}

						parcelIdsField.value = JSON.stringify(parcelIds);

						// Update current count
						const currentCountField = document.getElementById(FormFields.VOUCHER_CURRENT_COUNT);
						if (currentCountField) {
							currentCountField.value = parcelIds.length.toString();
						}

						// Update button state
						this.#updateButtonState();
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

	/**
	 * Attach cancel-all vouchers click listener
	 * @private
	 */
	#attachCancelAllListener() {
		const button = document.getElementById(FormFields.VOUCHER_CANCEL_ALL_BUTTON);

		if (!button) {
			return;
		}

		button.addEventListener('click', async (event) => {
			event.preventDefault();
			await this.#handleCancelAllVouchers(button);
		});
	}

	/**
	 * Handle cancel-all vouchers
	 * @private
	 * @param {HTMLElement} button - Cancel all button
	 */
	async #handleCancelAllVouchers(button) {
		const orderId = button.getAttribute('data-order-id');
		const parcelIdsField = document.getElementById(FormFields.VOUCHER_PARCEL_IDS);
		const parcelIds = parcelIdsField ? safeJsonParse(parcelIdsField.value, []) : [];

		if (!orderId || !Array.isArray(parcelIds) || parcelIds.length === 0) {
			alert('No BoxNow vouchers were found for this order.');
			return;
		}

		if (
			!window.confirm(
				`Cancel all ${parcelIds.length} BoxNow voucher(s) for this order? This cannot be undone.`
			)
		) {
			return;
		}

		button.disabled = true;

		try {
			const response = await this.apiClient.post('cancel_all_vouchers', {
				order_id: orderId,
			});

			if (response.success) {
				const remainingParcelIds = response.data?.remaining_parcel_ids || [];
				const failedCancellations = response.data?.failed_cancellations || [];

				if (parcelIdsField) {
					parcelIdsField.value = JSON.stringify(remainingParcelIds);
				}

				const currentCountField = document.getElementById(FormFields.VOUCHER_CURRENT_COUNT);
				if (currentCountField) {
					currentCountField.value = remainingParcelIds.length.toString();
				}

				this.#updateButtonState();

				if (failedCancellations.length > 0) {
					alert(
						'Some BoxNow vouchers could not be cancelled: ' +
							failedCancellations.join(', ')
					);
				}

				location.reload();
			} else {
				alert('Error canceling all vouchers: ' + sanitizeErrorMessage(response.data));
				button.disabled = false;
			}
		} catch (error) {
			alert('Error canceling all vouchers: ' + sanitizeErrorMessage(error));
			button.disabled = false;
		}
	}

	/**
	 * Update cancel-all button visibility
	 * @private
	 * @param {number} currentCount - Current voucher count
	 */
	#updateCancelAllButton(currentCount) {
		const button = document.getElementById(FormFields.VOUCHER_CANCEL_ALL_BUTTON);

		if (!button) {
			return;
		}

		const hasVouchers = currentCount > 0;
		button.style.display = hasVouchers ? '' : 'none';
		button.disabled = !hasVouchers;
	}

	/**
	 * Update button state based on current voucher count
	 * @private
	 */
	#updateButtonState() {
		const maxVouchers = parseInt(document.getElementById(FormFields.VOUCHER_MAX_VOUCHERS)?.value, 10) || 0;
		const currentCount = parseInt(document.getElementById(FormFields.VOUCHER_CURRENT_COUNT)?.value, 10) || 0;
		const quantityInput = document.getElementById(FormFields.VOUCHER_QUANTITY_INPUT);
		const radios = document.querySelectorAll(`input[name="${FormFields.VOUCHER_COMPARTMENT_SIZE}"]`);
		const showRecipientCheckbox = document.getElementById(FormFields.VOUCHER_SHOW_RECIPIENT_INFO);

		const remainingVouchers = maxVouchers - currentCount;
		const canCreate = remainingVouchers > 0;

		// Update cancel-all button
		this.#updateCancelAllButton(currentCount);

		// Update button state
		if (this.button) {
			this.button.disabled = !canCreate;
		}

		// Update quantity input max value. The field lives inside the order edit
		// form, so it must never be left in an invalid state (0 with min="1") or
		// the browser blocks saving the order.
		if (quantityInput) {
			quantityInput.disabled = !canCreate;

			if (canCreate) {
				quantityInput.max = remainingVouchers.toString();

				if (parseInt(quantityInput.value, 10) > remainingVouchers) {
					quantityInput.value = remainingVouchers.toString();
				}
			}
		}

		// Enable/disable radio buttons
		radios.forEach(radio => {
			radio.disabled = !canCreate;
		});

		if (showRecipientCheckbox) {
			showRecipientCheckbox.disabled = !canCreate;
		}
	}
}
