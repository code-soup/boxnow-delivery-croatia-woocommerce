<?php
/**
 * Service Provider Interface.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Interfaces;

use CodeSoup\BoxNow\Core\Container;

/**
 * If this file is called directly, abort.
 */
defined( 'ABSPATH' ) || die;

/**
 * The ServiceProviderInterface interface.
 */
interface ServiceProviderInterface {

	/**
	 * Register the service provider.
	 */
	public function register(): void;

	/**
	 * Get the container.
	 *
	 * @return Container
	 */
	public function get_container(): Container;
}
