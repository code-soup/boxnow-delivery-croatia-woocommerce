/**
 * Manages locker data persistence in localStorage and WooCommerce session.
 */
import { safeJsonParse, isValidLockerData } from '../utils/validation-helpers.js';
import { StorageKeys, AjaxActions } from './constants.js';

export class LockerStorage {

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
			localStorage.setItem(StorageKeys.SELECTED_LOCKER, jsonData);
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
			const jsonData = localStorage.getItem(StorageKeys.SELECTED_LOCKER);
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
			localStorage.removeItem(StorageKeys.SELECTED_LOCKER);
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
		console.log('[BOXNOW DEBUG] saveToSession() - skipping (using POST data now)');
		// Session saving is no longer needed since we use POST data directly
		// Hidden form fields are populated by details-renderer.js
		return { success: true };
	}

	/**
	 * Clear locker data from WooCommerce session via AJAX
	 * @returns {Promise<Object>} API response
	 */
	async clearSession() {
		// Session clearing is no longer needed since we use POST data directly
		// Just return success without making AJAX call
		console.log('[BOXNOW DEBUG] clearSession() - skipping (using POST data now)');
		return { success: true };
	}

	/**
	 * Save and sync locker data to both localStorage and session
	 * @param {Object} data - Locker data object
	 * @returns {Promise<Object>} Session save response
	 */
	async saveAndSync(data) {
		console.log('[BOXNOW DEBUG] saveAndSync() called with data:', data);

		// Save to localStorage first (synchronous)
		this.save(data);
		console.log('[BOXNOW DEBUG] Saved to localStorage');

		// Then sync to session (asynchronous)
		const result = await this.saveToSession(data);
		console.log('[BOXNOW DEBUG] saveAndSync() result:', result);

		return result;
	}

	/**
	 * Clear and sync locker data from both localStorage and session
	 * @returns {Promise<Object>} Session clear response
	 */
	async clearAndSync() {
		console.log('[BOXNOW DEBUG] clearAndSync() called');

		// Clear localStorage first (synchronous)
		this.clear();
		console.log('[BOXNOW DEBUG] localStorage cleared');

		// Then clear session (asynchronous)
		const result = await this.clearSession();
		console.log('[BOXNOW DEBUG] Session clear result:', result);

		return result;
	}

	/**
	 * Check if locker data exists in localStorage
	 * @returns {boolean}
	 */
	hasData() {
		return localStorage.getItem(StorageKeys.SELECTED_LOCKER) !== null;
	}

	/**
	 * Get warehouse data from localStorage
	 * @returns {string|null}
	 */
	getWarehouse() {
		return localStorage.getItem(StorageKeys.WAREHOUSE);
	}

	/**
	 * Save warehouse data to localStorage
	 * @param {string} warehouse - Warehouse identifier
	 */
	saveWarehouse(warehouse) {
		if (warehouse) {
			localStorage.setItem(StorageKeys.WAREHOUSE, warehouse);
		}
	}

	/**
	 * Clear warehouse data from localStorage
	 */
	clearWarehouse() {
		localStorage.removeItem(StorageKeys.WAREHOUSE);
	}
}
