<?php
/**
 * Settings Service
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services;

use CodeSoup\BoxNow\Constants\Option_Keys;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized settings management with caching.
 *
 * Eliminates repeated get_option() calls by maintaining an in-memory cache.
 */
class Settings_Service {

	/**
	 * Settings cache.
	 *
	 * @var array<string, mixed>
	 */
	private array $cache = array();

	/**
	 * Option key prefix.
	 */
	const PREFIX = 'codesoup_boxnow_';

	/**
	 * Get a setting value.
	 *
	 * @param string $key     Option key (without prefix).
	 * @param mixed  $default Default value if not found.
	 * @return mixed
	 */
	public function get( string $key, $default = '' ) {
		$option_key = $this->get_option_key( $key );

		if ( ! array_key_exists( $option_key, $this->cache ) ) {
			$this->cache[ $option_key ] = get_option( $option_key, $default );
		}

		return $this->cache[ $option_key ];
	}

	/**
	 * Get multiple settings at once.
	 *
	 * @param array<string> $keys Array of option keys.
	 * @return array<string, mixed>
	 */
	public function get_multiple( array $keys ): array {
		$result = array();

		foreach ( $keys as $key ) {
			$result[ $key ] = $this->get( $key );
		}

		return $result;
	}

	/**
	 * Update a setting value.
	 *
	 * @param string $key   Option key (without prefix).
	 * @param mixed  $value The value to set.
	 * @return bool
	 */
	public function set( string $key, $value ): bool {
		$option_key = $this->get_option_key( $key );

		$result = update_option( $option_key, $value );

		if ( $result ) {
			$this->cache[ $option_key ] = $value;
		}

		return $result;
	}

	/**
	 * Delete a setting.
	 *
	 * @param string $key Option key (without prefix).
	 * @return bool
	 */
	public function delete( string $key ): bool {
		$option_key = $this->get_option_key( $key );

		$result = delete_option( $option_key );

		if ( $result ) {
			unset( $this->cache[ $option_key ] );
		}

		return $result;
	}

	/**
	 * Get API configuration.
	 *
	 * @return array{api_url: string, client_id: string, client_secret: string, partner_id: string}
	 */
	public function get_api_config(): array {
		return array(
			'api_url'       => get_option( Option_Keys::API_URL, '' ),
			'client_id'     => get_option( Option_Keys::CLIENT_ID, '' ),
			'client_secret' => get_option( Option_Keys::CLIENT_SECRET, '' ),
			'partner_id'    => get_option( Option_Keys::PARTNER_ID, '' ),
			'warehouse_id'  => get_option( Option_Keys::WAREHOUSE_ID, '' ),
		);
	}

	/**
	 * Get widget configuration.
	 *
	 * @return array
	 */
	public function get_widget_config(): array {
		return array(
			'display_mode'    => $this->get( 'display_mode', 'popup' ),
			'button_color'    => $this->get( 'button_color', '#000000' ),
			'button_text'     => $this->get( 'button_text', __( 'Select Locker', 'codesoup-woo-boxnow' ) ),
			'button_position' => $this->get( 'button_position', 'after_shipping' ),
			'gps_option'      => $this->get( 'enable_geolocation', 'yes' ) === 'yes' ? 'on' : 'off',
		);
	}

	/**
	 * Clear all cached settings.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->cache = array();
	}

	/**
	 * Get full option key with prefix.
	 *
	 * @param string $key Key without prefix.
	 * @return string
	 */
	private function get_option_key( string $key ): string {
		// If key already has prefix, return as-is
		if ( str_starts_with( $key, self::PREFIX ) ) {
			return $key;
		}

		return self::PREFIX . $key;
	}
}
