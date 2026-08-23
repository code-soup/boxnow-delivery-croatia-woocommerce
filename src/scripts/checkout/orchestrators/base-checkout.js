/**
 * Base Checkout Orchestrator
 * Extracts common initialization logic for both Classic and Blocks checkouts
 */
import { LockerState, LockerStorage, ApiClient, EventBus } from '../core/index.js';
import { ShippingService, AddressService, ValidationService, WidgetService } from '../services/index.js';
import { ButtonManager, DetailsRenderer, PopupManager } from '../ui/index.js';

export class BaseCheckout {
	/**
	 * @param {Object} config - Global settings (boxNowDeliverySettings)
	 */
	constructor(config) {
		this.config = config;
		
		// Initialize core components
		this.initCore();
		
		// Initialize services
		this.initServices();
		
		// Initialize UI components
		this.initUI();
	}

	/**
	 * Initialize core components (state, event bus, API client, storage)
	 */
	initCore() {
		this.state = new LockerState();
		this.eventBus = new EventBus();
		this.apiClient = new ApiClient(this.config.ajaxUrl, this.config.nonce);
		this.storage = new LockerStorage(this.apiClient);
	}

	/**
	 * Initialize services (shipping, address, validation, widget)
	 */
	initServices() {
		this.shippingService = new ShippingService(this.state, this.eventBus);
		this.addressService = new AddressService(this.state, this.eventBus);
		this.validationService = new ValidationService(
			this.state,
			this.shippingService,
			this.storage,
			this.config
		);
		this.widgetService = new WidgetService(this.config, this.state, this.eventBus);
	}

	/**
	 * Initialize common UI components (button, details, popup)
	 * Subclasses can override to add additional UI components
	 */
	initUI() {
		this.buttonManager = new ButtonManager(this.config, this.shippingService, this.eventBus);
		this.detailsRenderer = new DetailsRenderer(this.config, this.eventBus);
		this.popupManager = new PopupManager(this.widgetService, this.shippingService);
	}

	/**
	 * Handle locker selection (common logic)
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

		// Populate address
		this.addressService.populate(lockerData);

		// Render details
		this.detailsRenderer.render(lockerData);

		// Close popup
		this.popupManager.close();

		// Emit event
		this.eventBus.emit('locker:selected', lockerData);
	}

	/**
	 * Clear locker selection (common logic)
	 */
	clearLocker() {
		this.storage.clearAndSync();
		this.addressService.restore();
		this.detailsRenderer.clear();
		this.state.clearLocker();
	}

	/**
	 * Setup widget message listener (common logic)
	 */
	setupWidgetMessageListener() {
		window.addEventListener('message', (event) => {
			const result = this.widgetService.handleMessage(event);

			if (result && result.type === 'close') {
				this.popupManager.close();
			} else if (result && result.type === 'locker-data') {
				this.handleLockerSelection(result.data);
			}
		}, false);
	}

	/**
	 * Setup clear locker event listener (common logic)
	 */
	setupClearLockerListener() {
		this.eventBus.on('locker:clear-requested', () => {
			this.clearLocker();
		});
	}

	/**
	 * Setup widget open event listener (common logic)
	 */
	setupWidgetOpenListener() {
		this.eventBus.on('widget:open-requested', () => {
			this.popupManager.open();
		});
	}

	/**
	 * Setup common event listeners
	 * Subclasses should call this and add their specific listeners
	 */
	setupCommonEventListeners() {
		this.setupWidgetMessageListener();
		this.setupClearLockerListener();
		this.setupWidgetOpenListener();
	}
}
