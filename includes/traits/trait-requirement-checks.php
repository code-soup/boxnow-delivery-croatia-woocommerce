<?php
/**
 * Trait for handling plugin requirement checks.
 *
 * @package CodeSoup\BoxNow\Traits
 */

declare(strict_types=1);

namespace CodeSoup\BoxNow\Traits;

/**
 * Trait RequirementChecksTrait
 *
 * Provides reusable methods to check for WordPress and PHP version requirements.
 */
trait RequirementChecksTrait {

	/**
	 * Run all requirement checks.
	 *
	 * @param array<string, mixed> $config The plugin configuration array.
	 * @throws \Exception If requirements not met.
	 */
	public static function run_requirement_checks( array $config ): void {
		if ( ! empty( $config['MIN_WC_VERSION'] ) ) {
			self::assert_woocommerce_version( (string) $config['MIN_WC_VERSION'] );
		}

		if ( ! self::is_wp_version_ok( $config['MIN_WP_VERSION'] ) ) {
			throw new \Exception(
				sprintf(
					'Minimum WordPress version required: %s. You are running version: %s.',
					$config['MIN_WP_VERSION'],
					get_bloginfo( 'version' )
				)
			);
		}

		if ( ! self::is_php_version_ok( $config['MIN_PHP_VERSION'] ) ) {
			throw new \Exception(
				sprintf(
					'Minimum PHP version required: %s. You are running version: %s.',
					$config['MIN_PHP_VERSION'],
					phpversion()
				)
			);
		}
	}

	/**
	 * Check if the WordPress version is sufficient.
	 *
	 * @param string $min_wp_version The minimum required WordPress version.
	 * @return bool
	 */
	private static function is_wp_version_ok( string $min_wp_version ): bool {
		return version_compare( get_bloginfo( 'version' ), $min_wp_version, '>=' );
	}

	/**
	 * Check if the PHP version is sufficient.
	 *
	 * @param string $min_php_version The minimum required PHP version.
	 * @return bool
	 */
	private static function is_php_version_ok( string $min_php_version ): bool {
		return version_compare( phpversion(), $min_php_version, '>=' );
	}

	/**
	 * Ensure WooCommerce is active and meets the minimum version.
	 *
	 * @param string $min_wc_version Minimum WooCommerce version.
	 * @throws \Exception If WooCommerce is missing or too old.
	 */
	private static function assert_woocommerce_version( string $min_wc_version ): void {
		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'WC_VERSION' ) ) {
			throw new \Exception(
				sprintf(
					'WooCommerce %s or higher is required. Please install and activate WooCommerce.',
					$min_wc_version
				)
			);
		}

		if ( ! version_compare( WC_VERSION, $min_wc_version, '>=' ) ) {
			throw new \Exception(
				sprintf(
					'Minimum WooCommerce version required: %s. You are running version: %s.',
					$min_wc_version,
					WC_VERSION
				)
			);
		}
	}
}
