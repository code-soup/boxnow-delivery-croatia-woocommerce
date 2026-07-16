/**
 * Classic WooCommerce checkout entry point.
 * Uses OOP architecture - no jQuery.
 */
import { CheckoutClassic } from './CheckoutClassic.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
	// Ensure global settings are available
	if (typeof boxNowDeliverySettings === 'undefined') {
		console.error('boxNowDeliverySettings not found');
		return;
	}

	// Create and run checkout
	const checkout = new CheckoutClassic(boxNowDeliverySettings);
	checkout.run();
});

