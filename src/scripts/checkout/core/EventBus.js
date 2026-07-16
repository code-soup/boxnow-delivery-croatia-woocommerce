/**
 * Event bus for cross-component communication.
 * Decouples components by providing publish/subscribe pattern.
 */
export class EventBus {
	/**
	 * Event listeners mapped by event name
	 * @private
	 */
	#listeners = new Map();

	/**
	 * Subscribe to an event
	 * @param {string} eventName - Event name
	 * @param {Function} callback - Callback function
	 * @returns {Function} Unsubscribe function
	 */
	on(eventName, callback) {
		if (!this.#listeners.has(eventName)) {
			this.#listeners.set(eventName, []);
		}
		
		this.#listeners.get(eventName).push(callback);
		
		// Return unsubscribe function
		return () => this.off(eventName, callback);
	}

	/**
	 * Subscribe to an event (one-time)
	 * @param {string} eventName - Event name
	 * @param {Function} callback - Callback function
	 * @returns {Function} Unsubscribe function
	 */
	once(eventName, callback) {
		const wrapper = (...args) => {
			callback(...args);
			this.off(eventName, wrapper);
		};
		
		return this.on(eventName, wrapper);
	}

	/**
	 * Unsubscribe from an event
	 * @param {string} eventName - Event name
	 * @param {Function} callback - Callback function to remove
	 */
	off(eventName, callback) {
		const callbacks = this.#listeners.get(eventName);
		if (!callbacks) {
			return;
		}
		
		const index = callbacks.indexOf(callback);
		if (index > -1) {
			callbacks.splice(index, 1);
		}
		
		// Clean up empty arrays
		if (callbacks.length === 0) {
			this.#listeners.delete(eventName);
		}
	}

	/**
	 * Emit an event
	 * @param {string} eventName - Event name
	 * @param {...*} args - Arguments to pass to callbacks
	 */
	emit(eventName, ...args) {
		const callbacks = this.#listeners.get(eventName);
		if (!callbacks) {
			return;
		}
		
		callbacks.forEach(callback => {
			try {
				callback(...args);
			} catch (error) {
				console.error(`Error in event handler for "${eventName}":`, error);
			}
		});
	}

	/**
	 * Remove all listeners for an event (or all events if no name provided)
	 * @param {string} [eventName] - Optional event name
	 */
	clear(eventName) {
		if (eventName) {
			this.#listeners.delete(eventName);
		} else {
			this.#listeners.clear();
		}
	}

	/**
	 * Get count of listeners for an event
	 * @param {string} eventName - Event name
	 * @returns {number}
	 */
	listenerCount(eventName) {
		const callbacks = this.#listeners.get(eventName);
		return callbacks ? callbacks.length : 0;
	}

	/**
	 * Get all event names that have listeners
	 * @returns {string[]}
	 */
	eventNames() {
		return Array.from(this.#listeners.keys());
	}
}

/**
 * Standard events used across the application
 */
export const Events = {
	// Locker events
	LOCKER_SELECTED: 'locker:selected',
	LOCKER_CLEARED: 'locker:cleared',
	LOCKER_DATA_UPDATED: 'locker:data-updated',
	
	// Shipping events
	SHIPPING_METHOD_CHANGED: 'shipping:method-changed',
	SHIPPING_BOXNOW_SELECTED: 'shipping:boxnow-selected',
	SHIPPING_BOXNOW_DESELECTED: 'shipping:boxnow-deselected',
	
	// Address events
	ADDRESS_POPULATED: 'address:populated',
	ADDRESS_RESTORED: 'address:restored',
	
	// Widget events
	WIDGET_OPENED: 'widget:opened',
	WIDGET_CLOSED: 'widget:closed',
	WIDGET_MESSAGE_RECEIVED: 'widget:message-received',
	
	// Checkout events
	CHECKOUT_UPDATED: 'checkout:updated',
	CHECKOUT_VALIDATION_FAILED: 'checkout:validation-failed',
	
	// Country events
	COUNTRY_CHANGED: 'country:changed',
};
