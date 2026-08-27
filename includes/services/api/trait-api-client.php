<?php
/**
 * API Client Trait
 *
 * @package CodeSoup\BoxNow
 */

namespace CodeSoup\BoxNow\Services\API;

use CodeSoup\BoxNow\Services\Settings_Service;

defined( 'ABSPATH' ) || exit;

/**
 * Shared API client functionality.
 */
trait API_Client_Trait {

	/**
	 * Settings service.
	 *
	 * @var Settings_Service
	 */
	protected Settings_Service $settings;

	/**
	 * Authentication service.
	 *
	 * @var Authentication_Service
	 */
	protected Authentication_Service $auth_service;

	/**
	 * Get API base URL.
	 *
	 * @return string
	 */
	protected function get_api_url(): string {
		return $this->settings->get( 'api_url', '' );
	}

	/**
	 * Build API endpoint URL.
	 *
	 * @param string $path Endpoint path.
	 * @return string
	 */
	protected function get_endpoint( string $path ): string {
		return 'https://' . $this->get_api_url() . $path;
	}

	/**
	 * Get access token.
	 *
	 * @return string|null
	 */
	protected function get_access_token() {
		return $this->auth_service->get_access_token();
	}
}
