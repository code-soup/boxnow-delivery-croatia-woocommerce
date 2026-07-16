<?php
/**
 * Settings Page
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Integrations\WooCommerce;

use CodeSoup\BoxNow\Core\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin settings page integration.
 */
class Settings_Page {

	/**
	 * Hooker instance.
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

	/**
	 * Constructor.
	 *
	 * @param Hooker $hooker Hooker instance.
	 */
	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		$this->hooker->add_filter( 'woocommerce_get_settings_pages', $this, 'add_settings_page' );
	}

	/**
	 * Add settings page to WooCommerce.
	 *
	 * The WC_Settings_Page parent class automatically registers itself via woocommerce_settings_tabs_array.
	 * We just need to instantiate our class ONCE and let the parent handle the rest.
	 *
	 * @param array $settings Settings pages.
	 * @return array
	 */
	public function add_settings_page( $settings ) {
		if ( ! class_exists( '\WC_Settings_Page' ) ) {
			return $settings;
		}

		// Check if already exists - prevent duplicates
		foreach ( $settings as $page ) {
			if ( $page instanceof \CodeSoup\BoxNow\Integrations\WooCommerce\WC_Settings_BoxNow ) {
				return $settings;
			}
		}

		// Create ONCE - parent constructor handles self-registration
		$settings[] = new \CodeSoup\BoxNow\Integrations\WooCommerce\WC_Settings_BoxNow();
		return $settings;
	}
}

