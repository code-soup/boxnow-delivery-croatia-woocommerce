/**
 * Renders selected locker details in the checkout UI.
 */
import { DOMSelectors } from '../core/index.js';

export class DetailsRenderer {
	/**
	 * Container IDs/classes
	 */
	static CONTAINER_ID = 'box_now_selected_locker_details';
	static CONTAINER_CLASS = 'box-now-selected-locker-details';

	/**
	 * @param {Object} config - Global settings
	 */
	constructor(config) {
		this.config = config;
	}

	/**
	 * Ensure details container exists in DOM
	 */
	ensureContainer() {
		// Check if container already exists
		if (document.getElementById(DetailsRenderer.CONTAINER_ID)) {
			return;
		}

		// Create container
		const container = document.createElement('div');
		container.id = DetailsRenderer.CONTAINER_ID;
		container.style.display = 'none';

		// Find insertion point: after button or after shipping method label
		const button = document.querySelector('.box-now-delivery-button');
		if (button) {
			button.insertAdjacentElement('afterend', container);
		} else {
			const label = document.querySelector(DOMSelectors.BOXNOW_METHOD_LABEL);
			if (label) {
				label.insertAdjacentElement('afterend', container);
			}
		}
	}

	/**
	 * Render locker details
	 * @param {Object} lockerData - Normalized locker data
	 */
	render(lockerData) {
		if (!lockerData) {
			return;
		}

		// Ensure container exists
		this.ensureContainer();

		// Get language (default to English)
		const language = document.documentElement.lang || 'en';

		// Extract locker details
		const {
			locker_id,
			name,
			addressLine1,
			addressLine2,
			city,
			postalCode,
			note,
			image,
		} = lockerData;

		// Build HTML content
		const noteHtml = note
			? `<p class="locker-detail" style="font-style: italic; margin-top: 10px;">${this.#escapeHtml(note)}</p>`
			: '';

		const imageHtml = image
			? `<p><img src="${this.#escapeHtml(image)}" alt="${this.#escapeHtml(name)}" style="max-width: 100%; height: auto; margin-top: 10px; border: 1px solid #ddd;" /></p>`
			: '';

		const title = language === 'el' ? 'Επιλεγμένο Locker' : 'Selected Locker';
		const displayCity = city || addressLine2 || '';

		const content = `
			<div id="locker-info">
				<p class="locker-title"><b>${title}</b></p>
				<p class="locker-detail">${this.#escapeHtml(name)}</p>
				<p class="locker-detail">${this.#escapeHtml(addressLine1)}, ${this.#escapeHtml(displayCity)}</p>
				<p class="locker-detail">${this.#escapeHtml(postalCode)}</p>
				${noteHtml}
				${imageHtml}
			</div>
		`;

		// Update both ID and class selectors for compatibility
		const containerById = document.getElementById(DetailsRenderer.CONTAINER_ID);
		if (containerById) {
			containerById.innerHTML = content;
			containerById.style.display = 'block';
		}

		const containersByClass = document.querySelectorAll(`.${DetailsRenderer.CONTAINER_CLASS}`);
		containersByClass.forEach(container => {
			container.innerHTML = content;
			container.style.display = 'block';
		});

		// Create/update hidden input for locker ID
		this.#createHiddenInput('_boxnow_locker_id', locker_id);

		// Create/update hidden input for full locker data
		this.#createHiddenInput('box_now_selected_locker_input', JSON.stringify(lockerData));
	}

	/**
	 * Clear locker details display
	 */
	clear() {
		const containerById = document.getElementById(DetailsRenderer.CONTAINER_ID);
		if (containerById) {
			containerById.innerHTML = '';
			containerById.style.display = 'none';
		}

		const containersByClass = document.querySelectorAll(`.${DetailsRenderer.CONTAINER_CLASS}`);
		containersByClass.forEach(container => {
			container.innerHTML = '';
			container.style.display = 'none';
		});

		// Remove hidden inputs
		const lockerIdInput = document.getElementById('_boxnow_locker_id');
		if (lockerIdInput) {
			lockerIdInput.remove();
		}

		const lockerDataInput = document.getElementById('box_now_selected_locker_input');
		if (lockerDataInput) {
			lockerDataInput.remove();
		}
	}

	/**
	 * Create or update hidden input field
	 * @private
	 * @param {string} id - Input ID
	 * @param {string} value - Input value
	 */
	#createHiddenInput(id, value) {
		let input = document.getElementById(id);
		
		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.id = id;
			input.name = id;
			
			const container = document.getElementById(DetailsRenderer.CONTAINER_ID);
			if (container) {
				container.appendChild(input);
			}
		}
		
		input.value = value;
	}

	/**
	 * Escape HTML to prevent XSS
	 * @private
	 * @param {string} text - Text to escape
	 * @returns {string}
	 */
	#escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
}
