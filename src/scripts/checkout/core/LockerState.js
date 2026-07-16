/**
 * Reactive state management for locker selection.
 * Provides centralized state with subscription support.
 */
export class LockerState {
	/**
	 * Internal state object
	 * @private
	 */
	#state = {
		lockerSelected: false,
		isSelectingShipping: false,
		isPopulatingAddress: false,
		selectedLocker: null,
		originalAddress: null,
	};

	/**
	 * Subscription callbacks mapped by state key
	 * @private
	 */
	#listeners = new Map();

	/**
	 * Get a state value
	 * @param {string} key - State key
	 * @returns {*} State value
	 */
	get(key) {
		return this.#state[key];
	}

	/**
	 * Set a state value and notify subscribers
	 * @param {string} key - State key
	 * @param {*} value - New value
	 */
	set(key, value) {
		const oldValue = this.#state[key];
		this.#state[key] = value;
		
		if (oldValue !== value) {
			this.#notify(key, value, oldValue);
		}
	}

	/**
	 * Update multiple state values at once
	 * @param {Object} updates - Key-value pairs to update
	 */
	update(updates) {
		Object.entries(updates).forEach(([key, value]) => {
			this.set(key, value);
		});
	}

	/**
	 * Subscribe to state changes
	 * @param {string} key - State key to watch
	 * @param {Function} callback - Callback function (newValue, oldValue)
	 * @returns {Function} Unsubscribe function
	 */
	subscribe(key, callback) {
		if (!this.#listeners.has(key)) {
			this.#listeners.set(key, []);
		}
		
		this.#listeners.get(key).push(callback);
		
		// Return unsubscribe function
		return () => {
			const callbacks = this.#listeners.get(key);
			const index = callbacks.indexOf(callback);
			if (index > -1) {
				callbacks.splice(index, 1);
			}
		};
	}

	/**
	 * Notify all subscribers of a state change
	 * @private
	 * @param {string} key - State key that changed
	 * @param {*} newValue - New value
	 * @param {*} oldValue - Previous value
	 */
	#notify(key, newValue, oldValue) {
		const callbacks = this.#listeners.get(key);
		if (callbacks) {
			callbacks.forEach(callback => {
				try {
					callback(newValue, oldValue);
				} catch (error) {
					console.error(`Error in state subscriber for key "${key}":`, error);
				}
			});
		}
	}

	/**
	 * Reset state to initial values
	 */
	reset() {
		this.update({
			lockerSelected: false,
			isSelectingShipping: false,
			isPopulatingAddress: false,
			selectedLocker: null,
			originalAddress: null,
		});
	}

	/**
	 * Get all state as plain object
	 * @returns {Object} Current state
	 */
	getAll() {
		return { ...this.#state };
	}

	/**
	 * Check if a locker is currently selected
	 * @returns {boolean}
	 */
	hasLocker() {
		return this.#state.lockerSelected && this.#state.selectedLocker !== null;
	}

	/**
	 * Get selected locker data
	 * @returns {Object|null} Locker data or null
	 */
	getLocker() {
		return this.#state.selectedLocker;
	}

	/**
	 * Set selected locker data
	 * @param {Object|null} lockerData - Locker data object
	 */
	setLocker(lockerData) {
		this.update({
			selectedLocker: lockerData,
			lockerSelected: lockerData !== null,
		});
	}

	/**
	 * Clear selected locker
	 */
	clearLocker() {
		this.setLocker(null);
	}
}
