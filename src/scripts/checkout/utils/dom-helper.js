/**
 * DOM Helper Utilities
 * Provides common DOM manipulation functions to reduce duplication
 */
import { Selectors, CSSClasses } from '../core/index.js';

export class DOMHelper {
	/**
	 * Insert element after shipping method label
	 * Common pattern used by ButtonManager, DetailsRenderer, EmbeddedManager
	 * 
	 * @param {HTMLElement} element - Element to insert
	 * @returns {boolean} - True if successfully inserted
	 */
	static insertAfterShippingLabel(element) {
		const label = document.querySelector(Selectors.BOXNOW_METHOD_LABEL);
		if (label) {
			label.insertAdjacentElement('afterend', element);
			return true;
		}
		return false;
	}

	/**
	 * Insert element after button (fallback to shipping label)
	 * Common pattern used by DetailsRenderer
	 * 
	 * @param {HTMLElement} element - Element to insert
	 * @returns {boolean} - True if successfully inserted
	 */
	static insertAfterButtonOrLabel(element) {
		// Try to insert after button first
		const button = document.querySelector(`.${CSSClasses.BUTTON_BASE}`);
		if (button) {
			button.insertAdjacentElement('afterend', element);
			return true;
		}
		
		// Fallback to shipping label
		return DOMHelper.insertAfterShippingLabel(element);
	}

	/**
	 * Create a hidden input field
	 * 
	 * @param {string} id - Input ID and name
	 * @param {string} value - Input value
	 * @param {HTMLElement} container - Container to append to
	 * @returns {HTMLInputElement} - Created or updated input element
	 */
	static createOrUpdateHiddenInput(id, value, container) {
		let input = document.getElementById(id);
		
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.id = id;
			input.name = id;
			
			if (container) {
				container.appendChild(input);
			}
		}
		
		input.value = value;
		return input;
	}

	/**
	 * Escape HTML to prevent XSS
	 * 
	 * @param {string} text - Text to escape
	 * @returns {string} - Escaped HTML
	 */
	static escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Toggle visibility classes on element
	 * 
	 * @param {HTMLElement} element - Element to toggle
	 * @param {boolean} visible - Whether to show or hide
	 * @param {boolean} inline - Whether to use inline visibility
	 */
	static toggleVisibility(element, visible, inline = false) {
		if (visible) {
			element.classList.remove(CSSClasses.HIDDEN);
			element.classList.add(inline ? CSSClasses.VISIBLE_INLINE : CSSClasses.VISIBLE);
		} else {
			element.classList.remove(CSSClasses.VISIBLE, CSSClasses.VISIBLE_INLINE);
			element.classList.add(CSSClasses.HIDDEN);
		}
	}

	/**
	 * Toggle visibility classes on multiple elements
	 * 
	 * @param {NodeList|Array} elements - Elements to toggle
	 * @param {boolean} visible - Whether to show or hide
	 * @param {boolean} inline - Whether to use inline visibility
	 */
	static toggleVisibilityMultiple(elements, visible, inline = false) {
		elements.forEach(element => {
			DOMHelper.toggleVisibility(element, visible, inline);
		});
	}
}
