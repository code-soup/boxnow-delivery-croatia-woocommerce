<?php
/**
 * I18n class.
 *
 * @package CodeSoup\BoxNow
 */

declare(strict_types=1);

namespace CodeSoup\BoxNow\Core;

use function CodeSoup\BoxNow\plugin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 */
class I18n {

	/**
	 * I18n constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks for internationalization
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load the plugin text domain.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'codesoup-woo-boxnow',
			false,
			plugin()->get_config( 'PLUGIN_BASENAME' ) . '/languages/'
		);
	}
}
