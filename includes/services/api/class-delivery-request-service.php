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
			return null;
		}

		$endpoint  = $this->get_endpoint( '/api/v1/delivery-requests' );
		$json_body = wp_json_encode( $data );

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
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );

		$data = json_decode( $response_body, true );

		if ( ! is_array( $data ) ) {
			return null;
		}

		// Return the data even for error responses (400, etc.)
		// The caller will check for 'code' and 'status' fields
		return $data;
	}

	/**
	 * Prepare delivery request data from order.
	 *
	 * @param \WC_Order $order                       Order object.
	 * @param int       $num_vouchers                Number of vouchers.
	 * @param int|null  $compartment_size            Single compartment size (1=small, 2=medium, 3=large).
	 * @param bool      $show_recipient_information  Whether to print recipient phone and email on the label. The name is always included.
	 * @return array
	 */
	public function prepare_delivery_data( $order, $num_vouchers = 1, $compartment_size = null, $show_recipient_information = true ) {
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
			'notifyOnAccepted'         => $send_voucher_via_email ? $voucher_email : '',
			'orderNumber'              => (string) $order->get_id(),
			'invoiceValue'             => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'paymentMode'              => $is_cod ? 'cod' : 'prepaid',
			'amountToBeCollected'      => $is_cod ? number_format( $order->get_total(), 2, '.', '' ) : '0',
			'allowReturn'              => 'yes' === $allow_returns,
			// Without this the recipient phone and email are left off the printed label.
			'showRecipientInformation' => (bool) $show_recipient_information,
			'origin'                   => array(
				'contactNumber' => $mobile_number,
				'contactEmail'  => $voucher_email,
				'locationId'    => $warehouse_id,
			),
			'destination'              => array(
				'contactName'   => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
				'contactNumber' => $this->normalize_phone_number( $this->get_customer_phone( $order ) ),
				'contactEmail'  => $order->get_billing_email(),
				'locationId'    => $order->get_meta( Meta_Keys::LOCKER_ID, true ),
				'easyAccess'    => true,
			),
			'items'                    => $items,
		);

		return $data;
	}

	/**
	 * Get the customer phone number from the order.
	 *
	 * Billing phone is the primary source because default and older WooCommerce
	 * checkouts store the phone on the billing address. Block based checkouts may
	 * only collect it on the shipping form, hence the fallback.
	 *
	 * @param \WC_Order $order Order object.
	 * @return string
	 */
	private function get_customer_phone( $order ) {
		$phone = (string) $order->get_billing_phone();

		if ( '' === trim( $phone ) && method_exists( $order, 'get_shipping_phone' ) ) {
			$phone = (string) $order->get_shipping_phone();
		}

		return $phone;
	}

	/**
	 * Normalize phone number to full international format.
	 *
	 * Numbers that already carry a country code (+XXX or 00XXX) keep it. Numbers
	 * without one are matched against Slovenian and Cypriot mobile prefixes and
	 * otherwise treated as Croatian. The national trunk zero is always dropped
	 * before the country code is prepended, as required by the BOX NOW API.
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function normalize_phone_number( $phone ) {
		$tel = trim( (string) $phone );

		if ( '' === $tel ) {
			return '';
		}

		// Already international, strip spaces, dashes and brackets.
		if ( substr( $tel, 0, 1 ) === '+' ) {
			return '+' . preg_replace( '/\D/', '', substr( $tel, 1 ) );
		}

		$digits = preg_replace( '/\D/', '', $tel );

		// International prefix written as 00, e.g. 00385911234567.
		if ( substr( $digits, 0, 2 ) === '00' ) {
			return '+' . substr( $digits, 2 );
		}

		// Slovenian mobile, 9 digits with trunk zero or 8 digits without it.
		$slovenian_with_zero    = array( '030', '031', '040', '041', '051', '064', '065', '068', '069', '070', '071' );
		$slovenian_without_zero = array( '30', '31', '40', '41', '51', '64', '65', '68', '69', '70', '71' );

		$is_slovenian = ( 9 === strlen( $digits ) && in_array( substr( $digits, 0, 3 ), $slovenian_with_zero, true ) )
			|| ( 8 === strlen( $digits ) && in_array( substr( $digits, 0, 2 ), $slovenian_without_zero, true ) );

		if ( $is_slovenian ) {
			return '+386' . ( substr( $digits, 0, 1 ) === '0' ? substr( $digits, 1 ) : $digits );
		}

		// Cypriot mobile, always 8 digits with no trunk zero.
		if ( 8 === strlen( $digits ) && in_array( substr( $digits, 0, 2 ), array( '95', '96', '97', '99' ), true ) ) {
			return '+357' . $digits;
		}

		// Everything else is treated as a Croatian mobile.
		if ( substr( $digits, 0, 1 ) === '0' ) {
			$digits = substr( $digits, 1 );
		}

		return '+385' . $digits;
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
