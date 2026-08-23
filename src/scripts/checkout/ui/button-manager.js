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
		console.log('[BOXNOW DEBUG] render() called, displayMode:', this.config.displayMode);

		if (this.config.displayMode !== 'popup') {
			console.log('[BOXNOW DEBUG] Display mode is not popup, skipping render');
			return;
		}

		const buttonPosition = this.config.buttonPosition || 'inline';
		console.log('[BOXNOW DEBUG] Button position:', buttonPosition);

		// Render inline button if needed
		if (buttonPosition === 'inline' || buttonPosition === 'both') {
			console.log('[BOXNOW DEBUG] Rendering inline button');
			this.renderInlineButton();
		}

		// Attach click listeners to all button types after DOM insertion
		// Use setTimeout to ensure button is in DOM
		console.log('[BOXNOW DEBUG] Scheduling attachClickListeners');
		setTimeout(() => {
			this.attachClickListeners();
		}, 0);
	}

	/**
	 * Attach click listeners to all button variants
	 */
	attachClickListeners() {
		console.log('[BOXNOW DEBUG] attachClickListeners() called');

		// Inline buttons (don't auto-select shipping)
		this.#attachClickListener(`.${CSSClasses.BUTTON_INLINE}`, false);

		// Checkbox buttons (auto-select shipping)
		this.#attachClickListener(`.${CSSClasses.BUTTON_CHECKBOX}`, true);

		// Auto-select buttons (auto-select shipping)
		this.#attachClickListener(`.${CSSClasses.BUTTON_AUTO_SELECT}`, true);

		console.log('[BOXNOW DEBUG] All click listeners attached');
	}

	/**
	 * Attach click listener to button selector
	 * @private
	 * @param {string} selector - CSS selector
	 * @param {boolean} autoSelectShipping - Whether to auto-select BoxNow shipping
	 */
	#attachClickListener(selector, autoSelectShipping) {
		const buttons = document.querySelectorAll(selector);

		console.log('[BOXNOW DEBUG] Attaching click listeners to', selector, '- found', buttons.length, 'buttons');
		logger.log('Attaching click listeners to', selector, '- found', buttons.length, 'buttons');

		buttons.forEach((button, index) => {
			console.log(`[BOXNOW DEBUG] Processing button ${index + 1}:`, button);

			// Remove existing listener to prevent duplicates
			if (button._boxnowClickHandler) {
				button.removeEventListener('click', button._boxnowClickHandler);
				console.log(`[BOXNOW DEBUG] Removed existing handler from button ${index + 1}`);
			}

			// Create new handler - using capture phase to catch event early
			const handler = (event) => {
				console.log('[BOXNOW DEBUG] ===== BUTTON CLICKED! =====');
				console.log('[BOXNOW DEBUG] Selector:', selector);
				console.log('[BOXNOW DEBUG] Button:', event.target);
				console.log('[BOXNOW DEBUG] Event:', event);
				console.log('[BOXNOW DEBUG] Event phase:', event.eventPhase);
				console.log('[BOXNOW DEBUG] Event bubbles:', event.bubbles);
				console.log('[BOXNOW DEBUG] Event cancelable:', event.cancelable);

				logger.log('Button clicked!', selector);
				event.preventDefault();
				event.stopPropagation();

				console.log('[BOXNOW DEBUG] Event prevented, autoSelectShipping:', autoSelectShipping);

				if (autoSelectShipping) {
					console.log('[BOXNOW DEBUG] Auto-selecting BoxNow shipping');
					logger.log('Auto-selecting BoxNow shipping');
					this.shippingService.selectBoxNow();
				}

				console.log('[BOXNOW DEBUG] About to emit widget:open-requested');
				logger.log('Emitting widget:open-requested');
				this.eventBus.emit('widget:open-requested');
				console.log('[BOXNOW DEBUG] Event emitted successfully');
			};

			// Store handler reference for removal
			button._boxnowClickHandler = handler;

			// Attach listener with capture phase to catch it before other handlers
			button.addEventListener('click', handler, true);
			console.log(`[BOXNOW DEBUG] Click listener attached to button ${index + 1} with capture=true`);
			logger.log('Click listener attached to button');

			// Test click programmatically
			console.log('[BOXNOW DEBUG] Testing if button is clickable...');
			console.log('[BOXNOW DEBUG] Button disabled:', button.disabled);
			console.log('[BOXNOW DEBUG] Button pointer-events:', window.getComputedStyle(button).pointerEvents);
			console.log('[BOXNOW DEBUG] Button z-index:', window.getComputedStyle(button).zIndex);
			console.log('[BOXNOW DEBUG] Button position:', window.getComputedStyle(button).position);
			console.log('[BOXNOW DEBUG] Button display:', window.getComputedStyle(button).display);
		});
	}

	/**
	 * Show inline buttons (when BoxNow shipping is selected)
	 */
	showInline() {
		const buttons = document.querySelectorAll(`.${CSSClasses.BUTTON_INLINE}`);
		console.log('[BOXNOW DEBUG] showInline() - found', buttons.length, 'buttons');
		buttons.forEach(button => {
			console.log('[BOXNOW DEBUG] Removing hidden, adding visible-inline to:', button);
			button.classList.remove(CSSClasses.HIDDEN);
			button.classList.add(CSSClasses.VISIBLE_INLINE);
			console.log('[BOXNOW DEBUG] Button classes after show:', button.className);
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

		console.log('[BOXNOW DEBUG] updateVisibility() called - isBoxNowSelected:', isBoxNowSelected);

		if (isBoxNowSelected) {
			console.log('[BOXNOW DEBUG] Showing inline button');
			this.showInline();
		} else {
			console.log('[BOXNOW DEBUG] Hiding inline button');
			this.hideInline();
		}
	}
}
