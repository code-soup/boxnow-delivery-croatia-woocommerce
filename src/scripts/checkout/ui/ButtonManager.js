/**
 * Manages "Pick a locker" button rendering and visibility.
 */
import { DOMSelectors } from '../core/index.js';

export class ButtonManager {
	/**
	 * Button CSS classes
	 */
	static CLASSES = {
		BASE: 'box-now-delivery-button',
		INLINE: 'box-now-delivery-button-inline',
		CHECKBOX: 'box-now-delivery-button-checkbox',
		AUTO_SELECT: 'box-now-delivery-button-auto-select',
	};

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
		if (document.querySelector(`.${ButtonManager.CLASSES.INLINE}`)) {
			return;
		}

		const buttonText = this.config.buttonText || 'Pick a locker';
		const buttonColor = this.config.buttonColor || '#6CD04E';

		// Create button element
		const button = document.createElement('button');
		button.type = 'button';
		button.className = `button ${ButtonManager.CLASSES.BASE} ${ButtonManager.CLASSES.INLINE}`;
		button.style.cssText = `display:none; background-color: ${buttonColor} !important; color: #fff !important;`;
		button.textContent = buttonText;

		// Find shipping method label
		const label = document.querySelector(DOMSelectors.BOXNOW_METHOD_LABEL);
		
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

		// Render inline button if needed
		if (buttonPosition === 'inline' || buttonPosition === 'both') {
			this.renderInlineButton();
		}

		// Attach click listeners to all button types
		this.attachClickListeners();
	}

	/**
	 * Attach click listeners to all button variants
	 */
	attachClickListeners() {
		// Inline buttons (don't auto-select shipping)
		this.#attachClickListener(`.${ButtonManager.CLASSES.INLINE}`, false);
		
		// Checkbox buttons (auto-select shipping)
		this.#attachClickListener(`.${ButtonManager.CLASSES.CHECKBOX}`, true);
		
		// Auto-select buttons (auto-select shipping)
		this.#attachClickListener(`.${ButtonManager.CLASSES.AUTO_SELECT}`, true);
	}

	/**
	 * Attach click listener to button selector
	 * @private
	 * @param {string} selector - CSS selector
	 * @param {boolean} autoSelectShipping - Whether to auto-select BoxNow shipping
	 */
	#attachClickListener(selector, autoSelectShipping) {
		const buttons = document.querySelectorAll(selector);
		
		buttons.forEach(button => {
			// Remove existing listener (if any)
			button.removeEventListener('click', button._boxnowClickHandler);
			
			// Create new handler
			const handler = (event) => {
				event.preventDefault();

				if (autoSelectShipping) {
					this.shippingService.selectBoxNow();
				}

				this.eventBus.emit('widget:open-requested');
			};

			// Store handler reference for removal
			button._boxnowClickHandler = handler;
			
			// Attach listener
			button.addEventListener('click', handler);
		});
	}

	/**
	 * Show inline buttons (when BoxNow shipping is selected)
	 */
	showInline() {
		const buttons = document.querySelectorAll(`.${ButtonManager.CLASSES.INLINE}`);
		buttons.forEach(button => button.style.display = 'inline-block');
	}

	/**
	 * Hide inline buttons (when BoxNow shipping is not selected)
	 */
	hideInline() {
		const buttons = document.querySelectorAll(`.${ButtonManager.CLASSES.INLINE}`);
		buttons.forEach(button => button.style.display = 'none');
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
