<?php
/**
 * Email Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Email\Email_Handler;

/**
 * The email service provider.
 */
class EmailServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton( 'email_handler', Email_Handler::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		// Initialize email handler
		$this->container->get( 'email_handler' )->init();
	}
}
