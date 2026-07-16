/**
 * Manages embedded map display (inline in checkout).
 */
import { DOMSelectors } from '../core/index.js';

export class EmbeddedManager {
	/**
	 * Container IDs/classes
	 */
	static AUTO_MAP_ID = 'box_now_delivery_embedded_map';
	static SHORTCODE_CLASS = 'box-now-delivery-embedded-map-container';
	static DETAILS_CLASS = 'box-now-selected-locker-details';

	/**
	 * @param {WidgetService} widgetService - Widget service
	 * @param {ShippingService} shippingService - Shipping service
	 */
	constructor(widgetService, shippingService) {
		this.widgetService = widgetService;
		this.shippingService = shippingService;
	}

	/**
	 * Ensure embedded map container exists in DOM
	 */
	ensureAutoMapContainer() {
		// Check if container already exists
		if (document.getElementById(EmbeddedManager.AUTO_MAP_ID)) {
			return;
		}

		// Create container
		const container = document.createElement('div');
		container.id = EmbeddedManager.AUTO_MAP_ID;
		container.style.display = 'none';

		// Insert after shipping method label
		const label = document.querySelector(DOMSelectors.BOXNOW_METHOD_LABEL);
		if (label) {
			label.insertAdjacentElement('afterend', container);
		}
	}

	/**
	 * Initialize embedded map in a container
	 * @param {HTMLElement} container - Container element
	 */
	initContainer(container) {
		// Skip if iframe already exists
		if (container.querySelector('iframe')) {
			return;
		}

		// Get user country
		const country = this.shippingService.getUserCountry();

		// Create iframe
		const iframe = this.widgetService.createEmbeddedIframe(country);

		// Create locker details container
		const detailsContainer = document.createElement('div');
		detailsContainer.className = EmbeddedManager.DETAILS_CLASS;
		detailsContainer.style.cssText = 'display: none; margin-top: 10px;';

		// Create locker info wrapper
		const lockerInfoContainer = document.createElement('div');
		lockerInfoContainer.className = 'locker-info-container';
		lockerInfoContainer.appendChild(detailsContainer);

		// Style the main container
		const isShortcode = container.classList.contains(EmbeddedManager.SHORTCODE_CLASS);
		const height = isShortcode 
			? (container.style.height || '80vh') 
			: '80vh';

		container.style.cssText = `
			position: relative;
			width: 100%;
			height: ${height};
			overflow: auto;
		`;

		// Append iframe and details
		container.appendChild(iframe);
		container.appendChild(lockerInfoContainer);
	}

	/**
	 * Initialize all embedded map containers
	 */
	init() {
		// Ensure auto-generated container exists
		this.ensureAutoMapContainer();

		// Initialize auto-generated embedded map
		const autoMap = document.getElementById(EmbeddedManager.AUTO_MAP_ID);
		if (autoMap) {
			this.initContainer(autoMap);
		}

		// Initialize shortcode embedded maps
		const shortcodeMaps = document.querySelectorAll(`.${EmbeddedManager.SHORTCODE_CLASS}`);
		shortcodeMaps.forEach(container => this.initContainer(container));

		// Update visibility based on shipping selection
		this.updateVisibility();
	}

	/**
	 * Show embedded maps
	 */
	show() {
		const autoMap = document.getElementById(EmbeddedManager.AUTO_MAP_ID);
		if (autoMap) {
			autoMap.style.display = 'block';
		}

		const shortcodeMaps = document.querySelectorAll(`.${EmbeddedManager.SHORTCODE_CLASS}`);
		shortcodeMaps.forEach(container => {
			container.style.display = 'block';
		});
	}

	/**
	 * Hide embedded maps
	 */
	hide() {
		const autoMap = document.getElementById(EmbeddedManager.AUTO_MAP_ID);
		if (autoMap) {
			autoMap.style.display = 'none';
		}

		const shortcodeMaps = document.querySelectorAll(`.${EmbeddedManager.SHORTCODE_CLASS}`);
		shortcodeMaps.forEach(container => {
			container.style.display = 'none';
		});
	}

	/**
	 * Update visibility based on shipping method selection
	 */
	updateVisibility() {
		const isBoxNowSelected = this.shippingService.isBoxNowSelected();
		
		if (isBoxNowSelected) {
			this.show();
		} else {
			this.hide();
		}
	}
}
