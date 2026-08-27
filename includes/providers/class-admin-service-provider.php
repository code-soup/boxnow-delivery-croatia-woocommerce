<?php
/**
 * Admin Service Provider.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Admin\Init as AdminInit;

/**
 * The admin service provider.
 */
class AdminServiceProvider extends AbstractServiceProvider {

	/**
	 * Register the service provider.
	 */
	public function register(): void {
		$this->container->singleton( 'admin', \CodeSoup\BoxNow\Admin\Init::class );
	}

	/**
	 * Boot the service provider.
	 */
	public function boot(): void {
		parent::boot();

		if ( is_admin() && ! wp_doing_ajax() ) {
			// Register hook to initialize after WordPress is loaded
			add_action(
				'init',
				function () {
					$this->container->get( 'admin' )->init();
				}
			);
		}
	}
}
