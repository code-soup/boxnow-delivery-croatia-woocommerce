<?php
/**
 * AJAX Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Ajax\Order_AJAX_Handler;

/**
 * The AJAX service provider.
 */
class AjaxServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton(
			'order_ajax_handler',
			function ( $container ) {
				return new Order_AJAX_Handler(
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

		// Initialize AJAX handlers
		$this->container->get( 'order_ajax_handler' )->init();
	}
}
