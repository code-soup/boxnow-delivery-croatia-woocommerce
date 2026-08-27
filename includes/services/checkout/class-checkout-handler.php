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

		if ( ! $order ) {
			return;
		}

		// Log shipping methods
		$shipping_methods = $order->get_shipping_methods();
		foreach ( $shipping_methods as $item ) {
		}

		$is_boxnow = Order_Helper::is_box_now_order( $order );

		if ( ! $is_boxnow ) {
			return;
		}

		// Try to get data from $_POST first (submitted with checkout form)
		$post_locker_id = isset( $_POST['boxnow_locker_id'] ) ? sanitize_text_field( wp_unslash( $_POST['boxnow_locker_id'] ) ) : '';

		if ( ! empty( $post_locker_id ) ) {

			// Build locker data from POST
			$locker_data = array(
				'locker_id'      => $post_locker_id,
				'locker_name'    => isset( $_POST['boxnow_locker_name'] ) ? sanitize_text_field( wp_unslash( $_POST['boxnow_locker_name'] ) ) : '',
				'warehouse'      => isset( $_POST['boxnow_warehouse'] ) ? sanitize_text_field( wp_unslash( $_POST['boxnow_warehouse'] ) ) : '',
				'locker_address' => isset( $_POST['shipping_address_1'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_address_1'] ) ) : '',
				'locker_city'    => isset( $_POST['shipping_city'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_city'] ) ) : '',
				'locker_postcode' => isset( $_POST['shipping_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ) ) : '',
				'locker_country' => isset( $_POST['shipping_country'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_country'] ) ) : '',
			);


			// Save to order
			foreach ( $locker_data as $key => $value ) {
				if ( ! empty( $value ) ) {
					$order->update_meta_data( '_boxnow_' . $key, $value );
				}
			}
			$order->save();
		} else {

			// Fallback to session data
			$session_data = Locker_Data_Manager::get_from_session();

			if ( empty( $session_data ) ) {
			} else {
				Locker_Data_Manager::save_to_order( $order );
			}
		}

		Locker_Data_Manager::clear_session();
	}

	/**
	 * AJAX handler to save locker selection.
	 */
	public function ajax_save_locker() {

		check_ajax_referer( 'codesoup_boxnow_nonce', 'nonce' );

		// Check if data is sent as JSON string
		if ( isset( $_POST['locker_data'] ) && is_string( $_POST['locker_data'] ) ) {
			$locker_data = json_decode( wp_unslash( $_POST['locker_data'] ), true );

			if ( is_array( $locker_data ) ) {
				$data = Locker_Data_Manager::sanitize_post_data( $locker_data );
			} else {
				$data = Locker_Data_Manager::sanitize_post_data( $_POST );
			}
		} else {
			// Fallback: try to get data directly from $_POST
			$data = Locker_Data_Manager::sanitize_post_data( $_POST );
		}


		// Default warehouse if not provided
		if ( empty( $data['warehouse'] ) ) {
			$warehouse_ids   = explode( ',', str_replace( ' ', '', get_option( 'boxnow_warehouse_id', '' ) ) );
			$data['warehouse'] = ! empty( $warehouse_ids ) ? $warehouse_ids[0] : '';
		}

		Locker_Data_Manager::save_to_session( $data );

		// Verify it was saved
		$session_check = Locker_Data_Manager::get_from_session();

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
		$button_description   = get_option( 'boxnow_button_description', '' );
		$shipping_method_name = __( 'BoxNow Delivery by CodeSoup', 'codesoup-woo-boxnow' );
		$button_color         = get_option( 'boxnow_button_color', '#6CD04E' );

		// Use constants for CSS classes
		$button_base_class     = \CodeSoup\BoxNow\Core\Constants::get_css_class( 'BUTTON_BASE' );
		$button_checkbox_class = \CodeSoup\BoxNow\Core\Constants::get_css_class( 'BUTTON_CHECKBOX' );

		?>
		<span class="codesoup-boxnow-button-wrapper">
			<button
				type="button"
				class="button <?php echo esc_attr( $button_base_class . ' ' . $button_checkbox_class ); ?>"
				style="display:block; margin-top: 10px; background-color: <?php echo esc_attr( $button_color ); ?> !important; color: #fff !important;"
			>
				<?php echo esc_html( $button_text . ' - ' . $shipping_method_name ); ?>
			</button>
			<?php if ( ! empty( $button_description ) ) : ?>
				<div class="boxnow-button-description" style="margin-top: 8px; font-size: 0.9em; color: #666;">
					<?php echo wp_kses_post( $button_description ); ?>
				</div>
			<?php endif; ?>
		</span>
		<?php
	}

}
