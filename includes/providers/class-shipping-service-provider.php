<?php
/**
 * Shipping Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Shipping\Shipping_Method;
use CodeSoup\BoxNow\Services\Shipping\Payment_Gateway_Handler;

/**
 * The shipping service provider.
 */
class ShippingServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton( 'shipping_method', Shipping_Method::class );
		$this->singleton( 'payment_gateway_handler', Payment_Gateway_Handler::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		// Register shipping method
		$this->container->get( 'hooker' )->add_filter(
			'woocommerce_shipping_methods',
			$this,
			'register_shipping_method'
		);

		// Initialize payment gateway handler
		$this->container->get( 'payment_gateway_handler' )->init();
	}

	/**
	 * Register BoxNow shipping method.
	 *
	 * @param array $methods Shipping methods.
	 * @return array
	 */
	public function register_shipping_method( $methods ) {
		$methods['codesoup_box_now_delivery'] = Shipping_Method::class;
		return $methods;
	}
}
