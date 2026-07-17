<?php
/**
 * Delivery Request Service
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\API;

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
			return null;
		}

		$endpoint = $this->get_endpoint( '/api/v1/delivery-requests' );

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $data ),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( ! in_array( $response_code, array( 200, 201 ), true ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		return $data;
	}

	/**
	 * Prepare delivery request data from order.
	 *
	 * @param \WC_Order $order             Order object.
	 * @param int       $num_parcels       Number of parcels.
	 * @param int|null  $compartment_size  Single compartment size (1=small, 2=medium, 3=large).
	 * @return array
	 */
	public function prepare_delivery_data( $order, $num_parcels = 1, $compartment_size = null ) {
		$payment_method = $order->get_payment_method();
		$is_cod         = 'cod' === $payment_method;

		$items = array();
		for ( $i = 0; $i < $num_parcels; $i++ ) {
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
			'notifyOnAccepted'    => '',
			'orderNumber'         => (string) $order->get_id(),
			'invoiceValue'        => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'paymentMode'         => $is_cod ? 'cod' : 'prepaid',
			'amountToBeCollected' => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'allowReturn'         => false,
			'origin'              => array(
				'contactNumber' => '',
				'contactEmail'  => '',
				'locationId'    => $order->get_meta( '_selected_warehouse', true ),
			),
			'destination'         => array(
				'contactName'   => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
				'contactNumber' => $this->normalize_phone_number( $order->get_billing_phone() ),
				'contactEmail'  => $order->get_billing_email(),
				'locationId'    => $order->get_meta( '_boxnow_locker_id', true ),
			),
			'parcels'             => $items,
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
