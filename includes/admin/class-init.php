<?php
/**
 * Admin Init class.
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Admin;

use function CodeSoup\BoxNow\plugin;

/** If this file is called directly, abort. */
defined( 'ABSPATH' ) || die;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Init {

	/**
	 * Init constructor.
	 */
	public function __construct() {
		// Hooks registered later to avoid circular dependency
		error_log( print_r('__construct', true) );
	}

	/**
	 * Initialize and register hooks.
	 */
	public function init(): void {
		error_log( print_r('init', true) );
		$hooker = plugin()->get( 'hooker' );
		$hooker->add_actions(
			array(
				array( 'admin_enqueue_scripts', $this ),
			)
		);
	}

	/**
	 * Enqueue the admin styles.
	 */
	public function admin_enqueue_scripts(): void {

		error_log( '=== BOXNOW admin_enqueue_scripts CALLED ===' );

		// Only enqueue on order edit screen.
		$screen = get_current_screen();

		// Support both traditional and HPOS order screens
		$valid_screens = array( 'shop_order', 'woocommerce_page_wc-orders' );

		error_log( '=== Screen ID: ' . ( $screen ? $screen->id : 'NULL' ) . ' ===' );
		error_log( '=== Is order screen: ' . ( $screen && in_array( $screen->id, $valid_screens, true ) ? 'YES' : 'NO' ) . ' ===' );

		if ( ! $screen || ! in_array( $screen->id, $valid_screens, true ) ) {
			return;
		}

		error_log( '=== PASSED SCREEN CHECK - WILL ENQUEUE ===' );



		$assets_handler = plugin()->get( 'assets' );
		$plugin_version = plugin()->get_config( 'PLUGIN_VERSION' );

		// Build dependencies list, only including assets that exist.
		$dependencies = array();

		// Check and enqueue the webpack runtime script.
		if ( $assets_handler->asset_exists( 'runtime.js' ) ) {
			wp_enqueue_script(
				'csbxwoo-admin-runtime',
				$assets_handler->get_asset_url( 'runtime.js' ),
				array(),
				$plugin_version,
				true
			);
			$dependencies[] = 'csbxwoo-admin-runtime';
		}

		// Check and enqueue the vendor libs script (if exists).
		if ( $assets_handler->asset_exists( 'vendor-libs.js' ) ) {
			wp_enqueue_script(
				'csbxwoo-admin-vendor',
				$assets_handler->get_asset_url( 'vendor-libs.js' ),
				$dependencies,
				$plugin_version,
				true
			);
			$dependencies[] = 'csbxwoo-admin-vendor';
		}

		// Admin order script.
		if ( $assets_handler->asset_exists( 'admin-order.js' ) ) {

			error_log( '=== admin-order.js EXISTS - ENQUEUING ===' );

			wp_enqueue_script(
				'csbxwoo-admin-order',
				$assets_handler->get_asset_url( 'admin-order.js' ),
				$dependencies,
				$plugin_version,
				true
			);

			// Localize script with AJAX data.
			wp_localize_script(
				'csbxwoo-admin-order',
				'myAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'codesoup_boxnow_nonce' ),
				)
			);
		}
	}
}
