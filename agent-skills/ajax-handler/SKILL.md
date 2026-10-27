---
name: ajax-handler
description: Create AJAX handlers for WordPress admin and checkout. Use when handling asynchronous requests from JavaScript.
---

# AJAX Handler

## Location in this plugin

- PHP: `includes/services/ajax/` — namespace `CodeSoup\BoxNow\Services\Ajax`
- Example: `Order_AJAX_Handler` registered in `AjaxServiceProvider`
- Checkout locker AJAX: `Checkout_Handler` in `includes/services/checkout/`

## Register hooks

```php
$this->hooker->add_action( 'wp_ajax_create_box_now_vouchers', $this, 'ajax_create_box_now_vouchers' );
```

Logged-out checkout:

```php
$this->hooker->add_action( 'wp_ajax_nopriv_codesoup_bndp_save_boxnow_locker', $this, 'ajax_save_locker' );
```

## Admin handler security pattern

From `Order_AJAX_Handler`:

1. Verify nonce (`codesoup_boxnow_nonce`, POST field `nonce` or `security`).
2. Resolve `order_id` and call `Order_Service::user_can_edit_order()` (HPOS-aware).
3. Validate BoxNow order and parcel ownership where applicable.
4. `wp_send_json_success()` / `wp_send_json_error()`.

Shop order capabilities: prefer `edit_shop_order` checks via `Order_Service`, not only `manage_options`.

## Checkout handler security pattern

```php
check_ajax_referer( 'codesoup_boxnow_nonce', 'nonce' );
```

Sanitize locker fields via `Locker_Data_Manager::sanitize_post_data()`.

## JavaScript (admin)

Admin bundle uses **fetch**, not jQuery — see `src/scripts/checkout/core/api-client.js` and `VoucherManager`:

```javascript
const apiClient = ApiClient.fromGlobal( myAjax );
await apiClient.post( 'create_box_now_vouchers', { order_id, voucher_quantity } );
```

`myAjax` is localized in `includes/admin/class-init.php`.

## PDF / GET actions

`print_box_now_voucher` uses GET with `nonce`, `order_id`, and `parcel_id`; verify order owns the parcel before streaming PDF.

## Service provider

```php
$this->singleton( 'order_ajax_handler', function ( $container ) {
	return new Order_AJAX_Handler(
		$container->get( 'hooker' ),
		$container->get( 'delivery_service' ),
		$container->get( 'parcel_service' ),
		$container->get( 'order_service' )
	);
} );

public function boot(): void {
	parent::boot();
	$this->container->get( 'order_ajax_handler' )->init();
}
```

## Rules

- Handlers live under `includes/services/ajax/` (not `includes/ajax/`).
- Use nonce action `codesoup_boxnow_nonce` unless introducing a separate action with a clear scope.
- Always sanitize input; use capability checks appropriate to shop orders.
- Match WordPress action names in JS `action` / `FormData` field.
