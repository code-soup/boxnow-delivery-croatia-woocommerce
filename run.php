<?php
/**
 * Plugin main file.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || die;

// Autoloaders already loaded in index.php before activation hooks.

use CodeSoup\BoxNow\Core\Plugin;

/**
 * Begins execution of the plugin.
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $instance = null;

	if ( is_null( $instance ) ) {
		$config = array(
			'MIN_WP_VERSION_SUPPORT_TERMS' => '6.0',
			'MIN_WP_VERSION'               => '6.0',
			'MIN_PHP_VERSION'              => '8.1',
			'MIN_MYSQL_VERSION'            => '',
			'PLUGIN_PREFIX'                => 'csbxwoo',
			'PLUGIN_NAME'                  => 'BoxNow Delivery for WooCommerce by CodeSoup',
			'PLUGIN_VERSION'               => '1.0.0',
			'PLUGIN_TEXTDOMAIN'            => 'codesoup-woo-boxnow',
			'ENVIRONMENT'                  => \wp_get_environment_type(),
		);

		// Pass the main plugin file path and config to the instance method.
		$instance = Plugin::instance( __FILE__, $config );
	}

	return $instance;
}

// Get the plugin running.
plugin()->run();
