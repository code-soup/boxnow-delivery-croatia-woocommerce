<?php
/**
 * Frontend Init Class.
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Frontend;

use CodeSoup\BoxNow\Services\Settings_Service;
use function CodeSoup\BoxNow\plugin;

/** If this file is called directly, abort. */
defined( 'ABSPATH' ) || die;

/**
 * The public-facing functionality of the plugin.
 */
class Init {

	/**
	 * Settings service.
	 *
	 * @var Settings_Service
	 */
	private Settings_Service $settings;

	/**
	 * Init constructor.
	 *
	 * @param Settings_Service $settings Settings service.
	 */
	public function __construct( Settings_Service $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Initialize and register hooks.
	 */
	public function init(): void {

		// Use add_action directly since hooker->run() has already been called
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

	}

	/**
	 * Enqueue the frontend styles.
	 */
	public function wp_enqueue_scripts(): void {


		// Only enqueue on checkout page.
		if ( ! is_checkout() ) {
			return;
		}


		$assets_handler = plugin()->get( 'assets' );
		$plugin_version = plugin()->get_config( 'PLUGIN_VERSION' );

		// Build dependencies list, only including assets that exist.
		$dependencies = array();

		// Check and enqueue the webpack runtime script.
		if ( $assets_handler->asset_exists( 'runtime.js' ) ) {
			wp_enqueue_script(
				'csbxwoo-frontend-runtime',
				$assets_handler->get_asset_url( 'runtime.js' ),
				array(),
				$plugin_version,
				true
			);
			$dependencies[] = 'csbxwoo-frontend-runtime';
		}

		// Check and enqueue the vendor libs script (if exists).
		if ( $assets_handler->asset_exists( 'vendor-libs.js' ) ) {
			wp_enqueue_script(
				'csbxwoo-frontend-vendor',
				$assets_handler->get_asset_url( 'vendor-libs.js' ),
				$dependencies,
				$plugin_version,
				true
			);
			$dependencies[] = 'csbxwoo-frontend-vendor';
		}

		// Checkout stylesheet.
		if ( $assets_handler->asset_exists( 'checkout.css' ) ) {
			wp_enqueue_style(
				'csbxwoo-checkout',
				$assets_handler->get_asset_url( 'checkout.css' ),
				array(),
				$plugin_version
			);
		}

		// Prepare settings for JavaScript.
		$settings = $this->get_checkout_settings();

		// Classic checkout script.
		if ( $assets_handler->asset_exists( 'checkout.js' ) ) {
			$script_url = $assets_handler->get_asset_url( 'checkout.js' );

			wp_enqueue_script(
				'csbxwoo-checkout',
				$script_url,
				$dependencies,
				$plugin_version,
				true
			);

			wp_localize_script(
				'csbxwoo-checkout',
				'boxNowDeliverySettings',
				$settings
			);

			// Add inline debug script
			wp_add_inline_script(
				'csbxwoo-checkout',
				'console.log("[BoxNow] Script file loaded from: ' . esc_js( $script_url ) . '");',
				'before'
			);
		} else {
		}

		// Checkout blocks script.
		if ( $assets_handler->asset_exists( 'checkout-blocks.js' ) ) {
			wp_enqueue_script(
				'csbxwoo-checkout-blocks',
				$assets_handler->get_asset_url( 'checkout-blocks.js' ),
				$dependencies,
				$plugin_version,
				true
			);

			wp_localize_script(
				'csbxwoo-checkout-blocks',
				'boxNowDeliverySettings',
				$settings
			);
		}
	}

	/**
	 * Get checkout settings for JavaScript.
	 *
	 * @return array
	 */
	private function get_checkout_settings(): array {
		$widget_config = $this->settings->get_widget_config();

		return array(
			'partnerId'                => esc_attr( $this->settings->get( 'partner_id', '' ) ),
			'displayMode'              => esc_attr( $widget_config['display_mode'] ),
			'buttonColor'              => esc_attr( $widget_config['button_color'] ),
			'buttonText'               => esc_attr( $widget_config['button_text'] ),
			'buttonPosition'           => esc_attr( $widget_config['button_position'] ),
			'lockerNotSelectedMessage' => esc_js( $this->settings->get( 'locker_not_selected_message', __( 'Please select a locker first!', 'codesoup-woo-boxnow' ) ) ),
			'gps_option'               => $widget_config['gps_option'],
			'ajaxUrl'                  => admin_url( 'admin-ajax.php' ),
			'nonce'                    => wp_create_nonce( 'codesoup_boxnow_nonce' ),
			'i18n'                     => array(
				'selectedLocker' => __( 'Selected Locker', 'codesoup-woo-boxnow' ),
				'changeButton'   => __( 'Change', 'codesoup-woo-boxnow' ),
			),
		);
	}
}
