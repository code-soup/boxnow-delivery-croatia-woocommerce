<?php
/**
 * Locker Data Service
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Services;

use CodeSoup\BoxNow\Constants\Meta_Keys;
use CodeSoup\BoxNow\Constants\Session_Keys;

defined( 'ABSPATH' ) || exit;

/**
 * Manages locker data storage and retrieval using DI.
 */
class Locker_Data_Service {

	/**
	 * Locker data fields mapping.
	 *
	 * @var array
	 */
	const FIELDS = array(
		'locker_id'       => array(
			'session_key' => Session_Keys::LOCKER_ID,
			'meta_key'    => Meta_Keys::LOCKER_ID,
		),
		'locker_name'     => array(
			'session_key' => Session_Keys::LOCKER_NAME,
			'meta_key'    => Meta_Keys::LOCKER_NAME,
		),
		'locker_address'  => array(
			'session_key' => Session_Keys::LOCKER_ADDRESS,
			'meta_key'    => Meta_Keys::LOCKER_ADDRESS,
		),
		'locker_city'     => array(
			'session_key' => Session_Keys::LOCKER_CITY,
			'meta_key'    => Meta_Keys::LOCKER_CITY,
		),
		'locker_postcode' => array(
			'session_key' => Session_Keys::LOCKER_POSTCODE,
			'meta_key'    => Meta_Keys::LOCKER_POSTCODE,
		),
		'locker_country'  => array(
			'session_key' => Session_Keys::LOCKER_COUNTRY,
			'meta_key'    => Meta_Keys::LOCKER_COUNTRY,
		),
		'locker_note'     => array(
			'session_key' => Session_Keys::LOCKER_NOTE,
			'meta_key'    => Meta_Keys::LOCKER_NOTE,
		),
		'locker_image'    => array(
			'session_key' => Session_Keys::LOCKER_IMAGE,
			'meta_key'    => Meta_Keys::LOCKER_IMAGE,
		),
		'warehouse'       => array(
			'session_key' => Session_Keys::WAREHOUSE,
			'meta_key'    => Meta_Keys::WAREHOUSE,
		),
	);

	/**
	 * Save locker data to session.
	 *
	 * @param array $data Locker data.
	 */
	public function save_to_session( array $data ): void {
		if ( ! $this->is_wc_session_available() ) {
			return;
		}

		foreach ( self::FIELDS as $field_key => $config ) {
			if ( isset( $data[ $field_key ] ) && ! empty( $data[ $field_key ] ) ) {
				WC()->session->set( $config['session_key'], $data[ $field_key ] );
			}
		}
	}

	/**
	 * Get locker data from session.
	 *
	 * @return array
	 */
	public function get_from_session(): array {
		if ( ! $this->is_wc_session_available() ) {
			return array();
		}

		$data = array();
		foreach ( self::FIELDS as $field_key => $config ) {
			$value = WC()->session->get( $config['session_key'] );
			if ( $value ) {
				$data[ $field_key ] = $value;
			}
		}

		return $data;
	}

	/**
	 * Clear locker data from session.
	 */
	public function clear_session(): void {
		if ( ! $this->is_wc_session_available() ) {
			return;
		}

		foreach ( self::FIELDS as $config ) {
			WC()->session->set( $config['session_key'], null );
		}
	}

	/**
	 * Save locker data to order meta.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $data  Locker data (optional, fetches from session if null).
	 */
	public function save_to_order( \WC_Order $order, ?array $data = null ): void {
		if ( null === $data ) {
			$data = $this->get_from_session();
		}

		foreach ( self::FIELDS as $field_key => $config ) {
			if ( isset( $data[ $field_key ] ) && ! empty( $data[ $field_key ] ) ) {
				$order->update_meta_data( $config['meta_key'], sanitize_text_field( $data[ $field_key ] ) );
			}
		}

		$order->save();
	}

	/**
	 * Sanitize POST data for locker fields.
	 *
	 * @param array $post_data POST data.
	 * @return array
	 */
	public function sanitize_post_data( array $post_data ): array {
		$data = array();

		foreach ( self::FIELDS as $field_key => $config ) {
			$data[ $field_key ] = isset( $post_data[ $field_key ] )
				? sanitize_text_field( wp_unslash( $post_data[ $field_key ] ) )
				: '';
		}

		return $data;
	}

	/**
	 * Check if WooCommerce session is available.
	 *
	 * @return bool
	 */
	private function is_wc_session_available(): bool {
		return function_exists( 'WC' ) && WC()->session instanceof \WC_Session;
	}
}
