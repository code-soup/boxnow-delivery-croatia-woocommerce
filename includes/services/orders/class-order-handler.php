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
use CodeSoup\BoxNow\Helpers\Order_Helper;

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
	 * Constructor.
	 *
	 * @param Hooker                   $hooker           Hooker instance.
	 * @param Delivery_Request_Service $delivery_service Delivery request service.
	 * @param Parcel_Service           $parcel_service   Parcel service.
	 */
	public function __construct( Hooker $hooker, Delivery_Request_Service $delivery_service, Parcel_Service $parcel_service ) {
		$this->hooker = $hooker;
		$this->delivery_service = $delivery_service;
		$this->parcel_service   = $parcel_service;
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
		$this->hooker->add_action( 'woocommerce_admin_order_data_after_shipping_address', $this, 'display_locker_details_in_admin', 10, 1 );
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

		if ( $order->get_meta( '_boxnow_parcel_id', true ) ) {
			return;
		}

		$data = $this->delivery_service->prepare_delivery_data( $order );
		$response = $this->delivery_service->create_delivery_request( $data );

		if ( $response && isset( $response['parcels'][0]['id'] ) ) {
			$order->update_meta_data( '_boxnow_parcel_id', $response['parcels'][0]['id'] );
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

		if ( ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		$parcel_id = $order->get_meta( '_boxnow_parcel_id', true );
		if ( ! empty( $parcel_id ) ) {
			$this->parcel_service->cancel_parcel( $parcel_id );
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
	 * Display locker details in admin order screen.
	 *
	 * @param \WC_Order $order Order object.
	 */
	public function display_locker_details_in_admin( $order ) {
		if ( ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		$locker_id = $order->get_meta( '_boxnow_locker_id' );
		if ( ! $locker_id ) {
			return;
		}

		$locker_name = $order->get_meta( '_boxnow_locker_name' );
		$locker_address = $order->get_meta( '_boxnow_locker_address' );
		$locker_city = $order->get_meta( '_boxnow_locker_city' );
		$locker_postcode = $order->get_meta( '_boxnow_locker_postcode' );
		$locker_country = $order->get_meta( '_boxnow_locker_country' );

		?>
		<div class="boxnow-locker-details" style="margin-top: 20px; padding: 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px;">
			<h4 style="margin-top: 0; margin-bottom: 12px; color: #2c3e50;">
				<?php esc_html_e( 'BoxNow Locker Details', 'codesoup-woo-boxnow' ); ?>
			</h4>
			<p style="margin: 4px 0;">
				<strong><?php esc_html_e( 'Locker ID:', 'codesoup-woo-boxnow' ); ?></strong>
				<?php echo esc_html( $locker_id ); ?>
			</p>
			<?php if ( $locker_name ) : ?>
			<p style="margin: 4px 0;">
				<strong><?php esc_html_e( 'Locker Name:', 'codesoup-woo-boxnow' ); ?></strong>
				<?php echo esc_html( $locker_name ); ?>
			</p>
			<?php endif; ?>
			<?php if ( $locker_address ) : ?>
			<p style="margin: 4px 0;">
				<strong><?php esc_html_e( 'Address:', 'codesoup-woo-boxnow' ); ?></strong>
				<?php echo esc_html( $locker_address ); ?>
			</p>
			<?php endif; ?>
			<?php if ( $locker_city || $locker_postcode ) : ?>
			<p style="margin: 4px 0;">
				<strong><?php esc_html_e( 'City:', 'codesoup-woo-boxnow' ); ?></strong>
				<?php echo esc_html( trim( sprintf( '%s %s', $locker_postcode, $locker_city ) ) ); ?>
			</p>
			<?php endif; ?>
			<?php if ( $locker_country ) : ?>
			<p style="margin: 4px 0;">
				<strong><?php esc_html_e( 'Country:', 'codesoup-woo-boxnow' ); ?></strong>
				<?php echo esc_html( $locker_country ); ?>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}

}
