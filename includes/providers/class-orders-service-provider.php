<?php
/**
 * Orders Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Orders\Order_Handler;

/**
 * The orders service provider.
 */
class OrdersServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton(
			'order_handler',
			function ( $container ) {
				return new Order_Handler(
					$container->get( 'hooker' ),
					$container->get( 'delivery_service' ),
					$container->get( 'parcel_service' )
				);
			}
		);
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		// Initialize order handler
		$this->container->get( 'order_handler' )->init();
	}
}
