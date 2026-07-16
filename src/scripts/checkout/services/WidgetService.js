/**
 * Manages BoxNow widget integration (iframe creation, URL building, message handling).
 */
import { DOMSelectors } from '../core/index.js';

export class WidgetService {
	/**
	 * Allowed iframe origins for security
	 */
	static ALLOWED_ORIGINS = [
		'https://widget-v5.boxnow.',
		'https://boxlockersloadfiles.blob.core.windows.net',
	];

	/**
	 * Country-specific base URLs
	 */
	static BASE_URLS = {
		CY: 'https://widget-v5.boxnow.cy',
		BG: 'https://widget-v5.boxnow.bg',
		HR: 'https://widget-v5.boxnow.hr',
		GR: 'https://widget-v5.boxnow.gr',
	};

	/**
	 * @param {Object} config - Global settings
	 * @param {LockerState} state - Application state
	 * @param {EventBus} eventBus - Event bus
	 */
	constructor(config, state, eventBus) {
		this.config = config;
		this.state = state;
		this.eventBus = eventBus;
	}

	/**
	 * Build widget URL based on country and settings
	 * @param {string} country - Country code (CY, BG, HR, GR)
	 * @param {boolean} isPopup - Whether URL is for popup mode
	 * @returns {string} Widget URL
	 */
	buildUrl(country, isPopup = false) {
		// Get base URL for country (default to Greece)
		const baseUrl = WidgetService.BASE_URLS[country] || WidgetService.BASE_URLS.GR;
		
		// Add popup path if needed
		const url = isPopup ? `${baseUrl}/popup.html` : baseUrl;

		// Build query parameters
		const params = [];

		if (this.config.partnerId) {
			params.push(`partnerId=${this.config.partnerId}`);
		}

		if (this.config.gps_option === 'off') {
			params.push('gps=no');
			
			// Get postal code from billing field
			const postalCodeField = document.getElementById(DOMSelectors.BILLING_POSTCODE);
			if (postalCodeField && postalCodeField.value) {
				params.push(`zip=${encodeURIComponent(postalCodeField.value)}`);
			}
		} else {
			params.push('gps=yes');
		}

		if (isPopup) {
			params.push('autoclose=yes');
			params.push('autoselect=no');
		}

		return params.length ? `${url}?${params.join('&')}` : url;
	}

	/**
	 * Create popup iframe element
	 * @param {string} country - Country code
	 * @returns {HTMLIFrameElement}
	 */
	createPopupIframe(country) {
		const iframe = document.createElement('iframe');
		iframe.src = this.buildUrl(country, true);
		iframe.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;border:none;z-index:9999;';
		iframe.setAttribute('allowfullscreen', '');

		return iframe;
	}

	/**
	 * Create embedded iframe element
	 * @param {string} country - Country code
	 * @returns {HTMLIFrameElement}
	 */
	createEmbeddedIframe(country) {
		const iframe = document.createElement('iframe');
		iframe.src = this.buildUrl(country, false);
		iframe.style.cssText = 'width:100%;height:100%;border:none;';
		iframe.setAttribute('allowfullscreen', '');
		
		return iframe;
	}

	/**
	 * Validate message origin for security
	 * @param {string} origin - Message origin
	 * @returns {boolean}
	 */
	isOriginAllowed(origin) {
		return WidgetService.ALLOWED_ORIGINS.some(allowed => origin.includes(allowed));
	}

	/**
	 * Handle postMessage from widget iframe
	 * @param {MessageEvent} event - Message event
	 * @returns {Object|null} Locker data or null
	 */
	handleMessage(event) {
		// Security check: validate origin
		if (!this.isOriginAllowed(event.origin)) {
			return null;
		}

		const data = event.data;

		// Ignore non-object messages unless it's "closeIframe"
		if (typeof data !== 'object' && data !== 'closeIframe') {
			return null;
		}

		// Handle close message
		if (data === 'closeIframe' || 
			(typeof data === 'object' && data !== null && data.boxnowClose !== undefined)) {
			this.eventBus.emit('widget:closed');
			return { type: 'close' };
		}

		// Handle locker selection
		if (typeof data === 'object' && data !== null) {
			this.eventBus.emit('widget:message-received', data);
			return { type: 'locker-data', data };
		}

		return null;
	}

	/**
	 * Normalize locker data from widget to standard format
	 * @param {Object} rawData - Raw data from widget
	 * @returns {Object} Normalized locker data
	 */
	normalizeLockerData(rawData) {
		return {
			locker_id: rawData.locker_id || rawData.lockerId || '',
			name: rawData.name || rawData.locker_name || '',
			addressLine1: rawData.addressLine1 || rawData.address || '',
			addressLine2: rawData.addressLine2 || '',
			city: rawData.city || '',
			postalCode: rawData.postalCode || rawData.postal_code || rawData.zip || '',
			country: rawData.country || '',
			note: rawData.note || '',
			image: rawData.image || rawData.locker_image || '',
			warehouseId: rawData.warehouseId || rawData.warehouse_id || '',
		};
	}
}
