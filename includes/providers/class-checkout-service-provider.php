<?php
/**
 * Checkout Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Checkout\Checkout_Handler;

/**
 * The checkout service provider.
 */
class CheckoutServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton( 'checkout_handler', Checkout_Handler::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		// Initialize checkout handler
		$this->container->get( 'checkout_handler' )->init();
	}
}
