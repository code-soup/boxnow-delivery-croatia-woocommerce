<?php
/**
 * Delivery Request Service
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\API;

use CodeSoup\BoxNow\Constants\Meta_Keys;
use CodeSoup\BoxNow\Services\Settings_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Handles delivery request API calls.
 */
class Delivery_Request_Service {

	use API_Client_Trait;

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
	 * Create delivery request.
	 *
	 * @param array $data Delivery request data.
	 * @return array|null
	 */
	public function create_delivery_request( $data ) {
		$access_token = $this->get_access_token();
		if ( ! $access_token ) {
			error_log( '=== BoxNow API: No access token ===' );
			return null;
		}

		$endpoint = $this->get_endpoint( '/api/v1/delivery-requests' );
		$json_body = wp_json_encode( $data );

		error_log( '=== BoxNow API Endpoint: ' . $endpoint . ' ===' );
		error_log( '=== BoxNow API Request Payload: ' . $json_body . ' ===' );

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => $json_body,
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( '=== BoxNow API Error: ' . $response->get_error_message() . ' ===' );
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		error_log( '=== BoxNow API Response Code: ' . $response_code . ' ===' );
		error_log( '=== BoxNow API Response Body: ' . $response_body . ' ===' );

		$data = json_decode( $response_body, true );

		if ( ! is_array( $data ) ) {
			error_log( '=== BoxNow API: Response is not valid JSON array ===' );
			return null;
		}

		// Return the data even for error responses (400, etc.)
		// The caller will check for 'code' and 'status' fields
		return $data;
	}

	/**
	 * Prepare delivery request data from order.
	 *
	 * @param \WC_Order $order             Order object.
	 * @param int       $num_vouchers      Number of vouchers.
	 * @param int|null  $compartment_size  Single compartment size (1=small, 2=medium, 3=large).
	 * @return array
	 */
	public function prepare_delivery_data( $order, $num_vouchers = 1, $compartment_size = null ) {
		$payment_method         = $order->get_payment_method();
		$is_cod                 = 'cod' === $payment_method;

		// Get API config which includes warehouse_id
		$api_config = $this->settings->get_api_config();

		// Fetch voucher options
		$voucher_option = get_option( \CodeSoup\BoxNow\Constants\Option_Keys::VOUCHER_OPTION, 'button' );
		$voucher_email  = get_option( \CodeSoup\BoxNow\Constants\Option_Keys::VOUCHER_EMAIL, '' );
		$mobile_number  = get_option( \CodeSoup\BoxNow\Constants\Option_Keys::MOBILE_NUMBER, '' );
		$allow_returns  = get_option( \CodeSoup\BoxNow\Constants\Option_Keys::ALLOW_RETURNS, 'no' );
		$warehouse_id   = $api_config['warehouse_id'];

		$send_voucher_via_email = 'email' === $voucher_option;

		$items = array();
		for ( $i = 0; $i < $num_vouchers; $i++ ) {
			$item_data = array(
				'value'  => number_format( (float) $order->get_subtotal(), 2, '.', '' ),
				'weight' => $this->calculate_order_weight( $order ),
			);

			// Use manually provided compartment size if available
			if ( null !== $compartment_size ) {
				$item_data['compartmentSize'] = (int) $compartment_size;
			}

			$items[] = $item_data;
		}

		$data = array(
			'notifyOnAccepted'    => $send_voucher_via_email ? $voucher_email : '',
			'orderNumber'         => (string) $order->get_id(),
			'invoiceValue'        => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'paymentMode'         => $is_cod ? 'cod' : 'prepaid',
			'amountToBeCollected' => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'allowReturn'         => 'yes' === $allow_returns,
			'origin'              => array(
				'contactNumber' => $mobile_number,
				'contactEmail'  => $voucher_email,
				'locationId'    => $warehouse_id,
			),
			'destination'         => array(
				'contactName'   => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
				'contactNumber' => $this->normalize_phone_number( $order->get_billing_phone() ),
				'contactEmail'  => $order->get_billing_email(),
				'locationId'    => $order->get_meta( Meta_Keys::LOCKER_ID, true ),
			),
			'items'               => $items,
		);

		return $data;
	}

	/**
	 * Normalize phone number to international format.
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function normalize_phone_number( $phone ) {
		$tel = trim( $phone );

		// Already has +
		if ( substr( $tel, 0, 1 ) === '+' ) {
			return $tel;
		}

		// Replace 00 with +
		if ( substr( $tel, 0, 2 ) === '00' ) {
			return '+' . substr( $tel, 2 );
		}

		// Remove non-digits
		$digits_only = preg_replace( '/[^\d]/', '', $tel );

		// Add Croatia prefix for certain patterns
		if ( in_array( substr( $tel, 0, 2 ), array( '22', '23', '24', '25', '26', '96', '97', '98', '99' ), true ) && strlen( $digits_only ) < 9 ) {
			return '+357' . $digits_only;
		}

		// Default Croatia prefix
		return '+385' . $digits_only;
	}

	/**
	 * Calculate total weight of order items.
	 *
	 * @param \WC_Order $order Order object.
	 * @return float
	 */
	private function calculate_order_weight( $order ) {
		$total_weight = 0;

		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( $product && $product->get_weight() ) {
				$total_weight += (float) $product->get_weight() * $item->get_quantity();
			}
		}

		return $total_weight > 0 ? $total_weight : 1;
	}

	/**
	 * Determine compartment size based on product dimensions.
	 *
	 * @param float $length Length.
	 * @param float $width  Width.
	 * @param float $height Height.
	 * @return string
	 * @throws \Exception If dimensions don't fit.
	 */
	public function determine_compartment_size( $length, $width, $height ) {
		$dimensions = array( $length, $width, $height );
		rsort( $dimensions );

		list( $l, $w, $h ) = $dimensions;

		if ( $l <= 36 && $w <= 40 && $h <= 8 ) {
			return 'S';
		} elseif ( $l <= 36 && $w <= 40 && $h <= 20 ) {
			return 'M';
		} elseif ( $l <= 36 && $w <= 40 && $h <= 43 ) {
			return 'L';
		}

		throw new \Exception( 'Invalid product dimensions.' );
	}
}
