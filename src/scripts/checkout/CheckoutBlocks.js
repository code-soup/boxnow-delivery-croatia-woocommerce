/**
 * WooCommerce Blocks checkout orchestrator.
 * Handles Blocks-specific integration (filters, fetch patching, observer).
 */
import { LockerState, LockerStorage, ApiClient, EventBus, Events, DOMSelectors, Timeouts, CustomEvents } from './core/index.js';
import { ShippingService, AddressService, ValidationService, WidgetService } from './services/index.js';
import { ButtonManager, DetailsRenderer, PopupManager } from './ui/index.js';

export class CheckoutBlocks {
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
		
		// Observer flag
		this.observerStarted = false;
	}

	/**
	 * Wait for WooCommerce Blocks registry to be available
	 * @param {Function} callback - Callback to execute when ready
	 */
	waitForRegistry(callback) {
		if (window.wc && window.wc.blocksCheckout) {
			callback();
		} else {
			setTimeout(() => this.waitForRegistry(callback), Timeouts.REGISTRY_POLL_INTERVAL);
		}
	}

	/**
	 * Register WooCommerce Blocks filters
	 */
	registerBlocksFilters() {
		const { registerCheckoutFilters } = window.wc.blocksCheckout;

		registerCheckoutFilters('box-now-delivery', {
			// Validate checkout response
			validateCheckoutResponse: (checkoutResponse) => {
				return this.validationService.validateBlocksCheckout(checkoutResponse);
			},
			
			// Prepare checkout data before sending
			beforeProcessCheckoutResponse: (checkoutData) => {
				return this.validationService.prepareBlocksCheckoutData(checkoutData);
			}
		});
	}

	/**
	 * Patch fetch to include locker ID in checkout requests
	 */
	patchFetch() {
		if (window._bndpFetchPatched) {
			return;
		}
		
		window._bndpFetchPatched = true;
		const originalFetch = window.fetch;

		window.fetch = async (input, init) => {
			try {
				const url = (typeof input === 'string') ? input : (input?.url || '');
				
				// Only patch checkout endpoint
				if (!url || !url.includes('/store/v1/checkout')) {
					return originalFetch.apply(window, arguments);
				}

				let opts = (typeof input === 'string') ? (init || {}) : { ...input };
				
				if ((opts.method || 'POST').toUpperCase() === 'POST' && opts.body && opts.headers) {
					const contentType = opts.headers['Content-Type'] || opts.headers['content-type'] || '';
					
					if (typeof opts.body === 'string' && contentType.includes('application/json')) {
						try {
							const bodyObj = JSON.parse(opts.body);
							const lockerData = this.storage.load();
							const lockerId = lockerData ? lockerData.locker_id : null;

							if (lockerId) {
								// Top-level for PHP
								bodyObj._boxnow_locker_id = lockerId;

								// Legacy extensions
								bodyObj.extensions = bodyObj.extensions || {};
								bodyObj.extensions['box-now-delivery'] = bodyObj.extensions['box-now-delivery'] || {};
								bodyObj.extensions['box-now-delivery']['_boxnow_locker_id'] = lockerId;
							}
							
							opts.body = JSON.stringify(bodyObj);
							
							if (typeof input !== 'string') {
								input = new Request(url, opts);
							} else {
								init = opts;
							}
						} catch (e) {
							console.error('Failed to patch checkout request body', e);
						}
					}
				}

				return originalFetch.call(window, input, init);
			} catch (error) {
				console.error('Error in fetch patch:', error);
				return originalFetch.apply(window, arguments);
			}
		};
	}

	/**
	 * Show selected locker from storage
	 */
	showSelectedLockerFromStorage() {
		const lockerData = this.storage.load();
		if (lockerData) {
			this.detailsRenderer.render(lockerData);
			this.state.setLocker(lockerData);
		}
	}

	/**
	 * Handle locker selection
	 * @param {Object} rawData - Raw data from widget
	 */
	handleLockerSelection(rawData) {
		const lockerData = this.widgetService.normalizeLockerData(rawData);

		if (!lockerData.locker_id || !lockerData.addressLine1 || !lockerData.postalCode || !lockerData.name) {
			console.warn('Invalid locker data', lockerData);
			return;
		}

		// Save to storage
		this.storage.saveAndSync(lockerData);

		// Update state
		this.state.setLocker(lockerData);

		// Render details
		this.detailsRenderer.render(lockerData);

		// Close popup
		this.popupManager.close();

		// Emit event
		this.eventBus.emit(Events.LOCKER_SELECTED, lockerData);
	}

	/**
	 * Setup event listeners
	 */
	setupEventListeners() {
		// Widget open request
		this.eventBus.on('widget:open-requested', () => {
			this.popupManager.open();
		});

		// Widget messages
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
			if (event.target.matches(DOMSelectors.BLOCKS_SHIPPING_RADIO)) {
				this.showSelectedLockerFromStorage();
			}
		});

		// Country change
		document.body.addEventListener('change', (event) => {
			if (event.target.id === DOMSelectors.BLOCKS_SHIPPING_COUNTRY || event.target.id === DOMSelectors.SHIPPING_COUNTRY) {
				this.storage.clearAndSync();
				this.detailsRenderer.clear();
				this.state.clearLocker();
			}
		});

		// Updated checkout
		document.body.addEventListener(CustomEvents.UPDATED_CHECKOUT, () => {
			this.showSelectedLockerFromStorage();
		});
	}

	/**
	 * Run the Blocks checkout
	 */
	run() {
		// Setup event listeners
		this.setupEventListeners();

		// Wait for Blocks registry and register filters
		this.waitForRegistry(() => {
			this.registerBlocksFilters();
			
			// Render button after delay (DOM needs to be ready)
			setTimeout(() => {
				this.buttonManager.render();
			}, Timeouts.BLOCKS_RENDER_DELAY);
		});

		// Patch fetch for checkout requests
		this.patchFetch();
	}
}
