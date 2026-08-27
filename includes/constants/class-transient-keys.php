<?php
/**
 * Transient Keys Constants
 *
 * @package CodeSoup\BoxNow
 */

declare( strict_types=1 );

namespace CodeSoup\BoxNow\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * WordPress transient keys used throughout the plugin.
 */
final class Transient_Keys {

	/**
	 * API Token Cache
	 */
	const ACCESS_TOKEN = 'codesoup_boxnow_access_token';

	/**
	 * Transient Expiration Times (in seconds)
	 */
	const TOKEN_EXPIRATION = 3500; // ~1 hour

	/**
	 * Prevent instantiation.
	 */
	private function __construct() {}
}
