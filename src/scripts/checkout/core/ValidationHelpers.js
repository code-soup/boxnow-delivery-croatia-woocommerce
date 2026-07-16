/**
 * Helper functions for data validation and sanitization.
 */

/**
 * Safely parse JSON with fallback.
 * @param {string} jsonString - JSON string to parse
 * @param {*} fallback - Fallback value if parsing fails
 * @returns {*} Parsed object or fallback
 */
export function safeJsonParse(jsonString, fallback = null) {
	if (!jsonString || typeof jsonString !== 'string') {
		return fallback;
	}

	try {
		return JSON.parse(jsonString);
	} catch (error) {
		console.error('JSON parse failed:', error);
		return fallback;
	}
}

/**
 * Validate locker data schema.
 * @param {Object} data - Data to validate
 * @returns {boolean} True if valid
 */
export function isValidLockerData(data) {
	if (!data || typeof data !== 'object') {
		return false;
	}

	// Required fields
	const requiredFields = ['locker_id', 'name', 'addressLine1', 'postalCode'];
	
	for (const field of requiredFields) {
		if (!data[field] || typeof data[field] !== 'string') {
			return false;
		}
	}

	return true;
}

/**
 * Validate array of strings.
 * @param {*} value - Value to validate
 * @returns {boolean} True if valid string array
 */
export function isStringArray(value) {
	if (!Array.isArray(value)) {
		return false;
	}

	return value.every(item => typeof item === 'string');
}

/**
 * Sanitize error message for display.
 * @param {Error|string} error - Error object or message
 * @returns {string} Safe error message
 */
export function sanitizeErrorMessage(error) {
	if (!error) {
		return 'An unknown error occurred.';
	}

	const message = typeof error === 'string' ? error : error.message;
	
	// Remove stack traces, file paths, internal details
	const cleaned = message
		.replace(/at\s+.*?\(.*?\)/g, '')
		.replace(/\/.*?\//g, '')
		.trim();

	return cleaned || 'An error occurred. Please try again.';
}
