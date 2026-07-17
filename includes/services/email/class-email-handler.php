<?php
/**
 * Email Handler
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Email;

use CodeSoup\BoxNow\Core\Hooker;
use CodeSoup\BoxNow\Helpers\Order_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Handles email customizations for BoxNow orders.
 */
class Email_Handler {

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
		$this->hooker->add_action( 'woocommerce_email_after_order_table', $this, 'add_locker_details_to_email', 10, 4 );
	}

	/**
	 * Add locker details to order emails.
	 *
	 * @param \WC_Order $order Order object.
	 * @param bool      $sent_to_admin Sent to admin.
	 * @param bool      $plain_text Plain text email.
	 * @param \WC_Email $email Email object.
	 */
	public function add_locker_details_to_email( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! Order_Helper::is_box_now_order( $order ) ) {
			return;
		}

		$locker_id      = $order->get_meta( '_boxnow_locker_id' );
		$locker_name    = $order->get_meta( '_boxnow_locker_name' );
		$locker_address = $order->get_meta( '_boxnow_locker_address' );
		$locker_city    = $order->get_meta( '_boxnow_locker_city' );
		$locker_postcode = $order->get_meta( '_boxnow_locker_postcode' );
		$locker_note    = $order->get_meta( '_boxnow_locker_note' );
		$locker_image   = $order->get_meta( '_boxnow_locker_image' );

		if ( ! $locker_id ) {
			return;
		}

		if ( $plain_text ) {
			$this->render_plain_text_locker_details( $locker_id, $locker_name, $locker_address, $locker_city, $locker_postcode, $locker_note, $locker_image );
		} else {
			$this->render_html_locker_details( $locker_id, $locker_name, $locker_address, $locker_city, $locker_postcode, $locker_note, $locker_image );
		}
	}

	/**
	 * Render HTML locker details.
	 *
	 * @param string $id Locker ID.
	 * @param string $name Locker name.
	 * @param string $address Locker address.
	 * @param string $city Locker city.
	 * @param string $postcode Locker postcode.
	 * @param string $note Locker note.
	 * @param string $image Locker image URL.
	 */
	private function render_html_locker_details( $id, $name, $address, $city, $postcode, $note, $image ) {
		?>
		<h2><?php esc_html_e( 'BoxNow Locker Details', 'codesoup-woo-boxnow' ); ?></h2>
		<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin-bottom: 20px;">
			<tbody>
				<?php if ( $id ) : ?>
				<tr>
					<th style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php esc_html_e( 'Locker ID', 'codesoup-woo-boxnow' ); ?></th>
					<td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html( $id ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $name ) : ?>
				<tr>
					<th style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php esc_html_e( 'Locker Name', 'codesoup-woo-boxnow' ); ?></th>
					<td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html( $name ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $address ) : ?>
				<tr>
					<th style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php esc_html_e( 'Address', 'codesoup-woo-boxnow' ); ?></th>
					<td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html( $address ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $city || $postcode ) : ?>
				<tr>
					<th style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php esc_html_e( 'City & Postcode', 'codesoup-woo-boxnow' ); ?></th>
					<td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html( trim( $postcode . ' ' . $city ) ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $note ) : ?>
				<tr>
					<th style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php esc_html_e( 'Note', 'codesoup-woo-boxnow' ); ?></th>
					<td style="text-align: left; border: 1px solid #eee; padding: 12px;"><?php echo esc_html( $note ); ?></td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php if ( $image ) : ?>
		<p style="margin-bottom: 20px;">
			<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" style="max-width: 100%; height: auto; border: 1px solid #eee;" />
		</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render plain text locker details.
	 *
	 * @param string $id Locker ID.
	 * @param string $name Locker name.
	 * @param string $address Locker address.
	 * @param string $city Locker city.
	 * @param string $postcode Locker postcode.
	 * @param string $note Locker note.
	 * @param string $image Locker image URL.
	 */
	private function render_plain_text_locker_details( $id, $name, $address, $city, $postcode, $note, $image ) {
		echo "\n" . esc_html__( 'BOXNOW LOCKER DETAILS', 'codesoup-woo-boxnow' ) . "\n\n";

		if ( $id ) {
			echo esc_html__( 'Locker ID:', 'codesoup-woo-boxnow' ) . ' ' . esc_html( $id ) . "\n";
		}

		if ( $name ) {
			echo esc_html__( 'Locker Name:', 'codesoup-woo-boxnow' ) . ' ' . esc_html( $name ) . "\n";
		}
		
		if ( $address ) {
			echo esc_html__( 'Address:', 'codesoup-woo-boxnow' ) . ' ' . esc_html( $address ) . "\n";
		}
		
		if ( $city || $postcode ) {
			echo esc_html__( 'City & Postcode:', 'codesoup-woo-boxnow' ) . ' ' . esc_html( trim( $postcode . ' ' . $city ) ) . "\n";
		}
		
		if ( $note ) {
			echo esc_html__( 'Note:', 'codesoup-woo-boxnow' ) . ' ' . esc_html( $note ) . "\n";
		}
		
		if ( $image ) {
			echo esc_html__( 'Image:', 'codesoup-woo-boxnow' ) . ' ' . esc_url( $image ) . "\n";
		}
		
		echo "\n";
	}
}
