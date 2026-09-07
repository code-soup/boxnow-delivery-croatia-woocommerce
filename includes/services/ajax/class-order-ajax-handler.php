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
use CodeSoup\BoxNow\Services\Order_Service;
use CodeSoup\BoxNow\Traits\Logging_Trait;

use function CodeSoup\BoxNow\plugin;

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
	 * Order service.
	 *
	 * @var Order_Service
	 */
	private Order_Service $order_service;

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
	 * @param Order_Service            $order_service    Order service.
	 */
	public function __construct(
		Hooker $hooker,
		Delivery_Request_Service $delivery_service,
		Parcel_Service $parcel_service,
		Order_Service $order_service
	) {
		$this->hooker           = $hooker;
		$this->delivery_service = $delivery_service;
		$this->parcel_service   = $parcel_service;
		$this->order_service    = $order_service;
	}

	/**
	 * Initialize AJAX hooks.
	 */
	public function init(): void {
		$this->hooker->add_action( 'wp_ajax_create_box_now_vouchers', $this, 'ajax_create_box_now_vouchers' );
		$this->hooker->add_action( 'wp_ajax_cancel_voucher', $this, 'ajax_cancel_voucher' );
		$this->hooker->add_action( 'wp_ajax_cancel_all_vouchers', $this, 'ajax_cancel_all_vouchers' );
		$this->hooker->add_action( 'wp_ajax_print_box_now_voucher', $this, 'ajax_print_box_now_voucher' );
	}

	/**
	 * Handle AJAX request to create vouchers.
	 */
	public function ajax_create_box_now_vouchers(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( __( 'Security check failed.', 'codesoup-woo-boxnow' ), 403 );
		}

		// Verify permissions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'codesoup-woo-boxnow' ), 403 );
		}

		$order_id          = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
		$voucher_quantity  = isset( $_POST['voucher_quantity'] ) ? absint( $_POST['voucher_quantity'] ) : 1;
		$compartment_size  = isset( $_POST['compartment_size'] ) ? absint( $_POST['compartment_size'] ) : null;

		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'codesoup-woo-boxnow' ), 400 );
		}

		// Validate compartment size (1=small, 2=medium, 3=large)
		if ( null !== $compartment_size && ! in_array( $compartment_size, array( 1, 2, 3 ), true ) ) {
			wp_send_json_error( __( 'Invalid compartment size.', 'codesoup-woo-boxnow' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			wp_send_json_error( __( 'Invalid BoxNow order.', 'codesoup-woo-boxnow' ), 400 );
		}

		// Check if parcels already exist
		$existing_parcel_ids = $order->get_meta( Meta_Keys::PARCEL_IDS, true );
		error_log( '=== Before create - Existing Parcel IDs: ' . wp_json_encode( $existing_parcel_ids ) . ' ===' );

		if ( ! empty( $existing_parcel_ids ) && is_array( $existing_parcel_ids ) ) {
			$existing_count = count( $existing_parcel_ids );
		} else {
			$existing_count = 0;
		}

		// Calculate max vouchers from order items
		$max_vouchers = 0;
		foreach ( $order->get_items() as $item ) {
			$max_vouchers += $item->get_quantity();
		}

		// Calculate remaining vouchers that can be created
		$remaining_vouchers = $max_vouchers - $existing_count;

		// Validate voucher quantity
		if ( $voucher_quantity < 1 || $voucher_quantity > $remaining_vouchers ) {
			wp_send_json_error(
				sprintf(
					/* translators: %d: maximum allowed vouchers */
					__( 'Invalid voucher quantity. Maximum allowed: %d (already created: %d)', 'codesoup-woo-boxnow' ),
					$remaining_vouchers,
					$existing_count
				),
				400
			);
		}

		try {
			$data = $this->delivery_service->prepare_delivery_data( $order, $voucher_quantity, $compartment_size );

			$response = $this->delivery_service->create_delivery_request( $data );

			if ( ! $response ) {
				throw new \Exception( __( 'Failed to create delivery request.', 'codesoup-woo-boxnow' ) );
			}

			// Check if response contains error code P410 (duplicate/already exists)
			if ( isset( $response['code'] ) && $response['code'] === 'P410' && isset( $response['parcelIds'] ) ) {
				// P410 means parcels already exist for this order number.
				// This happens when:
				// 1. Parcels were created and still exist in our meta
				// 2. Parcels were cancelled but BoxNow still has them tied to this order number
				// If we have NO existing parcels in our meta but BoxNow says they exist,
				// it means they were cancelled and BoxNow won't let us create new ones.
				if ( empty( $existing_parcel_ids ) || ! is_array( $existing_parcel_ids ) || count( $existing_parcel_ids ) === 0 ) {
					throw new \Exception(
						__( 'Cannot create voucher: A cancelled voucher exists for this order in BoxNow\'s system. The order number cannot be reused. Please contact BoxNow support or create a new order.', 'codesoup-woo-boxnow' )
					);
				}
				// If we already have these parcels in our system, just use them (normal duplicate case)
				$parcel_ids = $response['parcelIds'];
			} elseif ( isset( $response['code'] ) && isset( $response['status'] ) && $response['status'] >= 400 ) {
				// Other API error
				$error_msg = sprintf(
					'BoxNow API Error %s: %s',
					$response['code'],
					$response['status']
				);
				throw new \Exception( $error_msg );
			} elseif ( ! isset( $response['parcels'] ) ) {
				throw new \Exception( __( 'Failed to create delivery request.', 'codesoup-woo-boxnow' ) );
			} else {
				// Normal success response
				$parcel_ids = array_column( $response['parcels'], 'id' );
			}

			// Merge with existing parcel IDs (avoid duplicates)
			if ( ! empty( $existing_parcel_ids ) && is_array( $existing_parcel_ids ) ) {
				$parcel_ids = array_unique( array_merge( $existing_parcel_ids, $parcel_ids ) );
			}

			// Store parcel IDs and vouchers created flag in order meta.
			$order->update_meta_data( Meta_Keys::PARCEL_IDS, $parcel_ids );
			$order->update_meta_data( Meta_Keys::VOUCHERS_CREATED, 1 );
			$order->save();

			$this->log_info(
				sprintf(
					'Created %d vouchers for order #%d',
					count( $parcel_ids ),
					$order_id
				)
			);

			// Generate HTML for parcel items
			$parcel_items_html = '';
			$template_order_id = $order_id; // Store order_id before loop

			error_log( '=== Generating HTML for parcel IDs: ' . wp_json_encode( $parcel_ids ) . ' ===' );
			error_log( '=== Template Order ID: ' . $template_order_id . ' ===' );

			foreach ( $parcel_ids as $parcel_id ) {
				ob_start();
				// Set variables expected by parcel-link-item.php template
				$order_id = $template_order_id;
				error_log( '=== Including template for parcel_id: ' . $parcel_id . ', order_id: ' . $order_id . ' ===' );
				include plugin()->get_config( 'PLUGIN_BASE_PATH' ) . 'includes/admin/views/parcel-link-item.php';
				$parcel_items_html .= ob_get_clean();
			}
			$order_id = $template_order_id; // Restore after loop

			error_log( '=== Generated HTML length: ' . strlen( $parcel_items_html ) . ' bytes ===' );

			// Return just the parcel items HTML (column will be shown via JS)
			$table_html = $parcel_items_html;

			wp_send_json_success(
				array(
					'new_parcel_ids' => $parcel_ids,
					'html'           => $table_html,
					'message'        => sprintf(
						// translators: %d: number of vouchers created.
						__( '%d voucher(s) created successfully.', 'codesoup-woo-boxnow' ),
						count( $parcel_ids )
					),
				)
			);

		} catch ( \Exception $e ) {
			$this->log_error( 'Failed to create vouchers: ' . $e->getMessage() );
			wp_send_json_error( esc_html( $e->getMessage() ), 500 );
		}
	}



	/**
	 * Handle AJAX request to cancel voucher.
	 */
	public function ajax_cancel_voucher(): void {
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

			$this->order_service->remove_parcel_ids( $order, array( $parcel_id ) );

			$order->add_order_note(
				sprintf(
					/* translators: %s: parcel ID */
					__( 'BoxNow voucher cancelled successfully: %s', 'codesoup-woo-boxnow' ),
					$parcel_id
				),
				false
			);

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
	 * Handle AJAX request to cancel all vouchers.
	 */
	public function ajax_cancel_all_vouchers(): void {
		// Verify nonce.
		if ( ! $this->verify_nonce() ) {
			wp_send_json_error( __( 'Security check failed.', 'codesoup-woo-boxnow' ), 403 );
		}

		// Verify permissions.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'codesoup-woo-boxnow' ), 403 );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

		if ( ! $order_id ) {
			wp_send_json_error( __( 'Invalid order ID.', 'codesoup-woo-boxnow' ), 400 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			wp_send_json_error( __( 'Invalid BoxNow order.', 'codesoup-woo-boxnow' ), 400 );
		}

		$parcel_ids = $this->order_service->get_parcel_ids( $order );

		if ( empty( $parcel_ids ) ) {
			$order->add_order_note( __( 'BoxNow: Cancel all vouchers requested, but no parcel IDs were found.', 'codesoup-woo-boxnow' ), false );
			wp_send_json_error( __( 'No voucher parcel IDs were found for this order.', 'codesoup-woo-boxnow' ) );
		}

		$cancelled_parcel_ids = array();
		$failed_cancellations = array();

		foreach ( $parcel_ids as $parcel_id ) {
			if ( $this->parcel_service->cancel_parcel( $parcel_id ) ) {
				$cancelled_parcel_ids[] = $parcel_id;
			} else {
				$failed_cancellations[] = $parcel_id;
			}
		}

		$remaining_parcel_ids = $parcel_ids;

		if ( ! empty( $cancelled_parcel_ids ) ) {
			$remaining_parcel_ids = $this->order_service->remove_parcel_ids( $order, $cancelled_parcel_ids );
			$order->add_order_note(
				sprintf(
					/* translators: %s: comma-separated parcel IDs */
					__( 'BoxNow voucher cancellation request sent for parcel ID(s): %s', 'codesoup-woo-boxnow' ),
					implode( ', ', $cancelled_parcel_ids )
				),
				false
			);
		}

		if ( ! empty( $failed_cancellations ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: comma-separated parcel IDs */
					__( 'BoxNow voucher cancellation failed for parcel ID(s): %s', 'codesoup-woo-boxnow' ),
					implode( ', ', $failed_cancellations )
				),
				false
			);
		}

		if ( empty( $cancelled_parcel_ids ) ) {
			wp_send_json_error(
				sprintf(
					/* translators: %s: comma-separated parcel IDs */
					__( 'Failed to cancel vouchers: %s', 'codesoup-woo-boxnow' ),
					implode( ', ', $failed_cancellations )
				)
			);
		}

		$this->log_info(
			sprintf(
				'Canceled %d voucher(s) for order #%d',
				count( $cancelled_parcel_ids ),
				$order_id
			)
		);

		wp_send_json_success(
			array(
				'cancelled_parcel_ids' => $cancelled_parcel_ids,
				'failed_cancellations' => $failed_cancellations,
				'remaining_parcel_ids' => $remaining_parcel_ids,
			)
		);
	}

	/**
	 * Handle AJAX request to print voucher PDF.
	 */
	public function ajax_print_box_now_voucher(): void {
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