<?php
/**
 * Logging Trait
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Provides logging functionality using WooCommerce logger.
 */
trait Logging_Trait {

	/**
	 * Log an error message.
	 *
	 * @param string $message Error message.
	 * @param array  $context Additional context.
	 */
	protected function log_error( string $message, array $context = array() ): void {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Warning message.
	 * @param array  $context Additional context.
	 */
	protected function log_warning( string $message, array $context = array() ): void {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Info message.
	 * @param array  $context Additional context.
	 */
	protected function log_info( string $message, array $context = array() ): void {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Debug message.
	 * @param array  $context Additional context.
	 */
	protected function log_debug( string $message, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$this->log( 'debug', $message, $context );
	}

	/**
	 * Log an exception.
	 *
	 * @param \Exception $exception Exception to log.
	 * @param string     $prefix    Optional prefix for the message.
	 */
	protected function log_exception( \Exception $exception, string $prefix = '' ): void {
		$message = $prefix ? "{$prefix}: " : '';
		$message .= sprintf(
			'%s in %s:%d',
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine()
		);

		$this->log_error(
			$message,
			array(
				'exception' => get_class( $exception ),
				'code'      => $exception->getCode(),
				'trace'     => $exception->getTraceAsString(),
			)
		);
	}

	/**
	 * Internal log method.
	 *
	 * @param string $level   Log level (error, warning, info, debug).
	 * @param string $message Log message.
	 * @param array  $context Additional context.
	 */
	private function log( string $level, string $message, array $context = array() ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();
		$source = 'codesoup-boxnow';

		// Add class context if available
		if ( isset( $this ) ) {
			$context['class'] = get_class( $this );
		}

		// Format context for readability
		if ( ! empty( $context ) ) {
			$message .= ' | Context: ' . wp_json_encode( $context );
		}

		$logger->log( $level, $message, array( 'source' => $source ) );
	}
}
