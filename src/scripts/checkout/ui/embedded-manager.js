/**
 * Manages embedded map display (inline in checkout).
 */
import { ElementIDs, CSSClasses, Selectors } from '../core/index.js';

export class EmbeddedManager {

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
		if (document.getElementById(ElementIDs.EMBEDDED_MAP)) {
			return;
		}

		// Create container
		const container = document.createElement('div');
		container.id = ElementIDs.EMBEDDED_MAP;
		container.className = `${CSSClasses.AUTO_MAP} ${CSSClasses.HIDDEN}`;

		// Insert after shipping method label
		const label = document.querySelector(Selectors.BOXNOW_METHOD_LABEL);
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
		detailsContainer.className = `${CSSClasses.LOCKER_DETAILS} ${CSSClasses.EMBEDDED_DETAILS} ${CSSClasses.HIDDEN}`;

		// Create locker info wrapper
		const lockerInfoContainer = document.createElement('div');
		lockerInfoContainer.className = 'boxnow-locker-info-container';
		lockerInfoContainer.appendChild(detailsContainer);

		// Add class to main container
		container.classList.add('boxnow-embedded-container');

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
		const autoMap = document.getElementById(ElementIDs.EMBEDDED_MAP);
		if (autoMap) {
			this.initContainer(autoMap);
		}

		// Initialize shortcode embedded maps
		const shortcodeMaps = document.querySelectorAll(`.${CSSClasses.EMBEDDED_MAP_SHORTCODE}`);
		shortcodeMaps.forEach(container => this.initContainer(container));

		// Update visibility based on shipping selection
		this.updateVisibility();
	}

	/**
	 * Show embedded maps
	 */
	show() {
		const autoMap = document.getElementById(ElementIDs.EMBEDDED_MAP);
		if (autoMap) {
			autoMap.classList.remove(CSSClasses.HIDDEN);
			autoMap.classList.add(CSSClasses.VISIBLE);
		}

		const shortcodeMaps = document.querySelectorAll(`.${CSSClasses.EMBEDDED_MAP_SHORTCODE}`);
		shortcodeMaps.forEach(container => {
			container.classList.remove(CSSClasses.HIDDEN);
			container.classList.add(CSSClasses.VISIBLE);
		});
	}

	/**
	 * Hide embedded maps
	 */
	hide() {
		const autoMap = document.getElementById(ElementIDs.EMBEDDED_MAP);
		if (autoMap) {
			autoMap.classList.remove(CSSClasses.VISIBLE);
			autoMap.classList.add(CSSClasses.HIDDEN);
		}

		const shortcodeMaps = document.querySelectorAll(`.${CSSClasses.EMBEDDED_MAP_SHORTCODE}`);
		shortcodeMaps.forEach(container => {
			container.classList.remove(CSSClasses.VISIBLE);
			container.classList.add(CSSClasses.HIDDEN);
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
