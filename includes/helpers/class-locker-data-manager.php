<?php
/**
 * Locker Data Manager
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Helpers;

use CodeSoup\BoxNow\Constants\Meta_Keys;
use CodeSoup\BoxNow\Constants\Session_Keys;

defined( 'ABSPATH' ) || exit;

/**
 * Manages locker data storage and retrieval.
 *
 * @deprecated Use Locker_Data_Service instead. Will be removed in future version.
 */
class Locker_Data_Manager {

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
	 * @deprecated Use Locker_Data_Service::save_to_session() instead.
	 *
	 * @param array $data Locker data.
	 */
	public static function save_to_session( $data ) {
		\CodeSoup\BoxNow\plugin()->get( 'locker_data' )->save_to_session( $data );
	}

	/**
	 * Get locker data from session.
	 *
	 * @deprecated Use Locker_Data_Service::get_from_session() instead.
	 *
	 * @return array
	 */
	public static function get_from_session() {
		return \CodeSoup\BoxNow\plugin()->get( 'locker_data' )->get_from_session();
	}

	/**
	 * Clear locker data from session.
	 *
	 * @deprecated Use Locker_Data_Service::clear_session() instead.
	 */
	public static function clear_session() {
		\CodeSoup\BoxNow\plugin()->get( 'locker_data' )->clear_session();
	}

	/**
	 * Save locker data to order meta.
	 *
	 * @deprecated Use Locker_Data_Service::save_to_order() instead.
	 *
	 * @param \WC_Order $order Order object.
	 * @param array     $data  Locker data.
	 */
	public static function save_to_order( $order, $data = null ) {
		\CodeSoup\BoxNow\plugin()->get( 'locker_data' )->save_to_order( $order, $data );
	}

	/**
	 * Sanitize POST data for locker fields.
	 *
	 * @deprecated Use Locker_Data_Service::sanitize_post_data() instead.
	 *
	 * @param array $post_data POST data.
	 * @return array
	 */
	public static function sanitize_post_data( $post_data ) {
		return \CodeSoup\BoxNow\plugin()->get( 'locker_data' )->sanitize_post_data( $post_data );
	}
}
