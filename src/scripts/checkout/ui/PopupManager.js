/**
 * Manages popup map display (overlay + iframe).
 */
import { DOMSelectors } from '../core/index.js';

export class PopupManager {
	/**
	 * Element IDs
	 */
	static OVERLAY_ID = 'box_now_delivery_overlay';

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
		// Get user country
		const country = this.shippingService.getUserCountry();

		// Create overlay
		this.#createOverlay();

		// Create iframe
		const iframe = this.widgetService.createPopupIframe(country);

		// Add iframe to body
		document.body.appendChild(iframe);
	}

	/**
	 * Close popup and remove overlay
	 */
	close() {
		// Remove overlay
		const overlay = document.getElementById(PopupManager.OVERLAY_ID);
		if (overlay) {
			overlay.remove();
		}

		// Remove all widget iframes
		const iframes = document.querySelectorAll(DOMSelectors.WIDGET_IFRAME);
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
		const existing = document.getElementById(PopupManager.OVERLAY_ID);
		if (existing) {
			existing.remove();
		}

		// Create new overlay
		const overlay = document.createElement('div');
		overlay.id = PopupManager.OVERLAY_ID;
		overlay.style.cssText = `
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0);
			z-index: 9998;
		`;

		// Close on click
		overlay.addEventListener('click', () => this.close());

		// Add to body
		document.body.appendChild(overlay);
	}
}
