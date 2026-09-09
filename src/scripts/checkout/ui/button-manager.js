/**
 * Manages "Pick a locker" button rendering and visibility.
 */
import { Selectors, CSSClasses } from '../core/index.js';
import { logger } from '../utils/logger.js';

export class ButtonManager {

	/**
	 * @param {Object} config - Global settings
	 * @param {ShippingService} shippingService - Shipping service
	 * @param {EventBus} eventBus - Event bus
	 */
	constructor(config, shippingService, eventBus) {
		this.config = config;
		this.shippingService = shippingService;
		this.eventBus = eventBus;
	}

	/**
	 * Render inline button (next to shipping method)
	 */
	renderInlineButton() {
		// Check if button already exists
		if (document.querySelector(`.${CSSClasses.BUTTON_INLINE}`)) {
			return;
		}

		const buttonText = this.config.buttonText || 'Pick a locker';
		const buttonColor = this.config.buttonColor || '#6CD04E';

		// Create button element
		const button = document.createElement('button');
		button.type = 'button';
		button.className = `button ${CSSClasses.BUTTON_BASE} ${CSSClasses.BUTTON_INLINE} ${CSSClasses.HIDDEN}`;

		// Set button color using CSS custom property
		if (buttonColor) {
			button.style.setProperty('--boxnow-button-color', buttonColor);
		}

		button.textContent = buttonText;

		// Find shipping method label
		const label = document.querySelector(Selectors.BOXNOW_METHOD_LABEL);
		
		if (label) {
			label.insertAdjacentElement('afterend', button);
		}
	}

	/**
	 * Render all buttons based on configuration
	 */
	render() {
		if (this.config.displayMode !== 'popup') {
			return;
		}

		const buttonPosition = this.config.buttonPosition || 'inline';

		if (buttonPosition === 'inline' || buttonPosition === 'both') {
			this.renderInlineButton();
		}

		setTimeout(() => {
			this.attachClickListeners();
		}, 0);
	}

	/**
	 * Attach click listeners to all button variants
	 */
	attachClickListeners() {
		this.#attachClickListener(`.${CSSClasses.BUTTON_INLINE}`, false);
		this.#attachClickListener(`.${CSSClasses.BUTTON_CHECKBOX}`, true);
		this.#attachClickListener(`.${CSSClasses.BUTTON_AUTO_SELECT}`, true);
	}

	/**
	 * Attach click listener to button selector
	 * @private
	 * @param {string} selector - CSS selector
	 * @param {boolean} autoSelectShipping - Whether to auto-select BoxNow shipping
	 */
	#attachClickListener(selector, autoSelectShipping) {
		const buttons = document.querySelectorAll(selector);

		logger.log('Attaching click listeners to', selector, '- found', buttons.length, 'buttons');

		buttons.forEach((button) => {
			if (button._boxnowClickHandler) {
				button.removeEventListener('click', button._boxnowClickHandler);
			}

			const handler = (event) => {
				logger.log('Button clicked!', selector);
				event.preventDefault();
				event.stopPropagation();

				if (autoSelectShipping) {
					logger.log('Auto-selecting BoxNow shipping');
					this.shippingService.selectBoxNow();
				}

				logger.log('Emitting widget:open-requested');
				this.eventBus.emit('widget:open-requested');
			};

			button._boxnowClickHandler = handler;
			button.addEventListener('click', handler, true);
			logger.log('Click listener attached to button');
		});
	}

	/**
	 * Show inline buttons (when BoxNow shipping is selected)
	 */
	showInline() {
		const buttons = document.querySelectorAll(`.${CSSClasses.BUTTON_INLINE}`);
		buttons.forEach(button => {
			button.classList.remove(CSSClasses.HIDDEN);
			button.classList.add(CSSClasses.VISIBLE_INLINE);
		});
	}

	/**
	 * Hide inline buttons (when BoxNow shipping is not selected)
	 */
	hideInline() {
		const buttons = document.querySelectorAll(`.${CSSClasses.BUTTON_INLINE}`);
		buttons.forEach(button => {
			button.classList.remove(CSSClasses.VISIBLE_INLINE);
			button.classList.add(CSSClasses.HIDDEN);
		});
	}

	/**
	 * Update button visibility based on shipping selection
	 */
	updateVisibility() {
		const isBoxNowSelected = this.shippingService.isBoxNowSelected();

		if (isBoxNowSelected) {
			this.showInline();
		} else {
			this.hideInline();
		}
	}
}
