/**
 * Classic WooCommerce checkout orchestrator.
 * Coordinates all services and UI components.
 */
import { LockerState, LockerStorage, ApiClient, EventBus, Events, DOMSelectors, Timeouts, CustomEvents } from './core/index.js';
import { ShippingService, AddressService, ValidationService, WidgetService } from './services/index.js';
import { ButtonManager, DetailsRenderer, PopupManager, EmbeddedManager } from './ui/index.js';

export class CheckoutClassic {
	/**
	 * @param {Object} config - Global settings (boxNowDeliverySettings)
	 */
	constructor(config) {
		this.config = config;
		
		// Initialize core
		this.state = new LockerState();
		this.eventBus = new EventBus();
		this.apiClient = new ApiClient(config.ajaxUrl, config.nonce);
		this.storage = new LockerStorage(this.apiClient);
		
		// Initialize services
		this.shippingService = new ShippingService(this.state, this.eventBus);
		this.addressService = new AddressService(this.state, this.eventBus);
		this.validationService = new ValidationService(this.state, this.shippingService, this.storage, config);
		this.widgetService = new WidgetService(config, this.state, this.eventBus);
		
		// Initialize UI components
		this.buttonManager = new ButtonManager(config, this.shippingService, this.eventBus);
		this.detailsRenderer = new DetailsRenderer(config);
		this.popupManager = new PopupManager(this.widgetService, this.shippingService);
		this.embeddedManager = new EmbeddedManager(this.widgetService, this.shippingService);
		
		// Debounce timeout for checkout updates
		this.initTimeout = null;
	}

	/**
	 * Initialize checkout
	 */
	init() {
		// Skip init if currently populating address
		if (this.state.get('isPopulatingAddress')) {
			return;
		}

		// Render UI based on display mode
		if (this.config.displayMode === 'popup') {
			this.buttonManager.render();
			this.buttonManager.updateVisibility();
		} else if (this.config.displayMode === 'embedded') {
			this.embeddedManager.init();
		}

		// Restore locker details if BoxNow is selected
		if (this.shippingService.isBoxNowSelected()) {
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
			// Restore UI only (don't send to server)
			this.detailsRenderer.render(lockerData);
			this.state.setLocker(lockerData);
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
			if (this.config.displayMode === 'popup') {
				this.popupManager.open();
			}
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
			if (event.target.matches(DOMSelectors.SHIPPING_METHOD_RADIO)) {
				const selectedMethod = event.target.value;

				// If switching away from BoxNow, clear locker
				if (selectedMethod && !selectedMethod.includes('codesoup_box_now_delivery')) {
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
			if (event.target.id === DOMSelectors.SHIPPING_COUNTRY) {
				this.clearLocker();
			}

			if (event.target.id === DOMSelectors.BILLING_COUNTRY) {
				const shipToDifferent = document.getElementById(DOMSelectors.SHIP_TO_DIFFERENT_CHECKBOX);
				if (!shipToDifferent || !shipToDifferent.checked) {
					this.clearLocker();
				}
			}

			if (event.target.id === DOMSelectors.SHIP_TO_DIFFERENT_CHECKBOX) {
				// Skip if populating address
				if (!this.state.get('isPopulatingAddress')) {
					this.clearLocker();
				}
			}
		});

		// Checkout updated (WooCommerce event)
		document.body.addEventListener(CustomEvents.UPDATED_CHECKOUT, () => {
			// Debounce init calls
			if (this.initTimeout) {
				clearTimeout(this.initTimeout);
			}
			this.initTimeout = setTimeout(() => {
				this.init();
				this.initTimeout = null;
			}, Timeouts.CHECKOUT_UPDATE_DEBOUNCE);
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
