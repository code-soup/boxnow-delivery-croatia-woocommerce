/**
 * API client for AJAX requests using native fetch.
 * Replaces jQuery.post() and jQuery.ajax().
 */
export class ApiClient {
	/**
	 * @param {string} ajaxUrl - WordPress AJAX URL
	 * @param {string} nonce - Security nonce
	 */
	constructor(ajaxUrl, nonce) {
		this.ajaxUrl = ajaxUrl;
		this.nonce = nonce;
	}

	/**
	 * Send POST request to WordPress AJAX handler
	 * @param {string} action - WordPress action name
	 * @param {Object} data - Additional data to send
	 * @returns {Promise<Object>} Response data
	 */
	async post(action, data = {}) {
		const formData = new FormData();
		formData.append('action', action);
		formData.append('security', this.nonce);
		
		// Append all data fields
		Object.entries(data).forEach(([key, value]) => {
			formData.append(key, value);
		});

		try {
			const response = await fetch(this.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			const result = await response.json();
			return result;
		} catch (error) {
			console.error(`API request failed for action "${action}":`, error);
			throw error;
		}
	}

	/**
	 * Send GET request
	 * @param {string} url - Full URL
	 * @param {Object} params - Query parameters
	 * @returns {Promise<Object>} Response data
	 */
	async get(url, params = {}) {
		const urlObj = new URL(url);
		Object.entries(params).forEach(([key, value]) => {
			urlObj.searchParams.append(key, value);
		});

		try {
			const response = await fetch(urlObj.toString(), {
				method: 'GET',
				credentials: 'same-origin',
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			return await response.json();
		} catch (error) {
			console.error('GET request failed:', error);
			throw error;
		}
	}

	/**
	 * Send POST request with JSON body
	 * @param {string} url - Full URL
	 * @param {Object} data - Data object to send as JSON
	 * @returns {Promise<Object>} Response data
	 */
	async postJson(url, data = {}) {
		try {
			const response = await fetch(url, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(data),
				credentials: 'same-origin',
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			return await response.json();
		} catch (error) {
			console.error('POST JSON request failed:', error);
			throw error;
		}
	}

	/**
	 * Create API client from global settings
	 * @param {Object} settings - Global settings object (e.g., myAjax)
	 * @returns {ApiClient}
	 */
	static fromGlobal(settings) {
		if (!settings || !settings.ajaxurl || !settings.nonce) {
			throw new Error('Invalid settings object. Must contain ajaxurl and nonce.');
		}
		return new ApiClient(settings.ajaxurl, settings.nonce);
	}
}
