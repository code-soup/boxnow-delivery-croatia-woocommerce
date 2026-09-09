/**
 * Manages popup map display (overlay + iframe).
 */
import { ElementIDs, CSSClasses, Selectors } from '../core/index.js';
import { logger } from '../utils/logger.js';

export class PopupManager {

	/**
	 * @param {WidgetService} widgetService - Widget service
	 * @param {ShippingService} shippingService - Shipping service
	 */
	constructor(widgetService, shippingService) {
		this.widgetService = widgetService;
		this.shippingService = shippingService;
	}

	/**
	 * Create and display popup with widget
	 */
	open() {
		logger.log('PopupManager.open() called');

		const country = this.shippingService.getUserCountry();
		logger.logValue('User country', country);

		this.#createOverlay();

		const iframe = this.widgetService.createPopupIframe(country);
		logger.log('Created iframe');

		document.body.appendChild(iframe);
		logger.log('Iframe added to body');
	}

	/**
	 * Close popup and remove overlay
	 */
	close() {
		// Remove overlay
		const overlay = document.getElementById(ElementIDs.POPUP_OVERLAY);
		if (overlay) {
			overlay.remove();
		}

		// Remove all widget iframes
		const iframes = document.querySelectorAll(Selectors.WIDGET_IFRAME);
		iframes.forEach(iframe => iframe.remove());

		// Also remove popup wrapper if exists
		const popupWrapper = document.querySelector('.boxnow-popup');
		if (popupWrapper) {
			popupWrapper.remove();
		}
	}

	/**
	 * Create overlay element
	 * @private
	 */
	#createOverlay() {
		// Remove existing overlay if present
		const existing = document.getElementById(ElementIDs.POPUP_OVERLAY);
		if (existing) {
			existing.remove();
		}

		// Create new overlay
		const overlay = document.createElement('div');
		overlay.id = ElementIDs.POPUP_OVERLAY;
		overlay.className = CSSClasses.POPUP_OVERLAY;

		// Close on click
		overlay.addEventListener('click', () => this.close());

		// Add to body
		document.body.appendChild(overlay);
	}
}
