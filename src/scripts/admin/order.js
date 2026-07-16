/**
 * Admin order page entry point.
 * Uses OOP architecture - no jQuery.
 */
import { ApiClient } from '../checkout/core/ApiClient.js';
import { VoucherManager } from './VoucherManager.js';

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
	// Ensure global settings are available
	if (typeof myAjax === 'undefined') {
		console.error('myAjax not found');
		return;
	}

	// Create API client
	const apiClient = ApiClient.fromGlobal(myAjax);

	// Create and initialize voucher manager
	const voucherManager = new VoucherManager(apiClient);
	voucherManager.init();
});
