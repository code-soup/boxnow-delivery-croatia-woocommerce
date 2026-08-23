/**
 * Logger utility for conditional logging
 * Only logs in development mode
 */

/**
 * Check if in development mode
 * @returns {boolean}
 */
const isDevelopment = () => {
	// Check if webpack defined NODE_ENV
	if (typeof process !== 'undefined' && process.env && process.env.NODE_ENV) {
		return process.env.NODE_ENV === 'development';
	}
	
	// Fallback: check for localhost or .local domains
	if (typeof window !== 'undefined') {
		const hostname = window.location.hostname;
		return hostname === 'localhost' || 
		       hostname.endsWith('.local') || 
		       hostname.startsWith('127.') ||
		       hostname === '::1';
	}
	
	return false;
};

/**
 * Logger class with conditional output
 */
export class Logger {
	constructor(prefix = '[BoxNow]') {
		this.prefix = prefix;
		this.isDev = isDevelopment();
	}

	/**
	 * Log a message (development only)
	 * @param {...any} args - Arguments to log
	 */
	log(...args) {
		if (this.isDev) {
			console.log(this.prefix, ...args);
		}
	}

	/**
	 * Log a warning (always shown)
	 * @param {...any} args - Arguments to log
	 */
	warn(...args) {
		console.warn(this.prefix, ...args);
	}

	/**
	 * Log an error (always shown)
	 * @param {...any} args - Arguments to log
	 */
	error(...args) {
		console.error(this.prefix, ...args);
	}

	/**
	 * Log debug information (development only)
	 * @param {...any} args - Arguments to log
	 */
	debug(...args) {
		if (this.isDev) {
			console.debug(this.prefix, ...args);
		}
	}

	/**
	 * Log an object/value with label (development only)
	 * @param {string} label - Label for the value
	 * @param {any} value - Value to log
	 */
	logValue(label, value) {
		if (this.isDev) {
			console.log(`${this.prefix} ${label}:`, value);
		}
	}
}

/**
 * Default logger instance
 */
export const logger = new Logger('[BoxNow]');
