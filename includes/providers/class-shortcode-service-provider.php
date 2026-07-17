<?php
/**
 * Shortcode Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Services\Shortcodes\Shortcode_Handler;

/**
 * The shortcode service provider.
 */
class ShortcodeServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton( 'shortcode_handler', Shortcode_Handler::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		// Initialize shortcode handler
		$this->container->get( 'shortcode_handler' )->init();
	}
}
