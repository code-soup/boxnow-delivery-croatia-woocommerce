<?php
/**
 * Order Service
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services;

use CodeSoup\BoxNow\Constants\Meta_Keys;
use CodeSoup\BoxNow\Constants\Shipping_Method_Ids;

defined( 'ABSPATH' ) || exit;

/**
 * Service for order-related operations.
 */
class Order_Service {

	/**
	 * Check if order uses Box Now delivery.
	 *
	 * @param \WC_Order $order Order object.
	 * @return bool
	 */
	public function is_box_now_order( \WC_Order $order ): bool {
		return $order->has_shipping_method( Shipping_Method_Ids::CURRENT );
	}

	/**
	 * Check if Box Now delivery is currently selected.
	 *
	 * @return bool
	 */
	public function is_box_now_selected(): bool {
		if ( ! $this->is_wc_session_available() ) {
			return false;
		}

		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

		if ( ! is_array( $chosen_methods ) ) {
			return false;
		}

		foreach ( $chosen_methods as $method ) {
			if ( Shipping_Method_Ids::is_box_now_method( $method ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get all parcel IDs stored on an order.
	 *
	 * @param \WC_Order $order Order object.
	 * @return array<int, string>
	 */
	public function get_parcel_ids( \WC_Order $order ): array {
		$parcel_ids = array();
		$multiple   = $order->get_meta( Meta_Keys::PARCEL_IDS, true );

		if ( is_array( $multiple ) ) {
			foreach ( $multiple as $parcel_id ) {
				$this->add_parcel_id_to_list( $parcel_ids, $parcel_id );
			}
		} elseif ( is_string( $multiple ) && '' !== $multiple ) {
			$decoded = json_decode( $multiple, true );

			if ( is_array( $decoded ) ) {
				foreach ( $decoded as $parcel_id ) {
					$this->add_parcel_id_to_list( $parcel_ids, $parcel_id );
				}
			} else {
				$this->add_parcel_id_to_list( $parcel_ids, $multiple );
			}
		}

		$this->add_parcel_id_to_list( $parcel_ids, $order->get_meta( Meta_Keys::PARCEL_ID, true ) );

		return array_values( array_unique( $parcel_ids ) );
	}

	/**
	 * Remove parcel IDs from order meta after cancellation.
	 *
	 * @param \WC_Order          $order                 Order object.
	 * @param array<int, string> $parcel_ids_to_remove Parcel IDs to remove.
	 * @return array<int, string> Remaining parcel IDs.
	 */
	public function remove_parcel_ids( \WC_Order $order, array $parcel_ids_to_remove ): array {
		$parcel_ids_to_remove = array_filter(
			array_map( 'sanitize_text_field', array_map( 'strval', $parcel_ids_to_remove ) )
		);

		if ( empty( $parcel_ids_to_remove ) ) {
			return $this->get_parcel_ids( $order );
		}

		$remaining_parcel_ids = array_values( array_diff( $this->get_parcel_ids( $order ), $parcel_ids_to_remove ) );

		if ( empty( $remaining_parcel_ids ) ) {
			$order->delete_meta_data( Meta_Keys::PARCEL_IDS );
			$order->delete_meta_data( Meta_Keys::PARCEL_ID );
			$order->delete_meta_data( Meta_Keys::VOUCHERS_CREATED );
			$order->delete_meta_data( Meta_Keys::VOUCHER_CREATED );
		} else {
			$order->update_meta_data( Meta_Keys::PARCEL_IDS, $remaining_parcel_ids );
			$order->update_meta_data( Meta_Keys::VOUCHERS_CREATED, 1 );

			$single_parcel_id = sanitize_text_field( (string) $order->get_meta( Meta_Keys::PARCEL_ID, true ) );

			if ( '' !== $single_parcel_id && in_array( $single_parcel_id, $parcel_ids_to_remove, true ) ) {
				$order->delete_meta_data( Meta_Keys::PARCEL_ID );
			}
		}

		$order->save();

		return $remaining_parcel_ids;
	}

	/**
	 * Check if WooCommerce session is available.
	 *
	 * @return bool
	 */
	private function is_wc_session_available(): bool {
		return function_exists( 'WC' ) && WC()->session instanceof \WC_Session;
	}

	/**
	 * Add a sanitized parcel ID to a list.
	 *
	 * @param array<int, string> $parcel_ids Parcel ID list.
	 * @param mixed              $parcel_id  Parcel ID.
	 */
	private function add_parcel_id_to_list( array &$parcel_ids, $parcel_id ): void {
		$parcel_id = sanitize_text_field( (string) $parcel_id );

		if ( '' === $parcel_id ) {
			return;
		}

		$parcel_ids[] = $parcel_id;
	}
}
