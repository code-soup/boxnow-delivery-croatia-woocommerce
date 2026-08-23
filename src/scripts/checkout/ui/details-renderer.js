/**
 * Renders selected locker details in the checkout UI.
 */
import { ElementIDs, CSSClasses, Selectors } from '../core/index.js';

export class DetailsRenderer {

	/**
	 * @param {Object} config - Global settings
	 * @param {EventBus} eventBus - Event bus (optional)
	 */
	constructor(config, eventBus = null) {
		this.config = config;
		this.eventBus = eventBus;
	}

	/**
	 * Ensure details container exists in DOM
	 */
	ensureContainer() {
		// Check if container already exists
		if (document.getElementById(ElementIDs.LOCKER_DETAILS)) {
			return;
		}

		// Create container
		const container = document.createElement('div');
		container.id = ElementIDs.LOCKER_DETAILS;
		container.classList.add(CSSClasses.HIDDEN);

		// Find insertion point: after button or after shipping method label
		const button = document.querySelector(`.${CSSClasses.BUTTON_BASE}`);
		if (button) {
			button.insertAdjacentElement('afterend', container);
		} else {
			const label = document.querySelector(Selectors.BOXNOW_METHOD_LABEL);
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
			? `<p class="${CSSClasses.LOCKER_DETAIL} ${CSSClasses.LOCKER_NOTE}">${this.#escapeHtml(note)}</p>`
			: '';

		const imageHtml = image
			? `<p><img class="${CSSClasses.LOCKER_IMAGE}" src="${this.#escapeHtml(image)}" alt="${this.#escapeHtml(name)}" /></p>`
			: '';

		// Get localized strings from global config
		const title = this.config.i18n?.selectedLocker || 'Selected Locker';
		const changeText = this.config.i18n?.changeButton || 'Change';
		const displayCity = city || addressLine2 || '';

		const content = `
			<div id="${ElementIDs.LOCKER_INFO}">
				<p class="${CSSClasses.LOCKER_TITLE}">
					<b>${title}</b>
					<button type="button" class="${CSSClasses.CLEAR_LOCKER}">${changeText}</button>
				</p>
				<p class="${CSSClasses.LOCKER_DETAIL}">${this.#escapeHtml(name)}</p>
				<p class="${CSSClasses.LOCKER_DETAIL}">${this.#escapeHtml(addressLine1)}, ${this.#escapeHtml(displayCity)}</p>
				<p class="${CSSClasses.LOCKER_DETAIL}">${this.#escapeHtml(postalCode)}</p>
				${noteHtml}
				${imageHtml}
			</div>
		`;

		// Update both ID and class selectors for compatibility
		const containerById = document.getElementById(ElementIDs.LOCKER_DETAILS);
		if (containerById) {
			containerById.innerHTML = content;
			containerById.classList.remove(CSSClasses.HIDDEN);
			containerById.classList.add(CSSClasses.VISIBLE);
		}

		const containersByClass = document.querySelectorAll(`.${CSSClasses.LOCKER_DETAILS}`);
		containersByClass.forEach(container => {
			container.innerHTML = content;
			container.classList.remove(CSSClasses.HIDDEN);
			container.classList.add(CSSClasses.VISIBLE);
		});

		// Attach clear button listeners
		this.#attachClearListeners();

		// Create/update hidden input for locker ID (WordPress meta key format with underscore)
		this.#createHiddenInput(ElementIDs.LOCKER_ID_INPUT, locker_id);

		// Create/update hidden input for full locker data
		this.#createHiddenInput(ElementIDs.LOCKER_DATA_INPUT, JSON.stringify(lockerData));
	}

	/**
	 * Attach click listeners to clear buttons
	 * @private
	 */
	#attachClearListeners() {
		const clearButtons = document.querySelectorAll(`.${CSSClasses.CLEAR_LOCKER}`);

		clearButtons.forEach(button => {
			// Remove existing listener to prevent duplicates
			if (button._boxnowClearHandler) {
				button.removeEventListener('click', button._boxnowClearHandler);
			}

			// Create handler
			const handler = (e) => {
				e.preventDefault();

				// Emit clear event if eventBus is available
				if (this.eventBus) {
					this.eventBus.emit('locker:clear-requested');
				}
			};

			// Store handler reference and attach
			button._boxnowClearHandler = handler;
			button.addEventListener('click', handler);
		});
	}

	/**
	 * Clear locker details display
	 */
	clear() {
		const containerById = document.getElementById(ElementIDs.LOCKER_DETAILS);
		if (containerById) {
			containerById.innerHTML = '';
			containerById.classList.remove(CSSClasses.VISIBLE);
			containerById.classList.add(CSSClasses.HIDDEN);
		}

		const containersByClass = document.querySelectorAll(`.${CSSClasses.LOCKER_DETAILS}`);
		containersByClass.forEach(container => {
			container.innerHTML = '';
			container.classList.remove(CSSClasses.VISIBLE);
			container.classList.add(CSSClasses.HIDDEN);
		});

		// Remove hidden inputs
		const lockerIdInput = document.getElementById(ElementIDs.LOCKER_ID_INPUT);
		if (lockerIdInput) {
			lockerIdInput.remove();
		}

		const lockerDataInput = document.getElementById(ElementIDs.LOCKER_DATA_INPUT);
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
			
			const container = document.getElementById(ElementIDs.LOCKER_DETAILS);
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
