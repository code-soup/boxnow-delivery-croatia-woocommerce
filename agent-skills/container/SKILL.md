---
name: container
description: Use dependency injection container to resolve services and manage dependencies. Use when retrieving services, implementing dependency injection, or managing class instances.
---

# Container Service

`CodeSoup\BoxNow\Core\Container` is exposed as `plugin()->container` (public property on `Plugin`).

## Retrieve services

```php
use function CodeSoup\BoxNow\plugin;

$hooker = plugin()->get( 'hooker' );
$assets = plugin()->get( 'assets' );
```

`plugin()->get( $alias )` is the usual shortcut. There is **no** `plugin()->get_container()` method.

## In service providers

```php
$this->container->get( 'order_service' );
```

Use protected helpers on `AbstractServiceProvider`:

- `singleton( $id, $concrete )` — shared instance
- `factory( $id, $concrete )` — new instance per resolution (wraps `bind`)

## Registered aliases (BoxNow plugin)

Common bindings from `Plugin::bind_services()` and providers:

| Alias | Type |
|-------|------|
| `hooker` | `Hooker` |
| `assets` | `Assets` |
| `i18n` | `I18n` |
| `settings` | `Settings_Service` |
| `locker_data` | `Locker_Data_Service` |
| `order_service` | `Order_Service` |
| `error_handler` | `Error_Handler` |

Feature-specific services (examples): `order_ajax_handler`, `delivery_service`, `parcel_service` — see providers under `includes/providers/`.

## Automatic constructor resolution

The container resolves type-hinted constructor parameters when registering a class name or closure that receives `$container`.

## Manual registration

```php
$this->singleton(
	'order_ajax_handler',
	function ( $container ) {
		return new Order_AJAX_Handler(
			$container->get( 'hooker' ),
			$container->get( 'delivery_service' ),
			$container->get( 'parcel_service' ),
			$container->get( 'order_service' )
		);
	}
);
```

## Rules

- Register services in service providers, not in arbitrary classes.
- Use `singleton()` for shared services.
- Prefer constructor injection over `plugin()->get()` inside deep business logic.
