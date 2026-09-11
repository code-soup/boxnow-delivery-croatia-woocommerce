<?php
/**
 * Order Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Orders;

use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Services\API\Delivery_Request_Service;
use CodeSoup\BoxNow\Services\API\Parcel_Service;
use CodeSoup\BoxNow\Services\Order_Service;
use CodeSoup\BoxNow\Helpers\Order_Helper;
use CodeSoup\BoxNow\Constants\Meta_Keys;
use function CodeSoup\BoxNow\plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Handles order processing for Box Now Delivery.
 */
class Order_Handler {

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
	 * Initialize hooks.
	 */
	public function init(): void {
		$this->hooker->add_action( 'woocommerce_order_status_completed', $this, 'handle_order_completed', 10, 1 );
		$this->hooker->add_action( 'woocommerce_order_status_changed', $this, 'handle_order_cancellation', 5, 4 );
		$this->hooker->add_action( 'init', $this, 'register_custom_order_status' );
		$this->hooker->add_filter( 'woocommerce_admin_order_actions', $this, 'add_cancel_button', 10, 2 );
		$this->hooker->add_action( 'admin_enqueue_scripts', $this, 'add_cancel_button_css' );
		$this->hooker->add_action( 'add_meta_boxes', $this, 'register_metaboxes' );
	}

	/**
	 * Handle order completion.
	 *
	 * @param int $order_id Order ID.
	 */
	public function handle_order_completed( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		if ( $order->get_meta( Meta_Keys::VOUCHER_CREATED, true ) ) {
			return;
		}

		$data = $this->delivery_service->prepare_delivery_data( $order );
		$response = $this->delivery_service->create_delivery_request( $data );

		if ( $response && isset( $response['parcels'][0]['id'] ) ) {
			$order->update_meta_data( Meta_Keys::PARCEL_ID, $response['parcels'][0]['id'] );
			$order->update_meta_data( Meta_Keys::VOUCHER_CREATED, 'yes' );
			$order->save();
		}
	}

	/**
	 * Handle order cancellation.
	 *
	 * @param int       $order_id   Order ID.
	 * @param string    $old_status Old status.
	 * @param string    $new_status New status.
	 * @param \WC_Order $order      Order object.
	 */
	public function handle_order_cancellation( $order_id, $old_status, $new_status, $order ) {
		if ( ! in_array( $new_status, array( 'wc-boxnow-canceled', 'boxnow-canceled' ), true ) ) {
			return;
		}

		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		$parcel_ids = $this->order_service->get_parcel_ids( $order );

		if ( empty( $parcel_ids ) ) {
			$order->add_order_note( __( 'BoxNow: Order marked as canceled, but no parcel IDs were found for API cancellation.', 'codesoup-woo-boxnow' ), false );
			return;
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

		if ( ! empty( $cancelled_parcel_ids ) ) {
			$this->order_service->remove_parcel_ids( $order, $cancelled_parcel_ids );
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
	}

	/**
	 * Register custom order status.
	 */
	public function register_custom_order_status() {
		register_post_status(
			'wc-boxnow-canceled',
			array(
				'label'                     => __( 'Box Now Canceled', 'codesoup-woo-boxnow' ),
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					'Box Now Canceled <span class="count">(%s)</span>',
					'Box Now Canceled <span class="count">(%s)</span>',
					'codesoup-woo-boxnow'
				),
			)
		);
	}

	/**
	 * Add cancel button to order actions.
	 *
	 * @param array     $actions Actions.
	 * @param \WC_Order $order   Order object.
	 * @return array
	 */
	public function add_cancel_button( $actions, $order ) {
		if ( ! $order->has_status( array( 'completed' ) ) || ! Order_Helper::is_box_now_order( $order ) ) {
			return $actions;
		}

		$actions['boxnow_cancel'] = array(
			'url'    => wp_nonce_url(
				admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=wc-boxnow-canceled&order_id=' . $order->get_id() ),
				'woocommerce-mark-order-status'
			),
			'name'   => __( 'Cancel Order', 'codesoup-woo-boxnow' ),
			'action' => 'boxnow_cancel',
		);

		return $actions;
	}

	/**
	 * Add CSS for cancel button.
	 */
	public function add_cancel_button_css() {
		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.wc-action-button-boxnow_cancel::after { content: "\f153"; color: #a00; }'
		);
	}

	/**
	 * Register custom metaboxes.
	 */
	public function register_metaboxes(): void {
		$screen = 'shop_order';

		add_meta_box(
			'codesoup-box-now',
			__( 'BoxNow by CodeSoup', 'codesoup-woo-boxnow' ),
			array( $this, 'render_boxnow_metabox' ),
			$screen,
			'normal',
			'high'
		);
	}

	/**
	 * Render BoxNow metabox content.
	 *
	 * @param \WP_Post $post Post object.
	 */
	public function render_boxnow_metabox( $post ): void {
		$order = wc_get_order( $post->ID );

		if ( ! $order ) {
			return;
		}

		$this->display_voucher_section( $order );
	}

	/**
	 * Display voucher creation section.
	 *
	 * @param \WC_Order $order Order object.
	 */
	private function display_voucher_section( $order ): void {
		// Only show for BoxNow orders
		if ( ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		// Only show in button mode
		if ( 'button' !== get_option( 'boxnow_voucher_option', 'button' ) ) {
			return;
		}

		// Get locker details
		$locker_id       = $order->get_meta( Meta_Keys::LOCKER_ID );
		$locker_name     = $order->get_meta( Meta_Keys::LOCKER_NAME );
		$locker_address  = $order->get_meta( Meta_Keys::LOCKER_ADDRESS );
		$locker_city     = $order->get_meta( Meta_Keys::LOCKER_CITY );
		$locker_postcode = $order->get_meta( Meta_Keys::LOCKER_POSTCODE );
		$locker_country  = $order->get_meta( Meta_Keys::LOCKER_COUNTRY );

		// Calculate max vouchers from order items
		$max_vouchers = 0;
		foreach ( $order->get_items() as $item ) {
			$max_vouchers += $item->get_quantity();
		}

		// Get existing parcel IDs
		$parcel_ids = $order->get_meta( Meta_Keys::PARCEL_IDS, true );
		if ( ! is_array( $parcel_ids ) ) {
			$parcel_ids = ! empty( $parcel_ids ) ? array( $parcel_ids ) : array();
		}

		// Check if vouchers already created
		$vouchers_created = $order->get_meta( Meta_Keys::VOUCHERS_CREATED, true );
		$is_disabled      = (bool) $vouchers_created;

		// Generate existing parcel HTML
		$parcel_items_html = '';
		foreach ( $parcel_ids as $parcel_id ) {
			$order_id = $order->get_id();
			ob_start();
			include plugin()->get_config( 'PLUGIN_BASE_PATH' ) . 'includes/admin/views/parcel-link-item.php';
			$parcel_items_html .= ob_get_clean();
		}

		// Load template
		$template_path = plugin()->get_config( 'PLUGIN_BASE_PATH' ) . 'includes/admin/views/voucher-metabox.php';
		include $template_path;
	}

}
