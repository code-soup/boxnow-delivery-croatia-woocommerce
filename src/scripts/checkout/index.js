/**
 * Classic WooCommerce checkout entry point.
 * Uses OOP architecture - no jQuery.
 */
import { CheckoutClassic } from './orchestrators/checkout-classic.js';
import { logger } from './utils/logger.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
	logger.log('DOM ready, initializing checkout');

	// Ensure global settings are available
	if (typeof boxNowDeliverySettings === 'undefined') {
		logger.error('boxNowDeliverySettings not found');
		return;
	}

	logger.logValue('Settings', boxNowDeliverySettings);

	// Create and run checkout
	const checkout = new CheckoutClassic(boxNowDeliverySettings);
	checkout.run();

	logger.log('Checkout initialized');
});

