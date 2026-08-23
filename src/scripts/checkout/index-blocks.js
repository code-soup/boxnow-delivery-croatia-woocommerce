/**
 * WooCommerce Blocks checkout entry point.
 * Uses OOP architecture - no jQuery.
 */
import { CheckoutBlocks } from './orchestrators/checkout-blocks.js';
import { logger } from './utils/logger.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
	// Ensure global settings are available
	if (typeof boxNowDeliverySettings === 'undefined') {
		logger.error('boxNowDeliverySettings not found');
		return;
	}

	// Create and run Blocks checkout
	const checkout = new CheckoutBlocks(boxNowDeliverySettings);
	checkout.run();
});
