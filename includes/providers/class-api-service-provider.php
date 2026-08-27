<?php
/**
 * API Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\API\Authentication_Service;
use CodeSoup\BoxNow\Services\API\Delivery_Request_Service;
use CodeSoup\BoxNow\Services\API\Parcel_Service;

/**
 * The API service provider.
 */
class APIServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		// Authentication service is singleton (shared across all API calls)
		$this->singleton(
			'auth_service',
			function ( $container ) {
				return new Authentication_Service(
					$container->get( 'settings' ),
					$container->get( 'error_handler' )
				);
			}
		);

		// Delivery and Parcel services depend on Settings and Authentication
		$this->singleton(
			'delivery_service',
			function ( $container ) {
				return new Delivery_Request_Service(
					$container->get( 'settings' ),
					$container->get( 'auth_service' )
				);
			}
		);

		$this->singleton(
			'parcel_service',
			function ( $container ) {
				return new Parcel_Service(
					$container->get( 'settings' ),
					$container->get( 'auth_service' )
				);
			}
		);
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();
		// API services don't need hooks - they're used by other services
	}
}
