<?php
/**
 * Order AJAX Handler
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services\Ajax;

use CodeSoup\BoxNow\Constants\Meta_Keys;
use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Helpers\Order_Helper;
use CodeSoup\BoxNow\Services\API\Delivery_Request_Service;
use CodeSoup\BoxNow\Services\API\Parcel_Service;
use CodeSoup\BoxNow\Traits\Logging_Trait;

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX requests for order parcel management.
 */
class Order_AJAX_Handler {

	use Logging_Trait;

	/**
	 * Hooker instance.
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

	/**
	 * Delivery request service.
	 *
	 * @var Delivery_Request_Service
	 */
	private Delivery_Request_Service $delivery_service;

	/**
	 * Parcel service.
	 *
	 * @var Parcel_Service
	 */
	private Parcel_Service $parcel_service;

	/**
	 * Nonce action name.
	 */
	private const NONCE_ACTION = 'codesoup_boxnow_nonce';

	/**
	 * Constructor.
	 *
	 * @param Hooker                   $hooker           Hooker instance.
	 * @param Delivery_Request_Service $delivery_service Delivery request service.
	 * @param Parcel_Service           $parcel_service   Parcel service.
	 */
	public function __construct(
		Hooker $hooker,
		Delivery_Request_Service $delivery_service,
		Parcel_Service $parcel_service
	) {
		$this->hooker           = $hooker;
		$this->delivery_service = $delivery_service;
		$this->parcel_service   = $parcel_service;
	}

	/**
	 * Initialize AJAX hooks.
	 */
	public function init(): void {
		$this->hooker->add_action( 'wp_ajax_create_box_now_parcels', $this, 'ajax_create_box_now_parcels' );
		$this->hooker->add_action( 'wp_ajax_cancel_parcel', $this, 'ajax_cancel_parcel' );
		$this->hooker->add_action( 'wp_ajax_print_box_now_parcel', $this, 'ajax_print_box_now_parcel' );
	}

	/**
	 * Handle AJAX request to create parcels.
	 */
	public function ajax_create_box_now_parcels(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( __( 'Security check failed.', 'codesoup-woo-boxnow' ), 403 );
		}

		// Verify permissions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'codesoup-woo-boxnow' ), 403 );
		}

		$order_id         = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$parcel_quantity  = isset( $_POST['parcel_quantity'] ) ? absint( $_POST['parcel_quantity'] ) : 1;
		$compartment_size = isset( $_POST['compartment_size'] ) ? absint( $_POST['compartment_size'] ) : null;

		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'codesoup-woo-boxnow' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			wp_send_json_error( __( 'Invalid BoxNow order.', 'codesoup-woo-boxnow' ), 400 );
		}

		try {
			$data = $this->delivery_service->prepare_delivery_data( $order, $parcel_quantity, $compartment_size );

			$response = $this->delivery_service->create_delivery_request( $data );

			if ( ! $response || ! isset( $response['parcels'] ) ) {
				throw new \Exception( __( 'Failed to create delivery request.', 'codesoup-woo-boxnow' ) );
			}

			$parcel_ids = array_column( $response['parcels'], 'id' );

			// Store parcel IDs in order meta.
			$order->update_meta_data( Meta_Keys::PARCEL_IDS, $parcel_ids );
			$order->save();

			$this->log_info(
				sprintf(
					'Created %d parcels for order #%d',
					count( $parcel_ids ),
					$order_id
				)
			);

			wp_send_json_success(
				array(
					'new_parcel_ids' => $parcel_ids,
					'message'        => sprintf(
						// translators: %d: number of parcels created.
						__( '%d parcel(s) created successfully.', 'codesoup-woo-boxnow' ),
						count( $parcel_ids )
					),
				)
			);

		} catch ( \Exception $e ) {
			$this->log_error( 'Failed to create parcels: ' . $e->getMessage() );
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}



	/**
	 * Handle AJAX request to cancel parcel.
	 */
	public function ajax_cancel_parcel(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( __( 'Security check failed.', 'codesoup-woo-boxnow' ), 403 );
		}

		// Verify permissions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'codesoup-woo-boxnow' ), 403 );
		}

		$order_id  = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$parcel_id = isset( $_POST['parcel_id'] ) ? sanitize_text_field( wp_unslash( $_POST['parcel_id'] ) ) : '';

		if ( ! $order_id || ! $parcel_id ) {
			wp_send_json_error( __( 'Invalid order ID or parcel ID.', 'codesoup-woo-boxnow' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			wp_send_json_error( __( 'Invalid BoxNow order.', 'codesoup-woo-boxnow' ), 400 );
		}

		try {
			$success = $this->parcel_service->cancel_parcel( $parcel_id );

			if ( ! $success ) {
				throw new \Exception( __( 'Failed to cancel parcel.', 'codesoup-woo-boxnow' ) );
			}

			// Remove parcel ID from order meta.
			$parcel_ids = $order->get_meta( Meta_Keys::PARCEL_IDS, true );
			if ( ! is_array( $parcel_ids ) ) {
				$parcel_ids = array();
			}

			$index = array_search( $parcel_id, $parcel_ids, true );
			if ( false !== $index ) {
				unset( $parcel_ids[ $index ] );
				$parcel_ids = array_values( $parcel_ids ); // Reindex array.
			}

			$order->update_meta_data( Meta_Keys::PARCEL_IDS, $parcel_ids );
			$order->save();

			$this->log_info(
				sprintf(
					'Canceled parcel %s for order #%d',
					$parcel_id,
					$order_id
				)
			);

			wp_send_json_success( $parcel_id );

		} catch ( \Exception $e ) {
			$this->log_error( 'Failed to cancel parcel: ' . $e->getMessage() );
			wp_send_json_error( $e->getMessage(), 500 );
		}
	}

	/**
	 * Handle AJAX request to print parcel PDF.
	 */
	public function ajax_print_box_now_parcel(): void {
		// Verify permissions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'codesoup-woo-boxnow' ) );
		}

		$parcel_id = isset( $_GET['parcel_id'] ) ? sanitize_text_field( wp_unslash( $_GET['parcel_id'] ) ) : '';

		if ( empty( $parcel_id ) ) {
			wp_die( esc_html__( 'Parcel ID was not found.', 'codesoup-woo-boxnow' ) );
		}

		$this->parcel_service->output_parcel_label( $parcel_id );
	}

	/**
	 * Verify AJAX nonce.
	 *
	 * @return bool
	 */
	private function verify_nonce(): bool {
		$nonce = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : '';

		if ( isset( $_POST['nonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
		}

		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}
}