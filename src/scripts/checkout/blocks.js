/**
 * WooCommerce Blocks checkout entry point.
 * Uses OOP architecture - no jQuery.
 */
import { CheckoutBlocks } from './CheckoutBlocks.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
	// Ensure global settings are available
	if (typeof boxNowDeliverySettings === 'undefined') {
		console.error('boxNowDeliverySettings not found');
		return;
	}

	// Create and run Blocks checkout
	const checkout = new CheckoutBlocks(boxNowDeliverySettings);
	checkout.run();
});
