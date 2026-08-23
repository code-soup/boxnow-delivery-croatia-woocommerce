/**
 * Classic WooCommerce checkout orchestrator.
 * Coordinates all services and UI components.
 */
import { Events, ElementIDs, Selectors, TimeoutConstants, EventNames, ShippingMethods } from '../core/index.js';
import { EmbeddedManager } from '../ui/index.js';
import { BaseCheckout } from './base-checkout.js';
import { logger } from '../utils/logger.js';

export class CheckoutClassic extends BaseCheckout {
	/**
	 * @param {Object} config - Global settings (boxNowDeliverySettings)
	 */
	constructor(config) {
		// Call parent constructor to initialize core, services, and UI
		super(config);

		// Initialize Classic-specific UI components
		this.embeddedManager = new EmbeddedManager(this.widgetService, this.shippingService);

		// Debounce timeout for checkout updates
		this.initTimeout = null;
	}

	/**
	 * Initialize checkout
	 */
	init() {
		console.log('[BOXNOW DEBUG] init() called, displayMode:', this.config.displayMode);

		// Skip init if currently populating address
		if (this.state.get('isPopulatingAddress')) {
			console.log('[BOXNOW DEBUG] Skipping init - currently populating address');
			return;
		}

		// Render UI based on display mode
		if (this.config.displayMode === 'popup') {
			console.log('[BOXNOW DEBUG] Display mode is popup, calling buttonManager.render() and updateVisibility()');
			this.buttonManager.render();
			console.log('[BOXNOW DEBUG] About to call updateVisibility()');
			this.buttonManager.updateVisibility();
			console.log('[BOXNOW DEBUG] updateVisibility() completed');
		} else if (this.config.displayMode === 'embedded') {
			console.log('[BOXNOW DEBUG] Display mode is embedded, calling embeddedManager.init()');
			this.embeddedManager.init();
		}

		// Restore locker details if BoxNow is selected
		if (this.shippingService.isBoxNowSelected()) {
			console.log('[BOXNOW DEBUG] BoxNow is selected, restoring locker from storage');
			this.showSelectedLockerFromStorage();
		}
	}

	/**
	 * Show selected locker details from localStorage
	 */
	showSelectedLockerFromStorage() {
		// Skip if currently populating
		if (this.state.get('isPopulatingAddress')) {
			return;
		}

		const lockerData = this.storage.load();
		if (lockerData) {
			// Restore UI
			this.detailsRenderer.render(lockerData);

			// Update state
			this.state.setLocker(lockerData);

			// Populate address fields and check "ship to different address" checkbox
			this.addressService.populate(lockerData);
		}
	}

	/**
	 * Handle locker selection from widget
	 * @param {Object} rawData - Raw data from widget
	 */
	handleLockerSelection(rawData) {
		// Skip if currently populating
		if (this.state.get('isPopulatingAddress')) {
			return;
		}

		// Normalize data
		const lockerData = this.widgetService.normalizeLockerData(rawData);

		// Validate required fields
		if (!lockerData.locker_id || !lockerData.addressLine1 || !lockerData.postalCode || !lockerData.name) {
			console.warn('Invalid locker data received', lockerData);
			return;
		}

		// Save to storage (localStorage + session)
		this.storage.saveAndSync(lockerData);

		// Update state
		this.state.setLocker(lockerData);

		// Render details
		this.detailsRenderer.render(lockerData);

		// Populate shipping address
		this.addressService.populate(lockerData);

		// Close popup if in popup mode
		if (this.config.displayMode === 'popup') {
			this.popupManager.close();
		}

		// Emit event
		this.eventBus.emit(Events.LOCKER_SELECTED, lockerData);
	}

	/**
	 * Clear locker selection
	 */
	clearLocker() {
		// Clear storage
		this.storage.clearAndSync();

		// Clear state
		this.state.clearLocker();

		// Clear UI
		this.detailsRenderer.clear();

		// Restore original address
		this.addressService.restore();

		// Emit event
		this.eventBus.emit(Events.LOCKER_CLEARED);
	}

	/**
	 * Setup event listeners
	 */
	setupEventListeners() {
		// Widget open request (from button clicks)
		this.eventBus.on('widget:open-requested', () => {
			console.log('[BOXNOW DEBUG] widget:open-requested event received in orchestrator');
			console.log('[BOXNOW DEBUG] displayMode:', this.config.displayMode);
			console.log('[BOXNOW DEBUG] popupManager:', this.popupManager);

			logger.log('widget:open-requested event received, displayMode:', this.config.displayMode);
			if (this.config.displayMode === 'popup') {
				console.log('[BOXNOW DEBUG] Calling popupManager.open()');
				logger.log('Opening popup...');
				this.popupManager.open();
				console.log('[BOXNOW DEBUG] popupManager.open() called');
			} else {
				console.log('[BOXNOW DEBUG] Display mode is not popup, popup not opened');
			}
		});

		// Clear locker request (from "Change" button)
		this.eventBus.on('locker:clear-requested', () => {
			logger.log('Clear locker requested');
			this.clearLocker();
		});

		// Widget message handler
		window.addEventListener('message', (event) => {
			const result = this.widgetService.handleMessage(event);
			
			if (result && result.type === 'close') {
				this.popupManager.close();
			} else if (result && result.type === 'locker-data') {
				this.handleLockerSelection(result.data);
			}
		}, false);

		// Shipping method change
		document.body.addEventListener('change', (event) => {
			if (event.target.matches(Selectors.SHIPPING_METHOD_RADIO)) {
				const selectedMethod = event.target.value;

				// If switching away from BoxNow, clear locker
				if (selectedMethod && !selectedMethod.includes(ShippingMethods.BOXNOW_ID)) {
					this.clearLocker();
				}

				// Update UI visibility
				if (this.config.displayMode === 'popup') {
					this.buttonManager.updateVisibility();
				} else if (this.config.displayMode === 'embedded') {
					this.embeddedManager.updateVisibility();
				}
			}
		});

		// Country change - clear locker
		document.body.addEventListener('change', (event) => {
			if (event.target.id === ElementIDs.SHIPPING_COUNTRY) {
				this.clearLocker();
			}

			if (event.target.id === ElementIDs.BILLING_COUNTRY) {
				const shipToDifferent = document.getElementById(ElementIDs.SHIP_TO_DIFFERENT_CHECKBOX);
				if (!shipToDifferent || !shipToDifferent.checked) {
					this.clearLocker();
				}
			}

			if (event.target.id === ElementIDs.SHIP_TO_DIFFERENT_CHECKBOX) {
				// Skip if populating address
				if (!this.state.get('isPopulatingAddress')) {
					this.clearLocker();
				}
			}
		});

		// Checkout updated (WooCommerce event)
		document.body.addEventListener(EventNames.UPDATED_CHECKOUT, () => {
			// Debounce init calls
			if (this.initTimeout) {
				clearTimeout(this.initTimeout);
			}
			this.initTimeout = setTimeout(() => {
				this.init();
				this.initTimeout = null;
			}, TimeoutConstants.CHECKOUT_UPDATE_DEBOUNCE);
		});

		// Place order validation
		this.validationService.attachPlaceOrderValidation();
	}

	/**
	 * Run the checkout
	 */
	run() {
		// Initial setup
		this.init();

		// Setup event listeners
		this.setupEventListeners();

		// Show locker details from storage
		this.showSelectedLockerFromStorage();
	}
}
