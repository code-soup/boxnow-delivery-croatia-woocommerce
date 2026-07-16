<?php
/**
 * API Exception
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Exceptions;

defined( 'ABSPATH' ) || exit;

/**
 * Exception thrown when API operations fail.
 */
class API_Exception extends \Exception {

	/**
	 * HTTP status code.
	 *
	 * @var int
	 */
	private int $http_code;

	/**
	 * API response data.
	 *
	 * @var array
	 */
	private array $response_data;

	/**
	 * Constructor.
	 *
	 * @param string $message       Error message.
	 * @param int    $code          Error code.
	 * @param int    $http_code     HTTP status code.
	 * @param array  $response_data API response data.
	 */
	public function __construct( string $message, int $code = 0, int $http_code = 0, array $response_data = array() ) {
		parent::__construct( $message, $code );
		$this->http_code     = $http_code;
		$this->response_data = $response_data;
	}

	/**
	 * Get HTTP status code.
	 *
	 * @return int
	 */
	public function get_http_code(): int {
		return $this->http_code;
	}

	/**
	 * Get API response data.
	 *
	 * @return array
	 */
	public function get_response_data(): array {
		return $this->response_data;
	}

	/**
	 * Create exception from WP_Error.
	 *
	 * @param \WP_Error $error WP_Error object.
	 * @return self
	 */
	public static function from_wp_error( \WP_Error $error ): self {
		return new self(
			$error->get_error_message(),
			(int) $error->get_error_code(),
			0,
			$error->get_error_data() ? (array) $error->get_error_data() : array()
		);
	}

	/**
	 * Create exception from HTTP response.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return self
	 */
	public static function from_http_response( $response ): self {
		if ( is_wp_error( $response ) ) {
			return self::from_wp_error( $response );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true ) ?? array();

		$message = $data['message'] ?? $data['error'] ?? 'Unknown API error';

		return new self( $message, 0, $code, $data );
	}
}
