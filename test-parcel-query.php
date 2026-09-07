<?php
/**
 * Test script to query a specific BoxNow parcel
 * 
 * Usage: Navigate to your WordPress site URL + /test-parcel-query.php?parcel_id=5640740248
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Access denied' );
}

$parcel_id = isset( $_GET['parcel_id'] ) ? sanitize_text_field( $_GET['parcel_id'] ) : '5640740248';

echo '<h1>BoxNow Parcel Query Test</h1>';
echo '<p>Testing parcel ID: ' . esc_html( $parcel_id ) . '</p>';

// Get access token
$access_token_transient = get_transient( 'codesoup_boxnow_access_token' );

if ( ! $access_token_transient ) {
	echo '<p><strong>No cached token found. Fetching new token...</strong></p>';
	
	$api_url = get_option( 'codesoup_boxnow_api_url' );
	$client_id = get_option( 'codesoup_boxnow_client_id' );
	$client_secret = get_option( 'codesoup_boxnow_client_secret' );
	
	echo '<pre>';
	echo 'API URL: ' . esc_html( $api_url ) . "\n";
	echo 'Client ID: ' . esc_html( $client_id ? substr( $client_id, 0, 8 ) . '...' : 'NOT SET' ) . "\n";
	echo '</pre>';
	
	if ( empty( $api_url ) || empty( $client_id ) || empty( $client_secret ) ) {
		wp_die( 'API credentials not configured' );
	}
	
	$auth_url = 'https://' . $api_url . '/api/v1/auth-sessions';
	
	$auth_response = wp_remote_post(
		$auth_url,
		array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'clientId'     => $client_id,
					'clientSecret' => $client_secret,
				)
			),
			'timeout' => 10,
		)
	);
	
	if ( is_wp_error( $auth_response ) ) {
		wp_die( 'Auth failed: ' . $auth_response->get_error_message() );
	}
	
	$auth_body = json_decode( wp_remote_retrieve_body( $auth_response ), true );
	$access_token_transient = $auth_body['accessToken'] ?? null;
	
	if ( ! $access_token_transient ) {
		echo '<pre>';
		print_r( $auth_body );
		echo '</pre>';
		wp_die( 'No access token in auth response' );
	}
	
	echo '<p>✅ Got new access token</p>';
}

echo '<h2>Querying Parcel...</h2>';

$api_url = get_option( 'codesoup_boxnow_api_url' );
$parcel_url = 'https://' . $api_url . '/api/v1/parcels/' . rawurlencode( $parcel_id );

echo '<p>Endpoint: <code>' . esc_html( $parcel_url ) . '</code></p>';

$parcel_response = wp_remote_get(
	$parcel_url,
	array(
		'headers' => array(
			'Authorization' => 'Bearer ' . $access_token_transient,
			'Content-Type'  => 'application/json',
		),
		'timeout' => 10,
	)
);

if ( is_wp_error( $parcel_response ) ) {
	wp_die( 'Parcel query failed: ' . $parcel_response->get_error_message() );
}

$response_code = wp_remote_retrieve_response_code( $parcel_response );
$response_body = wp_remote_retrieve_body( $parcel_response );

echo '<h3>Response</h3>';
echo '<p>Status Code: <strong>' . esc_html( $response_code ) . '</strong></p>';
echo '<pre>';
echo esc_html( $response_body );
echo '</pre>';

if ( $response_code === 200 ) {
	$parcel_data = json_decode( $response_body, true );
	echo '<h3>Parsed Data</h3>';
	echo '<pre>';
	print_r( $parcel_data );
	echo '</pre>';
	
	if ( isset( $parcel_data['status'] ) ) {
		echo '<p>Parcel Status: <strong>' . esc_html( $parcel_data['status'] ) . '</strong></p>';
	}
	
	if ( isset( $parcel_data['origin'] ) ) {
		echo '<h4>Origin Location</h4>';
		echo '<pre>';
		print_r( $parcel_data['origin'] );
		echo '</pre>';
	}
}
