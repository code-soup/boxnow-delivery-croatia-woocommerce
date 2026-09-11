<?php
/**
 * Error Handler Service
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services;

use CodeSoup\BoxNow\Traits\Logging_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized error handling service.
 */
class Error_Handler {

	use Logging_Trait;

	/**
	 * Handle an exception and return WP_Error.
	 *
	 * @param \Exception $exception Exception to handle.
	 * @param string     $context   Context description.
	 * @return \WP_Error
	 */
	public function handle_exception( \Exception $exception, string $context = '' ): \WP_Error {
		$prefix = $context ? "[$context]" : '';
		$this->log_exception( $exception, $prefix );

		return new \WP_Error(
			'boxnow_error',
			$this->get_user_friendly_message( $exception ),
			array(
				'exception' => get_class( $exception ),
				'context'   => $context,
			)
		);
	}

	/**
	 * Handle API error and return WP_Error.
	 *
	 * @param \Exception $exception API exception.
	 * @param string     $operation Operation description.
	 * @return \WP_Error
	 */
	public function handle_api_error( \Exception $exception, string $operation = '' ): \WP_Error {
		$context = $operation ? "API: {$operation}" : 'API Error';
		$this->log_exception( $exception, $context );

		return new \WP_Error(
			'boxnow_api_error',
			sprintf(
				/* translators: %s: operation description */
				__( 'BoxNow API error: %s', 'codesoup-woo-boxnow' ),
				$this->get_user_friendly_message( $exception )
			),
			array(
				'exception' => get_class( $exception ),
				'operation' => $operation,
			)
		);
	}

	/**
	 * Check if value is WP_Error and log it.
	 *
	 * @param mixed  $value   Value to check.
	 * @param string $context Context description.
	 * @return bool True if it's an error.
	 */
	public function is_error( $value, string $context = '' ): bool {
		if ( is_wp_error( $value ) ) {
			$this->log_error(
				$value->get_error_message(),
				array(
					'context' => $context,
					'code'    => $value->get_error_code(),
					'data'    => $value->get_error_data(),
				)
			);
			return true;
		}
		return false;
	}

	/**
	 * Get user-friendly error message.
	 *
	 * @param \Exception $exception Exception.
	 * @return string
	 */
	private function get_user_friendly_message( \Exception $exception ): string {
		// In production, return generic message unless in debug mode
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return __( 'An error occurred. Please try again or contact support.', 'codesoup-woo-boxnow' );
		}

		// In debug mode, return the actual exception message
		return $exception->getMessage();
	}

	/**
	 * Create admin notice from WP_Error.
	 *
	 * @param \WP_Error $error Error object.
	 * @param string    $type  Notice type (error, warning, info, success).
	 */
	public function show_admin_notice( \WP_Error $error, string $type = 'error' ): void {
		add_action(
			'admin_notices',
			function () use ( $error, $type ) {
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $error->get_error_message() )
				);
			}
		);
	}

	/**
	 * Add user-facing error message (for frontend).
	 *
	 * @param string $message Error message.
	 * @param string $type    Message type (error, notice, success).
	 */
	public function add_wc_notice( string $message, string $type = 'error' ): void {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $message, $type );
		}
	}

	/**
	 * Validate API response and throw exception if invalid.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @param int            $expected_code Expected HTTP code.
	 * @throws \CodeSoup\BoxNow\Exceptions\API_Exception If response is invalid.
	 */
	public function validate_http_response( $response, int $expected_code = 200 ): void {
		if ( is_wp_error( $response ) ) {
			throw \CodeSoup\BoxNow\Exceptions\API_Exception::from_wp_error( $response );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== $expected_code ) {
			throw \CodeSoup\BoxNow\Exceptions\API_Exception::from_http_response( $response );
		}
	}
}
