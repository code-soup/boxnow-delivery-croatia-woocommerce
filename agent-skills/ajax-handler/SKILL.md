---
name: ajax-handler
description: Create AJAX handlers for WordPress admin and frontend. Use when handling asynchronous requests from JavaScript, form submissions, or dynamic content loading.
---

# AJAX Handler

Create AJAX handlers for admin and frontend requests.

## Create Handler

**File**: `includes/ajax/class-form-handler.php`

```php
<?php

namespace CodeSoup\BoxNow\Ajax;

use CodeSoup\BoxNow\Core\Hooker;

defined( 'ABSPATH' ) || exit;

class FormHandler {

	private Hooker $hooker;

	public function __construct( Hooker $hooker ) {
		$this->hooker = $hooker;
	}

	public function init(): void {
		// For logged-in users
		$this->hooker->add_action(
			'wp_ajax_csbxwoo_submit_form',
			$this,
			'handle_submit'
		);

		// For non-logged-in users
		$this->hooker->add_action(
			'wp_ajax_nopriv_csbxwoo_submit_form',
			$this,
			'handle_submit'
		);
	}

	public function handle_submit(): void {
		// Verify nonce
		if ( ! check_ajax_referer( 'csbxwoo_form_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid nonce', 'codesoup-woo-boxnow' ),
				),
				403
			);
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Permission denied', 'codesoup-woo-boxnow' ),
				),
				403
			);
		}

		// Sanitize input
		$name  = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		// Validate
		if ( empty( $name ) || empty( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Required fields missing', 'codesoup-woo-boxnow' ),
				),
				400
			);
		}

		// Process request
		$result = $this->process_form( $name, $email );

		if ( ! $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Processing failed', 'codesoup-woo-boxnow' ),
				),
				500
			);
		}

		// Success response
		wp_send_json_success(
			array(
				'message' => __( 'Form submitted', 'codesoup-woo-boxnow' ),
				'data'    => $result,
			)
		);
	}

	private function process_form( string $name, string $email ) {
		// Process logic
		return array(
			'id'   => 123,
			'name' => $name,
		);
	}
}
```

## Frontend JavaScript

```javascript
jQuery( document ).ready( function( $ ) {
	$( '#my-form' ).on( 'submit', function( e ) {
		e.preventDefault();

		$.ajax( {
			url: csbxwooData.ajaxUrl,
			type: 'POST',
			data: {
				action: 'csbxwoo_submit_form',
				nonce: csbxwooData.nonce,
				name: $( '#name' ).val(),
				email: $( '#email' ).val()
			},
			success: function( response ) {
				if ( response.success ) {
					console.log( response.data.message );
				} else {
					console.error( response.data.message );
				}
			},
			error: function( xhr ) {
				console.error( 'AJAX error' );
			}
		} );
	} );
} );
```

## Localize Script

```php
$this->assets->localize_script(
	'csbxwoo-main',
	'csbxwooData',
	array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'csbxwoo_form_nonce' ),
	)
);
```

## Admin Only Handler

```php
public function init(): void {
	// Only for logged-in users in admin
	$this->hooker->add_action(
		'wp_ajax_csbxwoo_admin_action',
		$this,
		'handle_admin_action'
	);
}
```

## Public Handler

```php
public function init(): void {
	// For both logged-in and non-logged-in
	$this->hooker->add_actions(
		$this,
		array(
			'wp_ajax_csbxwoo_public_action',
			'wp_ajax_nopriv_csbxwoo_public_action',
		)
	);
}

public function wp_ajax_csbxwoo_public_action(): void {
	// Handler logic
}

public function wp_ajax_nopriv_csbxwoo_public_action(): void {
	// Same handler for non-logged-in
	$this->wp_ajax_csbxwoo_public_action();
}
```

## Response Methods

### Success

```php
wp_send_json_success(
	array(
		'message' => __( 'Success', 'codesoup-woo-boxnow' ),
		'data'    => $data,
	)
);
```

### Error

```php
wp_send_json_error(
	array(
		'message' => __( 'Error', 'codesoup-woo-boxnow' ),
	),
	400
);
```

### Custom JSON

```php
wp_send_json(
	array(
		'custom' => 'response',
	),
	200
);
```

## Register in Service Provider

```php
<?php

namespace CodeSoup\BoxNow\Providers;

use CodeSoup\BoxNow\Abstracts\AbstractServiceProvider;
use CodeSoup\BoxNow\Ajax\FormHandler;

class AjaxServiceProvider extends AbstractServiceProvider {

	public function register(): void {
		$this->singleton(
			'ajax.form',
			FormHandler::class
		);
	}

	public function boot(): void {
		$this->container->get( 'ajax.form' )->init();
	}
}
```

## Security Checklist

- [ ] Verify nonce with `check_ajax_referer()`
- [ ] Check user capabilities
- [ ] Sanitize all input
- [ ] Validate data
- [ ] Use wp_send_json_* functions
- [ ] Return proper HTTP status codes
- [ ] Handle errors gracefully

## Rules

- Create handlers in `includes/ajax/`
- Use `wp_ajax_{action}` for logged-in users
- Use `wp_ajax_nopriv_{action}` for non-logged-in users
- Always verify nonce
- Always check permissions
- Sanitize all input
- Use wp_send_json_success/error
- Localize ajaxUrl and nonce
- Action name must match JavaScript

