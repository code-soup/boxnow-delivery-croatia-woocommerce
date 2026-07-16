/**
 * Manages locker data persistence in localStorage and WooCommerce session.
 */
import { safeJsonParse, isValidLockerData } from './ValidationHelpers.js';

export class LockerStorage {
	/**
	 * Storage keys
	 */
	static KEYS = {
		SELECTED_LOCKER: 'box_now_selected_locker',
		WAREHOUSE: 'boxnow_warehouse',
	};

	/**
	 * @param {Object} apiClient - API client for AJAX calls
	 */
	constructor(apiClient) {
		this.apiClient = apiClient;
	}

	/**
	 * Save locker data to localStorage
	 * @param {Object} data - Locker data object
	 */
	save(data) {
		if (!data) {
			return;
		}

		try {
			const jsonData = JSON.stringify(data);
			localStorage.setItem(LockerStorage.KEYS.SELECTED_LOCKER, jsonData);
		} catch (error) {
			console.error('Failed to save locker data to localStorage:', error);
		}
	}

	/**
	 * Load locker data from localStorage
	 * @returns {Object|null} Locker data or null
	 */
	load() {
		try {
			const jsonData = localStorage.getItem(LockerStorage.KEYS.SELECTED_LOCKER);
			const data = safeJsonParse(jsonData, null);

			// Validate schema before returning
			if (data && !isValidLockerData(data)) {
				console.warn('Invalid locker data in localStorage, clearing');
				this.clear();
				return null;
			}

			return data;
		} catch (error) {
			console.error('Failed to load locker data from localStorage:', error);
			return null;
		}
	}

	/**
	 * Clear locker data from localStorage
	 */
	clear() {
		try {
			localStorage.removeItem(LockerStorage.KEYS.SELECTED_LOCKER);
		} catch (error) {
			console.error('Failed to clear locker data from localStorage:', error);
		}
	}

	/**
	 * Save locker data to WooCommerce session via AJAX
	 * @param {Object} data - Locker data object
	 * @returns {Promise<Object>} API response
	 */
	async saveToSession(data) {
		if (!this.apiClient) {
			console.warn('No API client available for session save');
			return { success: false };
		}

		try {
			const response = await this.apiClient.post('box_now_save_locker_data', {
				locker_data: JSON.stringify(data),
			});
			return response;
		} catch (error) {
			console.error('Failed to save locker data to session:', error);
			return { success: false, error: error.message };
		}
	}

	/**
	 * Clear locker data from WooCommerce session via AJAX
	 * @returns {Promise<Object>} API response
	 */
	async clearSession() {
		if (!this.apiClient) {
			console.warn('No API client available for session clear');
			return { success: false };
		}

		try {
			const response = await this.apiClient.post('box_now_remove_locker_data', {});
			return response;
		} catch (error) {
			console.error('Failed to clear locker data from session:', error);
			return { success: false, error: error.message };
		}
	}

	/**
	 * Save and sync locker data to both localStorage and session
	 * @param {Object} data - Locker data object
	 * @returns {Promise<Object>} Session save response
	 */
	async saveAndSync(data) {
		// Save to localStorage first (synchronous)
		this.save(data);
		
		// Then sync to session (asynchronous)
		return await this.saveToSession(data);
	}

	/**
	 * Clear and sync locker data from both localStorage and session
	 * @returns {Promise<Object>} Session clear response
	 */
	async clearAndSync() {
		// Clear localStorage first (synchronous)
		this.clear();
		
		// Then clear session (asynchronous)
		return await this.clearSession();
	}

	/**
	 * Check if locker data exists in localStorage
	 * @returns {boolean}
	 */
	hasData() {
		return localStorage.getItem(LockerStorage.KEYS.SELECTED_LOCKER) !== null;
	}

	/**
	 * Get warehouse data from localStorage
	 * @returns {string|null}
	 */
	getWarehouse() {
		return localStorage.getItem(LockerStorage.KEYS.WAREHOUSE);
	}

	/**
	 * Save warehouse data to localStorage
	 * @param {string} warehouse - Warehouse identifier
	 */
	saveWarehouse(warehouse) {
		if (warehouse) {
			localStorage.setItem(LockerStorage.KEYS.WAREHOUSE, warehouse);
		}
	}

	/**
	 * Clear warehouse data from localStorage
	 */
	clearWarehouse() {
		localStorage.removeItem(LockerStorage.KEYS.WAREHOUSE);
	}
}
