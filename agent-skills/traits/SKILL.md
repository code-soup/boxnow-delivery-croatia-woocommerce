---
name: traits
description: Use existing traits and create custom traits for code reuse. Use when sharing common functionality across multiple classes without inheritance.
---

# Traits

## Available traits in this plugin

### `Logging_Trait`

**File**: `includes/traits/trait-logging.php`  
**Namespace**: `CodeSoup\BoxNow\Traits`

Uses the **WooCommerce logger** (`wc_get_logger()`), source slug `codesoup-boxnow`.

```php
use CodeSoup\BoxNow\Traits\Logging_Trait;

class Order_AJAX_Handler {
	use Logging_Trait;

	public function example(): void {
		$this->log_info( 'Vouchers created' );
		$this->log_error( 'API failure' );
		$this->log_warning( 'Retrying' );
		$this->log_debug( 'Payload', array( 'order_id' => 123 ) );
		$this->log_exception( $e, 'create_delivery_request' );
	}
}
```

`log_debug()` only writes when `WP_DEBUG` is true.

### `RequirementChecksTrait`

**File**: `includes/traits/trait-requirement-checks.php`

Used from `Plugin::is_compatible()` with config from `run.php`:

```php
$config = array(
	'MIN_WP_VERSION'  => '7.1',
	'MIN_PHP_VERSION' => '8.1',
	'MIN_WC_VERSION'  => '11.2',
);
self::run_requirement_checks( $config );
```

When `MIN_WC_VERSION` is set, WooCommerce must be active and meet that version.

There is **no** `CachingTrait` in this repository; add one only if you introduce shared caching behavior.

## Creating a new trait

- File: `includes/traits/trait-{name}.php`
- Name: `{Name}_Trait` (this plugin uses underscore before `Trait`, for example `Logging_Trait`)
- Register namespace in `includes/autoloader.php` if you add a new subdirectory

## Rules

- Keep traits focused; prefer services for orchestration logic.
- Document whether a trait depends on WooCommerce or other plugins.
