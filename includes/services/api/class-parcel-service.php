<?php
/**
 * Parcel Service
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\API;

use CodeSoup\BoxNow\Exceptions\API_Exception;
use CodeSoup\BoxNow\Services\Settings_Service;
use CodeSoup\BoxNow\Traits\Logging_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Handles parcel management API calls.
 */
class Parcel_Service {

	use API_Client_Trait;
	use Logging_Trait;

	/**
	 * Constructor.
	 *
	 * @param Settings_Service       $settings     Settings service.
	 * @param Authentication_Service $auth_service Authentication service.
	 */
	public function __construct( Settings_Service $settings, Authentication_Service $auth_service ) {
		$this->settings     = $settings;
		$this->auth_service = $auth_service;
	}

	/**
	 * Cancel parcel.
	 *
	 * @param string $parcel_id Parcel ID.
	 * @return bool
	 */
	public function cancel_parcel( $parcel_id ) {
		try {
			$access_token = $this->get_access_token();
			if ( ! $access_token ) {
				throw new API_Exception( 'No access token available' );
			}

			$endpoint = $this->get_endpoint( '/api/v1/parcels/' . $parcel_id . ':cancel' );

			$response = wp_remote_post(
				$endpoint,
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => '{}',
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw API_Exception::from_wp_error( $response );
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			// Accept both 200 and 204
			if ( in_array( $response_code, array( 200, 204 ), true ) ) {
				$this->log_info( "Parcel {$parcel_id} cancelled successfully" );
				return true;
			}

			throw API_Exception::from_http_response( $response );

		} catch ( API_Exception $e ) {
			$this->log_exception( $e, "Failed to cancel parcel {$parcel_id}" );
			return false;
		}
	}

	/**
	 * Get parcel label PDF.
	 *
	 * @param string $parcel_id Parcel ID.
	 * @return string|null PDF content or null on failure.
	 */
	public function get_parcel_label( $parcel_id ) {
		$access_token = $this->get_access_token();
		if ( ! $access_token ) {
			return null;
		}

		$endpoint = $this->get_endpoint( '/api/v1/parcels/' . $parcel_id . '/label.pdf' );

		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Accept'        => 'application/pdf',
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Output parcel label PDF.
	 *
	 * @param string $parcel_id Parcel ID.
	 */
	public function output_parcel_label( $parcel_id ) {
		$pdf_content = $this->get_parcel_label( $parcel_id );

		if ( null === $pdf_content ) {
			wp_die( esc_html__( 'Error: Unable to retrieve parcel label.', 'codesoup-woo-boxnow' ) );
		}

		if ( headers_sent() ) {
			wp_die( esc_html__( 'Error: Headers already sent.', 'codesoup-woo-boxnow' ) );
		}

		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="label-' . sanitize_file_name( $parcel_id ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $pdf_content ) );

		echo $pdf_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
