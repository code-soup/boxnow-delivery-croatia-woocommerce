---
name: hooker
description: Use Hooker service to register WordPress actions and filters. Use when adding WordPress hooks, filters, or actions in the plugin. Prefer Hooker over direct add_action/add_filter except documented enqueue exceptions.
---

# Hooker Service

The Hooker service **collects** hooks during bootstrap and registers them when `Hooker::run()` is called from `Plugin::run()`.

## Get Hooker instance

Inject via constructor (preferred):

```php
use CodeSoup\BoxNow\Core\Hooker;

public function __construct( Hooker $hooker ) {
	$this->hooker = $hooker;
}
```

Or resolve when registering hooks (in `init()`, not in constructors):

```php
use function CodeSoup\BoxNow\plugin;

$hooker = plugin()->get( 'hooker' );
```

## Add action

```php
$this->hooker->add_action(
	'init',
	$this,
	'method_name',
	10,
	1
);
```

If the method name matches the hook name, omit the third argument:

```php
$this->hooker->add_action( 'init', $this );
```

## Add filter

```php
$this->hooker->add_filter(
	'woocommerce_checkout_fields',
	$this,
	'add_hidden_fields',
	10,
	1
);
```

## Multiple actions or filters

`add_actions()` and `add_filters()` accept an **array of tuples** `[ $hook, $component, $method, $priority, $accepted_args ]`:

```php
$this->hooker->add_actions(
	array(
		array( 'wp_ajax_create_box_now_vouchers', $this, 'ajax_create_box_now_vouchers' ),
		array( 'wp_ajax_cancel_voucher', $this, 'ajax_cancel_voucher' ),
	)
);
```

Method name is required when the hook name is not a valid PHP method name (for example `wp_ajax_*`).

## Method validation

Hooker throws if the callback method does not exist on the object.

## Exception: asset enqueue timing

`includes/admin/class-init.php` and `includes/frontend/class-init.php` call `add_action( 'admin_enqueue_scripts' | 'wp_enqueue_scripts', ... )` **directly** so scripts enqueue even when `admin_enqueue_scripts` fires before deferred Hooker registration completes. Do not copy this pattern unless you have the same bootstrap ordering issue.

## Rules

- Prefer Hooker for feature hooks registered from service providers.
- Pass `$this` as the component for class methods.
- Register hooks from `init()` on services, not from constructors.
- Do not assume a `register()` bulk API exists on Hooker (it does not).
