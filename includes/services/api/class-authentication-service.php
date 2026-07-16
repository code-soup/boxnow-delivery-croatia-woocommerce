<?php
/**
 * Authentication Service
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\API;

use CodeSoup\BoxNow\Constants\Transient_Keys;
use CodeSoup\BoxNow\Exceptions\API_Exception;
use CodeSoup\BoxNow\Services\Error_Handler;
use CodeSoup\BoxNow\Services\Settings_Service;
use CodeSoup\BoxNow\Traits\Logging_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Handles API authentication.
 */
class Authentication_Service {

	use Logging_Trait;

	/**
	 * Settings service.
	 *
	 * @var Settings_Service
	 */
	private Settings_Service $settings;

	/**
	 * Error handler.
	 *
	 * @var Error_Handler
	 */
	private Error_Handler $error_handler;

	/**
	 * Constructor.
	 *
	 * @param Settings_Service $settings      Settings service.
	 * @param Error_Handler    $error_handler Error handler.
	 */
	public function __construct( Settings_Service $settings, Error_Handler $error_handler ) {
		$this->settings      = $settings;
		$this->error_handler = $error_handler;
	}

	/**
	 * Get access token (cached or fresh).
	 *
	 * @return string|null
	 */
	public function get_access_token() {
		$token = get_transient( Transient_Keys::ACCESS_TOKEN );

		if ( false !== $token ) {
			return $token;
		}

		return $this->fetch_access_token();
	}

	/**
	 * Fetch fresh access token from API.
	 *
	 * @return string|null
	 */
	private function fetch_access_token() {
		try {
			$config = $this->settings->get_api_config();

			if ( empty( $config['api_url'] ) || empty( $config['client_id'] ) || empty( $config['client_secret'] ) ) {
				$this->log_error( 'Missing API credentials in settings' );
				return null;
			}

			$response = wp_remote_post(
				'https://' . $config['api_url'] . '/api/v1/auth-sessions',
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode(
						array(
							'grant_type'    => 'client_credentials',
							'client_id'     => $config['client_id'],
							'client_secret' => $config['client_secret'],
						)
					),
					'timeout' => 15,
				)
			);

			// Validate response using error handler
			$this->error_handler->validate_http_response( $response, 200 );

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) || ! isset( $body['access_token'] ) ) {
				throw new API_Exception( 'Invalid token response structure' );
			}

			$token = $body['access_token'];
			set_transient( Transient_Keys::ACCESS_TOKEN, $token, Transient_Keys::TOKEN_EXPIRATION );

			$this->log_info( 'Access token fetched successfully' );

			return $token;

		} catch ( API_Exception $e ) {
			$this->log_exception( $e, 'Failed to fetch access token' );
			return null;
		}
	}

	/**
	 * Clear cached access token.
	 */
	public function clear_token() {
		delete_transient( Transient_Keys::ACCESS_TOKEN );
	}
}
