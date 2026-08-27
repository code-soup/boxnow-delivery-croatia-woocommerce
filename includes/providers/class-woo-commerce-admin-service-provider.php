<?php
/**
 * WooCommerce Admin Service Provider
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Integrations\WooCommerce\Settings_Page;

/**
 * The WooCommerce admin service provider.
 */
class WooCommerceAdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->singleton( 'wc_settings_page', Settings_Page::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();
		$this->container->get( 'wc_settings_page' )->init();
	}
}
