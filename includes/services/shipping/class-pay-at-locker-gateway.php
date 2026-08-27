<?php
/**
 * Pay at Locker Gateway
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Virtual payment gateway for card payment at BoxNow locker.
 */
class Pay_At_Locker_Gateway extends \WC_Payment_Gateway {

	/**
	 * Constructor.
	 *
	 * @param string $title Gateway title.
	 */
	public function __construct( $title = '' ) {
		$this->id                 = 'boxnow_pay_at_locker';
		$this->method_title       = __( 'BoxNow Pay at Locker', 'codesoup-woo-boxnow' );
		$this->method_description = __( 'Customer pays with card at the BoxNow locker upon pickup.', 'codesoup-woo-boxnow' );
		$this->has_fields         = false;
		$this->enabled            = 'yes';

		if ( ! empty( $title ) ) {
			$this->title = $title;
		} else {
			$this->title = __( 'Pay with Card at BoxNow', 'codesoup-woo-boxnow' );
		}

		$this->description = __( 'Pay with your card when you pick up your order from the BoxNow locker.', 'codesoup-woo-boxnow' );
	}

	/**
	 * Process payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}

		$order->update_status( 'on-hold', __( 'Awaiting card payment at BoxNow locker.', 'codesoup-woo-boxnow' ) );

		$order->add_order_note(
			sprintf(
				__( 'Customer will pay with card at BoxNow locker upon pickup.', 'codesoup-woo-boxnow' )
			)
		);

		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
