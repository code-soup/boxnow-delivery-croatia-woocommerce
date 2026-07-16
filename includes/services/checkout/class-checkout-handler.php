<?php
/**
 * Checkout Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Checkout;

use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Helpers\Locker_Data_Manager;
use CodeSoup\BoxNow\Helpers\Order_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Handles checkout integration for locker selection.
 */
class Checkout_Handler {

	/**
	 * Hooker instance.
	 *
	 * @var Hooker
	 */
	private Hooker $hooker;

	/**
	 * Constructor.
	 *
	 * @param Hooker $hooker Hooker instance.
	 */
	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		$this->hooker->add_action( 'woocommerce_checkout_update_order_meta', $this, 'save_locker_data' );
		$this->hooker->add_action( 'wp_ajax_codesoup_bndp_save_boxnow_locker', $this, 'ajax_save_locker' );
		$this->hooker->add_action( 'wp_ajax_nopriv_codesoup_bndp_save_boxnow_locker', $this, 'ajax_save_locker' );
		$this->hooker->add_action( 'wp_ajax_codesoup_bndp_clear_boxnow_locker', $this, 'ajax_clear_locker' );
		$this->hooker->add_action( 'wp_ajax_nopriv_codesoup_bndp_clear_boxnow_locker', $this, 'ajax_clear_locker' );
		$this->hooker->add_filter( 'woocommerce_checkout_fields', $this, 'add_hidden_fields' );
		$this->hooker->add_action( 'woocommerce_after_checkout_billing_form', $this, 'render_locker_button', 20 );
	}

	/**
	 * Save locker data to order.
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_locker_data( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		Locker_Data_Manager::save_to_order( $order );
		Locker_Data_Manager::clear_session();
	}

	/**
	 * AJAX handler to save locker selection.
	 */
	public function ajax_save_locker() {
		check_ajax_referer( 'codesoup_boxnow_nonce', 'nonce' );

		$data = Locker_Data_Manager::sanitize_post_data( $_POST );

		// Default warehouse if not provided
		if ( empty( $data['warehouse'] ) ) {
			$warehouse_ids   = explode( ',', str_replace( ' ', '', get_option( 'boxnow_warehouse_id', '' ) ) );
			$data['warehouse'] = ! empty( $warehouse_ids ) ? $warehouse_ids[0] : '';
		}

		Locker_Data_Manager::save_to_session( $data );

		wp_send_json_success( $data );
	}

	/**
	 * AJAX handler to clear locker selection.
	 */
	public function ajax_clear_locker() {
		check_ajax_referer( 'codesoup_boxnow_nonce', 'nonce' );

		Locker_Data_Manager::clear_session();

		wp_send_json_success();
	}

	/**
	 * Add hidden fields to checkout.
	 *
	 * @param array $fields Checkout fields.
	 * @return array
	 */
	public function add_hidden_fields( $fields ) {
		$fields['order']['boxnow_locker_id'] = array(
			'type'     => 'hidden',
			'required' => false,
			'class'    => array( 'boxnow-locker-id' ),
		);

		$fields['order']['boxnow_locker_name'] = array(
			'type'     => 'hidden',
			'required' => false,
			'class'    => array( 'boxnow-locker-name' ),
		);

		$fields['order']['boxnow_warehouse'] = array(
			'type'     => 'hidden',
			'required' => false,
			'class'    => array( 'boxnow-warehouse' ),
		);

		return $fields;
	}

	/**
	 * Render locker selection button below ship-to-different-address checkbox.
	 */
	public function render_locker_button() {
		$button_position = get_option( 'boxnow_button_position', 'inline' );

		if ( 'inline' === $button_position || 'custom' === $button_position ) {
			return;
		}

		$button_text          = get_option( 'boxnow_button_text', __( 'Pick a Locker', 'codesoup-woo-boxnow' ) );
		$shipping_method_name = __( 'BoxNow Delivery by CodeSoup', 'codesoup-woo-boxnow' );
		$button_color         = get_option( 'boxnow_button_color', '#6CD04E' );

		?>
		<span class="codesoup-boxnow-button-wrapper">
			<button
				type="button"
				class="button box-now-delivery-button box-now-delivery-button-checkbox"
				style="display:block; margin-top: 10px; background-color: <?php echo esc_attr( $button_color ); ?> !important; color: #fff !important;"
			>
				<?php echo esc_html( $button_text . ' - ' . $shipping_method_name ); ?>
			</button>
		</span>
		<?php
	}

}
